<?php
/*
 * Plugin Name: BIX Payments
 * Plugin URI: 
 * Description: Accept payments with credit card via BIX Payments in your store.
 * Author: Keya Paral
 * Author URI: 
 * Version: 1.0.0
 *
 */
register_activation_hook( __FILE__, 'bix_wallet_activate' );
function bix_wallet_activate(){

    // Require parent plugin
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires the Woocommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}
/**
 * Add new payment getway WC_Bix_Payment_Gateway
 * @since 1.0.0
 * @return void
 */
add_filter('woocommerce_payment_gateways', 'bix_add_payment_gateway');
function bix_add_payment_gateway( $gateways ){
	$gateways[] = 'WC_Bix_Payment_Gateway';
	return $gateways; 
}

/**
 * Changed the or place button text
 * @since 1.0.0
 * @return void
 */
add_filter( 'woocommerce_available_payment_gateways', 'bix_available_payment_gateways' );
function bix_available_payment_gateways( $available_gateways ) {
    if (! is_checkout() ) return $available_gateways;  // stop doing anything if we're not on checkout page.
    if (array_key_exists('bix_payment',$available_gateways)) {
         $available_gateways['bix_payment']->order_button_text = __( 'Pay', 'woocommerce' );
    }
    return $available_gateways;
}


/**
 * Update order meta after order placed
 * @since 1.0.0
 * @return void
 */
add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted ) {
	session_start();
    $order = wc_get_order( $order_id );
   // echo get_post_meta($order_id, '_payment_method', true);
   	if ( get_post_meta($order_id, '_payment_method', true) == 'bix_payment')
   	{
   		 if(isset( $_SESSION['body'] ) && isset( $_SESSION['response'] ))
	    {
			$order->update_meta_data( 'body',  $_SESSION['body']  );

			$order->update_meta_data( 'response',  $_SESSION['response']   );

			$code = $_SESSION['response'];

			if($code['code'] == 200)
			{
				$order->update_status('completed');
			}
			else  if($code['code'] == 400){
				$order->update_status('failed');
			}
	    }
   	}
    $order->save();
} , 10, 2);

/**
 * unset session after order placed
 * @since 1.0.0
 * @return void
 */
add_action( 'woocommerce_thankyou', 'bix_woocommerce_auto_processing_orders');
function bix_woocommerce_auto_processing_orders( $order_id ) {
    if ( ! $order_id )
        return;

    $order = wc_get_order( $order_id );

    // If order is "on-hold" update status to "processing"
    if( $order->has_status( 'on-hold' ) ) {

    	session_start();
    	$code = $_SESSION['response'];
    	//($code);
		if($code['code'] == 200)
		{
			$order->update_status('completed');
		}
		else  if($code['code'] == 400)
		{
		 	$order->update_status('failed');
		}
		unset ($_SESSION['response']);
		unset ($_SESSION['body']);
    }
}

add_action('plugins_loaded', 'init_bix_payment_gateway');

function init_bix_payment_gateway(){
	require 'class-woocommerce-bix-payment-gateway.php';
}
/**
 * Load css files
 * @since 1.0.0
 * @return void
 */
function bix_load_scripts() {
   
    wp_enqueue_style( 'my-plugin-custom-styles', plugins_url('assets/css/style.css' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'bix_load_scripts' );


/**
 * unset session after order placed
 * @since 1.0.0
 * @return void
 */
add_action( 'wp_ajax_bix_registration_submit', 'bix_registration_submit_fn' );
add_action( 'wp_ajax_nopriv_bix_registration_submit', 'bix_registration_submit_fn' );

function bix_registration_submit_fn() {

    parse_str($_POST["formdata"], $_POST);
	$password =$_POST['password'];
	$username = $_POST['email'];

	$hash = md5($password);
	 
	$data = array(
	  "email"=> $username,
	  "passwd"=> $hash
	);
	 
	$payload = json_encode($data);
	 
	// Prepare new cURL resource
	$ch = curl_init('https://bixwallet.com:9000/users/login');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, true);

	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);


	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	 
	// Set HTTP Header for POST request 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Content-Length: ' . strlen($payload))
	);
	$result = curl_exec($ch);

	if (curl_error($ch)) {
	  $error_msg = curl_error($ch);
	}
	if($error_msg)
	{
		echo json_encode(array('registration'=>false,'data'=>$error_msg,'message'=>__('Registration successfull')));
	}
	else{
		if (!session_id()) {
		    session_start();
		}
		$_SESSION['bixusers'] = $result;
		echo json_encode(array('registration'=>true,'message'=>__('Registration successfull')));
	}

	curl_close($ch);
    die();
}

