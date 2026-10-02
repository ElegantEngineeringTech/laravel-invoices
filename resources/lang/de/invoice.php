<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Entwurf', 'pending' => 'Ausstehend', 'paid' => 'Bezahlt', 'refunded' => 'Erstattet'],
    'types' => ['invoice' => 'Rechnung', 'quote' => 'Angebot', 'credit' => 'Gutschrift', 'proforma' => 'Proforma-Rechnung'],
    'pdf' => [
        'page' => 'Seite', 'serial_number' => 'Nummer', 'due_at' => 'Fällig am', 'created_at' => 'Erstellt am', 'paid_at' => 'Bezahlt am', 'description' => 'Beschreibung', 'from' => 'Von', 'to' => 'Für', 'shipping_to' => 'Geliefert an',
        'items' => ['label' => 'Beschreibung', 'quantity' => 'Menge', 'unit_price' => 'Einzelpreis', 'tax' => 'Steuer', 'discount' => 'Rabatt', 'amount' => 'Betrag'],
        'summary' => ['tax' => 'Steuer', 'subtotal' => 'Zwischensumme', 'discount' => 'Rabatt', 'discounted' => 'Zwischensumme nach Rabatt', 'total' => 'Gesamt'],
    ],
];
