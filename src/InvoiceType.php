<?php

namespace Finller\Invoice;

enum InvoiceType: string
{
    case Invoice = 'invoice';
    case Quote = 'quote';
    case Credit = 'credit';

    function trans()
    {
        return match ($this) {
            self::Invoice => __('invoices::invoice.types.invoice'),
            self::Quote => __('invoices::invoice.types.quote'),
            self::Credit => __('invoices::invoice.types.credit'),
        };
    }
}
