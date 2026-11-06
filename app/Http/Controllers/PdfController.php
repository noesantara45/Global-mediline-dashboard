<?php

namespace App\Http\Controllers;

use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    public function printOutboundInvoice(OutboundInvoice $invoice): Response
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.outbound-invoice', compact('invoice'));
        // Menggunakan ukuran kertas A4 potrait
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Invoice-{$safeNumber}.pdf");
    }

    public function printSuratJalan(OutboundInvoice $invoice): Response
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.surat-jalan', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Surat-Jalan-{$safeNumber}.pdf");
    }

    public function printInboundInvoice(InboundInvoice $invoice): Response
    {
        $invoice->load(['supplier', 'items']);
        $pdf = Pdf::loadView('pdf.inbound-invoice', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Pembelian-{$safeNumber}.pdf");
    }
}
