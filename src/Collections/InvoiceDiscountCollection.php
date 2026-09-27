<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Money\Money;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, InvoiceDiscount>
 */
class InvoiceDiscountCollection extends Collection implements GOBLable
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

        $roundingMode = InvoiceServiceProvider::getRoundingMode();

        $subtotal = $item->price_subtotal;

        foreach ($this->items as $discount) {
            if ($force || $discount->amount_subtotal === null) {
                $discount->amount_subtotal = $subtotal;
            }

            /**
             * percentage is the source of truth
             * we do not store it if not defined
             */
            if ($discount->percentage !== null) {
                if ($force || $discount->amount === null) {
                    $discount->amount = $discount->amount_subtotal->multipliedBy(
                        (string) ($discount->percentage / 100.0),
                        $roundingMode,
                    );
                }
            }

            if ($discount->amount !== null) {
                $subtotal = $subtotal->minus($discount->amount, $roundingMode);
            }
        }

        return $this;
    }

    public function toGOBL(array $values = []): array
    {
        return $this
            ->map(fn ($discount) => $discount->toGOBL($values))
            ->values()
            ->all();
    }
}
