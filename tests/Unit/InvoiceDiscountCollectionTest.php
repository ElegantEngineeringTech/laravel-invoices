<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\InvoiceDiscount;

it('Allocates amounts to discounts', function () {

    $discounts = new InvoiceDiscountCollection([
        $discount1 = new InvoiceDiscount(
            percentage: 50,
            amount: Money::of(50, 'EUR'),
            amount_subtotal: Money::of(100, 'EUR'),
        ),
        $discount2 = new InvoiceDiscount(
            percentage: 20,
            amount: Money::of(10, 'EUR'),
            amount_subtotal: Money::of(50, 'EUR'),
        ),
    ]);

    $discounts->allocate(
        Money::of(30, 'EUR'),
    );

    expect($discount1->amount)->toCost(Money::of(25, 'EUR'));
    expect($discount1->amount_subtotal)->toBe(null);

    expect($discount2->amount)->toCost(Money::of(5, 'EUR'));
    expect($discount2->amount_subtotal)->toBe(null);

});
