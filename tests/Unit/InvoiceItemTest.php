<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\InvoiceItem;

it('[InvoiceItem] does denormalize an item without discounts or taxes', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
    ]);

    $item->denormalize();

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(0, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(0, 'EUR'));
    expect($item->price)->toCost(Money::of(100, 'EUR'));

});

it('[InvoiceItem] does denormalize an item with discounts', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'discounts' => new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 10.0,
                amount: Money::of(10, 'EUR'),
            ),
        ]),
    ]);

    $item->denormalize();

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(10, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(0, 'EUR'));
    expect($item->price)->toCost(Money::of(90, 'EUR'));

    expect($discount1->percentage)->toBe(10.0);
    expect($discount1->amount)->toCost(Money::of(10, 'EUR'));
});

it('[InvoiceItem] does denormalize an item with taxes', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'taxes' => new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 20.0,
                amount: Money::of(20, 'EUR'),
            ),
        ]),
    ]);

    $item->denormalize();

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(0, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(20, 'EUR'));
    expect($item->price)->toCost(Money::of(120, 'EUR'));

    expect($tax1->percentage)->toBe(20.0);
    expect($tax1->amount)->toCost(Money::of(20, 'EUR'));
});

it('[InvoiceItem] does not override values when denormalizing', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'price_subtotal' => Money::of(1_111, 'EUR'),
        'price_discount' => Money::of(2_222, 'EUR'),
        'price_tax' => Money::of(3_333, 'EUR'),
        'price' => Money::of(4_444, 'EUR'),
        'discounts' => new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 5_000.0,
                amount: Money::of(5_555, 'EUR'),
            ),
        ]),
        'taxes' => new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 6_000.0,
                amount: Money::of(6_666, 'EUR'),
            ),
        ]),
    ]);

    $item->denormalize();

    expect($item->price_subtotal)->toCost(Money::of(1_111, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(2_222, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(3_333, 'EUR'));
    expect($item->price)->toCost(Money::of(4_444, 'EUR'));

    expect($discount1->percentage)->toBe(5_000.0);
    expect($discount1->amount)->toCost(Money::of(5_555, 'EUR'));

    expect($tax1->percentage)->toBe(6_000.0);
    expect($tax1->amount)->toCost(Money::of(6_666, 'EUR'));

});

it('[InvoiceItem] overrides amounts when denormalizing with $force', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'price_subtotal' => Money::of(1_111, 'EUR'),
        'price_discount' => Money::of(2_222, 'EUR'),
        'price_tax' => Money::of(3_333, 'EUR'),
        'price' => Money::of(4_444, 'EUR'),
        'discounts' => new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 10.0,
                amount: Money::of(5_555, 'EUR'),
            ),
        ]),
        'taxes' => new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 20.0,
                amount: Money::of(6_666, 'EUR'),
            ),
        ]),
    ]);

    $item->denormalize(force: true);

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(10, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(18, 'EUR'));
    expect($item->price)->toCost(Money::of(108, 'EUR'));

    expect($discount1->percentage)->toBe(10.0);
    expect($discount1->amount)->toCost(Money::of(10, 'EUR'));

    expect($tax1->percentage)->toBe(20.0);
    expect($tax1->amount)->toCost(Money::of(18, 'EUR'));

});

it('[InvoiceItem] does denormalize amounts from percentages', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'discounts' => new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 10
            ),
            $discount2 = new InvoiceDiscount(
                percentage : 5
            ),
        ]),
        'taxes' => new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 20
            ),
            $tax2 = new InvoiceTax(
                percentage : 5
            ),
        ]),
    ]);

    $item->denormalize();

    expect($discount1->amount)->toCost(Money::of(10, 'EUR'));
    expect($discount2->amount)->toCost(Money::of('4.5', 'EUR'));

    expect($tax1->amount)->toCost(Money::of('17.1', 'EUR'));
    expect($tax2->amount)->toCost(Money::of('4.28', 'EUR'));

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of('14.5', 'EUR'));
    expect($item->price_tax)->toCost(Money::of('21.38', 'EUR'));
    expect($item->price)->toCost(Money::of('106.88', 'EUR'));

});

it('[InvoiceItem] does not denormalize percentages from amounts', function () {
    $item = new InvoiceItem([
        'unit_price' => Money::of(10, 'EUR'),
        'quantity' => 10,
        'discounts' => new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                amount: Money::of(10, 'EUR'),
            ),
            $discount2 = new InvoiceDiscount(
                amount: Money::of('4.5', 'EUR'),
            ),
        ]),
        'taxes' => new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                amount: Money::of('17.1', 'EUR'),
            ),
            $tax2 = new InvoiceTax(
                amount: Money::of('4.28', 'EUR'),
            ),
        ]),
    ]);

    $item->denormalize();

    expect($discount1->percentage)->toBe(null);
    expect($discount2->percentage)->toBe(null);

    expect($tax1->percentage)->toBe(null);
    expect($tax2->percentage)->toBe(null);

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of('14.5', 'EUR'));
    expect($item->price_tax)->toCost(Money::of('21.38', 'EUR'));
    expect($item->price)->toCost(Money::of('106.88', 'EUR'));

});
