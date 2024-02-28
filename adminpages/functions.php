<?php
/****************************************************************

	IMPORTANT. PLEASE READ.

	DO NOT EDIT THIS FILE or any other file in the /wp-content/plugins/paid-memberships-pro/ directory.
	Doing so could break the PMPro plugin and/or keep you from upgrading this plugin in the future.
	We regularly release updates to the plugin, including important security fixes and new features.
	You want to be able to upgrade.

	If you were asked to insert code into "your functions.php file", it was meant that you edit the functions.php
	in the root folder of your active theme. e.g. /wp-content/themes/twentytwelve/functions.php
	You can also create a custom plugin to place customization code into. Instructions are here:
	https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/

	Further documentation for customizing Paid Memberships Pro can be found here:
	https://www.paidmembershipspro.com/documentation/

****************************************************************/

/*
	Checks if PMPro settings are complete or if there are any errors.
	
	Stripe currently does not support:
	* Billing Limits.
*/
function pmpro_checkLevelForStripeCompatibility($level = NULL)
{
	$gateway = get_option( "pmpro_gateway");
	if($gateway == "stripe")
	{
		global $wpdb;

		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{
					if(!pmpro_checkLevelForStripeCompatibility($level))
						return false;
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1" , $level ) );

			// Check if this level has a billing period longer than 1 year.
			if ( 
				( $level->cycle_period === 'Year' && $level->cycle_number > 1 ) ||
				( $level->cycle_period === 'Month' && $level->cycle_number > 12 ) ||
				( $level->cycle_period === 'Week' && $level->cycle_number > 52 ) ||
				( $level->cycle_period === 'Day' && $level->cycle_number > 365 )
			) {
				return false;
			}
		}
	}

	return true;
}

/*
	Checks if PMPro settings are complete or if there are any errors.
	
	Payflow currently does not support:
	* Trial Amounts > 0.
*/
function pmpro_checkLevelForPayflowCompatibility($level = NULL)
{
	$gateway = get_option( "pmpro_gateway");
	if($gateway == "payflowpro")
	{
		global $wpdb;

		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{					
					if(!pmpro_checkLevelForPayflowCompatibility($level))
						return false;
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1" , $level ) );

			//check this level
			if($level->trial_amount > 0)
			{
				return false;
			}
		}
	}

	return true;
}

/*
	Checks if PMPro settings are complete or if there are any errors.
	
	Braintree currently does not support:
	* Trial Amounts > 0.
	* Daily or Weekly billing periods.
	* Also check that a plan has been created at Braintree
*/
function pmpro_checkLevelForBraintreeCompatibility($level = NULL)
{
	$gateway = get_option( "pmpro_gateway");
	if($gateway == "braintree")
	{
		global $wpdb;

		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{
					if(!pmpro_checkLevelForBraintreeCompatibility($level))
						return false;
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1" , $level ) );

			//check this level
			if($level->trial_amount > 0 ||
			   ($level->cycle_number > 0 && ($level->cycle_period == "Day" || $level->cycle_period == "Week")))
			{
				return false;
			}
			
			//check for plan
			if(pmpro_isLevelRecurring($level)) {
				if(!PMProGateway_braintree::checkLevelForPlan($level->id))
					return false;
			}
		}
	}

	return true;
}

/**
 * Checks if a discount code's settings are compatible with the active gateway.
 *
 */
function pmpro_check_discount_code_for_gateway_compatibility( $discount_code = NULL ) {
	// Return if no gateway is set.
	$gateway = get_option( 'pmpro_gateway' );
	if ( empty( $gateway ) ) {
		return true;
	}

	global $wpdb;
	
	// Check ALL the discount codes if none specified.
	if ( empty( $discount_code ) ) {
		$discount_codes = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_discount_codes" );
		if ( ! empty( $discount_codes ) ) {
			foreach ( $discount_codes as $discount_code ) {
				if ( ! pmpro_check_discount_code_for_gateway_compatibility( $discount_code ) ) {
					return false;
				}
			}
		}
	} else {
		if ( ! is_numeric( $discount_code ) ) {
			// Convert the code array into a single id.
			$discount_code = $discount_code->id;
		}
		// Check ALL the discount code levels for this code.
		$discount_codes_levels = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = %d", $discount_code ) );
		if ( ! empty( $discount_codes_levels ) ) {
			foreach ( $discount_codes_levels as $discount_code_level ) {
				if ( ! pmpro_check_discount_code_level_for_gateway_compatibility( $discount_code_level ) ) {
					return false;
				}
			}
		}
	}
	return true;
}

/**
 * Checks if a discount code's settings are compatible with the active gateway.
 *
 */
