<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Pdf;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Dompdf\Dompdf;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Elegantly\Invoices\Contracts\HasLabel;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Support\Buyer;
use Elegantly\Invoices\Support\PaymentInstruction;
use Elegantly\Invoices\Support\Seller;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Http\Response;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PdfInvoice implements Attachable
{
    use FormatForPdf;

    public string $template;

    /**
     * @param  array<string, mixed>  $fields  Additianl fileds to display in the header
     * @param  PdfInvoiceItem[]  $items
     * @param  InvoiceDiscount[]  $discounts
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

        public Seller $seller = new Seller,
        public Buyer $buyer = new Buyer,
        public array $items = [],

        public ?string $description = null,
        public ?string $tax_label = null,
        public array $discounts = [],

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

    public function getCurrency(): string
    {
        /** @var ?PdfInvoiceItem $firstItem */
        $firstItem = Arr::first($this->items);

        return $firstItem?->currency->getCurrencyCode() ?? config()->string('invoices.default_currency');
    }

    /**
     * Before discount and taxes
     */
    public function subTotalAmount(): Money
    {
        return array_reduce(
            $this->items,
            fn ($total, $item) => $total->plus($item->subTotalAmount()),
            Money::of(0, $this->getCurrency())
        );
    }

    public function totalDiscountAmount(): Money
    {
        if (! $this->discounts) {
            return Money::of(0, $this->getCurrency());
        }

        $amount = $this->subTotalAmount();

        return array_reduce(
            $this->discounts,
            function ($total, $discount) use ($amount) {
                return $total->plus($discount->computeDiscountAmountOn($amount));
            },
            Money::of(0, $amount->getCurrency())
        );
    }

    public function subTotalDiscountedAmount(): Money
    {
        return $this->subTotalAmount()->minus($this->totalDiscountAmount());
    }

    /**
     * After discount and taxes
     */
    public function totalTaxAmount(): Money
    {
        $totalDiscount = $this->totalDiscountAmount();

        /**
         * Taxes must be calculated based on the discounted subtotal.
         * Since discounts are applied at the invoice level, but taxes are calculated at the item level,
         * we allocate the total discount proportionally across individual items before computing taxes.
         */
        $ratios = array_map(
            fn ($item) => $item->subTotalAmount()->abs()->getMinorAmount()->toInt(),
            $this->items
        );

        if (array_sum($ratios) === 0) {
            return Money::of(0, $this->getCurrency());
        }

        $allocatedDiscounts = $totalDiscount->allocate(...$ratios);

        $totalTaxAmount = Money::of(0, $this->getCurrency());

        foreach ($this->items as $index => $item) {

            if ($item->unit_tax) {
                /**
                 * When unit_tax is defined, the amount is considered correct
                 */
                $itemTaxAmount = $item->unit_tax->multipliedBy($item->quantity);
            } elseif ($item->tax_percentage) {

                $itemDiscount = $allocatedDiscounts[$index];

                $itemTaxAmount = $item->subTotalAmount()
                    ->minus($itemDiscount)
                    ->multipliedBy($item->tax_percentage / 100.0, roundingMode: RoundingMode::HALF_EVEN);

            } else {
                $itemTaxAmount = Money::of(0, $totalTaxAmount->getCurrency());
            }

            $totalTaxAmount = $totalTaxAmount->plus($itemTaxAmount);

        }

        return $totalTaxAmount;
    }

    public function totalAmount(): Money
    {
        return $this->subTotalDiscountedAmount()->plus($this->totalTaxAmount());
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array{ size?: string, orientation?: string }  $paper
     */
    public function pdf(array $options = [], array $paper = []): Dompdf
    {

        $pdf = new Dompdf(array_merge(
            // @phpstan-ignore-next-line
            config('invoices.pdf.options') ?? config('invoices.pdf_options') ?? [],
            $options,
        ));

        $pdf->setPaper(
            // @phpstan-ignore-next-line
            $paper['size'] ?? config('invoices.pdf.paper.size') ?? config('invoices.pdf.paper.paper') ?? config('invoices.paper_options.paper') ?? 'a4',
            // @phpstan-ignore-next-line
            $paper['orientation'] ?? config('invoices.pdf.paper.orientation') ?? config('invoices.paper_options.orientation') ?? 'portrait'
        );

        $html = $this->view()->render();

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

    public function view(): \Illuminate\Contracts\View\View
    {
        // @phpstan-ignore-next-line
        return view($this->template, ['invoice' => $this]);
    }
}
