<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\Money;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Elegantly\Invoices\Contracts\GOBLable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
class InvoiceDiscount implements Arrayable, GOBLable, Jsonable, JsonSerializable
{
    use FormatForPdf;

    public ?string $code = null;

    public ?Money $amount = null;

    public ?float $percentage = null;

    public ?string $name = null;

    /**
     * @param  null|string|array{
     *      code?: null|string,
     *      name?: null|string,
     *      amount?: null|int|Money,
     *      currency?: null|string,
     *      percentage?: null|float,
     * }  $code
     */
    public function __construct(
        null|string|array $code = null,
        ?string $name = null,
        ?Money $amount = null,
        ?float $percentage = null,
    ) {
        if (is_array($code)) {

            $this->code = $code['code'] ?? null;
            $this->percentage = $code['percentage'] ?? null;
            $this->name = $code['name'] ?? null;

            $amount = $code['amount'] ?? null;
            $currency = $code['currency'] ?? null;

            if ($amount instanceof Money) {
                $this->amount = $amount;
            } elseif ($amount && $currency) {
                $this->amount = Money::ofMinor($amount, $currency);
            }

        } else {
            $this->code = $code;
            $this->amount = $amount;
            $this->percentage = $percentage;
            $this->name = $name;
        }
    }

    /**
     * @return array{
     *      code: null|string,
     *      name: null|string,
     *      amount: null|int,
     *      currency: null|string,
     *      percentage: null|float,
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'amount' => $this->amount?->getMinorAmount()->toInt(),
            'currency' => $this->amount?->getCurrency()->getCurrencyCode(),
            'percentage' => $this->percentage,
        ];
    }

    /**
     * @return array{
     *      code: null|string,
     *      name: null|string,
     *      amount: null|int,
     *      currency: null|string,
     *      percentage: null|float,
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options) ?: '';
    }

    /**
     * @return array{
     *      code: null|string,
     *      name: null|string,
     *      amount: null|int,
     *      currency: null|string,
     *      percentage: null|float,
     * }
     */
    public function toLivewire()
    {
        return $this->toArray();
    }

    /**
     * @param array{
     *      code: null|string,
     *      name: null|string,
     *      amount: null|int,
     *      currency: null|string,
     *      percentage: null|float,
     * } $value
     */
    // @phpstan-ignore-next-line
    public static function fromLivewire($value)
    {
        return new static($value);
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
            'amount' => $this->amount?->getAmount()->toString(),
            'percent' => $this->percentage ? "{$this->percentage}%" : null,
            'reason' => $this->name,
            'code' => $this->code,
            ...$values,
        ], fn ($value) => filled($value));
    }
}
