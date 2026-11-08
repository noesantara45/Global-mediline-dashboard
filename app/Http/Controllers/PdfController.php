<?php

namespace App\Http\Controllers;

use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfController extends Controller
{
    public function printOutboundInvoice(OutboundInvoice $invoice): Response
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.outbound-invoice', [
            'invoice' => $invoice,
            'kopBase64' => self::cachedPublicImageBase64('images/kop-surat.png'),
            'wmBase64' => self::cachedPublicImageBase64('images/logo-gm-clear.png'),
        ]);
        // Menggunakan ukuran kertas A4 potrait
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Invoice-{$safeNumber}.pdf");
    }

    public function printSuratJalan(OutboundInvoice $invoice): Response
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.surat-jalan', [
            'invoice' => $invoice,
            'kopBase64' => self::cachedPublicImageBase64('images/kop-surat.png'),
            'wmBase64' => self::cachedPublicImageBase64('images/logo-gm-clear.png'),
        ]);
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Surat-Jalan-{$safeNumber}.pdf");
    }

    public function printInboundInvoice(InboundInvoice $invoice): Response
    {
        $invoice->load(['supplier', 'items']);
        $pdf = Pdf::loadView('pdf.inbound-invoice', [
            'invoice' => $invoice,
            'kopBase64' => self::cachedPublicImageBase64('images/kop-surat.png'),
            'wmBase64' => self::cachedPublicImageBase64('images/logo-gm-clear.png'),
        ]);
        $pdf->setPaper('a4', 'portrait');
        $safeNumber = str_replace(['/', '\\'], '-', (string) $invoice->invoice_number);

        return $pdf->stream("Pembelian-{$safeNumber}.pdf");
    }

    public function downloadOriginalInboundFile(InboundInvoice $invoice): StreamedResponse
    {
        $filePath = trim((string) $invoice->pdf_file);

        if (
            $filePath === '' ||
            str_contains($filePath, '..') ||
            str_starts_with($filePath, '/') ||
            str_contains($filePath, '\\')
        ) {
            abort(404);
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($filePath)) {
                return Storage::disk($disk)->response($filePath, basename($filePath), [
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }

    public static function cachedPublicImageBase64(string $relativePath): ?string
    {
        /** @var array<string, ?string> $memo */
        static $memo = [];

        if (array_key_exists($relativePath, $memo)) {
            return $memo[$relativePath];
        }

        $fullPath = public_path($relativePath);
        if (! is_file($fullPath)) {
            return $memo[$relativePath] = null;
        }

        $data = file_get_contents($fullPath);
        if ($data === false) {
            return $memo[$relativePath] = null;
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/png';

        return $memo[$relativePath] = 'data:'.$mimeType.';base64,'.base64_encode($data);
    }
}
