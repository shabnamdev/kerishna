<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SHCD_Customer_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
    }

    public static function menu() {
        add_menu_page(
            SHCD_KERISHNA_NAME,
            'Kerishna',
            'manage_woocommerce',
            SHCD_KERISHNA_SLUG,
            array( __CLASS__, 'render' ),
            SHCD_CUSTOMER_URL . 'assets/images/kerishna-menu-icon.png',
            58
        );
    }

    public static function assets( $hook ) {
        // The menu icon is displayed across the WordPress admin, so its small
        // stylesheet is enqueued on every admin screen through the proper hook.
        wp_enqueue_style(
            'shcd-kerishna-menu-icon',
            SHCD_CUSTOMER_URL . 'assets/css/admin-menu.css',
            array(),
            SHCD_CUSTOMER_VERSION
        );

        if ( 'toplevel_page_shcd-kerishna' !== $hook ) { return; }

        wp_enqueue_style( 'shcd-kerishna-admin', SHCD_CUSTOMER_URL . 'assets/css/admin.css', array(), SHCD_CUSTOMER_VERSION );
        wp_enqueue_script( 'shcd-kerishna-admin', SHCD_CUSTOMER_URL . 'assets/js/admin.js', array(), SHCD_CUSTOMER_VERSION, true );

        $locale = strtolower( (string) get_user_locale() );
        $default_lang = 0 === strpos( $locale, 'fa' ) ? 'fa' : ( 0 === strpos( $locale, 'ar' ) ? 'ar' : 'en' );

        wp_localize_script(
            'shcd-kerishna-admin',
            'SHCDCustomer',
            array(
                'restUrl'      => esc_url_raw( rest_url( SHCD_Customer_API::NS ) ),
                'nonce'        => wp_create_nonce( SHCD_Customer_Security::NONCE_ACTION ),
                'siteUrl'      => home_url( '/' ),
                'version'      => SHCD_CUSTOMER_VERSION,
                'pluginId'     => 'shcd-kerishna',
                'pluginName'   => SHCD_KERISHNA_NAME,
                'defaultLang'  => $default_lang,
                'developerUrl' => 'https://shabnam.dev',
            )
        );
    }

    public static function render() {
        if ( ! SHCD_Customer_Security::capability() ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'kerishna-shop-migrator' ) );
        }
        include SHCD_CUSTOMER_DIR . 'includes/views/admin-page.php';
    }
}
