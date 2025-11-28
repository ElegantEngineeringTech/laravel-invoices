<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Models;

use Brick\Money\Money;
use Carbon\CarbonInterface;
use Elegantly\Invoices\Casts\Discounts;
use Elegantly\Invoices\Contracts\HasLabel;
use Elegantly\Invoices\Database\Factories\InvoiceFactory;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\SerialNumberGenerator;
use Elegantly\Invoices\Support\Buyer;
use Elegantly\Invoices\Support\PaymentInstruction;
use Elegantly\Invoices\Support\Seller;
use Elegantly\Money\MoneyCast;
use Exception;
use finfo;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property int $id
 * @property ?int $parent_id
 * @property ?Invoice $parent
 * @property ?Invoice $quote
 * @property ?Invoice $credit
 * @property string $type
 * @property string $state
 * @property ?CarbonInterface $state_set_at
 * @property string $description
 * @property ?array<string, mixed> $seller_information
 * @property ?array<string, mixed> $buyer_information
 * @property ?CarbonInterface $due_at
 * @property ?string $tax_type
 * @property ?string $tax_exempt
 * @property Collection<int, InvoiceItem> $items
 * @property ?Model $buyer
 * @property ?int $buyer_id
 * @property ?string $buyer_type
 * @property ?Model $seller
 * @property ?int $seller_id
 * @property ?string $seller_type
 * @property ?Model $invoiceable
 * @property ?int $invoiceable_id
 * @property ?string $invoiceable_type
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property InvoiceDiscount[] $discounts
 * @property ?array<array-key, mixed> $metadata
 * @property ?Money $subtotal_amount
 * @property ?Money $discount_amount
 * @property ?Money $tax_amount
 * @property ?Money $total_amount
 * @property ?string $currency
 * @property string $serial_number
 * @property string $serial_number_format
 * @property ?string $serial_number_prefix
 * @property ?int $serial_number_serie
 * @property ?int $serial_number_year
 * @property ?int $serial_number_month
 * @property int $serial_number_count
 * @property ?string $logo Binary format
 * @property ?SupportCollection<int, PaymentInstruction> $payment_instructions
 */
class Invoice extends Model implements Attachable
{
    /**
     * @use HasFactory<InvoiceFactory>
     */
    use HasFactory;

