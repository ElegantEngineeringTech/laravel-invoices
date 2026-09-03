<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Rascunho', 'pending' => 'Pendente', 'paid' => 'Paga', 'refunded' => 'Reembolsada'],
    'types' => ['invoice' => 'Fatura', 'quote' => 'Orçamento', 'credit' => 'Nota de crédito', 'proforma' => 'Fatura proforma'],
    'pdf' => [
        'page' => 'Página', 'serial_number' => 'Número da fatura', 'due_at' => 'Vencimento', 'created_at' => 'Criada em', 'paid_at' => 'Paga em', 'description' => 'Descrição', 'from' => 'De', 'to' => 'Para', 'shipping_to' => 'Enviar para',
        'items' => ['label' => 'Descrição', 'quantity' => 'Qtd.', 'unit_price' => 'Preço unitário', 'tax' => 'Imposto', 'discount' => 'Desconto', 'amount' => 'Valor'],
        'summary' => ['tax' => 'Imposto', 'subtotal' => 'Subtotal', 'discount' => 'Desconto', 'discounted' => 'Subtotal após desconto', 'total' => 'Total'],
    ],
];
