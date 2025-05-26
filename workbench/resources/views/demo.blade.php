<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

    <div class="flex p-16">

        <div class="relative mx-auto flex w-full max-w-3xl flex-col gap-2">

            <div>
                <a class="text-blue-500" href="/pdf">View as PDF</a>
            </div>

            <div class="bg-white p-12 shadow-md">
                @include('invoices::default.invoice', [
                    'invoice' => $invoice,
                ])
            </div>

        </div>

    </div>

    {{-- Must be added at the end to overwrite Tailwind --}}
    @include('invoices::default.style')

</body>

</html>
