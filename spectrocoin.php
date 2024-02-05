<?php
/*
Plugin Name: SpectroCoin Bitcoin Payment Gateway
Author:      SpectroCoin
Author URI:  https://spectrocoin.com
Text Domain: spectrocoin-accepting-bitcoin
Plugin URI:  https://github.com/SpectroCoin/WordPress-WooCommerce-Bitcoin-Payment-Gateway-Plugin
Description: Integrates SpectroCoin crypto payments with WooCommerce.
Version:     1.5.0
Requires at least: 6.2
Requires PHP: 7.4
WC requires at least: 7.4
WC tested up to: 8.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
	die('Access denied.');
}

define('SPECTROCOIN_REQUIRED_PHP_VERSION', '7.4');
define('SPECTROCOIN_WP_VERSION', '6.2');

$plugin_folder = explode('/', plugin_basename(__FILE__))[0];
define('SPECTROCOIN_PLUGIN_FOLDER_NAME', $plugin_folder );


/**
 * Initialize plugin
 */
function spectrocoin_init_plugin()
{
	if (spectrocoin_requirements_met()) {
		require_once(__DIR__ . '/includes/class-wc-gateway-spectrocoin.php');
		load_plugin_textdomain('spectrocoin-accepting-bitcoin', false, dirname(plugin_basename(__FILE__)) . '/languages');

		if (class_exists('WC_Gateway_Spectrocoin')) {
			add_filter('woocommerce_payment_gateways', 'spectrocoin_gateway_class');
			add_filter('plugin_action_links', 'spectrocoin_add_custom_links_left', 10, 2);
			add_filter('plugin_row_meta', 'spectrocoin_add_custom_links_right', 10, 2);

			add_action('before_woocommerce_init', 'spectrocoin_declare_cart_checkout_blocks_compatibility');
		}
	}
}

/**
 * Checks if the system requirements are met
 * @return bool True if system requirements are met, false if not
 */
function spectrocoin_requirements_met()
{
	$requirements_met = true;
	$message = '';
	if (version_compare(PHP_VERSION, SPECTROCOIN_REQUIRED_PHP_VERSION, '<')) {
		$requirements_met = false;
		$message .= sprintf(
			esc_html__('Spectrocoin plugin requires PHP version %s or greater.', 'spectrocoin-accepting-bitcoin'),
			SPECTROCOIN_REQUIRED_PHP_VERSION
		);
	}

	if (!function_exists('is_plugin_active')) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if (version_compare($GLOBALS['wp_version'], SPECTROCOIN_WP_VERSION, '<')) {
		$requirements_met = false;
		$message .= sprintf(
			esc_html__('SpectroCoin plugin requires WordPress version %s or greater.', 'spectrocoin-accepting-bitcoin'),
			SPECTROCOIN_WP_VERSION
		);
	}

	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		$requirements_met = false;
		$message .= esc_html__('SpectroCoin plugin requires WooCommerce to be installed and activated.', 'spectrocoin-accepting-bitcoin');
	}

	if (!$requirements_met) {
		spectrocoin_admin_error_notice($message);
		spectrocoin_deactivate_plugin();
	}

	return $requirements_met;
}

/**
 * Display error message in admin settings
 * @param string $message Error message
 * @param bool $allow_hyperlink Allow hyperlink in error message
 */
function spectrocoin_admin_error_notice($message, $allow_hyperlink = false) {
    static $displayed_messages = array();

    $allowed_html = $allow_hyperlink ? array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'target' => array(),
        ),
    ) : array();

    $processed_message = wp_kses($message, $allowed_html);

    $current_page = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

    if (!empty($processed_message) && !in_array($processed_message, $displayed_messages) && $current_page == "spectrocoin") {
        array_push($displayed_messages, $processed_message);
        ?>
        <div class="notice notice-error">
            <p><?php echo __("SpectroCoin Error: ", 'spectrocoin-accepting-bitcoin') . $processed_message; // Using $processed_message directly ?></p>
        </div>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var notices = document.querySelectorAll('.notice-error');
                notices.forEach(function(notice) {
                    notice.style.display = 'block';
                });
            });
        </script>
        <?php
    }
}


/**
 * Handle plugin deactivation
 */
function spectrocoin_deactivate_plugin()
{
	deactivate_plugins(plugin_basename(__FILE__));
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
function spectrocoin_get_payment_settings_url()
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
		$settings_url = spectrocoin_get_payment_settings_url();
		$custom_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'spectrocoin-accepting-bitcoin') . '</a>';
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
			'community-support' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/">' . esc_html__('Community support', 'spectrocoin-accepting-bitcoin') . '</a>',
			'rate-us' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/reviews/#new-post">' . esc_html__('Rate us', 'spectrocoin-accepting-bitcoin') . '</a>',
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
		wp_enqueue_style('spectrocoin-payment-settings-css', esc_url(plugin_dir_url(__FILE__)) . 'assets/style/settings.css', array(), '1.0.0');
	}
}

function spectrocoin_declare_cart_checkout_blocks_compatibility() {
	if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil'))
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
}

add_action( 'woocommerce_blocks_loaded', 'spectrocoin_register_order_approval_payment_method_type' );

add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/**
 * Custom function to register a payment method type
 */
function spectrocoin_register_order_approval_payment_method_type() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . '/includes/class-wc-gateway-spectrocoin-blocks-integration.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register( new WC_Gateway_Blocks_SpectroCoin );
        }
    );
}

add_action('plugins_loaded', 'spectrocoin_init_plugin');
add_action('admin_enqueue_scripts', 'spectrocoin_enqueue_admin_styles');