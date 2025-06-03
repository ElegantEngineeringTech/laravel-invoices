<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Language Lines
    |--------------------------------------------------------------------------
    */
    'invoice' => 'Invoice',
    'serial_number' => 'Invoice number',
    'due_at' => 'Date due',
    'created_at' => 'Date of issue',
    'paid_at' => 'Date of payment',
    'description' => 'Description',
    'total_amount' => 'Total',
    'tax' => 'Tax',
    'tax_label' => 'Tax',
    'subtotal_amount' => 'Subtotal',
    'subtotal_discounted_amount' => 'Subtotal After Discount',
    'amount' => 'Amount',
    'unit_price' => 'Unit price',
    'quantity' => 'Qty',
    'discount_name' => 'Discount',

    'from' => 'Bill From',
    'to' => 'Bill To',
    'shipping_to' => 'Shipping To',

    'states' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
    ],

    'types' => [
        'invoice' => 'Invoice',
        'quote' => 'Quote',
        'credit' => 'Credit note',
        'proforma' => 'Proforma invoice',
    ],

    'page' => 'Page',
];
