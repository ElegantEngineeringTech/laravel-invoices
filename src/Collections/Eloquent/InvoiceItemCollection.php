<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections\Eloquent;

use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, InvoiceItem>
 */
class InvoiceItemCollection extends Collection
{
    use SumMoney;

    public function denormalize(): static
    {
        return $this->each(function ($item) {
            $item->denormalize();
        });
    }

    public function toPdfItems(): PdfInvoiceItemCollection
    {
        return new PdfInvoiceItemCollection(array_map(
            fn ($item) => $item->toPdfInvoiceItem(),
            $this->all()
        ));
    }
}
