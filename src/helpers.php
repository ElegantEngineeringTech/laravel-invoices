<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\Money;

function money(?Money $money, ?string $locale = null): ?string
{
    return $money?->formatToLocale($locale ?? app()->getLocale());
}

function color(int $index, int $seed = 0): string
{
    $hue = fmod(($index + $seed) * 137.508, 360);

    return sprintf('hsl(%.1f 80%% 55%%)', $hue);
}
