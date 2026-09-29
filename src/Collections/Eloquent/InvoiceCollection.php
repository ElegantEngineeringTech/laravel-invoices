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

    public function denormalize(bool $force = false): static
    {
        return $this->each(function ($item) use ($force) {
            $item->denormalize($force);
        });
    }

    /**
     * @param  null|string[]  $except
     */
    public function replicate(?array $except = null): static
    {
        // @phpstan-ignore-next-line
        return $this->map(fn ($item) => $item->replicate($except));
    }

    /**
     * @return SupportCollection<int, PdfInvoice>
     */
    public function toPdfInvoices(): SupportCollection
    {
        return $this->toBase()->map(fn ($item) => $item->toPdfInvoice());
    }
}
