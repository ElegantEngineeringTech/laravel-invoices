@if ($party->company && $party->name)
    <p class="pb-1 text-xs"><strong>{{ $party->company }}</strong></p>
    <p class="pb-1 text-xs">{{ $party->name }}</p>
@elseif($party->company)
    <p class="pb-1 text-xs"><strong>{{ $party->company }}</strong></p>
@elseif ($party->name)
    <p class="pb-1 text-xs"><strong>{{ $party->name }}</strong></p>
@endif

@if ($party->address)
    @include('invoices::default.includes.address', [
        'address' => $party->address,
    ])
@endif

@if ($party->email)
    <p class="pb-1 text-xs">{{ $party->email }}</p>
@endif
@if ($party->phone)
    <p class="pb-1 text-xs">{{ $party->phone }}</p>
@endif
@if ($party->tax_number)
    <p class="pb-1 text-xs">{{ $party->tax_number }}</p>
@endif

@if ($party->fields)
    @foreach ($party->fields as $key => $value)
        <p class="pb-1 text-xs">
            @if (is_string($key))
                {{ $key }}
            @endif
            {{ $value }}
        </p>
    @endforeach
@endif
