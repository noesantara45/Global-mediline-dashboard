<!DOCTYPE html>
<html>
<head>
    <title>Sales Order</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SALES ORDER</h2>
        <p>No: {{ $so->so_number }}</p>
    </div>
    
    <div>
        <p><strong>Pelanggan:</strong><br>
        {{ $so->client->name }}<br>
        {{ $so->client->address }}<br>
        {{ $so->client->phone }}</p>
        <p><strong>Tanggal Order:</strong> {{ $so->order_date }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($so->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align: right;">Subtotal</td>
                <td>Rp {{ number_format($so->subtotal_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right;">PPN 11%</td>
                <td>Rp {{ number_format($so->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Total Keseluruhan</td>
                <td>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
