<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Collections;

use Elegantly\Invoices\Concerns\SumMoney;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, PdfInvoiceItem>
 */
class PdfInvoiceItemCollection extends Collection
{
    use SumMoney;

    public function denormalize(): static
    {
        return $this->each(function ($item) {
            $item->denormalize();
        });
    }
}
