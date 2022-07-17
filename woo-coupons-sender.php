<?
/*
Plugin Name: Woo Coupons Sender
Plugin URI:  https://github.com/mark2me
Description: WooCommerce Coupons
Author:       Simon Chunag
Author URI:   https://github.com/mark2me
Version:      1.0
Text Domain:  woo-coupons-sender
Domain Path:  /languages
Requires PHP: 7.3
Requires at least: 5.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    return;
}



new woo_coupons_sender();

class woo_coupons_sender{

    public function __construct()
    {
        // template path
        define( 'CUSTOM_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) .'/' );

        add_filter( 'woocommerce_coupon_data_tabs',  array( $this, 'add_coupon_data_tab' ), 99 );

        add_action( 'woocommerce_coupon_data_panels', array( $this, 'add_coupon_data_panel' ), 10, 2 );

        // /wp-admin/admin-ajax.php?action=send_coupon_to_user
        add_action( 'wp_ajax_send_coupon_to_user', array( $this, 'add_ajax_send_coupon' ) );

        // include the email class files
        add_filter( 'woocommerce_email_classes', function($emails){

            if ( ! isset( $emails[ 'Coupon_Sender_Email' ] ) ) {
                require_once 'emails/class-coupon-sender-email.php';
                $emails[ 'Coupon_Sender_Email' ] = new Coupon_Sender_Email();
            }
            return $emails;
        } );

        add_action( 'custom_item_email', array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
    }

    public function add_coupon_data_tab($array) {
        $array['vi_wcc_customer_coupon'] = array(
            'label'  => '發送折價券',
            'target' => 'vi_wcc_customer_coupon_data',
            'class'  => '',
        );
        return $array;
    }

    public function add_coupon_data_panel( $coupon_get_id, $coupon ) {

        echo '<div class="wcc-coupons-field panel woocommerce_options_panel" id="vi_wcc_customer_coupon_data">';
        echo '<div style="padding:15px;">';

        $users = $this->get_customers();

        $code = $coupon->get_code();
        echo '<p>現有客戶數：'. count($users).'</p>';
        echo '<div>';
        foreach ( $users as $user ) {
            echo "<span style=\"margin-right:10px;\">{$user['name']} {$user['email']}</span>";
        }
        echo '</div>';

        $url = '/wp-admin/admin-ajax.php?action=send_coupon_to_user&code='.$code;
        echo '<p><a class="button button-primary button-large" href="'.$url.'" target="_blank">立即發送</a></p>';

        //print_r( $coupon->get_code() );
        echo '</div><div>';

    }

    public function add_ajax_send_coupon(){

        $coupon_code = (isset($_GET['code']) && !empty($_GET['code'])) ? $_GET['code']:0;

        global $woocommerce;
        $coupon = new WC_Coupon($coupon_code);
        if( $coupon->id > 0 ){

            $users = $this->get_customers();
            $bcc = [];
            foreach ( $users as $user ) {
                $bcc[] = "{$user['name']}<{$user['email']}>";
            }

            $headers = array();
            $headers[] = 'Bcc: ' . implode(",", $bcc);
            $headers[] = 'Content-Type: text/html';

            $to = get_option( 'admin_email' );
            $subject = '您有一個新的折價券可使用';
            $message = 'A new Coupons<br>';
            $message .= 'Coupons:<br>';
            $message .= $coupon_code;

            $mailer  = WC()->mailer();
            $email   = new WC_Email();
            $rs = $email->send( $to, $subject, $message, $headers, array() );

            echo ($rs == 1) ? 'ok':'error';

            print_r($headers);

        }else{
            echo '無效的折價券';
        }
        exit();
    }

    public function get_customers(){
        $args = array(
            'role'    => 'customer',
            'orderby' => 'user_nicename',
            'order'   => 'ASC'
        );

        $users = get_users( $args );
        $emails = [];
        foreach ( $users as $user ) {
            $emails[] = [
                'name' => esc_html( $user->display_name ),
                'email' => esc_html( $user->user_email )
            ];
        }

        return $emails;
    }
}