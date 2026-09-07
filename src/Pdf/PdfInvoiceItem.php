<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceServiceProvider;

class PdfInvoiceItem
{
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

    public function denormalizeUnitPrice(bool $force = false): static
    {
        if ($this->unit_price === null || $force) {
            $this->unit_price = $this->price_subtotal?->dividedBy(
                (string) $this->quantity,
                InvoiceServiceProvider::getRoundingMode()
            );
        }

        return $this;
    }

    public function denormalizePriceSubtotal(bool $force = false): static
    {
        if ($this->price_subtotal === null || $force) {
            $this->price_subtotal = $this->unit_price?->multipliedBy(
                (string) $this->quantity,
                InvoiceServiceProvider::getRoundingMode()
            );
        }

        return $this;
    }

    public function denormalizePriceDiscount(bool $force = false): static
    {
        if ($this->price_discount === null || $force) {
            $this->price_discount = $this->discounts->denormalize($this, $force)->amount();
        }

        return $this;
    }

    public function denormalizePriceTax(bool $force = false): static
    {
        if ($this->price_tax === null || $force) {
            $this->price_tax = $this->taxes->denormalize($this, $force)->amount();
        }

        return $this;
    }

    public function denormalizePrice(bool $force = false): static
    {
        if ($this->price === null || $force) {
            $this->price = $this->price_subtotal?->minus($this->price_discount ?? 0)->plus($this->price_tax ?? 0);
        }

        return $this;
    }

    /**
     * Once set manually, prices are not updated
     */
    public function denormalize(bool $force = false): static
    {
        return $this
            ->denormalizeUnitPrice($force)
            ->denormalizePriceSubtotal($force)
            ->denormalizePriceDiscount($force)
            ->denormalizePriceTax($force)
            ->denormalizePrice($force);
    }
}
