<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, null|string|array<string,null|string>>
 */
class PaymentInstruction implements Arrayable, JsonSerializable
{
    public ?string $name = null;

    /**
     * @param  null|string|array{name: ?string, description: ?string, qrcode: ?string, fields: null|array<array-key, null|int|float|string>}  $name
     * @param  array<array-key, null|int|float|string>  $fields
     * @param  ?string  $qrcode  A Base64 encoded image
     */
    public function __construct(
        null|string|array $name = null,
        public ?string $description = null,
        public ?string $qrcode = null,
        public array $fields = [],
    ) {
        if (is_array($name)) {
            $this->name = $name['name'] ?? null;
            $this->description = $name['description'] ?? null;
            $this->qrcode = $name['qrcode'] ?? null;
            $this->fields = $name['fields'] ?? [];
        } else {
            $this->name = $name;
        }
    }

    /**
     * @param  array{name: ?string, description: ?string, qrcode: ?string, fields: null|array<array-key, null|int|float|string>}  $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * @return array{
     *    name: ?string,
     *    description: ?string,
     *    qrcode: ?string,
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

    /**
     * Specify the data which should be serialized to JSON.
     *
     * @return array{
     *    name: ?string,
     *    description: ?string,
     *    qrcode: ?string,
     *    fields: null|array<array-key, null|int|float|string>,
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
