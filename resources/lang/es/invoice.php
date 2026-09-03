<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Borrador', 'pending' => 'Pendiente', 'paid' => 'Pagada', 'refunded' => 'Reembolsada'],
    'types' => ['invoice' => 'Factura', 'quote' => 'Presupuesto', 'credit' => 'Factura rectificativa', 'proforma' => 'Factura proforma'],
    'pdf' => [
        'page' => 'Página', 'serial_number' => 'Número de factura', 'due_at' => 'Vencimiento', 'created_at' => 'Creada el', 'paid_at' => 'Pagada el', 'description' => 'Descripción', 'from' => 'De', 'to' => 'Para', 'shipping_to' => 'Enviar a',
        'items' => ['label' => 'Descripción', 'quantity' => 'Cant.', 'unit_price' => 'Precio unitario', 'tax' => 'Impuesto', 'discount' => 'Descuento', 'amount' => 'Importe'],
        'summary' => ['tax' => 'Impuesto', 'subtotal' => 'Subtotal', 'discount' => 'Descuento', 'discounted' => 'Subtotal después del descuento', 'total' => 'Total'],
    ],
];
