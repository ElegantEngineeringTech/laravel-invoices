<?php

declare(strict_types=1);

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;

it('[PdfInvoice] converts float amounts using its currency', function () {
    $money = Money::of('12.34', 'USD');

    $invoice = PdfInvoice::make([
        'currency' => 'USD',
        'subtotal_amount' => 12.34,
        'discount_amount' => 1.25,
        'tax_amount' => 2.5,
        'total_amount' => $money,
        'items' => [['unit_price' => 12.34]],
    ]);

    expect($invoice->subtotal_amount)->toCost($money)
        ->and($invoice->discount_amount)->toCost(Money::of('1.25', 'USD'))
        ->and($invoice->tax_amount)->toCost(Money::of('2.50', 'USD'))
        ->and($invoice->total_amount)->toBe($money)
        ->and($invoice->items->first()->unit_price)->toCost($money);
});

it('[PdfInvoice] requires a currency for float amounts', function () {
    PdfInvoice::make(['total_amount' => 10.5]);
})->throws(InvalidArgumentException::class);

it('[PdfInvoice] can denormalize amounts', function () {
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

    expect($invoice->subtotal_amount)->toCost(Money::of(100, 'EUR'));
    expect($invoice->discount_amount)->toCost(Money::of(10, 'EUR'));
    expect($invoice->tax_amount)->toCost(Money::of(18, 'EUR'));
    expect($invoice->total_amount)->toCost(Money::of(108, 'EUR'));

});

it('[PdfInvoice] makes an invoice from data', function () {
    $createdAt = now();
    $unitPrice = Money::of(40, 'EUR');
    $total = Money::of(48, 'EUR');

    $invoice = PdfInvoice::make([
        'type' => 'Receipt',
        'state' => 'paid',
        'serial_number' => 'INV-001',
        'created_at' => $createdAt,
        'due_at' => $createdAt,
        'paid_at' => $createdAt,
        'fields' => ['Order' => '123'],
        'seller' => ['company' => 'Example Ltd'],
        'buyer' => new Party(name: 'Jane Doe'),
        'items' => [[
            'label' => 'Service',
            'unit_price' => $unitPrice,
            'taxes' => [['percentage' => 20.0, 'amount' => Money::of(8, 'EUR')]],
            'discounts' => [['percentage' => 10.0]],
        ]],
        'subtotal_amount' => $unitPrice,
        'discount_amount' => Money::zero('EUR'),
        'tax_amount' => Money::of(8, 'EUR'),
        'total_amount' => $total,
        'description' => 'Thank you',
        'paymentInstructions' => [['name' => 'Bank transfer']],
        'template' => 'custom',
        'templateData' => ['color' => 'blue'],
        'logo' => '/tmp/logo.png',
    ]);

    expect($invoice->type)->toBe('Receipt')
        ->and($invoice->state)->toBe('paid')
        ->and($invoice->serial_number)->toBe('INV-001')
        ->and($invoice->created_at)->toBe($createdAt)
        ->and($invoice->due_at)->toBe($createdAt)
        ->and($invoice->paid_at)->toBe($createdAt)
        ->and($invoice->fields)->toBe(['Order' => '123'])
        ->and($invoice->seller->company)->toBe('Example Ltd')
        ->and($invoice->buyer->name)->toBe('Jane Doe')
        ->and($invoice->items)->toBeInstanceOf(PdfInvoiceItemCollection::class)
        ->and($invoice->items->first())->toBeInstanceOf(PdfInvoiceItem::class)
        ->and($invoice->items->first()->unit_price)->toBe($unitPrice)
        ->and($invoice->items->first()->taxes->first())->toBeInstanceOf(InvoiceTax::class)
        ->and($invoice->items->first()->discounts->first())->toBeInstanceOf(InvoiceDiscount::class)
        ->and($invoice->subtotal_amount)->toBe($unitPrice)
        ->and($invoice->discount_amount)->toBeInstanceOf(Money::class)
        ->and($invoice->tax_amount)->toBeInstanceOf(Money::class)
        ->and($invoice->total_amount)->toBe($total)
        ->and($invoice->description)->toBe('Thank you')
        ->and($invoice->paymentInstructions[0])->toBeInstanceOf(PaymentInstruction::class)
        ->and($invoice->paymentInstructions[0]->name)->toBe('Bank transfer')
        ->and($invoice->template)->toBe('invoices::custom')
        ->and($invoice->templateData)->toBe(['color' => 'blue'])
        ->and($invoice->logo)->toBe('/tmp/logo.png');
});

it('[PdfInvoice] makes an invoice with constructor defaults', function () {
    $invoice = PdfInvoice::make([]);

    expect($invoice->type)->toBe(InvoiceType::Invoice)
        ->and($invoice->state)->toBe(InvoiceState::Draft)
        ->and($invoice->seller)->toBeInstanceOf(Party::class)
        ->and($invoice->buyer)->toBeInstanceOf(Party::class)
        ->and($invoice->items)->toBeInstanceOf(PdfInvoiceItemCollection::class)
        ->and($invoice->paymentInstructions)->toBe([]);
});
