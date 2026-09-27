<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Math\BigRational;
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
            if ($force || $discount->subtotal === null) {
                $discount->subtotal = $subtotal;
            }

            // percentage is the source of truth
            if ($discount->percentage !== null) {
                if ($force || $discount->amount === null) {
                    $discount->amount = $discount->subtotal->multipliedBy(
                        (string) ($discount->percentage / 100.0),
                        $roundingMode,
                    );
                }
            } elseif ($discount->amount !== null) {
                if ($discount->subtotal->isZero()) {
                    $discount->percentage = 0.0;
                } else {
                    $discount->percentage = BigRational::ofFraction(
                        $discount->amount->getMinorAmount(),
                        $discount->subtotal->getMinorAmount(),
                    )
                        ->multipliedBy(100)
                        ->toScale(2, $roundingMode)
                        ->toFloat();
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
