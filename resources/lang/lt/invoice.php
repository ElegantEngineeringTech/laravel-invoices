<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Juodraštis', 'pending' => 'Laukiama', 'paid' => 'Apmokėta', 'refunded' => 'Grąžinta'],
    'types' => ['invoice' => 'Sąskaita faktūra', 'quote' => 'Pasiūlymas', 'credit' => 'Kreditinė sąskaita', 'proforma' => 'Išankstinė sąskaita'],
    'pdf' => [
        'page' => 'Puslapis', 'serial_number' => 'Numeris', 'due_at' => 'Mokėjimo terminas', 'created_at' => 'Sukurta', 'paid_at' => 'Apmokėta', 'description' => 'Aprašymas', 'from' => 'Nuo', 'to' => 'Kam', 'shipping_to' => 'Pristatyta į',
        'items' => ['label' => 'Aprašymas', 'quantity' => 'Kiekis', 'unit_price' => 'Vieneto kaina', 'tax' => 'Mokestis', 'discount' => 'Nuolaida', 'amount' => 'Suma'],
        'summary' => ['tax' => 'Mokestis', 'subtotal' => 'Tarpinė suma', 'discount' => 'Nuolaida', 'discounted' => 'Tarpinė suma po nuolaidos', 'total' => 'Iš viso'],
    ],
];
