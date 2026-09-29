<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Kladde', 'pending' => 'Afventer', 'paid' => 'Betalt', 'refunded' => 'Refunderet'],
    'types' => ['invoice' => 'Faktura', 'quote' => 'Tilbud', 'credit' => 'Kreditnota', 'proforma' => 'Proformafaktura'],
    'pdf' => [
        'page' => 'Side', 'serial_number' => 'Nummer', 'due_at' => 'Forfalder', 'created_at' => 'Oprettet den', 'paid_at' => 'Betalt den', 'description' => 'Beskrivelse', 'from' => 'Fra', 'to' => 'Til', 'shipping_to' => 'Leveret til',
        'items' => ['label' => 'Beskrivelse', 'quantity' => 'Antal', 'unit_price' => 'Enhedspris', 'tax' => 'Moms', 'discount' => 'Rabat', 'amount' => 'Beløb'],
        'summary' => ['tax' => 'Moms', 'subtotal' => 'Subtotal', 'discount' => 'Rabat', 'discounted' => 'Subtotal efter rabat', 'total' => 'I alt'],
    ],
];
