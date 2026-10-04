@php
    use function Elegantly\Invoices\random_color;
@endphp

<span class="inline-block size-1 rounded-full align-top"
    style="background-color: {{ random_color($index, $seed) }}"></span>
