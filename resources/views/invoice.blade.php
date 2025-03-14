<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .totals { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fatura #{{ $invoice->invoice_number }}</h1>
        <p>Fatura Tarihi: {{ $invoice->invoice_date }}</p>
    </div>

    <div class="invoice-info">
        <p>Müşteri: {{ $invoice->order->full_name }}</p>
        <p>E-posta: {{ $invoice->order->email }}</p>
        <p>Telefon: {{ $invoice->order->phone_number }}</p>
    </div>

    <h2>Sipariş Edilen Ürünler</h2>
    <table>
        <thead>
            <tr>
                <th>Ürün Adı</th>
                <th>SKU</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->orderProducts as $product)
            <tr>
                <td>{{ $product->variant->product->name ?? 'Ürün Adı Yok' }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->quantity }} {{ $product->quantity_type }}</td>
                <td>{{ number_format($product->sale_price ?? $product->base_price, 2) }} {{ $invoice->currency }}</td>
                <td>{{ number_format(($product->sale_price ?? $product->base_price) * $product->quantity, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Ara Toplam: {{ number_format($invoice->order->subtotal, 2) }} {{ $invoice->currency }}</p>
        <p>Vergi Tutarı: {{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</p>
        <p>Kargo Ücreti: {{ number_format($invoice->order->shipping_cost, 2) }} {{ $invoice->currency }}</p>
        <h3>Genel Toplam: {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</h3>
    </div>
</body>
</html>
