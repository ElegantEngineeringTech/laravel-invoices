<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Money\Money;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, InvoiceTax>
 */
class InvoiceTaxCollection extends Collection
{
    use SumMoney;

    public function amount(): ?Money
    {
        return $this->sumMoney('amount');
    }

    public function clone(): static
    {
        return $this->map(fn ($item) => clone $item);
    }

    public function denormalize(InvoiceItem|PdfInvoiceItem $item): static
    {

        $this->each(function ($tax) use ($item) {
            if ($item->price_subtotal === null || $item->price_discount === null) {
                return;
            }

            $subtotal = $item->price_subtotal->minus(
                $item->price_discount,
                InvoiceServiceProvider::getRoundingMode()
            );

            if ($tax->amount === null && $tax->percentage) {
                $tax->amount = $subtotal->multipliedBy(
                    (string) ($tax->percentage / 100),
                    InvoiceServiceProvider::getRoundingMode()
                );
            }

            if ($tax->percentage === null && $tax->amount) {
                $tax->percentage = $tax->amount
                    ->getMinorAmount()
                    ->multipliedBy(100)
                    ->dividedBy(
                        $subtotal->getMinorAmount(),
                        scale: 2,
                        roundingMode: InvoiceServiceProvider::getRoundingMode()
                    )
                    ->toFloat();
            }
        });

        return $this;
    }
}
