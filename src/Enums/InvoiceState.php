<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Enums;

use Elegantly\Invoices\Contracts\HasLabel;

enum InvoiceState: string implements HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('invoices::invoice.states.draft'),
            self::Pending => __('invoices::invoice.states.pending'),
            self::Paid => __('invoices::invoice.states.paid'),
            self::Refunded => __('invoices::invoice.states.refunded'),
        };
    }

    public function trans(): string
    {
        return $this->getLabel();
    }
}
