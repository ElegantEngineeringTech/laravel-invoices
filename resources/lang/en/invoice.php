<?php

declare(strict_types=1);

return [
    'states' => [
        'draft' => 'Draft', 'pending' => 'Pending', 'paid' => 'Paid', 'refunded' => 'Refunded',
    ],
    'types' => [
        'invoice' => 'Invoice', 'quote' => 'Quote', 'credit' => 'Credit note', 'proforma' => 'Pro forma invoice',
    ],
    'pdf' => [
        'page' => 'Page', 'serial_number' => 'Number', 'due_at' => 'Due on', 'created_at' => 'Created on', 'paid_at' => 'Paid on', 'description' => 'Description', 'from' => 'From', 'to' => 'For', 'shipping_to' => 'Delivered to',
        'items' => ['label' => 'Description', 'quantity' => 'Qty', 'unit_price' => 'Unit price', 'tax' => 'Tax', 'discount' => 'Discount', 'amount' => 'Amount'],
        'summary' => ['tax' => 'Tax', 'subtotal' => 'Subtotal', 'discount' => 'Discount', 'discounted' => 'Subtotal after discount', 'total' => 'Total'],
    ],
];
