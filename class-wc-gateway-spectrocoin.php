<?php

defined('ABSPATH') or exit;

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

if (!class_exists('WC_Payment_Gateway')) {
	return;
}
;

require_once __DIR__ . '/SCMerchantClient/SCMerchantClient.php';
/**
 * WC_Gateway_Spectrocoin Class.
 */
class WC_Gateway_Spectrocoin extends WC_Payment_Gateway
{
	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = true;
	/** @var WC_Logger Logger instance */
	public static $log = false;
	/** @var String pay currency */
	private static $pay_currency = 'BTC';
	/** @var String */
	private static $callback_name = 'spectrocoin_callback';
	/** @var SCMerchantClient */
	// public $form_fields;
	private $scClient;
	protected $merchant_id;
	protected $project_id;
	protected $private_key;
	protected $order_status;
	private $all_order_statuses;
	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		
		$this->id = 'spectrocoin';
		$this->has_fields = false;
		$this->order_button_text = __('Pay with SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->method_title = __('SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->supports = array('products');
		// Define user set variables.
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->merchant_id = $this->get_option('merchant_id');
		$this->project_id = $this->get_option('project_id');
		$this->private_key = $this->get_option('private_key');
		$this->order_status = $this->get_option('order_status');
		$this->all_order_statuses = wc_get_order_statuses();
		// Set up action hooks
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		// Check and initialize if necessary
		$this->initialize_spectrocoin_client();

		// Load the settings.
		$this->form_fields = $this->generate_form_fields();
		$this->init_settings();
	}

	/**
	 * Initializes the SpectroCoin API client if credentials are valid.
	 */
	private function initialize_spectrocoin_client()
	{
		if (!$this->private_key) {
			self::log("Please generate and enter your private key!");
		} elseif (!$this->merchant_id) {
			self::log("Please enter merchant id!");
		} elseif (!$this->project_id) {
			self::log("Please enter application id!");
		} else {
			$this->scClient = new SCMerchantClient(
				'https://spectrocoin.com/api/merchant/1',
				$this->merchant_id,
				$this->project_id,
				$this->private_key
			);
			add_action('woocommerce_api_' . self::$callback_name, array($this, 'callback'));
		}
	}
	/**
	 * Logging method.
	 * @param string $message
	 */
	public static function log($message)
	{
		if (self::$log_enabled) {
			if (empty(self::$log)) {
				self::$log = new WC_Logger();
			}
			self::$log->add('spectrocoin', $message);
		}
	}
	/**
	 * Function to toggle the display of the SpectroCoin payment option
	 */
	public function is_available()
	{
		if($this->checkCurrency()){
			return true;
		}
		else{
			return false;
		}
	}

	public function is_display_logo_enabled()
	{
		$display_logo = $this->get_option('display_logo');
		return $display_logo === 'yes';
	}

	/**
	 * Get gateway icon.
	 * @return string
	 */
	public function get_icon()
	{
		$display_logo = $this->is_display_logo_enabled();

		if ($display_logo) {
			$icon = plugins_url('assets/images/spectrocoin-logo.svg', __FILE__);
			$icon_html = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('SpectroCoin logo', 'spectrocoin-accepting-bitcoin') . '" />';
			return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
		} else {
			// If display_logo is not enabled, return an empty string (no logo will be displayed)
			return '';
		}
	}

