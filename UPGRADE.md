# Upgrading from `v5` to `v6`

This guide covers the breaking changes and migration steps for upgrading from `v5` to `v6`.

The key change is **denormalized, item-level accounting**. Discounts and taxes now belong to items. Line amounts and invoice totals are explicit values: you can supply them yourself or call `denormalize()` to calculate missing values.

## Table of Contents

- [Upgrade Order](#upgrade-order)
- [Requirements and Dependencies](#requirements-and-dependencies)
- [Configuration](#configuration)
    - [Date Formats](#date-formats)
    - [PDF Settings](#pdf-settings)
    - [Custom Classes](#custom-classes)
- [Database Migrations](#database-migrations)
    - [Publishing and Running Migrations](#publishing-and-running-migrations)
    - [What the Data Migrations Convert](#what-the-data-migrations-convert)
    - [Historical Amounts and Rounding](#historical-amounts-and-rounding)
    - [Custom Schemas and Legacy Data](#custom-schemas-and-legacy-data)
    - [Rollback](#rollback)
- [Migrating `PdfInvoice`](#migrating-pdfinvoice)
    - [Before: Invoice-Level Discounts and Item Tax Properties](#before-invoice-level-discounts-and-item-tax-properties)
    - [After: Item Collections and Denormalization](#after-item-collections-and-denormalization)
    - [Replacing Calculation Methods](#replacing-calculation-methods)
    - [Fixed Taxes and Currency](#fixed-taxes-and-currency)
    - [Invoice-Wide Discounts](#invoice-wide-discounts)
    - [Precomputed PDF Amounts](#precomputed-pdf-amounts)
- [Migrating the `Invoice` Model](#migrating-the-invoice-model)
    - [Creating and Saving Invoices](#creating-and-saving-invoices)
    - [Updating Existing Amounts](#updating-existing-amounts)
    - [Storing Precomputed Amounts](#storing-precomputed-amounts)
    - [Custom PDF Conversion and Removed Methods](#custom-pdf-conversion-and-removed-methods)
    - [Custom Models and Collections](#custom-models-and-collections)
    - [Metadata, IDs, and Quantities](#metadata-ids-and-quantities)
    - [Credit Relations and Replication](#credit-relations-and-replication)
    - [Serial Numbers](#serial-numbers)
- [Custom Views and Translations](#custom-views-and-translations)
    - [Formatting Helpers and Amount Properties](#formatting-helpers-and-amount-properties)
    - [Translation Keys](#translation-keys)
    - [Layout, Header, Footer, and Payment Instructions](#layout-header-footer-and-payment-instructions)
- [Serialized Discounts and Integrations](#serialized-discounts-and-integrations)
    - [Discount Fields and Constructors](#discount-fields-and-constructors)
    - [Legacy JSON, Jobs, and Livewire State](#legacy-json-jobs-and-livewire-state)
    - [GOBL Export](#gobl-export)
    - [Stripe Checkout](#stripe-checkout)
- [Deployment and Verification](#deployment-and-verification)

## Upgrade Order

1. On `v5`, capture historical amounts and any PDFs you need to compare. Back up the database: the upgrade removes columns and rewrites amounts.
2. Update PHP and package dependencies in your development environment.
3. Update application code, custom models, configuration, templates, and translations using the sections below.
4. Publish the new migrations and run them against a copy of your existing database.
5. Compare historical invoices and resolve any differences before deploying.
6. Deploy the updated code and data migrations together. Restart workers before processing invoices again.

If you use only `PdfInvoice`, skip the database migrations and Eloquent sections.

## Requirements and Dependencies

| Dependency | `v5` | `v6` |
| --- | --- | --- |
| PHP | `^8.3` | `^8.4` |
| Laravel / `illuminate/contracts` | `^13.0` | `^13.0` |
| `dompdf/dompdf` | `^3.1` | `^3.1` |
| `elegantly/laravel-money` | `^4.0.0` | `^4.2.0` |

Update the package in your application:

```bash
composer require elegantly/laravel-invoices:"^6.0" --with-all-dependencies
```

The optional Stripe integration requires `stripe/stripe-php`. Applications do not need to adopt the package repository's Pest 5 or Testbench changes unless they use those tools themselves.

## Configuration

Merge changes from [config/invoices.php](config/invoices.php) into your application's published configuration. Publishing without `--force` leaves an existing config file intact.

### Date Formats

The default template now uses Carbon's `isoFormat()` instead of PHP-style `format()` tokens.

| Before | After |
| --- | --- |
| `Y-m-d` | `YYYY-MM-DD` |
| `d/m/Y` | `DD/MM/YYYY` |
| `M j, Y` | `MMM D, YYYY` |

Update `invoices.date_format` and any custom template that reads it:

```blade
{{ $invoice->created_at?->isoFormat(config('invoices.date_format')) }}
```

### PDF Settings

Move settings that still use the removed fallbacks:

| Legacy setting | Current setting |
| --- | --- |
| `invoices.pdf_options` | `invoices.pdf.options` |
| `invoices.paper_options.paper` | `invoices.pdf.paper.size` |
| `invoices.pdf.paper.paper` | `invoices.pdf.paper.size` |
| `invoices.paper_options.orientation` | `invoices.pdf.paper.orientation` |

The default `fontHeightRatio` changes from `0.8` to `1.1`. Review spacing in your PDFs if you adopt the new default.

The default layout no longer uses `pdf.template_data.color`. To retain a colored header, add it to your published header partial; see [Layout, Header, Footer, and Payment Instructions](#layout-header-footer-and-payment-instructions).

### Custom Classes

Two settings are added:

```php
// Merge these entries into config/invoices.php.
return [
    'tax_class' => \Elegantly\Invoices\InvoiceTax::class,
    'payment_instructions_class' => \Elegantly\Invoices\Support\PaymentInstruction::class,
];
```

Existing model, party, address, identity, tax ID, and discount class settings remain available. If you replace the discount class, update its constructor and renamed properties before configuring it.

## Database Migrations

### Publishing and Running Migrations

Keep your already-published migrations and their original timestamps. Publish the new migrations without overwriting the old files:

```bash
php artisan vendor:publish --tag="invoices-migrations"
php artisan migrate:status
```

The seven new migrations must run in this order, after all `v5` migrations:

| Order | Migration | Purpose |
| --- | --- | --- |
| 1 | `drop_unit_discount_column_in_invoice_items_table` | Drops `unit_discount` and `discount_percentage`. |
| 2 | `add_price_column_to_invoice_items_table` | Adds `price_subtotal`, `price_discount`, `price_tax`, and `price`. |
| 3 | `add_discounts_to_invoice_items_table` | Adds item `discounts` JSON. |
| 4 | `add_taxes_to_invoice_items_table` | Adds item `taxes` JSON. |
| 5 | `migrate_discounts_to_invoice_items_table` | Copies invoice discounts into item breakdowns. |
| 6 | `migrate_taxes_to_invoice_items_table` | Copies old tax properties into item breakdowns. |
| 7 | `migrate_prices_columns_to_invoice_items_table` | Calculates line amounts and rewrites invoice totals. |

Review the generated timestamps, especially if you publish migrations individually or have renamed older package migrations. Then run:

```bash
php artisan migrate
```

The package discovers existing migration files by their filename suffix. If you removed or renamed previously published files, publishing can create copies that Laravel considers new migrations; reconcile these with your migration history before running them.

### What the Data Migrations Convert

**Discounts:** the migration reads `invoices.discounts` and writes `invoice_items.discounts`:

- `name` becomes `label`.
- `percent_off` becomes `percentage`.
- Fixed `amount_off` is allocated across items using their absolute `unit_price × quantity` as weights and `AllocationMode::FloorToLargestRemainder`.
- Percentage-only discounts are copied to every item, then calculated sequentially by the price migration.
- If all item weights are zero, fixed discount allocations are zero.

**Taxes:** for items with `unit_tax` or `tax_percentage`, the migration writes `invoice_items.taxes`:

- Fixed `amount` is `unit_tax × quantity`.
- `tax_percentage` becomes `percentage`.
- Invoice `tax_type` is copied to tax `type`.
- Invoice `tax_exempt` is copied to `taxability`, falling back to `standard_rated`.
- Type strings are copied as-is; country, state, and custom tax labels are not inferred.

**Amounts:** the final migration calculates the four line amounts and replaces the invoice's `subtotal_amount`, `discount_amount`, `tax_amount`, `total_amount`, and `currency` with their sums.

Only items with both `unit_price` and `currency` are included in that final calculation. An invoice with no qualifying items gets `null` totals and currency.

The legacy invoice `discounts`, `tax_type`, and `tax_exempt` columns, and item `unit_tax` and `tax_percentage` columns, remain in the database. They are no longer used by the package's accounting or PDF conversion. Writing to them does not update the new breakdowns.

### Historical Amounts and Rounding

The final data migration **overwrites existing totals**, even if they were precomputed or already populated. The non-overwriting behavior of runtime `denormalize()` does not apply to this migration.

Compare these cases against your historical records:

| Behavior | `v5` | `v6` |
| --- | --- | --- |
| Multiple percentage discounts | Each uses the original invoice subtotal. | Each uses the line subtotal remaining after previous discounts. |
| Percentage discount rounding | Discount calculated at invoice level. | Discount calculated and rounded for each item. |
| Tax discount allocation | Invoice discount allocated with `FloorToFirst`. | Tax uses each item's own discounted subtotal. |
| Fixed discount allocation during upgrade | Previously calculated at invoice level. | Migration uses `FloorToLargestRemainder`. |
| Migration rounding mode | — | Tax and price data migrations explicitly use `HalfUp`, regardless of `invoices.rounding_mode`. |

For example, on a EUR 100.00 subtotal, two percentage discounts of 10% and 5% previously removed EUR 15.00. Sequential discounts remove EUR 14.50. Taxes then use the new discounted amount.

If historical amounts must remain exact, adapt the published data migrations to populate amounts from the snapshots captured on `v5`. Preserve both line breakdowns and invoice totals; restoring only the invoice total can leave PDF summaries inconsistent. The price migration must not replace those restored amounts afterward.

Where you only have historical invoice totals, the new item collection's `allocate()` can distribute them across populated line breakdowns. Choose the allocation rules that match the original records; see the [allocation example](README.md#replicating-scaling-and-allocating-amounts).

### Custom Schemas and Legacy Data

Review these cases before running the conversion:

- **Custom item discounts:** `unit_discount` and `discount_percentage` are dropped without conversion. The core `v5` accounting did not consume them. If your application did, preserve and convert them in your own migration. Also adapt the invoice-discount migration, which initializes item discount arrays and would otherwise replace custom entries.
- **Custom table names or connections:** the data migrations use `DB::table('invoices')` and `DB::table('invoice_items')` on the default connection. Model configuration does not change these queries. Adapt published migrations for tenant databases or renamed tables.
- **Sparse or custom JSON:** the conversion expects the legacy fields emitted by `InvoiceDiscount::toArray()`. Normalize custom payloads with missing keys, missing fixed-amount currencies, or a different schema before conversion.
- **Missing prices/currencies:** backfill from your authoritative data or provide explicit new amounts through a custom migration; skipped lines do not contribute to the rewritten totals.
- **Custom tax metadata:** apply country, state, label, and other extensions after the package conversions, or adapt the final migration's tax serialization to preserve them.
- **Existing `v6` columns:** if your application already added any of the new columns, reconcile the schema migrations before running them.

The updated historical `create_invoice_items_table` stub uses `jsonb` for `metadata`. This does not alter an existing application's metadata column; no conversion of existing metadata JSON is needed for the package upgrade.

### Rollback

The three data migrations have empty `down()` methods. Rolling them back does not restore the old totals or reverse the breakdown conversion. Rolling back the drop migration recreates empty discount columns, not their former values.

To return to `v5` with the original records, restore the pre-upgrade database backup and the matching application code/dependencies. A code-only rollback is insufficient after these migrations.

## Migrating `PdfInvoice`

### Before: Invoice-Level Discounts and Item Tax Properties

On `v5`, items were arrays, discounts belonged to the invoice, and amounts were calculated when requested:

```php
namespace App\Http\Controllers;

use Brick\Money\Money;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Invoices\Support\Party;

class InvoiceController extends Controller
{
    public function show()
    {
        $invoice = new PdfInvoice(
            serial_number: 'INV-260001',
            seller: Party::fromArray(config('invoices.default_seller')),
            buyer: new Party(company: 'Doe Corporation'),
            items: [
                new PdfInvoiceItem(
                    label: 'Consulting',
                    unit_price: Money::of('100.00', 'EUR'),
                    quantity: 2,
                    tax_percentage: 20,
                ),
            ],
            discounts: [
                new InvoiceDiscount(name: 'Welcome offer', code: 'WELCOME', percent_off: 10),
            ],
            tax_label: 'VAT France (20%)',
        );

        return $invoice->stream();
    }
}
```

### After: Item Collections and Denormalization

On `v6`, put discounts and taxes on the item and calculate amounts explicitly:

```php
namespace App\Http\Controllers;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;
use Elegantly\Invoices\Support\Party;

class InvoiceController extends Controller
{
    public function show()
    {
        $invoice = new PdfInvoice(
            serial_number: 'INV-260001',
            seller: Party::fromArray(config('invoices.default_seller')),
            buyer: new Party(company: 'Doe Corporation'),
            items: new PdfInvoiceItemCollection([
                new PdfInvoiceItem(
                    label: 'Consulting',
                    unit_price: Money::of('100.00', 'EUR'),
                    quantity: 2,
                    discounts: new InvoiceDiscountCollection([
                        new InvoiceDiscount(code: 'WELCOME', label: 'Welcome offer', percentage: 10),
                    ]),
                    taxes: new InvoiceTaxCollection([
                        new InvoiceTax(type: 'vat', country: 'FR', percentage: 20, label: 'VAT France (20%)'),
                    ]),
                ),
            ]),
        );

        $invoice->denormalize();

        // Subtotal: EUR 200.00; discount: EUR 20.00;
        // tax: EUR 36.00; total: EUR 216.00.
        return $invoice->stream();
    }
}
```

`PdfInvoice::make()` and `PdfInvoiceItem::make()` can build the same structures from nested arrays. See the [array example](README.md#creating-pdfs-from-arrays).

### Replacing Calculation Methods

Replace calls on PDFs and custom templates:

| Removed call | Replacement after denormalization |
| --- | --- |
| `$invoice->subTotalAmount()` | `$invoice->subtotal_amount` |
| `$invoice->totalDiscountAmount()` | `$invoice->discount_amount` |
| `$invoice->subTotalDiscountedAmount()` | `$invoice->subtotal_amount->minus($invoice->discount_amount)` |
| `$invoice->totalTaxAmount()` | `$invoice->tax_amount` |
| `$invoice->totalAmount()` | `$invoice->total_amount` |
| `$item->subTotalAmount()` | `$item->price_subtotal` |
| `$item->totalTaxAmount()` | `$item->price_tax` |
| `$item->totalAmount()` | `$item->price` |
| `$invoice->getCurrency()` | Read the currency from an amount, for example `$invoice->total_amount?->getCurrency()->getCurrencyCode()`. |

Rendering, downloading, generating attachment data, and `view()` do not calculate missing amounts. Call `denormalize()` or supply amounts before any of these operations. Without source prices or amounts, values can remain `null` instead of the old computed zero.

Direct constructors have changed argument order. Update positional constructor calls as well as named arguments, especially custom subclasses forwarding arguments to `PdfInvoice`, `PdfInvoiceItem`, or `InvoiceDiscount`.

### Fixed Taxes and Currency

`unit_tax` is replaced by a tax entry's **whole-line amount**. Multiply the old per-unit tax by the quantity:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

$quantity = 2;
$oldUnitTax = Money::of('20.00', 'EUR');

$item = new PdfInvoiceItem(
    label: 'Consulting',
    unit_price: Money::of('100.00', 'EUR'),
    quantity: $quantity,
    taxes: new InvoiceTaxCollection([
        new InvoiceTax(label: 'Fixed tax', amount: $oldUnitTax->multipliedBy($quantity)),
    ]),
);
$item->denormalize();
// price_tax is EUR 40.00.
```

The `currency` constructor argument and property are removed from `PdfInvoiceItem`. Pass currency through its `Money` values, or through `currency` when using `make()`. Use the same currency across all amounts being summed.

The old constructor's 0–100 check for `tax_percentage` is also removed. If your application relies on that validation, apply it to the new `percentage` input before constructing taxes.

If you supply both a fixed tax amount and a percentage, normal denormalization preserves the amount. Forced denormalization recalculates from the percentage. To keep a permanently fixed tax, leave its percentage unset.

### Invoice-Wide Discounts

The invoice no longer has a `discounts` constructor argument or property. For a percentage promotion, add a separate discount entry to every applicable item. For a fixed invoice discount, allocate its amount across lines:

```php
use Brick\Money\AllocationMode;
use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

$items = new PdfInvoiceItemCollection([
    new PdfInvoiceItem(label: 'Consulting', unit_price: Money::of('100.00', 'EUR')),
    new PdfInvoiceItem(label: 'Support', unit_price: Money::of('50.00', 'EUR')),
]);
$items->denormalize();

$shares = Money::of('15.00', 'EUR')->allocate(
    $items->map(fn ($item) => $item->price_subtotal->abs()->getMinorAmount()->toInt())->all(),
    AllocationMode::FloorToFirst,
);

foreach ($items as $index => $item) {
    $item->discounts = new InvoiceDiscountCollection([
        new InvoiceDiscount(code: 'WELCOME', amount: $shares[$index]),
    ]);
}

$invoice = new PdfInvoice(serial_number: 'INV-260002', items: $items);
$invoice->denormalize(force: true);
// discount_amount is EUR 15.00; total_amount is EUR 135.00.
```

The forced call refreshes line totals already populated before adding discounts. Allocate only when the item weights have a nonzero total.

`getDiscounts()` now returns grouped item discounts, not an invoice-level array. Groups use code and percentage; the default grouped entry does not copy the individual `label`. Customize the summary template/grouping if historical discount names must appear there.

### Precomputed PDF Amounts

When amounts come from another system, pass line and invoice amounts directly:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Collections\PdfInvoiceItemCollection;
use Elegantly\Invoices\Pdf\PdfInvoice;
use Elegantly\Invoices\Pdf\PdfInvoiceItem;

$invoice = new PdfInvoice(
    serial_number: 'INV-260003',
    items: new PdfInvoiceItemCollection([
        new PdfInvoiceItem(
            label: 'Consulting',
            quantity: 2,
            unit_price: Money::of('100.00', 'EUR'),
            price_subtotal: Money::of('200.00', 'EUR'),
            price_discount: Money::zero('EUR'),
            price_tax: Money::zero('EUR'),
            price: Money::of('200.00', 'EUR'),
        ),
    ]),
    subtotal_amount: Money::of('200.00', 'EUR'),
    discount_amount: Money::zero('EUR'),
    tax_amount: Money::zero('EUR'),
    total_amount: Money::of('200.00', 'EUR'),
);

return $invoice->download();
```

Normal `denormalize()` fills only `null` fields. When `price_discount` or `price_tax` is already set, it also skips the corresponding nested collection. Include precomputed breakdown entries if you want them displayed. `force: true` replaces derived values using the available prices, quantities, percentages, and fixed amounts.

## Migrating the `Invoice` Model

### Creating and Saving Invoices

Move discounts from the invoice to its items, and replace item `unit_tax` / `tax_percentage` with tax entries. Calculate invoice totals before saving:

```php
namespace App\Actions;

use Brick\Money\Money;
use Elegantly\Invoices\Collections\InvoiceDiscountCollection;
use Elegantly\Invoices\Collections\InvoiceTaxCollection;
use Elegantly\Invoices\InvoiceDiscount;
use Elegantly\Invoices\InvoiceTax;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class CreateInvoice
{
    public function handle(array $buyerInformation): Invoice
    {
        $invoice = new Invoice([
            'seller_information' => config('invoices.default_seller'),
            'buyer_information' => $buyerInformation,
            'state' => 'pending',
            'due_at' => now()->addDays(30),
        ]);

        $invoice->setItems([
            new InvoiceItem([
                'label' => 'Consulting',
                'unit_price' => Money::of('100.00', 'EUR'),
                'quantity' => 2,
                'discounts' => new InvoiceDiscountCollection([
                    new InvoiceDiscount(code: 'WELCOME', percentage: 10),
                ]),
                'taxes' => new InvoiceTaxCollection([
                    new InvoiceTax(type: 'vat', country: 'FR', percentage: 20),
                ]),
            ]),
        ]);

        return DB::transaction(fn () => $invoice->denormalize()->saveWithItems());
    }
}
```

Items fill missing amounts on creation and update. **Saving an invoice no longer denormalizes its monetary totals automatically.** `saveWithItems()` saves both records but does not start a transaction itself.

`setItems()` and `addItems()` modify the loaded relation. They do not delete existing database rows when replacing a collection; remove unwanted rows through `items()`.

### Updating Existing Amounts

On `v5`, updating an invoice recalculated its totals. On `v6`, populated amounts are preserved, so edits to source data need an explicit forced recalculation:

```php
namespace App\Actions;

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceQuantity
{
    public function handle(Invoice $invoice, int $itemId, int $quantity): Invoice
    {
        $invoice->load('items');
        $item = $invoice->items->firstWhere('id', $itemId);
        abort_unless($item, 404);
        $item->quantity = $quantity;

        return DB::transaction(fn () => $invoice->denormalize(force: true)->saveWithItems());
    }
}
```

The Artisan command follows the same distinction and now saves items as well as invoices:

```bash
# Fill missing amounts, preserving existing amounts.
php artisan invoices:denormalize

# Recalculate selected invoices and their items.
php artisan invoices:denormalize 1 2 3 --force
```

Unlike the old command's quiet save, this command uses normal save events. Review custom observers that send messages or perform other work on invoice/item updates. Do not use `--force` as a routine upgrade step when historical amounts must remain authoritative.

### Storing Precomputed Amounts

Assign all line and invoice amounts to preserve an imported snapshot:

```php
use Brick\Money\Money;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;

$invoice = new Invoice([
    'subtotal_amount' => Money::of('200.00', 'EUR'),
    'discount_amount' => Money::zero('EUR'),
    'tax_amount' => Money::zero('EUR'),
    'total_amount' => Money::of('200.00', 'EUR'),
]);
$invoice->setItems([
    new InvoiceItem([
        'label' => 'Consulting',
        'quantity' => 2,
        'unit_price' => Money::of('100.00', 'EUR'),
        'price_subtotal' => Money::of('200.00', 'EUR'),
        'price_discount' => Money::zero('EUR'),
        'price_tax' => Money::zero('EUR'),
        'price' => Money::of('200.00', 'EUR'),
    ]),
]);
$invoice->saveWithItems();
```

Normal save events preserve supplied amounts. As with PDFs, include discount and tax entries when you need breakdowns. Keep the same currency across the invoice and its items; raw database amounts and breakdown JSON remain in minor units.

### Custom PDF Conversion and Removed Methods

Update overrides of `toPdfInvoice()` that construct their own PDF. Prefer starting from the parent's conversion so stored totals and typed collections are included:

```php
namespace App\Models;

use Elegantly\Invoices\Pdf\PdfInvoice;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    public function toPdfInvoice(): PdfInvoice
    {
        $pdfInvoice = parent::toPdfInvoice();
        $pdfInvoice->template = 'invoices::company.layout';
        $pdfInvoice->fields['Support'] = 'billing@example.com';

        return $pdfInvoice;
    }
}
```

| Removed model API | Migration |
| --- | --- |
| `getDiscounts()` | Read item `discounts`, or use `toPdfInvoice()->getDiscounts()` for grouped summaries. |
| `getTaxLabel()` | Set `label` on each applicable `InvoiceTax`, or customize the PDF summary. |
| `parseSerialNumber()` | Use `SerialNumberGenerator::parse()`; see [Serial Numbers](#serial-numbers). |
| `credit()` / `$invoice->credit` | Use `credits()` / `$invoice->credits`. |

The parent `toPdfInvoice()` copies stored amounts; it does not denormalize them. Existing stream/download/mail attachment calls therefore depend on the new data being populated first.

### Custom Models and Collections

Merge custom casts with `parent::casts()` so the new amount and breakdown casts remain active. Remove overrides that still cast invoice-level `discounts`, item `unit_tax`, or item `tax_percentage` as package accounting fields.

If you override `denormalize()`, match the new signature and forward the force flag:

```php
namespace App\Models;

class Invoice extends \Elegantly\Invoices\Models\Invoice
{
    public function denormalize(bool $force = false): static
    {
        return parent::denormalize($force);
    }
}
```

Do the same for custom `InvoiceItem` or PDF subclasses. Custom `booted()` implementations must call `parent::booted()` if they should retain serial-number generation and item denormalization events.

Invoices now use `InvoiceCollection`, and items use `InvoiceItemCollection`. If you override `newCollection()` or use `#[CollectedBy]`, extend the package collection instead of replacing it with a plain collection:

```php
namespace App\Collections;

class InvoiceItemCollection extends \Elegantly\Invoices\Collections\Eloquent\InvoiceItemCollection
{
    // Your application helpers.
}
```

The invoice model calls collection methods such as `denormalize()` and `toPdfItems()`, so custom item collections must retain them.

Enum casts still require `getType()` and `getState()` to return the cast enum for PDF conversion; use the [contextual enum example](README.md#casting-types-and-states-to-enums).

### Metadata, IDs, and Quantities

Item `metadata` changes from `AsArrayObject` to an ordinary array cast. Replace in-place offset mutations with reassignment:

**Before:**

```php
$item->metadata['external_id'] = 'line_123';
$item->save();
```

**After:**

```php
$item->metadata = [
    ...($item->metadata ?? []),
    'external_id' => 'line_123',
];
$item->save();
```

`InvoiceItem` now guards `id` against mass assignment. Update importers that intentionally assigned primary keys via `create()` or `fill()` to assign the ID explicitly.

New items default to quantity `1` and empty discount/tax collections. If your custom schema allows null quantities, PDF conversion now maps a falsy quantity to `0`, whereas the old null fallback was `1`; normalize that data if it should represent one unit.

### Credit Relations and Replication

Replace the removed singular relation in queries and eager loads:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = Invoice::with('credits')->findOrFail($invoiceId);
$firstCredit = $invoice->credits->first();
```

`replicate()` now excludes serial-number attributes. Configure the new document's numbering and replicate its items separately:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = Invoice::with('items')->findOrFail($invoiceId);
$credit = $invoice->replicate();
$credit->type = 'credit';
$credit->configureSerialNumber(format: 'PPP-YYCCCC', prefix: 'CRE');
$credit->parent()->associate($invoice);
$credit->setItems($invoice->items->replicate());
$credit->multiplyBy(-1)->saveWithItems();
```

### Serial Numbers

Numbering still uses prefix, series, year, and month scopes. Update these changed behaviors:

- `configureSerialNumber()` without `format` now uses configuration, rather than retaining the model's existing format. Pass the stored format explicitly when that is your intent.
- `denormalizeSerialNumber()` requires both a serial and format; it no longer fills the format from configuration automatically.
- Saving an existing invoice with a blank serial can now generate one when `auto_generate` is enabled.
- Custom overrides of prefix/format methods must match their widened `BackedEnum` signatures. A missing prefix token now throws `LogicException` rather than a plain `Exception`.

For a manual serial, set the value and format together:

```php
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\SerialNumberGenerator;

$invoice = new Invoice;
$invoice->setSerialNumber('INV-260001', 'PPP-YYCCCC')->save();

// Replaces $invoice->parseSerialNumber().
$parts = new SerialNumberGenerator($invoice->serial_number_format)
    ->parse($invoice->serial_number);
```

## Custom Views and Translations

Published views and translations are not replaced automatically. Merge your customizations with the files under `resources/views/default/` and `resources/lang/` in the package. For applications using unmodified published files, republish them:

```bash
php artisan vendor:publish --tag="invoices-views" --force
php artisan vendor:publish --tag="invoices-translations" --force
```

### Formatting Helpers and Amount Properties

`Elegantly\Invoices\Concerns\FormatForPdf` is removed, including `formatMoney()` and `formatPercentage()` on PDFs, items, and discounts.

**Before:**

```blade
{{ $invoice->formatMoney($invoice->totalAmount()) }}
{{ $discount->formatPercentage($discount->percent_off) }}
```

**After:**

```blade
@php
    use Illuminate\Support\Number;
    use function Elegantly\Invoices\format_money;
@endphp

{{ format_money($invoice->total_amount) }}
{{ Number::percentage($discount->percentage, locale: app()->getLocale()) }}
```

`format_money()` normalizes nonbreaking spaces to ordinary spaces, so formatted string snapshots may need updating. `Number::percentage()` has its own precision and locale behavior; specify those options where you need to match an existing display.

Custom templates should loop over `$invoice->getDiscounts()` and `$invoice->getTaxes()` instead of `$invoice->discounts` or `tax_label`. Item amounts come from `price_subtotal`, `price_discount`, `price_tax`, and `price`.

### Translation Keys

PDF labels move under `invoices::invoice.pdf`. Type and state labels remain under `types` and `states`.

| Old suffix after `invoices::invoice.` | New suffix |
| --- | --- |
| `invoice` | `types.invoice` |
| `serial_number`, `created_at`, `due_at`, `paid_at` | `pdf.serial_number`, `pdf.created_at`, `pdf.due_at`, `pdf.paid_at` |
| `description` for invoice notes | `pdf.description` |
| `description` for the item heading | `pdf.items.label` |
| `from`, `to`, `shipping_to`, `page` | `pdf.from`, `pdf.to`, `pdf.shipping_to`, `pdf.page` |
| `quantity`, `unit_price`, `amount` | `pdf.items.quantity`, `pdf.items.unit_price`, `pdf.items.amount` |
| `tax` for the item heading | `pdf.items.tax` |
| `tax_label` / summary `tax` | `pdf.summary.tax` |
| `discount_name` | `pdf.summary.discount` |
| `subtotal_amount` | `pdf.summary.subtotal` |
| `subtotal_discounted_amount` | `pdf.summary.discounted` |
| `total_amount` | `pdf.summary.total` |

The new item discount heading is `pdf.items.discount`. Update application translation overrides and assertions that reference the old keys.

### Layout, Header, Footer, and Payment Instructions

The default layout now uses dedicated partials:

- `invoices::default.includes.header`
- `invoices::default.includes.footer`
- `invoices::default.includes.payment-instructions`
- `invoices::default.includes.fields`
- `invoices::default.includes.indicator`

The header partial is empty by default. To preserve the previous accent strip, put this in `resources/views/vendor/invoices/default/includes/header.blade.php`:

```blade
<div class="h-2 w-full"
     style="background-color: {{ data_get($invoice->templateData, 'color', '#050038') }}">
</div>
```

Merge the new CSS with custom styles, and review pagination and footer spacing. For custom discount/tax indicators, call `getDiscounts()` / `getTaxes()` before reading entry indexes; grouping assigns those indexes.

Associative custom fields still work. The new field partials also support ordered `['key' => ..., 'value' => ...]` entries, so field rendering can now be shared across header, party, and address templates.

## Serialized Discounts and Integrations

### Discount Fields and Constructors

| `v5` | `v6` |
| --- | --- |
| `name` | `label` |
| `percent_off` | `percentage` |
| `amount_off` | `amount` |
| `code` | `code` |
| `InvoiceDiscount::fromArray($data)` | `new InvoiceDiscount($data)` using the new keys. |
| `computeDiscountAmountOn($amount)` | Attach to an item and denormalize, then read the entry's `amount`. |

The first constructor argument changes from `name` to `code`. Update positional calls. Both fixed and percentage amounts are preserved by normal denormalization; with `force: true`, a percentage recalculates its amount. The old fixed-amount precedence therefore no longer applies during forced recalculation.

Serialized discounts now include `amount_subtotal`, the base used to calculate a discount. Percentages are rounded to two decimal places. Tax entries have their own `amount` and optional `amount_taxable`; percentage tax calculations use the discounted line subtotal, not `amount_taxable`.

### Legacy JSON, Jobs, and Livewire State

The package migrations convert database invoice discounts, not copies stored in your application's API payloads, caches, session data, or queued jobs. Update those producers and consumers to the new keys.

Update API resources and client models for item `discounts`, `taxes`, and line amounts. Because the legacy database columns remain but their casts are removed, generic Eloquent JSON responses may still expose old discounts as a raw JSON string or old taxes as raw database values. Hide or map those fields explicitly rather than treating them as the new accounting data.

Use an explicit adapter when importing old discount JSON:

```php
namespace App\Support;

use Brick\Money\Money;
use Elegantly\Invoices\InvoiceDiscount;

class LegacyDiscountImporter
{
    public function convert(array $data): InvoiceDiscount
    {
        $currency = $data['currency'] ?? config('invoices.default_currency');

        return new InvoiceDiscount(
            code: $data['code'] ?? null,
            label: $data['name'] ?? null,
            percentage: isset($data['percent_off']) ? (float) $data['percent_off'] : null,
            amount: isset($data['amount_off'])
                ? Money::ofMinor($data['amount_off'], $currency)
                : null,
        );
    }
}
```

Attach the imported result to an item; an old fixed invoice discount still needs allocation across its items.

Discount/tax array constructors and their JSON serialization use **minor units**. Numeric values inside `PdfInvoice::make()` or `PdfInvoiceItem::make()` use **major units**, including nested discounts and taxes. Convert serialized minor-unit values to `Money` before passing them through `make()`; otherwise, an amount such as `1980` becomes EUR 1,980.00 rather than EUR 19.80.

`InvoiceDiscount::fromLivewire()` now expects the new serialized keys. Update Livewire form bindings such as `percent_off` → `percentage`, and refresh old state when deploying the new schema. Drain or migrate jobs carrying old discount/PDF objects before restarting workers with the new classes.

### GOBL Export

Item `toGOBL()` now includes discounts and taxes. Nested item overrides use replacement semantics rather than the old recursive merge, so overriding `item.price` produces a single value instead of combining values.

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = Invoice::with(['items', 'parent'])->findOrFail($invoiceId);
$gobl = $invoice->toGOBL();
$line = $invoice->items->first()->toGOBL([
    'item' => ['ref' => 'SKU-001'],
]);
```

Update export fixtures for the added breakdowns and discount `base` field. `Invoice::toGOBL()` is new and uses the stored invoice snapshot.

### Stripe Checkout

The new integration imports precomputed line amounts and breakdowns. Existing custom importers should populate the new fields instead of legacy invoice discounts or item tax properties. Normal denormalization preserves imported amounts; forced recalculation can replace provider rounding.

See the [Stripe Checkout example](README.md#importing-stripe-checkout-line-items) for the required expansions and mapping.

## Deployment and Verification

Before processing invoices on the upgraded deployment:

1. Finish outstanding legacy jobs and stop invoice writers while schema and data conversion run. Laravel maintenance mode alone does not stop queue workers.
2. Install the updated dependencies, deploy compatible code/configuration/views, and run the reviewed migrations.
3. Compare pre-upgrade snapshots with converted records, especially multiple discounts, fixed taxes, credits, mixed tax rates, missing prices, and non-default currencies.
4. Confirm line amounts sum to invoice totals and discounts/taxes reconcile with their breakdowns.
5. Test creating an invoice, editing quantity with forced recalculation, preserving imported amounts, PDF previews/downloads, mail attachments, and serial-number generation.
6. Rebuild application caches and restart workers using your application's deployment process, then resume invoice processing.

An optional read-only reconciliation in Tinker or an application command:

```php
use Elegantly\Invoices\Models\Invoice;

$invoice = Invoice::with('items')->findOrFail($invoiceId);

$reconciled = [
    'subtotal' => $invoice->subtotal_amount?->isEqualTo($invoice->items->sumMoney('price_subtotal')),
    'discount' => $invoice->discount_amount?->isEqualTo($invoice->items->sumMoney('price_discount')),
    'tax' => $invoice->tax_amount?->isEqualTo($invoice->items->sumMoney('price_tax')),
    'total' => $invoice->total_amount?->isEqualTo($invoice->items->sumMoney('price')),
];
```

Use this for invoices with populated totals and line amounts. Investigate skipped or incomplete records separately; reconciliation is not a reason to force-recalculate an authoritative snapshot.

For complete examples of the new API, see the [README](README.md).
