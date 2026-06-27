<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Contracts;

interface GOBLable
{
    /**
     * Convert the object to its GOBL representation.
     *
     * @return array<string, mixed>
     */
    public function toGOBL(): array;
}
