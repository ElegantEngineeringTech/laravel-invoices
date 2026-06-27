<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Elegantly\Invoices\Contracts\GOBLable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, null|string>
 *
 * @see https://docs.gobl.org/draft-0/org/address
 */
class Address implements Arrayable, GOBLable
{
    /**
     * @param  null|string|string[]  $street
     * @param  array<array-key, null|int|float|string>  $fields
     */
    public function __construct(
        public ?string $company = null,
        public ?string $name = null,
        public null|string|array $street = null,
        public ?string $state = null,
        public ?string $postal_code = null,
        public ?string $city = null,
        public ?string $country = null,
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
            street: data_get($values, 'street'),
            // @phpstan-ignore-next-line
            state: data_get($values, 'state'),
            // @phpstan-ignore-next-line
            postal_code: data_get($values, 'postal_code'),
            // @phpstan-ignore-next-line
            city: data_get($values, 'city'),
            // @phpstan-ignore-next-line
            country: data_get($values, 'country'),
            // @phpstan-ignore-next-line
            fields: data_get($values, 'fields') ?? [],
        );
    }

    /**
     * @return array{
     *    company: ?string,
     *    name: ?string,
     *    street: null|string|string[],
     *    state: ?string,
     *    postal_code: ?string,
     *    city: ?string,
     *    country: ?string,
     *    fields: null|array<array-key, null|int|float|string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company,
            'name' => $this->name,
            'street' => $this->street,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'fields' => $this->fields,
        ];
    }

    /**
     * Convert the address to its GOBL representation.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function toGOBL(array $values = []): array
    {
        return array_filter([
            'street' => is_array($this->street) ? implode("\n", $this->street) : $this->street,
            'locality' => $this->city,
            'code' => $this->postal_code,
            'country' => $this->country,
            ...$values,
        ], fn ($value) => filled($value));
    }
}
