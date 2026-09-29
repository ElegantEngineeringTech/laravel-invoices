<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\CurrencyDisplay;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;

function format_money(
    ?Money $money,
    ?string $locale = null,
    CurrencyDisplay $currencyDisplay = CurrencyDisplay::Symbol,
    bool $hideFractionIfWhole = false
): ?string {
    $locale ??= App::getLocale();

    $value = $money?->formatToLocale(
        locale: $locale,
        currencyDisplay: $currencyDisplay,
        hideFractionIfWhole: $hideFractionIfWhole
    );

    return str_replace(
        ["\u{202F}", "\u{00A0}"],
        ' ',
        $value
    );
}

function random_color(int $index, int $seed = 0): string
{
    $h = fmod(($index + $seed) * 137.508, 360);
    $s = 0.80;
    $l = 0.55;

    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;

    [$r, $g, $b] = match (true) {
        $h < 60 => [$c, $x, 0],
        $h < 120 => [$x, $c, 0],
        $h < 180 => [0, $c, $x],
        $h < 240 => [0, $x, $c],
        $h < 300 => [$x, 0, $c],
        default => [$c, 0, $x],
    };

    return sprintf(
        '#%02X%02X%02X',
        round(($r + $m) * 255),
        round(($g + $m) * 255),
        round(($b + $m) * 255),
    );
}
