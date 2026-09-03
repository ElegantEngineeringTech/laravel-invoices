<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Luonnos', 'pending' => 'Odottaa', 'paid' => 'Maksettu', 'refunded' => 'Hyvitetty'],
    'types' => ['invoice' => 'Lasku', 'quote' => 'Tarjous', 'credit' => 'Hyvityslasku', 'proforma' => 'Proforma-lasku'],
    'pdf' => [
        'page' => 'Sivu', 'serial_number' => 'Laskun numero', 'due_at' => 'Eräpäivä', 'created_at' => 'Luotu', 'paid_at' => 'Maksettu', 'description' => 'Kuvaus', 'from' => 'Lähettäjä', 'to' => 'Vastaanottaja', 'shipping_to' => 'Toimitusosoite',
        'items' => ['label' => 'Kuvaus', 'quantity' => 'Määrä', 'unit_price' => 'Yksikköhinta', 'tax' => 'Vero', 'discount' => 'Alennus', 'amount' => 'Summa'],
        'summary' => ['tax' => 'Vero', 'subtotal' => 'Välisumma', 'discount' => 'Alennus', 'discounted' => 'Välisumma alennuksen jälkeen', 'total' => 'Yhteensä'],
    ],
];
