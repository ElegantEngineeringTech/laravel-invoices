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

    public function denormalize(
        InvoiceItem|PdfInvoiceItem $item,
        bool $force = false,
    ): static {
        if ($item->price_subtotal === null) {
            return $this;
        }

        $subtotal = $item->price_subtotal;

        foreach ($this->items as $discount) {

            if (
                $discount->percentage !== null &&
                ($discount->amount === null || $force)
            ) {
                $discount->amount = $subtotal->multipliedBy(
                    (string) ($discount->percentage / 100),
                    InvoiceServiceProvider::getRoundingMode()
                );
            }

            if (
                $discount->amount &&
                ($discount->percentage === null || $force)
            ) {
                if ($subtotal->isZero()) {
                    $discount->percentage = 0.0;
                } else {
                    $discount->percentage = $discount->amount
                        ->getAmount()
                        ->multipliedBy(100)
                        ->dividedBy(
                            $subtotal->getAmount(),
                            scale: 2,
                            roundingMode: InvoiceServiceProvider::getRoundingMode()
                        )
                        ->toFloat();
                }
            }

            if ($discount->amount) {
                $subtotal = $subtotal->minus($discount->amount);
            }
        }

        return $this;
    }
}
