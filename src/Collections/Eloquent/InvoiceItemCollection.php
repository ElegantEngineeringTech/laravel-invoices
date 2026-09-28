<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections\Eloquent;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, InvoiceItem>
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
