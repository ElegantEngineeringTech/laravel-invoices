# Everything You Need to Manage Invoices in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-invoices/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-invoices/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-invoices.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-invoices)

This package provides a robust, easy-to-use system for managing invoices within a Laravel application, with options for database storage, serial numbering, and PDF generation.

![laravel-invoices](https://repository-images.githubusercontent.com/527661364/f98e92f9-62a6-48a1-a7b1-1a587b92a430)

## Demo

Try out [the interactive demo](https://elegantly.dev/laravel-invoices) to explore package capabilities.

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [The PdfInvoice Class](#the-pdfinvoice-class)

    - [Full exemple](#full-exemple)
    - [Custom Fields](#custom-fields)
    - [Seller & Buyer](#seller--buyer)
    - [Shipping Address](#shipping-address)
    - [Logo](#logo)
    - [Invoice Items](#invoice-items)
    - [Tax](#tax)
    - [Discounts](#discounts)
    - [Customizing the Template](#customizing-the-template)
    - [Customizing the PDF Font](#customizing-the-pdf-font)
    - [Rendering the Invoice as a PDF](#rendering-the-invoice-as-a-pdf)
    - [Rendering the Invoice in a View](#rendering-the-invoice-in-a-view)
    - [Creating an Invoice Builder with Livewire](#creating-an-invoice-builder-with-livewire)

4. [The Invoice Eloquent Model](#the-invoice-eloquent-model)
    - [Basic Usage](#basic-usage)
    - [Serial Numbers](#serial-numbers)
        - [Unique Serial Numbers](#unique-serial-numbers)
        - [Serial Numbers with Multiple Prefixes and Series](#serial-numbers-with-multiple-prefixes-and-series)
        - [Customizing the Serial Number Format](#customizing-the-serial-number-format)
    - [Displaying a PDF](#displaying-a-pdf)
        - [Customizing the PDF](#customizing-the-pdf)
        - [Dynamic Logo](#dynamic-logo)
        - [Displaying Your Invoice as a PDF](#displaying-your-invoice-as-a-pdf)
        - [Attaching Your Invoice to an Email](#attaching-your-invoice-to-an-email)

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

## PDF Invoice

This package provides a powerfull standalone `PdfInvoice` class, it can:

-   Display your invoice in a PDF
-   Display your invoice in a view

The `PdfInvoice` is also integrated into the Invoice Eloquent Model so you can easily display a Invoice model into a PDF.

You can even use this package exclusively for the `PdfInvoice` class if that suits your needs.

### Full exemple

```php
use \Elegantly\Invoices\Pdf\PdfInvoice;
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;
use \Elegantly\Invoices\Support\Seller;
use \Elegantly\Invoices\Support\Buyer;
use \Elegantly\Invoices\Support\Address;
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
                '8405 Old James St.Rochester'
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
    logo: public_path('/images/logo.png'), // local path or base64 string
    template: "default.layout", // use the default template or use your own
    templateData: [ // custom date to pass to the template
        'color' => '#050038'
    ],
);
```

### Rendering the Invoice as a Pdf

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

### Download the Invoice as a PDF

#### From a controller

To download the pdf, simply return the `download` method.

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

To download the pdf from your Livewire component, your can use the `streamDownload` method like this:

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

### Rendering the Invoice into a view

You can render your invoice within a larger view, enabling you to create an "invoice builder" experience similar to the [interactive demo](https://elegantly.devlaravel-invoices).

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

### Adding a tax

Taxes are added individually to each `PdfInvoiceItem` and both percentage or amount are supported.

### Tax percentage

To add a tax represented as a percentage, simply specify the `tax_percentage` property.
The value should be a float between 0 and 100.

```php
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;

new PdfInvoiceItem(
    label: "Laratranslate Unlimitted" ,
    unit_price: Money::of(99.0, 'USD'),
    tax_percentage: 20.0, // a float between 0.0 and 100.0
),
```

### Tax amount

To add a tax represented as an amount, simply specify the `unit_tax` property.

```php
use \Elegantly\Invoices\Pdf\PdfInvoiceItem;

new PdfInvoiceItem(
    label: "Laratranslate Unlimitted" ,
    unit_price: Money::of(99.0, 'USD'),
    unit_tax: Money::of(19.8, 'USD'),
),
```

### Adding a discount

-   Discounts are represented by the `InvoiceDiscount` class and are added to `PdfInvoice`. They can't be attached to `PdfInvoiceItem` at the moment.
-   You can add multiple discounts.
-   Both `amount_off` and `percent_off` with `amount_off` having the priority.

### Discount as a percentage

To add a discount represented as a percentage, simply specify the `percent_off` property.

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

### Discount as an amount

To add a discount represented as an amount, simply specify the `amount_off` property.

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

## Customization

## Customizing the Font

See the [Dompdf font guide](https://github.com/dompdf/dompdf).

## Customizing the Template

To customize the invoice template, first publish the views using:

```bash
php artisan vendor:publish --tag="invoices-views"
```

Then modify the blade files to your liking.

> [!NOTE]
> If you add new CSS clas, don't forget to define them in the `style.blade.php` file.

Alternatively, you can create a completely custom template by editing the config file like this:

```php
return [

    // ...

    'pdf' => [

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

> [!WARNING]
> Your custom template file must be in `resources/views/vendor/invoices`

Ensure that your custom template follows the same structure and conventions as the default one to maintain compatibility with various use cases.

## The Invoice Eloquent Model

The `Invoice`Model design is very similar to the `PdfInvoice`one.

It provides a powerfull way:

-   to genereate unique and complex serial number
-   to attach your invoice to any model
-   to join your invoice to a email

> [!NOTE]
> Don't forget to publish and run the migrations

### Full exemple

Here is a complete exemple of how you can create and store an invoice.

We will consider the following architecture:

-   Teams have users
-   Teams have invoices
-   Invoices are attached to offers

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
    'type'=> "invoice",
    'state'=> "paid",
    'seller_information'=> config('invoices.default_seller'),
    'buyer_information'=>[
        'company'=> "Doe Corporation" // (optional)
        'name'=> 'John Doe', // (optional)
        'address'=> [
            'street'=> '8405 Old James St.Rochester',
            'city'=> 'New York',
            'postal_code'=> '14609',
            'state'=> 'NY',
            'country'=> 'United States',
        ],
        'shipping_address'=> [ // (optional)
            'street'=> [ // multiple lines street
                '8405 Old James St.Rochester'
                'Apartment 1',
            ],
            'city'=> 'New York',
            'postal_code'=> '14609',
            'state'=> 'NY',
            'country'=> 'United States',
        ]
        'email'=> 'john.doe@example.com',
        'fields'=> [
            // Custom fields to display with the buyer
            "foo" => "bar"
        ]
    ],
    'description'=> "An invoice description",
    'due_at'=> now(),
    'paid_at'=> now(),
    'tax_type'=> "eu_VAT_FR",
    'tax_exempt'=> null,
);

// Learn more about the serial number in the next section
$invoice->configureSerialNumber(
    prefix: "ORD",
    serie: $customer->id,
    year: now()->format('Y'),
    month: now()->format('m')
)

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

### Multiple Prefixes and Series for Serial Numbers

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

### From Invoice to PdfInvoice

You can obtained a `PdfInvoice` class from your `Invoice` model by calling the `toPdfInvoice` method:

```php
$invoice = Invoice::first();

$pdfInvoice = $invoice->toPdfInvoice();
```

### Displaying/Downloading/Storing Your Invoice as a PDF

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

### Mailable Attachment

You can easily attach an invoice to your `Mailable` like this:

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

### Notification Attachment

You can easily attach an invoice to your notification like this:

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
            ->attach($this->invoice->toMailAttachment());
    }
}
```

### Customizing the PDF

To customize how your model is converted to a `PdfInvoice`, follow these steps:

1. **Create a Custom Model**: Define your own `\App\Models\Invoice` class and ensure it extends the base `\Elegantly\Invoices\Models\Invoice` class.

```php
namespace App\Models;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    // ...
}
```

2. **Override the `toPdfInvoice` Method**: Implement your specific logic within the `toPdfInvoice` method to control the customization.

```php
namespace App\Models;

use Elegantly\Invoices\Pdf\PdfInvoice;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    function toPdfInvoice(): PdfInvoice
    {
        return new PdfInvoice(
            // ...
        );
    }
}
```

3. **Update the Configuration File**: Publish the package configuration file and update the `model_invoice` key as shown below:

```bash
php artisan vendor:publish --tag="invoices-config"
```

```php
return [
    // ...

    'model_invoice' => \App\Models\Invoice::class,

    // ...
];
```

### Casting states and types to Enums

By default, `type` and `state` properties are just strings and are not cast to an enum so you can use as many value as you need.

However, you might want to cast those properties to your enums or use the provided ones.

To do so, you will have to customize the `Invoice` model by:

1. Create your own Invoice class and extend `\Elegantly\Invoices\Models\Invoice`

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
}
```

2. Publish the configs

```bash
php artisan vendor:publish --tag="invoices-config"
```

3. Update the `model_invoice` key in the config:

```php
return [
    // ...

    'model_invoice' => \App\Models\Invoice::class,

    // ...
];
```

### Dynamic Logo

If you need to set the logo dynamically on the invoice, for example, when allowing users to upload their own logo, you can achieve this by overriding the `getLogo` method.

1. Create your own Invoice class and extend `\Elegantly\Invoices\Models\Invoice`

> [!NOTE]  
> The returned value must be either a base64-encoded data URL or a path to a locally accessible file.

```php
namespace App\Models;

use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    public function getLogo(): ?string
    {
        $file = new File(public_path('logo.png'));
        $mime = $file->getMimeType();
        $logo = "data:{$mime};base64," . base64_encode($file->getContent());

        return $logo;
    }
}
```

2. Publish the configs

```bash
php artisan vendor:publish --tag="invoices-config"
```

3. Update the `model_invoice` key in the config:

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
