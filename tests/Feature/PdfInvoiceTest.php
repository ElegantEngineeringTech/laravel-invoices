<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

it('computes the right amounts', function (
    $items,
    $taxPercentage,
    $discounPercentage,
    $subtotalAmount,
    $totalDiscountAmount,
    $totalTaxAmount,
    $totalAmount
) {
    $pdfInvoice = new PdfInvoice(
        type: InvoiceType::Invoice,
        state: InvoiceState::Paid,
        serial_number: 'FAKE-INVOICE-01',
        due_at: now(),
        created_at: now(),
        items: array_map(
            fn ($item) => new PdfInvoiceItem(
                label: 'Item 1',
                unit_price: Money::of($item['unit_price'], 'USD'),
                quantity: $item['quantity'] ?? 1,
                tax_percentage: $taxPercentage
            ),
            $items
        ),
        discounts: [
            new InvoiceDiscount(
                percent_off: $discounPercentage
            ),
        ]
    );

    expect($pdfInvoice->subTotalAmount()->getAmount()->toFloat())->toEqual($subtotalAmount);
    expect($pdfInvoice->totalDiscountAmount()->getAmount()->toFloat())->toEqual($totalDiscountAmount);
    expect($pdfInvoice->totalTaxAmount()->getAmount()->toFloat())->toEqual($totalTaxAmount);
    expect($pdfInvoice->totalAmount()->getAmount()->toFloat())->toEqual($totalAmount);
})->with([
    [[], 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
    [[['unit_price' => 0.0]], 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
    [[['unit_price' => 0.0]], 10.0, 0.0, 0.0, 0.0, 0.0, 0.0],
    [[['unit_price' => 0.0]], 10.0, 10.0, 0.0, 0.0, 0.0, 0.0],
    [[['unit_price' => 100.0]], 0.0, 0.0, 100.0, 0.0, 0.0, 100.0],
    [[['unit_price' => 100.0, 'quantity' => 2]], 0.0, 0.0, 200.0, 0.0, 0.0, 200.0],
    [[['unit_price' => 100.0, 'quantity' => 0.1]], 0.0, 0.0, 10.0, 0.0, 0.0, 10.0],
    [[['unit_price' => 100.0]], 20.0, 0.0, 100.0, 0.0, 20.0, 120.0],
    [[['unit_price' => 100.0]], 0.0, 10.0, 100.0, 10.0, 0.0, 90.0],
    [[['unit_price' => 100.0]], 20.0, 10.0, 100.0, 10.0, 18.0, 108.0],
    [[['unit_price' => 100.0], ['unit_price' => 50.0]], 0.0, 0.0, 150.0, 0.0, 0.0, 150.0],
    [[['unit_price' => 100.0], ['unit_price' => 50.0]], 20.0, 0.0, 150.0, 0.0, 30.0, 180.0],
    [[['unit_price' => 100.0], ['unit_price' => 50.0]], 20.0, 10.0, 150.0, 15.0, 27.0, 162.0],
    [[['unit_price' => 50.0, 'quantity' => 2], ['unit_price' => 50.0]], 20.0, 10.0, 150.0, 15.0, 27.0, 162.0],
    [[['unit_price' => 1_000.0, 'quantity' => 0.1], ['unit_price' => 50.0]], 20.0, 10.0, 150.0, 15.0, 27.0, 162.0],
    [[['unit_price' => -100.0]], 0.0, 0.0, -100.0, 0.0, 0.0, -100.0],
    [[['unit_price' => -100.0, 'quantity' => 2]], 0.0, 0.0, -200.0, 0.0, 0.0, -200.0],
    [[['unit_price' => -100.0, 'quantity' => 0.1]], 0.0, 0.0, -10.0, 0.0, 0.0, -10.0],
    [[['unit_price' => -100.0], ['unit_price' => -50.0]], 20.0, 10.0, -150.0, -15.0, -27.0, -162.0],
]);
