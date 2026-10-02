<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Database\Factories;

use Brick\Money\Money;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition()
    {
        $currency = config()->string('invoices.default_currency');
        $unitPrice = Money::of(fake()->numberBetween(1, 1000), $currency);
        $quantity = fake()->numberBetween(1, 10);

        return [
            'label' => fake()->sentence(),
            'description' => fake()->sentence(),
            'unit_price' => $unitPrice->getMinorAmount()->toInt(),
            'currency' => $unitPrice->getCurrency()->getCurrencyCode(),
            'quantity' => $quantity,
        ];
    }
}
