<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Money\Money;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, InvoiceDiscount>
 */
class InvoiceDiscountCollection extends Collection
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

        $this->each(function ($discount) use ($item) {
            if ($item->price_subtotal === null) {
                return;
            }

            if ($discount->amount === null && $discount->percentage) {
                $discount->amount = $item->price_subtotal->multipliedBy(
                    (string) ($discount->percentage / 100),
                    InvoiceServiceProvider::getRoundingMode()
                );
            }

            if ($discount->percentage === null && $discount->amount) {
                $discount->percentage = $discount->amount
                    ->getMinorAmount()
                    ->multipliedBy(100)
                    ->dividedBy(
                        $item->price_subtotal->getMinorAmount(),
                        scale: 2,
                        roundingMode: InvoiceServiceProvider::getRoundingMode()
                    )
                    ->toFloat();
            }
        });

        return $this;
    }
}
