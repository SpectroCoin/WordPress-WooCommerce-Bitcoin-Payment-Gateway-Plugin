<?php

declare(strict_types=1);

/*
Plugin Name: SpectroCoin Payment Extension for WooCommerce
Author:      SpectroCoin
Author URI:  https://spectrocoin.com
Text Domain: spectrocoin-accepting-bitcoin
Plugin URI:  https://github.com/SpectroCoin/WordPress-WooCommerce-Bitcoin-Payment-Gateway-Plugin
Description: SpectroCoin Payments for WooCommerce is a Wordpress plugin that allows to accept cryptocurrencies at WooCommerce-powered online stores.
Version:     2.0.0
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 6.7.1
WC requires at least: 7.4
WC tested up to: 9.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace SpectroCoin;

use SpectroCoin\Includes\SpectroCoinGateway;
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
function spectrocoinInitPlugin(): void
{
    if (isRequirementsMet()) {
        load_plugin_textdomain('spectrocoin-accepting-bitcoin', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (class_exists(SpectroCoinGateway::class)) {
            add_filter('woocommerce_payment_gateways', '\SpectroCoin\spectrocoinGatewayClass');
            add_filter('plugin_action_links', '\SpectroCoin\addCustomLinksLeft', 10, 2);
            add_filter('plugin_row_meta', '\SpectroCoin\addCustomLinksRight', 10, 2);

            add_action('before_woocommerce_init', '\SpectroCoin\declareBlocksCompatibility');
        }
    }
}

/**
 * Checks if the system requirements are met
 * @return bool True if system requirements are met, false if not
 */
function isRequirementsMet(): bool
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
        // Store the error message in a transient
        set_transient('spectrocoin_requirements_not_met', $message, 30);
    
        // Deactivate the plugin to prevent further execution
        deactivatePlugin();
    }
    

    return $requirements_met;
}

/**
 * Handle plugin deactivation
 */
function deactivatePlugin(): void
{
    deactivate_plugins(plugin_basename(__FILE__));
}

/**
 * Gateway class initialization
 */
function spectrocoinGatewayClass(array $methods): array
{
    $methods[] = SpectroCoinGateway::class;
    return $methods;
}

/**
 * Get payment settings url
 */
function getSpectrocoinSettingsUrl(): string
{
    $checkout_url = get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=spectrocoin');
    return esc_url($checkout_url);
}

/**
 * Add custom links to plugin page
 */
function addCustomLinksLeft(array $links, string $file): array
{
    if (strpos($file, 'spectrocoin') !== false) {
        $settings_url = getSpectrocoinSettingsUrl();
        $custom_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'spectrocoin-accepting-bitcoin') . '</a>';
        array_push($links, $custom_link);
    }
    return $links;
}

/**
 * Add custom links to plugin page
 */
function addCustomLinksRight(array $plugin_meta, string $file): array
{
    if (strpos($file, 'spectrocoin') !== false) {
        $custom_links = [
            'community-support' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/">' . esc_html__('Community support', 'spectrocoin-accepting-bitcoin') . '</a>',
            'rate-us' => '<a target = "_blank" href="https://wordpress.org/support/plugin/spectrocoin-accepting-bitcoin/reviews/#new-post">' . esc_html__('Rate us', 'spectrocoin-accepting-bitcoin') . '</a>',
        ];
        $plugin_meta = array_merge($plugin_meta, $custom_links);
    }
    return $plugin_meta;
}

/**
 * Enqueue admin styles
 */
function EnqueueAdminStyles(): void
{
    $current_screen = get_current_screen();
    if ($current_screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'spectrocoin') {
        wp_enqueue_style('spectrocoin-payment-settings-css', esc_url(plugin_dir_url(__FILE__)) . 'assets/style/settings.css', [], '1.0.0');
    }
}

function declareBlocksCompatibility(): void
{
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('woocommerce_blocks_loaded', '\SpectroCoin\registerOrderApprovalPaymentMethodType');

add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function registerOrderApprovalPaymentMethodType(): void
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (PaymentMethodRegistry $payment_method_registry): void {
            $payment_method_registry->register(new SpectroCoinBlocksIntegration);
        }
    );
}

add_action('plugins_loaded', '\SpectroCoin\spectrocoinInitPlugin');
add_action('admin_enqueue_scripts', '\SpectroCoin\EnqueueAdminStyles');

/**
 * Display Admin Notice from Transient
 */
add_action('admin_notices', function () {
    // Retrieve the error notice
    $notice = get_transient('spectrocoin_requirements_not_met');
    
    if ($notice) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . esc_html($notice) . '</p>';
        echo '</div>';
        
        // Clear the transient after displaying the notice
        delete_transient('spectrocoin_requirements_not_met');
    }
});

