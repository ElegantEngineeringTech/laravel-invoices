<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Concerns;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Elegantly\Invoices\InvoiceServiceProvider;

use function Elegantly\Money\sumMoney;

trait SumMoney
{
    public function sumMoney(
        string $column,
        ?RoundingMode $roundingMode = null,
    ): ?Money {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        return sumMoney($this->items, $column, $roundingMode);
    }
}
