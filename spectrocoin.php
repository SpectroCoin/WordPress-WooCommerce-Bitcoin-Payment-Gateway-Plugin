<?php
/*
Plugin Name: SpectroCoin
Plugin URI:  https://github.com/SpectroFinance/SpectroCoin-Merchant-WordPress-WooCommerce
Description: This module integrates SpectroCoin Payments with Wordpress's Woocommerce a plugin to accept Bitcoin payments.
Version:     0.2
Author:      SpectroCoin
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

define( 'SC_REQUIRED_PHP_VERSION', '5.3' );

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function spectrocoin_requirements_met() {
	global $wp_version;
	if ( version_compare( PHP_VERSION, SC_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return false;
	}

	return true;
}

// Function to display admin notice
function spectrocoin_admin_notice( $message ) {
	?>
	<div class="notice notice-error">
		<p><?php echo esc_html( $message ); ?></p>
	</div>
	<?php
}

// Function to handle plugin deactivation
function spectrocoin_deactivate_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

add_action( 'plugins_loaded', 'init_spectrocoin_plugin' );

function init_spectrocoin_plugin() {
	if ( !spectrocoin_requirements_met() ) {
		if ( version_compare( PHP_VERSION, SC_REQUIRED_PHP_VERSION, '<' ) ) {
			spectrocoin_admin_notice( 'SpectroCoin plugin requires PHP version ' . SC_REQUIRED_PHP_VERSION . ' or greater.' );
		} else {
			spectrocoin_admin_notice( 'SpectroCoin plugin requires WooCommerce to be installed and activated.' );
		}

		// Deactivate the plugin if requirements are not met
		spectrocoin_deactivate_plugin();

		return;
	}

	require_once( __DIR__ . '/class-wc-gateway-spectrocoin.php' );

	if ( class_exists( 'WC_Gateway_Spectrocoin' ) ) {
		add_filter( 'woocommerce_payment_gateways', 'spectrocoin_gateway_class' );
		// Add custom link to the plugin list
		add_filter( 'plugin_action_links', 'spectrocoin_add_custom_link', 10, 2 );
	}
}

function spectrocoin_gateway_class( $methods ) {
	$methods[] = 'WC_Gateway_Spectrocoin'; 
	return $methods;
}

function get_sc_payment_settings_url() {
	$checkout_url = get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=spectrocoin' );
	return esc_url( $checkout_url );
}

// Function to add custom link
function spectrocoin_add_custom_link( $links, $file ) {
	if ( strpos( $file, 'spectrocoin' ) !== false ) {
		// Get the URL for the payment settings
		$settings_url = get_sc_payment_settings_url();

		// Create the custom link HTML
		$custom_link = '<a href="' . esc_url( $settings_url ) . '">Settings</a>';

		// Add the custom link to the $links array
		array_push( $links, $custom_link );
	}

	return $links;
}
