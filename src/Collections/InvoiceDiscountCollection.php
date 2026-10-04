<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\AllocationMode;
use Brick\Money\Money;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @template TValue of InvoiceDiscount
 *
 * @extends Collection<int, TValue>
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

    /**
     * Mutate each discount's monetary amounts by scaling them.
     * Uses the configured rounding mode when none is provided.
     */
    public function multiplyBy(BigNumber|int|string $that, ?RoundingMode $roundingMode = null): static
    {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        return $this->each(function ($item) use ($roundingMode, $that) {
            $item->amount_subtotal = $item->amount_subtotal?->multipliedBy($that, $roundingMode);
            $item->amount = $item->amount?->multipliedBy($that, $roundingMode);
        });
    }

    public function allocate(
        Money $amount,
        AllocationMode $mode = AllocationMode::FloorToFirst,
        ?RoundingMode $roundingMode = null
    ): static {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        $ratios = $this
            ->toBase()
            ->map(fn ($discount) => abs($discount->amount?->getMinorAmount()->toInt() ?? 0))
            ->all();

        $amounts = $amount->allocate($ratios, $mode);

        return $this->each(function ($discount, $index) use ($amounts) {
            $discount->amount_subtotal = null;
            $discount->amount = $amounts[$index];
        });
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

    public function group(): static
    {
        $class = InvoiceServiceProvider::getInvoiceDiscountClass();

        $index = -1;

        $discounts = $this
            ->toBase()
            ->groupBy(fn ($discount) => implode('|', [$discount->code, $discount->percentage]))
            ->map(function ($discounts, $group) use ($class, &$index) {
                $index++;
                [$code, $percentage] = explode('|', $group);

                $discounts->each(function ($discount) use ($index) {
                    $discount->setIndex($index);
                });

                return new $class(
                    code: $code ?: null,
                    percentage: $percentage ? (float) $percentage : null,
                    amount: static::make($discounts)->amount(),
                )->setIndex($index);
            });

        // @phpstan-ignore-next-line
        return static::make($discounts);
    }

    public function toGOBL(array $values = []): array
    {
        return $this
            ->map(fn ($discount) => $discount->toGOBL($values))
            ->values()
            ->all();
    }
}
