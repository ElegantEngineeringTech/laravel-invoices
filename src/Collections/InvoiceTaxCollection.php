<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\AllocationMode;
use Brick\Money\Money;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, InvoiceTax>
 */
class InvoiceTaxCollection extends Collection implements GOBLable
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
     * Mutate each tax's monetary amounts by scaling them.
     * Uses the configured rounding mode when none is provided.
     */
    public function multiplyBy(BigNumber|int|string $that, ?RoundingMode $roundingMode = null): static
    {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        return $this->each(function ($item) use ($roundingMode, $that) {
            $item->amount_taxable = $item->amount_taxable?->multipliedBy($that, $roundingMode);
            $item->amount = $item->amount?->multipliedBy($that, $roundingMode);
        });
    }

    public function allocate(
        Money $amount,
        AllocationMode $mode = AllocationMode::FloorToFirst,
        ?RoundingMode $roundingMode = null
    ): static {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        $ratios = $this->toBase()->map(fn ($tax) => abs($tax->amount?->getMinorAmount()->toInt() ?? 0))->all();

        $amounts = $amount->allocate($ratios, $mode);

        return $this->each(function ($tax, $index) use ($amounts) {
            $tax->amount_taxable = null;
            $tax->amount = $amounts[$index];
        });
    }

    public function denormalize(
        InvoiceItem|PdfInvoiceItem $item,
        bool $force = false,
    ): static {

        if (
            $item->price_subtotal === null ||
            $item->price_discount === null
        ) {
            return $this;
        }

        $roundingMode = InvoiceServiceProvider::getRoundingMode();

        $subtotal = $item->price_subtotal->minus($item->price_discount, $roundingMode);

        foreach ($this->items as $tax) {
            /**
             * percentage is the source of truth
             * we do not store it if not defined
             */
            if ($tax->percentage !== null) {
                if ($force || $tax->amount === null) {
                    $tax->amount = $subtotal->multipliedBy(
                        (string) ($tax->percentage / 100),
                        $roundingMode,
                    );
                }
            }
        }

        return $this;
    }

    public function toGOBL(array $values = []): array
    {
        return $this
            ->map(fn ($tax) => $tax->toGOBL($values))
            ->values()
            ->all();
    }
}
