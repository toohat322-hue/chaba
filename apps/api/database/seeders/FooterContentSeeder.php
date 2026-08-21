<?php

namespace Database\Seeders;

use App\Models\FooterColumn;
use App\Models\FooterFeature;
use App\Models\FooterPaymentMethod;
use App\Models\FooterSocialLink;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

// Footer CMS default content — seeds real, non-fake starting data so the
// storefront footer looks identical to before this feature existed, and an
// admin edits from here rather than starting from an empty footer.
class FooterContentSeeder extends Seeder
{
    public function run(): void
    {
        $settings = StoreSetting::current();
        $settings->update([
            'about_title_ar' => 'من نحن',
            'about_title_fr' => 'À propos',
            'about_title_en' => 'About Us',
            'about_description_ar' => 'شابة هو وجهتك الموثوقة للعطور الفاخرة في الجزائر، نقدّم مختارات أصيلة 100% من أرقى العطور السعودية والتركية.',
            'about_description_fr' => 'CHABA est votre destination de confiance pour les parfums de luxe en Algérie, proposant une sélection 100% authentique des plus beaux parfums saoudiens et turcs.',
            'about_description_en' => 'CHABA is your trusted destination for luxury fragrances in Algeria, offering a 100% authentic selection of the finest Saudi and Turkish perfumes.',
            // Matches the placeholder already used by NEXT_PUBLIC_WHATSAPP_NUMBER
            // in apps/web/.env.local — real number is a TODO for the business.
            'whatsapp_number' => $settings->whatsapp_number ?? '213555000000',
            'whatsapp_message_ar' => 'مرحبًا، أريد الاستفسار عن منتجات CHABA',
            'whatsapp_message_fr' => 'Bonjour, je souhaite me renseigner sur les produits CHABA',
            'whatsapp_message_en' => "Hi, I'd like to ask about CHABA products",
            'whatsapp_active' => true,
        ]);

        // These 4 are the storefront's canonical trust messages — "authentic"
        // and "delivery" used to also live in components/home/TrustBar.tsx
        // (home-page only); removed from there when these were added here so
        // the same phrase doesn't appear twice on the home page.
        FooterFeature::query()->delete();
        FooterFeature::create([
            'icon' => 'shield', 'title_ar' => 'الدفع عند الاستلام متاح', 'title_fr' => 'Paiement à la livraison disponible',
            'title_en' => 'Cash on delivery available',
            'description_ar' => 'ادفع عند استلام طلبك بأمان', 'description_fr' => 'Payez en toute sécurité à la réception',
            'description_en' => 'Pay safely when your order arrives', 'sort_order' => 1,
        ]);
        FooterFeature::create([
            'icon' => 'gift', 'title_ar' => 'سياسة إرجاع مرنة', 'title_fr' => 'Politique de retour flexible',
            'title_en' => 'Flexible return policy',
            'description_ar' => 'إرجاع سهل خلال 7 أيام', 'description_fr' => 'Retour facile sous 7 jours',
            'description_en' => 'Easy returns within 7 days', 'sort_order' => 2,
        ]);
        FooterFeature::create([
            'icon' => 'badge', 'title_ar' => 'عطور أصلية 100%', 'title_fr' => '100% parfums authentiques',
            'title_en' => '100% authentic perfumes',
            'description_ar' => 'مصدر موثوق ومضمون', 'description_fr' => 'Source fiable et garantie',
            'description_en' => 'Trusted, guaranteed source', 'sort_order' => 3,
        ]);
        FooterFeature::create([
            'icon' => 'truck', 'title_ar' => 'توصيل لجميع الولايات', 'title_fr' => 'Livraison dans toutes les wilayas',
            'title_en' => 'Delivery to all wilayas',
            'description_ar' => 'تغطية كاملة عبر 58 ولاية', 'description_fr' => 'Couverture complète des 58 wilayas',
            'description_en' => 'Full coverage across all 58 wilayas', 'sort_order' => 4,
        ]);

        FooterColumn::query()->delete();

        $browse = FooterColumn::create(['title_ar' => 'تصفح', 'title_fr' => 'Parcourir', 'title_en' => 'Browse', 'sort_order' => 1]);
        $browseLinks = [
            ['عطور سعودية', 'Parfums saoudiens', 'Saudi Perfumes', '/category/saudi-perfumes'],
            ['عطور تركية', 'Parfums turcs', 'Turkish Perfumes', '/category/turkish-perfumes'],
            ['دهن العود', "Huile d'oud", 'Oud Oil', '/category/oud-oil'],
            ['عروض حصرية', 'Offres exclusives', 'Exclusive Offers', '/category/exclusive-offers'],
        ];
        foreach ($browseLinks as $i => [$ar, $fr, $en, $url]) {
            $browse->links()->create(['label_ar' => $ar, 'label_fr' => $fr, 'label_en' => $en, 'url' => $url, 'sort_order' => $i]);
        }

        $support = FooterColumn::create(['title_ar' => 'خدمة العملاء', 'title_fr' => 'Service client', 'title_en' => 'Customer Service', 'sort_order' => 2]);
        $supportLinks = [
            ['الأسئلة الشائعة', 'Questions fréquentes', 'FAQ', '/faq'],
            ['الشحن والإرجاع', 'Livraison et retours', 'Shipping & Returns', '/shipping-returns'],
            ['تتبع الطلب', 'Suivre ma commande', 'Track Order', '/track-order'],
            ['اتصل بنا', 'Contact', 'Contact Us', '/contact'],
        ];
        foreach ($supportLinks as $i => [$ar, $fr, $en, $url]) {
            $support->links()->create(['label_ar' => $ar, 'label_fr' => $fr, 'label_en' => $en, 'url' => $url, 'sort_order' => $i]);
        }

        $info = FooterColumn::create(['title_ar' => 'معلومات', 'title_fr' => 'Informations', 'title_en' => 'Information', 'sort_order' => 3]);
        $infoLinks = [
            ['من نحن', 'À propos', 'About Us', '/about'],
            ['سياسة الخصوصية', 'Confidentialité', 'Privacy Policy', '/privacy'],
            ['الشروط والأحكام', 'Conditions générales', 'Terms & Conditions', '/terms'],
        ];
        foreach ($infoLinks as $i => [$ar, $fr, $en, $url]) {
            $info->links()->create(['label_ar' => $ar, 'label_fr' => $fr, 'label_en' => $en, 'url' => $url, 'sort_order' => $i]);
        }

        FooterPaymentMethod::query()->delete();
        FooterPaymentMethod::create([
            'name_ar' => 'الدفع عند الاستلام', 'name_fr' => 'Paiement à la livraison', 'name_en' => 'Cash on Delivery',
            'icon' => 'cod', 'is_active' => true, 'sort_order' => 1,
        ]);
        FooterPaymentMethod::create([
            'name_ar' => 'بطاقة CIB', 'name_fr' => 'Carte CIB', 'name_en' => 'CIB Card',
            'icon' => 'cib', 'is_active' => (bool) config('services.cib.enabled'), 'sort_order' => 2,
        ]);
        FooterPaymentMethod::create([
            'name_ar' => 'بطاقة الذهبية', 'name_fr' => 'Carte Edahabia', 'name_en' => 'Edahabia Card',
            'icon' => 'edahabia', 'is_active' => (bool) config('services.edahabia.enabled'), 'sort_order' => 3,
        ]);
        // Not real gateways this store processes today (see StoreFooterPaymentMethodRequest's
        // ICONS docblock) — seeded inactive so an admin can switch one on with a
        // single toggle the moment it's actually integrated, rather than
        // building the row from scratch. Never shown on the live storefront
        // until an admin deliberately activates it.
        FooterPaymentMethod::create([
            'name_ar' => 'فيزا', 'name_fr' => 'Visa', 'name_en' => 'Visa',
            'icon' => 'visa', 'is_active' => false, 'sort_order' => 4,
        ]);
        FooterPaymentMethod::create([
            'name_ar' => 'ماستركارد', 'name_fr' => 'Mastercard', 'name_en' => 'Mastercard',
            'icon' => 'mastercard', 'is_active' => false, 'sort_order' => 5,
        ]);
        FooterPaymentMethod::create([
            'name_ar' => 'أبل باي', 'name_fr' => 'Apple Pay', 'name_en' => 'Apple Pay',
            'icon' => 'applepay', 'is_active' => false, 'sort_order' => 6,
        ]);
        FooterPaymentMethod::create([
            'name_ar' => 'مدى', 'name_fr' => 'mada', 'name_en' => 'mada',
            'icon' => 'mada', 'is_active' => false, 'sort_order' => 7,
        ]);

        // Placeholder handles (not verified real accounts) so the "Follow us"
        // column is visible immediately — active on purpose per explicit
        // request, unlike the payment methods above. Replace each `url`
        // with the real handle from /admin/footer/social-links; nothing
        // else needs to change since these are already active.
        FooterSocialLink::query()->delete();
        $socialLinks = [
            ['tiktok', 'https://tiktok.com/@chaba'],
            ['snapchat', 'https://snapchat.com/add/chaba'],
            ['instagram', 'https://instagram.com/chaba'],
            ['twitter', 'https://x.com/chaba'],
            ['facebook', 'https://facebook.com/chaba'],
        ];
        foreach ($socialLinks as $i => [$platform, $url]) {
            FooterSocialLink::create(['platform' => $platform, 'url' => $url, 'is_active' => true, 'sort_order' => $i]);
        }
    }
}
