<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    // ═══════════════════════════════════════════
    //  SHIPROCKET
    // ═══════════════════════════════════════════

    /**
     * Get or refresh Shiprocket auth token.
     */
    private static function getShiprocketToken(): ?string
    {
        $settings = DB::table('tbl_settings')->first();

        // Return cached token if still valid
        if ($settings->shiprocket_token && $settings->shiprocket_token_expires_at) {
            $expiresAt = \Carbon\Carbon::parse($settings->shiprocket_token_expires_at);
            if ($expiresAt->isFuture()) {
                return $settings->shiprocket_token;
            }
        }

        // Generate new token
        try {
            $response = Http::post('https://apiv2.shiprocket.in/v1/external/auth/login', [
                'email' => $settings->shiprocket_email,
                'password' => $settings->shiprocket_password,
            ]);

            if ($response->successful()) {
                $token = $response->json('token');
                DB::table('tbl_settings')->update([
                    'shiprocket_token' => $token,
                    'shiprocket_token_expires_at' => now()->addDays(9), // Shiprocket tokens valid for 10 days
                ]);
                return $token;
            }

            Log::error('Shiprocket auth failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Shiprocket auth exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Make authenticated Shiprocket API request.
     */
    private static function shiprocketRequest(string $method, string $endpoint, array $data = []): ?array
    {
        $token = self::getShiprocketToken();
        if (!$token) return null;

        try {
            $http = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ]);

            $response = match ($method) {
                'GET' => $http->get("https://apiv2.shiprocket.in/v1/external{$endpoint}", $data),
                'POST' => $http->post("https://apiv2.shiprocket.in/v1/external{$endpoint}", $data),
                'PUT' => $http->put("https://apiv2.shiprocket.in/v1/external{$endpoint}", $data),
                'PATCH' => $http->patch("https://apiv2.shiprocket.in/v1/external{$endpoint}", $data),
                default => null,
            };

            if ($response && $response->successful()) {
                return $response->json();
            }

            Log::error("Shiprocket {$method} {$endpoint} failed", ['response' => $response?->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error("Shiprocket {$endpoint} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a Shiprocket order.
     */
    public static function createShiprocketOrder(array $orderData): ?array
    {
        return self::shiprocketRequest('POST', '/orders/create/adhoc', $orderData);
    }

    /**
     * Generate AWB (Air Waybill) for shipment.
     */
    public static function generateAWB(int $shipmentId, ?int $courierId = null): ?array
    {
        $data = ['shipment_id' => $shipmentId];
        if ($courierId) {
            $data['courier_id'] = $courierId;
        }
        return self::shiprocketRequest('POST', '/courier/assign/awb', $data);
    }

    /**
     * Schedule pickup for a shipment.
     */
    public static function schedulePickup(int $shipmentId): ?array
    {
        return self::shiprocketRequest('POST', '/courier/generate/pickup', [
            'shipment_id' => [$shipmentId],
        ]);
    }

    /**
     * Get shipping label URL.
     */
    public static function getShippingLabel(int $shipmentId): ?string
    {
        $result = self::shiprocketRequest('POST', '/courier/generate/label', [
            'shipment_id' => [$shipmentId],
        ]);
        return $result['label_url'] ?? null;
    }

    /**
     * Track shipment by AWB.
     */
    public static function trackShipment(string $awbCode): ?array
    {
        return self::shiprocketRequest('GET', "/courier/track/awb/{$awbCode}");
    }

    /**
     * Track shipment by Shiprocket order ID.
     */
    public static function trackShipmentByOrderId(int $shiprocketOrderId): ?array
    {
        return self::shiprocketRequest('GET', "/courier/track/shipment/{$shiprocketOrderId}");
    }

    /**
     * Get available couriers for shipment.
     */
    public static function checkServiceability(string $pickupPincode, string $deliveryPincode, int $weightGrams, bool $isCod = false, int $amountPaise = 0): ?array
    {
        $weightKg = max(0.5, round($weightGrams / 1000, 2));

        return self::shiprocketRequest('GET', '/courier/serviceability/', [
            'pickup_postcode' => $pickupPincode,
            'delivery_postcode' => $deliveryPincode,
            'weight' => $weightKg,
            'cod' => $isCod ? 1 : 0,
            'declared_value' => round($amountPaise / 100, 2),
        ]);
    }

    /**
     * Create a return order in Shiprocket.
     */
    public static function createReturnOrder(array $returnData): ?array
    {
        return self::shiprocketRequest('POST', '/orders/create/return', $returnData);
    }

    /**
     * Cancel a Shiprocket order.
     */
    public static function cancelOrder(array $orderIds): ?array
    {
        return self::shiprocketRequest('POST', '/orders/cancel', [
            'ids' => $orderIds,
        ]);
    }

    /**
     * Get list of pickup locations.
     */
    public static function getPickupLocations(): ?array
    {
        return self::shiprocketRequest('GET', '/settings/company/pickup');
    }

    /**
     * Add a new pickup location (for seller onboarding).
     */
    public static function addPickupLocation(array $locationData): ?array
    {
        return self::shiprocketRequest('POST', '/settings/company/addpickup', $locationData);
    }

    /**
     * Get COD remittance details.
     */
    public static function getCODRemittance(): ?array
    {
        return self::shiprocketRequest('GET', '/account/details/cod');
    }

    // ═══════════════════════════════════════════
    //  DELHIVERY
    // ═══════════════════════════════════════════

    private static function getDelhiveryApiKey(): ?string
    {
        $settings = DB::table('tbl_settings')->first();
        return $settings->delhivery_api_key;
    }

    /**
     * Make authenticated Delhivery API request.
     */
    private static function delhiveryRequest(string $method, string $endpoint, $data = null, bool $isForm = false): ?array
    {
        $apiKey = self::getDelhiveryApiKey();
        if (!$apiKey) {
            Log::error('Delhivery API key not configured');
            return null;
        }

        $baseUrl = app()->environment('production')
            ? 'https://track.delhivery.com'
            : 'https://staging-express.delhivery.com';

        try {
            $http = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
            ]);

            if ($isForm) {
                $http = $http->asForm();
            }

            $response = match ($method) {
                'GET' => $http->get("{$baseUrl}{$endpoint}", is_array($data) ? $data : []),
                'POST' => $isForm
                    ? $http->post("{$baseUrl}{$endpoint}", is_array($data) ? $data : [])
                    : $http->withBody(is_string($data) ? $data : json_encode($data), 'application/json')
                        ->post("{$baseUrl}{$endpoint}"),
                default => null,
            };

            if ($response && $response->successful()) {
                return $response->json();
            }

            Log::error("Delhivery {$method} {$endpoint} failed", ['response' => $response?->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error("Delhivery {$endpoint} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create Delhivery shipment.
     */
    public static function createDelhiveryShipment(array $shipmentData): ?array
    {
        $format = 'format=json&data=' . json_encode(['shipments' => [$shipmentData], 'pickup_location' => ['name' => $shipmentData['pickup_location'] ?? 'default']]);
        return self::delhiveryRequest('POST', '/api/cmu/create.json', $format, true);
    }

    /**
     * Track Delhivery shipment.
     */
    public static function trackDelhiveryShipment(string $waybill): ?array
    {
        return self::delhiveryRequest('GET', '/api/v1/packages/json/', ['waybill' => $waybill]);
    }

    /**
     * Check Delhivery serviceability.
     */
    public static function checkDelhiveryServiceability(string $originPin, string $destPin): ?array
    {
        return self::delhiveryRequest('GET', '/c/api/pin-codes/json/', [
            'filter_codes' => $destPin,
            'o_pin' => $originPin,
        ]);
    }

    /**
     * Cancel Delhivery shipment.
     */
    public static function cancelDelhiveryShipment(string $waybill): ?array
    {
        return self::delhiveryRequest('POST', '/api/p/edit', [
            'waybill' => $waybill,
            'cancellation' => true,
        ], true);
    }

    // ═══════════════════════════════════════════
    //  UNIFIED INTERFACE
    // ═══════════════════════════════════════════

    /**
     * Create a shipping order through the preferred aggregator.
     * Returns standardized result with order_id, shipment_id, awb, etc.
     */
    public static function createShippingOrder(array $orderDetails, string $preferredAggregator = 'shiprocket'): ?array
    {
        $settings = DB::table('tbl_settings')->first();

        // Determine which aggregator to use
        if ($preferredAggregator === 'shiprocket' && ($settings->shiprocket_enabled ?? false)) {
            return self::createShiprocketShippingOrder($orderDetails);
        } elseif ($preferredAggregator === 'delhivery' && ($settings->delhivery_enabled ?? false)) {
            return self::createDelhiveryShippingOrder($orderDetails);
        }

        // Fallback: try any available
        if ($settings->shiprocket_enabled ?? false) {
            return self::createShiprocketShippingOrder($orderDetails);
        }
        if ($settings->delhivery_enabled ?? false) {
            return self::createDelhiveryShippingOrder($orderDetails);
        }

        Log::error('No shipping aggregator enabled');
        return null;
    }

    /**
     * Create order through Shiprocket with standard interface.
     */
    private static function createShiprocketShippingOrder(array $d): ?array
    {
        $shiprocketData = [
            'order_id' => $d['order_id'],
            'order_date' => now()->format('Y-m-d H:i'),
            'channel_id' => '',
            'billing_customer_name' => $d['customer_name'],
            'billing_last_name' => '',
            'billing_address' => $d['address_line1'],
            'billing_address_2' => $d['address_line2'] ?? '',
            'billing_city' => $d['city'],
            'billing_pincode' => $d['pincode'],
            'billing_state' => $d['state'],
            'billing_country' => 'India',
            'billing_email' => $d['email'] ?? 'customer@kick.mybd.in',
            'billing_phone' => $d['phone'],
            'shipping_is_billing' => true,
            'order_items' => $d['items'],
            'payment_method' => ($d['payment_method'] ?? 'prepaid') === 'cod' ? 'COD' : 'Prepaid',
            'sub_total' => round(($d['total_amount_paise'] ?? 0) / 100, 2),
            'length' => $d['length_cm'] ?? 10,
            'breadth' => $d['breadth_cm'] ?? 10,
            'height' => $d['height_cm'] ?? 10,
            'weight' => max(0.5, round(($d['weight_grams'] ?? 500) / 1000, 2)),
            'pickup_location' => $d['pickup_location'] ?? 'Primary',
        ];

        $result = self::createShiprocketOrder($shiprocketData);
        if (!$result) return null;

        $shipmentId = $result['shipment_id'] ?? null;
        $awb = null;
        $courierName = null;
        $labelUrl = null;

        // Auto-assign AWB
        if ($shipmentId) {
            $awbResult = self::generateAWB($shipmentId);
            if ($awbResult) {
                $awbData = $awbResult['response']['data'] ?? $awbResult;
                $awb = $awbData['awb_code'] ?? $awbData['awb_assign_status'] ?? null;
                $courierName = $awbData['courier_name'] ?? null;
            }

            // Generate label
            $labelUrl = self::getShippingLabel($shipmentId);

            // Schedule pickup
            self::schedulePickup($shipmentId);
        }

        return [
            'aggregator' => 'shiprocket',
            'order_id' => $result['order_id'] ?? null,
            'shipment_id' => $shipmentId,
            'awb' => $awb,
            'courier_name' => $courierName,
            'label_url' => $labelUrl,
            'raw_response' => $result,
        ];
    }

    /**
     * Create order through Delhivery with standard interface.
     */
    private static function createDelhiveryShippingOrder(array $d): ?array
    {
        $items = $d['items'] ?? [];
        $productDesc = implode(', ', array_column($items, 'name'));

        $shipmentData = [
            'name' => $d['customer_name'],
            'add' => ($d['address_line1'] ?? '') . ' ' . ($d['address_line2'] ?? ''),
            'pin' => $d['pincode'],
            'city' => $d['city'],
            'state' => $d['state'],
            'country' => 'India',
            'phone' => $d['phone'],
            'order' => (string) $d['order_id'],
            'payment_mode' => ($d['payment_method'] ?? 'prepaid') === 'cod' ? 'COD' : 'Pre-paid',
            'return_pin' => $d['pickup_pincode'] ?? '',
            'return_city' => $d['pickup_city'] ?? '',
            'return_phone' => $d['pickup_phone'] ?? '',
            'return_add' => $d['pickup_address'] ?? '',
            'return_state' => $d['pickup_state'] ?? '',
            'return_country' => 'India',
            'products_desc' => $productDesc,
            'hsn_code' => $d['hsn_code'] ?? '',
            'cod_amount' => ($d['payment_method'] ?? 'prepaid') === 'cod' ? round(($d['total_amount_paise'] ?? 0) / 100, 2) : 0,
            'order_date' => now()->format('Y-m-d H:i:s'),
            'total_amount' => round(($d['total_amount_paise'] ?? 0) / 100, 2),
            'seller_name' => $d['seller_name'] ?? 'Kick Marketplace',
            'quantity' => array_sum(array_column($items, 'units')),
            'waybill' => '',
            'shipment_width' => $d['breadth_cm'] ?? 10,
            'shipment_height' => $d['height_cm'] ?? 10,
            'weight' => max(500, $d['weight_grams'] ?? 500),
            'pickup_location' => ['name' => $d['pickup_location'] ?? 'default'],
        ];

        $result = self::createDelhiveryShipment($shipmentData);
        if (!$result) return null;

        $packages = $result['packages'] ?? [];
        $waybill = $packages[0]['waybill'] ?? null;

        return [
            'aggregator' => 'delhivery',
            'order_id' => $d['order_id'],
            'shipment_id' => null,
            'awb' => $waybill,
            'courier_name' => 'Delhivery',
            'label_url' => null,
            'raw_response' => $result,
        ];
    }

    /**
     * Track a shipment across aggregators.
     */
    public static function trackOrder(string $aggregator, string $awbOrOrderId): ?array
    {
        return match ($aggregator) {
            'shiprocket' => self::trackShipment($awbOrOrderId),
            'delhivery' => self::trackDelhiveryShipment($awbOrOrderId),
            default => null,
        };
    }

    /**
     * Check serviceability and get shipping estimates.
     */
    public static function checkAvailability(string $pickupPincode, string $deliveryPincode, int $weightGrams, bool $isCod = false, int $amountPaise = 0): array
    {
        $settings = DB::table('tbl_settings')->first();
        $results = [];

        if ($settings->shiprocket_enabled ?? false) {
            $sr = self::checkServiceability($pickupPincode, $deliveryPincode, $weightGrams, $isCod, $amountPaise);
            if ($sr) {
                $couriers = $sr['data']['available_courier_companies'] ?? [];
                foreach ($couriers as $courier) {
                    $results[] = [
                        'aggregator' => 'shiprocket',
                        'courier_id' => $courier['courier_company_id'] ?? null,
                        'courier_name' => $courier['courier_name'] ?? 'Unknown',
                        'rate_paise' => (int) round(($courier['rate'] ?? 0) * 100),
                        'etd_days' => (int) ($courier['estimated_delivery_days'] ?? 7),
                        'cod_available' => (bool) ($courier['cod'] ?? false),
                    ];
                }
            }
        }

        if ($settings->delhivery_enabled ?? false) {
            $dl = self::checkDelhiveryServiceability($pickupPincode, $deliveryPincode);
            if ($dl && !empty($dl['delivery_codes'])) {
                $results[] = [
                    'aggregator' => 'delhivery',
                    'courier_id' => null,
                    'courier_name' => 'Delhivery',
                    'rate_paise' => null, // Delhivery doesn't return rate in serviceability check
                    'etd_days' => 5,
                    'cod_available' => true,
                ];
            }
        }

        return $results;
    }

    /**
     * Cancel a shipping order.
     */
    public static function cancelShippingOrder(string $aggregator, $identifier): bool
    {
        return match ($aggregator) {
            'shiprocket' => (bool) self::cancelOrder(is_array($identifier) ? $identifier : [$identifier]),
            'delhivery' => (bool) self::cancelDelhiveryShipment((string) $identifier),
            default => false,
        };
    }
}
