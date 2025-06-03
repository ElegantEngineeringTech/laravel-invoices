<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Language Lines
    |--------------------------------------------------------------------------
    */
    'invoice' => 'Rechnung',
    'serial_number' => 'Rechnung Nr.',
    'due_at' => 'Fällig am',
    'created_at' => 'Rechnungsdatum',
    'paid_at' => 'Bezahlt am',
    'description' => 'Beschreibung',
    'total_amount' => 'Gesamtbetrag',
    'tax' => 'Steuer',
    'tax_label' => 'Steuer',
    'subtotal_amount' => 'Zwischensumme',
    'subtotal_discounted_amount' => 'Zwischensumme nach Rabatt',
    'amount' => 'Betrag',
    'unit_price' => 'Einzelpreis',
    'quantity' => 'Menge',
    'discount_name' => 'Rabatt',

    'from' => 'Rechnungsersteller',
    'to' => 'Rechnungsempfänger',
    'shipping_to' => 'Versandadresse',

    'states' => [
        'draft' => 'Entwurf',
        'pending' => 'Ausstehend',
        'paid' => 'Bezahlt',
        'refunded' => 'Erstattet',
    ],

    'types' => [
        'invoice' => 'Rechnung',
        'quote' => 'Angebot',
        'credit' => 'Gutschrift',
        'proforma' => 'Proforma-Rechnung',
    ],

    'page' => 'Seite',
];
