<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Registered customer backup/import helper.
 *
 * Source-side methods are read-only. Destination-side import never overwrites an
 * existing account matched by email or phone; it only reuses that user and stores
 * an internal source mapping so imported orders can attach to the right account.
 */
final class SHCD_Customer_Customers {
    private static $phone_index = null;

    public static function export_for_orders( $orders ) {
        $ids = array();
        foreach ( (array) $orders as $order ) {
            if ( $order instanceof WC_Order ) {
                $id = absint( $order->get_customer_id() );
                if ( $id ) { $ids[ $id ] = $id; }
            }
        }

        $customers = array();
        foreach ( array_values( $ids ) as $user_id ) {
            $row = self::serialize_customer( $user_id );
            if ( $row ) { $customers[] = $row; }
        }
        return $customers;
    }


    /**
     * Export registered WooCommerce customer accounts in a read-only paginated batch.
     */
    public static function get_batch( $page = 1, $per_page = 500 ) {
        $page     = max( 1, absint( $page ) );
        $per_page = min( 500, max( 1, absint( $per_page ) ) );

        $query = new WP_User_Query(
            array(
                'role'        => 'customer',
                'number'      => $per_page,
                'paged'       => $page,
                'orderby'     => 'ID',
                'order'       => 'ASC',
                'fields'      => 'ids',
                'count_total' => true,
            )
        );

        $customers = array();
        foreach ( (array) $query->get_results() as $user_id ) {
            $row = self::serialize_customer( $user_id );
            if ( $row ) { $customers[] = $row; }
        }

        $total       = absint( $query->get_total() );
        $total_pages = $per_page ? (int) ceil( $total / $per_page ) : 0;

        return array(
            'customers'   => $customers,
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => $total_pages,
            'has_more'    => $page < $total_pages,
        );
    }

    public static function serialize_customer( $user_id ) {
        $user_id = absint( $user_id );
        $user    = $user_id ? get_userdata( $user_id ) : false;
        if ( ! $user ) { return false; }

        $billing  = self::user_address( $user_id, 'billing' );
        $shipping = self::user_address( $user_id, 'shipping' );

        return array(
            'source_user_id' => $user_id,
            'user_login'     => SHCD_Customer_Security::clean_text( $user->user_login, 60 ),
            'user_email'     => SHCD_Customer_Security::clean_email( $user->user_email ),
            'display_name'   => SHCD_Customer_Security::clean_text( $user->display_name, 250 ),
            'first_name'     => SHCD_Customer_Security::clean_text( get_user_meta( $user_id, 'first_name', true ), 100 ),
            'last_name'      => SHCD_Customer_Security::clean_text( get_user_meta( $user_id, 'last_name', true ), 100 ),
            'registered_at'  => SHCD_Customer_Security::clean_text( $user->user_registered, 32 ),
            'phone'          => SHCD_Customer_Security::clean_text( $billing['phone'], 100 ),
            'billing'        => $billing,
            'shipping'       => $shipping,
        );
    }

    public static function import_batch( $customers, $source_hash, $settings, $force_create = false ) {
        $stats = self::empty_stats();
        if ( ! $force_create && empty( $settings['create_customers'] ) ) {
            $stats['skipped'] = count( (array) $customers );
            return $stats;
        }

        foreach ( (array) $customers as $customer ) {
            if ( ! is_array( $customer ) ) { continue; }
            try {
                $result = self::import_one( $customer, $source_hash );
                $bucket = isset( $result['result'] ) ? $result['result'] : 'reused';
                if ( isset( $stats[ $bucket ] ) ) { $stats[ $bucket ]++; }
                if ( count( $stats['id_map'] ) < 200 ) {
                    $stats['id_map'][] = array(
                        'source_user_id'      => absint( isset( $result['source_user_id'] ) ? $result['source_user_id'] : 0 ),
                        'destination_user_id' => absint( isset( $result['destination_user_id'] ) ? $result['destination_user_id'] : 0 ),
                        'matched_by'          => SHCD_Customer_Security::clean_text( isset( $result['matched_by'] ) ? $result['matched_by'] : '', 30 ),
                    );
                }
            } catch ( Throwable $e ) {
                $stats['failed']++;
                if ( count( $stats['errors'] ) < 20 ) {
                    $stats['errors'][] = array(
                        'customer' => absint( isset( $customer['source_user_id'] ) ? $customer['source_user_id'] : 0 ),
                        'message'  => self::safe_error( $e->getMessage() ),
                    );
                }
            }
        }
        return $stats;
    }

