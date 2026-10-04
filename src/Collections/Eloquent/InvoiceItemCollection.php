<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections\Eloquent;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\AllocationMode;
use Brick\Money\Money;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TModel of InvoiceItem
 *
 * @extends Collection<TKey, TModel>
 */
class InvoiceItemCollection extends Collection implements GOBLable
{
    use SumMoney;

    public function denormalize(bool $force = false): static
    {
        return $this->each(function ($item) use ($force) {
            $item->denormalize($force);
        });
    }

    /**
     * @param  null|string[]  $except
     */
    public function replicate(?array $except = null): static
    {
        // @phpstan-ignore-next-line
        return $this->map(fn ($item) => $item->replicate($except));
    }

    public function allocate(
        Money $subtotal,
        Money $tax,
        Money $discount,
        Money $total,
        AllocationMode $mode = AllocationMode::FloorToFirst,
        ?RoundingMode $roundingMode = null
    ): static {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        $ratiosSubtotal = $this
            ->toBase()
            ->map(fn ($item) => abs($item->price_subtotal?->getMinorAmount()->toInt() ?? 0));

        $ratiosDiscount = $this
            ->toBase()
            ->map(fn ($item) => abs($item->price_discount?->getMinorAmount()->toInt() ?? 0));

        $ratiosTax = $this
            ->toBase()
            ->map(fn ($item) => abs($item->price_tax?->getMinorAmount()->toInt() ?? 0));

        $ratiosPrice = $this
            ->toBase()
            ->map(fn ($item) => abs($item->price?->getMinorAmount()->toInt() ?? 0));

        $subtotals = $subtotal->isZero() ? array_fill(0, $ratiosSubtotal->count(), $subtotal) : $subtotal->allocate($ratiosSubtotal->all(), $mode);
        $discounts = $discount->isZero() ? array_fill(0, $ratiosDiscount->count(), $discount) : $discount->allocate($ratiosDiscount->all(), $mode);
        $taxes = $tax->isZero() ? array_fill(0, $ratiosTax->count(), $tax) : $tax->allocate($ratiosTax->all(), $mode);
        $prices = $total->isZero() ? array_fill(0, $ratiosPrice->count(), $total) : $total->allocate($ratiosPrice->all(), $mode);

        return $this->each(function ($item, $index) use ($discounts, $prices, $subtotals, $roundingMode, $taxes) {

            $item->unit_price = $subtotals[$index]->dividedBy($item->quantity, $roundingMode);
            $item->price_subtotal = $subtotals[$index];
            $item->price_discount = $discounts[$index];
            $item->price_tax = $taxes[$index];
            $item->price = $prices[$index];

            $item->discounts?->allocate($item->price_discount);
            $item->taxes?->allocate($item->price_tax);

        });
    }

    /**
     * Mutate each item's monetary amounts, discounts, and taxes by scaling them.
     * Uses the configured rounding mode when none is provided.
     */
    public function multiplyBy(BigNumber|int|string $that, ?RoundingMode $roundingMode = null): static
    {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        return $this->each(function ($item) use ($roundingMode, $that) {

            $item->discounts?->multiplyBy($that, $roundingMode);
            $item->taxes?->multiplyBy($that, $roundingMode);

            $item->unit_price = $item->unit_price?->multipliedBy($that, $roundingMode);
            $item->price_subtotal = $item->price_subtotal?->multipliedBy($that, $roundingMode);
            $item->price_discount = $item->price_discount?->multipliedBy($that, $roundingMode);
            $item->price_tax = $item->price_tax?->multipliedBy($that, $roundingMode);
            $item->price = $item->price?->multipliedBy($that, $roundingMode);

        });
    }

    public function toPdfItems(): PdfInvoiceItemCollection
    {
        return new PdfInvoiceItemCollection(array_map(
            fn ($item) => $item->toPdfInvoiceItem(),
            $this->all()
        ));
    }

    public function toGOBL(array $values = []): array
    {
        return $this->map(fn ($item) => $item->toGOBL($values))->values()->all();
    }
}
