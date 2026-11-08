<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Pembelian - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 40px 40px; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            position: relative;
        }

        /* WATERMARK */
        .watermark {
            position: absolute;
            top: 25%;
            left: 15%;
            width: 70%;
            opacity: 0.05;
            z-index: -100;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin: 25px 0 20px 0;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 25px;
        }

        .info-table td {
            padding: 4px;
            vertical-align: top;
        }

        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            text-align: center;
        }

        .items-table th {
            background-color: #fff;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }

        .items-table td.text-left {
            text-align: left;
        }
        .items-table td.text-right {
            text-align: right;
        }

        .summary-table {
            width: 320px;
            float: right;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px;
            border-bottom: none;
        }

        .summary-table td:nth-child(2) {
            text-align: right;
        }

        .summary-total {
            color: #000;
            font-weight: bold;
            font-size: 14px;
        }

        .signature-section {
            margin-top: 80px;
            width: 300px;
            text-align: center;
        }
        .signature-space {
            height: 80px;
        }
        .clear {
            clear: both;
        }

        .footer-deco {
            position: fixed;
            bottom: -40px;
            left: -40px;
            right: -40px;
            height: 20px;
            background: linear-gradient(to right, #2E8B57, #84C038);
        }
    </style>
</head>
<body>

    <?php
        $kopBase64 ??= \App\Http\Controllers\PdfController::cachedPublicImageBase64('images/kop-surat.png');
        $wmBase64 ??= \App\Http\Controllers\PdfController::cachedPublicImageBase64('images/logo-gm-clear.png');
    ?>

    @if($wmBase64)
        <img src="{{ $wmBase64 }}" class="watermark">
    @endif

    <div class="footer-deco"></div>

    @if($kopBase64)
        <div style="text-align: center; margin-bottom: 20px; margin-top: -20px;">
            <img src="{{ $kopBase64 }}" style="width: 100%; max-width: 800px;">
        </div>
    @endif

    <div class="title">REKAP FAKTUR PEMBELIAN</div>

    <table class="info-table">
        <tr>
            <td width="12%"><strong>Supplier</strong></td>
            <td width="2%">:</td>
            <td width="46%">{{ $invoice->supplier?->name ?? '-' }}</td>

            <td width="15%"><strong>No Faktur</strong></td>
            <td width="2%">:</td>
            <td width="23%">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Telepon</strong></td>
            <td>:</td>
            <td>{{ $invoice->supplier?->phone ?? '-' }}</td>

            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td>{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>:</td>
            <td>{{ $invoice->supplier?->address ?? '-' }}</td>

            <td><strong>Jatuh Tempo</strong></td>
            <td>:</td>
            <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>:</td>
            <td colspan="4">{{ $invoice->status }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="37%">Nama Barang</th>
                <th width="8%">Qty</th>
                <th width="8%">Satuan</th>
                <th width="14%">Harga Beli</th>
                <th width="12%">Diskon</th>
                <th width="16%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                @php
                    $gross = (float) $item->quantity * (float) $item->unit_price;
                    $discount = max(0, $gross - (float) $item->subtotal);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->item_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit ?: 'Pcs' }}</td>
                    <td class="text-right">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $discount > 0 ? 'Rp' . number_format($discount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>Subtotal</td>
            <td>Rp{{ number_format($invoice->subtotal_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>PPN</td>
            <td>Rp{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        <tr class="summary-total">
            <td>Total Pembelian</td>
            <td>Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Sudah Dibayar</td>
            <td>Rp{{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    @if(filled($invoice->notes))
        <div style="margin-top: 20px;">
            <strong>Catatan:</strong> {{ $invoice->notes }}
        </div>
    @endif

    <div class="signature-section">
        <div>PT Global Mediline Indonesia</div>
        <div class="signature-space"></div>
        <div><strong>Bagian Pembelian</strong></div>
    </div>

</body>
</html>

