<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Чернова', 'pending' => 'В очакване', 'paid' => 'Платена', 'refunded' => 'Възстановена'],
    'types' => ['invoice' => 'Фактура', 'quote' => 'Оферта', 'credit' => 'Кредитно известие', 'proforma' => 'Проформа фактура'],
    'pdf' => [
        'page' => 'Страница', 'serial_number' => 'Номер на фактура', 'due_at' => 'Падеж', 'created_at' => 'Създадена на', 'paid_at' => 'Платена на', 'description' => 'Описание', 'from' => 'От', 'to' => 'До', 'shipping_to' => 'Доставка до',
        'items' => ['label' => 'Описание', 'quantity' => 'Количество', 'unit_price' => 'Единична цена', 'tax' => 'Данък', 'discount' => 'Отстъпка', 'amount' => 'Сума'],
        'summary' => ['tax' => 'Данък', 'subtotal' => 'Междинна сума', 'discount' => 'Отстъпка', 'discounted' => 'Междинна сума след отстъпка', 'total' => 'Общо'],
    ],
];
