<?php
/**
 * Get array of PMPro Capabilities
 * Used below to figure out which page to have the main Membership menu link to.
 * The order is important. The first cap the user has is used.
 */
function pmpro_getPMProCaps() {
	$pmpro_caps = array(
		//pmpro_memberships_menu //this controls viewing the menu itself
		'pmpro_dashboard',				
		'pmpro_memberslist',
		'pmpro_orders',
		'pmpro_reports',				
		'pmpro_membershiplevels',
		'pmpro_discountcodes',
		'pmpro_pagesettings',
		'pmpro_paymentsettings',
		'pmpro_emailsettings',		
		'pmpro_emailtemplates',
		'pmpro_userfields',
		'pmpro_advancedsettings',
		'pmpro_addons',
		'pmpro_subscriptions',
		'pmpro_updates',
		'pmpro_manage_pause_mode'
	);

	return $pmpro_caps;
}

/**
 * Dashboard Menu
 */
function pmpro_add_pages() {
	global $wpdb;

	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach( $pmpro_caps as $cap ) {
		if( current_user_can( $cap ) ) {
			$top_menu_cap = $cap;
			break;
		}
	}

	if( empty( $top_menu_cap ) ) {
		return;
	}

	// Top level menu
	add_menu_page( __( 'Memberships', 'paid-memberships-pro' ), __( 'Memberships', 'paid-memberships-pro' ), 'pmpro_memberships_menu', 'pmpro-dashboard', $top_menu_cap, 'dashicons-groups', 30 );
	
	// Main submenus
	add_submenu_page( 'pmpro-dashboard', __( 'Dashboard', 'paid-memberships-pro' ), __( 'Dashboard', 'paid-memberships-pro' ), 'pmpro_dashboard', 'pmpro-dashboard', 'pmpro_dashboard' );
	$list_table_hook = add_submenu_page( 'pmpro-dashboard', __( 'Members', 'paid-memberships-pro' ), __( 'Members', 'paid-memberships-pro' ), 'pmpro_memberslist', 'pmpro-memberslist', 'pmpro_memberslist' );
	add_submenu_page( 'pmpro-dashboard', __( 'Orders', 'paid-memberships-pro' ), __( 'Orders', 'paid-memberships-pro' ), 'pmpro_orders', 'pmpro-orders', 'pmpro_orders' );
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro-subscriptions' ) {
		add_submenu_page( 'pmpro-dashboard', __( 'Subscriptions', 'paid-memberships-pro' ), __( 'Subscriptions', 'paid-memberships-pro' ), 'pmpro_subscriptions', 'pmpro-subscriptions', 'pmpro_subscriptions' );
	}
	add_submenu_page( 'pmpro-dashboard', __( 'Reports', 'paid-memberships-pro' ), __( 'Reports', 'paid-memberships-pro' ), 'pmpro_reports', 'pmpro-reports', 'pmpro_reports' );
	add_submenu_page( 'pmpro-dashboard', __( 'Settings', 'paid-memberships-pro' ), __( 'Settings', 'paid-memberships-pro' ), 'pmpro_membershiplevels', 'pmpro-membershiplevels', 'pmpro_membershiplevels' );
	add_submenu_page( 'pmpro-dashboard', __( 'Add Ons', 'paid-memberships-pro' ), __( 'Add Ons', 'paid-memberships-pro' ), 'pmpro_addons', 'pmpro-addons', 'pmpro_addons' );

	// Check License Key for Correct Link Color
	$key = get_option( 'pmpro_license_key', '' );
	if ( pmpro_license_isValid( $key, NULL ) ) {
		$span_color = '#33FF00';
	} else {
		$span_color = '#FCD34D';
	}
	add_submenu_page( 'pmpro-dashboard', __( 'License', 'paid-memberships-pro' ), __( '<span style="color: ' . $span_color . '">License</span>', 'paid-memberships-pro' ), 'manage_options', 'pmpro-license', 'pmpro_license_settings_page' );
	add_submenu_page( 'pmpro-member', __( 'Add Member', 'paid-memberships-pro' ), __( '<span>Add Member</span>', 'paid-memberships-pro' ), 'manage_options', 'pmpro-member', 'pmpro_member_edit_display' );

	// Settings tabs
	add_submenu_page( 'admin.php', __( 'Discount Codes', 'paid-memberships-pro' ), __( 'Discount Codes', 'paid-memberships-pro' ), 'pmpro_discountcodes', 'pmpro-discountcodes', 'pmpro_discountcodes' );
	add_submenu_page( 'admin.php', __( 'Page Settings', 'paid-memberships-pro' ), __( 'Page Settings', 'paid-memberships-pro' ), 'pmpro_pagesettings', 'pmpro-pagesettings', 'pmpro_pagesettings' );
	add_submenu_page( 'admin.php', __( 'Payment Settings', 'paid-memberships-pro' ), __( 'Payment Settings', 'paid-memberships-pro' ), 'pmpro_paymentsettings', 'pmpro-paymentsettings', 'pmpro_paymentsettings' );
	add_submenu_page( 'admin.php', __( 'Email Settings', 'paid-memberships-pro' ), __( 'Email Settings', 'paid-memberships-pro' ), 'pmpro_emailsettings', 'pmpro-emailsettings', 'pmpro_emailsettings' );
	add_submenu_page( 'admin.php', __( 'Email Templates', 'paid-memberships-pro' ), __( 'Email Templates', 'paid-memberships-pro' ), 'pmpro_emailtemplates', 'pmpro-emailtemplates', 'pmpro_emailtemplates' );
	add_submenu_page( 'admin.php', __( 'User Fields', 'paid-memberships-pro' ), __( 'User Fields', 'paid-memberships-pro' ), 'pmpro_userfields', 'pmpro-userfields', 'pmpro_userfields' );
	add_submenu_page( 'admin.php', __( 'Advanced Settings', 'paid-memberships-pro' ), __( 'Advanced Settings', 'paid-memberships-pro' ), 'pmpro_advancedsettings', 'pmpro-advancedsettings', 'pmpro_advancedsettings' );

	add_action( 'load-' . $list_table_hook, 'pmpro_list_table_screen_options' );

	//updates page only if needed
	if ( pmpro_isUpdateRequired() ) {
		add_submenu_page( 'pmpro-dashboard', __( 'Updates Required', 'paid-memberships-pro' ), __( 'Updates Required', 'paid-memberships-pro' ), 'pmpro_updates', 'pmpro-updates', 'pmpro_updates' );
	}
	
	//Logic added here in order to always reach this page if PMPro is setup. ?page=pmpro-wizard is always reachable should people want to rerun through the Setup Wizard.
	if ( pmpro_show_setup_wizard_link() ) {
		$wizard_location = 'pmpro-dashboard';
	} else {
		$wizard_location = 'admin.php';	// Registers the page, but doesn't show up in menu.
	}
	
	add_submenu_page( $wizard_location, __( 'Setup Wizard', 'paid-memberships-pro' ), __( 'Setup Wizard', 'paid-memberships-pro' ), 'pmpro_wizard', 'pmpro-wizard', 'pmpro_wizard' );
}
add_action( 'admin_menu', 'pmpro_add_pages' );

