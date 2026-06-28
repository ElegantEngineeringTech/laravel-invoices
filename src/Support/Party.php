<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Elegantly\Invoices\Contracts\GOBLable;
use Elegantly\Invoices\InvoiceServiceProvider;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

/**
 * @implements Arrayable<string, null|string|array<string,null|string>>
 *
 * @see https://docs.gobl.org/draft-0/org/party
 */
class Party implements Arrayable, Castable, GOBLable, Jsonable, JsonSerializable
{
    /**
     * @param  array<array-key, null|int|float|string>  $fields
     * @param  array<array-key, Identity>  $identities
     */
    public function __construct(
        public ?string $company = null,
        public ?string $name = null,
        public ?Address $address = null,
        public ?Address $shipping_address = null,
        public ?TaxId $tax_id = null,
        public ?string $email = null,
        public ?string $phone = null,
        public array $identities = [],
        public array $fields = [],
    ) {
        // code...
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        $addressClass = InvoiceServiceProvider::getAddressClass();
        $identityClass = InvoiceServiceProvider::getIdentityClass();
        $taxIdClass = InvoiceServiceProvider::getTaxIdClass();

        return new self(
            // @phpstan-ignore-next-line
            company: data_get($values, 'company'),
            // @phpstan-ignore-next-line
            name: data_get($values, 'name'),
            // @phpstan-ignore-next-line
            address: ($address = data_get($values, 'address')) ? $addressClass::fromArray($address) : null,
            // @phpstan-ignore-next-line
            shipping_address: ($shipping_address = data_get($values, 'shipping_address')) ? $addressClass::fromArray($shipping_address) : null,
            // @phpstan-ignore-next-line
            tax_id: ($taxId = data_get($values, 'tax_id')) ? $taxIdClass::fromArray($taxId) : null,
            // @phpstan-ignore-next-line
            email: data_get($values, 'email'),
            // @phpstan-ignore-next-line
            phone: data_get($values, 'phone'),
            // @phpstan-ignore-next-line
            identities: array_map(fn ($value) => $identityClass::fromArray($value), data_get($values, 'identities') ?? []),
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
     *    tax_id: ?array{ country?: null|string, code?: null|string },
     *    email: ?string,
     *    phone: ?string,
     *    identities: array<array-key, array{ type: null|string, code: null|string }>,
     *    fields: array<array-key, null|int|float|string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company,
            'name' => $this->name,
            'address' => $this->address?->toArray(),
            'shipping_address' => $this->shipping_address?->toArray(),
            'tax_id' => $this->tax_id?->toArray(),
            'email' => $this->email,
            'phone' => $this->phone,
            'identities' => array_map(fn ($value) => $value->toArray(), $this->identities),
            'fields' => $this->fields,
        ];
    }

    /**
     * Convert the party to its GOBL representation.
     *
     * @param  array<array-key, mixed>  $values
     * @param  array<array-key, mixed>  $identity
     * @param  array<array-key, mixed>  $address
     * @return array{
     *     name?: ?string,
     *     identities?: array<array-key, array{type?: null|string, code?: null|string}>,
     *     addresses?: array<array-key, array{street?: null|string, locality?: null|string, code?: null|string, country?: null|string}>,
     *     emails?: array<array-key, string>,
     *     telephones?: array<array-key, string>,
     *     tax_id?: array{country?: null|string, code?: null|string},
     * }
     */
    public function toGOBL(
        array $identity = [],
        array $address = [],
        array $values = []
    ): array {
        return array_filter(array_merge_recursive(
            [
                'name' => $this->company ?? $this->name,
                'identities' => array_filter(
                    array_map(fn ($value) => $value->toGOBL($identity), $this->identities),
                    fn ($value) => filled($value)
                ),
                'addresses' => array_filter([
                    $this->address?->toGOBL($address),
                ], fn ($value) => filled($value)),
                'emails' => $this->email ? [
                    ['addr' => $this->email],
                ] : null,
                'telephones' => $this->phone ? [
                    ['num' => $this->phone],
                ] : null,
                'tax_id' => $this->tax_id?->toGOBL(),
            ],
            $values
        ), fn ($value) => filled($value));
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options) ?: '';
    }

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array<string, mixed>  $arguments
     * @return CastsAttributes<null|Party, null|string>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $class = static::class;

        /**
         * @implements CastsAttributes<null|Party, null|string>
         */
        return new class($class) implements CastsAttributes
        {
            /**
             * @param  class-string<Party>  $class
             */
            public function __construct(
                private string $class,
            ) {}

            public function get(Model $model, string $key, mixed $value, array $attributes): ?Party
            {
                return match (true) {
                    is_array($value) => $this->class::fromArray($value),
                    is_string($value) => $this->class::fromArray(json_decode($value, true)),
                    default => null
                };

            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                return match (true) {
                    $value === null => null,
                    default => json_encode($value) ?: null
                };
            }

            public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
            {
                $firstValue = is_string($firstValue) ? json_decode($firstValue) : $firstValue;
                $secondValue = is_string($secondValue) ? json_decode($secondValue) : $secondValue;

                /** loose comparison because keys order does not matter */
                return $firstValue == $secondValue;
            }
        };
    }
}
