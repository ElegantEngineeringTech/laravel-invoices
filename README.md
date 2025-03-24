# Everything You Need to Manage Invoices in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)

This package provides a robust, easy-to-use system for managing invoices within a Laravel application, with options for database storage, serial numbering, and PDF generation.

![laravel-invoices](https://repository-images.githubusercontent.com/527661364/030d6384-41e5-454c-a9f5-9f6eebe93da7)

## Demo

Try out [the interactive demo](https://elegantengineering.tech/laravel-invoices) to explore package capabilities.

## Table of Contents

1. [Requirements](#requirements)

1. [Installation](#installation)

1. [The Invoice Eloquent Model](#the-invoice-eloquent-model)

    - [Storing an Invoice in Your Database](#storing-an-invoice-in-your-database)
    - [Automatically Generating Unique Serial Numbers](#automatically-generating-unique-serial-numbers)
    - [Managing Multiple Prefixes and Series](#managing-multiple-prefixes-and-series)
    - [Customizing the Serial Number Format](#customizing-the-serial-number-format)
    - [Displaying Your Invoice as a PDF](#displaying-your-invoice-as-a-pdf)
    - [Attaching Your Invoice to an Email](#attaching-your-invoice-to-an-email)
    - [Customizing the PDF Invoice](#customizing-the-pdf-invoice)
    - [Adding a Dynamic Logo](#adding-a-dynamic-logo)

1. [The PdfInvoice Class](#the-pdfinvoice-class)

    - [Rendering the Invoice as a PDF](#rendering-the-invoice-as-a-pdf)
    - [Rendering the Invoice in a View](#rendering-the-invoice-in-a-view)

1. [Customization](#customization)
    - [Customizing the Font](#customizing-the-font)
    - [Customizing the Template](#customizing-the-template)

## Requirements

-   PHP 8.1+
-   Laravel 11.0+
-   `dompdf/dompdf` for PDF rendering
-   `elegantly/laravel-money` for money computation

## Installation

You can install the package via composer:

```bash
composer require elegantly/laravel-invoices
```

You can publish and run the migrations with:

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
        'company_number' => null,
    ],

    /**
     * ISO 4217 currency code
     */
    'default_currency' => 'USD',

    'pdf' => [

        'paper' => [
            'paper' => 'a4',
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

## The Invoice Eloquent Model

### Storing an Invoice in Your Database

You can store an Invoice in your database using the Eloquent Model: `Elegantly\Invoices\Models\Invoice`.

> [!NOTE]
> Don't forget to publish and run the migrations

Here is a full example:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;

// Let's say your app edit invoices for your users
$customer = User::find(1);
// Let's imagine that your users have purchased something in your app
$order = Order::find(2);

$invoice = new Invoice([
    'type' => InvoiceType::Invoice,
    'state' => InvoiceState::Draft,
    'description' => 'A description for my invoice',
    'seller_information' => config('invoices.default_seller'),
    'buyer_information' => [
        'name' => 'John Doe',
        'address' => [
            'street' => '8405 Old James St.Rochester',
            'city' => 'New York',
            'postal_code' => '14609',
            'state' => 'New York (NY)',
            'country' => 'United States',
        ],
        'email' => 'john.doe@example.com',
        'tax_number' => "FR123456789",
    ],
    // ...
]);

$invoice->buyer()->associate($customer); // optionnally associate the invoice to any model
$invoice->invoiceable()->associate($order); // optionnally associate the invoice to any model

$invoice->save();

$invoice->items()->saveMany([
    new InvoiceItem([
        'unit_price' => Money::of(100, 'USD'),
        'unit_tax' => Money::of(20, 'USD'),
        'currency' => 'USD',
        'quantity' => 1,
        'label' => 'A label for my item',
        'description' => 'A description for my item',
    ]),
]);
```

### Automatically Generating Unique Serial Numbers

This package provides a simple and reliable way to generate serial numbers automatically, such as "INV240001".

You can configure the format of your serial numbers in the configuration file. The default format is `PPYYCCCC`, where each letter has a specific meaning (see the config file for details).

When `invoices.serial_number.auto_generate` is set to `true`, a unique serial number is assigned to each new invoice automatically.

Serial numbers are generated sequentially, with each new serial number based on the latest available one. To define what qualifies as the `previous` serial number, you can extend the `Elegantly\Invoices\Models\Invoice` class and override the `getPreviousInvoice` method.

By default, the previous invoice is determined based on criteria such as prefix, series, year, and month for accurate, scoped numbering.

### Managing Multiple Prefixes and Series

In more complex applications, you may need to use different prefixes and/or series for your invoices.

For instance, you might want to define a unique series for each user, creating serial numbers that look like: `INV0001-2400X`, where `0001` represents the user’s ID, `24` the year and `X` the index of the invoice.

> [!NOTE]
> When using IDs for series, it's recommended to plan for future growth to avoid overflow.
> Even if you have a limited number of users now, ensure that the ID can accommodate the maximum number of digits allowed by the serial number format.

When creating an invoice, you can dynamically specify the prefix and series as follows:

```php
use Elegantly\Invoices\Models\Invoice;
$invoice = new Invoice();

$invoice->configureSerialNumber(
    prefix: "ORG",
    serie: $buyer_id,
);
```

### Customizing the Serial Number Format

In most cases, the format of your serial numbers should remain consistent, so it's recommended to set it in the configuration file.

The format you choose will determine the types of information you need to provide to `configureSerialNumber`.

Below is an example of the most complex serial number format you can create with this package:

```php

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

### Displaying Your Invoice as a PDF

The Invoice model has a `toPdfInvoice()` that return a `PdfInvoice` class.

You can stream the `PdfInvoice` instance as a response, or download it:

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
}
```

### Attaching Your Invoice to an Email

The `Invoice` model provide a `toMailAttachment` method making it easy to use with `Mailable`

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

### Customizing the PDF Invoice

To customize how your model is converted to a `PdfInvoice`, follow these steps:

1. **Create a Custom Model**: Define your own `\App\Models\Invoice` class and ensure it extends the base `\Elegantly\Invoices\Models\Invoice` class.

2. **Override the `toPdfInvoice` Method**: Implement your specific logic within the `toPdfInvoice` method to control the customization.

3. **Update the Configuration File**: Publish the package configuration file and update the `model_invoice` key as shown below:

    ```php
    return [
        'model_invoice' => \App\Models\Invoice::class,
    ];
    ```

### Adding a Dynamic Logo

If you need to set the logo dynamically on the invoice, for example, when allowing users to upload their own logo, you can achieve this by overriding the `getLogo` method. Follow the steps outlined in the [Customizing the PDF Invoice](#customizing-the-pdf-invoice) section to create your own Model.

To dynamically set the logo, define the `getLogo` method as shown below:

```php
class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    // ...

    public function getLogo(): ?string
    {
        $file = new File(public_path('logo.png'));
        $mime = $file->getMimeType();
        $logo = "data:{$mime};base64," . base64_encode($file->getContent());

        return $logo;
    }
}
```

> [!NOTE]  
> The returned value must be either a base64-encoded data URL or a path to a locally accessible file.

## The PdfInvoice Class

This package includes a standalone `PdfInvoice` class, making it easy to render invoices as a PDF or directly within a view.

You can even use this package exclusively for the `PdfInvoice` class if that suits your needs.

Here’s an example of a fully configured `PdfInvoice` instance:

```php
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Support\Address;
use Elegantly\Invoices\Support\Seller;
use Elegantly\Invoices\Support\Buyer;

$pdfInvoice = new PdfInvoice(
    name: "Invoice",
    state: "Paid",
    serial_number: "INV-241200001",
    seller: new Seller(
        name: 'elegantly',
        address: new Address(
            street: "Place de l'Opéra",
            city: 'Paris',
            postal_code: '75009',
            country: 'France',
        ),
        email: 'john.doe@example.com',
        tax_number: 'FR123456789',
        fields: [
            "foo" => "bar"
        ]
    ),
    buyer: new Buyer(
        name: 'John Doe',
        address: new Address(
            street: '8405 Old James St.Rochester',
            city: 'New York',
            postal_code: '14609',
            state: 'New York (NY)',
            country: 'United States',
        ),
        email: 'john.doe@example.com',
        data: [
            "foo" => "bar"
        ]
    ),
    description: "An invoice description",
    created_at: now(),
    due_at: now(),
    paid_at: now(),
    tax_label: "VAT (20%)",
    fields: [],
    items: [],
    discounts: [],
    logo: public_path('/images/logo.png'),
    template: "default.layout",
    templateData: [
        'color' => '#050038'
    ],
)
```

### Rendering the Invoice as a PDF

#### In a Controller

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

    public function showAsPdf()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return $pdfInvoice->stream();
    }

    public function download()
    {
        $pdfInvoice = new PdfInvoice(
            // ...
        );

        return $pdfInvoice->download();
    }
}
```

#### In a Livewire Component

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
        }, 'laravel-invoices-demo.pdf');
    }
}
```

#### Store in a file

```php

$pdfInvoice = new PdfInvoice(
    // ...
);

Storage::put(
    "path/to/invoice.pdf",
    $pdfInvoice->getPdfOutput()
);
```

### Rendering the Invoice in a View

You can render your invoice within a larger view, enabling you to create an "invoice builder" experience similar to the [interactive demo](https://elegantengineering.tech/laravel-invoices).

To achieve this, include the main part of the invoice in your view as shown below:

```blade
<div class="aspect-[210/297] bg-white shadow-md">
    @include('invoices::default.invoice', ['invoice' => $invoice])
</div>
```

This approach allows you to seamlessly integrate the invoice into a dynamic and customizable user interface.

> [!NOTE]  
> The default template is styled using Tailwind-compatible syntax, making it seamlessly compatible with websites that use Tailwind.  
> If you don’t use Tailwind, the styling may not render as intended.

## Customization

## Customizing the Font

See the [Dompdf font guide](https://github.com/dompdf/dompdf).

## Customizing the Template

To customize the invoice template, you can publish the provided views and modify them as needed.

Alternatively, you can create a completely custom template. Ensure that your custom template follows the same structure and conventions as the default one to maintain compatibility with various use cases.

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
