<?php
/*
Plugin Name: SpectroCoin Bitcoin Payment Gateway
Author:      SpectroCoin
Text Domain: spectrocoin
Plugin URI:  https://github.com/SpectroFinance/SpectroCoin-Merchant-WordPress-WooCommerce
Description: This module integrates SpectroCoin Payments with Wordpress's Woocommerce a plugin to accept Bitcoin payments.
Version:     1.0.1
Requires at least: 6.1
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
	die('Access denied.');
}

define('SC_REQUIRED_PHP_VERSION', '7.4');
define('SC_WP_VERSION', '6.1');

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function spectrocoin_requirements_met()
{
	$requirements_met = true;
	$message = '';
	if (version_compare(PHP_VERSION, SC_REQUIRED_PHP_VERSION, '<')) {
		$requirements_met = false;
		$message .= 'SpectroCoin plugin requires PHP version ' . SC_REQUIRED_PHP_VERSION . ' or greater. ';
	}

	if (!function_exists('is_plugin_active')) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if (version_compare($GLOBALS['wp_version'], SC_WP_VERSION, '<')) {
		$requirements_met = false;
		$message .= 'SpectroCoin plugin requires WordPress version ' . SC_WP_VERSION . ' or greater. ';
	}

	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		$requirements_met = false;
		$message .= 'SpectroCoin plugin requires WooCommerce to be installed and activated. ';
	}

	if (!$requirements_met) {
		spectrocoin_admin_notice($message);
		spectrocoin_deactivate_plugin();
	}

	return $requirements_met;
}

function spectrocoin_admin_notice($message)
{
	?>
	<div class="notice notice-error">
		<p>
			<?php echo esc_html($message); ?>
		</p>
	</div>
	<?php
}

function spectrocoin_deactivate_plugin()
{
	deactivate_plugins(plugin_basename(__FILE__));
}

add_action('plugins_loaded', 'init_spectrocoin_plugin');
add_action('admin_enqueue_scripts', 'spectrocoin_enqueue_admin_styles');

function init_spectrocoin_plugin()
{
	if (spectrocoin_requirements_met()) {
		require_once(__DIR__ . '/class-wc-gateway-spectrocoin.php');

		if (class_exists('WC_Gateway_Spectrocoin')) {
			add_filter('woocommerce_payment_gateways', 'spectrocoin_gateway_class');
			add_filter('plugin_action_links', 'spectrocoin_add_custom_link', 10, 2);
		}
	}
}

function spectrocoin_gateway_class($methods)
{
	$methods[] = 'WC_Gateway_Spectrocoin';
	return $methods;
}

function get_sc_payment_settings_url()
{
	$checkout_url = get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=spectrocoin');
	return esc_url($checkout_url);
}

function spectrocoin_add_custom_link($links, $file)
{
	if (strpos($file, 'spectrocoin') !== false) {
		$settings_url = get_sc_payment_settings_url();
		$custom_link = '<a href="' . esc_url($settings_url) . '">Settings</a>';
		array_push($links, $custom_link);
	}
	return $links;
}

function spectrocoin_enqueue_admin_styles()
{
	$current_screen = get_current_screen();
	if ($current_screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'spectrocoin') {
		wp_enqueue_style('spectrocoin-payment-settings-css', plugin_dir_url(__FILE__) . 'assets/style/settings.css', array(), '1.0.0');
	}
}