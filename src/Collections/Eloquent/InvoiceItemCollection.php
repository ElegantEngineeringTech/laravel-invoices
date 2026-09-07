<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections\Eloquent;

use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, InvoiceItem>
 */
class InvoiceItemCollection extends Collection implements GOBLable
{
    use SumMoney;

    public function denormalize(bool $force = false): static
    {
        return $this->each(function ($item) use ($force) {
            $item->denormalize($force);
        });
    }

    public function toPdfItems(): PdfInvoiceItemCollection
    {
        return new PdfInvoiceItemCollection(array_map(
            fn ($item) => $item->toPdfInvoiceItem(),
            $this->all()
        ));
    }

    public function toGOBL(array $values = []): array
    {
        return $this->map(fn ($item) => $item->toGOBL($values))->values()->all();
    }
}
