<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Elegantly\Invoices\Enums\InvoiceType;
use Elegantly\Invoices\Models\Invoice;
use Elegantly\Invoices\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

it('can set the right serial number year and month from a date', function (
    ?string $format,
    ?CarbonInterface $date,
    ?int $expectedYear,
    ?int $expectedMonth,
) {
    /** @var Invoice */
    $invoice = Invoice::factory()
        ->state([
            'serial_number_format' => $format,
        ])
        ->make();

    $invoice->configureSerialNumber(
        year: $date?->format('Y'),
        month: $date?->format('m'),
    );

    expect($invoice->serial_number_format)->toBe($format);
    expect($invoice->serial_number_year)->toBe($expectedYear);
    expect($invoice->serial_number_month)->toBe($expectedMonth);
})->with([
    ['PPPSSS-YYMMCCC', Carbon::create(2024, 6), 24, 6],
    ['PPPSSS-YYMMCCC', Carbon::create(2004, 12), 04, 12],
    ['PPPSSS-YYYYMMCCC', Carbon::create(2024, 6), 2024, 6],
    ['PPPSSS-MMCCC', Carbon::create(2024, 6), null, 6],
    ['PPPSSS-YYYYCCC', Carbon::create(2024, 6), 2024, null],
    ['PPPSSS-CC', Carbon::create(2024, 6), null, null],
]);

it('can generate and denormalize serial numbers', function (
    ?string $format,
    ?string $prefix,
    ?int $serie,
    ?int $year,
    string $expectedSerialNumber
) {
    /** @var Invoice */
    $invoice = Invoice::factory()
        ->state([
            'serial_number_format' => $format,
            'serial_number_prefix' => $prefix,
            'serial_number_serie' => $serie,
            'serial_number_year' => $year,
        ])
        ->create();

    expect($invoice->serial_number)->toBe($expectedSerialNumber);
    expect($invoice->serial_number_format)->toBe($format);
    expect($invoice->serial_number_prefix)->toBe($prefix);
    expect($invoice->serial_number_year)->toBe($year);
    expect($invoice->serial_number_month)->toBe(null);
    expect($invoice->serial_number_count)->toBe(1);
})->with([
    ['PPPSSS-YYCCC', 'INV', 42, 23, 'INV042-23001'],
    ['PPP-YYCCC', 'INV', null, 23, 'INV-23001'],
    ['PPPCCC', 'INV', null, null, 'INV001'],
    ['CCCCC', null, null, null, '00001'],
]);

it('can generate and denormalize serial numbers with values from config file', function () {
    $this->travelTo(
        Carbon::create(2024, 8, 3, 0, 0, 0)
    );

    config()->set('invoices.serial_number.format', [
        InvoiceType::Invoice->value => 'PPYYCCCC',
    ]);

    /** @var Invoice */
    $invoice = Invoice::factory()->create();

    expect($invoice->serial_number)->toBe('IN240001');
    expect($invoice->serial_number_format)->toBe(config('invoices.serial_number.format.invoice'));
    expect($invoice->serial_number_prefix)->toBe(config('invoices.serial_number.prefix.invoice'));
    expect($invoice->serial_number_year)->toBe(24);
    expect($invoice->serial_number_serie)->toBe(null);
    expect($invoice->serial_number_month)->toBe(null);
    expect($invoice->serial_number_count)->toBe(1);
});

it('can find the previous generate invoice and generate the right following serial number', function (
    ?string $format,
    ?string $prefix,
    ?int $serie,
    ?int $year,
    string $expectedLastSerialNumber,
    string $expectedNextSerialNumber
) {

    /** @var Collection<int, Invoice> */
    $invoices = Invoice::factory()
        ->count(12)
        ->state([
            'serial_number_format' => $format,
            'serial_number_prefix' => $prefix,
            'serial_number_serie' => $serie,
            'serial_number_year' => $year,
        ])
        ->create();

    $latestInvoice = $invoices->last();

    expect($latestInvoice->serial_number)->toBe($expectedLastSerialNumber);

    /** @var Invoice */
    $nextInvoice = Invoice::factory()
        ->state([
            'serial_number_format' => $format,
            'serial_number_prefix' => $prefix,
            'serial_number_serie' => $serie,
            'serial_number_year' => $year,
        ])
        ->make();

    expect($nextInvoice->getPreviousInvoice()->id)->toBe($latestInvoice->id);
    expect($nextInvoice->generateSerialNumber()->serial_number)->toBe($expectedNextSerialNumber);
})->with([
    ['PPPSSS-YYCCC', 'INV', 42, 23, 'INV042-23012', 'INV042-23013'],
    ['PPP-YYCCC', 'INV', null, 23, 'INV-23012', 'INV-23013'],
    ['PPPCCC', 'INV', null, null, 'INV012', 'INV013'],
    ['CCCCC', null, null, null, '00012', '00013'],
]);

