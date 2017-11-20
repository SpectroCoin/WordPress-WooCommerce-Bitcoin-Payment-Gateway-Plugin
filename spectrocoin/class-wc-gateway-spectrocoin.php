<?php

defined( 'ABSPATH' ) or exit;

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

add_action( 'plugins_loaded', 'spectrocoin_init');

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	};

require_once __DIR__ . '/SCMerchantClient/SCMerchantClient.php';
/**
 * WC_Gateway_Spectrocoin Class.
 */
class WC_Gateway_Spectrocoin extends WC_Payment_Gateway {
	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = true;
	/** @var WC_Logger Logger instance */
	public static $log = false;
	/** @var String pay currency */
	private static $pay_currency = 'BTC';
	/** @var String */
	private static $callback_name = 'spectrocoin_callback';
	/** @var SCMerchantClient */
	private $scClient;
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'spectrocoin';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Pay with SpectroCoin', 'woocommerce' );
		$this->method_title       = __( 'SpectroCoin', 'woocommerce' );
		$this->supports           = array( 'products' );
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		// Define user set variables.
		$this->title			= $this->get_option( 'title' );
		$this->description		= $this->get_option( 'description' );
		$this->merchant_id 		= $this->get_option( 'merchant_id' );
		$this->project_id 		= $this->get_option( 'project_id' );
		$this->private_key		= $this->get_option( 'private_key' );
		$this->order_status     = $this->get_option( 'order_status' );
		
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		
		if ( !$this->private_key ) {
			self::log( "Please generate and enter your private_key!" );

		} else if ( !$this->merchant_id ) {
			self::log( "Please enter merchant id!" );
		} else if ( !$this->project_id ) {
			self::log( "Please enter application id!" );
		} else {
			$this->scClient = NEW SCMerchantClient(
				'https://spectrocoin.com/api/merchant/1',
				$this->merchant_id,
				$this->project_id,
				$this->private_key
			);
			add_action( 'woocommerce_api_' . self::$callback_name, array( &$this, 'callback' ) );
		}
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
	}
	/**
	 * Logging method.
	 * @param string $message
	 */
	public static function log( $message ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'spectrocoin', $message );
		}
	}
	/**
	 * Get gateway icon.
	 * @return string
	 */
	public function get_icon() {
		$icon      = plugins_url( 'assets/images/spectrocoin.png', __FILE__ );
		$icon_html = '<img src="' . esc_attr( $icon ) . '" alt="' . esc_attr__( 'SpectroCoin logo', 'woocommerce' ) . '" />';
		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}
	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	 
	     public function admin_options()
    {
      ?>
      <p><?php _e('<b><h3>SpectroCoin</h3></b><br>Accept Bitcoin through the SpectroCoin and receive payments in your chosen currency.<br>
       Still have questions? Contact us via <a href="skype:spectrocoin_merchant?chat">skype: spectrocoin_merchant</a> &middot; <a href="mailto:merchant@spectrocoin.com">email: merchant@spectrocoin.com</a><br>', 'woothemes'); ?></p>
      <table class="form-table">
        <?php $this->generate_settings_html(); ?>
      </table>
      <?php
    }
	
			public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'woocommerce'),
					'type' => 'checkbox',
					'label' => __('Enable SpectroCoin', 'woocommerce'),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __('Title', 'woocommerce'),
					'type' => 'Text',
					'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
					'default' => __('Bitcoin', 'woocommerce'),
					'desc_tip' => true,
				),
				'description' => array(
					'title' => __('Description', 'woocommerce'),
					'type' => 'text',
					'desc_tip' => true,
					'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
					'default' => __('Pay via Bitcoin.', 'woocommerce')
				),
				'merchant_id' => array(
					'title' => __('Merchant Id', 'woocommerce'),
					'type' => 'text'
				),
				'project_id' => array(
					'title' => __('Project Id', 'woocommerce'),
					'type' => 'text'
				),
				'private_key' => array(
					'title' => __('Private key', 'woocommerce'),
					'type' => 'textarea',
					'description' => __('private key.', 'woocommerce'),
					'default' => __('Please add your private key with (-----BEGIN PRIVATE KEY-----  -----END PRIVATE KEY-----) ', 'woocommerce'),
					'desc_tip' => true,
				),
				'order_status' => array(
					'title' => __('Order status'),
					'desc_tip' => true,
					'description' => __('Order status after payment has been received.', 'woocommerce'),
					'type' => 'select',
					'default' => 'completed',
					'options' => array(
						'pending' => __('pending', 'woocommerce'),
						'processing' => __('processing', 'woocommerce'),
						'completed' => __('completed', 'woocommerce'),
					),
				),

			);
		}

		public function thankyou_page() {
			if ($this->instructions) {
				echo wpautop(wptexturize($this->instructions));
			}
		}
		
	/**
	 * Process the payment and return the result.
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = wc_get_order( $order_id );
		$total = $order->get_total();
		$currency = $order->get_order_currency();
		$request = $this->new_request( $order, $total, $currency);
		$response = $this->scClient->createOrder( $request );
		if ($response instanceof ApiError) {
			self::log("Failed to create SpectroCoin payment for order {$order_id}. Response message {$response->getMessage()}. Response code: {$response->getCode()}");
			return array(
				'result' => 'failure',
				'messages' => $response->getMessage()
			);
		}
		$order->update_status( 'on-hold', __( 'Waiting for SpectroCoin payment', 'woocommerce' ) );
		$order->reduce_order_stock();
		$woocommerce->cart->empty_cart();
		return array(
			'result'   => 'success',
			'redirect' => $response->getRedirectUrl()
		);

	}
	/**
	 * Used to process callbacks from SpectroCoin
	 */
	public function callback() {
		if ( $this->enabled != 'yes' ) {
			return;
		}
		$callback = $this->scClient->parseCreateOrderCallback( $_POST );
		if ( $callback ) {
			$valid = $this->scClient->validateCreateOrderCallback( $callback );
			if ($valid == true) {
				$order_id = $this->parse_order_id($callback->getOrderId());
				$status = $callback->getStatus();
				$order = wc_get_order( $order_id );
				if ($order) {
					switch ($status) {
						case (1): // new
						case (2): // pending
							$order->update_status( 'pending' );
							break;
						case (3): // paid
							$order->update_status( $this->order_status );
							break;
						case (4): // failed
						case (5): // expired
							$order->update_status( 'failed' );
							break;
						case (6): // test
							// $order->update_status( $this->order_status );
							break;
					}
					echo "*ok*";
					exit;
				} else self::log( "Order '{$order_id}' not found!" );
			} else self::log( "Sent callback is invalid" );
		} else self::log( "Sent callback is invalid" );
	}
	private function new_request( $order, $total, $receive_currency ) {
		$callback = get_site_url( null, '?wc-api=' . self::$callback_name );
		$successCallback = $this->get_return_url( $order );
		$failureCallback = $this->get_return_url( $order );
		return new CreateOrderRequest(
			$order->id . '-' . $this->random_str( 5 ),
			self::$pay_currency,
			null,
			$receive_currency,
			$total,
			"Order #{$order->id}",
			"en",
			$callback,
			$successCallback,
			$failureCallback
		);
	}

	private function parse_order_id($order_id) {
		return explode('-', $order_id)[0];
	}
	private function random_str($length) {
		return substr(md5(rand(1, pow(2, 16))), 0, $length);
	}
}
