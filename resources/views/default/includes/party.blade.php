@if ($party->company && $party->name)
    <p class="p-px text-xs"><strong>{{ $party->company }}</strong></p>
    <p class="p-px text-xs">{{ $party->name }}</p>
@elseif($party->company)
    <p class="p-px text-xs"><strong>{{ $party->company }}</strong></p>
@elseif ($party->name)
    <p class="p-px text-xs"><strong>{{ $party->name }}</strong></p>
@endif

@if ($party->address)
    @include('invoices::default.includes.address', [
        'address' => $party->address,
    ])
@endif

@if ($party->email)
    <p class="p-px text-xs">{{ $party->email }}</p>
@endif

@if ($party->phone)
    <p class="p-px text-xs">{{ $party->phone }}</p>
@endif

@if ($party->tax_id)
    <p class="p-px text-xs">{{ $party->tax_id->getLabel() }}</p>
@endif

@if ($party->identities)
    @foreach ($party->identities as $identity)
        <p class="p-px text-xs">
            {{ $identity->getLabel() }} {{ $identity->code }}
        </p>
    @endforeach
@endif

@if ($party->fields)
    @foreach ($party->fields as $key => $value)
        @if (is_array($value))
            <p class="p-px text-xs">
                @if (is_string($k = $value['key'] ?? null))
                    {{ __($k) }}
                @endif
                {{ $value['value'] ?? null }}
            </p>
        @else
            <p class="p-px text-xs">
                @if (is_string($key))
                    {{ __($key) }}
                @endif
                {{ $value }}
            </p>
        @endif
    @endforeach
@endif
