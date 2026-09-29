<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Abbozz', 'pending' => 'Pendenti', 'paid' => 'Imħallsa', 'refunded' => 'Rimborżata'],
    'types' => ['invoice' => 'Fattura', 'quote' => 'Kwotazzjoni', 'credit' => 'Nota ta’ kreditu', 'proforma' => 'Fattura proforma'],
    'pdf' => [
        'page' => 'Paġna', 'serial_number' => 'Numru', 'due_at' => 'Dovuta fil', 'created_at' => 'Maħluqa fil', 'paid_at' => 'Imħallsa fil', 'description' => 'Deskrizzjoni', 'from' => 'Minn', 'to' => 'Għal', 'shipping_to' => 'Konsenjata lil',
        'items' => ['label' => 'Deskrizzjoni', 'quantity' => 'Kwantità', 'unit_price' => 'Prezz unitarju', 'tax' => 'Taxxa', 'discount' => 'Skont', 'amount' => 'Ammont'],
        'summary' => ['tax' => 'Taxxa', 'subtotal' => 'Subtotal', 'discount' => 'Skont', 'discounted' => 'Subtotal wara l-iskont', 'total' => 'Total'],
    ],
];
