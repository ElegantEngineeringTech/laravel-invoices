<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

expect()->extend('toCost', function (Money $money) {
    return expect((string) $this->value)->toBe((string) $money);
});
