<?php

declare(strict_types=1);

use Brick\Money\Money;
use Carbon\Carbon;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Invoices\Support\Address;
use Elegantly\Invoices\Support\Buyer;
use Elegantly\Invoices\Support\Seller;
use Illuminate\Support\Facades\Route;

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
    seller: new Seller(
        name: 'Elegantly',
        address: new Address(
            street: "9 rue Geoffroy l'Angevin",
            postal_code: '75004',
            city: 'Paris',
            country: 'France',
        ),
        email: 'support@youdji.com',
        phone: '069547XXXX',
        tax_number: 'FR88897962361',
        fields: [
            'SIREN' => '897962361',
        ],
    ),
    buyer: new Buyer(
        name: 'CHASR',
        billing_address: new Address(
            street : '8 Allée Du Sequoia',
            postal_code : '77400',
            city : 'Lagny-sur-Marne',
            country : 'France',
        ),
        shipping_address: new Address(
            street : '8 Allée Du Sequoia',
            postal_code : '77400',
            city : 'Lagny-sur-Marne',
            country : 'France',
        ),
        tax_number: 'FR15948344072',
        email: 'heloise@chasr.io',
    ),
    items: [
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),

        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
        new PdfInvoiceItem(
            label: 'Casting Pro',
            description: 'Feb 20 – Mar 20, 2025',
            currency: 'EUR',
            unit_price: Money::of(97, 'EUR'),
            tax_percentage: 20,
        ),
    ],
    discounts: [
        new InvoiceDiscount(
            name: 'Discount',
            code: 'AEX45',
            percent_off: 20
        ),
    ],
    tax_label: 'VAT (France)'
);

Route::get('/', function () use ($invoice) {
    return view('demo', [
        'invoice' => $invoice,
    ]);
});

Route::get('/pdf', function () use ($invoice) {
    return $invoice->stream();
});
