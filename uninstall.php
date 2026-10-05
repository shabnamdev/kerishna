<?php
/**
 * Secure uninstall for Kerishna Shop Migrator.
 * Data is preserved by default; only plugin-owned persistent settings are removed.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$shcd_kerishna_option_names = array(
    'shcd_customer_settings',
    'shcd_customer_db_cache',
);

foreach ( $shcd_kerishna_option_names as $shcd_kerishna_option_name ) {
    delete_option( $shcd_kerishna_option_name );
    delete_site_option( $shcd_kerishna_option_name );
}

// Replay-protection transients are intentionally not queried/deleted directly.
// They expire automatically after the short API replay window and no order,
// customer, product or source-site data is touched during uninstall.