/**
 * Keep the Memberships menu selected on subpages.
 */
function pmpro_parent_file( $parent_file ) {
	global $parent_file, $plugin_page, $submenu_file;
	
	$pmpro_settings_tabs = array(
		'pmpro-membershiplevels',
		'pmpro-discountcodes',
		'pmpro-pagesettings',
		'pmpro-paymentsettings',
		'pmpro-emailsettings',
		'pmpro-emailtemplates',
		'pmpro-advancedsettings',
	);
	
	if( isset( $_REQUEST['page']) && in_array( $_REQUEST['page'], $pmpro_settings_tabs ) ) {
		$parent_file = 'pmpro-dashboard';
		$plugin_page = 'pmpro-dashboard';
		$submenu_file = 'pmpro-membershiplevels';
	}
	
	return $parent_file;
}
add_filter( 'parent_file', 'pmpro_parent_file' );

/**
 * Filter the title of the Edit Member admin page.
 */
function pmpro_admin_title( $admin_title, $title ) {
	// Only filter on the Edit Member page.
	if ( isset( $_REQUEST['page']) && $_REQUEST['page'] === 'pmpro-member' ) {
		$user = PMPro_Member_Edit_Panel::get_user();
		if ( empty( $user->ID ) ) {
			$title = __( 'Add Member', 'paid-memberships-pro' );
		} else {
			/* translators: %s: User's display name. */
			$title = sprintf( __( 'Edit Member: %s', 'paid-memberships-pro' ), $user->display_name );
		}
		/* translators: Edit/Add Member Admin screen title. 1: Screen name, 2: Site name. */
		$admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), $title, get_bloginfo( 'name' ) );
	}
	return $admin_title;
}
add_filter( 'admin_title', 'pmpro_admin_title', 10, 2 );

