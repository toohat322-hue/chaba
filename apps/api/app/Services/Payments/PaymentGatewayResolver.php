<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a PaymentProviderInterface implementation by gateway key
 * ('cod'/'cib'/'edahabia'). Replaces the single flat container binding that
 * used to exist in AppServiceProvider — the map (built in
 * AppServiceProvider::register()) only contains gateways that are actually
 * enabled, so resolving a disabled/unconfigured gateway fails fast here
 * instead of reaching a provider that's guaranteed to throw.
 */
class PaymentGatewayResolver
{
    /**
     * @param  array<string, class-string<PaymentProviderInterface>>  $gateways
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $gateways,
    ) {}

    public function resolve(string $gateway): PaymentProviderInterface
    {
        $class = $this->gateways[$gateway] ?? null;

        if (! $class) {
            throw ApiException::businessRule(
                'payment_method_unavailable',
                "Payment method \"{$gateway}\" is not available.",
            );
        }

        return $this->container->make($class);
    }
}
