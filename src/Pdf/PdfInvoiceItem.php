<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Money\MoneyParser;

/**
 * @phpstan-consistent-constructor
 *
 * @phpstan-type ItemData array{
 *     label?: ?string,
 *     currency?: ?string,
 *     unit_price?: Money|float|null,
 *     price_subtotal?: Money|float|null,
 *     price_discount?: Money|float|null,
 *     price_tax?: Money|float|null,
 *     price?: Money|float|null,
 *     quantity?: string|int|float,
 *     quantity_unit?: ?string,
 *     description?: ?string,
 *     discounts?: InvoiceDiscountCollection<InvoiceDiscount>|array<InvoiceDiscount|array{code?: ?string, label?: ?string, percentage?: ?float, amount?: Money|int|float|null, amount_subtotal?: Money|int|float|null, currency?: ?string}>,
 *     taxes?: InvoiceTaxCollection<InvoiceTax>|array<InvoiceTax|array{type?: ?string, country?: ?string, state?: ?string, taxability?: ?string, amount?: Money|int|float|null, currency?: ?string, amount_taxable?: Money|int|float|null, percentage?: ?float, label?: ?string}>
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
        $roundingMode = InvoiceServiceProvider::getRoundingMode();
        $currency = $data['currency'] ?? InvoiceServiceProvider::getDefaultCurrency();
        $discounts = $data['discounts'] ?? new InvoiceDiscountCollection;

        if (is_array($discounts)) {
            $discounts = new InvoiceDiscountCollection(array_map(function ($discount) use ($currency, $roundingMode) {
                if ($discount instanceof InvoiceDiscount) {
                    return $discount;
                }

                $discountCurrency = $discount['currency'] ?? $currency;

                return new InvoiceDiscount([
                    ...$discount,
                    'amount' => MoneyParser::parse($discount['amount'] ?? null, $discountCurrency, $roundingMode),
                    'amount_subtotal' => MoneyParser::parse($discount['amount_subtotal'] ?? null, $discountCurrency, $roundingMode),
                ]);
            }, $discounts));
        }

        $taxes = $data['taxes'] ?? new InvoiceTaxCollection;

        if (is_array($taxes)) {
            $taxes = new InvoiceTaxCollection(array_map(function ($tax) use ($currency, $roundingMode) {
                if ($tax instanceof InvoiceTax) {
                    return $tax;
                }

                $taxCurrency = $tax['currency'] ?? $currency;

                return new InvoiceTax([
                    ...$tax,
                    'amount' => MoneyParser::parse($tax['amount'] ?? null, $taxCurrency, $roundingMode),
                    'amount_taxable' => MoneyParser::parse($tax['amount_taxable'] ?? null, $taxCurrency, $roundingMode),
                ]);
            }, $taxes));
        }

        return new static(
            label: $data['label'] ?? null,
            unit_price: MoneyParser::parse($data['unit_price'] ?? null, $currency, $roundingMode),
            price_subtotal: MoneyParser::parse($data['price_subtotal'] ?? null, $currency, $roundingMode),
            price_discount: MoneyParser::parse($data['price_discount'] ?? null, $currency, $roundingMode),
            price_tax: MoneyParser::parse($data['price_tax'] ?? null, $currency, $roundingMode),
            price: MoneyParser::parse($data['price'] ?? null, $currency, $roundingMode),
            quantity: (float) ($data['quantity'] ?? 1),
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

            if ($this->unit_price !== null) {
                $currency = $this->unit_price->getCurrency();

                $this->price_discount ??= Money::zero($currency);
            }
        }

        if ($force || $this->price_tax === null) {
            $this->price_tax = $this->taxes
                ->denormalize($this, $force)
                ->amount();

            if ($this->unit_price !== null) {
                $currency = $this->unit_price->getCurrency();

                $this->price_tax ??= Money::zero($currency);
            }
        }

        if ($force || $this->price === null) {

            $this->price = $this->price_subtotal;

            if ($this->price_discount) {
                $this->price = $this->price?->minus($this->price_discount);
            }

            if ($this->price_tax) {
                $this->price = $this->price?->plus($this->price_tax);
            }

        }

        return $this;
    }
}
