<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Concept', 'pending' => 'In afwachting', 'paid' => 'Betaald', 'refunded' => 'Terugbetaald'],
    'types' => ['invoice' => 'Factuur', 'quote' => 'Offerte', 'credit' => 'Creditnota', 'proforma' => 'Proformafactuur'],
    'pdf' => [
        'page' => 'Pagina', 'serial_number' => 'Nummer', 'due_at' => 'Vervaldatum', 'created_at' => 'Aangemaakt op', 'paid_at' => 'Betaald op', 'description' => 'Omschrijving', 'from' => 'Van', 'to' => 'Voor', 'shipping_to' => 'Geleverd aan',
        'items' => ['label' => 'Omschrijving', 'quantity' => 'Aantal', 'unit_price' => 'Eenheidsprijs', 'tax' => 'Belasting', 'discount' => 'Korting', 'amount' => 'Bedrag'],
        'summary' => ['tax' => 'Belasting', 'subtotal' => 'Subtotaal', 'discount' => 'Korting', 'discounted' => 'Subtotaal na korting', 'total' => 'Totaal'],
    ],
];