/**
 * Admin Bar
 */
function pmpro_admin_bar_menu() {
	global $wp_admin_bar;

	//view menu at all?
	if ( ! current_user_can( 'pmpro_memberships_menu' ) || ! is_admin_bar_showing() ) {
		return;
	}
	
	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach ( $pmpro_caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			$top_menu_page = str_replace( '_', '-', $cap );
			break;
		}
	}

	$wp_admin_bar->add_menu(
		array(
			'id' => 'paid-memberships-pro',
			'title' => __( '<span class="ab-icon"></span>Memberships', 'paid-memberships-pro' ),
			'href' => admin_url( 'admin.php?page=' . $top_menu_page )
		) 
	);

	// Add menu item for Dashboard.
	if ( current_user_can( 'pmpro_dashboard' ) ) {
		$wp_admin_bar->add_menu( 
			array(
				'id' => 'pmpro-dashboard',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Dashboard', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-dashboard' ) 
			)
		);
	}
	
	// Add menu item for Members List.
	if ( current_user_can( 'pmpro_memberslist' ) ) {
		$wp_admin_bar->add_menu( 
			array(
				'id' => 'pmpro-members-list',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Members', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-memberslist' )
			)
		);
	}

	// Add menu item for Orders.
	if ( current_user_can( 'pmpro_orders' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-orders',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Orders', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-orders' )
			)
		);
	}

	// Add menu item for Reports.
	if ( current_user_can( 'pmpro_reports' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-reports',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Reports', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-reports' )
			)
		);
	}

	// Add menu item for Settings.
	if ( current_user_can( 'pmpro_membershiplevels' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-membership-levels',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Settings', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-membershiplevels' )
			)
		);
	}

	// Add menu item for Add Ons.
	if ( current_user_can( 'pmpro_addons' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-addons',
				'parent' => 'paid-memberships-pro',
				'title' => __( 'Add Ons', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-addons' )
			)
		);
	}

	// Add menu item for License.
	if ( current_user_can( 'manage_options' ) ) {
		// Check License Key for Correct Link Color
		$key = get_option( 'pmpro_license_key', '' );
		if ( pmpro_license_isValid( $key, NULL ) ) {
			$span_color = '#33FF00';
		} else {
			$span_color = '#FCD34D';
		}
		$wp_admin_bar->add_menu(
			array(
				'id' => 'pmpro-license',
				'parent' => 'paid-memberships-pro',
				'title' => __( '<span style="color: ' . $span_color . '; line-height: 26px;">License</span>', 'paid-memberships-pro' ),
				'href' => admin_url( 'admin.php?page=pmpro-license' )
			)
		);
	}
}
add_action( 'admin_bar_menu', 'pmpro_admin_bar_menu', 1000);

/**
 * Add the "Admin Membership Access" menu to the admin bar.
 */