    /**
     * Resolve a source order customer to a destination WP user.
     * Existing users are reused by source-map, email, then normalized phone.
     */
    public static function resolve_for_order( $customer, $billing, $shipping, $registered_customer, $source_hash, $settings ) {
        $customer            = is_array( $customer ) ? $customer : array();
        $billing             = is_array( $billing ) ? $billing : array();
        $shipping            = is_array( $shipping ) ? $shipping : array();
        $registered_customer = is_array( $registered_customer ) ? $registered_customer : array();

        $source_id = absint(
            isset( $registered_customer['source_user_id'] ) ? $registered_customer['source_user_id'] :
            ( isset( $customer['user_id'] ) ? $customer['user_id'] : 0 )
        );

        $profile = $registered_customer;
        $profile['source_user_id'] = $source_id;
        if ( empty( $profile['user_email'] ) ) {
            $profile['user_email'] = isset( $customer['email'] ) ? $customer['email'] : ( isset( $billing['email'] ) ? $billing['email'] : '' );
        }
        if ( empty( $profile['first_name'] ) ) {
            $profile['first_name'] = isset( $customer['first_name'] ) ? $customer['first_name'] : ( isset( $billing['first_name'] ) ? $billing['first_name'] : '' );
        }
        if ( empty( $profile['last_name'] ) ) {
            $profile['last_name'] = isset( $customer['last_name'] ) ? $customer['last_name'] : ( isset( $billing['last_name'] ) ? $billing['last_name'] : '' );
        }
        if ( empty( $profile['phone'] ) ) {
            $profile['phone'] = isset( $customer['phone'] ) ? $customer['phone'] : ( isset( $billing['phone'] ) ? $billing['phone'] : '' );
        }
        if ( empty( $profile['billing'] ) || ! is_array( $profile['billing'] ) ) { $profile['billing'] = $billing; }
        if ( empty( $profile['shipping'] ) || ! is_array( $profile['shipping'] ) ) { $profile['shipping'] = $shipping; }

        // A guest checkout on the source must remain a guest on the destination.
        // Only source orders attached to a registered WordPress/WooCommerce user are
        // eligible for customer-account creation/reuse.
        if ( ! $source_id ) { return 0; }

        $result = self::import_one( $profile, $source_hash, ! empty( $settings['create_customers'] ) );
        return absint( isset( $result['destination_user_id'] ) ? $result['destination_user_id'] : 0 );
    }

    private static function import_one( $customer, $source_hash, $allow_create = true ) {
        $source_id = absint( isset( $customer['source_user_id'] ) ? $customer['source_user_id'] : 0 );
        $email     = SHCD_Customer_Security::clean_email(
            isset( $customer['user_email'] ) ? $customer['user_email'] : ( isset( $customer['email'] ) ? $customer['email'] : '' )
        );
        $phone = self::normalize_phone( isset( $customer['phone'] ) ? $customer['phone'] : self::nested_phone( $customer ) );

        if ( $source_id ) {
            $mapped = self::find_by_source( $source_id, $source_hash );
            if ( $mapped ) {
                return array( 'result'=>'reused','source_user_id'=>$source_id,'destination_user_id'=>$mapped,'matched_by'=>'source_map' );
            }
        }

        if ( $email && is_email( $email ) ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                self::remember_source( $user->ID, $source_id, $source_hash );
                return array( 'result'=>'reused','source_user_id'=>$source_id,'destination_user_id'=>absint($user->ID),'matched_by'=>'email' );
            }
        }

        if ( $phone ) {
            $phone_user_id = self::find_by_phone( $phone );
            if ( $phone_user_id ) {
                self::remember_source( $phone_user_id, $source_id, $source_hash );
                return array( 'result'=>'reused','source_user_id'=>$source_id,'destination_user_id'=>$phone_user_id,'matched_by'=>'phone' );
            }
        }

        // Order-only imports may reuse an existing account, but must never create
        // a new customer account unless customer import was explicitly requested.
        if ( ! $allow_create ) {
            return array( 'result'=>'skipped','source_user_id'=>$source_id,'destination_user_id'=>0,'matched_by'=>'not_created' );
        }

