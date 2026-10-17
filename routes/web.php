<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/print/outbound-invoice/{invoice}', [PdfController::class, 'printOutboundInvoice'])->name('outbound-invoice.pdf');
});
