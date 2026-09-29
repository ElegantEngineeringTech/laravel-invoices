<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections\Eloquent;

use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @extends Collection<int, Invoice>
 */
class InvoiceCollection extends Collection
{
    use SumMoney;

    /**
     * @return SupportCollection<int, PdfInvoice>
     */
    public function toPdfInvoices(): SupportCollection
    {
        return $this->toBase()->map(fn ($item) => $item->toPdfInvoice());
    }
}
