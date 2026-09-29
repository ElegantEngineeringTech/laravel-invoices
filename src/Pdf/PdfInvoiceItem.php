<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;

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
