<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Contracts;

interface GOBLable
{
    public function toGOBL(): array;
}
