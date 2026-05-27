<?php
class Service_FeatureKeyResolver {

    private static array $map = [
        'lead'             => 'crm_leads',
        'sales_order'      => 'sales_orders',
        'sales_delivery'   => 'sales_deliveries',
        'purchase_order'   => 'purchase_orders',
        'purchase_receipt' => 'purchase_receipts',
        'customer'         => 'customers',
        'vendor'           => 'vendors',
        'product'          => 'products',
    ];

    /**
     * Maps a related_type / entity_type string to the feature key that gates access to that object.
     * Returns null for unknown types — callers should deny access on null.
     */
    public static function resolve(string $relatedType): ?string {
        return self::$map[$relatedType] ?? null;
    }
}
