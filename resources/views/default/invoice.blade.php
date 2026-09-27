@php
    use function Elegantly\Invoices\format_money;

    $dateFormat = config('invoices.date_format');
    $discounts = $invoice->getDiscounts();
    $taxes = $invoice->getTaxes();
@endphp

<div>
    <table class="mb-8 w-full">
        <tbody>
            <tr>
                <td class="p-0 align-top">
                    <h1 class="mb-1 p-px text-2xl">
                        <strong>{{ $invoice->getTypeLabel() }}</strong>
                    </h1>
                    <p class="mb-5 p-px text-sm">
                        {{ $invoice->getStateLabel() }}
                    </p>

                    <table class="w-full">
                        <tbody>
                            <tr class="text-xs">
                                <td class="whitespace-nowrap pr-2">
                                    <strong>{{ __('invoices::invoice.pdf.serial_number') }} </strong>
                                </td>
                                <td class="whitespace-nowrap" width="100%">
                                    <strong>{{ $invoice->serial_number }}</strong>
                                </td>
                            </tr>
                            @if ($invoice->created_at)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ __('invoices::invoice.pdf.created_at') }}
                                    </td>
                                    <td class="" width="100%">
                                        {{ $invoice->created_at->isoFormat($dateFormat) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($invoice->due_at)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ __('invoices::invoice.pdf.due_at') }}
                                    </td>
                                    <td class="" width="100%">
                                        {{ $invoice->due_at->isoFormat($dateFormat) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($invoice->paid_at)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        {{ __('invoices::invoice.pdf.paid_at') }}
                                    </td>
                                    <td width="100%">
                                        {{ $invoice->paid_at->isoFormat($dateFormat) }}
                                    </td>
                                </tr>
                            @endif

                            @foreach ($invoice->fields as $key => $value)
                                <tr class="text-xs">
                                    <td class="whitespace-nowrap pr-2">
                                        @if (is_string($key))
                                            {{ __($key) }}
                                        @endif
                                    </td>
                                    <td width="100%">
                                        {{ $value }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @if ($invoice->logo)
                    <td class="align-top" width="20%">
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
                    <p class="mb-1 whitespace-nowrap p-px text-xs text-gray-500">
                        {{ __('invoices::invoice.pdf.from') }}
                    </p>

                    @include('invoices::default.includes.party', [
                        'party' => $invoice->seller,
                    ])
                </td>
                <td class="p-0 align-top" width="33%">
                    <p class="mb-1 whitespace-nowrap p-px text-xs text-gray-500">
                        {{ __('invoices::invoice.pdf.to') }}
                    </p>

                    @include('invoices::default.includes.party', [
                        'party' => $invoice->buyer,
                    ])
                </td>

                @if ($invoice->buyer->shipping_address)
                    <td class="p-0 align-top" width="33%">

                        <p class="mb-1 whitespace-nowrap p-px text-xs text-gray-500">
                            {{ __('invoices::invoice.pdf.shipping_to') }}
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

    <table class="mb-5 w-full">
        <thead>
            <tr class="text-gray-500">
                <th class="whitespace-nowrap border-b py-2 pr-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.label') }}
                </th>
                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.quantity') }}
                </th>
                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.unit_price') }}
                </th>

                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.discount') }}
                </th>

                <th class="whitespace-nowrap border-b p-2 text-left text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.tax') }}
                </th>

                <th class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs font-normal">
                    {{ __('invoices::invoice.pdf.items.amount') }}
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
                        <p>{{ format_money($item->unit_price) }}</p>
                    </td>

                    <td class="whitespace-nowrap border-b p-2 align-top text-xs">
                        <p>
                            {{ format_money($item->price_discount) }}

                            @foreach ($item->discounts as $discount)
                                @include('invoices::default.includes.indicator', [
                                    'index' => $discount->getIndex(),
                                    'seed' => 4,
                                ])
                            @endforeach
                        </p>
                    </td>

                    <td class="whitespace-nowrap border-b p-2 align-top text-xs">
                        <p>
                            {{ format_money($item->price_tax) }}

                            @foreach ($item->taxes as $tax)
                                @include('invoices::default.includes.indicator', [
                                    'index' => $tax->getIndex(),
                                    'seed' => 104,
                                ])
                            @endforeach
                        </p>
                    </td>

                    <td class="whitespace-nowrap border-b py-2 pl-2 text-right align-top text-xs">
                        <p>{{ format_money($item->price) }}</p>
                    </td>
                </tr>
            @endforeach

            <tr>
                {{-- empty space --}}
                <td class="py-2 pr-2"></td>
                <td class="border-b p-2 text-xs" colspan="4">
                    {{ __('invoices::invoice.pdf.summary.subtotal') }}
                </td>
                <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                    {{ format_money($invoice->subtotal_amount) }}
                </td>
            </tr>

            @if ($discounts->isNotEmpty())
                @foreach ($discounts as $discount)
                    <tr class="text-gray-500">
                        {{-- empty space --}}
                        <td class="py-2 pr-2"></td>
                        <td class="border-b p-2 text-xs" colspan="4">
                            @if ($label = $discount->getLabel())
                                {{ $label }}
                            @else
                                {{ __('invoices::invoice.pdf.summary.discount') }}
                            @endif

                            @if ($discount->code)
                                {{ $discount->code }}
                            @endif

                            @if ($discount->percentage)
                                ({{ Number::percentage($discount->percentage) }})
                            @endif

                            @include('invoices::default.includes.indicator', [
                                'index' => $discount->getIndex(),
                                'seed' => 4,
                            ])

                        </td>
                        <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                            {{ format_money($discount->amount) }}
                        </td>
                    </tr>
                @endforeach

                <tr>
                    {{-- empty space --}}
                    <td class="py-2 pr-2"></td>
                    <td class="border-b p-2 text-xs" colspan="4">
                        {{ __('invoices::invoice.pdf.summary.discounted') }}
                    </td>
                    <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                        {{ format_money($invoice->subtotal_amount->minus($invoice->discount_amount)) }}
                    </td>
                </tr>
            @endif


            @if ($taxes->isNotEmpty())
                @foreach ($taxes as $tax)
                    <tr class="text-gray-500">
                        {{-- empty space --}}
                        <td class="py-2 pr-2"></td>
                        <td class="border-b p-2 text-xs" colspan="4">
                            @if ($label = $tax->getLabel())
                                {{ $label }}
                            @else
                                {{ __('invoices::invoice.pdf.summary.tax') }}

                                @if ($tax->country)
                                    {{ $tax->country }}
                                @endif

                                @if ($tax->type)
                                    {{ $tax->type }}
                                @endif
                            @endif

                            @if ($tax->percentage)
                                ({{ Number::percentage($tax->percentage) }})
                            @endif

                            @include('invoices::default.includes.indicator', [
                                'index' => $tax->getIndex(),
                                'seed' => 104,
                            ])

                        </td>
                        <td class="whitespace-nowrap border-b py-2 pl-2 text-right text-xs">
                            {{ format_money($tax->amount) }}
                        </td>
                    </tr>
                @endforeach

            @endif

            <tr>
                {{-- empty space --}}
                <td class="py-2 pr-2"></td>
                <td class="p-2 text-sm" colspan="4">
                    <strong>{{ __('invoices::invoice.pdf.summary.total') }}</strong>
                </td>
                <td class="whitespace-nowrap py-2 pl-2 text-right text-sm">
                    <strong>
                        {{ format_money($invoice->total_amount) }}
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->description)
        <p class="mb-2 p-px text-sm">
            <strong> {{ __('invoices::invoice.pdf.description') }} </strong>
        </p>
        <p class="whitespace-pre-line p-px text-xs">{!! $invoice->description !!}</p>
    @endif

    @if ($invoice->paymentInstructions)
        <div class="mt-12 pt-px">
            @foreach ($invoice->paymentInstructions as $paymentInstruction)
                @include('invoices::default.includes.payment-instructions', [
                    'first' => $loop->first,
                    'last' => $loop->last,
                    'index' => $loop->index,
                    'paymentInstruction' => $paymentInstruction,
                ])
            @endforeach
        </div>
    @endif

</div>
