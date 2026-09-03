<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Wersja robocza', 'pending' => 'Oczekuje', 'paid' => 'Opłacona', 'refunded' => 'Zwrócona'],
    'types' => ['invoice' => 'Faktura', 'quote' => 'Oferta', 'credit' => 'Faktura korygująca', 'proforma' => 'Faktura pro forma'],
    'pdf' => [
        'page' => 'Strona', 'serial_number' => 'Numer faktury', 'due_at' => 'Termin płatności', 'created_at' => 'Wystawiono', 'paid_at' => 'Opłacono', 'description' => 'Opis', 'from' => 'Od', 'to' => 'Dla', 'shipping_to' => 'Dostawa do',
        'items' => ['label' => 'Opis', 'quantity' => 'Ilość', 'unit_price' => 'Cena jednostkowa', 'tax' => 'Podatek', 'discount' => 'Rabat', 'amount' => 'Kwota'],
        'summary' => ['tax' => 'Podatek', 'subtotal' => 'Suma częściowa', 'discount' => 'Rabat', 'discounted' => 'Suma częściowa po rabacie', 'total' => 'Razem'],
    ],
];
