<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faktur Penjualan - {{ $invoice->invoice_number }}</title>
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

        /* HEADER */
        .header {
            position: relative;
            margin-bottom: 20px;
        }
        
        .logo {
            position: absolute;
            top: -10px;
            left: 0;
            width: 130px; /* Lebar logo */
        }
        
        .header-text {
            text-align: right; /* Taruh teks di kanan */
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #000; /* Hitam */
            margin-bottom: 5px;
        }
        
        .company-address, .company-contact {
            font-size: 12px;
            color: #555;
            margin-bottom: 3px;
        }
        
        .header-line {
            margin-top: 15px;
            border-bottom: 3px solid #2E8B57; /* Garis tebal hijau */
            position: relative;
        }
        
        .header-line::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            border-bottom: 1px solid #7cb594; /* Garis tipis hijau muda */
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #000; /* Hitam */
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
            border: 1px solid #000; /* Garis hitam */
            padding: 10px 8px;
            text-align: center;
        }
        
        .items-table th {
            background-color: #fff; /* Putih */
            color: #000; /* Hitam */
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
            color: #000; /* Hitam */
            font-weight: bold;
            font-size: 14px;
        }
        
        .summary-total td {
            border-bottom: none;
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
        
        /* FOOTER DECORATION */
        .footer-deco {
            position: fixed;
            bottom: -40px;
            left: -40px;
            right: -40px;
            height: 20px;
            background: linear-gradient(to right, #2E8B57, #84C038); /* Gradasi hijau seperti daun */
        }
    </style>
</head>
<body>

    <?php
        // Kop Surat Image
        $kopPath = public_path('images/kop-surat.png');
        $kopData = file_get_contents($kopPath);
        $kopType = mime_content_type($kopPath) ?: 'image/png';
        $kopBase64 = 'data:' . $kopType . ';base64,' . base64_encode($kopData);
        
        // Watermark (opsional, masih pakai logo-gm-clear.png jika mau)
        $wmPath = public_path('images/logo-gm-clear.png');
        $wmData = file_get_contents($wmPath);
        $wmType = mime_content_type($wmPath) ?: 'image/png';
        $wmBase64 = 'data:' . $wmType . ';base64,' . base64_encode($wmData);
    ?>

    <!-- Watermark Logo -->
    <img src="{{ $wmBase64 }}" class="watermark">

    <!-- Pita Hijau Bawah (Bisa dipertahankan atau dihapus) -->
    <div class="footer-deco"></div>

    <!-- Kop Surat Full Width -->
    <div style="text-align: center; margin-bottom: 20px; margin-top: -20px;">
        <img src="{{ $kopBase64 }}" style="width: 100%; max-width: 800px;">
    </div>
    
    <div class="title">FAKTUR PENJUALAN</div>

    <table class="info-table">
        <tr>
            <td width="12%"><strong>Customer</strong></td>
            <td width="2%">:</td>
            <td width="46%">{{ $invoice->client->name }}</td>
            
            <td width="15%"><strong>No Faktur</strong></td>
            <td width="2%">:</td>
            <td width="23%">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Telepon</strong></td>
            <td>:</td>
            <td>{{ $invoice->client->phone ?? '-' }}</td>
            
            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>:</td>
            <td>{{ $invoice->client->address ?? '-' }}</td>
            
            <td><strong>Jatuh Tempo</strong></td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Nama Barang</th>
                <th width="8%">Qty</th>
                <th width="8%">Satuan</th>
                <th width="15%">Harga Satuan</th>
                <th width="10%">Diskon</th>
                <th width="19%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $item->item_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-right">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>{{ floatval($item->discount_percentage) > 0 ? floatval($item->discount_percentage) . '%' : '-' }}</td>
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
            <td>Total Tagihan</td>
            <td>Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    <div class="signature-section">
        <div>PT Global Mediline Indonesia</div>
        <div class="signature-space">
            <!-- Tempat Tanda Tangan / Stempel -->
        </div>
        <div><strong>Ratih Ratu Kusuma</strong></div>
    </div>

</body>
</html>