function pmpro_check_discount_code_level_for_gateway_compatibility( $discount_code_level = NULL ) {
	// Return if no gateway is set.
	$gateway = get_option( 'pmpro_gateway' );
	if ( empty( $gateway ) ) {
		return true;
	}

	global $wpdb;

	// Check ALL the discount code levels if none specified.
	if ( empty( $discount_code_level ) ) {
		$sqlQuery = "SELECT * FROM $wpdb->pmpro_discount_codes_levels ORDER BY id ASC";
		$discount_codes_levels = $wpdb->get_results($sqlQuery, OBJECT);
		if ( ! empty( $discount_codes_levels ) ) {
			foreach ( $discount_codes_levels as $discount_code_level ) {
				if ( ! pmpro_check_discount_code_level_for_gateway_compatibility( $discount_code_level ) ) {
					return false;
				}
			}
		}
	} else {
		// Need to look it up?
		if ( is_numeric( $discount_code_level ) ) {
			$discount_code_level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_discount_codes_levels WHERE id = %d LIMIT 1" , $discount_code_level ) );
		}

		// Check this discount code level for gateway compatibility
		if ( $gateway == 'stripe' ) {
			// Check if this code level has a billing period longer than 1 year.
			if ( 
				( $discount_code_level->cycle_period === 'Year' && intval( $discount_code_level->cycle_number ) > 1 ) ||
				( $discount_code_level->cycle_period === 'Month' && intval( $discount_code_level->cycle_number ) > 12 ) ||
				( $discount_code_level->cycle_period === 'Week' && intval( $discount_code_level->cycle_number ) > 52 ) ||
				( $discount_code_level->cycle_period === 'Day' && intval( $discount_code_level->cycle_number ) > 365 )
			) {
				global $pmpro_stripe_error;
				$pmpro_stripe_error = true;
				return false;
			}
		} elseif ( $gateway == 'payflowpro' ) {
			if ( $discount_code_level->trial_amount > 0 ) {
				global $pmpro_payflow_error;
				$pmpro_payflow_error = true;
				return false;
			}
		} elseif ( $gateway == 'braintree' ) {
			if ( $discount_code_level->trial_amount > 0 ||
			   ( $discount_code_level->cycle_number > 0 && ( $discount_code_level->cycle_period == "Day" || $discount_code_level->cycle_period == "Week" ) ) ) {
			   	global $pmpro_braintree_error;
				$pmpro_braintree_error = true;
				return false;
			}
		} elseif ( $gateway == 'twocheckout' ) {
			if ( $discount_code_level->trial_amount > $discount_code_level->billing_amount ) {
				global $pmpro_twocheckout_error;
				$pmpro_twocheckout_error = true;
				return false;
			}
		}
	}

	return true;
}

/*
	Checks if PMPro settings are complete or if there are any errors.
	
	2Checkout currently does not support:
	* Trial amounts less than or greater than the absolute value of amonthly recurring amount.
*/
function pmpro_checkLevelForTwoCheckoutCompatibility($level = NULL)
{
	$gateway = get_option( "pmpro_gateway");
	if($gateway == "twocheckout")
	{
		global $wpdb;

		//check ALL the levels
		if(empty($level))
		{
			$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id ASC";
			$levels = $wpdb->get_results($sqlQuery, OBJECT);
			if(!empty($levels))
			{
				foreach($levels as $level)
				{					
					if(!pmpro_checkLevelForTwoCheckoutCompatibility($level))
						return false;
				}
			}
		}
		else
		{
			//need to look it up?
			if(is_numeric($level))
				$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = %d LIMIT 1" , $level ) );

			//check this level
			if(pmpro_isLevelTrial($level))
			{
				return false;
			}
		}
	}

	return true;
}

/**
 * Get the gateway-related classes for fields on the payment settings page.
 *
 * @param string $field The name of the field to check.
 * @param bool $force If true, it will rebuild the cached results.
 *
 * @since  1.8
 */
function pmpro_getClassesForPaymentSettingsField($field, $force = false)
{
	global $pmpro_gateway_options;
	$pmpro_gateways = pmpro_gateways();

	//build array of gateways and options
	if(!isset($pmpro_gateway_options) || $force)
	{
		$pmpro_gateway_options = array();

		foreach($pmpro_gateways as $gateway => $label)
		{
			//get options
			if(class_exists('PMProGateway_' . $gateway) && method_exists('PMProGateway_' . $gateway, 'getGatewayOptions'))
			{
				$pmpro_gateway_options[$gateway] = call_user_func(array('PMProGateway_' . $gateway, 'getGatewayOptions'));
			}
		}
	}

	//now check where this field shows up
	$rgateways = array();
	foreach($pmpro_gateway_options as $gateway => $options)
	{
		if(in_array($field, $options))
			$rgateways[] = "gateway_" . $gateway;
	}

	//return space separated string
	return implode(" ", $rgateways);
}


/**
 * Code to handle emailing billable invoices.
 *
 * @since 1.8.6
 */

/**
 * Get the gateway-related classes for fields on the payment settings page.
 *
 * @param string $field The name of the field to check.
 * @param bool $force If true, it will rebuild the cached results.
 *
 * @since  1.8
 */
