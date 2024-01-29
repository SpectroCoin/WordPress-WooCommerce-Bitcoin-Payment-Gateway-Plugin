<?php

//includes/class-wc-gateway-spectrocoin-blocks-integration.php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class SpectroCoin_Gateway_Blocks extends AbstractPaymentMethodType {
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
            plugin_dir_url(__FILE__) . 'block/checkout.js', // Ensure the JS file path is correct
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . '/../assets/js/checkout.js'), // Use file modification time for versioning
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('spectrocoin-blocks-integration', 'spectrocoin-accepting-bitcoin'); // Use your main class text domain
        }

        return ['spectrocoin-blocks-integration']; // Ensure this matches the handle used in wp_register_script
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title, // Use title from your main gateway class
            'description' => $this->gateway->description, // Use description from your main gateway class, ensure it's set and public
        ];
    }
}

?>