it('can create and generate unique serial numbers based on invoice type from config file', function () {
    config()->set('invoices.serial_number.format', [
        InvoiceType::Invoice->value => 'PPPYYC',
        InvoiceType::Quote->value => 'PPPYYCC',
        InvoiceType::Credit->value => 'PPPYYCCC',
        InvoiceType::Proforma->value => 'PPPYYCCCC',
    ]);

    config()->set('invoices.serial_number.prefix', [
        InvoiceType::Invoice->value => 'INV',
        InvoiceType::Quote->value => 'QUO',
        InvoiceType::Credit->value => 'CRE',
        InvoiceType::Proforma->value => 'PRO',
    ]);

    $year = now()->format('y');

    /** @var Invoice */
    $invoice = Invoice::factory()->invoice()->create();
    /** @var Invoice */
    $quote = Invoice::factory()->quote()->create();
    /** @var Invoice */
    $credit = Invoice::factory()->credit()->create();
    /** @var Invoice */
    $proforma = Invoice::factory()->proforma()->create();

    expect($invoice->serial_number)->toBe("INV{$year}1");
    expect($quote->serial_number)->toBe("QUO{$year}01");
    expect($credit->serial_number)->toBe("CRE{$year}001");
    expect($proforma->serial_number)->toBe("PRO{$year}0001");
});

it('can create following serial numbers even if format changes', function (
    string $firstFormat,
    string $expectedFirstLastSerialNumber,
    string $secondFormat,
    string $expectedSecondLastSerialNumber,
) {
    config()->set('invoices.serial_number.prefix', 'INV');
    config()->set('invoices.serial_number.format', $firstFormat);

    /** @var Collection<int, Invoice> $invoices */
    $invoices = Invoice::factory()
        ->state([
            'serial_number_year' => 24,
            'serial_number_serie' => 42,
        ])
        ->count(11)
        ->create();

    expect($invoices->last()->serial_number)->toBe($expectedFirstLastSerialNumber);

    config()->set('invoices.serial_number.format', $secondFormat);

    /** @var Invoice */
    $invoice = Invoice::factory()
        ->state([
            'serial_number_year' => 24,
            'serial_number_serie' => 42,
        ])
        ->create();

    expect($invoice->serial_number)->toBe($expectedSecondLastSerialNumber);
})->with([
    ['PPPSSSS-YYCCCC', 'INV0042-240011', 'PPPSSSSSS-YYCCCCCC', 'INV000042-24000012'],
]);

it('can create following serial numbers scoped by year', function () {

    /** @var Collection<int, Invoice> $invoices */
    $invoices = Invoice::factory()
        ->state([
            'serial_number_format' => 'PPP-YYCCC',
            'serial_number_year' => 23,
            'serial_number_prefix' => 'INV',
        ])
        ->count(11)
        ->create();

    expect($invoices->last()->serial_number)->toBe('INV-23011');

    /** @var Invoice */
    $invoice = Invoice::factory()
        ->state([
            'serial_number_format' => 'PPP-YYCCC',
            'serial_number_year' => 24,
            'serial_number_prefix' => 'INV',
        ])
        ->create();

    expect($invoice->serial_number)->toBe('INV-24001');
});

it('denormalize amounts in invoice', function () {
    /** @var Invoice */
    $invoice = Invoice::factory()->make();
    $invoice->save();

    $invoice->items()->saveMany(InvoiceItem::factory(2)->make());

    $invoice->denormalize()->save();
    $pdfInvoice = $invoice->toPdfInvoice();

    expect($invoice->subtotal_amount->getAmount()->toFloat())
        ->toEqual($pdfInvoice->subTotalAmount()->getAmount()->toFloat());

    expect($invoice->discount_amount->getAmount()->toFloat())
        ->toEqual($pdfInvoice->totalDiscountAmount()->getAmount()->toFloat());

    expect($invoice->tax_amount->getAmount()->toFloat())
        ->toEqual($pdfInvoice->totalTaxAmount()->getAmount()->toFloat());

    expect($invoice->total_amount->getAmount()->toFloat())
        ->toEqual($pdfInvoice->totalAmount()->getAmount()->toFloat());
});
