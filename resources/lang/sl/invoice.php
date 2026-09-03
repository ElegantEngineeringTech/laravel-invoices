<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Osnutek', 'pending' => 'Na čakanju', 'paid' => 'Plačano', 'refunded' => 'Povrnjeno'],
    'types' => ['invoice' => 'Račun', 'quote' => 'Ponudba', 'credit' => 'Dobropis', 'proforma' => 'Predračun'],
    'pdf' => [
        'page' => 'Stran', 'serial_number' => 'Številka računa', 'due_at' => 'Rok plačila', 'created_at' => 'Ustvarjeno', 'paid_at' => 'Plačano', 'description' => 'Opis', 'from' => 'Od', 'to' => 'Za', 'shipping_to' => 'Dostaviti na',
        'items' => ['label' => 'Opis', 'quantity' => 'Količina', 'unit_price' => 'Cena na enoto', 'tax' => 'Davek', 'discount' => 'Popust', 'amount' => 'Znesek'],
        'summary' => ['tax' => 'Davek', 'subtotal' => 'Vmesni seštevek', 'discount' => 'Popust', 'discounted' => 'Vmesni seštevek po popustu', 'total' => 'Skupaj'],
    ],
];
