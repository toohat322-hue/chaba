<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, Helvetica, sans-serif; background: #F8F6F0; margin: 0; padding: 24px; color: #1a1a1a; }
  .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d5; }
  .header { background: #073F3A; color: #F8F6F0; padding: 24px; text-align: center; }
  .header h1 { margin: 0; font-size: 20px; }
  .status { display: inline-block; margin-top: 8px; padding: 6px 16px; border-radius: 999px; background: #D4A63A; color: #073F3A; font-weight: bold; }
  .body { padding: 24px; text-align: center; }
  .footer { padding: 16px 24px; text-align: center; color: #888; font-size: 12px; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>تحديث على طلبك</h1>
      <div class="status">{{ $statusLabel }}</div>
    </div>
    <div class="body">
      <p>رقم الطلب: <strong>{{ $order->order_number }}</strong></p>
      <p>أصبحت حالة طلبك الآن: <strong>{{ $statusLabel }}</strong></p>
    </div>
    <div class="footer">CHABA &mdash; عطرك المفضل من قلب الجزائر</div>
  </div>
</body>
</html>
