<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Koncept', 'pending' => 'Čaká sa', 'paid' => 'Zaplatené', 'refunded' => 'Vrátené'],
    'types' => ['invoice' => 'Faktúra', 'quote' => 'Cenová ponuka', 'credit' => 'Dobropis', 'proforma' => 'Proforma faktúra'],
    'pdf' => [
        'page' => 'Strana', 'serial_number' => 'Číslo faktúry', 'due_at' => 'Splatnosť', 'created_at' => 'Vystavené', 'paid_at' => 'Zaplatené dňa', 'description' => 'Popis', 'from' => 'Od', 'to' => 'Pre', 'shipping_to' => 'Doručiť na',
        'items' => ['label' => 'Popis', 'quantity' => 'Množstvo', 'unit_price' => 'Jednotková cena', 'tax' => 'Daň', 'discount' => 'Zľava', 'amount' => 'Suma'],
        'summary' => ['tax' => 'Daň', 'subtotal' => 'Medzisúčet', 'discount' => 'Zľava', 'discounted' => 'Medzisúčet po zľave', 'total' => 'Celkom'],
    ],
];
