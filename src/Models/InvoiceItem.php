<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Models;

use Brick\Money\Money;
use Carbon\CarbonInterface;
use Elegantly\Invoices\Collections\Eloquent\InvoiceItemCollection;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\Database\Factories\InvoiceItemFactory;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property ?string $label
 * @property ?string $description
 * @property ?string $currency
 * @property ?Money $unit_price
 * @property ?Money $price_subtotal
 * @property ?Money $price_discount
 * @property ?Money $price_tax
 * @property ?Money $price
 * @property int $quantity
 * @property ?string $quantity_unit
 * @property ?InvoiceTaxCollection $taxes
 * @property ?InvoiceDiscountCollection $discounts
 * @property ?array<array-key, mixed> $metadata
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[CollectedBy(InvoiceItemCollection::class)]
class InvoiceItem extends Model implements GOBLable
{
    /**
     * @use HasFactory<InvoiceItemFactory>
     */
    use HasFactory;

    protected $attributes = [
        'quantity' => 1,
        'taxes' => '[]',
        'discounts' => '[]',
    ];

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::of('currency'),
            'price_subtotal' => MoneyCast::of('currency'),
            'price_discount' => MoneyCast::of('currency'),
            'price_tax' => MoneyCast::of('currency'),
            'price' => MoneyCast::of('currency'),
            'taxes' => AsCollection::using(InvoiceTaxCollection::class, InvoiceServiceProvider::getInvoiceTaxClass()),
            'discounts' => AsCollection::using(InvoiceDiscountCollection::class, InvoiceServiceProvider::getInvoiceDiscountClass()),
            'metadata' => 'array',
        ];
    }

    public static function booted()
    {
        static::creating(function (InvoiceItem $item) {
            //
        });

        static::updating(function (InvoiceItem $item) {
            //
        });

    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceServiceProvider::getInvoiceClass());
    }

    public function denormalize(bool $force = false): static
    {
        $roundingMode = InvoiceServiceProvider::getRoundingMode();

        // unit_price is the source of truth
        if ($this->unit_price !== null) {
            if ($force || $this->price_subtotal === null) {
                $this->price_subtotal = $this->unit_price->multipliedBy((string) $this->quantity, $roundingMode);
            }
        } elseif ($this->price_subtotal !== null) {
            $this->unit_price = $this->price_subtotal->dividedBy((string) $this->quantity, $roundingMode);
        }

        if ($force || $this->price_discount === null) {
            $this->price_discount = $this->discounts
                ?->denormalize($this, $force)
                ->amount();

            if ($this->unit_price !== null) {
                $currency = $this->unit_price->getCurrency();

                $this->price_discount ??= Money::zero($currency);
            }
        }

        if ($force || $this->price_tax === null) {
            $this->price_tax = $this->taxes
                ?->denormalize($this, $force)
                ->amount();

            if ($this->unit_price !== null) {
                $currency = $this->unit_price->getCurrency();

                $this->price_tax ??= Money::zero($currency);
            }
        }

        if ($force || $this->price === null) {

            $this->price = $this->price_subtotal;

            if ($this->price_discount) {
                $this->price = $this->price?->minus($this->price_discount);
            }

            if ($this->price_tax) {
                $this->price = $this->price?->plus($this->price_tax);
            }

        }

        return $this;
    }

    public function addTaxes(InvoiceTaxCollection|InvoiceTax $taxes): static
    {
        $taxes = $taxes instanceof InvoiceTax ? new InvoiceTaxCollection([$taxes]) : $taxes;

        if ($this->taxes === null) {
            $this->taxes = $taxes;
        } else {
            $this->taxes->push(...$taxes);
        }

        return $this;
    }

    public function toPdfInvoiceItem(): PdfInvoiceItem
    {
        return new PdfInvoiceItem(
            label: $this->label,
            unit_price: $this->unit_price,
            price_subtotal: $this->price_subtotal,
            price_discount: $this->price_discount,
            price_tax: $this->price_tax,
            price: $this->price,
            quantity: $this->quantity,
            quantity_unit: $this->quantity_unit,
            description: $this->description,
            discounts: $this->discounts?->clone() ?? new InvoiceDiscountCollection,
            taxes: $this->taxes?->clone() ?? new InvoiceTaxCollection,
        );
    }

    /**
     * Convert the identity to its GOBL representation.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function toGOBL(array $values = []): array
    {
        return array_filter([
            'quantity' => (string) $this->quantity,
            'discounts' => $this->discounts?->toGOBL(),
            'taxes' => $this->taxes?->toGOBL(),
            ...$values,
            'item' => array_filter([
                'name' => $this->label,
                'description' => $this->description,
                'price' => $this->unit_price?->getAmount()->toString(),
                'currency' => $this->unit_price?->getCurrency()->getCurrencyCode(),
                'unit' => $this->quantity_unit,
                // @phpstan-ignore-next-line
                ...($values['item'] ?? []),
            ], fn ($value) => filled($value)),
        ], fn ($value) => filled($value));
    }
}
