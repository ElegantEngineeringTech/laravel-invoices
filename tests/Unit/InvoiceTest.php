<?php

declare(strict_types=1);

use Elegantly\Invoices\Models\Invoice;
use Illuminate\Http\UploadedFile;

it('can retrieve a base64 encoded url from a binary file', function () {
    $invoice = new Invoice;

    $file = UploadedFile::fake()->image('foo.jpg');

    $invoice->setLogoFromFile($file);

    expect($invoice->logo)->not->toBe(null);

    $base64Logo = $invoice->getLogo();

    expect($base64Logo)->not->toBe(null);

});
