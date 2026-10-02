<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Utkast', 'pending' => 'Väntande', 'paid' => 'Betald', 'refunded' => 'Återbetald'],
    'types' => ['invoice' => 'Faktura', 'quote' => 'Offert', 'credit' => 'Kreditnota', 'proforma' => 'Proformafaktura'],
    'pdf' => [
        'page' => 'Sida', 'serial_number' => 'Nummer', 'due_at' => 'Förfaller', 'created_at' => 'Skapad den', 'paid_at' => 'Betald den', 'description' => 'Beskrivning', 'from' => 'Från', 'to' => 'För', 'shipping_to' => 'Levererat till',
        'items' => ['label' => 'Beskrivning', 'quantity' => 'Antal', 'unit_price' => 'Enhetspris', 'tax' => 'Skatt', 'discount' => 'Rabatt', 'amount' => 'Belopp'],
        'summary' => ['tax' => 'Skatt', 'subtotal' => 'Delsumma', 'discount' => 'Rabatt', 'discounted' => 'Delsumma efter rabatt', 'total' => 'Totalt'],
    ],
];
