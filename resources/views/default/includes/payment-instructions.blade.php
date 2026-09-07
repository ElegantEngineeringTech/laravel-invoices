<div class="-ml-12 -mr-12 -mt-px border-b border-t px-12 py-6">

    <table class="w-full">
        <tbody>
            <tr>
                <td class="w-full p-0 align-top">
                    @if ($paymentInstruction->name)
                        <p class="mb-1 p-px text-sm">
                            <strong>{!! __($paymentInstruction->name) !!}</strong>
                        </p>
                    @endif

                    @if ($paymentInstruction->description)
                        <p class="mb-3 p-px text-xs">
                            {!! __($paymentInstruction->description) !!}
                        </p>
                    @endif

                    <table>
                        <tbody>
                            @foreach ($paymentInstruction->fields as $key => $value)
                                <tr>
                                    @if (is_string($key))
                                        <td class="pr-5 text-xs">{{ __($key) }}</td>
                                        <td class="text-xs text-gray-500">{!! $value !!}</td>
                                    @else
                                        <td class="text-xs" colspan="2">{!! $value !!}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @if ($paymentInstruction->qrcode)
                    <td class="min-w-28 align-top">
                        <img src="{{ $paymentInstruction->qrcode }}" class="w-28 bg-white" />
                    </td>
                @endif
            </tr>
        </tbody>
    </table>
</div>
