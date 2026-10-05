<?php
/**
 * Plugin Name: Kerishna Shop Migrator
 * Description: Move store orders and customer accounts with source-safe backups, independent exports, smart imports, and duplicate-aware customer matching.
 * Version: 1.0.0
 * Author: shcd
 * Author URI: https://shcd.ir
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kerishna-shop-migrator
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'SHCD_CUSTOMER_VERSION' ) ) { return; }
define( 'SHCD_CUSTOMER_VERSION', '1.0.0' );
define( 'SHCD_CUSTOMER_FILE', __FILE__ );
define( 'SHCD_CUSTOMER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHCD_CUSTOMER_URL', plugin_dir_url( __FILE__ ) );
define( 'SHCD_KERISHNA_SLUG', 'shcd-kerishna' );
define( 'SHCD_KERISHNA_NAME', 'Kerishna Shop Migrator' );

function shcd_customer_autoload() {
    foreach ( array( 'class-shcd-customer-security.php','class-shcd-customer-storage.php','class-shcd-customer-customers.php','class-shcd-customer-orders.php','class-shcd-customer-api.php','class-shcd-customer-admin.php' ) as $file ) {
        require_once SHCD_CUSTOMER_DIR . 'includes/' . $file;
    }
}
shcd_customer_autoload();

add_action( 'plugins_loaded', 'shcd_customer_bootstrap', 20 );
function shcd_customer_bootstrap() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'shcd_customer_woocommerce_notice' );
        return;
    }
    SHCD_Customer_API::init();
    SHCD_Customer_Admin::init();
}

function shcd_customer_woocommerce_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) { return; }
    echo '<div class="notice notice-warning"><p>' . esc_html__( 'Kerishna Shop Migrator requires WooCommerce to be installed and active.', 'kerishna-shop-migrator' ) . '</p></div>';
}
