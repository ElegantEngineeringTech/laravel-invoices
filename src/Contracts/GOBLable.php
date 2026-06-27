<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Contracts;

interface GOBLable
{
    /**
     * Convert the identity to its GOBL representation.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function toGOBL(array $values = []): array;
}
