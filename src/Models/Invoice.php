<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Models;

use BackedEnum;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Elegantly\Invoices\Collections\Eloquent\InvoiceItemCollection;
use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\Contracts\HasLabel;
use Elegantly\Invoices\Database\Factories\InvoiceFactory;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\SerialNumberGenerator;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;
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
use LogicException;

use function Illuminate\Support\enum_value;

/**
 * @property int $id
 * @property ?int $parent_id
 * @property string $type
 * @property string $state
 * @property ?CarbonInterface $state_set_at
 * @property ?array<array-key, mixed> $fields
 * @property string $description
 * @property ?Party $seller_information
 * @property ?Party $buyer_information
 * @property ?int $buyer_id
 * @property ?string $buyer_type
 * @property ?int $seller_id
 * @property ?string $seller_type
 * @property ?int $invoiceable_id
 * @property ?string $invoiceable_type
 * @property ?CarbonInterface $due_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
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
 * @property-read ?Model $invoiceable
 * @property-read ?Model $buyer
 * @property-read ?Model $seller
 * @property-read ?static $parent
 * @property-read ?static $quote
 * @property-read InvoiceItemCollection $items
 * @property-read Collection<int, static> $credits
 */
class Invoice extends Model implements Attachable, GOBLable
{
    /**
     * @use HasFactory<InvoiceFactory>
     */
    use HasFactory;

