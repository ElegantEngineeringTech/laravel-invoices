<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Money\Money;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
class InvoiceTax implements Arrayable, Jsonable, JsonSerializable
{
    use FormatForPdf;

    public ?string $type = null;

    public ?string $taxability = null;

    public ?Money $amount = null;

    public ?float $percentage = null;

    public ?string $label = null;

    /**
     * @param  string|array{
     *      type?: null|string,
     *      taxability?: null|string,
     *      amount?: null|int|Money,
     *      currency?: null|string,
     *      percentage?: null|float,
     *      label?: null|string,
     * }  $type
     */
    public function __construct(
        null|string|array $type,
        ?string $taxability = null,
        ?Money $amount = null,
        ?float $percentage = null,
        ?string $label = null,
    ) {
        if (is_array($type)) {

            $this->type = $type['type'] ?? null;
            $this->taxability = $type['taxability'] ?? null;
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
            $this->taxability = $taxability;
            $this->amount = $amount;
            $this->percentage = $percentage;
            $this->label = $label;
        }
    }

    /**
     * @return array{
     *      type: ?string,
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
}
