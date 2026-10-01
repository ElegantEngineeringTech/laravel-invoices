<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;

/**
 * @phpstan-consistent-constructor
 *
 * @phpstan-type ItemData array{
 *     label?: ?string, unit_price?: ?Money, price_subtotal?: ?Money,
 *     price_discount?: ?Money, price_tax?: ?Money, price?: ?Money,
 *     quantity?: int|float, quantity_unit?: ?string, description?: ?string,
 *     discounts?: InvoiceDiscountCollection<InvoiceDiscount>|array<InvoiceDiscount|array{code?: ?string, label?: ?string, percentage?: ?float, amount?: Money|int|null, amount_subtotal?: Money|int|null, currency?: ?string}>,
 *     taxes?: InvoiceTaxCollection<InvoiceTax>|array<InvoiceTax|array{type?: ?string, country?: ?string, state?: ?string, taxability?: ?string, amount?: Money|int|null, currency?: ?string, amount_taxable?: Money|int|null, percentage?: ?float, label?: ?string}>
 * }
 */
class PdfInvoiceItem
{
    /**
     * @param  InvoiceDiscountCollection<InvoiceDiscount>  $discounts
     * @param  InvoiceTaxCollection<InvoiceTax>  $taxes
     */
    public function __construct(
        public ?string $label = null,
        public ?Money $unit_price = null,
        public ?Money $price_subtotal = null,
        public ?Money $price_discount = null,
        public ?Money $price_tax = null,
        public ?Money $price = null,
        public int|float $quantity = 1,
        public ?string $quantity_unit = null,
        public ?string $description = null,
        public InvoiceDiscountCollection $discounts = new InvoiceDiscountCollection,
        public InvoiceTaxCollection $taxes = new InvoiceTaxCollection,
    ) {
        //
    }

    /**
     * @param  ItemData  $data
     */
    public static function make(array $data): static
    {
        $discounts = $data['discounts'] ?? new InvoiceDiscountCollection;

        if (is_array($discounts)) {
            $discounts = new InvoiceDiscountCollection(array_map(
                fn ($discount) => $discount instanceof InvoiceDiscount ? $discount : new InvoiceDiscount($discount),
                $discounts
            ));
        }

        $taxes = $data['taxes'] ?? new InvoiceTaxCollection;

        if (is_array($taxes)) {
            $taxes = new InvoiceTaxCollection(array_map(
                fn ($tax) => $tax instanceof InvoiceTax ? $tax : new InvoiceTax($tax),
                $taxes
            ));
        }

        return new static(
            label: $data['label'] ?? null,
            unit_price: $data['unit_price'] ?? null,
            price_subtotal: $data['price_subtotal'] ?? null,
            price_discount: $data['price_discount'] ?? null,
            price_tax: $data['price_tax'] ?? null,
            price: $data['price'] ?? null,
            quantity: $data['quantity'] ?? 1,
            quantity_unit: $data['quantity_unit'] ?? null,
            description: $data['description'] ?? null,
            discounts: $discounts,
            taxes: $taxes,
        );
    }

    public function denormalize(bool $force = false): static
    {
        $roundingMode = InvoiceServiceProvider::getRoundingMode();

        // unit_price is the source of truth
        if ($this->unit_price !== null) {
            if ($force || $this->price_subtotal === null) {
                $this->price_subtotal = $this->unit_price->multipliedBy((string) $this->quantity, $roundingMode);
            }
        } elseif ($this->price_subtotal !== null) {
            $this->unit_price = $this->price_subtotal->dividedBy((string) $this->quantity, $roundingMode);
        }

        if ($force || $this->price_discount === null) {
            $this->price_discount = $this->discounts
                ->denormalize($this, $force)
                ->amount();
        }

        if ($force || $this->price_tax === null) {
            $this->price_tax = $this->taxes
                ->denormalize($this, $force)
                ->amount();
        }

        if ($this->unit_price !== null) {
            $currency = $this->unit_price->getCurrency();

            $this->price_discount ??= Money::zero($currency);
            $this->price_tax ??= Money::zero($currency);

            if ($force || $this->price === null) {
                $this->price = $this->price_subtotal
                    ->minus($this->price_discount)
                    ->plus($this->price_tax);
            }
        }

        return $this;
    }
}
