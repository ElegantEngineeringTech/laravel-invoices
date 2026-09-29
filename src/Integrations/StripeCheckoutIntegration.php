<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Integrations;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\Eloquent\InvoiceItemCollection;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use LogicException;
use Stripe\Collection;
use Stripe\LineItem;
use Stripe\PromotionCode;

class StripeCheckoutIntegration
{
    /**
     * @param  Collection<LineItem>  $items
     */
    public function toInvoiceItemCollection(Collection $items): InvoiceItemCollection
    {

        $items = collect($items->data)->map(fn ($item) => $this->toInvoiceItem($item));

        return new InvoiceItemCollection($items);
    }

    public function toInvoiceItem(LineItem $item): InvoiceItem
    {

        if ($item->price === null) {
            throw new LogicException('Stripe LineItem price must be expanded using "expand[]=data.price".');
        }

        if ($item->discounts === null) {
            throw new LogicException('Stripe LineItem discounts must be expanded using "expand[]=data.discounts.discount.promotion_code".');
        }

        if ($item->taxes === null) {
            throw new LogicException('Stripe LineItem taxes must be expanded using "expand[]=data.taxes".');
        }

        $itemClass = InvoiceServiceProvider::getInvoiceItemClass();
        $taxClass = InvoiceServiceProvider::getInvoiceTaxClass();
        $discountClass = InvoiceServiceProvider::getInvoiceDiscountClass();

        $currency = mb_strtoupper($item->currency);

        return new $itemClass([
            'label' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->price->unit_amount ? Money::ofMinor($item->price->unit_amount, $currency) : null,
            'price_subtotal' => Money::ofMinor($item->amount_subtotal, $currency),
            'price_discount' => Money::ofMinor($item->amount_discount, $currency),
            'price_tax' => Money::ofMinor($item->amount_tax, $currency),
            'price' => Money::ofMinor($item->amount_total, $currency),
            'discounts' => new InvoiceDiscountCollection(array_map(
                fn ($discount) => new $discountClass([
                    'amount' => Money::ofMinor($discount->amount, $currency),
                    'code' => $discount->discount->promotion_code instanceof PromotionCode ? $discount->discount->promotion_code->code : null,
                ]),
                $item->discounts,
            )),
            'taxes' => new InvoiceTaxCollection(array_map(
                fn ($tax) => new $taxClass([
                    'type' => $tax->rate->tax_type ?? mb_strtolower($tax->rate->display_name),
                    'country' => $tax->rate->country,
                    'state' => $tax->rate->state,
                    'taxability' => $tax->taxability_reason ?? 'standard_rated',
                    'amount' => Money::ofMinor($tax->amount, $currency),
                    'percentage' => $tax->rate->effective_percentage,
                    'label' => $tax->rate->display_name,
                ]),
                $item->taxes,
            )),
        ]);
    }
}
