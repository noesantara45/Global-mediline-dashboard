<!DOCTYPE html>
<html>
<head>
    <title>Surat Jalan</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .footer { margin-top: 50px; width: 100%; display: table; }
        .signature-box { display: table-cell; text-align: center; width: 33%; }
        .signature-line { margin-top: 80px; border-bottom: 1px solid #000; width: 80%; display: inline-block; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SURAT JALAN</h2>
        <p>No Reff: {{ $so->so_number }}</p>
    </div>
    
    <div>
        <p><strong>Tujuan Pengiriman:</strong><br>
        {{ $so->client->name }}<br>
        {{ $so->client->address }}<br>
        PIC: {{ $so->client->contact_person }} ({{ $so->client->phone }})</p>
        <p><strong>Tanggal Pengiriman:</strong> {{ date('Y-m-d') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($so->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Diterima Oleh,</p>
            <span class="signature-line"></span>
        </div>
        <div class="signature-box">
            <p>Pengirim,</p>
            <span class="signature-line"></span>
        </div>
        <div class="signature-box">
            <p>Hormat Kami,</p>
            <span class="signature-line"></span>
        </div>
    </div>
</body>
</html>
