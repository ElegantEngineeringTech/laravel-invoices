<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceTax;

it('Allocates amounts to taxes', function () {

    $taxes = new InvoiceTaxCollection([
        $tax1 = new InvoiceTax(
            percentage: 50,
            amount: Money::of(50, 'EUR'),
            amount_taxable: Money::of(100, 'EUR'),
        ),
        $tax2 = new InvoiceTax(
            percentage: 20,
            amount: Money::of(10, 'EUR'),
            amount_taxable: Money::of(50, 'EUR'),
        ),
    ]);

    $taxes->allocate(
        Money::of(30, 'EUR'),
    );

    expect($tax1->amount)->toCost(Money::of(25, 'EUR'));
    expect($tax1->amount_taxable)->toBe(null);

    expect($tax2->amount)->toCost(Money::of(5, 'EUR'));
    expect($tax2->amount_taxable)->toBe(null);

})->only();
