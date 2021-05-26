<?php
/*
Plugin Name: SpectroCoin
Plugin URI:  https://github.com/SpectroFinance/SpectroCoin-Merchant-WordPress-WooCommerce
Description: This module integrates SpectroCoin Payments with Wordpress's Woocommerce aplugin to accept Bitcoin payments.
Version:     0.1
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
	return true;
}

add_action( 'plugins_loaded', 'init_spectrocoin_plugin' );

function init_spectrocoin_plugin() {
	if ( spectrocoin_requirements_met() ) {
		require_once( __DIR__ . '/class-wc-gateway-spectrocoin.php' );

		if ( class_exists( 'WC_Gateway_Spectrocoin' ) ) {
			add_filter( 'woocommerce_payment_gateways', 'spectrocoin_gateway_class' );
		}
	} else {
		// TODO make message more informative
		trigger_error('Spectrocoin plugin\'s requirements not met. Update you Wordpress or PHP');
	}
}

function spectrocoin_gateway_class( $methods ) {
	$methods[] = 'WC_Gateway_Spectrocoin'; 
	return $methods;
}