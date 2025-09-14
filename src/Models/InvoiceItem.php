<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Models;

use Brick\Money\Money;
use Carbon\CarbonInterface;
use Elegantly\Invoices\Database\Factories\InvoiceItemFactory;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Money\MoneyCast;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?Money $unit_price
 * @property ?Money $unit_tax
 * @property ?float $tax_percentage between 0 and 100
 * @property ?string $currency
 * @property ?int $quantity
 * @property ?string $quantity_unit
 * @property ?string $label
 * @property ?string $description
 * @property ?ArrayObject<array-key, mixed> $metadata
 * @property int $invoice_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class InvoiceItem extends Model
{
    /**
     * @use HasFactory<InvoiceItemFactory>
     */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class.':currency',
            'unit_tax' => MoneyCast::class.':currency',
            'metadata' => AsArrayObject::class,
            'tax_percentage' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        /** @var class-string<Invoice> */
        $model = config()->string('invoices.model_invoice');

        return $this->belongsTo($model);
    }

    public function toPdfInvoiceItem(): PdfInvoiceItem
    {
        return new PdfInvoiceItem(
            label: $this->label,
            quantity: $this->quantity ?? 1,
            quantity_unit: $this->quantity_unit,
            description: $this->description,
            unit_price: $this->unit_price,
            unit_tax: $this->unit_tax,
            tax_percentage: $this->tax_percentage,
            currency: $this->currency,
        );
    }
}
