<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura #{{ $invoice->invoice_number }}</title>
    <style>
        .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .invoice-table th { background-color: #f2f2f2; }
        .totals { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Merhaba {{ $invoice->customer->name }}</h1>
    <p>Faturanız #{{ $invoice->invoice_number }} oluşturulmuştur. Detaylar aşağıda belirtilmiştir:</p>

    <h2>Sipariş Detayları</h2>
    <table class="invoice-table">
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
        <p>Toplam Tutar: {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</p>
    </div>

    <p>Detaylı bilgi için ekteki PDF dosyasını inceleyebilirsiniz.</p>
</body>
</html>
