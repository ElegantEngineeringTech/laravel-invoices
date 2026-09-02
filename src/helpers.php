<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\Money;

function money(?Money $money, ?string $locale = null): ?string
{
    return $money?->formatToLocale($locale ?? app()->getLocale());
}
