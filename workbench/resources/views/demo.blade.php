<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

    <div class="flex p-16">

        <div class="relative mx-auto flex w-full max-w-[794px] flex-col gap-2">

            <div>
                <a class="text-blue-500" href="/pdf">View as PDF</a>
            </div>

            <div class="relative bg-white px-12 pb-20 pt-12 shadow-md">

                <div class="absolute left-0 right-0 top-0">
                    @include('invoices::default.includes.header', ['invoice' => $invoice])
                </div>

                <div class="absolute bottom-0 left-0 right-0 mx-12 mb-12">
                    @include('invoices::default.includes.footer', ['invoice' => $invoice])
                </div>

                @include('invoices::default.invoice', [
                    'invoice' => $invoice,
                ])
            </div>

        </div>

    </div>

    {{-- Must be added at the end to overwrite Tailwind --}}
    {{-- @include('invoices::default.style') --}}

</body>

</html>
