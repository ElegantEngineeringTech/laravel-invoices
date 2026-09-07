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
class InvoiceTax implements Arrayable, GOBLable, Jsonable, JsonSerializable
{
    /**
     * Used internally to identify items in aggregate
     */
    protected int $index;

    public ?string $type = null;

    public ?string $country = null;

    public ?string $taxability = null;

    public ?Money $amount = null;

    public ?float $percentage = null;

    public ?string $label = null;

    /**
     * @param  string|array{
     *      type?: null|string,
     *      country?: null|string,
     *      taxability?: null|string,
     *      amount?: null|int|Money,
     *      currency?: null|string,
     *      percentage?: null|float,
     *      label?: null|string,
     * }  $type
     */
    public function __construct(
        null|string|array $type,
        ?string $country = null,
        ?string $taxability = null,
        ?Money $amount = null,
        ?float $percentage = null,
        ?string $label = null,
    ) {
        if (is_array($type)) {

            $this->type = $type['type'] ?? null;
            $this->taxability = $type['taxability'] ?? null;
            $this->country = $type['country'] ?? null;
            $this->percentage = $type['percentage'] ?? null;
            $this->label = $type['label'] ?? null;

            $amount = $type['amount'] ?? null;
            $currency = $type['currency'] ?? null;

            if ($amount instanceof Money) {
                $this->amount = $amount;
            } elseif ($amount && $currency) {
                $this->amount = Money::ofMinor($amount, $currency);
            }

        } else {
            $this->type = $type;
            $this->country = $country;
            $this->taxability = $taxability;
            $this->amount = $amount;
            $this->percentage = $percentage;
            $this->label = $label;
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
     *      type: ?string,
     *      country: ?string,
     *      taxability: ?string,
     *      amount: ?int,
     *      currency: ?string,
     *      percentage: ?float,
     *      label: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'country' => $this->country,
            'taxability' => $this->taxability,
            'amount' => $this->amount?->getMinorAmount()->toInt(),
            'currency' => $this->amount?->getCurrency()->getCurrencyCode(),
            'percentage' => $this->percentage,
            'label' => $this->label,
        ];
    }

    /**
     * @return array{
     *      type: ?string,
     *      country: ?string,
     *      taxability: ?string,
     *      amount: ?int,
     *      currency: ?string,
     *      percentage: ?float,
     *      label: ?string,
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
     *      type: ?string,
     *      country: ?string,
     *      taxability: ?string,
     *      amount: ?int,
     *      currency: ?string,
     *      percentage: ?float,
     *      label: ?string,
     * }
     */
    public function toLivewire()
    {
        return $this->toArray();
    }

    /**
     * @param array{
     *      type: ?string,
     *      country: ?string,
     *      taxability: ?string,
     *      amount: ?int,
     *      currency: ?string,
     *      percentage: ?float,
     *      label: ?string,
     * } $value
     */
    // @phpstan-ignore-next-line
    public static function fromLivewire($value)
    {
        return new static($value);
    }

    /**
     * @see https://docs.gobl.org/draft-0/tax/combo
     */
    public function toGOBL(array $values = []): array
    {
        return array_filter([
            //
            ...$values,
        ], fn ($value) => filled($value));
    }
}
