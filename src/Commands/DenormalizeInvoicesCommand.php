<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Commands;

use Elegantly\Invoices\Collections\Eloquent\InvoiceCollection;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Prompts\Progress;

use function Laravel\Prompts\info;

class DenormalizeInvoicesCommand extends Command
{
    public $signature = 'invoices:denormalize {ids?*} {--force}';

    public $description = 'Denormalize amount, tax and discounts to the invoices and invoice_items tables';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $force = (bool) $this->option('force');

        $model = InvoiceServiceProvider::getInvoiceClass();

        /** @var Builder<Invoice> $query */
        $query = $model::query()
            ->when($ids, fn (Builder $q) => $q->whereIn('id', $ids));

        /** @var int */
        $total = $query->count();

        if ($total < 1) {
            info('No invoices found');

            return self::SUCCESS;
        }

        $progress = new Progress('Denormalizing invoices amounts', $total);

        $progress->start();

        $query
            ->with(['items'])
            ->chunkById(1_000, function (InvoiceCollection $invoices) use ($force, $progress) {

                foreach ($invoices as $invoice) {
                    $invoice
                        ->denormalize($force)
                        ->saveWithItems();

                    $progress->advance();
                }

            });

        $progress->finish();

        return self::SUCCESS;
    }
}