function pmpro_admin_membership_access_menu_bar() {
	global $wp_admin_bar, $current_user;

	// Only show when viewing the frontend of the site.
	if ( is_admin() ) {
		return;
	}

	/**
	 * Filter to hide the "Admin Membership Access" menu in the admin bar.
	 * @since TBD
	 * @param bool $hide Whether to hide the "Admin Membership Access" menu in the admin bar. Default false.
	 */
	if ( apply_filters( 'pmpro_hide_admin_membership_access_toolbar', false ) ) {
		return;
	}

	// View menu at all?
	if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
		return;
	}

	// Let's save or delete the option now.
	if ( ! empty( $_REQUEST['pmpro-admin-membership-access'] ) ) {

		// Check the nonce.
		check_admin_referer( 'pmpro_admin_membership_access', 'pmpro_admin_membership_access_nonce' );

		// Let's get the value of the view_as now:
		$admin_membership_access = sanitize_text_field( $_REQUEST['pmpro-admin-membership-access'] );

		if ( $admin_membership_access == 'no' ) {
			update_user_meta( $current_user->ID, 'pmpro_admin_membership_access', 'no' );
		} elseif ( $admin_membership_access == 'current' ) {
			update_user_meta( $current_user->ID, 'pmpro_admin_membership_access', 'current' );
		} else {
			update_user_meta( $current_user->ID, 'pmpro_admin_membership_access', 'yes' );
		}

		echo "<meta http-equiv='refresh' content='0'>";
	}

	// Let's get the option now so we can show it.
	$admin_membership_access = get_user_meta( $current_user->ID, 'pmpro_admin_membership_access', true );

	// Set the title and the option value.
	if ( 'no' === $admin_membership_access ) {
		$title = '<span class="ab-icon dashicons dashicons-hidden non-member-icon"></span>' . esc_html__( 'Viewing without membership access', 'paid-memberships-pro' );
	} elseif ( 'current' === $admin_membership_access ) {
		$title = esc_html__( 'Viewing with current membership levels', 'paid-memberships-pro' );
	} else {
		$title = '<span class="ab-icon dashicons dashicons-saved has-access-icon"></span>' . esc_html__( 'Viewing with membership access', 'paid-memberships-pro' );
		$admin_membership_access = 'yes';
	}

	$wp_admin_bar->add_menu(
		array(
			'id' => 'pmpro-admin-membership-access',
			'parent' => 'top-secondary',
			'title' => $title,
		)
	);

	// Build a form input for changing the Admin Membership Access setting.
	ob_start();
	?>
	<form method="POST" id="pmpro-admin-membership-access-form" action="">
		<select name="pmpro-admin-membership-access" id="pmpro-admin-membership-access" onchange="this.form.submit()">
			<option value="yes" <?php selected( $admin_membership_access, 'yes', true ); ?>><?php esc_html_e( 'View with membership access', 'paid-memberships-pro' ); ?></option>
			<option value="current" <?php selected( $admin_membership_access, 'current', true ); ?>><?php esc_html_e( 'View with current membership levels', 'paid-memberships-pro' ); ?></option>
			<option value="no" <?php selected( $admin_membership_access, 'no', true ); ?>><?php esc_html_e( 'View without membership access', 'paid-memberships-pro' ); ?></option>
		</select>
		<?php wp_nonce_field( 'pmpro_admin_membership_access', 'pmpro_admin_membership_access_nonce' ); ?>
	</form>
	<?php

	// Add the form to the menu.
	$wp_admin_bar->add_node( array(
		'parent' => 'pmpro-admin-membership-access',
		'id' => 'pmpro-admin-membership-access-input',
		'title' => ob_get_clean(),
	) );
}
add_action( 'admin_bar_menu', 'pmpro_admin_membership_access_menu_bar' );

/**
 * Functions to load pages from adminpages directory
 */
function pmpro_reports() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

	require_once( PMPRO_DIR . '/adminpages/reports.php' );
}

function pmpro_memberslist() {
	require_once( PMPRO_DIR . '/adminpages/memberslist.php' );
}

function pmpro_discountcodes() {
	require_once( PMPRO_DIR . '/adminpages/discountcodes.php' );
}

function pmpro_dashboard() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

	require_once( PMPRO_DIR . '/adminpages/dashboard.php' );
}

function pmpro_wizard() {
	require_once( PMPRO_DIR . '/adminpages/wizard/wizard.php' );
}

function pmpro_membershiplevels() {
	require_once( PMPRO_DIR . '/adminpages/membershiplevels.php' );
}

function pmpro_pagesettings() {
	require_once( PMPRO_DIR . '/adminpages/pagesettings.php' );
}

function pmpro_paymentsettings() {
	require_once( PMPRO_DIR . '/adminpages/paymentsettings.php' );
}

function pmpro_emailsettings() {
	require_once( PMPRO_DIR . '/adminpages/emailsettings.php' );
}

function pmpro_userfields() {
	//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );
	
	require_once( PMPRO_DIR . '/adminpages/userfields.php' );
}

function pmpro_emailtemplates() {
	require_once( PMPRO_DIR . '/adminpages/emailtemplates.php' );
}

function pmpro_advancedsettings() {
	require_once( PMPRO_DIR . '/adminpages/advancedsettings.php' );
}

function pmpro_addons() {
	require_once( PMPRO_DIR . '/adminpages/addons.php' );
}

function pmpro_orders() {
	require_once( PMPRO_DIR . '/adminpages/orders.php' );
}

function pmpro_subscriptions() {
	require_once( PMPRO_DIR . '/adminpages/subscriptions.php' );
}

function pmpro_license_settings_page() {
	require_once( PMPRO_DIR . '/adminpages/license.php' );
}

function pmpro_updates() {
	require_once( PMPRO_DIR . '/adminpages/updates.php' );
}

/**
 * Move orphaned pages under the pmpro-dashboard menu page.
 */
