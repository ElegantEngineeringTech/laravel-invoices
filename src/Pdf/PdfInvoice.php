<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Money\Money;
use Carbon\CarbonInterface;
use Dompdf\Dompdf;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Contracts\HasLabel;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;
use Elegantly\Money\MoneyParser;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * @phpstan-consistent-constructor
 *
 * @phpstan-import-type ItemData from PdfInvoiceItem
 */
class PdfInvoice implements Attachable
{
    public string $template;

    /**
     * @param  array<string, mixed>  $fields  Additional fields displayed in the header
     * @param  PaymentInstruction[]  $paymentInstructions
     * @param  ?string  $logo  A local file path. The file must be accessible using file_get_contents.
     * @param  array<string, mixed>  $templateData
     */
    public function __construct(
        public HasLabel|string $type = InvoiceType::Invoice,
        public HasLabel|string $state = InvoiceState::Draft,
        public ?string $serial_number = null,
        public ?CarbonInterface $created_at = null,
        public ?CarbonInterface $due_at = null,
        public ?CarbonInterface $paid_at = null,
        public array $fields = [],

        public Party $seller = new Party,
        public Party $buyer = new Party,
        public PdfInvoiceItemCollection $items = new PdfInvoiceItemCollection,

        public ?Money $subtotal_amount = null,
        public ?Money $discount_amount = null,
        public ?Money $tax_amount = null,
        public ?Money $total_amount = null,

        public ?string $description = null,
        public array $paymentInstructions = [],

        ?string $template = null,
        public array $templateData = [],

        public ?string $logo = null,
    ) {
        // @phpstan-ignore-next-line
        $this->logo = $logo ?? config('invoices.pdf.logo') ?? config('invoices.default_logo');
        // @phpstan-ignore-next-line
        $this->template = sprintf('invoices::%s', $template ?? config('invoices.pdf.template') ?? config('invoices.default_template'));
        // @phpstan-ignore-next-line
        $this->templateData = $templateData ?: config('invoices.pdf.template_data') ?: [];
    }

    /**
     * @param  array{
     *     type?: HasLabel|string,
     *     state?: HasLabel|string,
     *     serial_number?: ?string,
     *     created_at?: ?CarbonInterface,
     *     due_at?: ?CarbonInterface,
     *     paid_at?: ?CarbonInterface,
     *     fields?: array<string, mixed>,
     *     seller?: Party|array<string, mixed>,
     *     buyer?: Party|array<string, mixed>,
     *     items?: PdfInvoiceItemCollection|array<PdfInvoiceItem|ItemData>,
     *     currency?: ?string,
     *     subtotal_amount?: Money|float|null,
     *     discount_amount?: Money|float|null,
     *     tax_amount?: Money|float|null,
     *     total_amount?: Money|float|null,
     *     description?: ?string,
     *     paymentInstructions?: array<PaymentInstruction|array{name?: ?string, description?: ?string, qrcode?: ?string, fields?: array<array-key, null|int|float|string>}>,
     *     template?: ?string, templateData?: array<string, mixed>, logo?: ?string
     * }  $data
     */
    public static function make(array $data): static
    {
        $currency = $data['currency'] ?? InvoiceServiceProvider::getDefaultCurrency();

        $items = $data['items'] ?? new PdfInvoiceItemCollection;

        if (is_array($items)) {
            $items = new PdfInvoiceItemCollection(array_map(
                fn ($item) => $item instanceof PdfInvoiceItem ? $item : PdfInvoiceItem::make([
                    'currency' => $currency,
                    ...$item,
                ]),
                $items
            ));
        }

        $seller = $data['seller'] ?? $data['seller_information'] ?? new Party;
        $buyer = $data['buyer'] ?? $data['buyer_information'] ?? new Party;

        return new static(
            type: InvoiceType::tryFrom($data['type'] ?? '') ?? InvoiceType::Invoice,
            state: InvoiceState::tryFrom($data['state'] ?? '') ?? InvoiceState::Draft,
            serial_number: $data['serial_number'] ?? null,
            created_at: $data['created_at'] ?? null,
            due_at: $data['due_at'] ?? null,
            paid_at: $data['paid_at'] ?? null,
            fields: $data['fields'] ?? [],
            seller: is_array($seller) ? Party::fromArray($seller) : $seller,
            buyer: is_array($buyer) ? Party::fromArray($buyer) : $buyer,
            items: $items,
            subtotal_amount: MoneyParser::parse($data['subtotal_amount'] ?? null, $currency),
            discount_amount: MoneyParser::parse($data['discount_amount'] ?? null, $currency),
            tax_amount: MoneyParser::parse($data['tax_amount'] ?? null, $currency),
            total_amount: MoneyParser::parse($data['total_amount'] ?? null, $currency),
            description: $data['description'] ?? null,
            paymentInstructions: array_map(
                fn ($instruction) => $instruction instanceof PaymentInstruction ? $instruction : new PaymentInstruction(
                    name: $instruction['name'] ?? null,
                    description: $instruction['description'] ?? null,
                    qrcode: $instruction['qrcode'] ?? null,
                    fields: $instruction['fields'] ?? [],
                ),
                $data['paymentInstructions'] ?? []
            ),
            template: $data['template'] ?? null,
            templateData: $data['templateData'] ?? [],
            logo: $data['logo'] ?? null,
        );
    }

