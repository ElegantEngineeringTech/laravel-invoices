@if ($invoice->description)
    <p class="mb-2 p-px text-sm">
        <strong> {{ __('invoices::invoice.pdf.description') }} </strong>
    </p>
    <p class="whitespace-pre-line p-px text-xs">{{ $invoice->description }}</p>
@endif
