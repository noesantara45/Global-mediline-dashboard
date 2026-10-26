<?php

namespace App\Http\Controllers;

use App\Models\OutboundInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function printOutboundInvoice(OutboundInvoice $invoice)
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.outbound-invoice', compact('invoice'));
        // Menggunakan ukuran kertas A4 potrait
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function printSuratJalan(OutboundInvoice $invoice)
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.surat-jalan', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Surat-Jalan-{$invoice->invoice_number}.pdf");
    }
}
