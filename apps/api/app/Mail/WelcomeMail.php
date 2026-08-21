<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    private const COPY = [
        'ar' => [
            'dir' => 'rtl',
            'subject' => 'مرحبًا بك في CHABA 🤍',
            'heading' => 'مرحبًا بك في CHABA',
            'greeting' => 'أهلًا',
            'punctuation' => '،',
            'body' => 'يسعدنا انضمامك إلينا. تصفّح أرقى العطور واستمتع بتجربة تسوّق مميزة، ونعدك دائمًا بجودة وأصالة تليق بك.',
            'cta' => 'تسوّق الآن',
        ],
        'fr' => [
            'dir' => 'ltr',
            'subject' => 'Bienvenue chez CHABA 🤍',
            'heading' => 'Bienvenue chez CHABA',
            'greeting' => 'Bonjour',
            'punctuation' => ',',
            'body' => "Nous sommes ravis de vous compter parmi nous. Découvrez nos parfums d'exception et profitez d'une expérience d'achat qui vous ressemble.",
            'cta' => 'Découvrir la boutique',
        ],
        'en' => [
            'dir' => 'ltr',
            'subject' => 'Welcome to CHABA 🤍',
            'heading' => 'Welcome to CHABA',
            'greeting' => 'Hi',
            'punctuation' => ',',
            'body' => "We're delighted to have you with us. Explore our finest perfumes and enjoy a shopping experience made for you.",
            'cta' => 'Shop now',
        ],
    ];

    public function __construct(public readonly User $user) {}

    public function build(): self
    {
        $locale = in_array($this->user->preferred_language, ['ar', 'fr', 'en'], true)
            ? $this->user->preferred_language
            : 'ar';
        $copy = self::COPY[$locale];
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return $this->subject($copy['subject'])
            ->view('emails.welcome')
            ->with([
                'user' => $this->user,
                'copy' => $copy,
                'logoUrl' => "{$frontendUrl}/brand/logo.png",
                'shopUrl' => "{$frontendUrl}/{$locale}",
            ]);
    }
}
