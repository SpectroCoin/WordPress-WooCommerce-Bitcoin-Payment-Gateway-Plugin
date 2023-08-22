<?php
/*
Plugin Name: SpectroCoin Bitcoin Payment Gateway
Author:      SpectroCoin
Text Domain: spectrocoin-accepting-bitcoin
Plugin URI:  https://github.com/SpectroFinance/SpectroCoin-Merchant-WordPress-WooCommerce
Description: This module integrates SpectroCoin Payments with Wordpress's Woocommerce a plugin to accept Bitcoin payments.
Version:     1.2.0
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
 * @return bool True if system requirements are met, false if not
 */
function spectrocoin_requirements_met()
{
	$requirements_met = true;
	$message = '';
	if (version_compare(PHP_VERSION, SC_REQUIRED_PHP_VERSION, '<')) {
		$requirements_met = false;
		$message .= sprintf(
			/*translators: %s is a placeholder for required PHP version */
			__('Spectrocoin plugin requires PHP version %s or greater.', 'spectrocoin-accepting-bitcoin'),
			SC_REQUIRED_PHP_VERSION
		);
	}

	if (!function_exists('is_plugin_active')) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if (version_compare($GLOBALS['wp_version'], SC_WP_VERSION, '<')) {
		$requirements_met = false;
		$message .= sprintf(
			/*translators: %s is a placeholder for required Wordpress version */
			__('SpectroCoin plugin requires WordPress version %s or greater.', 'spectrocoin-accepting-bitcoin'),
			SC_WP_VERSION
		);
	}

	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		$requirements_met = false;
		$message .= __('SpectroCoin plugin requires WooCommerce to be installed and activated.', 'spectrocoin-accepting-bitcoin');
	}

	if (!$requirements_met) {
		spectrocoin_admin_notice($message);
		spectrocoin_deactivate_plugin();
	}

	return $requirements_met;
}
/**
 * Display error message to admin
 */
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

/**
 * Handle plugin deactivation
 */
function spectrocoin_deactivate_plugin()
{
	deactivate_plugins(plugin_basename(__FILE__));
}

add_action('plugins_loaded', 'init_spectrocoin_plugin');
add_action('admin_enqueue_scripts', 'spectrocoin_enqueue_admin_styles');

/**
 * Initialize plugin
 */
function init_spectrocoin_plugin()
{
	if (spectrocoin_requirements_met()) {
		require_once(__DIR__ . '/class-wc-gateway-spectrocoin.php');
		load_plugin_textdomain('spectrocoin-accepting-bitcoin', false, dirname(plugin_basename(__FILE__)) . '/languages');

		if (class_exists('WC_Gateway_Spectrocoin')) {
			add_filter('woocommerce_payment_gateways', 'spectrocoin_gateway_class');
			add_filter('plugin_action_links', 'spectrocoin_add_custom_links_left', 10, 2);
			add_filter('plugin_row_meta', 'spectrocoin_add_custom_links_right', 10, 2);

		}
	}
}
/**
 * Gateway class initialization
 *  */
function spectrocoin_gateway_class($methods)
{
	$methods[] = 'WC_Gateway_Spectrocoin';
	return $methods;
}
/**
 * Get payment settings url
 */
function get_sc_payment_settings_url()
{
	$checkout_url = get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=spectrocoin');
	return esc_url($checkout_url);
}
/**
 * Add custom links to plugin page
 */
function spectrocoin_add_custom_links_left($links, $file)
{
	if (strpos($file, 'spectrocoin') !== false) {
		$settings_url = get_sc_payment_settings_url();
		$custom_link = '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'spectrocoin-accepting-bitcoin') . '</a>';
		array_push($links, $custom_link);
	}
	return $links;
}
/**
 * Add custom links to plugin page
 */
function spectrocoin_add_custom_links_right($plugin_meta, $file)
{
	if (strpos($file, 'spectrocoin') !== false) {
		$custom_links = array(
			'community-support' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/">' . __('Community support', 'spectrocoin-accepting-bitcoin') . '</a>',
			'rate-us' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/reviews/#new-post">' . __('Rate us', 'spectrocoin-accepting-bitcoin') . '</a>',
		);
		$plugin_meta = array_merge($plugin_meta, $custom_links);
	}
	return $plugin_meta;
}
/**
 * Enqueue admin styles
 */
function spectrocoin_enqueue_admin_styles()
{
	$current_screen = get_current_screen();
	if ($current_screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'spectrocoin') {
		wp_enqueue_style('spectrocoin-payment-settings-css', plugin_dir_url(__FILE__) . 'assets/style/settings.css', array(), '1.0.0');
	}
}