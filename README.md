# Everything You Need to Manage Invoices in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Laravel Pint](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/pint.yml?label=laravel%20pint&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3Apint)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)

Create, store, calculate, and render invoices, quotes, proformas, and credit notes in Laravel. The package supports multiple item-level discounts and taxes, serial numbers, PDF generation, mail attachments, polymorphic relations, Stripe Checkout imports, and [GOBL](https://gobl.org/) export.

Amounts are **denormalized**: items and invoices hold their calculated amounts. Supply precomputed values from your application or payment provider, or call `denormalize()` to calculate missing amounts. Existing values are preserved unless you request recalculation.

There are two independent ways to use the package:

- **`PdfInvoice`**: build and render invoices without a database.
- **`Invoice`**: store invoices and their items with Eloquent, then optionally generate PDFs or export them.

![laravel-invoices](https://repository-images.githubusercontent.com/527661364/f98e92f9-62a6-48a1-a7b1-1a587b92a430)

## Interactive Demo

Try [the interactive demo](https://elegantly.dev/laravel-invoices) to explore the package.

## Table of Contents

- [Interactive Demo](#interactive-demo)
- [Requirements](#requirements)
- [Installation](#installation)
    - [Configuration](#configuration)
- [Upgrading from v5](#upgrading-from-v5)
- [The `PdfInvoice` Class](#the-pdfinvoice-class)
    - [Full Example](#full-example)
    - [Creating PDFs from Arrays](#creating-pdfs-from-arrays)
    - [PDF Amounts and Denormalization](#pdf-amounts-and-denormalization)
    - [PDF Discounts and Taxes](#pdf-discounts-and-taxes)
    - [Parties, Addresses, and Custom Fields](#parties-addresses-and-custom-fields)
    - [Types, States, and Dates](#types-states-and-dates)
    - [Payment Instructions and QR Codes](#payment-instructions-and-qr-codes)
    - [Rendering, Downloading, and Storing PDFs](#rendering-downloading-and-storing-pdfs)
        - [Livewire Downloads](#livewire-downloads)
        - [Accessing Dompdf](#accessing-dompdf)
    - [Rendering Blade Views](#rendering-blade-views)
    - [PDF Mail Attachments](#pdf-mail-attachments)
        - [Attaching PDFs to Mailables](#attaching-pdfs-to-mailables)
        - [Attaching PDFs to Notifications](#attaching-pdfs-to-notifications)
    - [PDF Customization and Localization](#pdf-customization-and-localization)
        - [Logos, Templates, and Template Data](#logos-templates-and-template-data)
        - [Fonts and PDF Options](#fonts-and-pdf-options)
        - [Localization and Money Formatting](#localization-and-money-formatting)
        - [Extending PdfInvoice](#extending-pdfinvoice)
- [The `Invoice` Eloquent Model](#the-invoice-eloquent-model)
    - [Complete Example](#complete-example)
    - [Stored Amounts and Denormalization](#stored-amounts-and-denormalization)
    - [Managing Invoice Items](#managing-invoice-items)
    - [Stored Discounts and Taxes](#stored-discounts-and-taxes)
    - [Stored Parties, Fields, and Payment Instructions](#stored-parties-fields-and-payment-instructions)
    - [Relations, Quotes, and Credits](#relations-quotes-and-credits)
    - [Types, States, and Query Scopes](#types-states-and-query-scopes)
    - [Generating Serial Numbers](#generating-serial-numbers)
        - [Multiple Prefixes and Series](#multiple-prefixes-and-series)
        - [Manual Serial Numbers and Parsing](#manual-serial-numbers-and-parsing)
    - [Storing Logos](#storing-logos)
    - [Converting Models to PDFs](#converting-models-to-pdfs)
        - [Livewire Downloads from a Model](#livewire-downloads-from-a-model)
        - [Customizing PDF Output from the Model](#customizing-pdf-output-from-the-model)
    - [Model Mail Attachments](#model-mail-attachments)
        - [Mailables](#mailables)
        - [Notifications](#notifications)
    - [Replicating, Scaling, and Allocating Amounts](#replicating-scaling-and-allocating-amounts)
        - [Replicating and Scaling](#replicating-and-scaling)
        - [Allocating Amounts](#allocating-amounts)
    - [Collections and Money Totals](#collections-and-money-totals)
    - [Importing Stripe Checkout Line Items](#importing-stripe-checkout-line-items)
    - [GOBL Export](#gobl-export)
    - [Custom Models and Value Objects](#custom-models-and-value-objects)
    - [Casting Types and States to Enums](#casting-types-and-states-to-enums)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Requirements

- PHP 8.4+
- Laravel 13.x
- `dompdf/dompdf` 3.1+
- `elegantly/laravel-money` 4.2+

The Stripe integration additionally requires `stripe/stripe-php`.

## Installation

```bash
composer require elegantly/laravel-invoices
```

For Eloquent storage, publish and run the migrations:

```bash
php artisan vendor:publish --tag="invoices-migrations"
php artisan migrate
```

Standalone `PdfInvoice` usage does not require migrations.

### Configuration

```bash
php artisan vendor:publish --tag="invoices-config"
```

Use `config/invoices.php` to configure your default seller, currency, rounding, serial numbers, and PDF appearance. See the [configuration file](config/invoices.php) for all settings and defaults.

## Upgrading from v5

See the [migration guide](UPGRADE.md) for breaking changes, database conversions, and examples for upgrading from `v5` to `v6`.

## The `PdfInvoice` Class

Build PDFs, Blade previews, and mail attachments from your own data, without database storage.

### Full Example

```php
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
use Elegantly\Invoices\Support\Address;
use Elegantly\Invoices\Support\Identity;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;
use Elegantly\Invoices\Support\TaxId;

$pdfInvoice = new PdfInvoice(
    type: InvoiceType::Invoice,
    state: InvoiceState::Pending,
    serial_number: 'INV-260001',
    created_at: now(),
    due_at: now()->addDays(30),
    fields: ['Order' => 'PO0234'],
    seller: new Party(
        company: 'Acme Studio',
        name: 'Jane Doe',
        address: new Address(
            street: "Place de l'Opéra",
            city: 'Paris',
            postal_code: '75009',
            country: 'FR',
        ),
        tax_id: new TaxId(country: 'FR', code: '123456789'),
        email: 'billing@example.com',
        phone: '+33 1 23 45 67 89',
        identities: [new Identity(type: 'SIREN', code: '732829320')],
        fields: ['Website' => 'https://example.com'],
    ),
    buyer: new Party(
        company: 'Doe Corporation',
        name: 'John Doe',
        address: new Address(
            street: '8405 Old James St',
            city: 'New York',
            postal_code: '14609',
            state: 'NY',
            country: 'US',
        ),
        shipping_address: new Address(
            street: ['8405 Old James St', 'Apartment 1'],
            city: 'New York',
            postal_code: '14609',
            state: 'NY',
            country: 'US',
        ),
        email: 'john.doe@example.com',
    ),
    items: new PdfInvoiceItemCollection([
        new PdfInvoiceItem(
            label: 'Consulting',
            unit_price: Money::of('100.00', 'EUR'),
            quantity: 2,
            quantity_unit: 'hours',
            description: 'Application development',
            discounts: new InvoiceDiscountCollection([
                new InvoiceDiscount(
                    code: 'WELCOME',
                    label: 'Welcome offer',
                    percentage: 10,
                ),
            ]),
            taxes: new InvoiceTaxCollection([
                new InvoiceTax(
                    type: 'vat',
                    country: 'FR',
                    percentage: 20,
                    label: 'VAT France (20%)',
                ),
            ]),
        ),
    ]),
    description: 'Thank you for your business.',
    paymentInstructions: [
        new PaymentInstruction(
            name: 'Bank transfer',
            description: 'Use the invoice number as the payment reference.',
            fields: [
                'IBAN' => 'GB12ACME12345678123456',
                'SWIFT/BIC' => 'ACMEGB2L',
                'Reference' => 'INV-260001',
            ],
        ),
    ],
    // Optional: logo: public_path('images/logo.png'),
    template: 'default.layout',
    templateData: ['font' => 'DejaVu Sans'],
);

$pdfInvoice->denormalize();

// Subtotal: EUR 200.00; discount: EUR 20.00;
// tax: EUR 36.00; total: EUR 216.00.

return $pdfInvoice->stream();
```

### Creating PDFs from Arrays

Use `PdfInvoice::make()` to build an invoice from nested arrays. Numeric amounts are in **major units**, using `currency` or `invoices.default_currency` (`USD` by default).

```php
use Elegantly\Invoices\Pdf\PdfInvoice;

$pdfInvoice = PdfInvoice::make([
    'type' => 'invoice',
    'state' => 'pending',
    'serial_number' => 'INV-260002',
    'currency' => 'EUR',
    'created_at' => now(),
    'seller' => config('invoices.default_seller'),
    'buyer' => [
        'company' => 'Doe Corporation',
        'email' => 'john.doe@example.com',
    ],
    'items' => [
        [
            'label' => 'Consulting',
            'unit_price' => 100.00,
            'quantity' => 2,
            'discounts' => [['code' => 'WELCOME', 'percentage' => 10]],
            'taxes' => [['type' => 'vat', 'country' => 'FR', 'percentage' => 20]],
        ],
    ],
])->denormalize();
```

### PDF Amounts and Denormalization

Each item holds its amounts, and the invoice holds their totals:

| Item property | Meaning | Invoice total |
| --- | --- | --- |
| `unit_price` | Price of one unit before discounts and taxes. | — |
| `price_subtotal` | Line subtotal, normally `unit_price × quantity`. | `subtotal_amount` |
| `price_discount` | Sum of the line's discount amounts. | `discount_amount` |
| `price_tax` | Sum of the line's tax amounts. | `tax_amount` |
| `price` | Final line amount: `price_subtotal − price_discount + price_tax`. | `total_amount` |

#### Letting the Package Calculate Amounts

Call `denormalize()` to calculate line amounts and invoice totals before rendering:

```php
$pdfInvoice->denormalize();
```

- Only missing (`null`) amounts are calculated; existing amounts, including zero, are preserved.
- If you supply only `price_subtotal`, the unit price is derived from the quantity.
- Calculations use `invoices.rounding_mode`, defaulting to `RoundingMode::HalfUp`.

Rendering does not calculate amounts automatically.

#### Using Precomputed Amounts

Supply line amounts and invoice totals directly when they have already been calculated:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

$pdfInvoice = new PdfInvoice(
    serial_number: 'INV-260003',
    items: new PdfInvoiceItemCollection([
        new PdfInvoiceItem(
            label: 'Consulting',
            quantity: 2,
            unit_price: Money::of('100.00', 'EUR'),
            price_subtotal: Money::of('200.00', 'EUR'),
            price_discount: Money::of('20.00', 'EUR'),
            price_tax: Money::of('36.00', 'EUR'),
            price: Money::of('216.00', 'EUR'),
        ),
    ]),
    subtotal_amount: Money::of('200.00', 'EUR'),
    discount_amount: Money::of('20.00', 'EUR'),
    tax_amount: Money::of('36.00', 'EUR'),
    total_amount: Money::of('216.00', 'EUR'),
);
```

You can mix precomputed and calculated amounts: `denormalize()` fills the gaps. To display discount and tax breakdowns, also supply the corresponding entries with their amounts.

#### Recalculating after Changes

After changing a quantity, unit price, discount, or tax percentage, request recalculation explicitly:

```php
$pdfInvoice->items->first()->quantity = 3;
$pdfInvoice->denormalize(force: true);
```

`force: true` recalculates line amounts and invoice totals. Percentage discounts and taxes are recalculated; fixed amounts remain inputs.

### PDF Discounts and Taxes

Add percentage or fixed-amount discounts and taxes to each item:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

$item = new PdfInvoiceItem(
    label: 'Consulting',
    unit_price: Money::of('100.00', 'EUR'),
    quantity: 2,
    discounts: new InvoiceDiscountCollection([
        new InvoiceDiscount(code: 'SUMMER', label: 'Summer offer', percentage: 10),
        new InvoiceDiscount(label: 'Loyalty credit', amount: Money::of('5.00', 'EUR')),
    ]),
    taxes: new InvoiceTaxCollection([
        new InvoiceTax(type: 'vat', country: 'FR', percentage: 20),
        new InvoiceTax(label: 'Fixed levy', amount: Money::of('2.00', 'EUR')),
    ]),
);

$item->denormalize();
// Subtotal: 200.00; discounts: 25.00; taxes: 37.00; final amount: 212.00.
```

- Fixed amounts apply to the **whole line**, not each unit.
- Percentage discounts are applied sequentially, in the order provided.
- Each percentage tax is calculated on the subtotal after all discounts; taxes are not compounded.
- For an invoice-wide promotion, distribute its discount across the items.

The default template groups discount and tax breakdowns automatically. Use `getDiscounts()` and `getTaxes()` for these summaries in custom templates. Taxes can include `country`, `state`, and `taxability` for jurisdiction and exemption information.

Use `Money::of('19.80', 'EUR')` for major units or `Money::ofMinor(1980, 'EUR')` for minor units. If you use direct discount/tax array constructors instead of `Money`, their numeric amounts are in **minor units**; `PdfInvoice::make()` uses major units throughout.

### Parties, Addresses, and Custom Fields

Use `Party` for the seller and buyer. Add billing and shipping addresses, contact details, tax IDs, registration identities, and custom fields as shown in the full example.

Custom fields can appear in the invoice header, party details, or addresses:

```php
$pdfInvoice->fields = [
    'Order' => 'PO0234',
    'Customer reference' => 'ACME-42',
];
```

### Types, States, and Dates

Built-in document types and states have translated labels:

- `InvoiceType`: `Invoice`, `Quote`, `Credit`, `Proforma`.
- `InvoiceState`: `Draft`, `Pending`, `Paid`, `Refunded`.

For custom labels, use your own enum implementing `Elegantly\Invoices\Contracts\HasLabel`.

Set `created_at`, `due_at`, and `paid_at` to display dates. Change their format with `invoices.date_format`.

### Payment Instructions and QR Codes

Add bank details, payment links, and QR codes with `paymentInstructions`:

```php
use Elegantly\Invoices\Support\PaymentInstruction;

$pdfInvoice->paymentInstructions = [
    new PaymentInstruction(
        name: 'Bank transfer',
        description: 'Pay using the bank details below.',
        qrcode: 'data:image/png;base64,'.base64_encode(
            file_get_contents(public_path('images/payment-qr.png'))
        ),
        fields: [
            'Bank' => 'Acme Bank',
            'IBAN' => 'GB12ACME12345678123456',
            'SWIFT/BIC' => 'ACMEGB2L',
            '<a href="https://example.com/pay">Pay online</a>',
        ],
    ),
];
```

Payment fields and invoice descriptions support HTML. Generate QR codes with a package such as [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode).

### Rendering, Downloading, and Storing PDFs

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;
use Illuminate\Support\Facades\Storage;

class PdfInvoiceController extends Controller
{
    public function show()
    {
        return $this->makeInvoice()->stream();
    }

    public function download()
    {
        return $this->makeInvoice()->download(filename: 'invoice.pdf');
    }

    public function store()
    {
        $pdfInvoice = $this->makeInvoice();

        Storage::disk('local')->put(
            'invoices/'.$pdfInvoice->getFilename(),
            $pdfInvoice->getPdfOutput(),
        );

        return response()->noContent();
    }

    private function makeInvoice(): PdfInvoice
    {
        return PdfInvoice::make([
            'serial_number' => 'INV-260001',
            'currency' => 'EUR',
            'created_at' => now(),
            'seller' => config('invoices.default_seller'),
            'buyer' => ['company' => 'Doe Corporation'],
            'items' => [
                ['label' => 'Consulting', 'unit_price' => 100, 'quantity' => 2],
            ],
        ])->denormalize();
    }
}
```

`stream()` displays the PDF inline; `download()` downloads it. The default filename is the serial number followed by `.pdf`.

#### Livewire Downloads

```php
namespace App\Livewire;

use Elegantly\Invoices\Pdf\PdfInvoice;
use Livewire\Component;

class InvoicePreview extends Component
{
    public function download()
    {
        $pdfInvoice = PdfInvoice::make([
            'serial_number' => 'INV-260001',
            'currency' => 'EUR',
            'seller' => config('invoices.default_seller'),
            'buyer' => ['company' => 'Doe Corporation'],
            'items' => [
                ['label' => 'Consulting', 'unit_price' => 100, 'quantity' => 2],
            ],
        ])->denormalize();

        return response()->streamDownload(function () use ($pdfInvoice) {
            echo $pdfInvoice->getPdfOutput();
        }, $pdfInvoice->getFilename(), ['Content-Type' => 'application/pdf']);
    }

    public function render()
    {
        return view('livewire.invoice-preview');
    }
}
```

In `resources/views/livewire/invoice-preview.blade.php`:

```blade
<div>
    <button wire:click="download" wire:loading.attr="disabled">
        Download invoice
    </button>
</div>
```

#### Accessing Dompdf

`pdf()` returns an unrendered `Dompdf` instance. Override configured options, paper settings, or view data for a particular render:

```php
$dompdf = $pdfInvoice->pdf(
    options: ['defaultFont' => 'DejaVu Sans'],
    paper: ['size' => 'a4', 'orientation' => 'landscape'],
    data: ['footerText' => 'Thank you for your business'],
);

$dompdf->render();
$output = $dompdf->output();
```

### Rendering Blade Views

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;

class InvoicePreviewController extends Controller
{
    public function show()
    {
        $pdfInvoice = PdfInvoice::make([
            'serial_number' => 'INV-260001',
            'currency' => 'EUR',
            'seller' => config('invoices.default_seller'),
            'buyer' => ['company' => 'Doe Corporation'],
            'items' => [
                ['label' => 'Consulting', 'unit_price' => 100, 'quantity' => 2],
            ],
        ])->denormalize();

        return $pdfInvoice->view();
    }
}
```

To embed the invoice in another Blade view:

```blade
<div class="aspect-[210/297] bg-white shadow-md">
    @include('invoices::default.invoice', ['invoice' => $pdfInvoice])
</div>
```

The partial uses Tailwind CSS classes. Include the package's `invoices::default.style` partial or provide equivalent styles in your application. The complete default layout already includes the package's CSS.

### PDF Mail Attachments

#### Attaching PDFs to Mailables

```php
namespace App\Mail;

use Elegantly\Invoices\Pdf\PdfInvoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

class PdfInvoiceMail extends Mailable
{
    public function __construct(public PdfInvoice $pdfInvoice) {}

    public function content(): Content
    {
        return new Content(htmlString: '<p>Your invoice is attached.</p>');
    }

    public function attachments(): array
    {
        return [
            $this->pdfInvoice->toMailAttachment(filename: 'invoice.pdf'),
        ];
    }
}
```

Send the PDF built in the full example:

```php
use App\Mail\PdfInvoiceMail;
use Illuminate\Support\Facades\Mail;

Mail::to('john.doe@example.com')->send(new PdfInvoiceMail($pdfInvoice));
```

#### Attaching PDFs to Notifications

```php
namespace App\Notifications;

use Elegantly\Invoices\Pdf\PdfInvoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PdfInvoiceNotification extends Notification
{
    public function __construct(public PdfInvoice $pdfInvoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your invoice')
            ->line('Your invoice is attached.')
            ->attach($this->pdfInvoice);
    }
}
```

```php
use App\Notifications\PdfInvoiceNotification;
use Illuminate\Support\Facades\Notification;

Notification::route('mail', 'john.doe@example.com')
    ->notify(new PdfInvoiceNotification($pdfInvoice));
```

### PDF Customization and Localization

#### Logos, Templates, and Template Data

Set `logo` to an image path or data URI, or configure a default with `invoices.pdf.logo`.

Publish views to customize the layout, invoice content, header, footer, and payment instructions:

```bash
php artisan vendor:publish --tag="invoices-views"
```

Edit files in `resources/views/vendor/invoices/`. For a layout at `resources/views/vendor/invoices/my-custom/layout.blade.php`, use `template: 'my-custom.layout'` or set `invoices.pdf.template`.

Pass custom template settings through `templateData`, and extra Blade variables through `view($data)` or `pdf(data: ...)`. Add styles for custom classes in `style.blade.php`.

#### Fonts and PDF Options

The default layout reads `font` and `fonts` from `templateData`:

```php
$pdfInvoice->templateData = [
    'font' => 'Arimo',
    'fonts' => [
        'https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400..700;1,400..700&display=swap',
    ],
];
```

You can set these defaults in `invoices.pdf.template_data`. Configure Dompdf's font directories, cache, remote resources, and other options through `invoices.pdf.options`. See the [Dompdf font guide](https://github.com/dompdf/dompdf/wiki/About-Fonts-and-Character-Encoding).

#### Localization and Money Formatting

Built-in type/state labels and template text use Laravel translations. Set your application locale and publish translations to customize them:

```bash
php artisan vendor:publish --tag="invoices-translations"
```

Use `format_money()` in custom templates to format amounts with the application locale, or specify a locale:

```php
use Brick\Money\CurrencyDisplay;
use Brick\Money\Money;

use function Elegantly\Invoices\format_money;

$formatted = format_money(
    Money::of('100.00', 'EUR'),
    locale: 'fr',
    currencyDisplay: CurrencyDisplay::Symbol,
    hideFractionIfWhole: true,
);
```

#### Extending PdfInvoice

Extend `PdfInvoice` to customize behavior such as filenames:

```php
namespace App\ValueObjects;

class PdfInvoice extends \Elegantly\Invoices\Pdf\PdfInvoice
{
    public function getFilename(): string
    {
        return 'acme-'.parent::getFilename();
    }
}
```

## The `Invoice` Eloquent Model

Store invoice snapshots and totals with `Invoice`, and their line amounts, discounts, and taxes with `InvoiceItem`.

Publish and run the migrations before using the models. Assign amounts using `Money`; the package handles database storage and currency.

### Complete Example

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Support\PaymentInstruction;
use Illuminate\Support\Facades\DB;

$invoice = new Invoice([
    'type' => InvoiceType::Invoice->value,
    'state' => InvoiceState::Pending->value,
    'state_set_at' => now(),
    'seller_information' => config('invoices.default_seller'),
    'buyer_information' => [
        'company' => 'Doe Corporation',
        'name' => 'John Doe',
        'address' => [
            'street' => ['8405 Old James St', 'Apartment 1'],
            'city' => 'New York',
            'postal_code' => '14609',
            'state' => 'NY',
            'country' => 'US',
        ],
        'email' => 'john.doe@example.com',
    ],
    'description' => 'Application development',
    'due_at' => now()->addDays(30),
    'fields' => ['Order' => 'PO0234'],
    'metadata' => ['source' => 'customer-portal'],
    'payment_instructions' => [
        new PaymentInstruction(
            name: 'Bank transfer',
            fields: ['IBAN' => 'GB12ACME12345678123456'],
        ),
    ],
]);

$invoice->configureSerialNumber(
    format: 'PPP-YYCCCC',
    prefix: 'INV',
    year: now()->year,
);

$invoice->setItems([
    new InvoiceItem([
        'label' => 'Consulting',
        'unit_price' => Money::of('100.00', 'EUR'),
        'quantity' => 2,
        'quantity_unit' => 'hours',
        'description' => 'Application development',
        'discounts' => new InvoiceDiscountCollection([
            new InvoiceDiscount(code: 'WELCOME', percentage: 10),
        ]),
        'taxes' => new InvoiceTaxCollection([
            new InvoiceTax(type: 'vat', country: 'FR', percentage: 20),
        ]),
        'metadata' => ['product_id' => 'consulting'],
    ]),
]);

DB::transaction(fn () => $invoice->denormalize()->saveWithItems());

// The invoice and its item are stored, with total_amount = EUR 216.00.
```

The transaction saves the invoice and its items together.

### Stored Amounts and Denormalization

Line amounts and invoice totals are stored separately:

| Item attribute | Meaning | Invoice attribute |
| --- | --- | --- |
| `unit_price` | Price of one unit before discounts and taxes. | — |
| `price_subtotal` | Normally `unit_price × quantity`. | `subtotal_amount` |
| `price_discount` | Sum of line discounts. | `discount_amount` |
| `price_tax` | Sum of line taxes. | `tax_amount` |
| `price` | `price_subtotal − price_discount + price_tax`. | `total_amount` |

Use the same currency for an invoice and its items. Numeric amounts assigned to the models are in major units.

#### Calculating and Saving Amounts

```php
$invoice->denormalize()->saveWithItems();
```

`denormalize()` calculates missing line amounts and sums them into invoice totals.

- Items fill missing line amounts when saved. Call `denormalize()` on the invoice to update its totals.
- Existing amounts, including zero, are preserved.
- If you supply only `price_subtotal`, the unit price is derived from the quantity.
- Calculations use `invoices.rounding_mode`, defaulting to `RoundingMode::HalfUp`.

#### Storing Precomputed Amounts

Supply amounts directly to preserve your own calculations or those of a payment provider:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;

$invoice = new Invoice([
    'subtotal_amount' => Money::of('200.00', 'EUR'),
    'discount_amount' => Money::of('20.00', 'EUR'),
    'tax_amount' => Money::of('36.00', 'EUR'),
    'total_amount' => Money::of('216.00', 'EUR'),
]);

$invoice->setItems([
    new InvoiceItem([
        'label' => 'Consulting',
        'quantity' => 2,
        'unit_price' => Money::of('100.00', 'EUR'),
        'price_subtotal' => Money::of('200.00', 'EUR'),
        'price_discount' => Money::of('20.00', 'EUR'),
        'price_tax' => Money::of('36.00', 'EUR'),
        'price' => Money::of('216.00', 'EUR'),
        'discounts' => new InvoiceDiscountCollection([
            new InvoiceDiscount(code: 'WELCOME', amount: Money::of('20.00', 'EUR')),
        ]),
        'taxes' => new InvoiceTaxCollection([
            new InvoiceTax(type: 'vat', country: 'FR', amount: Money::of('36.00', 'EUR')),
        ]),
    ]),
]);

$invoice->saveWithItems();
```

You can mix precomputed and calculated amounts: `denormalize()` fills the gaps. Include discount and tax entries with their amounts for PDF breakdowns and exports.

#### Recalculating Existing Invoices

Existing derived amounts remain populated after you edit source data. Recalculate them explicitly:

```php
$invoice->items->first()->quantity = 3;
$invoice->denormalize(force: true)->saveWithItems();
```

`force: true` recalculates line amounts, percentage discounts and taxes, and invoice totals. Fixed amounts remain inputs.

#### Denormalizing with Artisan

Use the command to fill missing amounts or recalculate existing invoices in bulk:

```bash
# Fill missing amounts on all invoices and their items.
php artisan invoices:denormalize

# Limit processing to specific invoice IDs.
php artisan invoices:denormalize 1 2 3

# Recalculate derived amounts, replacing existing calculated values.
php artisan invoices:denormalize 1 2 3 --force
```

### Managing Invoice Items

```php
use Brick\Money\Money;
use Elegantly\Invoices\Models\InvoiceItem;

// Set the items.
$invoice->setItems([
    new InvoiceItem(['label' => 'Support', 'unit_price' => Money::of('50.00', 'EUR')]),
]);

// Add more items.
$invoice->addItems([
    new InvoiceItem(['label' => 'Hosting', 'unit_price' => Money::of('10.00', 'EUR')]),
]);

$invoice->denormalize(force: true)->saveWithItems();
```

Use `saveWithItems()` to persist the invoice and its items, or `saveItems()` for an already saved invoice. `setItems()` replaces the loaded collection; delete unwanted database rows explicitly through `items()`.

You can also manage items through the `items()` Eloquent relation. Reload items and use `denormalize(force: true)` to refresh calculated totals after changes.

For fractional quantities, change the published integer `quantity` column to a decimal column in your application's migrations.

Deleting an invoice deletes its items by default. Set `invoices.cascade_invoice_delete_to_invoice_items` to `false` to disable the model's cascading delete behavior.

### Stored Discounts and Taxes

Add percentage or fixed-amount discounts and taxes with the item helpers:

```php
use Brick\Money\Money;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;

$item = $invoice->items->first();

$item->addDiscounts(new InvoiceDiscount(
    code: 'SUMMER',
    label: 'Summer offer',
    percentage: 10,
));

$item->addTaxes([
    new InvoiceTax(type: 'vat', country: 'FR', percentage: 20),
    new InvoiceTax(label: 'Fixed levy', amount: Money::of('2.00', 'EUR')),
]);

$invoice->denormalize(force: true)->saveWithItems();
```

- Discounts are applied sequentially, in the order provided.
- Each percentage tax uses the subtotal after all discounts.
- Fixed amounts apply to the whole line, not each unit.
- Taxes can include `country`, `state`, and `taxability` for jurisdiction and exemption information.

Use `Money` for amounts. Direct discount/tax array constructors interpret numeric amounts with a `currency` key in **minor units**.

### Stored Parties, Fields, and Payment Instructions

Store seller and buyer details in `seller_information` and `buyer_information` so invoices retain their original details when customer records change. Include addresses, contact details, tax IDs, and registration identities as needed.

```php
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;

$invoice->seller_information = Party::make(config('invoices.default_seller'));

$invoice->buyer_information = [
    'company' => 'Doe Corporation',
    'tax_id' => ['country' => 'FR', 'code' => '123456789'],
    'identities' => [['type' => 'SIREN', 'code' => '732829320']],
    'fields' => ['Customer number' => 'ACME-42'],
];

$invoice->mergeFields(['Order' => 'PO0234']);
$invoice->metadata = ['external_id' => 'order_123'];

$invoice->payment_instructions = [
    new PaymentInstruction(
        name: 'Bank transfer',
        description: 'Use the invoice number as the reference.',
        fields: ['IBAN' => 'GB12ACME12345678123456'],
    ),
];

$invoice->save();
```

`fields` appear in the PDF header; `mergeFields()` adds to existing fields. Use `metadata` for application data that should not appear on the invoice.

Payment instructions appear in the PDF and can include bank details, HTML payment links, and QR codes through `qrcode`.

### Relations, Quotes, and Credits

Associate an invoice with any Eloquent model through its polymorphic relations:

```php
$invoice->buyer()->associate($customer);
$invoice->seller()->associate($team);
$invoice->invoiceable()->associate($order);
$invoice->save();
```

Define the inverse relation on your customer model:

```php
namespace App\Models;

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'buyer');
    }
}
```

Invoices can also relate to other invoices through `parent_id`:

- `parent()` belongs to the parent invoice.
- `children()` returns all child documents.
- `quote()` returns a child with type `quote`.
- `credits()` returns children with type `credit`.

```php
use Elegantly\Invoices\Models\Invoice;

$credit = new Invoice(['type' => 'credit']);
$credit->parent()->associate($invoice);
// Add credit items and amounts, then denormalize and save.
```

### Types, States, and Query Scopes

New invoices default to type `invoice` and state `draft`. Available values are:

- Types: `invoice`, `quote`, `credit`, `proforma`.
- States: `draft`, `pending`, `paid`, `refunded`.

Set the state and `state_set_at` when your application records a payment or refund. Use query scopes to filter documents:

```php
use Elegantly\Invoices\Models\Invoice;

$paidInvoices = Invoice::invoice()->paid()->with('items')->get();
$draftQuotes = Invoice::quote()->draft()->get();
$pendingCredits = Invoice::credit()->pending()->get();
$refundedInvoices = Invoice::refunded()->get();
$proformas = Invoice::where('type', 'proforma')->get();
```

Type scopes are `invoice()`, `quote()`, and `credit()`. State scopes are `draft()`, `pending()`, `paid()`, and `refunded()`.

To display a paid date in the PDF, set `paid_at` on the converted `PdfInvoice`.

### Generating Serial Numbers

Serial numbers are generated automatically when an invoice is saved without one. The default format, `PPYYCCCC`, produces values such as `IN260001`.

| Token | Meaning |
| --- | --- |
| `P` | Prefix |
| `S` | Series |
| `Y` | Year |
| `M` | Month |
| `C` | Sequential count |

Repeat a token to set its width, and add separators as needed.

#### Multiple Prefixes and Series

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = new Invoice;
$invoice->configureSerialNumber(
    format: 'PPP-SSSS-YYMMCCCC',
    prefix: 'INV',
    serie: 42,
    year: 2026,
    month: 10,
);
$invoice->save();

// First invoice in this scope: INV-0042-26100001.
```

Numbering is sequential within each prefix, series, year, and month. Override `getPreviousInvoice()` in a custom model to change that scope.

Set default numbering rules in configuration:

```php
// In config/invoices.php, alongside the other settings:
return [
    'serial_number' => [
        'auto_generate' => true,
        'format' => [
            'invoice' => 'PPPYYCCCC',
            'quote' => 'PPPYYCCCC',
            'credit' => 'PPPYYCCCC',
            'proforma' => 'PPPYYCCCC',
        ],
        'prefix' => [
            'invoice' => 'INV',
            'quote' => 'QUO',
            'credit' => 'CRE',
            'proforma' => 'PRO',
        ],
    ],
];
```

#### Manual Serial Numbers and Parsing

```php
$invoice->setSerialNumber(
    value: 'INV-0042-26100001',
    format: 'PPP-SSSS-YYMMCCCC',
)->save();
```

Manual numbers are preserved. Set `invoices.serial_number.auto_generate` to `false` if your application always supplies them.

Use `SerialNumberGenerator` independently to generate or parse numbers without a model:

```php
use Elegantly\Invoices\SerialNumberGenerator;

$generator = new SerialNumberGenerator('PPP-YYCCCC');
$serial = $generator->generate(prefix: 'INV', year: 26, count: 1);
$parts = $generator->parse($serial); // prefix, serie, year, month, count
```

### Storing Logos

Without a stored logo, PDFs fall back to `invoices.pdf.logo`. To capture a logo in the database, store its binary contents:

```php
// Capture the current configured logo.
$invoice->setLogoFromConfig();

// Or capture a local file / uploaded file.
$invoice->setLogoFromPath(public_path('images/logo.png'));
$invoice->setLogoFromFile($uploadedFile);

// Or assign raw image bytes directly.
$invoice->logo = $rawImageBytes;

$invoice->save();
```

### Converting Models to PDFs

Use `toPdfInvoice()` to render the stored invoice without recalculating its amounts:

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        return $invoice->loadMissing('items')->toPdfInvoice()->stream();
    }

    public function download(Invoice $invoice)
    {
        return $invoice->loadMissing('items')->toPdfInvoice()->download();
    }

    public function preview(Invoice $invoice)
    {
        return $invoice->loadMissing('items')->toPdfInvoice()->view();
    }

    public function store(Invoice $invoice)
    {
        $pdfInvoice = $invoice->loadMissing('items')->toPdfInvoice();

        Storage::put(
            'invoices/'.$pdfInvoice->getFilename(),
            $pdfInvoice->getPdfOutput(),
        );

        return response()->noContent();
    }
}
```

In `routes/web.php`:

```php
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);
Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview']);
Route::post('/invoices/{invoice}/pdf', [InvoiceController::class, 'store']);
```

For a Blade preview, include `invoices::default.invoice` with the converted PDF as `invoice` and include the package's CSS.

#### Livewire Downloads from a Model

```php
namespace App\Livewire;

use Elegantly\Invoices\Models\Invoice;
use Livewire\Component;

class InvoiceDownload extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function download()
    {
        $pdfInvoice = $this->invoice->loadMissing('items')->toPdfInvoice();

        return response()->streamDownload(function () use ($pdfInvoice) {
            echo $pdfInvoice->getPdfOutput();
        }, $pdfInvoice->getFilename(), ['Content-Type' => 'application/pdf']);
    }

    public function render()
    {
        return view('livewire.invoice-download');
    }
}
```

In `resources/views/livewire/invoice-download.blade.php`:

```blade
<div>
    <button wire:click="download" wire:loading.attr="disabled">
        Download {{ $invoice->serial_number }}
    </button>
</div>
```

Mount it in a Blade view with your stored invoice:

```blade
<livewire:invoice-download :invoice="$invoice" />
```

#### Customizing PDF Output from the Model

Extend the model and override `toPdfInvoice()` to adjust the returned PDF:

```php
namespace App\Models;

use Elegantly\Invoices\Pdf\PdfInvoice;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    public function toPdfInvoice(): PdfInvoice
    {
        $pdfInvoice = parent::toPdfInvoice();
        $pdfInvoice->template = 'invoices::my-custom.layout';
        $pdfInvoice->templateData = ['font' => 'DejaVu Sans'];
        $pdfInvoice->fields['Support'] = 'billing@example.com';

        return $pdfInvoice;
    }
}
```

Register the model through `invoices.model_invoice`. You can also return your own `PdfInvoice` subclass, or configure global PDF settings under `invoices.pdf`.

### Model Mail Attachments

Attach a stored invoice directly to mailables and notifications.

#### Mailables

```php
namespace App\Mail;

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

class InvoiceMail extends Mailable
{
    public function __construct(public Invoice $invoice) {}

    public function content(): Content
    {
        return new Content(htmlString: '<p>Your invoice is attached.</p>');
    }

    public function attachments(): array
    {
        return [$this->invoice->toMailAttachment()];
    }
}
```

```php
use App\Mail\InvoiceMail;
use Elegantly\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Mail;

$invoice = Invoice::with('items')->findOrFail($invoiceId);

Mail::to($invoice->buyer_information->email)->send(new InvoiceMail($invoice));
```

Customize the filename with `$invoice->toPdfInvoice()->toMailAttachment(filename: 'invoice.pdf')`.

#### Notifications

```php
namespace App\Notifications;

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceNotification extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your invoice')
            ->line('Your invoice is attached.')
            ->attach($this->invoice);
    }
}
```

```php
use App\Notifications\InvoiceNotification;
use Elegantly\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Notification;

$invoice = Invoice::with('items')->findOrFail($invoiceId);

Notification::route('mail', $invoice->buyer_information->email)
    ->notify(new InvoiceNotification($invoice));
```

### Replicating, Scaling, and Allocating Amounts

#### Replicating and Scaling

Replicate an invoice and its items to create a new document. For example, create a credit with negative amounts:

```php
use Elegantly\Invoices\Enums\InvoiceType;

$credit = $invoice->replicate();
$credit->type = InvoiceType::Credit->value;
$credit->configureSerialNumber(format: 'PPP-YYCCCC', prefix: 'CRE');
$credit->parent()->associate($invoice);
$credit->setItems($invoice->items->replicate());
$credit->multiplyBy(-1)->saveWithItems();
```

The copy receives a new serial number. `multiplyBy()` scales invoice, item, discount, and tax amounts without changing quantities or percentages. Use a string such as `'0.5'` for decimal factors.

#### Allocating Amounts

Allocate target amounts proportionally across existing items, for example to create a partial credit or match a payment provider's totals:

```php
use Brick\Money\AllocationMode;
use Brick\Money\Money;

$invoice->items->allocate(
    subtotal: Money::of('100.00', 'EUR'),
    tax: Money::of('18.00', 'EUR'),
    discount: Money::of('10.00', 'EUR'),
    total: Money::of('108.00', 'EUR'),
    mode: AllocationMode::FloorToFirst,
);

$invoice->subtotal_amount = $invoice->items->sumMoney('price_subtotal');
$invoice->discount_amount = $invoice->items->sumMoney('price_discount');
$invoice->tax_amount = $invoice->items->sumMoney('price_tax');
$invoice->total_amount = $invoice->items->sumMoney('price');
$invoice->saveWithItems();
```

Allocation uses existing item and breakdown amounts as proportions, and updates unit prices, discounts, and taxes. Start with calculated amounts and nonzero quantities. Save without forced denormalization to preserve the allocated result.

### Collections and Money Totals

Use `sumMoney()` to total amounts in the same currency, or convert collections to PDFs:

```php
use Elegantly\Invoices\Models\Invoice;

$invoices = Invoice::paid()->where('currency', 'EUR')->with('items')->get();
$total = $invoices->sumMoney('total_amount');
$pdfInvoices = $invoices->toPdfInvoices();

$subtotal = $invoice->items->sumMoney('price_subtotal');
$pdfItems = $invoice->items->toPdfItems();
```

### Importing Stripe Checkout Line Items

Install the optional Stripe SDK:

```bash
composer require stripe/stripe-php
```

Import Stripe Checkout line items while preserving Stripe's precomputed amounts and breakdowns:

```php
use Elegantly\Invoices\Integrations\StripeCheckoutIntegration;
use Elegantly\Invoices\Models\Invoice;
use Stripe\StripeClient;

$stripe = new StripeClient(config('services.stripe.secret'));
$lineItems = $stripe->checkout->sessions->allLineItems($checkoutSessionId, [
    'expand' => [
        'data.price',
        'data.discounts.discount.promotion_code',
        'data.taxes',
    ],
]);

$integration = new StripeCheckoutIntegration;
$invoice = new Invoice([
    'state' => 'paid',
    'state_set_at' => now(),
    'seller_information' => config('invoices.default_seller'),
    'buyer_information' => $buyerSnapshot,
]);

$invoice->setItems($integration->toInvoiceItemCollection($lineItems));
$invoice->denormalize()->saveWithItems();
```

Use the expansions shown above and fetch all pages for larger sessions. `toInvoiceItem($lineItem)` imports a single line. Your application supplies invoice parties, dates, and associations; `denormalize()` sums the imported amounts without overwriting them.

### GOBL Export

Export an invoice using the [GOBL invoice schema](https://docs.gobl.org/draft-0/bill/invoice):

```php
use Elegantly\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Storage;

$invoice = Invoice::with(['items', 'parent'])->firstOrFail();
$gobl = $invoice->toGOBL();

Storage::put(
    'invoices/'.$invoice->serial_number.'.json',
    json_encode($gobl, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
);
```

The export includes document details, parties, lines, discounts, taxes, payment terms, and a reference to the parent invoice where applicable.

Pass additional values to extend or override the mapping:

```php
$gobl = $invoice->toGOBL(['regime' => 'FR']);
$line = $invoice->items->first()->toGOBL([
    'item' => ['ref' => 'SKU-001'],
]);
```

Items, parties, discounts, and taxes can also be exported individually with `toGOBL()`.

### Custom Models and Value Objects

Extend the package classes and register your subclasses in `config/invoices.php`:

```php
// In config/invoices.php, alongside the other settings:
return [
    'model_invoice' => \App\Models\Invoice::class,
    'model_invoice_item' => \App\Models\InvoiceItem::class,
    'discount_class' => \App\ValueObjects\InvoiceDiscount::class,
    'tax_class' => \App\ValueObjects\InvoiceTax::class,
    'party_class' => \App\ValueObjects\Party::class,
    'identity_class' => \App\ValueObjects\Identity::class,
    'address_class' => \App\ValueObjects\Address::class,
    'tax_id_class' => \App\ValueObjects\TaxId::class,
    'payment_instructions_class' => \App\ValueObjects\PaymentInstruction::class,
];
```

Use `seller_class` and `buyer_class` to configure separate party classes if needed.

Factories are available through `Invoice::factory()` and `InvoiceItem::factory()`. Invoice factories include `invoice()`, `quote()`, `credit()`, and `proforma()` states.

### Casting Types and States to Enums

If you prefer enum attributes, merge enum casts with the parent's casts. Also override `getType()` and `getState()` to return the cast values for PDF conversion:

```php
namespace App\Models;

use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'type' => InvoiceType::class,
            'state' => InvoiceState::class,
        ];
    }

    public function getType(): InvoiceType
    {
        return $this->type;
    }

    public function getState(): InvoiceState
    {
        return $this->state;
    }
}
```

Set `invoices.model_invoice` to this subclass. For custom enums, implement `Elegantly\Invoices\Contracts\HasLabel` to provide PDF labels, and configure serial numbers for your custom document types.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Quentin Gabriele](https://github.com/QuentinGab)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
