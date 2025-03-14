<!DOCTYPE html>
<html>
<head>
    <title>Sipariş Onayı</title>
</head>
<body>
    <h2>Siparişiniz Oluşturuldu!</h2>
    <p>Merhaba {{ $order->full_name }},</p>

    <h3>Sipariş Detayları:</h3>
    <p>Sipariş Numarası: #{{ $order->order_number }}</p>
    <p>Tarih: {{ $order->created_at->format('d.m.Y H:i') }}</p>

    <h4>Ürünler:</h4>
    <ul>
        @foreach($order->orderProducts as $product)
        <li>
            {{ $product->variant->product->name }} -
            {{ $product->quantity }} x {{ number_format($product->base_price, 2) }} {{ $order->currency_code }}
        </li>
        @endforeach
    </ul>

    <p><strong>Toplam Tutar:</strong> {{ number_format($order->total_amount, 2) }} {{ $order->currency_code }}</p>

    <p>Teşekkür ederiz!</p>
    <p>{{ config('E Ticaret') }}</p>
</body>
</html>