        $login = self::unique_login( $customer, $source_id, $phone, $email );
        if ( ! $login ) { throw new Exception( 'ساخت نام کاربری یکتا برای مشتری ممکن نشد.' ); }

        $first_name = SHCD_Customer_Security::clean_text( isset( $customer['first_name'] ) ? $customer['first_name'] : '', 100 );
        $last_name  = SHCD_Customer_Security::clean_text( isset( $customer['last_name'] ) ? $customer['last_name'] : '', 100 );
        $display    = SHCD_Customer_Security::clean_text( isset( $customer['display_name'] ) ? $customer['display_name'] : trim( $first_name . ' ' . $last_name ), 250 );
        if ( ! $display ) { $display = $login; }

        $userdata = array(
            'user_login'   => $login,
            'user_pass'    => wp_generate_password( 32, true, true ),
            'user_email'   => ( $email && is_email( $email ) ) ? $email : '',
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $display,
            'role'         => 'customer',
        );
        $user_id = wp_insert_user( $userdata );
        if ( is_wp_error( $user_id ) ) { throw new Exception( esc_html( $user_id->get_error_message() ) ); }
        $user_id = absint( $user_id );

        self::write_address( $user_id, 'billing', isset( $customer['billing'] ) && is_array( $customer['billing'] ) ? $customer['billing'] : array() );
        self::write_address( $user_id, 'shipping', isset( $customer['shipping'] ) && is_array( $customer['shipping'] ) ? $customer['shipping'] : array() );
        if ( $phone && ! get_user_meta( $user_id, 'billing_phone', true ) ) {
            update_user_meta( $user_id, 'billing_phone', SHCD_Customer_Security::clean_text( isset($customer['phone'])?$customer['phone']:$phone, 100 ) );
        }
        self::remember_source( $user_id, $source_id, $source_hash );
        update_user_meta( $user_id, '_shcd_imported_customer', 'yes' );
        update_user_meta( $user_id, '_shcd_password_reset_required', 'yes' );
        self::$phone_index = null; // include the newly created user in subsequent matching.