function pmpro_fix_orphaned_sub_menu_pages( ) {
	global $submenu;

	if ( is_array( $submenu) && array_key_exists( 'pmpro-membershiplevels', $submenu ) ) {
		$pmpro_dashboard_submenu = $submenu['pmpro-dashboard'];	
		$pmpro_old_memberships_submenu = $submenu['pmpro-membershiplevels'];
	
		if ( is_array( $pmpro_dashboard_submenu ) && is_array( $pmpro_old_memberships_submenu ) ) {
			$submenu['pmpro-dashboard'] = array_merge( $pmpro_dashboard_submenu, $pmpro_old_memberships_submenu );
		}
	}
}
add_action( 'admin_init', 'pmpro_fix_orphaned_sub_menu_pages', 99 );

/**
 * Add a post display state for special PMPro pages in the page list table.
 *
 * @param array   $post_states An array of post display states.
 * @param WP_Post $post The current post object.
 */
function pmpro_display_post_states( $post_states, $post ) {
	// Get assigned page settings.
	global $pmpro_pages;

	if ( intval( $pmpro_pages['account'] ) === $post->ID ) {
		$post_states['pmpro_account_page'] = __( 'Membership Account Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['billing'] ) === $post->ID ) {
		$post_states['pmpro_billing_page'] = __( 'Membership Billing Information Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['cancel'] ) === $post->ID ) {
		$post_states['pmpro_cancel_page'] = __( 'Membership Cancel Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['checkout'] ) === $post->ID ) {
		$post_states['pmpro_checkout_page'] = __( 'Membership Checkout Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['confirmation'] ) === $post->ID ) {
		$post_states['pmpro_confirmation_page'] = __( 'Membership Confirmation Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['invoice'] ) === $post->ID ) {
		$post_states['pmpro_invoice_page'] = __( 'Membership Invoice Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['levels'] ) === $post->ID ) {
		$post_states['pmpro_levels_page'] = __( 'Membership Levels Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['login'] ) === $post->ID ) {
		$post_states['pmpro_login_page'] = __( 'Paid Memberships Pro Login Page', 'paid-memberships-pro' );
	}

	if ( intval( $pmpro_pages['member_profile_edit'] ) === $post->ID ) {
		$post_states['pmpro_member_profile_edit_page'] = __( 'Member Profile Edit Page', 'paid-memberships-pro' );
	}

	return $post_states;
}
add_filter( 'display_post_states', 'pmpro_display_post_states', 10, 2 );

/**
 * Screen options for the List Table
 *
 * Callback for the load-($page_hook_suffix)
 * Called when the plugin page is loaded
 *
 * @since    2.0.0
 */
function pmpro_list_table_screen_options() {
	global $user_list_table;
	$arguments = array(
		'label'   => __( 'Members Per Page', 'paid-memberships-pro' ),
		'default' => 13,
		'option'  => 'users_per_page',
	);
	add_screen_option( 'per_page', $arguments );
	// instantiate the User List Table
	$user_list_table = new PMPro_Members_List_Table();
}

/**
 * Add links to the plugin action links
 */
function pmpro_add_action_links( $links ) {

	//array of all caps in the menu
	$pmpro_caps = pmpro_getPMProCaps();

	//the top level menu links to the first page they have access to
	foreach( $pmpro_caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			$top_menu_page = str_replace( '_', '-', $cap );
			break;
		}
	}

	$new_links = array(
		'<a href="' . admin_url( 'admin.php?page=' . $top_menu_page ) . '">Settings</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( PMPRO_DIR . '/paid-memberships-pro.php' ), 'pmpro_add_action_links' );

/**
 * Add links to the plugin row meta
 */
function pmpro_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'paid-memberships-pro.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( apply_filters( 'pmpro_docs_url', 'http://paidmembershipspro.com/documentation/' ) ) . '" title="' . esc_attr( __( 'View PMPro Documentation', 'paid-memberships-pro' ) ) . '">' . __( 'Docs', 'paid-memberships-pro' ) . '</a>',
			'<a href="' . esc_url( apply_filters( 'pmpro_support_url', 'http://paidmembershipspro.com/support/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'paid-memberships-pro' ) ) . '">' . __( 'Support', 'paid-memberships-pro' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_plugin_row_meta', 10, 2 );

function pmpro_users_action_links( $actions, $user ) {
	$cap = apply_filters( 'pmpro_add_member_cap', 'edit_users' );

	if ( current_user_can( $cap ) && ! empty( $user->ID ) ) {
		$actions['editmember'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => (int) $user->ID ), admin_url( 'admin.php' ) ) ) . '">' . __( 'Edit Member', 'paid-memberships-pro' ) . '</a>';
	}

	return $actions;
}

add_filter( 'user_row_actions', 'pmpro_users_action_links', 10, 2 );
