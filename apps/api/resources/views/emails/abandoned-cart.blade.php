<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, Helvetica, sans-serif; background: #F8F6F0; margin: 0; padding: 24px; color: #1a1a1a; }
  .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d5; }
  .header { background: #073F3A; color: #F8F6F0; padding: 24px; text-align: center; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { padding: 24px; }
  ul { padding: 0; margin: 16px 0; list-style: none; }
  li { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
  .cta { display: block; text-align: center; margin-top: 20px; background: #D4A63A; color: #073F3A; font-weight: bold; text-decoration: none; padding: 12px; border-radius: 999px; }
  .footer { padding: 16px 24px; text-align: center; color: #888; font-size: 12px; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>سلتك بانتظارك</h1>
    </div>
    <div class="body">
      <p>لاحظنا أنك تركت بعض المنتجات في سلتك. لا تزال محجوزة لك:</p>
      <ul>
        @foreach ($cart->items as $item)
        <li>{{ $item->variant->product->name_ar ?? '' }} &times; {{ $item->quantity }}</li>
        @endforeach
      </ul>
      <a href="{{ $cartUrl }}" class="cta">إتمام الطلب الآن</a>
    </div>
    <div class="footer">CHABA &mdash; عطرك المفضل من قلب الجزائر</div>
  </div>
</body>
</html>
