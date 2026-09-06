<?php

declare(strict_types=1);

use Brick\Money\Money;
use Carbon\Carbon;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Invoices\Support\Address;
use Elegantly\Invoices\Support\Identity;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;
use Elegantly\Invoices\Support\TaxId;
use Illuminate\Support\Facades\Route;

$discount10 = new InvoiceDiscount(
    code: 'CODE10',
    percentage: 10,
);

$discount20 = new InvoiceDiscount(
    code: 'CODE20',
    percentage: 20,
);

$tax20 = new InvoiceTax(
    type: 'vat',
    taxability: 'standard',
    percentage: 20,
);

$invoice = new PdfInvoice(
    type: InvoiceType::Invoice,
    state: InvoiceState::Draft,
    serial_number: 'INV-0032/001',
    created_at: Carbon::create(2025, 1, 25),
    due_at: Carbon::create(2025, 2, 25),
    paid_at: Carbon::create(2025, 1, 26),
    fields: [
        'BDC' => 'BD01-7659',
    ],
    logo: 'https://avatars.githubusercontent.com/u/170185760?s=400&u=becdedf9606e6a80ea4831e8fc5cac301763368a&v=4',
    seller: new Party(
        company: 'Elegantly',
        address: new Address(
            street: "9 rue Geoffroy l'Angevin",
            postal_code: '75004',
            city: 'Paris',
            state: 'Île-de-France',
            country: 'France',
        ),
        email: 'support@example.com',
        phone: '069547XXXX',
        tax_id: new TaxId(
            country: 'FR',
            code: '88897962361'
        ),
        identities: [
            new Identity(
                type: 'SIREN',
                code: '897962361'
            ),
        ],
        fields: [
            'a custom field',
        ],
    ),
    buyer: new Party(
        company: 'Company & Co',
        name: 'Wile E. Coyote',
        address: new Address(
            street : '8 Allée Du Sequoia',
            postal_code : '77400',
            city : 'Lagny-sur-Marne',
            country : 'France',
        ),
        shipping_address: new Address(
            company: 'Company & Co',
            name : 'John Doe',
            street : [
                '8 Allée Du Sequoia',
                'Apt 1.',
            ],
            postal_code : '77400',
            city : 'Lagny-sur-Marne',
            country : 'France',
        ),
        tax_id: new TaxId(
            country: 'FR',
            code: '15948344072'
        ),
        email: 'john.doe@example.com',
    ),
    items: new PdfInvoiceItemCollection([
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Jan 1 – Jan 30',
            unit_price: Money::of(50, 'EUR'),
            quantity: 1,
            discounts: new InvoiceDiscountCollection([$discount10])->clone(),
            taxes: new InvoiceTaxCollection([$tax20])->clone(),
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 1 – Feb 18',
            unit_price: Money::of(50, 'EUR'),
            quantity: 1,
            discounts: new InvoiceDiscountCollection([$discount10, $discount20])->clone(),
            taxes: new InvoiceTaxCollection([$tax20])->clone(),
        ),
    ]),
    description: 'A simple description',
    paymentInstructions: [
        new PaymentInstruction(
            name: 'Bank Transfer',
            description: 'Make a direct bank transfer using the details below',
            qrcode: 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../resources/images/qrcode.png')),
            fields: [
                'Bank Name' => 'Acme Bank',
                'Account Number' => '12345678',
                'IBAN' => 'GB12ACME12345678123456',
                'SWIFT/BIC' => 'ACMEGB2L',
                'Reference' => 'INV-0032/001',
                '<a href="#">Pay online</a>',
            ],
        ),
    ]
);

Route::get('/', function () use ($invoice) {
    return view('demo', [
        'invoice' => $invoice->denormalize(),
    ]);
});

Route::get('/pdf', function () use ($invoice) {
    return $invoice->denormalize()->stream();
});
