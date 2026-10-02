<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Melnraksts', 'pending' => 'Gaida', 'paid' => 'Apmaksāts', 'refunded' => 'Atmaksāts'],
    'types' => ['invoice' => 'Rēķins', 'quote' => 'Piedāvājums', 'credit' => 'Kredītrēķins', 'proforma' => 'Proforma rēķins'],
    'pdf' => [
        'page' => 'Lapa', 'serial_number' => 'Numurs', 'due_at' => 'Apmaksas termiņš', 'created_at' => 'Izveidots', 'paid_at' => 'Apmaksāts', 'description' => 'Apraksts', 'from' => 'No', 'to' => 'Kam', 'shipping_to' => 'Piegādāts uz',
        'items' => ['label' => 'Apraksts', 'quantity' => 'Daudzums', 'unit_price' => 'Vienības cena', 'tax' => 'Nodoklis', 'discount' => 'Atlaide', 'amount' => 'Summa'],
        'summary' => ['tax' => 'Nodoklis', 'subtotal' => 'Starpsumma', 'discount' => 'Atlaide', 'discounted' => 'Starpsumma pēc atlaides', 'total' => 'Kopā'],
    ],
];
