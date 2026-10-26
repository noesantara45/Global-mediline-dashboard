<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $invoice->invoice_number ?? ($so->so_number ?? '') }}</title>
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
            margin: 20px 0 15px 0;
            letter-spacing: 1px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table {
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 4px;
            vertical-align: top;
        }

        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 8px;
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

        .note-box {
            margin-top: 15px;
            font-size: 11px;
            font-style: italic;
            color: #555;
        }
        
        /* FOOTER DECORATION */
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

    @php
        $inv = $invoice ?? $so ?? null;
        
        // Kop Surat Image
        $kopPath = public_path('images/kop-surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $kopData = file_get_contents($kopPath);
            $kopType = mime_content_type($kopPath) ?: 'image/png';
            $kopBase64 = 'data:' . $kopType . ';base64,' . base64_encode($kopData);
        }
        
        // Watermark Logo
        $wmPath = public_path('images/logo-gm-clear.png');
        $wmBase64 = '';
        if (file_exists($wmPath)) {
            $wmData = file_get_contents($wmPath);
            $wmType = mime_content_type($wmPath) ?: 'image/png';
            $wmBase64 = 'data:' . $wmType . ';base64,' . base64_encode($wmData);
        }
    @endphp

    @if($wmBase64)
        <img src="{{ $wmBase64 }}" class="watermark">
    @endif

    <div class="footer-deco"></div>

    @if($kopBase64)
        <div style="text-align: center; margin-bottom: 15px; margin-top: -20px;">
            <img src="{{ $kopBase64 }}" style="width: 100%; max-width: 800px;">
        </div>
    @endif
    
    <div class="title">SURAT JALAN / DELIVERY ORDER</div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Tujuan Kirim</strong></td>
            <td width="2%">:</td>
            <td width="43%">{{ $inv?->client?->name ?? '-' }}</td>
            
            <td width="15%"><strong>No. Surat Jalan</strong></td>
            <td width="2%">:</td>
            <td width="23%"><strong>SJ-{{ $inv?->invoice_number ?? ($inv?->so_number ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td><strong>Kontak / PIC</strong></td>
            <td>:</td>
            <td>{{ $inv?->client?->contact_person ?? '-' }} ({{ $inv?->client?->phone ?? '-' }})</td>
            
            <td><strong>No. Referensi</strong></td>
            <td>:</td>
            <td>{{ $inv?->invoice_number ?? ($inv?->so_number ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>:</td>
            <td>{{ $inv?->client?->address ?? '-' }}</td>
            
            <td><strong>Tanggal Kirim</strong></td>
            <td>:</td>
            <td>{{ $inv?->issue_date ? \Carbon\Carbon::parse($inv->issue_date)->format('d M Y') : date('d M Y') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="50%">Nama Barang</th>
                <th width="12%">Qty</th>
                <th width="12%">Satuan</th>
                <th width="20%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @if($inv && $inv->items)
                @foreach($inv->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->item_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit ?? 'Pcs' }}</td>
                    <td>{{ $item->notes ?? 'Kondisi Baik' }}</td>
                </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="note-box">
        <strong>Perhatian:</strong>
        <div>1. Harap barang diperiksa dengan teliti saat penerimaan.</div>
        <div>2. Barang yang telah diterima tidak dapat dikembalikan tanpa persetujuan terlebih dahulu.</div>
    </div>

    <table style="width: 100%; margin-top: 40px; text-align: center;">
        <tr>
            <td width="33%">
                <div>Diterima Oleh,</div>
                <div style="height: 65px;"></div>
                <div>( ........................................ )</div>
                <div style="font-size: 10px; color: #666; margin-top: 4px;">Tanda Tangan & Cap Penerima</div>
            </td>
            <td width="33%">
                <div>Pengirim / Ekspedisi,</div>
                <div style="height: 65px;"></div>
                <div>( ........................................ )</div>
                <div style="font-size: 10px; color: #666; margin-top: 4px;">Nama Jelas Driver / Kurir</div>
            </td>
            <td width="34%">
                <div>Hormat Kami,</div>
                <div style="font-weight: bold; font-size: 11px;">PT Global Mediline Indonesia</div>
                <div style="height: 50px;"></div>
                <div><strong>Ratih Ratu Kusuma</strong></div>
            </td>
        </tr>
    </table>

</body>
</html>
