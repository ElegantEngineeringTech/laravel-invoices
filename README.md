# Everything You Need to Manage Invoices in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)

This package provides a robust, easy-to-use system for managing invoices within a Laravel application, with options for database storage, serial numbering, and PDF generation.

![laravel-invoices](https://repository-images.githubusercontent.com/527661364/f98e92f9-62a6-48a1-a7b1-1a587b92a430)

## Interactive Demo

Try out [the interactive demo](https://elegantly.dev/laravel-invoices) to explore package capabilities.

## Table of Contents

-   [Requirements](#requirements)
-   [Installation](#installation)
-   [The `PdfInvoice` Class](#the-pdfinvoice-class)
    -   [Full Example](#full-example)
    -   [Rendering the Invoice as a PDF](#rendering-the-invoice-as-a-pdf)
    -   [Storing the PDF in a file](#storing-the-pdf-in-a-file)
    -   [Downloading the Invoice as a PDF](#downloading-the-invoice-as-a-pdf)
        -   [From a controller](#from-a-controller)
        -   [From a Livewire component](#from-a-livewire-component)
    -   [Rendering the Invoice as a view](#rendering-the-invoice-as-a-view)
    -   [Rendering the Invoice within a View](#rendering-the-invoice-within-a-view)
    -   [Adding Taxes](#adding-taxes)
        -   [Tax by Percentage](#tax-by-percentage)
        -   [Tax as a Fixed Amount](#tax-as-a-fixed-amount)
    -   [Adding Discounts](#adding-discounts)
        -   [Discount by Percentage](#discount-by-percentage)
        -   [Discount as a Fixed Amount](#discount-as-a-fixed-amount)
    -   [Adding Payment Instructions](#adding-payment-instructions)
        -   [QR Code Generation](#qr-code-generation)
    -   [Customization](#customization)
        -   [Customizing Fonts](#customizing-fonts)
        -   [Customizing the Invoice Template](#customizing-the-invoice-template)
-   [The `Invoice` Eloquent Model](#the-invoice-eloquent-model)
    -   [Complete Example](#complete-example)
    -   [Generating Unique Serial Numbers](#generating-unique-serial-numbers)
    -   [Using Multiple Prefixes and Series for Serial Numbers](#using-multiple-prefixes-and-series-for-serial-numbers)
    -   [Customizing the Serial Number Format](#customizing-the-serial-number-format)
    -   [Storing the Logo](#storing-the-logo)
    -   [Storing a Dynamic Logo](#storing-a-dynamic-logo)
    -   [Converting an `Invoice` Model to a `PdfInvoice`](#converting-an-invoice-model-to-a-pdfinvoice)
    -   [Display, Download, and Store Invoices](#display-download-and-store-invoices)
    -   [Attaching Invoices to Mailables](#attaching-invoices-to-mailables)
    -   [Attaching Invoices to Notifications](#attaching-invoices-to-notifications)
    -   [Customizing PDF Output from the Model](#customizing-pdf-output-from-the-model)
        -   [Using a Custom PdfInvoice Class](#using-a-custom-pdfinvoice-class)
    -   [Casting `state` and `type` to Enums](#casting-state-and-type-to-enums)
-   [Testing](#testing)
-   [Changelog](#changelog)
-   [Contributing](#contributing)
-   [Security Vulnerabilities](#security-vulnerabilities)
-   [Credits](#credits)
-   [License](#license)

## Requirements

-   PHP 8.1+
-   Laravel 11.0+
-   `dompdf/dompdf` for PDF rendering
-   `elegantly/laravel-money` for money computation which use `brick\money` under the hood

## Installation

You can install the package via composer:

```bash
composer require elegantly/laravel-invoices
```

If you intent to store your invoices using the Eloquent Model, you must publish and run the migrations with:

```bash
php artisan vendor:publish --tag="invoices-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="invoices-config"
```

This is the contents of the published config file:

```php
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Models\InvoiceItem;
use Elegantly\Invoices\Enums\InvoiceType;

return [

    'model_invoice' => Invoice::class,
    'model_invoice_item' => InvoiceItem::class,

    'discount_class' => InvoiceDiscount::class,

    'cascade_invoice_delete_to_invoice_items' => true,

    'serial_number' => [
        /**
         * If true, will generate a serial number on creation
         * If false, you will have to set the serial_number yourself
         */
        'auto_generate' => true,

        /**
         * Define the serial number format used for each invoice type
         *
         * P: Prefix
         * S: Serie
         * M: Month
         * Y: Year
         * C: Count
         * Example: IN0012-220234
         * Repeat letter to set the length of each information
         * Examples of formats:
         * - PPYYCCCC : IN220123 (default)
         * - PPPYYCCCC : INV220123
         * - PPSSSS-YYCCCC : INV0001-220123
         * - SSSS-CCCC: 0001-0123
         * - YYCCCC: 220123
         */
        'format' => 'PPYYCCCC',

        /**
         * Define the default prefix used for each invoice type
         */
        'prefix' => [
            InvoiceType::Invoice->value => 'IN',
            InvoiceType::Quote->value => 'QO',
            InvoiceType::Credit->value => 'CR',
            InvoiceType::Proforma->value => 'PF',
        ],

    ],

    'date_format' => 'Y-m-d',

    'default_seller' => [
        'company' => null,
        'name' => null,
        'address' => [
            'street' => null,
            'city' => null,
            'postal_code' => null,
            'state' => null,
            'country' => null,
        ],
        'email' => null,
        'phone' => null,
        'tax_number' => null,
        'fields' => [
            //
        ],
    ],

    /**
     * ISO 4217 currency code
     */
    'default_currency' => 'USD',

    'pdf' => [

        'paper' => [
            'size' => 'a4',
            'orientation' => 'portrait',
        ],

        /**
         * Default DOM PDF options
         *
         * @see Available options https://github.com/barryvdh/laravel-dompdf#configuration
         */
        'options' => [
            'isRemoteEnabled' => true,
            'isPhpEnabled' => false,
            'fontHeightRatio' => 1,
            /**
             * Supported values are: 'DejaVu Sans', 'Helvetica', 'Courier', 'Times', 'Symbol', 'ZapfDingbats'
             */
            'defaultFont' => 'Helvetica',

            'fontDir' => storage_path('fonts'), // advised by dompdf (https://github.com/dompdf/dompdf/pull/782)
            'fontCache' => storage_path('fonts'),
            'tempDir' => sys_get_temp_dir(),
            'chroot' => realpath(base_path()),
        ],

        /**
         * The logo displayed in the PDF
         */
        'logo' => null,

        /**
         * The template used to render the PDF
         */
        'template' => 'default.layout',

        'template_data' => [
            /**
             * The color displayed at the top of the PDF
             */
            'color' => '#050038',
        ],

    ],

];
```

## The `PdfInvoice` Class

This package provides a powerful, standalone `PdfInvoice` class. Its main functionalities include the ability to:

-   Display your invoice as a PDF document.
-   Render your invoice within a Blade view.

The `PdfInvoice` class is also integrated with the `Invoice` Eloquent Model, allowing you to easily convert an `Invoice` model instance into its PDF representation.

You can even use this package exclusively for the `PdfInvoice` class if you don't require database storage for your invoices.

### Full Example

```php
use \Elegantly\Invoices\Pdf\PdfInvoice;
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;
use \Elegantly\Invoices\Support\Seller;
use \Elegantly\Invoices\Support\Buyer;
use \Elegantly\Invoices\Support\Address;
use \Elegantly\Invoices\Support\PaymentInstruction;
use \Elegantly\Invoices\InvoiceDiscount;
use Brick\Money\Money;

$pdfInvoice = new PdfInvoice(
    name: "Invoice",
    state: "paid",
    serial_number: "INV-241200001",
    seller: new Seller(
        company: 'elegantly',
        name: 'Quentin Gabriele', // (optional)
        address: new Address(
            street: "Place de l'Opéra",
            city: 'Paris',
            postal_code: '75009',
            country: 'France',
        ),
        email: 'john.doe@example.com',
        tax_number: 'FR123456789',
        fields: [
            // Custom fields to display with the seller
            "foo" => "bar"
        ]
    ),
    buyer: new Buyer(
        company: "Doe Corporation" // (optional)
        name: 'John Doe', // (optional)
        address: new Address(
            street: '8405 Old James St.Rochester',
            city: 'New York',
            postal_code: '14609',
            state: 'NY',
            country: 'United States',
        ),
        shipping_address: new Address( // (optional)
            street: [ // multiple lines street
                '8405 Old James St.Rochester',
                'Apartment 1',
            ],
            city: 'New York',
            postal_code: '14609',
            state: 'NY',
            country: 'United States',
        ),
        email: 'john.doe@example.com',
        fields: [
            // Custom fields to display with the buyer
            "foo" => "bar"
        ]
    ),
    description: "An invoice description",
    created_at: now(),
    due_at: now(),
    paid_at: now(),
    tax_label: "VAT France (20%)",
    fields: [ // custom fields to display at the top
        'Order' => "PO0234"
    ],
    items: [
        new PdfInvoiceItem(
            label: "Laratranslate Unlimitted" ,
            unit_price: Money::of(99.0, 'USD'),
            tax_percentage: 20.0,
            quantity: 1,
            description: "Elegant All-in-One Translations Manager for Laravel",
        ),
    ],
    discounts: [
        new InvoiceDiscount(
            name: "Summer offer",
            code: "SUMMER",
            percent_off: 50,
        )
    ],
    paymentInstructions: [
        new PaymentInstruction(
            name: 'Bank Transfer',
            description: 'Make a direct bank transfer using the details below.',
            qrcode: 'data:image/png;base64,' . base64_encode(
                file_get_contents(__DIR__.'/../resources/images/qrcode.png')
            ),
            fields: [
                'Bank Name' => 'Acme Bank',
                'Account Number' => '12345678',
                'IBAN' => 'GB12ACME12345678123456',
                'SWIFT/BIC' => 'ACMEGB2L',
                'Reference' => 'INV-0032/001',
                '<a href="#">Pay online</a>',
            ],
        ),
    ],
    logo: public_path('/images/logo.png'), // local path or base64 string
    template: "default.layout", // use the default template or use your own
    templateData: [ // custom data to pass to the template
        'color' => '#050038'
    ],
);
```

### Rendering the Invoice as a PDF

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;

class InvoiceController extends Controller
{
    public function showAsPdf()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return $pdfInvoice->stream();
    }
}
```

### Storing the PDF in a file

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function store()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        Storage::put(
            "path/to/{$pdfInvoice->getFilename()}",
            $pdfInvoice->getPdfOutput()
        );

        // ...
    }
}
```

### Downloading the Invoice as a PDF

#### From a controller

To download the PDF, simply return the `download` method.

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;

class InvoiceController extends Controller
{
    public function download()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return $pdfInvoice->download(
            /**
             * (optional)
             * The default filename is the serial_number
             */
            filename: 'invoice.pdf'
        );
    }
}
```

#### From a Livewire component

To download the PDF from a Livewire component, use the `streamDownload` method as shown below:

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;

class Invoice extends Component
{
    public function download()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return response()->streamDownload(function () use ($pdfInvoice) {
            echo $pdf->getPdfOutput();
        }, $pdf->getFilename()); // The default filename is the serial number
    }
}
```

### Rendering the Invoice as a view

```php
namespace App\Http\Controllers;

use Elegantly\Invoices\Pdf\PdfInvoice;

class InvoiceController extends Controller
{
    public function showAsView()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return $pdfInvoice->view();
    }
}
```

### Rendering the Invoice within a View

You can embed the invoice within a larger Blade view to create interfaces like an "invoice builder," similar to the [interactive demo](https://elegantly.devlaravel-invoices).

To do this, include the main invoice partial in your view as shown below:

```php
<div class="aspect-[210/297] bg-white shadow-md">
    @include('invoices::default.invoice', ['invoice' => $invoice])
</div>
```

This approach allows for seamless integration of the invoice into a dynamic and customizable user interface.

> [!NOTE]  
> The default template uses Tailwind CSS for styling. This ensures seamless integration with websites already using Tailwind.
> If your project doesn't use Tailwind, the invoice styling may not appear as intended.

### Adding Taxes

Taxes are applied to individual `PdfInvoiceItem` item. You can define them either as a percentage or a fixed amount.

#### Tax by Percentage

To add a tax as a percentage, set the `tax_percentage` property on the `PdfInvoiceItem`. This value should be a float between 0 and 100.

```php
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;

new PdfInvoiceItem(
    label: "Laratranslate Unlimitted" ,
    unit_price: Money::of(99.0, 'USD'),
    tax_percentage: 20.0, // a float between 0.0 and 100.0
),
```

#### Tax as a Fixed Amount

To apply a tax as a specific monetary amount, set the `unit_tax` property on the `PdfInvoiceItem`.

```php
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;

new PdfInvoiceItem(
    label: "Laratranslate Unlimitted" ,
    unit_price: Money::of(99.0, 'USD'),
    unit_tax: Money::of(19.8, 'USD'),
),
```

### Adding Discounts

Discounts are represented by the `InvoiceDiscount` class and are applied to the entire `PdfInvoice`. They cannot be attached to individual `PdfInvoiceItem`s at this time.

-   You can add multiple discounts to a single invoice.
-   Discounts can be specified as a fixed amount (`amount_off`) or a percentage (`percent_off`). If both are provided for the same discount, the `amount_off` value takes precedence.

#### Discount by Percentage

To apply a discount as a percentage, set the `percent_off` property.

```php
use \Elegantly\Invoices\Pdf\PdfInvoice;
use \Elegantly\Invoices\InvoiceDiscount;
use Brick\Money\Money;

$pdfInvoice = new PdfInvoice(
    // ...
    discounts: [
        new InvoiceDiscount(
            name: "Summer offer",
            code: "SUMMER",
            percent_off: 20.0,
        )
    ],
);
```

#### Discount as a Fixed Amount

To apply a discount as a fixed amount, set the `amount_off` property.

```php
use \Elegantly\Invoices\Pdf\PdfInvoice;
use \Elegantly\Invoices\InvoiceDiscount;
use Brick\Money\Money;

$pdfInvoice = new PdfInvoice(
    // ...
    discounts: [
        new InvoiceDiscount(
            name: "Summer offer",
            code: "SUMMER",
            amount_off: Money::of(20.0, 'USD'),
        )
    ],
);
```

### Adding Payment Instructions

You can include detailed payment instructions directly within the generated PDF invoice. This can be helpful for providing bank transfer details, QR codes for quick payments, and custom payment links.

Here’s an example of how to add a payment instruction:

```php
use \Elegantly\Invoices\Pdf\PdfInvoice;
use \Elegantly\Invoices\Support\PaymentInstruction;

$pdfInvoice = new PdfInvoice(
    // ...
    paymentInstructions: [
        new PaymentInstruction(
            name: 'Bank Transfer',
            description: 'Make a direct bank transfer using the details below.',
            qrcode: 'data:image/png;base64,' . base64_encode(
                file_get_contents(__DIR__.'/../resources/images/qrcode.png')
            ),
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
```

> **Note:** You can include HTML tags (e.g., links) within the `fields` array for interactive content.

#### QR Code Generation

To dynamically generate QR codes, I recommend using the [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) package. It provides a simple and flexible API for generating QR codes in various formats.

### Customization

#### Customizing Fonts

See the [Dompdf font guide](https://github.com/dompdf/dompdf).

#### Customizing the Invoice Template

To customize the invoice template, first publish the package's views:

```bash
php artisan vendor:publish --tag="invoices-views"
```

After publishing, you can modify the Blade files in `resources/views/vendor/invoices/` to suit your needs.

> [!NOTE]
> If you introduce new CSS classes in your custom template, ensure you define their styles in the style.blade.php file.

Alternatively, to use a completely different custom template, you can specify its path in the configuration file:

> [!WARNING]
> Your custom template file must be in `resources/views/vendor/invoices`

```php
return [

    // ...

    'pdf' => [

        /**
         * The template used to render the PDF
         */
        'template' => 'my-custom.layout',

        'template_data' => [
            /**
             * The color displayed at the top of the PDF
             */
            'color' => '#050038',
        ],

    ],

];
```

Ensure that your custom template follows the same structure and conventions as the default one to maintain compatibility with various use cases.

## The `Invoice` Eloquent Model

The design of the `Invoice` Eloquent Model closely mirrors that of the `PdfInvoice` class.

This model provides powerful features for:

-   Generating unique and complex serial numbers.
-   Attaching your invoice to any other Eloquent model.
-   Easily including your invoice as an attachment in emails.

> [!NOTE]
> Remember to publish and run the database migrations

### Complete Example

The following example demonstrates how to create and store an invoice.

For this illustration, let's assume the following application structure:

-   `Team` models have `User` models.
-   `Team` models can have multiple `Invoice` models.
-   `Invoice` models can be attached to `Order` models.

```php
use App\Models\Team;
use App\Models\Order;

use Brick\Money\Money;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;

$customer = Team::find(1);
$order = Order::find(2);

$invoice = new Invoice(
    'type' => "invoice",
    'state' => "paid",
    'seller_information' => config('invoices.default_seller'),
    'buyer_information' =>[
        'company' => "Doe Corporation" // (optional)
        'name' => 'John Doe', // (optional)
        'address' => [
            'street' => '8405 Old James St.Rochester',
            'city' => 'New York',
            'postal_code' => '14609',
            'state' => 'NY',
            'country' => 'United States',
        ],
        'shipping_address' => [ // (optional)
            'street' => [ // multiple lines street
                '8405 Old James St.Rochester',
                'Apartment 1',
            ],
            'city' => 'New York',
            'postal_code' => '14609',
            'state' => 'NY',
            'country' => 'United States',
        ]
        'email' => 'john.doe@example.com',
        'fields' => [
            // Custom fields to display with the buyer
            "foo" => "bar"
        ]
    ],
    'description' => "An invoice description",
    'due_at' => now(),
    'paid_at' => now(),
    'tax_type' => "eu_VAT_FR",
    'tax_exempt' => null,
);

// Learn more about the serial number in the next section
$invoice->configureSerialNumber(
    prefix: "ORD",
    serie: $customer->id,
    year: now()->format('Y'),
    month: now()->format('m')
);

// Optional
// Learn more about the logo in the next section
$invoice->setLogoFromConfig();

$invoice->buyer()->associate($customer); // optionnally associate the invoice to any model
$invoice->invoiceable()->associate($order); // optionnally associate the invoice to any model

$invoice->save();

$invoice->items()->saveMany([
    new InvoiceItem([
        'label' => "Laratranslate Unlimitted",
        'description' => "Elegant All-in-One Translations Manager for Laravel",
        'unit_price' => Money::of(99.0, 'USD'),
        'tax_percentage' => 20.0,
        'quantity' => 1,
    ]),
]);
```

### Generating Unique Serial Numbers

This package provides a simple and reliable way to generate serial numbers automatically, such as "INV240001".

You can configure the format of your serial numbers in the configuration file. The default format is `PPYYCCCC`, where each letter has a specific meaning (see the config file for details).

When `invoices.serial_number.auto_generate` is set to `true`, a unique serial number is assigned to each new invoice automatically.

Serial numbers are generated sequentially, with each new serial number based on the latest available one. To define what qualifies as the `previous` serial number, you can extend the `Elegantly\Invoices\Models\Invoice` class and override the `getPreviousInvoice` method.

By default, the previous invoice is determined based on criteria such as prefix, series, year, and month for accurate, scoped numbering.

### Using Multiple Prefixes and Series for Serial Numbers

In more complex applications, you may need to use different prefixes and/or series for your invoices.

For instance, you might want to define a unique series for each user, creating serial numbers that look like: `INV0001-2400X`, where `0001` represents the user’s ID, `24` the year and `X` the index of the invoice.

> [!NOTE]
> When using IDs for series, it's recommended to plan for future growth to avoid overflow.
> Even if you have a limited number of users now, ensure that the ID can accommodate the maximum number of digits allowed by the serial number format.

When creating an invoice, you can dynamically specify the prefix and series with `configureSerialNumber` method:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = new Invoice();

$invoice->configureSerialNumber(
    prefix: "ORG",
    serie: $buyerId,
);
```

### Customizing the Serial Number Format

In most cases, the format of your serial numbers should remain consistent, so it's recommended to set it in the configuration file.

The format you choose will determine the types of information you need to provide to `configureSerialNumber`.

Below is an example of the most complex serial number format you can create with this package:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = new Invoice();

$invoice->configureSerialNumber(
    format: "PP-SSSSSS-YYMMCCCC",
    prefix: "IN",
    serie: 100,
    year: now()->format('Y'),
    month: now()->format('m')
);

$invoice->save();

$invoice->serial_number; // IN-000100-24010001
```

### Storing the Logo

By default, the PDF logo is loaded from the configuration defined in your config file.

If you want the logo to remain consistent over time, even if the config changes, you can store it directly in the database:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = new Invoice();

// Store the current config logo in the database
$invoice->setLogoFromConfig();

// ...

$invoice->save();
```

### Storing a Dynamic Logo

If your application allows users to upload or select their own company logos, you can dynamically set the logo on each invoice by updating the `logo` attribute on the `Invoice` model.

You can do this in several ways:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = new Invoice();

// Set the logo from an uploaded file (e.g., Illuminate\Http\UploadedFile)
$invoice->setLogoFromFile($file);

// Set the logo from a local filesystem path
$invoice->setLogoFromPath($path);

// Set the logo directly from raw file content (string or binary data)
$invoice->logo = $rawFileContent;

// ...

$invoice->save();
```

### Converting an `Invoice` Model to a `PdfInvoice`

You can obtained a `PdfInvoice` class from your `Invoice` model by calling the `toPdfInvoice` method:

```php
$invoice = Invoice::first();

$pdfInvoice = $invoice->toPdfInvoice();
```

### Display, Download, and Store Invoices

You can then stream the `PdfInvoice` instance directly or initiate a download:

```php
namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Request $request, string $serial)
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::where('serial_number', $serial)->firstOrFail();

        $this->authorize('view', $invoice);

        return $invoice->toPdfInvoice()->stream();
    }

    public function download(Request $request, string $serial)
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::where('serial_number', $serial)->firstOrFail();

        $this->authorize('view', $invoice);

        return $invoice->toPdfInvoice()->download();
    }

    public function store(Request $request, string $serial)
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::where('serial_number', $serial)->firstOrFail();

        Storage::put(
            "path/to/invoice.pdf",
            $invoice->toPdfInvoice()->getPdfOutput()
        );

        // ...
    }
}
```

### Attaching Invoices to Mailables

You can easily attach an invoice to your `Mailable` as follows:

```php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentInvoice extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Invoice $invoice,
    ) {}


    public function attachments(): array
    {
        return [
            $this->invoice->toMailAttachment()
        ];
    }
}
```

### Attaching Invoices to Notifications

You can easily attach an invoice to your `Notification` as follows:

```php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentInvoice extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Invoice $invoice,
    ) {}

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->attach($this->invoice);
    }
}
```

### Customizing PDF Output from the Model

To customize how your `Invoice` model is converted into a `PdfInvoice` object, follow these steps:

1.  **Create a Custom Invoice Model**:

Define your own `App\Models\Invoice` class and ensure it extends the base `Elegantly\Invoices\Models\Invoice`.

```php
namespace App\Models;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    // ...
}
```

2.  **Override the `toPdfInvoice` Method**:

In your custom `Invoice` model, override the `toPdfInvoice` method. This is where you'll implement your specific logic to construct and return the `PdfInvoice` object with your desired customizations.

```php
namespace App\Models;

use Elegantly\Invoices\Pdf\PdfInvoice;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    function toPdfInvoice(): PdfInvoice
    {
        return new PdfInvoice(
            // ... your custom PdfInvoice properties and configuration
        );
    }
}
```

3.  **Update the Package Configuration**:

First, if you haven't already, publish the package's configuration file:

```bash
php artisan vendor:publish --tag="invoices-config"
```

Then, modify the `config/invoices.php` file to tell the package to use your custom model by updating the `model_invoice` key:

```php
return [
    // ...

    'model_invoice' => \App\Models\Invoice::class,

    // ...
];
```

#### Using a Custom PdfInvoice Class

You can extend the default PdfInvoice class provided by the package to customize its behavior, such as changing the generated filename or adding additional logic.

1. Create Your Custom PdfInvoice Class

```php
class PdfInvoice extends \Elegantly\Invoices\Pdf\PdfInvoice
{

    public function __construct(
        // your custom constructor
    ){
        // ...
    }

    public function getFilename(): string
    {
        return str($this->serial_number)
            ->replace(['/', '\\'], '_')
            ->append('.pdf')
            ->value();
    }
}
```

In this example, we're overriding the `getFilename` method.

2. Return Your Custom `PdfInvoice` from the Invoice Model

Update your `Invoice` model to return an instance of your custom `PdfInvoice` class.

```php
namespace App\Models;

use App\ValueObjects\PdfInvoice;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    function toPdfInvoice(): PdfInvoice
    {
        return new PdfInvoice(
            // Pass any required data to your custom PdfInvoice constructor
        );
    }
}
```

By overriding the `toPdfInvoice` method, you can inject your custom logic while preserving compatibility with the rest of the package.

### Casting `state` and `type` to Enums

By default, the `type` and `state` properties on the `Invoice` model are stored as strings. This approach offers flexibility, as it doesn't restrict you to predefined values and they are not automatically cast to Enum objects.

However, you might prefer to cast these properties to Enum objects for better type safety and code clarity. You can use your own custom Enums or the ones provided by this package (e.g., `Elegantly\Invoices\Enums\InvoiceState`, `Elegantly\Invoices\Enums\InvoiceType`).

To enable Enum casting for these properties, follow these steps:

0.  **Create custom `InvoiceState` and `InvoiceType` Enums** (optional):

If you're working with commonly used invoice states and types, you can use the enums provided by this package:

-   `Elegantly\Invoices\Enums\InvoiceState`
-   `Elegantly\Invoices\Enums\InvoiceType`

For custom states or types, you can define your own enums.

Make sure your custom enums implement the `Elegantly\Invoices\Contracts\HasLabel` contract, like so:

```php
namespace App\Enums;

use Elegantly\Invoices\Contracts\HasLabel;

enum InvoiceType: string implements HasLabel
{
    case Invoice = 'invoice';
    case Quote = 'quote';
    case Credit = 'credit';
    case Proforma = 'proforma';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoice => __('invoices::invoice.types.invoice'),
            self::Quote => __('invoices::invoice.types.quote'),
            self::Credit => __('invoices::invoice.types.credit'),
            self::Proforma => __('invoices::invoice.types.proforma'),
        };
    }
}
```

1.  **Create a Custom `Invoice` Model**:

Define your own `App\Models\Invoice` class that extends `\Elegantly\Invoices\Models\Invoice`.
In this custom model, override the `casts()` method to specify the Enum classes for the `type` and `state` attributes.

```php
namespace App\Models;

use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;

/**
 * @property InvoiceType $type
 * @property InvoiceState $state
 */
class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    protected $attributes = [
        'type' => InvoiceType::Invoice->value,
        'state' => InvoiceState::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(), // Merge with parent casts for other potential attributes
            'type' => InvoiceType::class,
            'state' => InvoiceState::class,
        ];
    }
}
```

2.  **Publish Package Configuration**:

If you haven't already, publish the package's configuration file:

```bash
php artisan vendor:publish --tag="invoices-config"
```

3.  **Update Configuration to Use Your Custom Model**:

Modify the `config/invoices.php` file and update the `model_invoice` key to point to your newly created custom `Invoice` model:

```php
return [
    // ...

    'model_invoice' => \App\Models\Invoice::class,

    // ...
];
```

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

-   [Quentin Gabriele](https://github.com/QuentinGab)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
