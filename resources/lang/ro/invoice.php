<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Ciornă', 'pending' => 'În așteptare', 'paid' => 'Plătită', 'refunded' => 'Rambursată'],
    'types' => ['invoice' => 'Factură', 'quote' => 'Ofertă', 'credit' => 'Factură storno', 'proforma' => 'Factură proformă'],
    'pdf' => [
        'page' => 'Pagina', 'serial_number' => 'Numărul facturii', 'due_at' => 'Scadentă la', 'created_at' => 'Creată la', 'paid_at' => 'Plătită la', 'description' => 'Descriere', 'from' => 'De la', 'to' => 'Către', 'shipping_to' => 'Livrare la',
        'items' => ['label' => 'Descriere', 'quantity' => 'Cant.', 'unit_price' => 'Preț unitar', 'tax' => 'Taxă', 'discount' => 'Reducere', 'amount' => 'Sumă'],
        'summary' => ['tax' => 'Taxă', 'subtotal' => 'Subtotal', 'discount' => 'Reducere', 'discounted' => 'Subtotal după reducere', 'total' => 'Total'],
    ],
];
