<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Money;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Exception;

class PdfInvoiceItem
{
    use FormatForPdf;

    public Currency $currency;

    public function __construct(
        public ?string $label = null,
        public ?Money $unit_price = null,
        public ?Money $unit_tax = null,
        public ?float $tax_percentage = null,
        null|string|Currency $currency = null,
        public int|float $quantity = 1,
        public ?string $quantity_unit = null,
        public ?string $description = null,
    ) {
        if ($currency instanceof Currency) {
            $this->currency = $currency;
        } elseif ($currency) {
            $this->currency = Currency::of($currency);
        } elseif ($unit_price) {
            $this->currency = $unit_price->getCurrency();
        } elseif ($unit_tax) {
            $this->currency = $unit_tax->getCurrency();
        } else {
            $this->currency = Currency::of(config()->string('invoices.default_currency'));
        }

        if ($tax_percentage && ($tax_percentage > 100 || $tax_percentage < 0)) {
            throw new Exception("The tax_percentage parameter must be an integer between 0 and 100. {$tax_percentage} given.");
        }
    }

    public function subTotalAmount(): Money
    {
        if ($this->unit_price === null) {
            return Money::ofMinor(0, $this->currency);
        }

        return $this->unit_price->multipliedBy($this->quantity, RoundingMode::HALF_EVEN);
    }

    public function totalTaxAmount(): Money
    {
        if ($this->unit_tax) {
            return $this->unit_tax->multipliedBy($this->quantity, RoundingMode::HALF_EVEN);
        }

        if ($this->tax_percentage) {
            return $this->subTotalAmount()->multipliedBy($this->tax_percentage / 100.0, roundingMode: RoundingMode::HALF_EVEN);
        }

        return Money::ofMinor(0, $this->currency);
    }

    public function totalAmount(): Money
    {
        return $this->subTotalAmount()->plus($this->totalTaxAmount());
    }
}
