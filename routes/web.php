<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::middleware(['auth'])->group(function () {
    Route::get('/print/outbound-invoice/{invoice}', [PdfController::class, 'printOutboundInvoice'])->name('outbound-invoice.pdf');
    Route::get('/print/surat-jalan/{invoice}', [PdfController::class, 'printSuratJalan'])->name('surat-jalan.pdf');
});
