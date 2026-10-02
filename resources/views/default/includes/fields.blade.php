@foreach ($fields as $key => $value)
    @if (is_array($value))
        <tr class="text-xs">
            <td class="whitespace-nowrap pr-2">
                @if (is_string($k = $value['key'] ?? null))
                    {{ __($k) }}
                @endif
            </td>
            <td width="100%">
                {{ $value['value'] ?? null }}
            </td>
        </tr>
    @else
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
    @endif
@endforeach
