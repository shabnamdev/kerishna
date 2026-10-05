<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SHCD_Customer_API {
    const NS = 'shcd-kerishna/v1';
    const LEGACY_NS = 'shcd-customer/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
    }

    public static function routes() {
        // The new namespace follows the public plugin slug. The legacy namespace is
        // retained so older JSON/direct-copy clients can still reach this release.
        foreach ( array( self::NS, self::LEGACY_NS ) as $namespace ) {
            self::register_namespace_routes( $namespace );
        }
    }

    private static function register_namespace_routes( $namespace ) {
        register_rest_route($namespace,'/status',array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'status'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/settings',array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array(__CLASS__,'save_settings'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/credentials/generate',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'generate_credentials'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/export-batch',array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'export_batch'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/export-customers-batch',array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'export_customers_batch'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/import',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'import_file'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/import-batch',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'import_json_batch'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/import-csv',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'import_csv_file'),'permission_callback'=>array(__CLASS__,'admin_permission')));
        register_rest_route($namespace,'/receive',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'receive_remote'),'permission_callback'=>'__return_true'));
        register_rest_route($namespace,'/transfer-batch',array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'transfer_batch'),'permission_callback'=>array(__CLASS__,'admin_permission')));
    }

    public static function admin_permission( $request ) {
        return SHCD_Customer_Security::capability() && SHCD_Customer_Security::verify_nonce( $request );
    }

    public static function status() {
        $settings = SHCD_Customer_Security::settings();
        return rest_ensure_response(array(
            'success' => true,
            'version' => SHCD_CUSTOMER_VERSION,
            'storage' => SHCD_Customer_Storage::detect(),
            'settings'=> array(
                'destination_url'       => esc_url_raw($settings['destination_url']),
                'batch_size'            => absint($settings['batch_size']),
                'create_customers'      => !empty($settings['create_customers']),
                'create_missing_products'=> true,
                'has_credentials'       => !empty($settings['api_key'])&&!empty($settings['api_secret']),
                'api_key'               => (string)$settings['api_key'],
                'api_secret'            => (string)$settings['api_secret'],
            ),
        ));
    }

    public static function save_settings( $request ) {
        $current = SHCD_Customer_Security::settings();
        $data    = $request->get_json_params();
        $data    = is_array($data) ? $data : array();
        $url     = SHCD_Customer_Security::valid_destination_url(isset($data['destination_url'])?$data['destination_url']:'');
        if ( false === $url && ! empty($data['destination_url']) ) {
            return new WP_Error('shcd_destination_url','آدرس مقصد معتبر نیست.',array('status'=>400));
        }
        $current['destination_url'] = $url ? $url : '';

        // Empty credential fields intentionally preserve the previously saved values.
        if ( isset($data['destination_api_key']) && '' !== trim((string)$data['destination_api_key']) ) {
            $current['destination_api_key'] = SHCD_Customer_Security::clean_text($data['destination_api_key'],255);
        }
        if ( isset($data['destination_api_secret']) && '' !== trim((string)$data['destination_api_secret']) ) {
            $current['destination_api_secret'] = SHCD_Customer_Security::clean_text($data['destination_api_secret'],255);
        }

        $current['batch_size'] = min(500,max(1,absint(isset($data['batch_size'])?$data['batch_size']:500)));
        $current['create_customers'] = !empty($data['create_customers']) ? 1 : 0;
        $current['create_missing_products'] = 1; // Backup mode always preserves unmatched order items.
        SHCD_Customer_Security::save_settings($current);
        return rest_ensure_response(array('success'=>true));
    }

    public static function generate_credentials() {
        $settings = SHCD_Customer_Security::settings();
        $settings['api_key']    = SHCD_Customer_Security::random_token(24);
        $settings['api_secret'] = SHCD_Customer_Security::random_token(48);
        SHCD_Customer_Security::save_settings($settings);
        return rest_ensure_response(array('success'=>true,'api_key'=>$settings['api_key'],'api_secret'=>$settings['api_secret']));
    }

    public static function export_batch( $request ) {
        $page      = max(1,absint($request->get_param('page')));
        $settings  = SHCD_Customer_Security::settings();
        $per_page  = min(500,max(1,absint($request->get_param('per_page')?$request->get_param('per_page'):$settings['batch_size'])));
        // SOURCE READ-ONLY: get_batch() serializes orders only; it never mutates source data.
        $batch     = SHCD_Customer_Orders::get_batch( $page, $per_page, false );
        $batch['plugin']         = 'shcd-kerishna';
        $batch['version']        = SHCD_CUSTOMER_VERSION;
        $batch['schema_version'] = 6;
        $batch['data_type']      = 'orders';
        $batch['transfer_mode']  = 'copy_backup';
        $batch['source_read_only']= true;
        $batch['site_hash']      = self::site_hash();
        return rest_ensure_response($batch);
    }

    public static function export_customers_batch( $request ) {
        $page      = max( 1, absint( $request->get_param( 'page' ) ) );
        $settings  = SHCD_Customer_Security::settings();
        $per_page  = min( 500, max( 1, absint( $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : $settings['batch_size'] ) ) );
        // SOURCE READ-ONLY: customer profiles are serialized through WordPress user APIs only.
        $batch = SHCD_Customer_Customers::get_batch( $page, $per_page );
        $batch['plugin']          = 'shcd-kerishna';
        $batch['version']         = SHCD_CUSTOMER_VERSION;
        $batch['schema_version']  = 6;
        $batch['data_type']       = 'customers';
        $batch['transfer_mode']   = 'copy_backup';
        $batch['source_read_only']= true;
        $batch['site_hash']       = self::site_hash();
        return rest_ensure_response( $batch );
    }

    public static function import_file( $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file']['tmp_name'] ) || ! empty( $files['file']['error'] ) ) {
            return new WP_Error( 'shcd_file', 'فایل JSON معتبر ارسال نشده است.', array( 'status' => 400 ) );
        }

        $raw = self::read_uploaded_file( $files['file']['tmp_name'], 'JSON' );
        if ( is_wp_error( $raw ) ) { return $raw; }

        $raw     = self::strip_utf8_bom( $raw );
        $payload = json_decode( $raw, true, 512, JSON_BIGINT_AS_STRING );
        if ( ! is_array( $payload ) ) {
            return new WP_Error( 'shcd_json', 'ساختار JSON نامعتبر است: ' . self::json_error_message(), array( 'status' => 400 ) );
        }
        $result = self::import_payload( $payload, true );
        return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
    }

    public static function import_json_batch( $request ) {
        $payload = $request->get_json_params();
        if ( ! is_array($payload) ) {
            return new WP_Error('shcd_json','ساختار JSON نامعتبر است.',array('status'=>400));
        }
        $result = self::import_payload($payload,false);
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function import_csv_file( $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file']['tmp_name'] ) || ! empty( $files['file']['error'] ) ) {
            return new WP_Error( 'shcd_file', 'فایل CSV معتبر ارسال نشده است.', array( 'status' => 400 ) );
        }

        $raw = self::read_uploaded_file( $files['file']['tmp_name'], 'CSV' );
        if ( is_wp_error( $raw ) ) { return $raw; }
        $raw = self::strip_utf8_bom( $raw );
        if ( '' === trim( $raw ) ) {
            return new WP_Error( 'shcd_csv_empty', 'فایل CSV خالی است.', array( 'status' => 400 ) );
        }

        $rows = iterator_to_array( self::parse_csv_rows( $raw ), false );
        if ( empty( $rows ) || ! isset( $rows[0] ) ) {
            return new WP_Error( 'shcd_csv_empty', 'فایل CSV خالی است.', array( 'status' => 400 ) );
        }

        $headers = array_map( 'trim', (array) $rows[0] );
        $is_orders = in_array( 'source_order_id', $headers, true );
        $is_customers = in_array( 'source_user_id', $headers, true );
        if ( ! $is_orders && ! $is_customers ) {
            return new WP_Error( 'shcd_csv_format', 'نوع فایل CSV قابل تشخیص نیست. فایل باید خروجی سفارش‌ها یا مشتریان Kerishna باشد.', array( 'status' => 400 ) );
        }

        $settings       = SHCD_Customer_Security::settings();
        $source_hash    = '';
        $row_no         = 1;
        $stats          = self::empty_stats();
        $customer_stats = self::empty_customer_stats();
        $order_batch    = array();
        $customer_batch = array();
        $legacy_combined = $is_orders && in_array( 'customer_account', $headers, true );

        // New order-only CSV files must not create new customer accounts. Old combined
        // CSV exports keep their legacy behavior for backward compatibility.
        $order_settings = $settings;
        if ( $is_orders && ! $legacy_combined ) {
            $order_settings['create_customers'] = 0;
        }

        foreach ( array_slice( $rows, 1 ) as $row ) {
            $row_no++;
            if ( 1 === count( $row ) && '' === trim( (string) $row[0] ) ) { continue; }

            $mapped = array();
            foreach ( $headers as $i => $header ) {
                $mapped[ $header ] = isset( $row[ $i ] ) ? (string) $row[ $i ] : '';
            }

            $row_source_hash = SHCD_Customer_Security::clean_text( isset( $mapped['source_site_hash'] ) ? $mapped['source_site_hash'] : '', 128 );
            if ( ! $row_source_hash || ! preg_match( '/^[a-f0-9]{64}$/', $row_source_hash ) ) {
                if ( $is_orders ) {
                    $stats['failed']++;
                    if ( count( $stats['errors'] ) < 20 ) {
                        $stats['errors'][] = array( 'order'=>absint( isset($mapped['source_order_id'])?$mapped['source_order_id']:0 ), 'message'=>'ردیف ' . $row_no . ': شناسه سایت مبدا نامعتبر است.' );
                    }
                } else {
                    $customer_stats['failed']++;
                    if ( count( $customer_stats['errors'] ) < 20 ) {
                        $customer_stats['errors'][] = array( 'customer'=>absint( isset($mapped['source_user_id'])?$mapped['source_user_id']:0 ), 'message'=>'ردیف ' . $row_no . ': شناسه سایت مبدا نامعتبر است.' );
                    }
                }
                continue;
            }
            if ( ! $source_hash ) { $source_hash = $row_source_hash; }
            if ( $source_hash !== $row_source_hash ) {
                return new WP_Error( 'shcd_source', 'شناسه سایت مبدا در همه ردیف‌های CSV باید یکسان باشد.', array( 'status' => 400 ) );
            }

            if ( $is_orders ) {
                $data = self::csv_row_to_order( $mapped );
                if ( is_wp_error( $data ) ) {
                    $stats['failed']++;
                    if ( count( $stats['errors'] ) < 20 ) {
                        $stats['errors'][] = array( 'order'=>absint(isset($mapped['source_order_id'])?$mapped['source_order_id']:0), 'message'=>'ردیف ' . $row_no . ': ' . $data->get_error_message() );
                    }
                    continue;
                }
                $order_batch[] = $data;
                if ( count( $order_batch ) >= 500 ) {
                    $same_source = self::reject_source_site_import( $source_hash );
                    if ( is_wp_error( $same_source ) ) { return $same_source; }
                    $stats = self::merge_stats( $stats, SHCD_Customer_Orders::import_batch( array( 'orders'=>$order_batch ), $source_hash, $order_settings ) );
                    $order_batch = array();
                }
            } else {
                $data = self::csv_row_to_customer( $mapped );
                if ( is_wp_error( $data ) ) {
                    $customer_stats['failed']++;
                    if ( count( $customer_stats['errors'] ) < 20 ) {
                        $customer_stats['errors'][] = array( 'customer'=>absint(isset($mapped['source_user_id'])?$mapped['source_user_id']:0), 'message'=>'ردیف ' . $row_no . ': ' . $data->get_error_message() );
                    }
                    continue;
                }
                $customer_batch[] = $data;
                if ( count( $customer_batch ) >= 500 ) {
                    $same_source = self::reject_source_site_import( $source_hash );
                    if ( is_wp_error( $same_source ) ) { return $same_source; }
                    $customer_stats = self::merge_customer_stats( $customer_stats, SHCD_Customer_Customers::import_batch( $customer_batch, $source_hash, $settings, true ) );
                    $customer_batch = array();
                }
            }
        }

        if ( ! $source_hash ) {
            return new WP_Error( 'shcd_source_required', 'فایل CSV باید دارای source_site_hash معتبر باشد.', array( 'status' => 400 ) );
        }
        $same_source = self::reject_source_site_import( $source_hash );
        if ( is_wp_error( $same_source ) ) { return $same_source; }

        if ( $order_batch ) {
            $stats = self::merge_stats( $stats, SHCD_Customer_Orders::import_batch( array( 'orders'=>$order_batch ), $source_hash, $order_settings ) );
        }
        if ( $customer_batch ) {
            $customer_stats = self::merge_customer_stats( $customer_stats, SHCD_Customer_Customers::import_batch( $customer_batch, $source_hash, $settings, true ) );
        }

        $order_total = absint( $stats['created'] ) + absint( $stats['updated'] ) + absint( $stats['skipped'] ) + absint( $stats['failed'] );
        $customer_total = absint( $customer_stats['created'] ) + absint( $customer_stats['reused'] ) + absint( $customer_stats['skipped'] ) + absint( $customer_stats['failed'] );

        return rest_ensure_response(
            array(
                'success'        => true,
                'data_type'      => $is_orders ? ( $legacy_combined ? 'combined' : 'orders' ) : 'customers',
                'stats'          => $stats,
                'customer_stats' => $customer_stats,
                'total'          => $order_total,
                'customer_total' => $customer_total,
            )
        );
    }

    private static function csv_row_to_order( $row ) {
        $required=array('source_order_id','status');
        foreach($required as $key){if(!isset($row[$key])||trim((string)$row[$key])===''){return new WP_Error('shcd_csv_field','ستون '.$key.' خالی است.');}}
        $json_fields=array('billing','shipping','totals','items','shipping_items','fees','coupons');
        $data=array('schema_version'=>5);
        $simple=array('source_order_id','order_number','status','currency','prices_include_tax','date_created','date_paid','date_completed','payment_method','payment_title','transaction_id','customer_note','customer_ip','user_agent','created_via','cart_hash');
        foreach($simple as $key){$data[$key]=isset($row[$key])?(string)$row[$key]:'';}
        foreach($json_fields as $key){
            $raw=isset($row[$key])?(string)$row[$key]:'';
            if($raw===''){$data[$key]=array();continue;}
            $decoded=json_decode($raw,true);
            if(!is_array($decoded)){return new WP_Error('shcd_csv_json','داده ستون '.$key.' قابل خواندن نیست.');}
            $data[$key]=$decoded;
        }
        // CSV exports customer fields as individual columns, not as a JSON object.
        $data['customer']=array(
            'user_id'    => absint(isset($row['customer_user_id'])?$row['customer_user_id']:0),
            'email'      => SHCD_Customer_Security::clean_email(isset($row['customer_email'])?$row['customer_email']:''),
            'phone'      => SHCD_Customer_Security::clean_text(isset($row['customer_phone'])?$row['customer_phone']:'',100),
            'first_name' => SHCD_Customer_Security::clean_text(isset($row['customer_first_name'])?$row['customer_first_name']:'',100),
            'last_name'  => SHCD_Customer_Security::clean_text(isset($row['customer_last_name'])?$row['customer_last_name']:'',100),
        );
        $customer_account_raw = isset($row['customer_account']) ? (string)$row['customer_account'] : '';
        if ( '' !== $customer_account_raw ) {
            $customer_account = json_decode($customer_account_raw,true);
            if ( ! is_array($customer_account) ) { return new WP_Error('shcd_csv_customer','داده ستون customer_account قابل خواندن نیست.'); }
            $data['registered_customer'] = $customer_account;
        }
        $data['prices_include_tax']=!empty($data['prices_include_tax']) && !in_array(strtolower((string)$data['prices_include_tax']),array('0','false','no'),true);
        $data['source_order_id']=absint($data['source_order_id']);
        if(!$data['source_order_id']){return new WP_Error('shcd_csv_order','شناسه سفارش نامعتبر است.');}
        return $data;
    }

    private static function csv_row_to_customer( $row ) {
        $source_user_id = absint( isset( $row['source_user_id'] ) ? $row['source_user_id'] : 0 );
        if ( ! $source_user_id ) {
            return new WP_Error( 'shcd_csv_customer', 'شناسه مشتری نامعتبر است.' );
        }
        $billing_raw  = isset( $row['billing'] ) ? (string) $row['billing'] : '';
        $shipping_raw = isset( $row['shipping'] ) ? (string) $row['shipping'] : '';
        $billing      = '' !== $billing_raw ? json_decode( $billing_raw, true ) : array();
        $shipping     = '' !== $shipping_raw ? json_decode( $shipping_raw, true ) : array();
        if ( ! is_array( $billing ) || ! is_array( $shipping ) ) {
            return new WP_Error( 'shcd_csv_customer', 'اطلاعات آدرس مشتری در CSV قابل خواندن نیست.' );
        }
        return array(
            'source_user_id' => $source_user_id,
            'user_login'     => SHCD_Customer_Security::clean_text( isset($row['user_login'])?$row['user_login']:'', 60 ),
            'user_email'     => SHCD_Customer_Security::clean_email( isset($row['user_email'])?$row['user_email']:'' ),
            'display_name'   => SHCD_Customer_Security::clean_text( isset($row['display_name'])?$row['display_name']:'', 250 ),
            'first_name'     => SHCD_Customer_Security::clean_text( isset($row['first_name'])?$row['first_name']:'', 100 ),
            'last_name'      => SHCD_Customer_Security::clean_text( isset($row['last_name'])?$row['last_name']:'', 100 ),
            'registered_at'  => SHCD_Customer_Security::clean_text( isset($row['registered_at'])?$row['registered_at']:'', 32 ),
            'phone'          => SHCD_Customer_Security::clean_text( isset($row['phone'])?$row['phone']:'', 100 ),
            'billing'        => $billing,
            'shipping'       => $shipping,
        );
    }

    private static function empty_stats() {
        return array('created'=>0,'updated'=>0,'skipped'=>0,'failed'=>0,'errors'=>array(),'warnings'=>array(),'warning_count'=>0,'id_map'=>array());
    }

    private static function merge_stats( $a, $b ) {
        foreach(array('created','updated','skipped','failed') as $key){$a[$key]+=absint(isset($b[$key])?$b[$key]:0);}
        $a['warning_count'] += absint(isset($b['warning_count'])?$b['warning_count']:0);
        foreach((isset($b['errors'])?$b['errors']:array()) as $error){if(count($a['errors'])<20){$a['errors'][]=$error;}}
        foreach((isset($b['warnings'])?$b['warnings']:array()) as $warning){if(count($a['warnings'])<50){$a['warnings'][]=$warning;}}
        foreach((isset($b['id_map'])?$b['id_map']:array()) as $map){if(count($a['id_map'])<100){$a['id_map'][]=$map;}}
        return $a;
    }

    private static function empty_customer_stats() {
        return array('created'=>0,'reused'=>0,'skipped'=>0,'failed'=>0,'errors'=>array(),'id_map'=>array());
    }

    private static function merge_customer_stats( $a, $b ) {
        foreach(array('created','reused','skipped','failed') as $key){$a[$key]+=absint(isset($b[$key])?$b[$key]:0);}
        foreach((isset($b['errors'])?$b['errors']:array()) as $error){if(count($a['errors'])<20){$a['errors'][]=$error;}}
        foreach((isset($b['id_map'])?$b['id_map']:array()) as $map){if(count($a['id_map'])<200){$a['id_map'][]=$map;}}
        return $a;
    }

    public static function receive_remote( $request ) {
        $raw=$request->get_body();
        $auth=SHCD_Customer_Security::verify_remote($request,$raw);
        if(is_wp_error($auth)){return $auth;}
        $payload=json_decode($raw,true,512,JSON_BIGINT_AS_STRING);
        if(!is_array($payload)){return new WP_Error('shcd_json','JSON نامعتبر است.',array('status'=>400));}
        $result=self::import_payload($payload,false);
        return is_wp_error($result)?$result:rest_ensure_response($result);
    }

    private static function import_payload( $payload, $allow_many ) {
        $plugin_id = isset( $payload['plugin'] ) ? (string) $payload['plugin'] : '';
        if ( ! in_array( $plugin_id, array( 'shcd-kerishna','shabnam-karvan-migrator','shcd-customer' ), true ) ) {
            return new WP_Error( 'shcd_plugin', 'فرمت این فایل متعلق به Kerishna Shop Migrator نیست.', array( 'status'=>400 ) );
        }

        $orders    = isset( $payload['orders'] ) && is_array( $payload['orders'] ) ? $payload['orders'] : array();
        $customers = isset( $payload['customers'] ) && is_array( $payload['customers'] ) ? $payload['customers'] : array();
        $data_type = sanitize_key( isset( $payload['data_type'] ) ? $payload['data_type'] : '' );
        if ( ! in_array( $data_type, array( 'orders','customers','combined' ), true ) ) {
            // Backward-compatible inference for backups created by earlier releases.
            $data_type = ! empty( $orders ) ? ( ! empty( $customers ) ? 'combined' : 'orders' ) : ( ! empty( $customers ) ? 'customers' : '' );
        }
        if ( ! $data_type ) {
            return new WP_Error( 'shcd_payload', 'فایل هیچ سفارش یا مشتری قابل واردسازی ندارد.', array( 'status'=>400 ) );
        }

        $source_hash = SHCD_Customer_Security::clean_text( isset($payload['site_hash'])?$payload['site_hash']:'', 128 );
        if ( ! $source_hash || ! preg_match( '/^[a-f0-9]{64}$/', $source_hash ) ) {
            return new WP_Error( 'shcd_source', 'شناسه سایت مبدا معتبر نیست.', array( 'status'=>400 ) );
        }
        $same_source = self::reject_source_site_import( $source_hash );
        if ( is_wp_error( $same_source ) ) { return $same_source; }

        if ( ! $allow_many && 'customers' !== $data_type && count( $orders ) > 500 ) {
            return new WP_Error( 'shcd_batch', 'حداکثر 500 سفارش در هر بسته مجاز است.', array( 'status'=>400 ) );
        }
        if ( ! $allow_many && 'customers' === $data_type && count( $customers ) > 500 ) {
            return new WP_Error( 'shcd_batch', 'حداکثر 500 مشتری در هر بسته مجاز است.', array( 'status'=>400 ) );
        }

        $settings       = SHCD_Customer_Security::settings();
        $stats          = self::empty_stats();
        $customer_stats = self::empty_customer_stats();

        if ( 'customers' === $data_type ) {
            foreach ( array_chunk( $customers, 500 ) as $customer_batch ) {
                $customer_stats = self::merge_customer_stats( $customer_stats, SHCD_Customer_Customers::import_batch( $customer_batch, $source_hash, $settings, true ) );
            }
        } elseif ( 'orders' === $data_type ) {
            // Explicit order-only imports may reuse matching destination accounts, but
            // never create a new customer account as a side effect.
            $order_settings = $settings;
            $order_settings['create_customers'] = 0;
            foreach ( array_chunk( $orders, 500 ) as $order_batch ) {
                $stats = self::merge_stats( $stats, SHCD_Customer_Orders::import_batch( array( 'orders'=>$order_batch ), $source_hash, $order_settings ) );
            }
        } else {
            // Local legacy/combined backups respect the destination setting. A signed
            // direct transfer may explicitly request customer creation from the source UI.
            $combined_settings = $settings;
            $force_customers   = ! empty( $payload['customer_import_requested'] );
            if ( $force_customers ) { $combined_settings['create_customers'] = 1; }
            foreach ( array_chunk( $customers, 500 ) as $customer_batch ) {
                $customer_stats = self::merge_customer_stats( $customer_stats, SHCD_Customer_Customers::import_batch( $customer_batch, $source_hash, $combined_settings, $force_customers ) );
            }
            foreach ( array_chunk( $orders, 500 ) as $order_batch ) {
                $stats = self::merge_stats( $stats, SHCD_Customer_Orders::import_batch( array( 'orders'=>$order_batch ), $source_hash, $combined_settings ) );
            }
        }

        return array(
            'success'        => true,
            'data_type'      => $data_type,
            'stats'          => $stats,
            'customer_stats' => $customer_stats,
            'total'          => count( $orders ),
            'customer_total' => count( $customers ),
        );
    }

    public static function transfer_batch( $request ) {
        $settings=SHCD_Customer_Security::settings();
        $url=SHCD_Customer_Security::valid_destination_url($settings['destination_url']);
        $key=(string)$settings['destination_api_key'];
        $secret=(string)$settings['destination_api_secret'];
        if(!$url||!$key||!$secret){return new WP_Error('shcd_destination','اطلاعات مقصد کامل نیست.',array('status'=>400));}
        if ( self::is_same_site_url( $url ) ) {
            return new WP_Error('shcd_source_protected','آدرس مقصد همان سایت مبدا است. برای حفاظت از سفارش‌های مبدا، کپی به خودِ سایت مبدا مسدود شد.',array('status'=>409));
        }
        $page=max(1,absint($request->get_param('page')));
        // COPY/BACKUP ONLY: source orders are read and serialized, never moved/deleted/updated.
        $batch=SHCD_Customer_Orders::get_batch($page,min(500,max(1,absint($settings['batch_size']))),! empty($settings['create_customers']));
        $batch['plugin']='shcd-kerishna';
        $batch['version']=SHCD_CUSTOMER_VERSION;
        $batch['schema_version']=6;
        $batch['data_type']=! empty($settings['create_customers']) ? 'combined' : 'orders';
        $batch['customer_import_requested']=! empty($settings['create_customers']);
        $batch['transfer_mode']='copy_backup';
        $batch['source_read_only']=true;
        $batch['site_hash']=self::site_hash();
        $body=wp_json_encode($batch,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(false===$body){return new WP_Error('shcd_encode','تبدیل داده‌ها به JSON ناموفق بود.',array('status'=>500));}
        if(strlen($body)>SHCD_Customer_Security::MAX_PAYLOAD){return new WP_Error('shcd_payload_size','بسته سفارش‌ها بیشتر از 20 مگابایت است؛ تعداد هر بسته را کمتر کنید.',array('status'=>413));}
        $timestamp=time();
        $nonce=SHCD_Customer_Security::random_token(24);
        $signature=SHCD_Customer_Security::sign_payload($timestamp,$nonce,$body,$secret);
        $request_args=array('timeout'=>60,'redirection'=>2,'headers'=>array('Content-Type'=>'application/json; charset=utf-8','X-SHCD-Key'=>$key,'X-SHCD-Timestamp'=>(string)$timestamp,'X-SHCD-Nonce'=>$nonce,'X-SHCD-Signature'=>$signature),'body'=>$body);
        $endpoint=trailingslashit($url).'wp-json/'.self::NS.'/receive';
        $response=wp_remote_post($endpoint,$request_args);
        if(is_wp_error($response)){return new WP_Error('shcd_transfer',$response->get_error_message(),array('status'=>502));}
        $code=wp_remote_retrieve_response_code($response);
        // Compatibility with destinations still running a previous SHCD release.
        if ( 404 === $code ) {
            $legacy_endpoint=trailingslashit($url).'wp-json/'.self::LEGACY_NS.'/receive';
            $response=wp_remote_post($legacy_endpoint,$request_args);
            if(is_wp_error($response)){return new WP_Error('shcd_transfer',$response->get_error_message(),array('status'=>502));}
            $code=wp_remote_retrieve_response_code($response);
        }
        $remote_body=wp_remote_retrieve_body($response);
        $decoded=json_decode($remote_body,true);
        if($code<200||$code>=300){
            $message=is_array($decoded)&&!empty($decoded['message'])?SHCD_Customer_Security::clean_text($decoded['message'],300):'مقصد خطا برگرداند.';
            return new WP_Error('shcd_transfer_remote',$message,array('status'=>502,'remote'=>is_array($decoded)?$decoded:array()));
        }
        return rest_ensure_response(array('success'=>true,'page'=>$page,'total'=>absint($batch['total']),'total_pages'=>absint($batch['total_pages']),'has_more'=>!empty($batch['has_more']),'remote'=>is_array($decoded)?$decoded:array()));
    }

    /**
     * Immutable-source guard. A backup created by this site may never be imported
     * back into the same site. This prevents accidental writes to the source orders.
     */
    private static function reject_source_site_import( $source_hash ) {
        $source_hash = strtolower( trim( (string) $source_hash ) );
        if ( $source_hash && hash_equals( self::site_hash(), $source_hash ) ) {
            return new WP_Error(
                'shcd_source_protected',
                'این بکاپ متعلق به همین سایت است. برای حفاظت کامل از سفارش‌های مبدا، واردسازی روی خودِ سایت مبدا مسدود است؛ فایل را فقط در سایت مقصد وارد کنید.',
                array( 'status' => 409 )
            );
        }
        return true;
    }

    /** Compare a configured destination with the current WordPress home URL. */
    private static function is_same_site_url( $url ) {
        $normalize = static function( $value ) {
            $parts = wp_parse_url( untrailingslashit( (string) $value ) );
            if ( ! is_array( $parts ) || empty( $parts['host'] ) ) { return ''; }
            $scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
            $host   = strtolower( $parts['host'] );
            $port   = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
            $path   = isset( $parts['path'] ) ? '/' . trim( $parts['path'], '/' ) : '';
            if ( '/' === $path ) { $path = ''; }
            return $scheme . '://' . $host . $port . $path;
        };
        return $normalize( $url ) !== '' && hash_equals( $normalize( home_url('/') ), $normalize( $url ) );
    }

    /**
     * Read a REST-uploaded temporary file through the WordPress filesystem API.
     * The direct filesystem adapter is appropriate here because the file already
     * exists in PHP's upload temp directory and no remote credentials are needed.
     */
    private static function read_uploaded_file( $path, $label ) {
        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        $filesystem = new WP_Filesystem_Direct( null );
        $path       = (string) $path;
        $label      = strtoupper( SHCD_Customer_Security::clean_text( $label, 8 ) );

        if ( ! $path || ! $filesystem->exists( $path ) || ! $filesystem->is_file( $path ) ) {
            return new WP_Error( 'shcd_file_read', 'خواندن فایل ' . $label . ' ناموفق بود.', array( 'status' => 400 ) );
        }

        $size = $filesystem->size( $path );
        if ( false !== $size && $size > SHCD_Customer_Security::MAX_FILE_PAYLOAD ) {
            return new WP_Error( 'shcd_file_size', 'حجم فایل ' . $label . ' بیشتر از حد مجاز است.', array( 'status' => 413 ) );
        }

        $contents = $filesystem->get_contents( $path );
        if ( false === $contents ) {
            return new WP_Error( 'shcd_file_read', 'خواندن فایل ' . $label . ' ناموفق بود.', array( 'status' => 400 ) );
        }
        return $contents;
    }

    /**
     * Parse RFC-4180-style comma separated rows without direct filesystem calls.
     * This generator preserves quoted commas, escaped quotes and quoted newlines.
     */
    private static function parse_csv_rows( $csv ) {
        $csv       = (string) $csv;
        $length    = strlen( $csv );
        $row       = array();
        $field     = '';
        $in_quotes = false;

        for ( $i = 0; $i < $length; $i++ ) {
            $char = $csv[ $i ];

            if ( $in_quotes ) {
                if ( '"' === $char ) {
                    if ( $i + 1 < $length && '"' === $csv[ $i + 1 ] ) {
                        $field .= '"';
                        $i++;
                    } else {
                        $in_quotes = false;
                    }
                } else {
                    $field .= $char;
                }
                continue;
            }

            if ( '"' === $char ) {
                $in_quotes = true;
            } elseif ( ',' === $char ) {
                $row[] = $field;
                $field = '';
            } elseif ( "\r" === $char || "\n" === $char ) {
                if ( "\r" === $char && $i + 1 < $length && "\n" === $csv[ $i + 1 ] ) { $i++; }
                $row[] = $field;
                $field = '';
                yield $row;
                $row = array();
            } else {
                $field .= $char;
            }
        }

        if ( '' !== $field || ! empty( $row ) ) {
            $row[] = $field;
            yield $row;
        }
    }

    private static function strip_utf8_bom( $text ) {
        return substr($text,0,3)==="\xEF\xBB\xBF" ? substr($text,3) : $text;
    }

    private static function json_error_message() {
        return function_exists('json_last_error_msg') ? json_last_error_msg() : 'JSON decode error';
    }

    private static function site_hash() {
        return hash('sha256',home_url('/'));
    }
}