/**
 * Ajax for payment section
 * @since 1.0.0
 * @return json object
 */

add_action( 'wp_ajax_bix_card_payment_logout', 'bix_card_payment_logout_fn' );
add_action( 'wp_ajax_nopriv_bix_card_payment_logout', 'bix_card_payment_logout_fn' );

function bix_card_payment_logout_fn() {

	$wc_bix_payment_gateway = new WC_Bix_Payment_Gateway; 
    parse_str($_POST["formdata"], $_POST);
	$cardnumber =$_POST['wcardnumber'];
	$cvv = $_POST['wcvv'];
	$month =$_POST['wmonth'];
	$year = $_POST['wyear'];
	$amount = $_POST['amount'];
	$shipping_address_popup = $_POST['shipping_address_popup'];
	$shipping_postcode_popup = $_POST['shipping_postcode_popup'];
	$cardBrand = determinecardBrand($cardnumber);
	$bix_payload =  array (
	  'bxcAmount' => $amount,
	  'payTo' => $wc_bix_payment_gateway->merchant_mail_id,
	  'payToId' => 23,
	  'paymentType' => 'DIRECT_CARD',
	  'totalAmount' => $amount,
	  'message' => 'BIX_BUY_BY_DIRECT_CARD',
	  'transferType' => 'BUY',
	  'bixBankTransfer' => NULL,
	  'bixBtcTransfer' => NULL,
	  'bixDirectPayment' => 
	  array (
	    'bankCard' => 
	    array (
	      'cardType' => 1,
	      'expiryMonth' => $month,
	      'expiryYear' => $year,
	      'ownerName' => 'XXXX XXXXX',
	      'billingPostalCode' => $shipping_postcode_popup,
	      'ccvData' => $cvv,
	      'bixId' => 'XXXXXXXXXX',//bix id
	      'billingAddress' => $shipping_address_popup,
	      'cardBrand' => $cardBrand,
	      'currencyCode' => '840',
	      'cardNumber' => $cardnumber,
	    ),
	    'cardPayment' => 
	    array (
	      'totalAmount' => $amount,
	      'amount' => $amount,
	      'payFrom' => 'test@bixsystem.com',//mailid
	      'txFee' => 0,
	      'bixFee' => 0,
	    ),
	  ),
	);
	$response = wp_remote_post( "https://bixwallet.com:9008/v1/oparation/buy", array(
		'method'    => 'POST',
		'headers'   => array('Authorization' => 'Basic ' . base64_encode('2426177513:0fad1844-ba71-47d1-ba24-b6f2c9814870'),'Content-Type' => 'application/json; charset=utf-8'),
		'body'      => json_encode($bix_payload),
		'timeout'   => 90,
		'sslverify' => false
	));

	session_start();
	$_SESSION['body'] = $response['body'];
	$_SESSION['response'] = $response['response'];
	echo json_encode($response['response']);
    die();

}

/**
 * determinecardBrand 
 *1-VISA, 2-MC, 3-AMEX, 4-Discover, 5-Diners, 6-JCB
 * @since 1.0.0
 * @return integer
 */

