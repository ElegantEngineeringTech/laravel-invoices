@php
    $font = data_get($invoice->templateData, 'font');
    $fonts = data_get($invoice->templateData, 'fonts', []);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <title>{{ $invoice->serial_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @foreach ($fonts as $url)
        <link href="{{ $url }}" rel="stylesheet">
    @endforeach

    @if ($font)
        <style type="text/css">
            body {
                font-family: {{ $font }};
            }
        </style>
    @endif

    @include('invoices::default.style')
</head>

<body>

    <div class="fixed -left-12 -right-12 -top-12">
        @include('invoices::default.includes.header', ['invoice' => $invoice])
    </div>

    <div class="fixed -bottom-20 -left-12 -right-12 mx-12 mb-12">
        @include('invoices::default.includes.footer', ['invoice' => $invoice])
    </div>

    @include('invoices::default.invoice', ['invoice' => $invoice])

</body>

</html>
