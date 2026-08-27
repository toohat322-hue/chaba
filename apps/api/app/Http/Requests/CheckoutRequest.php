<?php

namespace App\Http\Requests;

use App\Models\StoreSetting;
use App\Rules\AlgerianPhone;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->customer_phone)) {
            $normalized = PhoneNormalizer::toE164($this->customer_phone);
            if ($normalized) {
                $this->merge(['customer_phone' => $normalized]);
            }
        }

        if (is_array($this->address) && is_string($this->address['phone'] ?? null)) {
            $normalized = PhoneNormalizer::toE164($this->address['phone']);
            if ($normalized) {
                $this->merge(['address' => [...$this->address, 'phone' => $normalized]]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', new AlgerianPhone],
            'customer_email' => ['nullable', 'email', 'max:255'],

            'address_id' => ['required_without:address', 'uuid'],

            'address' => ['required_without:address_id', 'array'],
            'address.full_name' => ['required_with:address', 'string', 'max:255'],
            'address.phone' => ['required_with:address', new AlgerianPhone],
            'address.wilaya_code' => ['required_with:address', 'exists:wilayas,code'],
            'address.commune_id' => ['required_with:address', 'uuid', 'exists:communes,id'],
            'address.address_line' => ['required_with:address', 'string', 'max:255'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],

            // payment_method is validated against whichever gateways are
            // actually enabled+configured (PaymentGatewayResolver) —
            // cib/edahabia only become selectable once their
            // CIB_ENABLED/EDAHABIA_ENABLED env vars are set. delivery_method
            // is checked against configured wilaya rates, not hardcoded here
            // (OrderService throws delivery_fee_not_configured if the chosen
            // wilaya+method has no fee set).
            'delivery_method' => ['required', 'in:home,pickup'],
            'payment_method' => ['required', 'in:'.implode(',', $this->availablePaymentMethods())],

            'notes' => ['nullable', 'string', 'max:1000'],
            'locale' => ['nullable', 'in:ar,fr,en'],
        ];
    }

    /**
     * @return list<string>
     */
    private function availablePaymentMethods(): array
    {
        $methods = ['cod'];

        if (config('services.cib.enabled')) {
            $methods[] = 'cib';
        }

        if (config('services.edahabia.enabled')) {
            $methods[] = 'edahabia';
        }

        // DB-driven gate (Admin Settings), not a config() flag like the
        // gateways above — the admin can flip this without a redeploy.
        $settings = StoreSetting::current();
        if ($settings->whatsapp_orders_enabled && $settings->whatsapp_active && $settings->whatsapp_number) {
            $methods[] = 'whatsapp';
        }

        return $methods;
    }
}
