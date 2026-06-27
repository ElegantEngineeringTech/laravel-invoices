<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Elegantly\Invoices\Contracts\GOBLable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, null|string>
 *
 * @example ['country' => 'FR', 'code'=> '732829320']
 *
 * @see https://docs.gobl.org/draft-0/tax/identity
 */
class TaxId implements Arrayable, GOBLable
{
    public function __construct(
        public ?string $country = null,
        public ?string $code = null,
    ) {
        // code...
    }

    public function getLabel(): ?string
    {
        if ($this->country) {
            return "{$this->country}{$this->code}";
        }

        return $this->code;
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            // @phpstan-ignore-next-line
            country: data_get($values, 'country'),
            // @phpstan-ignore-next-line
            code: data_get($values, 'code'),
        );
    }

    /**
     * @return array{
     *    country: ?string,
     *    code: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'code' => $this->code,
        ];
    }

    /**
     * Convert the identity to its GOBL representation.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function toGOBL(array $values = []): array
    {
        return array_filter([
            'country' => $this->country,
            'code' => $this->code,
            ...$values,
        ]);
    }
}
