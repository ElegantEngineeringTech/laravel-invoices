<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Contracts;

interface HasLabel
{
    public function getLabel(): ?string;
}
