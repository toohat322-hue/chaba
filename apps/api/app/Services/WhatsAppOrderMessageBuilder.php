<?php

namespace App\Services;

use App\Models\Order;

/**
 * Builds the pre-filled wa.me message text for a WhatsApp order — order
 * number, customer, address, line items, totals — in the customer's
 * checkout locale. Deliberately not folded into OrderService (which is
 * already large) and not a general-purpose backend i18n layer: just the
 * ~12 fixed labels this one message needs, per locale.
 */
class WhatsAppOrderMessageBuilder
{
    private const LABELS = [
        'ar' => [
            'heading' => '🛍️ *طلب جديد من CHABA*',
            'order_number' => '📦 *رقم الطلب:*',
            'customer' => '👤 *بيانات العميل:*',
            'name' => 'الاسم:',
            'phone' => 'الهاتف:',
            'address' => '📍 *العنوان:*',
            'wilaya' => 'الولاية:',
            'commune' => 'البلدية:',
            'items' => '🛒 *المنتجات:*',
            'size' => 'الحجم:',
            'quantity' => 'الكمية:',
            'price' => 'السعر:',
            'total' => 'الإجمالي:',
            'subtotal' => '💰 المجموع الفرعي:',
            'discount' => '🏷️ الخصم:',
            'delivery' => '🚚 التوصيل:',
            'grand_total' => '💵 *الإجمالي النهائي:*',
            'notes' => '📝 ملاحظات:',
            'thanks' => 'شكراً لطلبك من CHABA 🤍',
        ],
        'fr' => [
            'heading' => '🛍️ *Nouvelle commande CHABA*',
            'order_number' => '📦 *Numéro de commande :*',
            'customer' => '👤 *Informations client :*',
            'name' => 'Nom :',
            'phone' => 'Téléphone :',
            'address' => '📍 *Adresse :*',
            'wilaya' => 'Wilaya :',
            'commune' => 'Commune :',
            'items' => '🛒 *Produits :*',
            'size' => 'Taille :',
            'quantity' => 'Quantité :',
            'price' => 'Prix :',
            'total' => 'Total :',
            'subtotal' => '💰 Sous-total :',
            'discount' => '🏷️ Remise :',
            'delivery' => '🚚 Livraison :',
            'grand_total' => '💵 *Total à payer :*',
            'notes' => '📝 Remarques :',
            'thanks' => 'Merci pour votre commande CHABA 🤍',
        ],
        'en' => [
            'heading' => '🛍️ *New order from CHABA*',
            'order_number' => '📦 *Order number:*',
            'customer' => '👤 *Customer details:*',
            'name' => 'Name:',
            'phone' => 'Phone:',
            'address' => '📍 *Address:*',
            'wilaya' => 'Wilaya:',
            'commune' => 'Commune:',
            'items' => '🛒 *Items:*',
            'size' => 'Size:',
            'quantity' => 'Qty:',
            'price' => 'Price:',
            'total' => 'Total:',
            'subtotal' => '💰 Subtotal:',
            'discount' => '🏷️ Discount:',
            'delivery' => '🚚 Delivery:',
            'grand_total' => '💵 *Grand total:*',
            'notes' => '📝 Notes:',
            'thanks' => 'Thank you for your order from CHABA 🤍',
        ],
    ];

    private const CURRENCY_LABEL = ['ar' => 'د.ج', 'fr' => 'DA', 'en' => 'DZD'];

    private const DIVIDER = '━━━━━━━━━━━━━━';

    public function build(Order $order, string $locale): string
    {
        $labels = self::LABELS[$locale] ?? self::LABELS['ar'];

        $lines = [
            $labels['heading'],
            self::DIVIDER,
            '',
            $labels['order_number'].' '.$order->order_number,
            '',
            $labels['customer'],
            $labels['name'].' '.$order->guest_name,
            $labels['phone'].' '.$order->guest_phone,
            '',
        ];

        if ($order->address) {
            $lines[] = $labels['address'];
            if ($order->address->relationLoaded('wilaya') && $order->address->wilaya) {
                $lines[] = $labels['wilaya'].' '.($order->address->wilaya->{"name_{$locale}"} ?? $order->address->wilaya_code);
            }
            if ($order->address->relationLoaded('commune') && $order->address->commune) {
                $lines[] = $labels['commune'].' '.($order->address->commune->{"name_{$locale}"} ?? '');
            }
            $lines[] = $order->address->address_line;
            if ($order->address->landmark) {
                $lines[] = $order->address->landmark;
            }
            $lines[] = '';
        }

        $lines[] = self::DIVIDER;
        $lines[] = '';
        $lines[] = $labels['items'];
        $lines[] = '';

        $index = 1;
        foreach ($order->items as $item) {
            $lines[] = "{$index}️⃣ {$item->product_name_snapshot}";
            if ($item->size_snapshot) {
                $lines[] = $labels['size'].' '.$item->size_snapshot;
            }
            $lines[] = $labels['quantity'].' '.$item->quantity;
            $lines[] = $labels['price'].' '.$this->money($item->unit_price, $locale);
            $lines[] = $labels['total'].' '.$this->money($item->line_total, $locale);
            $lines[] = '';
            $index++;
        }

        $lines[] = self::DIVIDER;
        $lines[] = '';
        $lines[] = $labels['subtotal'].' '.$this->money($order->subtotal, $locale);

        if ($order->discount_total > 0) {
            $lines[] = $labels['discount'].' '.$this->money($order->discount_total, $locale);
        }

        $lines[] = $labels['delivery'].' '.$this->money($order->delivery_fee, $locale);
        $lines[] = $labels['grand_total'].' '.$this->money($order->grand_total, $locale);

        if ($order->notes) {
            $lines[] = '';
            $lines[] = $labels['notes'];
            $lines[] = $order->notes;
        }

        $lines[] = '';
        $lines[] = $labels['thanks'];

        return implode("\n", $lines);
    }

    /**
     * Same convention as the frontend's formatPrice() (lib/currency.ts):
     * amounts are stored as centimes, displayed as whole DZD with no
     * decimals, so the WhatsApp message and the site never disagree.
     */
    private function money(int $centimes, string $locale): string
    {
        $dzd = (int) round($centimes / 100);
        $label = self::CURRENCY_LABEL[$locale] ?? self::CURRENCY_LABEL['ar'];

        return number_format($dzd, 0, '.', ' ').' '.$label;
    }
}
