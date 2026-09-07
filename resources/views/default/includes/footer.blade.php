@php
    use function Elegantly\Invoices\money;
@endphp

<table class="w-full">
    <tbody>
        <tr class="text-xs text-gray-500">
            <td class="">
                {{ $invoice->serial_number }} • {{ money($invoice->total_amount) }}
            </td>
            <td class="text-right">
                <p class="dompdf-page">{{ __('invoices::invoice.pdf.page') }} </p>
            </td>
        </tr>
    </tbody>
</table>
