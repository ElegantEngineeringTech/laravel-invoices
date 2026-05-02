<?php

declare(strict_types=1);

use Brick\Math\RoundingMode;
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

    'rounding_mode' => RoundingMode::HalfUp,

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
            // Required to load external CSS or images (e.g., from a URL or storage path)
            'isRemoteEnabled' => true,

            // Security: Keep false unless you specifically need to execute PHP inside the PDF template
            'isPhpEnabled' => false,

            // Adjusts line-height rendering to prevent text from looking vertically "cramped"
            'fontHeightRatio' => 0.8,

            /**
             * Supported values are: 'DejaVu Sans', 'Helvetica', 'Courier', 'Times', 'Symbol', 'ZapfDingbats'.
             */
            'defaultFont' => 'Helvetica',

            // Custom font storage: Required if using Google Fonts
            'fontDir' => storage_path('app/dompdf'),
            'fontCache' => storage_path('app/dompdf'),

            // System paths for temporary file processing and security boundaries
            'tempDir' => sys_get_temp_dir(),
            'chroot' => realpath(base_path()), // Limits Dompdf's file access to the project root
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
             * The color used for the PDF header/accent.
             */
            'color' => '#050038',

            /**
             * The CSS font-family name.
             *
             * Note: 'Arimo' is recommended as it provides superior symbol support
             * compared to Helvetica, while maintaining a similar aesthetic.
             */
            'font' => null,

            /**
             * List of Google Font URLs to be imported into the document.
             */
            'fonts' => [
                // 'https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400..700;1,400..700&display=swap',
            ],
        ],

    ],

];