	/**
	 * Generate admin settings form.
	 */
	public function admin_options()
	{
		if (!$this->checkCurrency()) {
			echo '<div class="notice notice-error dissmisable"><p>';
			echo '<b>' . get_woocommerce_currency() . ' </b>';
			esc_html_e('currency is not accepted by SpectroCoin. Please change your currency in the', 'spectrocoin-accepting-bitcoin');
			echo ' <a href="' . admin_url('admin.php?page=wc-settings&tab=general') . '">';
			esc_html_e('WooCommerce settings', 'spectrocoin-accepting-bitcoin');
			echo '</a>.';
			echo '</p></div>';
		}
		?>
		<div class="header">
			<div class="header-flex header-flex-1">
				<?php
				printf(
					'<a class="logo-link" href="%1$s" target="_blank"><img class="spectrocoin-logo" src="%2$s"></a>',
					esc_url('https://spectrocoin.com/'),
					esc_url(plugins_url('/assets/images/spectrocoin-logo.svg', __FILE__))
				);
				?>
			</div>
			<!-- Closing div tag moved to the correct place -->
			<div class="header-flex header-flex-2">
				<?php
				printf(
					'<img class="header-image" src="%1$s">',
					esc_url(plugins_url('/assets/images/card_phone_top.svg', __FILE__))
				);
				?>
			</div>
		</div>


		<div class="spectrocoin-plugin-settings">
			<div class="flex-col flex-col-1">
				<table class="form-table">
					<?php $this->generate_settings_html($this->form_fields); ?>
				</table>
			</div>
			<div class="flex-col flex-col-2">
				<div class="white-card">
					<p>
					<h4>
						<?php _e('Introduction', 'spectrocoin-accepting-bitcoin'); ?>
					</h4>
					</p>
					<p>
						<?php
						_e('The Spectroin plugin allows seamless integration of payment gateways into your WordPress website. To get started, you\'ll need to obtain the essential credentials: Merchant ID, Project ID, and Private Key. These credentials are required to enable secure transactions between your website and the payment gateway. Follow the step-by-step tutorial below to acquire these credentials:', 'spectrocoin-accepting-bitcoin');
						?>
					</p>
					<ul>
						<li>
							<span>1. </span>
							<?php printf('<a href="%s" target="_blank">%s</a> %s', esc_url('https://auth.spectrocoin.com/signup'), __('Sign up', 'spectrocoin-accepting-bitcoin'), __('for a Spectroin Account.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>2. </span>
							<?php printf('<a href="%s" target="_blank">%s</a> %s', esc_url('https://auth.spectrocoin.com/login'), __('Log in', 'spectrocoin-accepting-bitcoin'), __('to your Spectroin account.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>3. </span>
							<?php printf('%s <b><a href="%s" target="_blank">%s</a></b> %s', __('On the dashboard, locate the', 'spectrocoin-accepting-bitcoin'), esc_url('https://spectrocoin.com/en/merchants/projects'), __('Business', 'spectrocoin-accepting-bitcoin'), __('tab and click on it.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>4. </span>
							<?php printf('%s <b><a href="%s" target="_blank">%s</a>.</b>', __('Click on', 'spectrocoin-accepting-bitcoin'), esc_url('https://spectrocoin.com/en/merchants/projects/new'), __('New project', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>5. </span>
							<?php _e('Fill in the project details and select desired settings (settings can be changed).', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>6. </span>
							<?php printf('%s <b>%s</b> %s', __('The', 'spectrocoin-accepting-bitcoin'), __('Private Key', 'spectrocoin-accepting-bitcoin'), __('can be obtained by switching on the Public key radio button (Private key won\'t be visible in the settings window, and it will have to be regenerated in settings). Copy or download the newly generated private key.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>7. </span>
							<?php _e('Click Submit.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>8. </span>
							<?php _e('Copy and paste the Merchant ID and Project ID.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>9. </span>
							<?php _e('Generate a test product. Create a test page on your WordPress website with a payment form connected to the Spectroin payment gateway. Perform a trial transaction using the test payment gateway (Test mode can be activated in project settings) to validate the integration\'s functionality. Verify the transaction details on the Spectroin dashboard to ensure it was successfully processed.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<br>
						<li><b>
								<?php _e('Note:', 'spectrocoin-accepting-bitcoin'); ?>
							</b>
							<?php _e('Keep in mind that if you want to use the business services of SpectroCoin, your account has to be verified.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
					</ul>
				</div>
				<?php
				printf(
					'<div class="contact-information">%1$s<br>%2$s <a href="skype:spectrocoin_merchant?chat">%3$s</a> &middot; <a href="mailto:merchant@spectrocoin.com">%4$s</a></div>',
					__('Accept Bitcoin through the SpectroCoin and receive payments in your chosen currency.', 'spectrocoin-accepting-bitcoin'),
					__('Still have questions? Contact us via', 'spectrocoin-accepting-bitcoin'),
					__('skype: spectrocoin_merchant', 'spectrocoin-accepting-bitcoin'),
					__('email: merchant@spectrocoin.com', 'spectrocoin-accepting-bitcoin')
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function generate_form_fields()
	{
		return array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'spectrocoin-accepting-bitcoin'),
				'type' => 'checkbox',
				'label' => __('Enable SpectroCoin', 'spectrocoin-accepting-bitcoin'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Title', 'spectrocoin-accepting-bitcoin'),
				'type' => 'Text',
				'description' => __('This controls the title which the user sees during checkout.', 'spectrocoin-accepting-bitcoin'),
				'default' => __('Bitcoin', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __('Description', 'spectrocoin-accepting-bitcoin'),
				'type' => 'text',
				'desc_tip' => true,
				'description' => __('This controls the description which the user sees during checkout.', 'spectrocoin-accepting-bitcoin'),
				'default' => __('Pay via Bitcoin.', 'spectrocoin-accepting-bitcoin')
			),
			'merchant_id' => array(
				'title' => __('Merchant Id', 'spectrocoin-accepting-bitcoin'),
				'type' => 'text'
			),
			'project_id' => array(
				'title' => __('Project Id', 'spectrocoin-accepting-bitcoin'),
				'type' => 'text'
			),
			'private_key' => array(
				'title' => __('Private key', 'spectrocoin-accepting-bitcoin'),
				'type' => 'textarea',
				'description' => __('private key.', 'spectrocoin-accepting-bitcoin'),
				'default' => __('Please add your private key with (-----BEGIN PRIVATE KEY-----  -----END PRIVATE KEY-----) ', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
			),
			'order_status' => array(
				'title' => __('Order status', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
				'description' => __('Order status after payment has been received.', 'spectrocoin-accepting-bitcoin'),
				'type' => 'select',
				'default' => 'completed',
				'options' => $this->all_order_statuses
			),
			'display_logo' => array(
				'title' => __('Display logo', 'spectrocoin-accepting-bitcoin'),
				'description' => __('This controls the display of SpectroCoin logo in the checkout page', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
				'type' => 'checkbox',
				'label' => __('Enable', 'spectrocoin-accepting-bitcoin'),
				'default' => 'yes'
			),
		);
	}
	/**
	 * Output for the order received page.
	 */
	public function thankyou_page()
	{
		if ($this->instructions) {
			echo wpautop(wptexturize($this->instructions));
		}
	}

	/**
	 * Process the payment and return the result.
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment($order_id)
	{
		global $woocommerce;
		$order = wc_get_order($order_id);
		$total = $order->get_total();
		$currency = $order->get_order_currency();
		$request = $this->new_request($order, $total, $currency);
		$response = $this->scClient->createOrder($request);
		if ($response instanceof ApiError) {
			self::log("Failed to create SpectroCoin payment for order {$order_id}. Response message: {$response->getMessage()}. Response code: {$response->getCode()}");
			return array(
				'result' => 'failure',
				'messages' => $response->getMessage()
			);
		}
		$order->update_status('on-hold', __('Waiting for SpectroCoin payment', 'spectrocoin-accepting-bitcoin'));
		$order->reduce_order_stock();
		$woocommerce->cart->empty_cart();
		return array(
			'result' => 'success',
			'redirect' => $response->getRedirectUrl()
		);

	}

	/**
	 * Used to process callbacks from SpectroCoin
	 */
	public function callback()
	{
		if ($this->enabled != 'yes') {
			return;
		}
		$callback = $this->scClient->parseCreateOrderCallback($_POST);
		if ($callback) {
			$valid = $this->scClient->validateCreateOrderCallback($callback);
			if ($valid == true) {
				$order_id = $this->parse_order_id($callback->getOrderId());
				$status = $callback->getStatus();
				$order = wc_get_order($order_id);
				if ($order) {
					switch ($status) {
						case (1): // new
						case (2): // pending
							$order->update_status('pending');
							break;
						case (3): // paid
							$order->update_status($this->order_status);
							break;
						case (4): // failed
						case (5): // expired
							$order->update_status('failed');
							break;
						case (6): // test
							// $order->update_status( $this->order_status );
							break;
					}
					echo esc_html__('*ok*', 'spectrocoin-accepting-bitcoin');
					exit;
				} else {
					self::log("Order '{$order_id}' not found!");
					echo esc_html__('order not found', 'spectrocoin-accepting-bitcoin');
					exit;
				}
			} else {
				self::log("Sent callback is invalid");
				echo esc_html__('invalid callback data', 'spectrocoin-accepting-bitcoin');
				exit;
			}
		} else {
			self::log("Sent callback is invalid");
			echo esc_html__('invalid callback format', 'spectrocoin-accepting-bitcoin');
			exit;
		}
	}

	/**
	 *	Create new request for SpectroCoin API
	 *	@param WC_Order $order
	 *	@param float $total
	 *	@param string $receive_currency
	 */
	private function new_request($order, $total, $receive_currency)
	{
		$callback = get_site_url(null, '?wc-api=' . self::$callback_name);
		$successCallback = $this->get_return_url($order);
		$failureCallback = $this->get_return_url($order);
		return new CreateOrderRequest(
			$order->id . '-' . $this->random_str(5),
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

	/**
	 * Parse order id from SpectroCoin callback
	 * @param string $order_id
	 * @return string
	 */
	private function parse_order_id($order_id)
	{
		return explode('-', $order_id)[0];
	}

	private function checkCurrency()
  	{	
		$jsonFile = file_get_contents(plugin_dir_path( __FILE__ ) . 'SCMerchantClient/data/acceptedCurrencies.JSON'); 
		$acceptedCurrencies = json_decode($jsonFile, true);
		$currentCurrencyIsoCode = get_woocommerce_currency();
		if (in_array($currentCurrencyIsoCode, $acceptedCurrencies)) {
		    return true;
		} 
		else {
		    return false;
		}

	}
	/**
	 * Generate random string
	 * @param int $length
	 * @return string
	 */
	private function random_str($length)
	{
		return substr(md5(rand(1, pow(2, 16))), 0, $length);
	}
}