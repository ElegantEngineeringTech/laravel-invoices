<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\Eloquent\InvoiceItemCollection;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\InvoiceItem;

it('Allocates amounts to items', function () {

    $items = new InvoiceItemCollection([
        $item1 = new InvoiceItem([
            'unit_price' => Money::of(10, 'EUR'),
            'quantity' => 10,
            'price_subtotal' => Money::of(100, 'EUR'),
            'price_discount' => Money::of(10, 'EUR'),
            'price_tax' => Money::of(18, 'EUR'),
            'price' => Money::of(108, 'EUR'),
            'discounts' => new InvoiceDiscountCollection([
                new InvoiceDiscount([
                    'percentage' => 10,
                    'amount' => Money::of(10, 'EUR'),
                ]),
            ]),
            'taxes' => new InvoiceTaxCollection([
                new InvoiceTax([
                    'percentage' => 20,
                    'amount' => Money::of(18, 'EUR'),
                ]),
            ]),
        ]),
        $item2 = new InvoiceItem([
            'unit_price' => Money::of(10, 'EUR'),
            'quantity' => 10,
            'price_subtotal' => Money::of(100, 'EUR'),
            'price_discount' => Money::of(10, 'EUR'),
            'price_tax' => Money::of(18, 'EUR'),
            'price' => Money::of(108, 'EUR'),
            'discounts' => new InvoiceDiscountCollection([
                new InvoiceDiscount([
                    'percentage' => 10,
                    'amount' => Money::of(10, 'EUR'),
                ]),
            ]),
            'taxes' => new InvoiceTaxCollection([
                new InvoiceTax([
                    'percentage' => 20,
                    'amount' => Money::of(18, 'EUR'),
                ]),
            ]),
        ]),
    ]);

    $items->allocate(
        subtotal: Money::of(100, 'EUR'),
        discount: Money::of(10, 'EUR'),
        tax: Money::of(18, 'EUR'),
        total: Money::of(108, 'EUR'),
    );

    expect($item1->price_subtotal)->toCost(Money::of(50, 'EUR'));
    expect($item1->price_discount)->toCost(Money::of(5, 'EUR'));
    expect($item1->price_tax)->toCost(Money::of(9, 'EUR'));
    expect($item1->price)->toCost(Money::of(54, 'EUR'));
    expect($item1->unit_price)->toCost(Money::of(5, 'EUR'));

    expect($item2->price_subtotal)->toCost(Money::of(50, 'EUR'));
    expect($item2->price_discount)->toCost(Money::of(5, 'EUR'));
    expect($item2->price_tax)->toCost(Money::of(9, 'EUR'));
    expect($item2->price)->toCost(Money::of(54, 'EUR'));
    expect($item2->unit_price)->toCost(Money::of(5, 'EUR'));

})->only();
