<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, null|string|array<string,null|string>>
 */
class Buyer implements Arrayable
{
    /**
     * @param  array<array-key, null|int|float|string>  $fields
     */
    public function __construct(
        public ?string $company = null,
        public ?string $name = null,
        public ?Address $address = null,
        public ?Address $shipping_address = null,
        public ?string $tax_number = null,
        public ?string $email = null,
        public ?string $phone = null,
        public array $fields = [],
    ) {
        // code...
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            // @phpstan-ignore-next-line
            company: data_get($values, 'company'),
            // @phpstan-ignore-next-line
            name: data_get($values, 'name'),
            // @phpstan-ignore-next-line
            address: ($address = data_get($values, 'address')) ? Address::fromArray($address) : null,
            // @phpstan-ignore-next-line
            shipping_address: ($shipping_address = data_get($values, 'shipping_address')) ? Address::fromArray($shipping_address) : null,
            // @phpstan-ignore-next-line
            tax_number: data_get($values, 'tax_number'),
            // @phpstan-ignore-next-line
            email: data_get($values, 'email'),
            // @phpstan-ignore-next-line
            phone: data_get($values, 'phone'),
            // @phpstan-ignore-next-line
            fields: data_get($values, 'fields') ?? data_get($values, 'data') ?? [],
        );
    }

    /**
     * @return array{
     *    company: ?string,
     *    name: ?string,
     *    address: null|array{
     *       company: ?string,
     *       name: ?string,
     *       street: null|string|string[],
     *       state: ?string,
     *       postal_code: ?string,
     *       city: ?string,
     *       country: ?string,
     *       fields: null|array<array-key, null|int|float|string>,
     *    },
     *    shipping_address: null|array{
     *       company: ?string,
     *       name: ?string,
     *       street: null|string|string[],
     *       state: ?string,
     *       postal_code: ?string,
     *       city: ?string,
     *       country: ?string,
     *       fields: null|array<array-key, null|int|float|string>,
     *    },
     *    tax_number: ?string,
     *    email: ?string,
     *    phone: ?string,
     *    fields: null|array<array-key, null|int|float|string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company,
            'name' => $this->name,
            'address' => $this->address?->toArray(),
            'shipping_address' => $this->shipping_address?->toArray(),
            'tax_number' => $this->tax_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'fields' => $this->fields,
        ];
    }
}
