<?php

declare(strict_types=1);

use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;

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
