<?php
/*
Plugin Name: SpectroCoin Bitcoin Payment Gateway
Author:      SpectroCoin
Author URI:  https://spectrocoin.com
Text Domain: spectrocoin-accepting-bitcoin
Plugin URI:  https://github.com/SpectroCoin/WordPress-WooCommerce-Bitcoin-Payment-Gateway-Plugin
Description: Integrates SpectroCoin crypto payments with WooCommerce.
Version:     2.0.0
Requires at least: 6.0.0
Tested up to: 6.6
Requires PHP: 8.0
WC requires at least: 8.0
WC tested up to: 9.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace SpectroCoin;

use SpectroCoin\Includes\SpectroCoinGateway;
use SpectroCoin\Includes\SpectroCoinLogger;
use SpectroCoin\Includes\SpectroCoinBlocksIntegration;
use SpectroCoin\SCMerchantClient\Config;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Initialize plugin
 */
function spectrocoinInitPlugin()
{
    if (spectrocoinRequirementsMet()) {
        load_plugin_textdomain('spectrocoin-accepting-bitcoin', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (class_exists('SpectroCoin\Includes\SpectroCoinGateway')) {
            add_filter('woocommerce_payment_gateways', '\SpectroCoin\spectrocoinGatewayClass');
            add_filter('plugin_action_links', '\SpectroCoin\spectrocoinAddCustomLinksLeft', 10, 2);
            add_filter('plugin_row_meta', '\SpectroCoin\spectrocoinAddCustomLinksRight', 10, 2);

            add_action('before_woocommerce_init', '\SpectroCoin\spectrocoinDeclareCartCheckoutBlocksCompatibility');
        }
    }
}

/**
 * Checks if the system requirements are met
 * @return bool True if system requirements are met, false if not
 */
function spectrocoinRequirementsMet()
{
    $requirements_met = true;
    $message = '';
    if (version_compare(PHP_VERSION, Config::SPECTROCOIN_REQUIRED_PHP_VERSION, '<')) {
        $requirements_met = false;
        $message .= sprintf(
            esc_html__('Spectrocoin plugin requires PHP version %s or greater.', 'spectrocoin-accepting-bitcoin'),
            Config::SPECTROCOIN_REQUIRED_PHP_VERSION
        );
    }

    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    }

    if (version_compare($GLOBALS['wp_version'], Config::SPECTROCOIN_WP_VERSION, '<')) {
        $requirements_met = false;
        $message .= sprintf(
            esc_html__('SpectroCoin plugin requires WordPress version %s or greater.', 'spectrocoin-accepting-bitcoin'),
            Config::SPECTROCOIN_WP_VERSION
        );
    }

    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        $requirements_met = false;
        $message .= esc_html__('SpectroCoin plugin requires WooCommerce to be installed and activated.', 'spectrocoin-accepting-bitcoin');
    }

    if (!$requirements_met) {
        SpectroCoinLogger::displayAdminErrorNotice($message);
        spectrocoinDeactivatePlugin();
    }

    return $requirements_met;
}

/**
 * Handle plugin deactivation
 */
function spectrocoinDeactivatePlugin()
{
    deactivate_plugins(plugin_basename(__FILE__));
}

/**
 * Gateway class initialization
 */
function spectrocoinGatewayClass($methods)
{
    $methods[] = 'SpectroCoin\Includes\WCGatewaySpectroCoin';
    return $methods;
}

/**
 * Get payment settings url
 */
function spectrocoinGetPaymentSettingsUrl()
{
    $checkout_url = get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=spectrocoin');
    return esc_url($checkout_url);
}

/**
 * Add custom links to plugin page
 */
function spectrocoinAddCustomLinksLeft($links, $file)
{
    if (strpos($file, 'spectrocoin') !== false) {
        $settings_url = spectrocoinGetPaymentSettingsUrl();
        $custom_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'spectrocoin-accepting-bitcoin') . '</a>';
        array_push($links, $custom_link);
    }
    return $links;
}

/**
 * Add custom links to plugin page
 */
function spectrocoinAddCustomLinksRight($plugin_meta, $file)
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
function spectrocoinEnqueueAdminStyles()
{
    $current_screen = get_current_screen();
    if ($current_screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'spectrocoin') {
        wp_enqueue_style('spectrocoin-payment-settings-css', esc_url(plugin_dir_url(__FILE__)) . 'assets/style/settings.css', array(), '1.0.0');
    }
}

function spectrocoinDeclareCartCheckoutBlocksCompatibility() {
    if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil'))
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
}

add_action( 'woocommerce_blocks_loaded', '\SpectroCoin\spectrocoinRegisterOrderApprovalPaymentMethodType' );

add_action('before_woocommerce_init', function(){
    if ( class_exists( FeaturesUtil::class ) ) {
        FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true);
    }
});

function spectrocoinRegisterOrderApprovalPaymentMethodType() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new SpectroCoinBlocksIntegration );
        }
    );
}

add_action('plugins_loaded', '\SpectroCoin\spectrocoinInitPlugin');
add_action('admin_enqueue_scripts', '\SpectroCoin\spectrocoinEnqueueAdminStyles');
