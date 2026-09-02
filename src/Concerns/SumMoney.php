<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Concerns;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Elegantly\Invoices\InvoiceServiceProvider;

trait SumMoney
{
    public function sumMoney(
        string $column,
        ?RoundingMode $roundingMode = null,
    ): ?Money {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        $items = $this->where($column, '!=', null);

        if ($first = $items->shift()) {
            // @phpstan-ignore-next-line
            return $items->reduce(
                // @phpstan-ignore-next-line
                fn (Money $total, $item) => $total->plus(data_get($item, $column), $roundingMode),
                // @phpstan-ignore-next-line
                data_get($first, $column)
            );
        }

        return null;

    }
}