function pmpro_add_email_order_modal() {

	// emailing?
	if ( ! empty( $_REQUEST['pmpro_email_to'] ) && ! empty( $_REQUEST['pmpro_email_order'] ) ) {
		// verify nonce
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['pmpro_email_invoice_nonce'] ), 'pmpro_email_invoice' ) ) {
			wp_die( __( 'Security error.', 'paid-memberships-pro' ) );
		}

		$email = new PMProEmail();
		$user  = get_user_by( 'email', sanitize_email( $_REQUEST['pmpro_email_to'] ) );
		$order = new MemberOrder( intval( $_REQUEST['pmpro_email_order'] ) );
		if ( ! empty( $user ) && ! empty( $order ) && $email->sendBillableInvoiceEmail( $user, $order ) ) { ?>
			<div class="notice notice-success pmpro_message pmpro_success is-dismissible">
				<p><?php esc_html_e( 'Invoice emailed successfully.', 'paid-memberships-pro' ); ?></p>
			</div>
		<?php } else { ?>
			<div class="notice notice-error pmpro_message pmpro_error is-dismissible">
				<p><?php esc_html_e( 'Error emailing invoice.', 'paid-memberships-pro' ); ?></p>
			</div>
		<?php }
	}

	?>
	<script>
		// Update fields in email modal.
		jQuery(document).ready(function ($) {
			var order, order_id;
			$('.email_link').on('click',function () {
				order_id = $(this).data('order');
				$('input[name=pmpro_email_order]').val(order_id);
				// Get email address from order ID
				data = {
					action: 'pmpro_get_order_json',
					order_id: order_id
				};
				$.post(ajaxurl, data, function (response) {
					order = JSON.parse(response);
					$('input[name=pmpro_email_to]').val(order.Email);
				});
			});
		});
	</script>
	<?php add_thickbox(); ?>
	<div id="email_invoice" style="display:none;">
		<h3><?php esc_html_e( 'Email Invoice', 'paid-memberships-pro' ); ?></h3>
		<form method="post" action="">
			<input type="hidden" name="pmpro_email_order" value=""/>
			<?php _e( 'Send an invoice for this order to: ', 'paid-memberships-pro' ); ?>
			<input type="text" value="" name="pmpro_email_to"/>
			<?php wp_nonce_field( 'pmpro_email_invoice', 'pmpro_email_invoice_nonce' ); ?>
			<button class="button button-primary alignright"><?php esc_html_e( 'Send Email', 'paid-memberships-pro' ); ?></button>
		</form>
	</div>
	<?php
}

/**
 * Get the available level templates.
 * 
 * @return array $level_templates An associative array of level templates and their attributes.
 */
function pmpro_edit_level_templates() {

	$level_templates = array(
		'free' => array(
			'name' => __( 'Free', 'paid-memberships-pro' ),
			'description' => __( 'A free membership level that never expires.', 'paid-memberships-pro' )
		),
		'monthly' => array(
			'name' => __( 'Monthly', 'paid-memberships-pro' ),
			'description' => __( 'Charge a recurring monthly subscription that never ends.', 'paid-memberships-pro' )
		),
		'annual' => array(
			'name' => __( 'Annual', 'paid-memberships-pro' ),
			'description' => __( 'Charge a recurring annual subscription that never ends.', 'paid-memberships-pro' )
		),
		'onetime' => array(
			'name' => __( 'One Time', 'paid-memberships-pro' ),
			'description' => __( 'Charge a one-time payment for a fixed period.', 'paid-memberships-pro' )
		),
		'lifetime' => array(
			'name' => __( 'Lifetime', 'paid-memberships-pro' ),
			'description' => __( 'Charge a one-time payment for a level that never expires.', 'paid-memberships-pro' )
		),
		'trial' => array(
			'name' => __( 'Trial', 'paid-memberships-pro' ),
			'description' => __( 'Trial membership that captures recurring payment info at checkout.', 'paid-memberships-pro' )
		),
		'none' => array(
			'name' => __( 'Advanced', 'paid-memberships-pro' ),
			'description' => __( 'Show all settings. I want to create an advanced membership level.', 'paid-memberships-pro' )
		),
		'approvals' => array(
			'name' => __( 'Approval', 'paid-memberships-pro' ),
			'description' => __( 'Give admins the ability to approve or deny members.', 'paid-memberships-pro' ),
			'external-link' => 'https://www.paidmembershipspro.com/add-ons/approval-process-membership/',
			'type'	=> 'add_on'
		),
		'gift' => array(
			'name' => __( 'Gift', 'paid-memberships-pro' ),
			'description' => __( 'Allow anyone to purchase a gift of membership.' ),
			'external-link' => 'https://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/',
			'type' => 'add_on'
		)
	);

	/**
	 * Filter to add or remove level templates from the Membership Levels > Add New popup.
	 *
	 * @since 2.9
	 *
	 * @param $level_templates array An array of templates with name and description.
	 *
	 * @return $level_templates array An array of templates with name and description.
	 */
	$level_templates = apply_filters( 'pmpro_membershiplevels_templates', $level_templates );

	return $level_templates;
}
