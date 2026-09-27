<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Integrations;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceServiceProvider;
use Elegantly\Invoices\Models\InvoiceItem;
use LogicException;
use Stripe\LineItem;
use Stripe\PromotionCode;

class StripeLineItemIntegration
{
    public function __construct(
        public readonly LineItem $item
    ) {
        //
    }

    public function toInvoiceItem(): InvoiceItem
    {

        if ($this->item->price === null) {
            throw new LogicException('Stripe LineItem price must be expanded using "expand[]=data.price".');
        }

        if ($this->item->discounts === null) {
            throw new LogicException('Stripe LineItem discounts must be expanded using "expand[]=data.discounts.discount.promotion_code".');
        }

        if ($this->item->taxes === null) {
            throw new LogicException('Stripe LineItem taxes must be expanded using "expand[]=data.taxes".');
        }

        $itemClass = InvoiceServiceProvider::getInvoiceItemClass();
        $taxClass = InvoiceServiceProvider::getInvoiceTaxClass();
        $discountClass = InvoiceServiceProvider::getInvoiceDiscountClass();

        $currency = mb_strtoupper($this->item->currency);

        return new $itemClass([
            'label' => $this->item->description,
            'quantity' => $this->item->quantity,
            'unit_price' => $this->item->price->unit_amount ? Money::ofMinor($this->item->price->unit_amount, $currency) : null,
            'price_subtotal' => Money::ofMinor($this->item->amount_subtotal, $currency),
            'price_discount' => Money::ofMinor($this->item->amount_discount, $currency),
            'price_tax' => Money::ofMinor($this->item->amount_tax, $currency),
            'price' => Money::ofMinor($this->item->amount_total, $currency),
            'discounts' => new InvoiceDiscountCollection(array_map(
                fn ($discount) => new $discountClass([
                    'amount' => Money::ofMinor($discount->amount, $currency),
                    'code' => $discount->discount->promotion_code instanceof PromotionCode ? $discount->discount->promotion_code->code : null,
                ]),
                $this->item->discounts,
            )),
            'taxes' => new InvoiceTaxCollection(array_map(
                fn ($tax) => new $taxClass([
                    'type' => $tax->rate->tax_type,
                    'country' => $tax->rate->country,
                    'state' => $tax->rate->state,
                    'taxability' => $tax->taxability_reason,
                    'amount' => Money::ofMinor($tax->amount, $currency),
                    'percentage' => $tax->rate->effective_percentage,
                    'label' => $tax->rate->display_name,
                ]),
                $this->item->taxes,
            )),
        ]);
    }
}
