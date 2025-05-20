 @if ($address->company)
     <p class="pb-1 text-xs"><strong>{{ $address->company }}</strong></p>
 @endif

 @if ($address->name)
     <p class="pb-1 text-xs">{{ $address->name }}</p>
 @endif

 @if ($address->street)
     <p class="pb-1 text-xs">{{ $address->street }}</p>
 @endif

 @if ($address->city)
     <p class="pb-1 text-xs">
         {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
     </p>
 @endif

 @if ($address->country)
     <p class="pb-1 text-xs">{{ $address->country }}</p>
 @endif

 @if ($address->fields)
     @foreach ($address->fields as $key => $value)
         <p class="pb-1 text-xs">
             @if (is_string($key))
                 {{ $key }}
             @endif
             {{ $value }}
         </p>
     @endforeach
 @endif
