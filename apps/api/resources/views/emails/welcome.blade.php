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
  .greeting { font-size: 15px; margin: 0 0 12px; }
  .lede { color: #444; line-height: 1.7; margin: 0 0 24px; }
  .cta { text-align: center; margin: 8px 0; }
  .cta a { display: inline-block; background: #D4A63A; color: #073F3A; text-decoration: none; font-weight: bold; font-size: 14px; padding: 12px 32px; border-radius: 999px; }
  .footer { padding: 16px 24px; text-align: center; color: #888; font-size: 12px; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <img src="{{ $logoUrl }}" alt="CHABA">
      <h1>{{ $copy['heading'] }}</h1>
    </div>
    <div class="body">
      <p class="greeting">{{ $copy['greeting'] }} {{ $user->full_name }}{{ $copy['punctuation'] }}</p>
      <p class="lede">{{ $copy['body'] }}</p>
      <div class="cta">
        <a href="{{ $shopUrl }}">{{ $copy['cta'] }}</a>
      </div>
    </div>
    <div class="footer">CHABA &mdash; عطرك المفضل من قلب الجزائر</div>
  </div>
</body>
</html>
