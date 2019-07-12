<?php 

class WC_Bix_Payment_Gateway extends WC_Payment_Gateway{

	public function __construct(){

		$this->id = 'bix_payment';
		$this->method_title = __('Pay with card','woocommerce-bix-payment-gateway');
		$this->title = __('Pay with card','woocommerce-bix-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->merchant_mail_id = $this->get_option('merchant_mail_id');
		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

	}

	public function init_form_fields(){
		
		/**
		 * created fileds for setting page
		 *
		 * @since 1.0.0
		 * @return void
	 	*/

		$this->form_fields = array(

			'enabled' => array(
			'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
			'type' 			=> 'checkbox',
			'label' 		=> __( 'Enable BIX Payments', 'woocommerce' ),
			'default' 		=> 'yes'
			),
			'title' => array(
				'title' 		=> __( 'Method Title', 'woocommerce' ),
				'type' 			=> 'text',
				'default'		=> __( 'BIX Payments', 'woocommerce' ),
				'description' 	=> __( 'This controls the title', 'woocommerce' ),
				'desc_tip'		=> true,
			),
			'description' => array(
				'title' => __( 'Customer Message', 'woocommerce' ),
				'type' => 'textarea',
				'default' => '',
				'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce' ),

			),
			'merchant_mail_id'=>array(
				'title' 		=> __( 'Merchant mail id', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the merchant mail id', 'woocommerce' ),
				'default'		=> __( '', 'woocommerce' ),
				'desc_tip'		=> true,
			),
		);

	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' description,merchant_mail_id
	 *
	 * @since 1.0.0
	 * @return void
	 */

	public function admin_options() {

		?>
		<h3><?php _e( 'BIX Payments Settings', 'woocommerce' ); ?></h3>
		<table class="form-table">
		<?php
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
		?>
		</table><!--/.form-table-->
		<?php
	}

	public function is_available(  ){
		return true;
	}

	/**
	 * Proceed payment logic implemented here
	 * @since 1.0.0
	 * @return void
	 */
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __( 'Awaiting payment', 'woocommerce' ));
		// Reduce stock levels
		$order->reduce_order_stock();
		$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

	/**
	 * Display popup for entering the card details
	 * @since 1.0.0
	 * @return void
	 */
	public function payment_fields(){
		//die;
		if (!session_id()) {
		    session_start();
		}
		?>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		 <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
		
		<script type="text/javascript">
			
	    	flag1 = 0;
	    	function isEmail(email) {
			  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			  return regex.test(email);
			}

	    	jQuery(document).on('click', '#place_order', function(e){
	    		

				jQuery("#shipping_address_popup").val(jQuery("input[name=billing_first_name]").val()+' '+jQuery("input[name=billing_last_name]").val()+'\n'+jQuery("input[name=billing_address_1]").val()+','+jQuery("input[name=billing_city]").val()+','+jQuery("input[name=billing_country]").val());

				jQuery("#shipping_postcode_popup").val(jQuery("input[name=billing_postcode]").val());
 						
				if(jQuery("input[name=payment_method]:checked").val()=='bix_payment')
				{
					 e.preventDefault();  

					var cart_total = jQuery("#custom_cart_total").val();
					$flag = 0;

					setTimeout(function(){
							if(jQuery("#select2-billing_country-container .select2-selection__placeholder").html()=='Select an option…'){
								jQuery("#billing_country_field").addClass('woocommerce-invalid');
							}

					}, 100);

	    			if(jQuery("input[name=billing_first_name]").val()=='')
	    			{
	    				$flag = 1;

	    				jQuery("#billing_first_name_field").addClass('woocommerce-invalid');
	    				
	    			}
	    			if(jQuery("input[name=billing_last_name]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_last_name_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_country]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_country_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_address_1]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_address_1_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_city]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_city_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_postcode]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_postcode_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_phone]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_phone_field").addClass('woocommerce-invalid');
	    			}
	    			if(jQuery("input[name=billing_email]").val()=='')
	    			{
	    				$flag = 1;
	    				jQuery("#billing_email_field").addClass('woocommerce-invalid');
	    			}
	    			if(!isEmail(jQuery("input[name=billing_email]").val()))
	    			{
	    				$flag = 1;
	    				jQuery("#billing_email_field").addClass('woocommerce-invalid');
	    			}

	    			

	    			if(jQuery("#ship-to-different-address-checkbox").prop("checked") == true)
	    			{
	    				setTimeout(function(){
							if(jQuery("#select2-shipping_state-container .select2-selection__placeholder").html()=='Select an option…'){
								jQuery("#shipping_state_field").addClass('woocommerce-invalid');
							}

						}, 100);
	    				if(jQuery("input[name=shipping_first_name]").val()=='')
		    			{
		    				$flag = 1;

		    				jQuery("#shipping_first_name_field").addClass('woocommerce-invalid');
		    				
		    			}
		    			if(jQuery("input[name=shipping_last_name]").val()=='')
		    			{
		    				$flag = 1;
		    				jQuery("#shipping_last_name_field").addClass('woocommerce-invalid');
		    			}
		    			if(jQuery("input[name=shipping_country]").val()=='')
		    			{
		    				$flag = 1;
		    				jQuery("#shipping_country_field").addClass('woocommerce-invalid');
		    			}
		    			if(jQuery("input[name=shipping_address_1]").val()=='')
		    			{
		    				$flag = 1;
		    				jQuery("#shipping_address_1_field").addClass('woocommerce-invalid');
		    			}
		    			if(jQuery("input[name=shipping_city]").val()=='')
		    			{
		    				$flag = 1;
		    				jQuery("#shipping_city_field").addClass('woocommerce-invalid');
		    			}
		    			if(jQuery("input[name=shipping_postcode]").val()=='')
		    			{
		    				$flag = 1;
		    				jQuery("#shipping_postcode_field").addClass('woocommerce-invalid');
		    			}
	    			}

	    			if( $flag == 0 ){
	    				jQuery( "#card-wrap-withoutlogin" ).dialog();
						jQuery("#popupamount").val(cart_total);
						jQuery("#price-span").html(cart_total);
	    			}
				} 
	    	});
	    	jQuery(document).on('click', '#card_payment_logout', function(e){
	    		
	    		e.preventDefault();
	    		if(flag1 == 0 )
				{
					$eerorflag = 0;
					e.preventDefault();
 					if(jQuery("#wcardnumber").val()=='')
 					{
 						jQuery("#wcardnumber").addClass('error');
 						$eerorflag = 1;
 					}
 					else{
 						jQuery("#wcardnumber").removeClass('error');
 					}
 					if(jQuery("#wcvv").val()=='')
 					{
 						jQuery("#wcvv").addClass('error');
 						$eerorflag = 1;
 					}
 					else{
 						jQuery("#wcvv").removeClass('error');
 					}
 					if(jQuery("#wmonth").val()=='')
 					{
 						jQuery("#wmonth").addClass('error');
 						$eerorflag = 1;
 					}
 					else{
 						jQuery("#wmonth").removeClass('error');
 					}
 					if(jQuery("#wyear").val()=='')
 					{
 						jQuery("#wyear").addClass('error');
 						$eerorflag = 1;
 					}
 					else{
 						jQuery("#wyear").removeClass('error');
 					}
 					if($eerorflag==0){
 						flag1=1;
 						jQuery('#loading').show();
 						jQuery("#wcardnumber").removeClass('error');
 						jQuery("#wcvv").removeClass('error');
 						jQuery("#wmonth").removeClass('error');
 						jQuery("#wyear").removeClass('error');


 						jQuery.ajax({
							type: "POST",
							url: "<?php echo site_url(); ?>/wp-admin/admin-ajax.php",
						
							dataType: "json",
							
							data: {
								action: 'bix_card_payment_logout',
								// add your parameters here
								formdata: jQuery('#cart-payment-beforelogin').serialize()
							},
							beforeSend: function() {
							},
							success: function (output) {

								// alert(output.code);
								jQuery('#loading').hide();
								if( output.code==202 )
								{
									jQuery("#card-wrap-withoutlogin").dialog("close");
									jQuery( "#payment-suc" ).dialog();
									jQuery('form[name=checkout]').submit();
									
								}
								else if( output.code==400 )
								{
									jQuery("#card-wrap-withoutlogin").dialog("close");
									jQuery( "#payment-unsuc" ).dialog();
								    //jQuery('form[name=checkout]').submit();
								}
								else if( output.code==500 )
								{
									jQuery("#card-wrap-withoutlogin").dialog("close");
									jQuery( "#payment-unsuc" ).dialog();
								}
								else{
									jQuery("#card-wrap-withoutlogin").dialog("close");
									jQuery( "#payment-unsuc" ).dialog();
								}

							}
						});
 					}
				}

	    	});
	    	
	    	jQuery(document).on('click', '#non-bix-mem', function(e){

	    		jQuery( "#login-wrap" ).dialog("close");
	    		jQuery( "#card-wrap-withoutlogin" ).dialog();

	    	});

	    	jQuery(document).on('click', '#payment-suc-ok', function(e){

	    		jQuery("#payment-suc").dialog("close");
	    	});

	    	jQuery(document).on('click', '#payment-unsuc-ok', function(e){

	    		jQuery("#payment-unsuc").dialog("close");
	    	});
	    	
	    	jQuery(document).on('click', '.ui-dialog-titlebar-close', function(e){
	    		location.reload();
	    	});
	
		</script>
			

			<div id="payment-suc" style="display: none;">
		 		<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/assets/img/checked.png" alt="">
		 		<h3>Payment with bank card successful!</h3>
				<!-- <a href="#" class="payment-suc-ok" id="payment-suc-ok">ok</a> -->
			</div>
			<div id="payment-unsuc" style="display: none;">
		 		<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/assets/img/cancel.png" alt="">
				<h3>Payment with bank card unsuccessful!</h3>
				<!-- <a href="#" class="payment-unsuc-ok" id="payment-unsuc-ok">ok</a> -->
			</div>

			<div id="loading" style="display: none;">
			  <img id="loading-image" src="<?php echo plugin_dir_url( __FILE__ ); ?>/assets/img/ajax-loader.gif" alt="Loading..." />
			</div>


			<div id="card-wrap-withoutlogin" style="display: none;">
		 		<!-- <a href="#" class="logo"><img src="<?php echo plugin_dir_url( __FILE__ ); ?>/assets/img/logo.png" alt=""></a> -->
				<h3>Amount : $<span id="price-span"></span></h3>
				<form action="" method="post" name="cart-payment-beforelogin"  id="cart-payment-beforelogin">
					<input type="hidden" name="amount" id="popupamount" value="1">
					<label for="">
						Enter card data
					</label>
					<input type="tel" name="wcardnumber"  id="wcardnumber" onKeyPress="if(this.value.length==16) return false;" onkeyup="this.value=this.value.replace(/[^\d]/,'')" placeholder="Card Number :" required="">
					<div class="cvv-code">
						<label for="">
							CVV Code
						</label>
						<input type="tel" name="wcvv" id="wcvv" onKeyPress="if(this.value.length==4) return false;" onkeyup="this.value=this.value.replace(/[^\d]/,'')" placeholder="CVV Code" required="">	
					</div>
					<br>
					<input type="hidden" name="shipping_address_popup"  id="shipping_address_popup" value="">
					<input type="hidden" name="shipping_postcode_popup"  id="shipping_postcode_popup" value="">
					<div class="expy-date">
						<label for="">Expiration</label>
						<input type="tel" placeholder="MM"  max="31" onKeyPress="if(this.value.length==2) return false;" onkeyup="this.value=this.value.replace(/[^\d]/,'')" class="month" name="wmonth"  id="wmonth" required="">
						<input type="tel" placeholder="YY"  max="12" onKeyPress="if(this.value.length==2) return false;" onkeyup="this.value=this.value.replace(/[^\d]/,'')" class="year" name="wyear" id="wyear" required="">
					</div>
					<input type="submit"  id="card_payment_logout" value="Pay with card">			
				</form>
			</div>
			
			<p class="paywithcardp"><?php echo $this->description; ?></p>
		<?php
	}
}