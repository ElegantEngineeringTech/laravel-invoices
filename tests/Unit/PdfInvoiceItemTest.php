<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

it('[PdfInvoiceItem] converts float amounts using its currency', function () {
    $money = Money::of('12.34', 'EUR');

    $item = PdfInvoiceItem::make([
        'currency' => 'EUR',
        'unit_price' => 12.34,
        'price_subtotal' => 24.68,
        'price_discount' => 1.25,
        'price_tax' => 4.68,
        'price' => $money,
        'discounts' => [['amount' => 1.25, 'amount_subtotal' => 24.68]],
        'taxes' => [['amount' => 4.68, 'amount_taxable' => 23.43]],
    ]);

    expect($item->unit_price)->toCost($money)
        ->and($item->price_subtotal)->toCost(Money::of('24.68', 'EUR'))
        ->and($item->price_discount)->toCost(Money::of('1.25', 'EUR'))
        ->and($item->price_tax)->toCost(Money::of('4.68', 'EUR'))
        ->and($item->price)->toBe($money)
        ->and($item->discounts->first()->amount)->toCost(Money::of('1.25', 'EUR'))
        ->and($item->discounts->first()->amount_subtotal)->toCost(Money::of('24.68', 'EUR'))
        ->and($item->taxes->first()->amount)->toCost(Money::of('4.68', 'EUR'))
        ->and($item->taxes->first()->amount_taxable)->toCost(Money::of('23.43', 'EUR'));
});

it('[PdfInvoiceItem] requires a currency for float amounts', function () {
    PdfInvoiceItem::make(['unit_price' => 10.5]);
})->throws(InvalidArgumentException::class);

it('[PdfInvoiceItem] makes an item from data with nested discounts and taxes', function () {
    $discount = new InvoiceDiscount(amount: Money::of(2, 'EUR'));
    $tax = new InvoiceTax(amount: Money::of(4, 'EUR'));
    $unitPrice = Money::of(20, 'EUR');

    $item = PdfInvoiceItem::make([
        'label' => 'Service',
        'unit_price' => $unitPrice,
        'price_subtotal' => Money::of(40, 'EUR'),
        'price_discount' => Money::of(2, 'EUR'),
        'price_tax' => Money::of(4, 'EUR'),
        'price' => Money::of(42, 'EUR'),
        'quantity' => 2,
        'quantity_unit' => 'hours',
        'description' => 'Consulting',
        'discounts' => [$discount, ['percentage' => 5.0]],
        'taxes' => [$tax, ['percentage' => 10.0]],
    ]);

    expect($item->label)->toBe('Service')
        ->and($item->unit_price)->toBe($unitPrice)
        ->and($item->price_subtotal)->toCost(Money::of(40, 'EUR'))
        ->and($item->price_discount)->toCost(Money::of(2, 'EUR'))
        ->and($item->price_tax)->toCost(Money::of(4, 'EUR'))
        ->and($item->price)->toCost(Money::of(42, 'EUR'))
        ->and($item->quantity)->toBe(2)
        ->and($item->quantity_unit)->toBe('hours')
        ->and($item->description)->toBe('Consulting')
        ->and($item->discounts)->toBeInstanceOf(InvoiceDiscountCollection::class)
        ->and($item->discounts->first())->toBe($discount)
        ->and($item->discounts->last()->percentage)->toBe(5.0)
        ->and($item->taxes)->toBeInstanceOf(InvoiceTaxCollection::class)
        ->and($item->taxes->first())->toBe($tax)
        ->and($item->taxes->last()->percentage)->toBe(10.0);
});

it('[PdfInvoiceItem] makes an item with constructor defaults', function () {
    $item = PdfInvoiceItem::make([]);

    expect($item->quantity)->toBe(1)
        ->and($item->unit_price)->toBeNull()
        ->and($item->discounts)->toBeInstanceOf(InvoiceDiscountCollection::class)
        ->and($item->taxes)->toBeInstanceOf(InvoiceTaxCollection::class);
});

it('[PdfInvoiceItem] can denormalize a simple item without discounts or taxes', function () {
    $item = new PdfInvoiceItem(
        unit_price: Money::of(10, 'EUR'),
        quantity: 10,
    );

    $item->denormalize();

    expect($item->price_subtotal)->toCost(Money::of(100, 'EUR'));
    expect($item->price_discount)->toCost(Money::of(0, 'EUR'));
    expect($item->price_tax)->toCost(Money::of(0, 'EUR'));
    expect($item->price)->toCost(Money::of(100, 'EUR'));

});

it('[PdfInvoiceItem] does not override values when denormalizing', function () {
    $item = new PdfInvoiceItem(
        unit_price: Money::of(10, 'EUR'),
        quantity: 10,
        price_subtotal: Money::of(1_111, 'EUR'),
        price_discount: Money::of(2_222, 'EUR'),
        price_tax: Money::of(3_333, 'EUR'),
        price: Money::of(4_444, 'EUR'),
        discounts: new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 5_000.0,
                amount: Money::of(5_555, 'EUR'),
            ),
        ]),
        taxes: new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 6_000.0,
                amount: Money::of(6_666, 'EUR'),
            ),
        ]),
    );

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

it('[PdfInvoiceItem] overrides amounts when denormalizing with $force', function () {
    $item = new PdfInvoiceItem(
        unit_price: Money::of(10, 'EUR'),
        quantity: 10,
        price_subtotal: Money::of(1_111, 'EUR'),
        price_discount: Money::of(2_222, 'EUR'),
        price_tax: Money::of(3_333, 'EUR'),
        price: Money::of(4_444, 'EUR'),
        discounts: new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 10.0,
                amount: Money::of(5_555, 'EUR'),
            ),
        ]),
        taxes: new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 20.0,
                amount: Money::of(6_666, 'EUR'),
            ),
        ]),
    );

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

it('[PdfInvoiceItem] does denormalize amounts from percentages', function () {
    $item = new PdfInvoiceItem(
        unit_price: Money::of(10, 'EUR'),
        quantity: 10,
        discounts: new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                percentage : 10
            ),
            $discount2 = new InvoiceDiscount(
                percentage : 5
            ),
        ]),
        taxes: new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                percentage : 20
            ),
            $tax2 = new InvoiceTax(
                percentage : 5
            ),
        ]),
    );

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

it('[PdfInvoiceItem] does not denormalize percentages from amounts', function () {
    $item = new PdfInvoiceItem(
        unit_price: Money::of(10, 'EUR'),
        quantity: 10,
        discounts: new InvoiceDiscountCollection([
            $discount1 = new InvoiceDiscount(
                amount: Money::of(10, 'EUR'),
            ),
            $discount2 = new InvoiceDiscount(
                amount: Money::of('4.5', 'EUR'),
            ),
        ]),
        taxes: new InvoiceTaxCollection([
            $tax1 = new InvoiceTax(
                amount: Money::of('17.1', 'EUR'),
            ),
            $tax2 = new InvoiceTax(
                amount: Money::of('4.28', 'EUR'),
            ),
        ]),
    );

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
