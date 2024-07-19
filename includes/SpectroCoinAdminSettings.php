<?php

namespace SpectroCoin\Includes;

use WC_Settings_API;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class SpectroCoinAdminSettings extends WC_Settings_API
{
    

    public function __construct()
    {
        $this->id = 'spectrocoin';
        $this->method_title = esc_html__('SpectroCoin', 'spectrocoin-accepting-bitcoin');
        $this->method_description = esc_html__('Take payments via SpectroCoin. Accept more than 30 cryptocurrencies, such as ETH, BTC, and USDT.', 'spectrocoin');
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => esc_html__('Enable/Disable', 'spectrocoin-accepting-bitcoin'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable SpectroCoin', 'spectrocoin-accepting-bitcoin'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => esc_html__('Title', 'spectrocoin-accepting-bitcoin'),
                'type' => 'text',
                'description' => esc_html__('This controls the title which the user sees during checkout. Default is "Pay with SpectroCoin".', 'spectrocoin-accepting-bitcoin'),
                'default' => esc_html__('Pay with SpectroCoin', 'spectrocoin-accepting-bitcoin'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => esc_html__('Description', 'spectrocoin-accepting-bitcoin'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => esc_html__('This controls the description which the user sees during checkout.', 'spectrocoin-accepting-bitcoin'),
            ),
            'project_id' => array(
                'title' => esc_html__('Project ID', 'spectrocoin-accepting-bitcoin'),
                'type' => 'text',
            ),
            'client_id' => array(
                'title' => esc_html__('Client ID', 'spectrocoin-accepting-bitcoin'),
                'type' => 'text'
            ),
            'client_secret' => array(
                'title' => esc_html__('Client Secret', 'spectrocoin-accepting-bitcoin'),
                'type' => 'text',
            ),
            'order_status' => array(
                'title' => esc_html__('Order status', 'spectrocoin-accepting-bitcoin'),
                'desc_tip' => true,
                'description' => esc_html__('Order status after payment has been received. Custom order statuses will appear in the list.', 'spectrocoin-accepting-bitcoin'),
                'type' => 'select',
                'default' => 'completed',
                'options' => wc_get_order_statuses()
            ),
            'display_logo' => array(
                'title' => esc_html__('Display logo', 'spectrocoin-accepting-bitcoin'),
                'description' => esc_html__('This controls the display of SpectroCoin logo in the checkout page', 'spectrocoin-accepting-bitcoin'),
                'desc_tip' => true,
                'type' => 'checkbox',
                'label' => esc_html__('Enable', 'spectrocoin-accepting-bitcoin'),
                'default' => 'yes'
            ),
            'test_mode' => array(
                'title' => esc_html__('Hide from checkout', 'spectrocoin-accepting-bitcoin'),
                'description' => esc_html__('When enabled SpectroCoin payment option will be visible only for admin user.', 'spectrocoin-accepting-bitcoin'),
                'desc_tip' => true,
                'type' => 'checkbox',
                'label' => esc_html__('Enable', 'spectrocoin-accepting-bitcoin'),
                'default' => 'no'
            ),
        );
    }

    public function process_admin_options()
    {
        $saved = parent::process_admin_options();
        return $saved;
    }
}