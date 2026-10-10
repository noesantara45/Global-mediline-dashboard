<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/print/outbound-invoice/{invoice}', [PdfController::class, 'printOutboundInvoice'])->name('outbound-invoice.pdf');
});
