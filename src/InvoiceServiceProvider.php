<?php

declare(strict_types=1);

namespace Elegantly\Invoices;

use BackedEnum;
use Elegantly\Invoices\Commands\DenormalizeInvoicesCommand;
use Exception;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function Illuminate\Support\enum_value;

class InvoiceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-invoices')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(DenormalizeInvoicesCommand::class)
            ->hasMigration('create_invoices_table')
            ->hasMigration('create_invoice_items_table')
            ->hasMigration('add_discounts_column_to_invoices_table')
            ->hasMigration('add_type_column_to_invoices_table')
            ->hasMigration('add_denormalized_columns_to_invoices_table')
            ->hasMigration('add_serial_number_details_columns_to_invoices_table')
            ->hasMigration('migrate_serial_number_details_columns_to_invoices_table')
            ->hasMigration('add_payment_instructions_to_invoices_table');
    }

    public static function getSerialNumberPrefixConfiguration(null|string|BackedEnum $type): ?string
    {
        /** @var null|int|string */
        $value = enum_value($type);

        /** @var string|array<string, string> $prefixes */
        $prefixes = config('invoices.serial_number.prefix', '');

        if (is_string($prefixes)) {
            return $prefixes;
        }

        return $prefixes[$value] ?? null;
    }

    public static function getSerialNumberFormatConfiguration(null|string|BackedEnum $type): string
    {
        /** @var null|int|string */
        $value = enum_value($type);

        /** @var string|array<string, string> $formats */
        $formats = config('invoices.serial_number.format') ?? '';

        if (is_string($formats)) {
            return $formats;
        }

        /** @var ?string $format */
        $format = $formats[$value] ?? null;

        if (! $format) {
            throw new Exception("No serial number format defined in config for type: {$value}.");
        }

        return $format;
    }
}
