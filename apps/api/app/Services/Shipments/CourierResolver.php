<?php

namespace App\Services\Shipments;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a CourierInterface implementation by courier-partner key, the
 * same provider-name-keyed pattern as PaymentGatewayResolver — a new
 * courier partner is added purely by extending the map (see
 * AppServiceProvider::register()), with no ShipmentService/route changes.
 */
class CourierResolver
{
    /**
     * @param  array<string, class-string<CourierInterface>>  $couriers
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $couriers,
    ) {}

    public function resolve(?string $courierPartner): CourierInterface
    {
        $key = $courierPartner ?? 'manual';
        $class = $this->couriers[$key] ?? null;

        if (! $class) {
            throw ApiException::businessRule(
                'courier_unavailable',
                "Courier \"{$key}\" is not available.",
            );
        }

        return $this->container->make($class);
    }
}
