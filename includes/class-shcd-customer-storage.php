<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SHCD_Customer_Storage {
    public static function detect() {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'wc_orders';
        $legacy_table = $wpdb->posts;
        $lookup_table = $wpdb->prefix . 'wc_order_product_lookup';

        // The WooCommerce utility is the authoritative way to determine whether
        // HPOS is currently active. The option fallback is only used when the
        // utility is unavailable or throws during an incomplete WooCommerce boot.
        $hpos_enabled = 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' );
        if ( function_exists( 'wc_get_container' ) && class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
            try {
                $util = wc_get_container()->get( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' );
                $hpos_enabled = (bool) $util->custom_orders_table_usage_is_enabled();
            } catch ( Throwable $e ) {
                // Keep the option-derived fallback above.
            }
        }

        $primary_table = $hpos_enabled ? $orders_table : $legacy_table;

        return array(
            'mode'             => $hpos_enabled ? 'HPOS' : 'Legacy',
            'orders_table'     => $primary_table,
            'lookup_table'     => $lookup_table,
            'tables'           => array( $primary_table, $lookup_table ),
            'wpdb_prefix'      => $wpdb->prefix,
            'database_charset' => defined( 'DB_CHARSET' ) && DB_CHARSET ? DB_CHARSET : get_option( 'blog_charset', 'UTF-8' ),
        );
    }
}
