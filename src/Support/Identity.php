<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Support;

use Elegantly\Invoices\Contracts\GOBLable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, null|string>
 *
 * @example ['type' => 'SIREN', 'code'=> '732829320']
 *
 * @see https://docs.gobl.org/draft-0/org/identity
 */
class Identity implements Arrayable, GOBLable
{
    public function __construct(
        public ?string $type = null,
        public ?string $code = null,
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
            type: data_get($values, 'type'),
            // @phpstan-ignore-next-line
            code: data_get($values, 'code'),
        );
    }

    /**
     * @return array{
     *    type: ?string,
     *    code: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code,
        ];
    }

    /**
     * @return array{
     *    type: ?string,
     *    code: ?string,
     * }
     */
    public function toGOBL(): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code,
        ];
    }
}
