<div>
    <table class="mb-8 w-full">
        <tbody>
            <tr>
                <td class="p-0 align-top">
                    <h1 class="mb-1 text-2xl">
                        <strong>{{ $invoice->getTypeLabel() }}</strong>
                    </h1>
                    <p class="mb-5 text-sm">
                        {{ $invoice->getStateLabel() }}
                    </p>

                    <table class="w-full">
                        <tbody>
                            <tr class="text-xs">
                                <td class="whitespace-nowrap pr-2">
                                    <strong>{{ __('invoices::invoice.serial_number') }} </strong>
                                </td>
                                <td class="whitespace-nowrap" width="100%">
                                    <strong>{{ $invoice->serial_number }}</strong>
                                </td>
                            </tr>
                            <tr class="text-xs">
                                <td class="whitespace-nowrap pr-2">
                                    {{ __('invoices::invoice.created_at') }}
                                </td>
                                <td class="" width="100%">
                                    {{ $invoice->created_at?->format(config('invoices.date_format')) }}
                                </td>
                            </tr>
                            @if ($invoice->due_at)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ __('invoices::invoice.due_at') }}
                                    </td>
                                    <td class="" width="100%">
                                        {{ $invoice->due_at->format(config('invoices.date_format')) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($invoice->paid_at)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ __('invoices::invoice.paid_at') }}
                                    </td>
                                    <td class="" width="100%">
                                        {{ $invoice->paid_at->format(config('invoices.date_format')) }}
                                    </td>
                                </tr>
                            @endif

                            @foreach ($invoice->fields as $key => $value)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ $key }}
                                    </td>
                                    <td class="" width="100%">
                                        {{ $value }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @if ($invoice->logo)
                    <td class="p-0 align-top" width="20%">
                        <img src="{{ $invoice->logo }}" alt="logo" height="100" />
                    </td>
                @endif
            </tr>

        </tbody>
    </table>

    <table class="mb-6 w-full">
        <tbody>
            <tr>
                <td class="p-0 align-top" width="33%">
                    <p class="mb-1 pb-1 text-xs text-gray-500">{{ __('invoices::invoice.from') }}</p>

                    @include('invoices::default.includes.party', [
                        'party' => $invoice->seller,
                    ])
                </td>
                <td class="p-0 align-top" width="33%">
                    <p class="mb-1 pb-1 text-xs text-gray-500">{{ __('invoices::invoice.to') }}</p>

                    @include('invoices::default.includes.party', [
                        'party' => $invoice->buyer,
                    ])
                </td>

                @if ($invoice->buyer->shipping_address)
                    <td class="p-0 align-top" width="33%">

                        <p class="mb-1 whitespace-nowrap pb-1 text-xs text-gray-500">
                            {{ __('invoices::invoice.shipping_to') }}
                        </p>

                        @if ($invoice->buyer->shipping_address)
                            @include('invoices::default.includes.address', [
                                'address' => $invoice->buyer->shipping_address,
                            ])
                        @endif
                    </td>
                @endif

            </tr>
        </tbody>
    </table>

    @php
        $hasTaxes = $invoice->tax_label || $invoice->totalTaxAmount()->isPositive();
    @endphp

    <table class="mb-5 w-full">
        <thead>
            <tr class="text-gray-500">
                <th class="whitespace-nowrap border-b py-2 pr-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.description') }}
                </th>
                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.quantity') }}
                </th>
                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.unit_price') }}
                </th>

                @if ($hasTaxes)
                    <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                        {{ __('invoices::invoice.tax') }}
                    </th>
                @else
                    <th class="p-0"></th>
                @endif

                <th class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs font-normal">
                    {{ __('invoices::invoice.amount') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td @class(['align-top py-2 pr-2', 'border-b' => !$loop->last])>
                        <p class="text-xs"><strong>{{ $item->label }}</strong></p>
                        @if ($item->description)
                            <p class="pt-1 text-xs">{{ $item->description }}</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-b p-2 align-top text-xs">
                        <p>{{ $item->quantity }}</p>
                    </td>
                    <td class="whitespace-nowrap border-b p-2 align-top text-xs">
                        <p>{{ $item->formatMoney($item->unit_price) }}</p>
                    </td>

                    @if ($hasTaxes)
                        <td class="whitespace-nowrap border-b p-2 align-top text-xs">
                            @if ($item->unit_tax !== null && $item->tax_percentage !== null)
                                <p>
                                    {{ $item->formatMoney($item->unit_tax) }}
                                    ({{ $item->formatPercentage($item->tax_percentage) }})
                                </p>
                            @elseif ($item->unit_tax !== null)
                                <p>{{ $item->formatMoney($item->unit_tax) }}</p>
                            @elseif($item->tax_percentage !== null)
                                <p>{{ $item->formatPercentage($item->tax_percentage) }}</p>
                            @else
                                <p>-</p>
                            @endif
                        </td>
                    @else
                        <td class="p-0"></td>
                    @endif

                    <td class="whitespace-nowrap border-b py-2 pl-2 text-right align-top text-xs">
                        <p>{{ $item->formatMoney($item->totalAmount()) }}</p>
                    </td>
                </tr>
            @endforeach

            <tr>
                {{-- empty space --}}
                <td class="py-2 pr-2"></td>
                <td class="border-b p-2 text-xs" colspan="3">
                    {{ __('invoices::invoice.subtotal_amount') }}
                </td>
                <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                    {{ $invoice->formatMoney($invoice->subTotalAmount()) }}
                </td>
            </tr>

            @if ($invoice->discounts)
                @foreach ($invoice->discounts as $discount)
                    <tr>
                        {{-- empty space --}}
                        <td class="py-2 pr-2"></td>
                        <td class="border-b p-2 text-xs" colspan="3">
                            {{ __($discount->name ?: $discount->code) ?: __('invoices::invoice.discount_name') }}
                            @if ($discount->percent_off)
                                ({{ $discount->formatPercentage($discount->percent_off) }})
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                            {{ $invoice->formatMoney($discount->computeDiscountAmountOn($invoice->subTotalAmount())?->multipliedBy(-1)) }}
                        </td>
                    </tr>
                @endforeach

                <tr>
                    {{-- empty space --}}
                    <td class="py-2 pr-2"></td>
                    <td class="border-b p-2 text-xs" colspan="3">
                        {{ __('invoices::invoice.subtotal_discounted_amount') }}
                    </td>
                    <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                        {{ $invoice->formatMoney($invoice->subTotalDiscountedAmount()) }}
                    </td>
                </tr>
            @endif



            @if ($hasTaxes)
                <tr>
                    {{-- empty space --}}
                    <td class="py-2 pr-2"></td>
                    <td class="border-b p-2 text-xs" colspan="3">
                        {{ $invoice->tax_label ?? __('invoices::invoice.tax_label') }}
                    </td>
                    <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                        {{ $invoice->formatMoney($invoice->totalTaxAmount()) }}
                    </td>
                </tr>
            @endif

            <tr>
                {{-- empty space --}}
                <td class="py-2 pr-2"></td>
                <td class="p-2 text-sm" colspan="3">
                    <strong>{{ __('invoices::invoice.total_amount') }}</strong>
                </td>
                <td class="whitespace-nowrap py-2 pl-2 text-right text-sm">
                    <strong>
                        {{ $invoice->formatMoney($invoice->totalAmount()) }}
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->description)
        <p class="mb-2 text-sm">
            <strong> {{ __('invoices::invoice.description') }} </strong>
        </p>
        <p class="whitespace-pre-line text-xs">{!! $invoice->description !!}</p>
    @endif

    @if ($invoice->paymentInstructions)
        <div class="mt-12">
            @foreach ($invoice->paymentInstructions as $paymentInstruction)
                <div @class([
                    'border-b' => !$loop->last,
                    '-ml-12 -mr-12 px-12 bg-zinc-100 py-6',
                ])>

                    <table class="w-full">
                        <tbody>
                            <tr>
                                <td class="w-full p-0 align-top">
                                    @if ($paymentInstruction->name)
                                        <p class="mb-1 text-xs">
                                            <strong>{!! $paymentInstruction->name !!}</strong>
                                        </p>
                                    @endif

                                    @if ($paymentInstruction->description)
                                        <p class="mb-3 text-xs">
                                            {!! $paymentInstruction->description !!}
                                        </p>
                                    @endif

                                    <table>
                                        <tbody>
                                            @foreach ($paymentInstruction->fields as $key => $value)
                                                <tr>
                                                    @if (is_string($key))
                                                        <td class="py-1 pr-5 text-xs">{{ $key }}</td>
                                                        <td class="py-1 pl-2 text-xs text-gray-500">
                                                            {!! $value !!}
                                                        </td>
                                                    @else
                                                        <td class="py-1 pr-5 text-xs" colspan="2">
                                                            {!! $value !!}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                @if ($paymentInstruction->qrcode)
                                    <td class="min-w-28 p-0 align-top">
                                        <img src="{{ $paymentInstruction->qrcode }}" class="w-28 bg-white" />
                                    </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif


</div>