    public function denormalize(bool $force = false): static
    {
        $this->items->denormalize($force);

        if ($force || $this->subtotal_amount === null) {
            $this->subtotal_amount = $this->items->sumMoney('price_subtotal');
        }

        if ($force || $this->discount_amount === null) {
            $this->discount_amount = $this->items->sumMoney('price_discount');
        }

        if ($force || $this->tax_amount === null) {
            $this->tax_amount = $this->items->sumMoney('price_tax');
        }

        if ($force || $this->total_amount === null) {
            $this->total_amount = $this->items->sumMoney('price');
        }

        return $this;
    }

    /**
     * @return InvoiceDiscountCollection<InvoiceDiscount>
     */
    public function getDiscounts(): InvoiceDiscountCollection
    {
        $discounts = $this->items
            ->toBase()
            ->flatMap(fn ($item) => $item->discounts);

        return new InvoiceDiscountCollection($discounts)->group();
    }

    /**
     * @return InvoiceTaxCollection<InvoiceTax>
     */
    public function getTaxes(): InvoiceTaxCollection
    {
        $taxes = $this->items
            ->toBase()
            ->flatMap(fn ($item) => $item->taxes);

        return new InvoiceTaxCollection($taxes)->group();
    }

    public function getTypeLabel(): ?string
    {
        return $this->type instanceof HasLabel ? $this->type->getLabel() : $this->type;
    }

    public function getStateLabel(): ?string
    {
        return $this->state instanceof HasLabel ? $this->state->getLabel() : $this->state;
    }

    public function getFilename(): string
    {
        return str($this->serial_number)
            ->replace(['/', '\\'], '_')
            ->append('.pdf')
            ->value();
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array{ size?: string, orientation?: string }  $paper
     * @param  array<string, mixed>  $data
     */
    public function pdf(array $options = [], array $paper = [], array $data = []): Dompdf
    {

        $pdf = new Dompdf(array_merge(
            // @phpstan-ignore-next-line
            config('invoices.pdf.options') ?? [],
            $options,
        ));

        $pdf->setPaper(
            // @phpstan-ignore-next-line
            $paper['size'] ?? config('invoices.pdf.paper.size') ?? 'a4',
            // @phpstan-ignore-next-line
            $paper['orientation'] ?? config('invoices.pdf.paper.orientation') ?? 'portrait'
        );

        $html = $this->view($data)->render();

        $pdf->loadHtml($html);

        return $pdf;
    }

    public function getPdfOutput(): ?string
    {
        $pdf = $this->pdf();

        $pdf->render();

        return $pdf->output();
    }

    public function stream(?string $filename = null): Response
    {
        $filename ??= $this->getFilename();

        $output = $this->getPdfOutput();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $filename, Str::ascii($filename)),
        ]);
    }

    public function download(?string $filename = null): Response
    {
        $filename ??= $this->getFilename();

        $output = $this->getPdfOutput();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, Str::ascii($filename)),
            'Content-Length' => strlen($output ?? ''),
        ]);
    }

    public function toMailAttachment(?string $filename = null): Attachment
    {
        return Attachment::fromData(fn () => $this->getPdfOutput())
            ->as($filename ?? $this->getFilename())
            ->withMime('application/pdf');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function view(array $data = []): View
    {
        // @phpstan-ignore-next-line
        return view($this->template, ['invoice' => $this], $data);
    }
}
