<?php

declare(strict_types=1);

namespace SpectroCoin\Includes;

use SpectroCoin\Includes\SpectroCoinGateway;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

final class SpectroCoinBlocksIntegration extends AbstractPaymentMethodType {
    private SpectroCoinGateway $gateway;
    protected $name = 'spectrocoin';

    public function initialize(): void {
        $this->gateway = new SpectroCoinGateway();
    }

    public function is_active(): bool {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array {
        wp_register_script(
            'spectrocoin-blocks-integration',
            plugin_dir_url(__FILE__) . '../assets/js/block-checkout.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/block-checkout.js'),
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('spectrocoin-blocks-integration', 'spectrocoin-accepting-bitcoin');
        }

        return ['spectrocoin-blocks-integration'];
    }

    public function get_payment_method_data(): array {
        $checkout_icon_url = plugins_url('/assets/images/spectrocoin-logo.svg', __DIR__);
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'checkout_icon' => $checkout_icon_url,
        ];
    }
}

?>
