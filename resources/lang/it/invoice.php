<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Bozza', 'pending' => 'In attesa', 'paid' => 'Pagata', 'refunded' => 'Rimborsata'],
    'types' => ['invoice' => 'Fattura', 'quote' => 'Preventivo', 'credit' => 'Nota di credito', 'proforma' => 'Fattura proforma'],
    'pdf' => [
        'page' => 'Pagina', 'serial_number' => 'Numero fattura', 'due_at' => 'Scadenza', 'created_at' => 'Creata il', 'paid_at' => 'Pagata il', 'description' => 'Descrizione', 'from' => 'Da', 'to' => 'A', 'shipping_to' => 'Spedire a',
        'items' => ['label' => 'Descrizione', 'quantity' => 'Qtà', 'unit_price' => 'Prezzo unitario', 'tax' => 'Imposta', 'discount' => 'Sconto', 'amount' => 'Importo'],
        'summary' => ['tax' => 'Imposta', 'subtotal' => 'Subtotale', 'discount' => 'Sconto', 'discounted' => 'Subtotale dopo lo sconto', 'total' => 'Totale'],
    ],
];
