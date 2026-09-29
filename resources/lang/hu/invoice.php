<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Piszkozat', 'pending' => 'Függőben', 'paid' => 'Fizetve', 'refunded' => 'Visszatérítve'],
    'types' => ['invoice' => 'Számla', 'quote' => 'Ajánlat', 'credit' => 'Jóváíró számla', 'proforma' => 'Díjbekérő'],
    'pdf' => [
        'page' => 'Oldal', 'serial_number' => 'Szám', 'due_at' => 'Fizetési határidő', 'created_at' => 'Létrehozva', 'paid_at' => 'Fizetve', 'description' => 'Leírás', 'from' => 'Feladó', 'to' => 'Címzett', 'shipping_to' => 'Kiszállítva ide',
        'items' => ['label' => 'Leírás', 'quantity' => 'Mennyiség', 'unit_price' => 'Egységár', 'tax' => 'Adó', 'discount' => 'Kedvezmény', 'amount' => 'Összeg'],
        'summary' => ['tax' => 'Adó', 'subtotal' => 'Részösszeg', 'discount' => 'Kedvezmény', 'discounted' => 'Kedvezményes részösszeg', 'total' => 'Összesen'],
    ],
];
