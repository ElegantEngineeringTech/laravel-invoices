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
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Support\Party;
use Elegantly\Invoices\Support\PaymentInstruction;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PdfInvoice implements Attachable
{
    public string $template;

    /**
     * @param  array<string, mixed>  $fields  Additianl fields to display in the header
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

    public function getDiscounts(): InvoiceDiscountCollection
    {

        $index = -1;

        $discounts = $this->items
            ->toBase()
            ->flatMap(fn ($item) => $item->discounts)
            ->groupBy(fn ($discount) => implode('|', [$discount->code, $discount->name, $discount->percentage]))
            ->map(function ($discounts, $group) use (&$index) {
                $index++;
                [$code, $name, $percentage] = explode('|', $group);

                $discounts->each(function ($discount) use ($index) {
                    $discount->setIndex($index);
                });

                return new InvoiceDiscount(
                    code: $code,
                    name: $name,
                    percentage: (float) $percentage,
                    amount: new InvoiceDiscountCollection($discounts)->amount(),
                )->setIndex($index);
            });

        return new InvoiceDiscountCollection($discounts);

    }

    public function getTaxes(): InvoiceTaxCollection
    {

        $index = -1;

        $discounts = $this->items
            ->toBase()
            ->flatMap(fn ($item) => $item->taxes)
            ->groupBy(fn ($tax) => implode('|', [$tax->type, $tax->label, $tax->percentage, $tax->taxability]))
            ->map(function ($taxes, $group) use (&$index) {
                $index++;
                [$type, $label, $percentage, $taxability] = explode('|', $group);

                $taxes->each(function ($tax) use ($index) {
                    $tax->setIndex($index);
                });

                return new InvoiceTax(
                    type: $type,
                    label: $label,
                    taxability: $taxability,
                    percentage: (float) $percentage,
                    amount: new InvoiceTaxCollection($taxes)->amount(),
                )->setIndex($index);
            });

        return new InvoiceTaxCollection($discounts);

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
