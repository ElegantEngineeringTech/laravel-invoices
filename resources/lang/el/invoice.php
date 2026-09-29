<?php

declare(strict_types=1);

return [
    'states' => ['draft' => 'Πρόχειρο', 'pending' => 'Σε αναμονή', 'paid' => 'Πληρωμένο', 'refunded' => 'Επιστραφέν'],
    'types' => ['invoice' => 'Τιμολόγιο', 'quote' => 'Προσφορά', 'credit' => 'Πιστωτικό τιμολόγιο', 'proforma' => 'Προτιμολόγιο'],
    'pdf' => [
        'page' => 'Σελίδα', 'serial_number' => 'Αριθμός', 'due_at' => 'Λήξη', 'created_at' => 'Δημιουργήθηκε στις', 'paid_at' => 'Πληρώθηκε στις', 'description' => 'Περιγραφή', 'from' => 'Από', 'to' => 'Για', 'shipping_to' => 'Παραδόθηκε σε',
        'items' => ['label' => 'Περιγραφή', 'quantity' => 'Ποσότητα', 'unit_price' => 'Τιμή μονάδας', 'tax' => 'Φόρος', 'discount' => 'Έκπτωση', 'amount' => 'Ποσό'],
        'summary' => ['tax' => 'Φόρος', 'subtotal' => 'Υποσύνολο', 'discount' => 'Έκπτωση', 'discounted' => 'Υποσύνολο μετά την έκπτωση', 'total' => 'Σύνολο'],
    ],
];
