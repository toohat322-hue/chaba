<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterSubscriptionConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    private const COPY = [
        'ar' => [
            'dir' => 'rtl',
            'subject' => 'تم تأكيد اشتراكك في نشرة CHABA',
            'heading' => 'تم تأكيد اشتراكك',
            'body' => 'شكرًا لاشتراكك في نشرة CHABA البريدية. سنوافيك بأحدث العطور والعروض الحصرية أولًا بأول.',
            'unsubscribe' => 'إلغاء الاشتراك',
        ],
        'fr' => [
            'dir' => 'ltr',
            'subject' => 'Votre abonnement à la newsletter CHABA est confirmé',
            'heading' => 'Abonnement confirmé',
            'body' => 'Merci de vous être abonné(e) à la newsletter CHABA. Vous serez parmi les premiers informés de nos nouveautés et offres exclusives.',
            'unsubscribe' => 'Se désabonner',
        ],
        'en' => [
            'dir' => 'ltr',
            'subject' => "You're subscribed to the CHABA newsletter",
            'heading' => 'Subscription confirmed',
            'body' => "Thanks for subscribing to the CHABA newsletter. You'll be the first to hear about new arrivals and exclusive offers.",
            'unsubscribe' => 'Unsubscribe',
        ],
    ];

    public function __construct(public readonly NewsletterSubscriber $subscriber) {}

    public function build(): self
    {
        $locale = in_array($this->subscriber->locale, ['ar', 'fr', 'en'], true)
            ? $this->subscriber->locale
            : 'ar';
        $copy = self::COPY[$locale];
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return $this->subject($copy['subject'])
            ->view('emails.newsletter-subscribed')
            ->with([
                'copy' => $copy,
                'logoUrl' => "{$frontendUrl}/brand/logo.png",
                'unsubscribeUrl' => "{$frontendUrl}/{$locale}/newsletter/unsubscribe/{$this->subscriber->unsubscribe_token}",
            ]);
    }
}
