<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for SpectroCoin Gateway.
 */
return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable SpectroCoin', 'woocommerce' ),
		'default' => 'yes'
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'SpectroCoin', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'Pay via SpectroCoin.', 'woocommerce' )
	),
	'merchant_id' => array(
		'title'       => __( 'Merchant Id', 'woocommerce' ),
		'type'        => 'text'
	),
	'application_id' => array(
		'title'       => __( 'Application Id', 'woocommerce' ),
		'type'        => 'text'
	),
	'order_status'  => array(
		'title'       => __('Order status'),
		'desc_tip'    => true,
		'description' => __( 'Order status after payment has been received.', 'woocommerce' ),
		'type'        => 'select',
		'default'     => 'completed',
		'options'     => array(
			'pending'    => __( 'pending', 'woocommerce' ),
			'processing' => __( 'processing', 'woocommerce' ),
			'completed'  => __( 'completed', 'woocommerce' )
		)
	)
	
);
