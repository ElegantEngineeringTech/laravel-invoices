@php
    use function Elegantly\Invoices\color;
@endphp


<span class="inline-block size-1 rounded-full" style="background-color: {{ color($index, $seed) }}"></span>
