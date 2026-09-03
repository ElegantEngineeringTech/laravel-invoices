<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Koncept', 'pending' => 'Čeká na vyřízení', 'paid' => 'Zaplaceno', 'refunded' => 'Vráceno'],
    'types' => ['invoice' => 'Faktura', 'quote' => 'Cenová nabídka', 'credit' => 'Dobropis', 'proforma' => 'Proforma faktura'],
    'pdf' => [
        'page' => 'Strana', 'serial_number' => 'Číslo faktury', 'due_at' => 'Splatnost', 'created_at' => 'Vystaveno', 'paid_at' => 'Zaplaceno dne', 'description' => 'Popis', 'from' => 'Od', 'to' => 'Pro', 'shipping_to' => 'Doručit na',
        'items' => ['label' => 'Popis', 'quantity' => 'Množství', 'unit_price' => 'Jednotková cena', 'tax' => 'Daň', 'discount' => 'Sleva', 'amount' => 'Částka'],
        'summary' => ['tax' => 'Daň', 'subtotal' => 'Mezisoučet', 'discount' => 'Sleva', 'discounted' => 'Mezisoučet po slevě', 'total' => 'Celkem'],
    ],
];
