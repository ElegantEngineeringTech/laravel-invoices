<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\Money;
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
    /**
     * Used internally to identify items in aggregate
     */
    protected int $index;

    public ?string $label = null;

    public ?string $code = null;

    public ?Money $amount = null;

    public ?float $percentage = null;

    /**
     * @param  null|string|array{
     *      code?: null|string,
     *      label?: null|string,
     *      amount?: null|int|Money,
     *      currency?: null|string,
     *      percentage?: null|float,
     * }  $code
     */
    public function __construct(
        null|string|array $code = null,
        ?string $label = null,
        ?Money $amount = null,
        ?float $percentage = null,
    ) {
        if (is_array($code)) {

            $this->code = $code['code'] ?? null;
            $this->label = $code['label'] ?? null;
            $this->percentage = $code['percentage'] ?? null;

            $amount = $code['amount'] ?? null;
            $currency = $code['currency'] ?? null;

            if ($amount instanceof Money) {
                $this->amount = $amount;
            } elseif ($amount && $currency) {
                $this->amount = Money::ofMinor($amount, $currency);
            }

        } else {
            $this->code = $code;
            $this->label = $label;
            $this->amount = $amount;
            $this->percentage = $percentage;
        }
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setIndex(int $value): static
    {
        $this->index = $value;

        return $this;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return array{
     *      code: null|string,
     *      label: null|string,
     *      amount: null|int,
     *      currency: null|string,
     *      percentage: null|float,
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'amount' => $this->amount?->getMinorAmount()->toInt(),
            'currency' => $this->amount?->getCurrency()->getCurrencyCode(),
            'percentage' => $this->percentage,
        ];
    }

    /**
     * @return array{
     *      code: null|string,
     *      label: null|string,
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
     *      label: null|string,
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
     *      label: null|string,
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
            'reason' => $this->getLabel(),
            'code' => $this->code,
            ...$values,
        ], fn ($value) => filled($value));
    }
}
