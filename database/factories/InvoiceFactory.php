<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Database\Factories;

use Carbon\CarbonImmutable;
use Elegantly\Invoices\Enums\InvoiceState;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Support\Address;
use Elegantly\Invoices\Support\Buyer;
use Elegantly\Invoices\Support\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        $created_at = fake()->dateTime(
            max: CarbonImmutable::create(2024, 12, 31)
        );

        return [
            'type' => InvoiceType::Invoice->value,
            'state' => InvoiceState::Draft->value,
            'state_set_at' => fake()->dateTimeBetween($created_at),
            'updated_at' => fake()->dateTimeBetween($created_at),
            'created_at' => $created_at,
            'due_at' => fake()->dateTimeBetween($created_at, '+ 30 days'),
            'description' => fake()->sentence(),
            // @phpstan-ignore-next-line
            'seller_information' => Seller::fromArray(config('invoices.default_seller')),
            'buyer_information' => new Buyer(
                name : fake()->company(),
                address : new Address(
                    street: fake()->streetName(),
                    city: fake()->city(),
                    postal_code : fake()->postcode(),
                    country: fake()->country(),
                ),
                email : fake()->email(),
                phone : fake()->phoneNumber(),
                tax_number : (string) fake()->numberBetween(12345678, 99999999),
            ),
        ];
    }

    public function quote(): static
    {
        return $this->state([
            'type' => InvoiceType::Quote,
        ]);
    }

    public function proforma(): static
    {
        return $this->state([
            'type' => InvoiceType::Proforma,
        ]);
    }

    public function invoice(): static
    {
        return $this->state([
            'type' => InvoiceType::Invoice,
        ]);
    }

    public function credit(): static
    {
        return $this->state([
            'type' => InvoiceType::Credit,
        ]);
    }
}