    protected $attributes = [
        'type' => InvoiceType::Invoice->value,
        'state' => InvoiceState::Draft->value,
    ];

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state_set_at' => 'datetime',
            'due_at' => 'datetime',
            'fields' => 'array',
            'metadata' => 'array',
            'seller_information' => InvoiceServiceProvider::getSellerClass(),
            'buyer_information' => InvoiceServiceProvider::getBuyerClass(),
            'payment_instructions' => AsCollection::of(PaymentInstruction::class),
            'subtotal_amount' => MoneyCast::of('currency'),
            'discount_amount' => MoneyCast::of('currency'),
            'tax_amount' => MoneyCast::of('currency'),
            'total_amount' => MoneyCast::of('currency'),
        ];
    }

    public static function booted()
    {
        static::creating(function (Invoice $invoice) {
            $invoice->denormalize();

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

            if (
                config('invoices.serial_number.auto_generate') &&
                blank($invoice->serial_number)
            ) {
                $invoice->generateSerialNumber();
            } else {
                $invoice->denormalizeSerialNumber();
            }

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
        return $this->hasMany(InvoiceServiceProvider::getInvoiceItemClass());
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
        return $this->belongsTo(InvoiceServiceProvider::getInvoiceClass());
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function quote(): HasOne
    {
        return $this
            ->hasOne(InvoiceServiceProvider::getInvoiceClass(), 'parent_id')
            ->where('type', InvoiceType::Quote);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(InvoiceServiceProvider::getInvoiceClass(), 'parent_id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function credits(): HasMany
    {
        return $this->children()->where('type', InvoiceType::Credit);
    }

    /**
     * @param  array<int, InvoiceItem>|InvoiceItemCollection  $items
     */
    public function setItems(array|InvoiceItemCollection $items = []): static
    {
        return $this->setRelation('items', new InvoiceItemCollection($items));
    }

    /**
     * @param  array<int, InvoiceItem>|InvoiceItemCollection  $items
     */
    public function addItems(array|InvoiceItemCollection $items = []): static
    {
        return $this->setRelation(
            'items',
            $this->items->push(...$items)
        );
    }

    /**
     * @return iterable<InvoiceItem>
     */
    public function saveItems(): iterable
    {
        return $this->items()->saveMany($this->items);
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

    /**
     * Manually set the serial number
     */
    public function setSerialNumber(
        string $value,
        string|BackedEnum $format,
    ): static {
        $format = $format instanceof BackedEnum ? ((string) $format->value) : $format;

        $this->serial_number = $value;
        $this->serial_number_format = $format;

        return $this->denormalizeSerialNumber();
    }

    public function setSerialNumberPrefix(
        null|string|BackedEnum $value = null,
        bool $throw = true,
    ): static {

        $value = $value instanceof BackedEnum ? ((string) $value->value) : $value;

        if ($value === null) {
            $this->serial_number_prefix = null;
        } elseif ($length = mb_substr_count($this->serial_number_format, 'P')) {
            $this->serial_number_prefix = mb_substr($value, -$length);
        } elseif ($throw) {
            throw new LogicException('The Serial Number Format does not contain a prefix.');
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
        null|string|BackedEnum $format = null,
        null|string|BackedEnum $prefix = null,
        string|int|null $serie = null,
        string|int|null $year = null,
        string|int|null $month = null,
        bool $throw = false,
    ): static {
        $format = $format instanceof BackedEnum ? ((string) $format->value) : $format;

        $this->serial_number_format = $format ?? InvoiceServiceProvider::getSerialNumberFormatConfiguration($this->type);

        return $this
            ->setSerialNumberPrefix($prefix, $throw)
            ->setSerialNumberSerie($serie, $throw)
            ->setSerialNumberYear($year, $throw)
            ->setSerialNumberMonth($month, $throw);
    }

    public function generateSerialNumber(): static
    {
        $this->configureSerialNumber(
            format: $this->serial_number_format ?: InvoiceServiceProvider::getSerialNumberFormatConfiguration($this->type),
            prefix: $this->serial_number_prefix ?: InvoiceServiceProvider::getSerialNumberPrefixConfiguration($this->type),
            serie: $this->serial_number_serie,
            year: $this->serial_number_year ?? now()->format('Y'),
            month: $this->serial_number_month ?? now()->format('m'),
        );

        $previousCount = (int) $this->getPreviousInvoice()?->serial_number_count;

        $generator = new SerialNumberGenerator($this->serial_number_format);

        $this->serial_number = $generator->generate(
            prefix: $this->serial_number_prefix,
            serie: $this->serial_number_serie,
            year: $this->serial_number_year,
            month: $this->serial_number_month,
            count: $previousCount + 1
        );

        return $this->denormalizeSerialNumber();
    }

    public function denormalizeSerialNumber(): static
    {
        if (! $this->serial_number_format || ! $this->serial_number) {
            return $this;
        }

        $generator = new SerialNumberGenerator($this->serial_number_format);

        $values = $generator->parse($this->serial_number);

        $this->serial_number_prefix = $values['prefix'];
        $this->serial_number_serie = $values['serie'];
        $this->serial_number_year = $values['year'];
        $this->serial_number_month = $values['month'];
        $this->serial_number_count = (int) $values['count'];

        return $this;
    }

    public function denormalize(bool $force = false): static
    {
        $this->items->denormalize($force);

        if ($this->subtotal_amount === null || $force) {
            $this->subtotal_amount = $this->items->sumMoney('price_subtotal');
        }

        if ($this->discount_amount === null || $force) {
            $this->discount_amount = $this->items->sumMoney('price_discount');
        }

        if ($this->tax_amount === null || $force) {
            $this->tax_amount = $this->items->sumMoney('price_tax');
        }

        if ($this->total_amount === null || $force) {
            $this->total_amount = $this->items->sumMoney('price');
        }

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
     * Get the attachable representation of the model.
     */
    public function toMailAttachment(): Attachment
    {
        return $this->toPdfInvoice()->toMailAttachment();
    }

    public function setLogoFromFile(File|UploadedFile $file): static
    {
        $this->logo = $file->getContent();

        return $this;
    }

    public function setLogoFromPath(string $path): static
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Logo file does not exist: {$path}");
        }

        if (! is_readable($path)) {
            throw new \InvalidArgumentException("Logo file is not readable: {$path}");
        }

        $file = file_get_contents($path);

        if ($file === false) {
            throw new \RuntimeException("Failed to read logo file: {$path}");
        }

        $this->logo = $file;

        return $this;
    }

    /**
     * Store the default logo in database
     */
    public function setLogoFromConfig(): static
    {
        /** @var ?string $path */
        $path = config('invoices.pdf.logo');

        if ($path) {
            return $this->setLogoFromPath($path);
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

    /**
     * @param  null|string[]  $except
     */
    public function replicate(?array $except = null): static
    {
        return parent::replicate(array_merge([
            'serial_number',
            'serial_number_format',
            'serial_number_prefix',
            'serial_number_serie',
            'serial_number_year',
            'serial_number_month',
            'serial_number_count',
            'serial_number_details',
        ], $except ?? []));
    }

    public function saveWithItems(): static
    {
        $this->save();
        $this->items()->saveMany($this->items);

        return $this;
    }

    /**
     * Mutate the invoice amounts and its items by scaling them.
     * Uses the configured rounding mode when none is provided.
     */
    public function multiplyBy(BigNumber|int|string $that, ?RoundingMode $roundingMode = null): static
    {
        $roundingMode ??= InvoiceServiceProvider::getRoundingMode();

        $this->subtotal_amount = $this->subtotal_amount?->multipliedBy($that, $roundingMode);
        $this->discount_amount = $this->discount_amount?->multipliedBy($that, $roundingMode);
        $this->tax_amount = $this->tax_amount?->multipliedBy($that, $roundingMode);
        $this->total_amount = $this->total_amount?->multipliedBy($that, $roundingMode);

        $this->items->multiplyBy($that, $roundingMode);

        return $this;
    }

    public function toPdfInvoice(): PdfInvoice
    {
        return new PdfInvoice(
            type: $this->getType(),
            state: $this->getState(),
            serial_number: $this->serial_number,
            due_at: $this->due_at,
            created_at: $this->created_at,
            fields : $this->fields ?? [],
            buyer: $this->buyer_information ?? new Party,
            seller: $this->seller_information ?? new Party,
            description: $this->description,
            items: $this->items->toPdfItems()->values(),
            logo: $this->getLogo(),
            paymentInstructions: $this->payment_instructions?->all() ?? [],
        );
    }

    /**
     * @see https://docs.gobl.org/draft-0/bill/invoice#invoice
     */
    public function toGOBL(array $values = []): array
    {
        return array_filter([
            '$schema' => 'https://gobl.org/draft-0/bill/invoice',
            'type' => match (enum_value($this->type)) {
                InvoiceType::Invoice->value => 'standard',
                InvoiceType::Quote->value, InvoiceType::Proforma->value => 'proforma',
                InvoiceType::Credit->value => 'credit-note',
                default => 'other',
            },
            'code' => $this->serial_number,
            'issue_date' => $this->created_at->toDateString(),
            'currency' => $this->currency,
            'preceding' => $this->parent ? [
                array_filter([
                    'code' => $this->parent->serial_number,
                    'issue_date' => $this->parent->created_at->toDateString(),
                ], fn ($value) => filled($value)),
            ] : null,
            'supplier' => $this->seller_information?->toGOBL(),
            'customer' => $this->buyer_information?->toGOBL(),
            'lines' => $this->items->toGOBL(),
            'payment' => $this->due_at ? [
                'terms' => [
                    'key' => 'due-date',
                    'due_dates' => [
                        ['date' => $this->due_at->toDateString()],
                    ],
                ],
            ] : null,
            'notes' => $this->description ? [
                [
                    'key' => 'general',
                    'text' => $this->description,
                ],
            ] : null,
            ...$values,
        ], fn ($value) => filled($value));
    }
}
