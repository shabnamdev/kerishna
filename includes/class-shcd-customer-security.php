<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SHCD_Customer_Security {
    const OPTION = 'shcd_customer_settings';
    // Must stay 'wp_rest': WordPress core's own cookie-auth check (rest_cookie_check_errors)
    // always validates the X-WP-Nonce header against this exact action for logged-in users,
    // before the request ever reaches our permission_callback. Using any other action here
    // causes every REST call to fail at the core level with "Cookie check failed"
    // (بررسی کوکی انجام نشد), regardless of what our own verify_nonce() does.
    const NONCE_ACTION = 'wp_rest';
    const API_WINDOW = 300;
    const MAX_PAYLOAD = 20971520;
    const MAX_FILE_PAYLOAD = 104857600;
    public static function settings() {
        $defaults = array('api_key'=>'','api_secret'=>'','destination_url'=>'','destination_api_key'=>'','destination_api_secret'=>'','batch_size'=>500,'create_customers'=>1,'create_missing_products'=>1);
        $saved = get_option(self::OPTION,array());
        $settings = wp_parse_args(is_array($saved)?$saved:array(),$defaults);
        $settings['batch_size']=min(500,max(1,absint($settings['batch_size'])));
        // Backup semantics are mandatory: a missing catalog product must not drop an order.
        $settings['create_missing_products']=1;
        return $settings;
    }
    public static function save_settings($settings){ update_option(self::OPTION,$settings,false); }
    public static function capability(){ return current_user_can('manage_woocommerce'); }
    public static function verify_nonce($request){ $nonce=$request->get_header('X-WP-Nonce'); if(!$nonce){$nonce=$request->get_param('_wpnonce');} return (bool)wp_verify_nonce(sanitize_text_field((string)$nonce),self::NONCE_ACTION); }
    public static function random_token($bytes=32){ try{return bin2hex(random_bytes($bytes));}catch(Exception $e){return wp_generate_password($bytes*2,false,false);} }
    public static function sign_payload($timestamp,$nonce,$body,$secret){return hash_hmac('sha256',$timestamp.'.'.$nonce.'.'.$body,$secret);}
    public static function verify_remote($request,$raw_body){
        $settings=self::settings(); $key=sanitize_text_field((string)$request->get_header('X-SHCD-Key')); $timestamp=absint($request->get_header('X-SHCD-Timestamp')); $nonce=sanitize_text_field((string)$request->get_header('X-SHCD-Nonce')); $signature=sanitize_text_field((string)$request->get_header('X-SHCD-Signature'));
        if(!$key||!$timestamp||!$nonce||!$signature){return new WP_Error('shcd_auth_missing','اطلاعات احراز هویت ناقص است.',array('status'=>401));}
        if(!$settings['api_key']||!$settings['api_secret']||!hash_equals((string)$settings['api_key'],$key)){return new WP_Error('shcd_auth_key','کلید API نامعتبر است.',array('status'=>401));}
        if(abs(time()-$timestamp)>self::API_WINDOW){return new WP_Error('shcd_auth_time','درخواست منقضی شده است.',array('status'=>401));}
        if(!preg_match('/^[A-Za-z0-9_-]{16,128}$/',$nonce)){return new WP_Error('shcd_auth_nonce','Nonce نامعتبر است.',array('status'=>401));}
        if(strlen($raw_body)>self::MAX_PAYLOAD){return new WP_Error('shcd_payload_size','Payload بزرگ‌تر از حد مجاز است.',array('status'=>413));}
        $expected=self::sign_payload($timestamp,$nonce,$raw_body,(string)$settings['api_secret']);
        if(!hash_equals($expected,$signature)){return new WP_Error('shcd_auth_signature','امضای درخواست نامعتبر است.',array('status'=>401));}
        $lock_key='shcd_customer_nonce_'.hash('sha256',$nonce);
        if(get_transient($lock_key)){return new WP_Error('shcd_replay','درخواست تکراری شناسایی شد.',array('status'=>409));}
        set_transient($lock_key,1,self::API_WINDOW);
        return true;
    }
    public static function clean_text($value,$max=1000){$value=is_scalar($value)?(string)$value:'';$value=wp_check_invalid_utf8($value);$value=sanitize_text_field($value);return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max);}
    public static function clean_email($email){return sanitize_email(self::clean_text($email,254));}
    public static function clean_number($value){$value=is_scalar($value)?(string)$value:'0';return wc_format_decimal($value,wc_get_price_decimals());}
    public static function valid_destination_url($url){$url=esc_url_raw(trim((string)$url)); if(!$url){return false;} $parts=wp_parse_url($url); if(empty($parts['scheme'])||!in_array(strtolower($parts['scheme']),array('http','https'),true)||empty($parts['host'])){return false;} return untrailingslashit($url);}
}
