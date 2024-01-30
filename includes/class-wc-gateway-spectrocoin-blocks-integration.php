<?php

//includes/class-wc-gateway-spectrocoin-blocks-integration.php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_Blocks_SpectroCoin extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'spectrocoin';

    public function initialize() {
        $this->gateway = new WC_Gateway_Spectrocoin();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'spectrocoin-blocks-integration',
            plugin_dir_url(__FILE__) . '../assets/js/checkout.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/checkout.js'),
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('spectrocoin-blocks-integration', 'spectrocoin-accepting-bitcoin');
        }

        return ['spectrocoin-blocks-integration'];
    }

    public function get_payment_method_data() {
        $checkout_icon_url = plugins_url('/assets/images/spectrocoin-logo.svg', __DIR__);
        error_log($checkout_icon_url);
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'checkout_icon' => $checkout_icon_url,
        ];
    }
}

?>