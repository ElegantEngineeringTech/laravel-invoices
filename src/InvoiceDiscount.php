<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Elegantly\Invoices\Concerns\FormatForPdf;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
class InvoiceDiscount implements Arrayable, JsonSerializable
{
    use FormatForPdf;

    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?Money $amount_off = null,
        public ?float $percent_off = null,
    ) {
        // code...
    }

    public function computeDiscountAmountOn(Money $amout): Money
    {
        if ($this->amount_off) {
            return $this->amount_off;
        }

        if ($this->percent_off !== null) {
            return $amout->multipliedBy($this->percent_off / 100.0, RoundingMode::HALF_CEILING);
        }

        return Money::of(0, $amout->getCurrency());
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
}