function determinecardBrand ($cardNumber)
{
	   $cardBrand = 0;

	   $firstCardNumberDigitInt = substr($cardNumber, 0, 1);
	   $firstTwoCardNumberDigitsInt = substr($cardNumber, 0, 2);
       $firstFourCardNumberDigitsInt = substr($cardNumber, 0, 4);
       $irstSixCardNumberDigitsInt = substr($cardNumber, 0, 6);

        
        // --- VISA: the first digit is 4 ---
        if($firstCardNumberDigitInt == 4) {
            if(strlen($cardNumber) > 19)
                $cardBrand = -1; // VISA card, but no of digits > 19
            else
                $cardBrand = 1;
        }
         // --- MC: the first two digit must be 50, 51, 52, 53, 54, or 55 ---
        if($firstTwoCardNumberDigitsInt == 50 || $firstTwoCardNumberDigitsInt == 51 ||
           $firstTwoCardNumberDigitsInt == 52 || $firstTwoCardNumberDigitsInt == 53 ||
           $firstTwoCardNumberDigitsInt == 54 || $firstTwoCardNumberDigitsInt == 55) {
            if(strlen($cardNumber) != 16)
                $cardBrand = -2; // MC card, but no of digits != 16
            else
                $cardBrand = 2;
        }
        
        // --- AMEX: the first two digit must be 34 or 37 ---
        if($firstTwoCardNumberDigitsInt == 34 || $firstTwoCardNumberDigitsInt == 37) {
            if(strlen($cardNumber) != 15)
                $cardBrand = -3; // AMEX card, but no of digits != 15
            else
                $cardBrand = 3;
        }
        
        // --- Discover or Mastro: the first two digit are 60 ---
        if($firstTwoCardNumberDigitsInt == 60) {
            // --- Discover card ---
            if(($firstSixCardNumberDigitsInt >= 601100 && $firstSixCardNumberDigitsInt <= 601109) ||
               ($firstSixCardNumberDigitsInt >= 601120 && $firstSixCardNumberDigitsInt <= 601149) ||
               ($firstSixCardNumberDigitsInt == 601174) ||
               ($firstSixCardNumberDigitsInt >= 601177 && $firstSixCardNumberDigitsInt <= 601179) ||
               ($firstSixCardNumberDigitsInt >= 601186 && $firstSixCardNumberDigitsInt <= 601199)) {
               if(strlen($cardNumber) != 16)
                    $cardBrand = -4; // Discover card, but no of digits != 16
               else
                    $cardBrand = 4;
            }
            // --- Maestro card : Not supported --
            else {
                $cardBrand = -9;  
            }
        }
        
        // --- Discover or Maestro: the first two digit are 64 ---
        if($firstTwoCardNumberDigitsInt == 60) {
            // --- Discover card ---
            if(($firstSixCardNumberDigitsInt >= 644000 && $firstSixCardNumberDigitsInt <= 659999)) {
               if(strlen($cardNumber)  != 16)
                    $cardBrand = -4; // Discover card, but no of digits != 16
               else
                    $cardBrand = 4;
            }
            // --- Maestro card : Not supported --
            else {
                $cardBrand = -9;  
            }
        }
        
        // --- Diners: the first two digit must be 30, 36, 38, 39 ---
        if($firstTwoCardNumberDigitsInt == 30 || $firstTwoCardNumberDigitsInt == 36 ||
           $firstTwoCardNumberDigitsInt == 38 || $firstTwoCardNumberDigitsInt == 39) {
            if(strlen($cardNumber) != 14)
                $cardBrand = -5; // Diners card, but no of digits != 14
            else
                $cardBrand = 5;
        }
        
        // --- JCB: the first four digit are in the range 3528 - 3589 ---
        if($firstFourCardNumberDigitsInt >= 3528 && $firstFourCardNumberDigitsInt <= 3589) {
           $cardBrand = 6;
        }
        
        return $cardBrand; 
        
}

/**
 * Added a new hidden field for cart total at checkout page
 *1-VISA, 2-MC, 3-AMEX, 4-Discover, 5-Diners, 6-JCB
 * @since 1.0.0
 * @return void
 */

add_action( 'woocommerce_after_checkout_billing_form', 'bix_display_extra_fields_after_billing_address' , 10, 1 );

function bix_display_extra_fields_after_billing_address () {

	global $woocommerce;
    // Will get you cart object
    $cart = $woocommerce->cart;
    // Will get you cart object
    $cart_total = WC()->cart->total;
	?>

	<input type="hidden" name="custom_cart_total" id="custom_cart_total" value="<?php echo $cart_total; ?>">
  <?php 
}

