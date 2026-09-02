<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Elegantly\Invoices\InvoiceServiceProvider;

class PdfInvoiceItem
{
    use FormatForPdf;

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
        $this->denormalize();
    }

    public function denormalizeUnitPrice(): static
    {
        if ($this->unit_price === null) {
            $this->unit_price = $this->price_subtotal?->dividedBy(
                (string) $this->quantity,
                InvoiceServiceProvider::getRoundingMode()
            );
        }

        return $this;
    }

    public function denormalizePriceSubtotal(): static
    {
        if ($this->price_subtotal === null) {
            $this->price_subtotal = $this->unit_price?->multipliedBy(
                (string) $this->quantity,
                InvoiceServiceProvider::getRoundingMode()
            );
        }

        return $this;
    }

    public function denormalizePriceDiscount(): static
    {
        if ($this->price_discount === null) {
            $this->price_discount = $this->discounts->denormalize($this)->amount();
        }

        return $this;
    }

    public function denormalizePriceTax(): static
    {
        if ($this->price_tax === null) {
            $this->price_tax = $this->taxes->denormalize($this)->amount();
        }

        return $this;
    }

    public function denormalizePrice(): static
    {
        if ($this->price === null) {
            $this->price = $this->price_subtotal?->minus($this->price_discount ?? 0)->plus($this->price_tax ?? 0);
        }

        return $this;
    }

    /**
     * Once set manually, prices are not updated
     */
    public function denormalize(): static
    {
        return $this
            ->denormalizeUnitPrice()
            ->denormalizePriceSubtotal()
            ->denormalizePriceDiscount()
            ->denormalizePriceTax()
            ->denormalizePrice();
    }
}
