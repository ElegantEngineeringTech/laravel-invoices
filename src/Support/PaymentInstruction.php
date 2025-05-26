<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, null|string|array<string,null|string>>
 */
class PaymentInstruction implements Arrayable
{
    /**
     * @param  array<array-key, null|int|float|string>  $fields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $qrcode = null,
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
            name: data_get($values, 'name'),
            // @phpstan-ignore-next-line
            description: data_get($values, 'description'),
            // @phpstan-ignore-next-line
            qrcode: data_get($values, 'qrcode'),
            // @phpstan-ignore-next-line
            fields: data_get($values, 'fields') ?? [],
        );
    }

    /**
     * @return array{
     *    name: ?string,
     *    description: ?string,
     *    fields: null|array<array-key, null|int|float|string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'qrcode' => $this->qrcode,
            'fields' => $this->fields,
        ];
    }
}
