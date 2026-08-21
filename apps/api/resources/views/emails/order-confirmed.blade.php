<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, Helvetica, sans-serif; background: #F8F6F0; margin: 0; padding: 24px; color: #1a1a1a; }
  .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d5; }
  .header { background: #073F3A; color: #F8F6F0; padding: 24px; text-align: center; }
  .header img { height: 40px; margin-bottom: 12px; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { padding: 24px; }
  .greeting { font-size: 15px; margin: 0 0 4px; }
  .lede { color: #555; margin: 0 0 20px; }
  .info-box { background: #F8F6F0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; }
  .info-box p { margin: 4px 0; }
  .info-box .label { color: #888; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { text-align: right; padding: 8px 4px; border-bottom: 1px solid #eee; font-size: 14px; }
  .totals td { font-size: 14px; }
  .totals .grand { font-weight: bold; font-size: 16px; color: #073F3A; }
  .cta { text-align: center; margin: 28px 0 8px; }
  .cta a { display: inline-block; background: #D4A63A; color: #073F3A; text-decoration: none; font-weight: bold; font-size: 14px; padding: 12px 28px; border-radius: 999px; }
  .footer { padding: 16px 24px; text-align: center; color: #888; font-size: 12px; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <img src="{{ $logoUrl }}" alt="CHABA">
      <h1>شكرًا لطلبك من CHABA</h1>
    </div>
    <div class="body">
      <p class="greeting">مرحبًا {{ $order->guest_name }}،</p>
      <p class="lede">تم استلام طلبك بنجاح وهو الآن قيد المعالجة.</p>

      <div class="info-box">
        <p><span class="label">رقم الطلب:</span> <strong>{{ $order->order_number }}</strong></p>
        <p><span class="label">طريقة الدفع:</span> {{ $paymentMethodLabel }}</p>
        @if ($order->address)
        <p>
          <span class="label">عنوان التوصيل:</span>
          {{ $order->address->wilaya?->name_ar }}@if($order->address->commune) — {{ $order->address->commune->name_ar }}@endif
          — {{ $order->address->address_line }}
          @if ($order->address->landmark)
            ({{ $order->address->landmark }})
          @endif
        </p>
        @endif
      </div>

      <table>
        <thead>
          <tr><th>المنتج</th><th>الكمية</th><th>السعر</th></tr>
        </thead>
        <tbody>
          @foreach ($order->items as $item)
          <tr>
            <td>{{ $item->product_name_snapshot }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->line_total / 100) }} دج</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <table class="totals">
        <tr><td>المجموع الفرعي</td><td>{{ number_format($order->subtotal / 100) }} دج</td></tr>
        @if ($order->discount_total > 0)
        <tr><td>الخصم</td><td>-{{ number_format($order->discount_total / 100) }} دج</td></tr>
        @endif
        <tr><td>رسوم التوصيل</td><td>{{ number_format($order->delivery_fee / 100) }} دج</td></tr>
        @if ($order->tax_total > 0)
        <tr><td>الضريبة</td><td>{{ number_format($order->tax_total / 100) }} دج</td></tr>
        @endif
        <tr class="grand"><td>المجموع الكلي</td><td>{{ number_format($order->grand_total / 100) }} دج</td></tr>
      </table>

      <div class="cta">
        <a href="{{ $trackingUrl }}">تتبع طلبك</a>
      </div>
    </div>
    <div class="footer">CHABA &mdash; عطرك المفضل من قلب الجزائر</div>
  </div>
</body>
</html>
