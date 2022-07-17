<?php
/**
 * Custom Email
 *
 * An email sent to the admin when an order status is changed to Pending Payment.
 *
 * @class       Custom_Email
 * @extends     WC_Email
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email' ) ) {
    return;
}

class Coupon_Sender_Email extends WC_Email {

    function __construct() {

        // Add email ID, title, description, heading, subject
        $this->id                   = 'woo_coupons_sender';
        $this->title                = '寄送折價券';
        $this->description          = '新增折價券時自動寄送給客戶';

        // $this->customer_email = true;

        $this->heading              = '你的折價券';
        $this->subject              = '送你一個新的折價券';

        // email template path
        $this->template_html        = 'emails/woo_coupons_sender_html.php';
        $this->template_plain       = 'emails/woo_coupons_sender_plain.php';
        $this->template_base        = CUSTOM_TEMPLATE_PATH;

        // Triggers for this email
        add_action( 'woocommerce_update_coupon', array( $this, 'queue_notification' ) , 10, 2);
        add_action( 'custom_item_email_notification', array( $this, 'trigger' ),10,2 );

        // Call parent constructor
        parent::__construct();

    }

    public function queue_notification( $coupon_id, $coupon ) {
        $logger = wc_get_logger();
        $logger->info(  $coupon_id.'===add_schedule' , array( 'source' => 'send_email_add_schedule' ) );
        wp_schedule_single_event( time()+ 30, 'custom_item_email' , array( $coupon_id, $coupon ) );
    }


    public function trigger( $coupon_id, $coupon ) {

        $this->object = $coupon;

        $this->recipient = get_option( 'admin_email' );

        add_filter( 'woocommerce_email_headers', function($header){

            $main = new woo_coupons_sender();
            $users = $main->get_customers();
            $bcc = [];
            foreach ( $users as $user ) {
                $bcc[] = "{$user['name']}<{$user['email']}>";
            }
            $header .= "Bcc:" . implode(",", $bcc);
            return $header;
        });

        $logger = wc_get_logger();
        $logger->info( 'get_headers=='.print_r( $this->get_headers() ,true) , array( 'source' => 'send_emain_trigger' ) );

        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    // return the html content
    function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'item_data'     => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'			=> $this
        ), '', $this->template_base );
    }

    // return the plain content
    function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'item_data'     => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'			=> $this
        ), '', $this->template_base );
    }

    // form fields that are displayed in WooCommerce->Settings->Emails
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', 'woocommerce' ),
                'default' 		=> 'yes'
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', 'woocommerce' ),
                'type' 			=> 'text',
                'description' 	=> sprintf('如果沒有填寫郵件主旨，將使用預設文字：<code>%s</code>.', $this->subject ),
                'placeholder'   => $this->get_default_subject(),
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email heading', 'woocommerce' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( '郵件內容的標題，如果沒有填寫，將使用預設文字：<code>%s</code>',  $this->heading ),
                'placeholder' 	=> $this->get_default_heading(),
                'default' 		=> ''
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', 'woocommerce' ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', 'woocommerce' ),
                'default' 		=> 'html',
                'class'			=> 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true
            )
        );
    }
}