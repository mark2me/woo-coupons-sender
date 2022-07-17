<?php
/**
 * Admin new order email
 */
//$order = new WC_order( $item_data->order_id );

$coupon = $item_data;

?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php
    $code = $coupon->get_code();
    $code = '<span style="color:#c00;background-color:#eee;padding:5px 10px; font-size:20px; letter-space:2px; ">'.$code.'</span>';

    echo sprintf( __( '你有一個新的折價券：%s', 'woo-coupons-sender' ), $code );
?>


<?php do_action( 'woocommerce_email_footer' ); ?>