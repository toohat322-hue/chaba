<!DOCTYPE html>
<html lang="{{ $copy['dir'] === 'rtl' ? 'ar' : 'en' }}" dir="{{ $copy['dir'] }}">
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, Helvetica, sans-serif; background: #F8F6F0; margin: 0; padding: 24px; color: #1a1a1a; }
  .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e0d5; }
  .header { background: #073F3A; color: #F8F6F0; padding: 32px 24px; text-align: center; }
  .header img { height: 40px; margin-bottom: 12px; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { padding: 24px; text-align: {{ $copy['dir'] === 'rtl' ? 'right' : 'left' }}; }
  .lede { color: #444; line-height: 1.7; margin: 0; }
  .footer { padding: 16px 24px; text-align: center; color: #888; font-size: 12px; }
  .footer a { color: #888; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <img src="{{ $logoUrl }}" alt="CHABA">
      <h1>{{ $copy['heading'] }}</h1>
    </div>
    <div class="body">
      <p class="lede">{{ $copy['body'] }}</p>
    </div>
    <div class="footer">
      CHABA &mdash; عطرك المفضل من قلب الجزائر
      <br>
      <a href="{{ $unsubscribeUrl }}">{{ $copy['unsubscribe'] }}</a>
    </div>
  </div>
</body>
</html>
