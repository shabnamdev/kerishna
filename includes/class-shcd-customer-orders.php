<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SHCD_Customer_Orders {
    private static $product_cache = array();

    public static function get_batch( $page = 1, $per_page = 500, $include_customers = true ) {
        $page     = max( 1, absint( $page ) );
        $per_page = min( 500, max( 1, absint( $per_page ) ) );

        $result = wc_get_orders(array(
            'limit'    => $per_page,
            'page'     => $page,
            'paginate' => true,
            'status'   => 'any',
            'orderby'  => 'id',
            'order'    => 'ASC',
            'return'   => 'objects',
        ));

        $orders = array();
        foreach ( $result->orders as $order ) {
            $orders[] = self::serialize_order( $order );
        }
        $customers = $include_customers ? SHCD_Customer_Customers::export_for_orders( $result->orders ) : array();

        return array(
            'orders'      => $orders,
            'customers'   => $customers,
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => absint( $result->total ),
            'total_pages' => absint( $result->max_num_pages ),
            'has_more'    => $page < $result->max_num_pages,
        );
    }

    private static function serialize_order( $order ) {
        $items = array();
        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            $identity = self::product_identity( $item );
            $items[] = array_merge(array(
                'item_id'      => absint( $item_id ),
                // Kept for backward compatibility. These are SOURCE ids only.
                'product_id'   => absint( $item->get_product_id() ),
                'variation_id' => absint( $item->get_variation_id() ),
                'sku'          => $identity['effective_sku'],
                'name'         => SHCD_Customer_Security::clean_text( $item->get_name(), 500 ),
                'quantity'     => (float) $item->get_quantity(),
                'subtotal'     => (string) $item->get_subtotal(),
                'subtotal_tax' => (string) $item->get_subtotal_tax(),
                'total'        => (string) $item->get_total(),
                'total_tax'    => (string) $item->get_total_tax(),
                'taxes'        => self::serialize_tax_data( $item->get_taxes(), true ),
                'item_meta'    => self::serialize_public_item_meta( $item ),
            ), $identity);
        }

        $shipping = array();
        foreach ( $order->get_items( 'shipping' ) as $item ) {
            $shipping[] = array(
                'method_id'    => SHCD_Customer_Security::clean_text( $item->get_method_id(), 100 ),
                'method_title' => SHCD_Customer_Security::clean_text( $item->get_method_title(), 255 ),
                'instance_id'  => method_exists( $item, 'get_instance_id' ) ? absint( $item->get_instance_id() ) : 0,
                'total'        => (string) $item->get_total(),
                'total_tax'    => (string) $item->get_total_tax(),
                'taxes'        => self::serialize_tax_data( $item->get_taxes(), false ),
                'item_meta'    => self::serialize_public_item_meta( $item ),
            );
        }

        $fees = array();
        foreach ( $order->get_items( 'fee' ) as $item ) {
            $fees[] = array(
                'name'       => SHCD_Customer_Security::clean_text( $item->get_name(), 255 ),
                'amount'     => (string) $item->get_amount(),
                'total'      => (string) $item->get_total(),
                'total_tax'  => (string) $item->get_total_tax(),
                'tax_class'  => SHCD_Customer_Security::clean_text( $item->get_tax_class(), 100 ),
                'tax_status' => method_exists( $item, 'get_tax_status' ) ? SHCD_Customer_Security::clean_text( $item->get_tax_status(), 20 ) : '',
                'taxes'      => self::serialize_tax_data( $item->get_taxes(), false ),
                'item_meta'  => self::serialize_public_item_meta( $item ),
            );
        }

        $coupons = array();
        foreach ( $order->get_items( 'coupon' ) as $item ) {
            $coupons[] = array(
                'code'         => SHCD_Customer_Security::clean_text( $item->get_code(), 255 ),
                'discount'     => (string) $item->get_discount(),
                'discount_tax' => (string) $item->get_discount_tax(),
            );
        }

        return array(
            'schema_version'  => 5,
            'source_order_id' => absint( $order->get_id() ),
            'order_number'    => SHCD_Customer_Security::clean_text( $order->get_order_number(), 100 ),
            'status'          => sanitize_key( $order->get_status() ),
            'currency'        => SHCD_Customer_Security::clean_text( $order->get_currency(), 10 ),
            'prices_include_tax'=> (bool) $order->get_prices_include_tax(),
            'date_created'    => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
            'date_paid'       => $order->get_date_paid() ? $order->get_date_paid()->date( 'c' ) : '',
            'date_completed'  => $order->get_date_completed() ? $order->get_date_completed()->date( 'c' ) : '',
            'customer'        => array(
                'user_id'    => absint( $order->get_customer_id() ),
                'email'      => SHCD_Customer_Security::clean_email( $order->get_billing_email() ),
                'phone'      => SHCD_Customer_Security::clean_text( $order->get_billing_phone(), 100 ),
                'first_name' => SHCD_Customer_Security::clean_text( $order->get_billing_first_name(), 100 ),
                'last_name'  => SHCD_Customer_Security::clean_text( $order->get_billing_last_name(), 100 ),
            ),
            'billing'         => self::address( $order->get_address( 'billing' ) ),
            'shipping'        => self::address( $order->get_address( 'shipping' ) ),
            'payment_method'  => SHCD_Customer_Security::clean_text( $order->get_payment_method(), 100 ),
            'payment_title'   => SHCD_Customer_Security::clean_text( $order->get_payment_method_title(), 255 ),
            'transaction_id'  => SHCD_Customer_Security::clean_text( $order->get_transaction_id(), 255 ),
            'customer_note'   => SHCD_Customer_Security::clean_text( $order->get_customer_note(), 2000 ),
            'customer_ip'     => SHCD_Customer_Security::clean_text( $order->get_customer_ip_address(), 64 ),
            'user_agent'      => SHCD_Customer_Security::clean_text( $order->get_customer_user_agent(), 500 ),
            'created_via'     => SHCD_Customer_Security::clean_text( $order->get_created_via(), 100 ),
            'cart_hash'       => SHCD_Customer_Security::clean_text( $order->get_cart_hash(), 255 ),
            'totals'          => array(
                'discount'     => (string) $order->get_discount_total(),
                'discount_tax' => (string) $order->get_discount_tax(),
                'shipping'     => (string) $order->get_shipping_total(),
                'shipping_tax' => (string) $order->get_shipping_tax(),
                'cart_tax'     => (string) $order->get_cart_tax(),
                'total_tax'    => (string) $order->get_total_tax(),
                'total'        => (string) $order->get_total(),
            ),
            'items'          => $items,
            'shipping_items' => $shipping,
            'fees'           => $fees,
            'coupons'        => $coupons,
        );
    }

    private static function serialize_tax_data( $taxes, $include_subtotal = false ) {
        $out = array( 'total' => array() );
        if ( $include_subtotal ) {
            $out['subtotal'] = array();
        }
        if ( ! is_array( $taxes ) ) {
            return $out;
        }
        foreach ( array( 'total', 'subtotal' ) as $bucket ) {
            if ( 'subtotal' === $bucket && ! $include_subtotal ) {
                continue;
            }
            $values = isset( $taxes[ $bucket ] ) && is_array( $taxes[ $bucket ] ) ? $taxes[ $bucket ] : array();
            foreach ( $values as $rate_id => $amount ) {
                $key = is_numeric( $rate_id ) ? (string) absint( $rate_id ) : sanitize_key( (string) $rate_id );
                if ( '' === $key ) {
                    continue;
                }
                $out[ $bucket ][ $key ] = SHCD_Customer_Security::clean_number( $amount );
            }
        }
        return $out;
    }

    private static function product_identity( $item ) {
        $source_product_id   = absint( $item->get_product_id() );
        $source_variation_id = absint( $item->get_variation_id() );
        $parent              = $source_product_id ? wc_get_product( $source_product_id ) : false;
        $effective           = $item->get_product();
        $variation           = ( $effective && $effective->is_type( 'variation' ) ) ? $effective : false;

        if ( ! $parent && $variation && $variation->get_parent_id() ) {
            $parent = wc_get_product( $variation->get_parent_id() );
        }
        if ( ! $effective && $parent ) {
            $effective = $parent;
        }

        $product_sku      = $parent ? (string) $parent->get_sku( 'edit' ) : '';
        $variation_sku    = $variation ? (string) $variation->get_sku( 'edit' ) : '';
        $effective_sku    = $variation_sku ? $variation_sku : ( $effective ? (string) $effective->get_sku( 'edit' ) : $product_sku );
        $product_global   = self::product_global_unique_id( $parent );
        $variation_global = self::product_global_unique_id( $variation );

        return array(
            'source_product_id'          => $source_product_id,
            'source_variation_id'        => $source_variation_id,
            'product_type'               => $effective ? SHCD_Customer_Security::clean_text( $effective->get_type(), 50 ) : '',
            'product_name'               => $parent ? SHCD_Customer_Security::clean_text( $parent->get_name(), 500 ) : '',
            'product_slug'               => $parent ? SHCD_Customer_Security::clean_text( $parent->get_slug(), 255 ) : '',
            'product_sku'                => SHCD_Customer_Security::clean_text( $product_sku, 100 ),
            'variation_sku'              => SHCD_Customer_Security::clean_text( $variation_sku, 100 ),
            'effective_sku'              => SHCD_Customer_Security::clean_text( $effective_sku, 100 ),
            'product_global_unique_id'   => SHCD_Customer_Security::clean_text( $product_global, 100 ),
            'variation_global_unique_id' => SHCD_Customer_Security::clean_text( $variation_global, 100 ),
            'variation_attributes'       => $variation && method_exists( $variation, 'get_variation_attributes' ) ? self::clean_attributes( $variation->get_variation_attributes() ) : array(),
        );
    }

    private static function product_global_unique_id( $product ) {
        if ( ! $product || ! method_exists( $product, 'get_global_unique_id' ) ) {
            return '';
        }
        return (string) $product->get_global_unique_id( 'edit' );
    }

    private static function serialize_public_item_meta( $item ) {
        $out = array();
        foreach ( $item->get_meta_data() as $meta ) {
            $data = method_exists( $meta, 'get_data' ) ? $meta->get_data() : array();
            $key  = isset( $data['key'] ) ? (string) $data['key'] : '';
            if ( '' === $key || 0 === strpos( $key, '_' ) ) {
                continue;
            }
            $out[] = array(
                'key'   => SHCD_Customer_Security::clean_text( $key, 255 ),
                'value' => self::clean_meta_value( isset( $data['value'] ) ? $data['value'] : '' ),
            );
        }
        return $out;
    }

    private static function clean_meta_value( $value, $depth = 0 ) {
        if ( $depth > 3 ) {
            return '';
        }
        if ( is_scalar( $value ) || null === $value ) {
            return SHCD_Customer_Security::clean_text( (string) $value, 2000 );
        }
        if ( is_array( $value ) ) {
            $clean = array();
            foreach ( array_slice( $value, 0, 100, true ) as $key => $item ) {
                $clean_key = is_int( $key ) ? $key : SHCD_Customer_Security::clean_text( $key, 255 );
                $clean[ $clean_key ] = self::clean_meta_value( $item, $depth + 1 );
            }
            return $clean;
        }
        return '';
    }

    private static function clean_attributes( $attributes ) {
        $out = array();
        foreach ( (array) $attributes as $key => $value ) {
            $key = SHCD_Customer_Security::clean_text( $key, 255 );
            if ( '' === $key ) { continue; }
            $out[ $key ] = SHCD_Customer_Security::clean_text( $value, 500 );
        }
        return $out;
    }

    private static function address( $address ) {
        $keys = array( 'first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone' );
        $out = array();
        foreach ( $keys as $key ) {
            $value = isset( $address[ $key ] ) ? $address[ $key ] : '';
            $out[ $key ] = ( 'email' === $key ) ? SHCD_Customer_Security::clean_email( $value ) : SHCD_Customer_Security::clean_text( $value, 500 );
        }
        return $out;
    }

    public static function import_batch( $payload, $source_hash, $settings ) {
        $orders = isset( $payload['orders'] ) && is_array( $payload['orders'] ) ? $payload['orders'] : array();
        if ( count( $orders ) > 500 ) {
            return array( 'created'=>0,'updated'=>0,'skipped'=>0,'failed'=>count($orders),'errors'=>array(array('order'=>0,'message'=>'حداکثر 500 سفارش در هر بسته داخلی مجاز است.')),'warnings'=>array(),'warning_count'=>0,'id_map'=>array() );
        }

        $stats = array( 'created'=>0, 'updated'=>0, 'skipped'=>0, 'failed'=>0, 'errors'=>array(), 'warnings'=>array(), 'warning_count'=>0, 'id_map'=>array() );
        foreach ( $orders as $data ) {
            try {
                $result = self::import_one( $data, $source_hash, $settings );
                $bucket = isset( $result['result'] ) ? $result['result'] : 'updated';
                if ( isset( $stats[ $bucket ] ) ) {
                    $stats[ $bucket ]++;
                }
                foreach ( (array) ( isset( $result['warnings'] ) ? $result['warnings'] : array() ) as $warning ) {
                    $stats['warning_count']++;
                    if ( count( $stats['warnings'] ) < 50 ) {
                        $stats['warnings'][] = array(
                            'order'   => absint( $result['source_order_id'] ),
                            'message' => self::safe_error( $warning ),
                        );
                    }
                }
                if ( count( $stats['id_map'] ) < 100 ) {
                    $stats['id_map'][] = array(
                        'source_order_id'      => absint( $result['source_order_id'] ),
                        'destination_order_id' => absint( $result['destination_order_id'] ),
                    );
                }
            } catch ( Throwable $e ) {
                $stats['failed']++;
                if ( count( $stats['errors'] ) < 20 ) {
                    $stats['errors'][] = array(
                        'order'   => isset( $data['source_order_id'] ) ? absint( $data['source_order_id'] ) : 0,
                        'message' => self::safe_error( $e->getMessage() ),
                    );
                }
            }
        }
        return $stats;
    }

    private static function import_one( $data, $source_hash, $settings ) {
        if ( ! is_array( $data ) ) { throw new Exception( 'ساختار سفارش نامعتبر است.' ); }
        $source_id = isset( $data['source_order_id'] ) ? absint( $data['source_order_id'] ) : 0;
        if ( ! $source_id ) { throw new Exception( 'شناسه سفارش مبدا نامعتبر است.' ); }

        // Resolve every product BEFORE touching an existing destination order.
        // Source numeric IDs are never trusted as destination IDs.
        $prepared_items = self::prepare_line_items(
            (array) ( isset( $data['items'] ) ? $data['items'] : array() ),
            $source_hash,
            true
        );
        $import_warnings = array();
        foreach ( $prepared_items as $prepared ) {
            if ( ! empty( $prepared['warning'] ) ) {
                $import_warnings[] = $prepared['warning'];
            }
        }

        $existing = self::find_existing( $source_id, $source_hash );
        $created  = false;
        $order    = $existing;
        $tx       = false;

        try {
            if ( function_exists( 'wc_transaction_query' ) ) {
                wc_transaction_query( 'start' );
                $tx = true;
            }

            if ( ! $order ) {
                // Build a new destination order in memory and persist it once, after all
                // source data has been mapped. This avoids creating an empty placeholder
                // order before product validation/import has completed.
                $order = new WC_Order();
                $created = true;
            }

            $customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : array();
            $billing  = isset($data['billing']) && is_array($data['billing']) ? $data['billing'] : array();
            $shipping_address = isset($data['shipping']) && is_array($data['shipping']) ? $data['shipping'] : array();
            $registered_customer = isset($data['registered_customer']) && is_array($data['registered_customer']) ? $data['registered_customer'] : array();
            // Always attempt to reuse a matching destination account. The customer
            // helper only creates a new account when customer creation is explicitly
            // enabled; order-only imports therefore never create users as a side effect.
            $customer_id = SHCD_Customer_Customers::resolve_for_order(
                $customer,
                $billing,
                $shipping_address,
                $registered_customer,
                $source_hash,
                $settings
            );
            if ( $customer_id ) { $order->set_customer_id( $customer_id ); }

            $order->set_currency( SHCD_Customer_Security::clean_text( isset($data['currency']) ? $data['currency'] : get_woocommerce_currency(), 10 ) );
            if ( method_exists( $order, 'set_prices_include_tax' ) && array_key_exists( 'prices_include_tax', $data ) ) {
                $order->set_prices_include_tax( ! empty( $data['prices_include_tax'] ) );
            }
            $order->set_payment_method( SHCD_Customer_Security::clean_text( isset($data['payment_method']) ? $data['payment_method'] : '', 100 ) );
            $order->set_payment_method_title( SHCD_Customer_Security::clean_text( isset($data['payment_title']) ? $data['payment_title'] : '', 255 ) );
            $order->set_transaction_id( SHCD_Customer_Security::clean_text( isset($data['transaction_id']) ? $data['transaction_id'] : '', 255 ) );
            $order->set_customer_note( SHCD_Customer_Security::clean_text( isset($data['customer_note']) ? $data['customer_note'] : '', 2000 ) );
            $order->set_customer_ip_address( SHCD_Customer_Security::clean_text( isset($data['customer_ip']) ? $data['customer_ip'] : '', 64 ) );
            $order->set_customer_user_agent( SHCD_Customer_Security::clean_text( isset($data['user_agent']) ? $data['user_agent'] : '', 500 ) );
            $order->set_created_via( SHCD_Customer_Security::clean_text( isset($data['created_via']) ? $data['created_via'] : 'shcd-kerishna', 100 ) );
            $order->set_cart_hash( SHCD_Customer_Security::clean_text( isset($data['cart_hash']) ? $data['cart_hash'] : '', 255 ) );
            $order->set_address( self::clean_address( $billing ), 'billing' );
            $order->set_address( self::clean_address( $shipping_address ), 'shipping' );

            self::clear_items( $order );
            foreach ( $prepared_items as $prepared ) {
                self::add_line_item( $order, $prepared['data'], $prepared['product'], $prepared['match_method'], $source_hash );
            }
            foreach ( (array) ( isset($data['shipping_items']) ? $data['shipping_items'] : array() ) as $shipping_data ) { self::add_shipping_item( $order, $shipping_data ); }
            foreach ( (array) ( isset($data['fees']) ? $data['fees'] : array() ) as $fee_data ) { self::add_fee_item( $order, $fee_data ); }
            foreach ( (array) ( isset($data['coupons']) ? $data['coupons'] : array() ) as $coupon_data ) { self::add_coupon_item( $order, $coupon_data ); }

            $totals = isset($data['totals']) && is_array($data['totals']) ? $data['totals'] : array();
            self::apply_totals( $order, $totals );
            if ( method_exists( $order, 'calculate_totals' ) ) {
                $order->calculate_totals( false );
            }
            self::apply_totals( $order, $totals );
            self::apply_dates( $order, $data );

            $status = sanitize_key( isset($data['status']) ? $data['status'] : 'pending' );
            if ( in_array( 'wc-' . $status, array_keys( wc_get_order_statuses() ), true ) ) {
                $order->set_status( $status );
            }

            // Destination-only audit mapping. The source order remains untouched.
            $order->update_meta_data( '_shcd_source_order_id', $source_id );
            $order->update_meta_data( '_shcd_source_site', $source_hash );
            $order->update_meta_data( '_shcd_source_order_number', SHCD_Customer_Security::clean_text( isset($data['order_number']) ? $data['order_number'] : '', 100 ) );
            $order->update_meta_data( '_shcd_source_customer_id', absint( isset($customer['user_id']) ? $customer['user_id'] : 0 ) );
            $order->update_meta_data( '_shcd_source_status', $status );
            $order->update_meta_data( '_shcd_imported_by', 'shcd-kerishna' );
            $order->update_meta_data( '_shcd_import_schema', absint( isset($data['schema_version']) ? $data['schema_version'] : 2 ) );
            $destination_id = $order->save();

            if ( $tx ) {
                wc_transaction_query( 'commit' );
                $tx = false;
            }

            return array(
                'result'               => $created ? 'created' : 'updated',
                'source_order_id'      => $source_id,
                'destination_order_id' => absint( $destination_id ? $destination_id : $order->get_id() ),
                'warnings'             => $import_warnings,
            );
        } catch ( Throwable $e ) {
            if ( $tx && function_exists( 'wc_transaction_query' ) ) {
                wc_transaction_query( 'rollback' );
                $tx = false;
            }
            // COPY/BACKUP SAFETY POLICY:
            // Never delete any WooCommerce order from either site. In particular, do not
            // hard-delete a partially-created destination order as an error-cleanup step.
            // The source-side export/transfer path is read-only and never reaches this code.
            throw $e;
        }
    }

    private static function prepare_line_items( $items, $source_hash, $allow_missing = true ) {
        $prepared = array();
        foreach ( $items as $data ) {
            if ( ! is_array( $data ) ) { continue; }
            $resolved = self::resolve_destination_product( $data, $source_hash );
            $warning  = '';
            if ( ! $resolved['product'] ) {
                // Backup semantics: an unavailable destination catalog entry must never make
                // the source order fail. Keep the commercial line as an archived order item
                // with destination product_id/variation_id = 0 and source identity in meta.
                $label = SHCD_Customer_Security::clean_text( isset($data['name']) ? $data['name'] : '', 500 );
                $sku   = SHCD_Customer_Security::clean_text( isset($data['variation_sku']) && $data['variation_sku'] ? $data['variation_sku'] : ( isset($data['sku']) ? $data['sku'] : '' ), 100 );
                $warning = 'محصول مقصد پیدا نشد و آیتم به‌صورت بکاپ مستقل ثبت شد: ' . ( $sku ? 'SKU ' . $sku : ( $label ? $label : 'بدون شناسه پایدار' ) );
            }
            $prepared[] = array(
                'data'         => $data,
                'product'      => $resolved['product'],
                'match_method' => $resolved['product'] ? $resolved['method'] : 'unmatched_backup_item',
                'warning'      => $warning,
            );
        }
        return $prepared;
    }

    private static function resolve_destination_product( $data, $source_hash ) {
        $source_product_id   = absint( isset($data['source_product_id']) ? $data['source_product_id'] : ( isset($data['product_id']) ? $data['product_id'] : 0 ) );
        $source_variation_id = absint( isset($data['source_variation_id']) ? $data['source_variation_id'] : ( isset($data['variation_id']) ? $data['variation_id'] : 0 ) );
        $cache_key = $source_hash . ':' . $source_product_id . ':' . $source_variation_id . ':' . md5( wp_json_encode(array(
            isset($data['variation_sku']) ? $data['variation_sku'] : '',
            isset($data['product_sku']) ? $data['product_sku'] : '',
            isset($data['sku']) ? $data['sku'] : '',
            isset($data['product_slug']) ? $data['product_slug'] : '',
            isset($data['variation_attributes']) ? $data['variation_attributes'] : array(),
        )) );
        if ( isset( self::$product_cache[ $cache_key ] ) ) {
            return self::$product_cache[ $cache_key ];
        }

        $expects_variation = $source_variation_id > 0 || ! empty( $data['variation_attributes'] ) || ( isset($data['product_type']) && 'variation' === $data['product_type'] );
        $variation_sku = SHCD_Customer_Security::clean_text( isset($data['variation_sku']) ? $data['variation_sku'] : '', 100 );
        $legacy_sku    = SHCD_Customer_Security::clean_text( isset($data['sku']) ? $data['sku'] : '', 100 );
        $product_sku   = SHCD_Customer_Security::clean_text( isset($data['product_sku']) ? $data['product_sku'] : '', 100 );

        if ( $variation_sku ) {
            $product = self::product_by_sku( $variation_sku );
            if ( $product && $product->is_type( 'variation' ) ) {
                return self::$product_cache[ $cache_key ] = array( 'product'=>$product, 'method'=>'variation_sku' );
            }
        }

        $sku_parent_candidate = false;
        if ( $legacy_sku ) {
            $product = self::product_by_sku( $legacy_sku );
            if ( $product && ( ! $expects_variation || $product->is_type( 'variation' ) ) ) {
                return self::$product_cache[ $cache_key ] = array( 'product'=>$product, 'method'=>'sku' );
            }
            if ( $product && $expects_variation && ! $product->is_type( 'variation' ) ) {
                // A variation can have no own SKU; the legacy/effective SKU may therefore
                // identify its parent. Resolve the child by attributes instead of guessing.
                $sku_parent_candidate = $product;
            }
        }

        $variation_global = SHCD_Customer_Security::clean_text( isset($data['variation_global_unique_id']) ? $data['variation_global_unique_id'] : '', 100 );
        if ( $variation_global ) {
            $product = self::product_by_global_unique_id( $variation_global );
            if ( $product && $product->is_type( 'variation' ) ) {
                return self::$product_cache[ $cache_key ] = array( 'product'=>$product, 'method'=>'variation_global_unique_id' );
            }
        }

        $parent = $sku_parent_candidate;
        if ( ! $parent && $product_sku ) {
            $candidate = self::product_by_sku( $product_sku );
            if ( $candidate && ! $candidate->is_type( 'variation' ) ) {
                $parent = $candidate;
            }
        }

        if ( ! $parent ) {
            $product_global = SHCD_Customer_Security::clean_text( isset($data['product_global_unique_id']) ? $data['product_global_unique_id'] : '', 100 );
            if ( $product_global ) {
                $candidate = self::product_by_global_unique_id( $product_global );
                if ( $candidate && ! $candidate->is_type( 'variation' ) ) {
                    $parent = $candidate;
                }
            }
        }

        if ( ! $parent ) {
            $slug = sanitize_title( isset($data['product_slug']) ? $data['product_slug'] : '' );
            if ( $slug ) {
                $post = get_page_by_path( $slug, OBJECT, 'product' );
                if ( $post ) {
                    $candidate = wc_get_product( $post->ID );
                    if ( $candidate ) { $parent = $candidate; }
                }
            }
        }

        if ( ! $parent ) {
            $product_name = SHCD_Customer_Security::clean_text( isset($data['product_name']) ? $data['product_name'] : '', 500 );
            if ( $product_name ) {
                $parent = self::find_unique_product_by_name( $product_name, false );
            }
        }

        if ( $expects_variation && $parent ) {
            $attrs = isset($data['variation_attributes']) && is_array($data['variation_attributes']) ? self::clean_attributes($data['variation_attributes']) : array();
            if ( $attrs ) {
                $variation = self::find_matching_variation( $parent, $attrs );
                if ( $variation ) {
                    return self::$product_cache[ $cache_key ] = array( 'product'=>$variation, 'method'=>'parent_identity+variation_attributes' );
                }
            }
        }

        if ( ! $expects_variation && $parent ) {
            return self::$product_cache[ $cache_key ] = array( 'product'=>$parent, 'method'=>'product_identity' );
        }

        // Backward-compatible fallback for old schema v2 files: exact unique item name.
        // This is intentionally conservative and never assumes that equal numeric IDs mean equal products.
        $item_name = SHCD_Customer_Security::clean_text( isset($data['name']) ? $data['name'] : '', 500 );
        if ( $item_name ) {
            $candidate = self::find_unique_product_by_name( $item_name, $expects_variation );
            if ( $candidate ) {
                return self::$product_cache[ $cache_key ] = array( 'product'=>$candidate, 'method'=>'exact_unique_name' );
            }
        }

        return self::$product_cache[ $cache_key ] = array( 'product'=>false, 'method'=>'unmatched' );
    }

    private static function product_by_sku( $sku ) {
        $id = wc_get_product_id_by_sku( $sku );
        return $id ? wc_get_product( $id ) : false;
    }

    private static function product_by_global_unique_id( $value ) {
        if ( ! $value || ! function_exists( 'wc_get_product_id_by_global_unique_id' ) ) {
            return false;
        }
        $id = wc_get_product_id_by_global_unique_id( $value );
        return $id ? wc_get_product( $id ) : false;
    }

    private static function find_matching_variation( $parent, $attributes ) {
        if ( ! $parent || ! $parent->is_type( 'variable' ) || ! $attributes ) {
            return false;
        }

        $exact = array();
        $loose = array();
        foreach ( (array) $parent->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation || ! $variation->is_type( 'variation' ) || ! method_exists( $variation, 'get_variation_attributes' ) ) {
                continue;
            }
            $stored = $variation->get_variation_attributes();
            $matches = true;
            $has_wildcard = false;
            foreach ( $stored as $key => $value ) {
                if ( '' === (string) $value ) {
                    $has_wildcard = true;
                    continue;
                }
                if ( ! isset( $attributes[ $key ] ) || (string) $attributes[ $key ] !== (string) $value ) {
                    $matches = false;
                    break;
                }
            }
            if ( ! $matches ) { continue; }
            if ( $has_wildcard ) { $loose[] = $variation; } else { $exact[] = $variation; }
        }

        // Prefer one unambiguous exact variation over "Any" wildcard variations.
        if ( 1 === count( $exact ) ) { return $exact[0]; }
        if ( count( $exact ) > 1 ) { return false; }
        return 1 === count( $loose ) ? $loose[0] : false;
    }

    private static function find_unique_product_by_name( $name, $variation_only ) {
        $name = trim( (string) $name );
        if ( '' === $name ) { return false; }

        $post_types = $variation_only ? array( 'product_variation' ) : array( 'product' );
        $ids = get_posts(
            array(
                'post_type'              => $post_types,
                'post_status'            => 'any',
                'posts_per_page'         => 3,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'title'                  => $name,
                'suppress_filters'       => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        if ( 1 !== count( $ids ) ) { return false; }
        $product = wc_get_product( absint( $ids[0] ) );
        if ( ! $product ) { return false; }
        if ( $variation_only && ! $product->is_type( 'variation' ) ) { return false; }
        if ( ! $variation_only && $product->is_type( 'variation' ) ) { return false; }
        return $product;
    }

    private static function clear_items( $order ) {
        foreach ( array('line_item','shipping','fee','coupon') as $type ) {
            foreach ( $order->get_items( $type ) as $item ) {
                $order->remove_item( $item->get_id() );
            }
        }
    }

    private static function apply_totals( $order, $totals ) {
        $order->set_discount_total( SHCD_Customer_Security::clean_number(isset($totals['discount'])?$totals['discount']:0) );
        $order->set_discount_tax( SHCD_Customer_Security::clean_number(isset($totals['discount_tax'])?$totals['discount_tax']:0) );
        $order->set_shipping_total( SHCD_Customer_Security::clean_number(isset($totals['shipping'])?$totals['shipping']:0) );
        $order->set_shipping_tax( SHCD_Customer_Security::clean_number(isset($totals['shipping_tax'])?$totals['shipping_tax']:0) );
        $order->set_cart_tax( SHCD_Customer_Security::clean_number(isset($totals['cart_tax'])?$totals['cart_tax']:0) );
        $order->set_total( SHCD_Customer_Security::clean_number(isset($totals['total'])?$totals['total']:0) );
    }

    private static function apply_dates( $order, $data ) {
        foreach ( array('date_created'=>'set_date_created','date_paid'=>'set_date_paid','date_completed'=>'set_date_completed') as $key => $setter ) {
            if ( ! empty($data[$key]) && function_exists('wc_string_to_datetime') ) {
                try { $order->{$setter}( wc_string_to_datetime( SHCD_Customer_Security::clean_text($data[$key], 64) ) ); } catch ( Throwable $e ) { /* optional date */ }
            }
        }
    }

    private static function find_existing( $source_id, $source_hash ) {
        $orders = wc_get_orders(array(
            'limit'      => 1,
            'return'     => 'objects',
            'meta_query' => array(
                array('key'=>'_shcd_source_order_id','value'=>$source_id,'compare'=>'='),
                array('key'=>'_shcd_source_site','value'=>$source_hash,'compare'=>'='),
            ),
        ));
        return ! empty($orders) ? $orders[0] : false;
    }

    private static function add_line_item( $order, $data, $product, $match_method, $source_hash ) {
        if ( ! is_array($data) ) { return; }
        $qty = max( 0.000001, (float) ( isset($data['quantity']) ? $data['quantity'] : 1 ) );
        $item = new WC_Order_Item_Product();

        if ( $product ) {
            // Do not overwrite product_id after set_product(). For variations WooCommerce
            // correctly stores parent product_id + destination variation_id itself.
            $item->set_product( $product );
        }
        $item->set_name( SHCD_Customer_Security::clean_text( isset($data['name']) ? $data['name'] : 'محصول واردشده', 500 ) );
        $item->set_quantity( $qty );
        $item->set_subtotal( SHCD_Customer_Security::clean_number(isset($data['subtotal'])?$data['subtotal']:0) );
        $item->set_total( SHCD_Customer_Security::clean_number(isset($data['total'])?$data['total']:0) );
        self::apply_item_taxes( $item, $data, true );

        self::apply_public_item_meta( $item, isset($data['item_meta']) ? $data['item_meta'] : array() );

        $source_product_id   = absint( isset($data['source_product_id']) ? $data['source_product_id'] : ( isset($data['product_id']) ? $data['product_id'] : 0 ) );
        $source_variation_id = absint( isset($data['source_variation_id']) ? $data['source_variation_id'] : ( isset($data['variation_id']) ? $data['variation_id'] : 0 ) );
        $item->add_meta_data( '_shcd_source_site', $source_hash, true );
        $item->add_meta_data( '_shcd_source_item_id', absint(isset($data['item_id'])?$data['item_id']:0), true );
        $item->add_meta_data( '_shcd_source_product_id', $source_product_id, true );
        $item->add_meta_data( '_shcd_source_variation_id', $source_variation_id, true );
        $item->add_meta_data( '_shcd_product_match_method', SHCD_Customer_Security::clean_text($match_method,100), true );
        if ( $product ) {
            $item->add_meta_data( '_shcd_destination_product_id', absint($product->get_id()), true );
        } else {
            $sku = SHCD_Customer_Security::clean_text( isset($data['variation_sku']) && $data['variation_sku'] ? $data['variation_sku'] : ( isset($data['sku']) ? $data['sku'] : '' ), 100 );
            if ( $sku ) { $item->add_meta_data( '_shcd_source_sku', $sku, true ); }
            $item->add_meta_data( '_shcd_imported_product_name', SHCD_Customer_Security::clean_text(isset($data['name'])?$data['name']:'',500), true );
            $item->add_meta_data( '_shcd_unmatched_product', 'yes', true );
        }
        $order->add_item( $item );
    }

    private static function apply_item_taxes( $item, $data, $include_subtotal ) {
        if ( ! method_exists( $item, 'set_taxes' ) ) {
            return;
        }

        $taxes = isset( $data['taxes'] ) && is_array( $data['taxes'] )
            ? self::serialize_tax_data( $data['taxes'], $include_subtotal )
            : array( 'total' => array() );
        if ( $include_subtotal && ! isset( $taxes['subtotal'] ) ) {
            $taxes['subtotal'] = array();
        }

        // Backward compatibility for schema <= 3 exports: synthesize one legacy bucket
        // from the aggregate amounts so old JSON files import without protected setters.
        if ( empty( $taxes['total'] ) ) {
            $legacy_total = SHCD_Customer_Security::clean_number( isset($data['total_tax']) ? $data['total_tax'] : 0 );
            if ( (float) $legacy_total != 0.0 ) {
                $taxes['total'] = array( '0' => $legacy_total );
            }
        }
        if ( $include_subtotal && empty( $taxes['subtotal'] ) ) {
            $legacy_subtotal = SHCD_Customer_Security::clean_number( isset($data['subtotal_tax']) ? $data['subtotal_tax'] : ( isset($data['total_tax']) ? $data['total_tax'] : 0 ) );
            if ( (float) $legacy_subtotal != 0.0 ) {
                $taxes['subtotal'] = array( '0' => $legacy_subtotal );
            } elseif ( ! empty( $taxes['total'] ) ) {
                // WC_Order_Item_Product::set_taxes() expects subtotal >= total and older
                // WooCommerce versions require both buckets when tax exists.
                $taxes['subtotal'] = $taxes['total'];
            }
        }

        $item->set_taxes( $taxes );
    }

    private static function apply_public_item_meta( $item, $meta_rows ) {
        foreach ( (array) $meta_rows as $meta ) {
            if ( ! is_array( $meta ) ) { continue; }
            $key = SHCD_Customer_Security::clean_text( isset($meta['key']) ? $meta['key'] : '', 255 );
            if ( '' === $key || 0 === strpos( $key, '_shcd_' ) || 0 === strpos( $key, '_' ) ) { continue; }
            $item->add_meta_data( $key, self::clean_meta_value( isset($meta['value']) ? $meta['value'] : '' ), false );
        }
    }

    private static function add_shipping_item( $order, $data ) {
        if ( ! is_array($data) ) { return; }
        $item = new WC_Order_Item_Shipping();
        $item->set_method_id( SHCD_Customer_Security::clean_text(isset($data['method_id'])?$data['method_id']:'shcd',100) );
        $item->set_method_title( SHCD_Customer_Security::clean_text(isset($data['method_title'])?$data['method_title']:'هزینه ارسال',255) );
        if ( method_exists( $item, 'set_instance_id' ) && isset( $data['instance_id'] ) ) {
            $item->set_instance_id( absint( $data['instance_id'] ) );
        }
        $item->set_total( SHCD_Customer_Security::clean_number(isset($data['total'])?$data['total']:0) );
        // WC_Order_Item_Shipping::set_total_tax() is protected; set_taxes() is public.
        self::apply_item_taxes( $item, $data, false );
        self::apply_public_item_meta( $item, isset($data['item_meta']) ? $data['item_meta'] : array() );
        $order->add_item( $item );
    }

    private static function add_fee_item( $order, $data ) {
        if ( ! is_array($data) ) { return; }
        $item = new WC_Order_Item_Fee();
        $item->set_name( SHCD_Customer_Security::clean_text(isset($data['name'])?$data['name']:'هزینه',255) );
        $item->set_amount( SHCD_Customer_Security::clean_number(isset($data['amount'])?$data['amount']:0) );
        $item->set_total( SHCD_Customer_Security::clean_number(isset($data['total'])?$data['total']:0) );
        $item->set_tax_class( SHCD_Customer_Security::clean_text(isset($data['tax_class'])?$data['tax_class']:'',100) );
        if ( method_exists( $item, 'set_tax_status' ) && ! empty( $data['tax_status'] ) ) {
            $item->set_tax_status( SHCD_Customer_Security::clean_text($data['tax_status'],20) );
        }
        self::apply_item_taxes( $item, $data, false );
        self::apply_public_item_meta( $item, isset($data['item_meta']) ? $data['item_meta'] : array() );
        $order->add_item( $item );
    }

    private static function add_coupon_item( $order, $data ) {
        if ( ! is_array($data) ) { return; }
        $item = new WC_Order_Item_Coupon();
        $item->set_code( SHCD_Customer_Security::clean_text(isset($data['code'])?$data['code']:'',255) );
        $item->set_discount( SHCD_Customer_Security::clean_number(isset($data['discount'])?$data['discount']:0) );
        $item->set_discount_tax( SHCD_Customer_Security::clean_number(isset($data['discount_tax'])?$data['discount_tax']:0) );
        $order->add_item( $item );
    }

    private static function clean_address( $address ) {
        $keys = array('first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone');
        $out = array();
        foreach ( $keys as $key ) {
            $value = isset($address[$key]) ? $address[$key] : '';
            $out[$key] = 'email' === $key ? SHCD_Customer_Security::clean_email($value) : SHCD_Customer_Security::clean_text($value,500);
        }
        return $out;
    }

    private static function safe_error( $message ) {
        $message = wp_strip_all_tags((string)$message);
        return function_exists('mb_substr') ? mb_substr($message,0,300) : substr($message,0,300);
    }
}
