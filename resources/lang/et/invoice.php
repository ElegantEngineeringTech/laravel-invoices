<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Mustand', 'pending' => 'Ootel', 'paid' => 'Tasunud', 'refunded' => 'Tagastatud'],
    'types' => ['invoice' => 'Arve', 'quote' => 'Pakkumine', 'credit' => 'Kreeditarve', 'proforma' => 'Proformaarve'],
    'pdf' => [
        'page' => 'Lehekülg', 'serial_number' => 'Number', 'due_at' => 'Tasumise tähtaeg', 'created_at' => 'Loodud', 'paid_at' => 'Tasunud', 'description' => 'Kirjeldus', 'from' => 'Saatja', 'to' => 'Saaja', 'shipping_to' => 'Kohale toimetatud',
        'items' => ['label' => 'Kirjeldus', 'quantity' => 'Kogus', 'unit_price' => 'Ühikhind', 'tax' => 'Maks', 'discount' => 'Soodustus', 'amount' => 'Summa'],
        'summary' => ['tax' => 'Maks', 'subtotal' => 'Vahesumma', 'discount' => 'Soodustus', 'discounted' => 'Vahesumma pärast soodustust', 'total' => 'Kokku'],
    ],
];