        return array( 'result'=>'created','source_user_id'=>$source_id,'destination_user_id'=>$user_id,'matched_by'=>'created' );
    }

    private static function user_address( $user_id, $prefix ) {
        $keys = array( 'first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone' );
        $out = array();
        foreach ( $keys as $key ) {
            $value = get_user_meta( $user_id, $prefix . '_' . $key, true );
            $out[ $key ] = ( 'email' === $key ) ? SHCD_Customer_Security::clean_email( $value ) : SHCD_Customer_Security::clean_text( $value, 500 );
        }
        return $out;
    }

    private static function write_address( $user_id, $prefix, $address ) {
        $keys = array( 'first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone' );
        foreach ( $keys as $key ) {
            if ( ! array_key_exists( $key, $address ) ) { continue; }
            $value = ( 'email' === $key ) ? SHCD_Customer_Security::clean_email( $address[ $key ] ) : SHCD_Customer_Security::clean_text( $address[ $key ], 500 );
            if ( '' !== $value ) { update_user_meta( $user_id, $prefix . '_' . $key, $value ); }
        }
    }

    private static function nested_phone( $customer ) {
        if ( isset( $customer['billing'] ) && is_array( $customer['billing'] ) && ! empty( $customer['billing']['phone'] ) ) {
            return $customer['billing']['phone'];
        }
        return '';
    }

    private static function mapping_key( $source_hash ) {
        return '_shcd_customer_source_' . substr( strtolower( preg_replace( '/[^a-f0-9]/', '', (string) $source_hash ) ), 0, 16 );
    }

    private static function remember_source( $user_id, $source_id, $source_hash ) {
        $user_id   = absint( $user_id );
        $source_id = absint( $source_id );
        if ( ! $user_id || ! $source_id || ! $source_hash ) { return; }
        update_user_meta( $user_id, self::mapping_key( $source_hash ), $source_id );
    }

    private static function find_by_source( $source_id, $source_hash ) {
        if ( ! $source_id || ! $source_hash ) { return 0; }
        $ids = get_users(array(
            'number'     => 1,
            'fields'     => 'ids',
            'meta_key'   => self::mapping_key( $source_hash ),
            'meta_value' => absint( $source_id ),
        ));
        return ! empty( $ids ) ? absint( $ids[0] ) : 0;
    }

    private static function phone_meta_keys() {
        $keys = array( 'billing_phone', 'phone', 'mobile', 'billing_mobile', '_billing_phone' );
        $keys = apply_filters( 'shcd_kerishna_customer_phone_meta_keys', $keys );
        return array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $keys ) ) ) );
    }

    public static function normalize_phone( $phone ) {
        $phone = strtr( (string) $phone, array(
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ) );
        $digits = preg_replace( '/\D+/', '', $phone );
        if ( ! $digits ) { return ''; }

        // Common Iranian mobile representations: +98..., 0098..., 98..., 9...
        if ( 0 === strpos( $digits, '0098' ) && strlen( $digits ) >= 14 ) {
            $digits = '0' . substr( $digits, 4 );
        } elseif ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
            $digits = '0' . substr( $digits, 2 );
        } elseif ( 10 === strlen( $digits ) && '9' === $digits[0] ) {
            $digits = '0' . $digits;
        }
        return $digits;
    }

    private static function find_by_phone( $phone ) {
        $phone = self::normalize_phone( $phone );
        if ( ! $phone ) { return 0; }
        if ( null === self::$phone_index ) { self::$phone_index = self::build_phone_index(); }
        return isset( self::$phone_index[ $phone ] ) ? absint( self::$phone_index[ $phone ] ) : 0;
    }

    private static function build_phone_index() {
        $index = array();
        $keys  = self::phone_meta_keys();
        if ( empty( $keys ) ) { return $index; }

        // Use WordPress user APIs instead of querying usermeta directly. This keeps
        // the lookup compatible with persistent object caches and WordPress.org
        // coding standards while preserving the same normalized-phone matching.
        $meta_query = array( 'relation' => 'OR' );
        foreach ( $keys as $meta_key ) {
            $meta_query[] = array(
                'key'     => $meta_key,
                'compare' => 'EXISTS',
            );
        }

        $user_ids = get_users(
            array(
                'fields'      => 'ids',
                'number'      => -1,
                'orderby'     => 'ID',
                'order'       => 'ASC',
                'count_total' => false,
                'meta_query'  => $meta_query,
            )
        );

        foreach ( (array) $user_ids as $user_id ) {
            $user_id = absint( $user_id );
            if ( ! $user_id ) { continue; }
            foreach ( $keys as $meta_key ) {
                $values = get_user_meta( $user_id, $meta_key, false );
                foreach ( (array) $values as $value ) {
                    $normalized = self::normalize_phone( $value );
                    if ( $normalized && ! isset( $index[ $normalized ] ) ) {
                        $index[ $normalized ] = $user_id;
                    }
                }
            }
        }

        return $index;
    }

    private static function unique_login( $customer, $source_id, $phone, $email ) {
        $base = sanitize_user( isset( $customer['user_login'] ) ? $customer['user_login'] : '', true );
        if ( ! $base && $email ) {
            $parts = explode( '@', $email );
            $base = sanitize_user( (string) reset( $parts ), true );
        }
        if ( ! $base && $phone ) { $base = sanitize_user( 'customer-' . $phone, true ); }
        if ( ! $base ) { $base = 'customer-' . ( $source_id ? $source_id : wp_rand( 100000, 999999 ) ); }
        $base = substr( $base, 0, 50 );

        $candidate = $base;
        for ( $i = 0; $i < 10000; $i++ ) {
            if ( ! username_exists( $candidate ) ) { return $candidate; }
            $suffix = '-' . ( $source_id ? $source_id : ( $i + 1 ) );
            if ( $i > 0 ) { $suffix .= '-' . ( $i + 1 ); }
            $candidate = substr( $base, 0, max( 1, 60 - strlen( $suffix ) ) ) . $suffix;
        }
        return '';
    }

    private static function empty_stats() {
        return array( 'created'=>0, 'reused'=>0, 'skipped'=>0, 'failed'=>0, 'errors'=>array(), 'id_map'=>array() );
    }

    private static function safe_error( $message ) {
        $message = wp_strip_all_tags( (string) $message );
        return function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 300 ) : substr( $message, 0, 300 );
    }
}
