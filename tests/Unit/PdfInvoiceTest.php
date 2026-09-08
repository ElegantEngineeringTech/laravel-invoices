<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

it('[PdfInvoice] can denormalize amounts from percentages', function () {
    $invoice = new PdfInvoice(
        items: new PdfInvoiceItemCollection([
            $item1 = new PdfInvoiceItem(
                unit_price: Money::of(40, 'EUR'),
                quantity: 1,
                discounts: new InvoiceDiscountCollection([
                    $discount1 = new InvoiceDiscount(
                        percentage : 10
                    ),
                ]),
                taxes: new InvoiceTaxCollection([
                    $tax1 = new InvoiceTax(
                        percentage : 20
                    ),
                ]),
            ),
            $item2 = new PdfInvoiceItem(
                unit_price: Money::of(60, 'EUR'),
                quantity: 1,
                discounts: new InvoiceDiscountCollection([
                    $discount2 = new InvoiceDiscount(
                        percentage : 10
                    ),
                ]),
                taxes: new InvoiceTaxCollection([
                    $tax2 = new InvoiceTax(
                        percentage : 20
                    ),
                ]),
            ),
        ])
    );

    $invoice->denormalize();

    expect($discount1->amount)->toCost(Money::of(4, 'EUR'));
    expect($tax1->amount)->toCost(Money::of('7.2', 'EUR'));

    expect($discount2->amount)->toCost(Money::of(6, 'EUR'));
    expect($tax2->amount)->toCost(Money::of('10.8', 'EUR'));

    expect($item1->price_subtotal)->toCost(Money::of(40, 'EUR'));
    expect($item1->price_discount)->toCost(Money::of(4, 'EUR'));
    expect($item1->price_tax)->toCost(Money::of('7.2', 'EUR'));

    expect($item2->price_subtotal)->toCost(Money::of(60, 'EUR'));
    expect($item2->price_discount)->toCost(Money::of(6, 'EUR'));
    expect($item2->price_tax)->toCost(Money::of('10.8', 'EUR'));

    expect($invoice->subtotal_amount)->toCost(Money::of(100, 'EUR'));
    expect($invoice->discount_amount)->toCost(Money::of(10, 'EUR'));
    expect($invoice->tax_amount)->toCost(Money::of(18, 'EUR'));
    expect($invoice->total_amount)->toCost(Money::of(108, 'EUR'));

});

it('[PdfInvoice] can denormalize percentages from amounts', function () {
    $invoice = new PdfInvoice(
        items: new PdfInvoiceItemCollection([
            $item1 = new PdfInvoiceItem(
                unit_price: Money::of(40, 'EUR'),
                quantity: 1,
                discounts: new InvoiceDiscountCollection([
                    $discount1 = new InvoiceDiscount(
                        amount: Money::of(4, 'EUR'),
                    ),
                ]),
                taxes: new InvoiceTaxCollection([
                    $tax1 = new InvoiceTax(
                        amount: Money::of('7.2', 'EUR'),
                    ),
                ]),
            ),
            $item2 = new PdfInvoiceItem(
                unit_price: Money::of(60, 'EUR'),
                quantity: 1,
                discounts: new InvoiceDiscountCollection([
                    $discount2 = new InvoiceDiscount(
                        amount: Money::of(6, 'EUR'),
                    ),
                ]),
                taxes: new InvoiceTaxCollection([
                    $tax2 = new InvoiceTax(
                        amount: Money::of('10.8', 'EUR'),
                    ),
                ]),
            ),
        ])
    );

    $invoice->denormalize();

    expect($discount1->percentage)->toBe(10.0);
    expect($tax1->percentage)->toBe(20.0);

    expect($discount2->percentage)->toBe(10.0);
    expect($tax2->percentage)->toBe(20.0);

    expect($item1->price_subtotal)->toCost(Money::of(40, 'EUR'));
    expect($item1->price_discount)->toCost(Money::of(4, 'EUR'));
    expect($item1->price_tax)->toCost(Money::of('7.2', 'EUR'));

    expect($item2->price_subtotal)->toCost(Money::of(60, 'EUR'));
    expect($item2->price_discount)->toCost(Money::of(6, 'EUR'));
    expect($item2->price_tax)->toCost(Money::of('10.8', 'EUR'));

    expect($invoice->subtotal_amount)->toCost(Money::of(100, 'EUR'));
    expect($invoice->discount_amount)->toCost(Money::of(10, 'EUR'));
    expect($invoice->tax_amount)->toCost(Money::of(18, 'EUR'));
    expect($invoice->total_amount)->toCost(Money::of(108, 'EUR'));

});
