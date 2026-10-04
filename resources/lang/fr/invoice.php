<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Language Lines
    |--------------------------------------------------------------------------
    */
    'states' => [
        'draft' => 'Brouillon',
        'pending' => 'En attente',
        'paid' => 'Payée',
        'refunded' => 'Remboursée',
    ],

    'types' => [
        'invoice' => 'Facture',
        'quote' => 'Devis',
        'credit' => "Facture d'avoir",
        'proforma' => 'Facture Proforma',
    ],

    'pdf' => [
        'page' => 'Page',
        'serial_number' => 'Numéro',
        'due_at' => 'Due le',
        'created_at' => 'Créée le',
        'paid_at' => 'Payée le',
        'description' => 'Description',
        'from' => 'De',
        'to' => 'Pour',
        'shipping_to' => 'Livré à',
        'items' => [
            'label' => 'Description',
            'quantity' => 'Qté',
            'unit_price' => 'Prix unitaire',
            'tax' => 'Tax',
            'discount' => 'Remise',
            'amount' => 'Montant',
        ],
        'summary' => [
            'tax' => 'Tax',
            'subtotal' => 'Sous-total',
            'discount' => 'Remise',
            'discounted' => 'Sous-total après remise',
            'total' => 'Total',
        ],
    ],
];
