<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Nacrt', 'pending' => 'Na čekanju', 'paid' => 'Plaćeno', 'refunded' => 'Vraćeno'],
    'types' => ['invoice' => 'Račun', 'quote' => 'Ponuda', 'credit' => 'Odobrenje', 'proforma' => 'Predračun'],
    'pdf' => [
        'page' => 'Stranica', 'serial_number' => 'Broj', 'due_at' => 'Dospijeće', 'created_at' => 'Stvoren', 'paid_at' => 'Plaćen', 'description' => 'Opis', 'from' => 'Od', 'to' => 'Za', 'shipping_to' => 'Dostavljeno na',
        'items' => ['label' => 'Opis', 'quantity' => 'Količina', 'unit_price' => 'Jedinična cijena', 'tax' => 'Porez', 'discount' => 'Popust', 'amount' => 'Iznos'],
        'summary' => ['tax' => 'Porez', 'subtotal' => 'Međuzbroj', 'discount' => 'Popust', 'discounted' => 'Međuzbroj nakon popusta', 'total' => 'Ukupno'],
    ],
];
