<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Sipariş Teslim Edildi</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    .header {
      background-color: #28a745;
      padding: 20px;
      text-align: center;
      color: #fff;
    }
    .header h1 {
      margin: 0;
      font-size: 24px;
    }
    .content {
      padding: 20px;
    }
    .content h2 {
      color: #333;
      font-size: 22px;
      margin-bottom: 10px;
    }
    .content p {
      font-size: 16px;
      line-height: 1.6;
      color: #555;
      margin: 10px 0;
    }
    .order-details {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }
    .order-details h3 {
      font-size: 20px;
      color: #333;
      margin-bottom: 15px;
    }
    .order-details p {
      margin: 5px 0;
      font-size: 16px;
      color: #444;
    }
    .order-details strong {
      color: #000;
    }
    .footer {
      background-color: #f9f9f9;
      padding: 15px;
      text-align: center;
      font-size: 12px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Sipariş Teslim Edildi</h1>
    </div>
    <div class="content">
      <h2>Siparişiniz Başarıyla Teslim Edildi!</h2>
      <p>Merhaba {{$order->full_name}},</p>
      <p>Siparişiniz başarıyla teslim edilmiştir. Aşağıda siparişinizin detaylarını bulabilirsiniz.</p>
      <div class="order-details">
        <h3>Sipariş Detayları</h3>
        <p><strong>Sipariş Numarası:</strong> #{{ $order->order_number }}</p>
        <p><strong>Kargo Takip Numarası:</strong> #{{ $order->shipping_tracking_number }}</p>
        <p><strong>Kargo Çıkış Tarihi:</strong> {{ $order->shipment_date }}</p>
        <p><strong>Kargo Teslimat Tarihi:</strong> {{ $order->delivery_date }}</p>
      </div>
      <p>Alışverişiniz için teşekkür eder, tekrar bekleriz.</p>
    </div>
    <div class="footer">
      <p>&copy; {{ date('Y') }} Şirket Adı. Tüm hakları saklıdır.</p>
    </div>
  </div>
</body>
</html>