    protected $attributes = [
        'type' => InvoiceType::Invoice->value,
        'state' => InvoiceState::Draft->value,
    ];

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state_set_at' => 'datetime',
            'due_at' => 'datetime',
            'seller_information' => 'array',
            'buyer_information' => 'array',
            'metadata' => 'array',
            'discounts' => Discounts::class,
            'subtotal_amount' => MoneyCast::class.':currency',
            'discount_amount' => MoneyCast::class.':currency',
            'tax_amount' => MoneyCast::class.':currency',
            'total_amount' => MoneyCast::class.':currency',
            'payment_instructions' => AsCollection::of(PaymentInstruction::class),
        ];
    }

    public static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (
                config('invoices.serial_number.auto_generate') &&
                blank($invoice->serial_number)
            ) {
                $invoice->generateSerialNumber();
            } else {
                $invoice->denormalizeSerialNumber();
            }
        });

        static::updating(function (Invoice $invoice) {
            $invoice->denormalize();
        });

        static::deleting(function (Invoice $invoice) {
            if (config('invoices.cascade_invoice_delete_to_invoice_items')) {
                $invoice->items()->delete();
            }
        });
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        /** @var class-string<InvoiceItem> */
        $model = config()->string('invoices.model_invoice_item');

        return $this->hasMany($model);
    }

    /**
     * Any model that is the "parent" of the invoice like a Mission, a Transaction, ...
     *
     * @return MorphTo<Model, $this>
     **/
    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Typically, the buyer is one of your users, teams or any other model.
     * When editing your invoice, you should not rely on the information of this relation as they can change in time and impact all buyer's invoices.
     * Instead you should store the buyer information in his property on the invoice creation/validation.
     *
     * @return MorphTo<Model, $this>
     */
    public function buyer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * In case, your application is a marketplace, you would also attach the invoice to the seller
     * When editing your invoice, you should not rely on the information of this relation as they can change in time and impact all seller's invoices.
     * Instead you should store the seller information in his property on the invoice creation/validation.
     *
     * @return MorphTo<Model, $this>
     */
    public function seller(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Invoice can be attached with another one
     * A Quote or a Credit can have another Invoice as parent.
     * Ex: $invoice = $quote->parent and $quote = $invoice->quote
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function quote(): HasOne
    {
        return $this->hasOne(Invoice::class, 'parent_id')->where('type', InvoiceType::Quote);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function credit(): HasOne
    {
        return $this->hasOne(Invoice::class, 'parent_id')->where('type', InvoiceType::Credit);
    }

    /**
     * Generates a new serial number for an invoice.
     *
     * The count value for the new serial number is based on the previous serial number.
     * This function can be customized to determine what constitutes the previous invoice.
     */
    public function getPreviousInvoice(): ?static
    {
        /** @var ?static $invoice */
        $invoice = static::query()
            ->withoutGlobalScopes()
            ->where('serial_number_prefix', $this->serial_number_prefix)
            ->where('serial_number_serie', $this->serial_number_serie)
            ->where('serial_number_year', $this->serial_number_year)
            ->where('serial_number_month', $this->serial_number_month)
            ->latest('serial_number_count')
            ->first();

        return $invoice;
    }

    public function setSerialNumberPrefix(
        ?string $value = null,
        bool $throw = true,
    ): static {

        if ($value === null) {
            $this->serial_number_prefix = null;
        } elseif ($length = mb_substr_count($this->serial_number_format, 'P')) {
            $this->serial_number_prefix = mb_substr($value, -$length);
        } elseif ($throw) {
            throw new Exception('The Serial Number Format does not contain a prefix.');
        }

        return $this;
    }

    public function setSerialNumberSerie(
        null|int|string $value = null,
        bool $throw = true,
    ): static {

        if ($value === null) {
            $this->serial_number_serie = null;
        } elseif ($length = mb_substr_count($this->serial_number_format, 'S')) {
            $this->serial_number_serie = (int) mb_substr((string) $value, -$length);
        } elseif ($throw) {
            throw new Exception('The Serial Number Format does not contain a serie.');
        }

        return $this;
    }

    public function setSerialNumberYear(
        null|int|string $value = null,
        bool $throw = true,
    ): static {

        if ($value === null) {
            $this->serial_number_year = null;
        } elseif ($length = mb_substr_count($this->serial_number_format, 'Y')) {
            $this->serial_number_year = (int) mb_substr((string) $value, -$length);
        } elseif ($throw) {
            throw new Exception('The Serial Number Format does not contain a year.');
        }

        return $this;
    }

    public function setSerialNumberMonth(
        null|int|string $value = null,
        bool $throw = true,
    ): static {

        if ($value === null) {
            $this->serial_number_month = null;
        } elseif ($length = mb_substr_count($this->serial_number_format, 'M')) {
            $this->serial_number_month = (int) mb_substr((string) $value, -$length);
        } elseif ($throw) {
            throw new Exception('The Serial Number Format does not contain a month.');
        }

        return $this;
    }

    public function configureSerialNumber(
        ?string $format = null,
        ?string $prefix = null,
        string|int|null $serie = null,
        string|int|null $year = null,
        string|int|null $month = null,
        bool $throw = false,
    ): static {
        $this->serial_number_format = $format ?? $this->serial_number_format ?? InvoiceServiceProvider::getSerialNumberFormatConfiguration($this->type);

        return $this
            ->setSerialNumberPrefix($prefix, $throw)
            ->setSerialNumberSerie($serie, $throw)
            ->setSerialNumberYear($year, $throw)
            ->setSerialNumberMonth($month, $throw);
    }

    public function generateSerialNumber(): static
    {
        $this->configureSerialNumber(
            format: $this->serial_number_format,
            prefix: $this->serial_number_prefix ?? InvoiceServiceProvider::getSerialNumberPrefixConfiguration($this->type),
            serie: $this->serial_number_serie,
            year: $this->serial_number_year ?? now()->format('Y'),
            month: $this->serial_number_month ?? now()->format('m'),
        );

        $generator = new SerialNumberGenerator($this->serial_number_format);

        $previousCount = (int) $this->getPreviousInvoice()?->serial_number_count;

        $this->serial_number = $generator->generate(
            prefix: $this->serial_number_prefix,
            serie: $this->serial_number_serie,
            year: $this->serial_number_year,
            month: $this->serial_number_month,
            count: $previousCount + 1
        );

        $this->denormalizeSerialNumber();

        return $this;
    }

    /**
     * @return array{ 'prefix': ?string, 'serie': ?int, 'month': ?int, 'year': ?int, 'count': ?int}
     */
    public function parseSerialNumber(): array
    {
        $format = $this->serial_number_format ?? InvoiceServiceProvider::getSerialNumberFormatConfiguration($this->type);

        $generator = new SerialNumberGenerator($format);

        return $generator->parse($this->serial_number);
    }

    public function denormalizeSerialNumber(): static
    {
        $this->serial_number_format ??= InvoiceServiceProvider::getSerialNumberFormatConfiguration($this->type);

        $values = $this->parseSerialNumber();

        $this->serial_number_prefix = $values['prefix'];
        $this->serial_number_serie = $values['serie'];
        $this->serial_number_year = $values['year'];
        $this->serial_number_month = $values['month'];
        $this->serial_number_count = (int) $values['count'];

        return $this;
    }

    public function getTaxLabel(): ?string
    {
        return null;
    }

    /**
     * @return InvoiceDiscount[]
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    /**
     * Denormalize amounts computed from items to the invoice table
     * Allowing easier query
     */
    public function denormalize(): static
    {
        $pdfInvoice = $this->toPdfInvoice();
        $this->currency = $pdfInvoice->getCurrency();
        $this->subtotal_amount = $pdfInvoice->subTotalAmount();
        $this->discount_amount = $pdfInvoice->totalDiscountAmount();
        $this->tax_amount = $pdfInvoice->totalTaxAmount();
        $this->total_amount = $pdfInvoice->totalAmount();

        return $this;
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeInvoice(Builder $query): Builder
    {
        return $query->where('type', InvoiceType::Invoice);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('type', InvoiceType::Credit);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeQuote(Builder $query): Builder
    {
        return $query->where('type', InvoiceType::Quote);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::Paid);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::Refunded);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::Draft);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::Pending);
    }

    /**
     * Get the attachable representation of the model.
     */
    public function toMailAttachment(): Attachment
    {
        return $this->toPdfInvoice()->toMailAttachment();
    }

    /**
     * Store the default logo in database
     */
    public function setLogoFromConfig(): static
    {
        /** @var ?string */
        $path = config('invoices.pdf.logo');

        if ($path) {
            return $this->setLogoFromPath($path);
        }

        return $this;
    }

    public function setLogoFromFile(File|UploadedFile $file): static
    {
        $this->logo = $file->getContent();

        return $this;
    }

    public function setLogoFromPath(string $path): static
    {
        if ($file = file_get_contents($path)) {
            $this->logo = $file;
        }

        return $this;
    }

    /**
     * @return string|null A base64 encoded data url or a path to a local file
     */
    public function getLogo(): ?string
    {
        if ($this->logo) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($this->logo);

            return "data:{$mimeType};base64,".base64_encode($this->logo);
        }

        return null;
    }

    public function getType(): string|HasLabel
    {
        return InvoiceType::tryFrom($this->type) ?? $this->type;
    }

    public function getState(): string|HasLabel
    {
        return InvoiceState::tryFrom($this->state) ?? $this->state;
    }

    public function toPdfInvoice(): PdfInvoice
    {
        return new PdfInvoice(
            type: $this->getType(),
            state: $this->getState(),
            serial_number: $this->serial_number,
            due_at: $this->due_at,
            created_at: $this->created_at,
            buyer: Buyer::fromArray($this->buyer_information ?? []),
            seller: Seller::fromArray($this->seller_information ?? []),
            description: $this->description,
            items: $this->items->values()->map(fn ($item) => $item->toPdfInvoiceItem())->all(),
            tax_label: $this->getTaxLabel(),
            discounts: $this->getDiscounts(),
            logo: $this->getLogo(),
            paymentInstructions: $this->payment_instructions?->all() ?? [],
        );
    }
}
