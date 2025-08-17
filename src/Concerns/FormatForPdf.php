<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Concerns;

use Brick\Money\Money;
use Illuminate\Support\Facades\App;
use NumberFormatter;

trait FormatForPdf
{
    public function formatMoney(?Money $money = null, ?string $locale = null): ?string
    {
        return $money ? str_replace("\xe2\x80\xaf", ' ', $money->formatTo($locale ?? app()->getLocale())) : null;
    }

    public function formatPercentage(null|float|int $percentage, ?string $locale = null): string|false|null
    {
        if (! $percentage) {
            return null;
        }

        $formatter = new NumberFormatter($locale ?? App::getLocale(), NumberFormatter::PERCENT);

        return $formatter->format($percentage / 100);
    }
}
