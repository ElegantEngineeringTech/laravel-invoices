<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Math\RoundingMode;
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

    public ?string $name = null;

    /**
     * @param  null|string|array{name?: ?string, code?: ?string, amount_off?: null|int|Money, currency?: null|string, percent_off?: ?float}  $name
     */
    public function __construct(
        null|string|array $name = null,
        public ?string $code = null,
        public ?Money $amount_off = null,
        public ?float $percent_off = null,
    ) {
        if (is_array($name)) {
            $amount_off = $name['amount_off'] ?? null;

            if ($amount_off === null || $amount_off instanceof Money) {
                $this->amount_off = $amount_off;
            } else {
                $this->amount_off = Money::ofMinor($amount_off, $name['currency'] ?? config()->string('invoices.default_currency'));
            }

            $this->name = $name['name'] ?? null;
            $this->code = $name['code'] ?? null;
            $this->percent_off = $name['percent_off'] ?? null;

        } else {
            $this->name = $name;
        }
    }

    public function computeDiscountAmountOn(Money $amount): Money
    {
        if ($this->amount_off) {
            return $this->amount_off;
        }

        if ($this->percent_off !== null) {
            return $amount->multipliedBy(
                (string) ($this->percent_off / 100.0),
                // @phpstan-ignore-next-line
                config('invoices.rounding_mode', RoundingMode::HalfUp)
            );
        }

        return Money::of(0, $amount->getCurrency());
    }

    /**
     * @param  null|array{
     *      name: ?string,
     *      code: ?string,
     *      currency: ?string,
     *      amount_off: ?int,
     *      percent_off: ?float,
     * }  $array
     */
    public static function fromArray(?array $array): static
    {
        $currency = $array['currency'] ?? config()->string('invoices.default_currency');
        $amount_off = $array['amount_off'] ?? null;
        $percent_off = $array['percent_off'] ?? null;

        return new static(
            name: $array['name'] ?? '',
            code: $array['code'] ?? '',
            amount_off: $amount_off ? Money::ofMinor($amount_off, $currency) : null,
            percent_off: $percent_off ? (float) $percent_off : null
        );
    }

    /**
     * @return array{
     *      name: ?string,
     *      code: ?string,
     *      amount_off: ?int,
     *      currency: ?string,
     *      percent_off: ?float,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'amount_off' => $this->amount_off?->getMinorAmount()->toInt(),
            'currency' => $this->amount_off?->getCurrency()->getCurrencyCode(),
            'percent_off' => $this->percent_off,
        ];
    }

    /**
     * @return array{
     *      name: ?string,
     *      code: ?string,
     *      amount_off: ?int,
     *      currency: ?string,
     *      percent_off: ?float,
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
     *      name: ?string,
     *      code: ?string,
     *      amount_off: ?int,
     *      currency: ?string,
     *      percent_off: ?float,
     * }
     */
    public function toLivewire()
    {
        return $this->toArray();
    }

    /**
     * @param ?array{
     *      name: ?string,
     *      code: ?string,
     *      amount_off: ?int,
     *      currency: ?string,
     *      percent_off: ?float,
     * } $value
     */
    // @phpstan-ignore-next-line
    public static function fromLivewire($value)
    {
        return static::fromArray($value);
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
            'amount' => $this->amount_off?->getAmount()->toString(),
            'percent' => $this->percent_off ? "{$this->percent_off}%" : null,
            'reason' => $this->name,
            'code' => $this->code,
            ...$values,
        ], fn ($value) => filled($value));
    }
}
