<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

if (!class_exists('WC_Payment_Gateway')) {
	return;
}

require_once __DIR__ . '/../SCMerchantClient/SCMerchantClient.php';
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
    private $scClient;
    public $id;
    public $has_fields;
    public $order_button_text;
    public $method_title;
    public $method_description;
    public $supports;
    public $title;
    public $description;
    public $merchant_id;
    public $project_id;
    public $private_key;
    public $form_fields = array();
    public $order_status;
	public $display_logo;
    private $all_order_statuses;
	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		
		$this->id = 'spectrocoin';
		$this->has_fields = false;
		$this->order_button_text = esc_html__('Pay with SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->method_title = esc_html__('SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->method_description = esc_html__('Take payments via SpectroCoin. Accept more than 30 cryptocurrencies, such as ETH, BTC, and USDT.', 'spectrocoin');
		$this->supports = array('products');

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->merchant_id = $this->get_option('merchant_id');
		$this->project_id = $this->get_option('project_id');
		$this->private_key = $this->get_option('private_key');
		$this->order_status = $this->get_option('order_status');
		$this->all_order_statuses = wc_get_order_statuses();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		
        $this->spectrocoin_initialize_client();
		$this->init_settings();
		$this->form_fields = $this->spectrocoin_generate_form_fields();
	}

	/**
	 * Function which is called when SpectroCoin settings is saved.
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();
		if ($saved) {
			$this->spectrocoin_reload_settings();
			$this->spectrocoin_validate_settings(true);
		}
	
		return $saved;
	}
	

	/**
	 * Initializes the SpectroCoin API client if credentials are valid.
	 */
    private function spectrocoin_initialize_client() {
        $this->scClient = new SCMerchantClient(
            'https://spectrocoin.com/api/merchant/1',
            $this->merchant_id,
            $this->project_id,
            $this->private_key
        );
        add_action('woocommerce_api_' . self::$callback_name, array($this, 'callback'));
		
	}
	/**
	 * Reloads settings from database.
	 */
	private function spectrocoin_reload_settings() {
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->merchant_id = $this->get_option('merchant_id');
		$this->project_id = $this->get_option('project_id');
		$this->private_key = $this->get_option('private_key');
		$this->order_status = $this->get_option('order_status');
	}

	/**
	 * Check validity of credentials in settings.
	 * @param bool $display_notice If true, then error notices will be displayed Default = true.
	 * @return bool
	 */
	public function spectrocoin_validate_settings($display_notice = true){
		$is_valid = true;

		if (empty($this->merchant_id)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Merchant ID is empty');
				error_log('SpectroCoin Error: Merchant ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->project_id)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Project ID is empty');
				error_log('SpectroCoin Error: Project ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->private_key)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Private Key is empty');
				error_log('SpectroCoin Error: Private Key is empty');
			}
			$is_valid = false;
		} elseif (!SpectroCoin_ValidationUtil::spectrocoin_validate_private_key($this->private_key)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Invalid Private Key');
				error_log('SpectroCoin Error: Invalid Private Key');
			}
			$is_valid = false;
		}

		$this->title = sanitize_text_field($this->get_option('title'));
		if (empty($this->title)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Title cannot be empty');
				error_log('SpectroCoin Error: Title cannot be empty');
			}
			$is_valid = false;
		}

		$this->description = sanitize_textarea_field($this->get_option('description'));

		$this->enabled = sanitize_text_field($this->get_option('enabled'));
		if (!in_array($this->enabled, ['yes', 'no'])) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Invalid value for enabled status');
				error_log('SpectroCoin Error: Invalid value for enabled status');
			}
			$is_valid = false;
		}

		$this->order_status = sanitize_text_field($this->get_option('order_status'));
		if (!array_key_exists($this->order_status, $this->all_order_statuses)) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Invalid order status');
				error_log('SpectroCoin Error: Invalid order status');
			}
			$is_valid = false;
		}

		$this->display_logo = sanitize_text_field($this->get_option('display_logo'));
		if (!in_array($this->display_logo, ['yes', 'no'])) {
			if ($display_notice) {
				spectrocoin_admin_error_notice('Invalid value for display logo status');
				error_log('SpectroCoin Error: Invalid value for display logo status');
			}
			$is_valid = false;
		}
		return $is_valid;
	}

	/**
	 * Logging method. Logs messages to WooCommerce log if logging is enabled.
	 * @param string $message
	 */
	public static function spectrocoin_log($message)
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
	 * The payment method will be displayed when the following conditions are met:
	 * 1. The SpectroCoin plugin is enabled
	 * 2. The SpectroCoin plugin is configured with valid credentials
	 * 3. The currency is accepted by SpectroCoin
	 * @return bool
	 */
	public function is_available() {
		if (!function_exists('is_plugin_active')) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		if (is_plugin_active(SPECTROCOIN_PLUGIN_FOLDER_NAME . '/spectrocoin.php') && $this->enabled === 'yes') {
			if ($this->spectrocoin_check_currency()) {
				if($this->spectrocoin_validate_settings(false)){
					if ($this->scClient === null) {
						$this->spectrocoin_initialize_client();
					}
					if ($this->scClient !== null) {
						return true;
					}
				}
			}
			else{
				$currency = esc_html(get_woocommerce_currency());
				$message = $currency . ' currency is not accepted by SpectroCoin. Please change your currency in the ';
				error_log('SpectroCoin Error: ' . $message . "WooCommerce settings");

				$settings_link = esc_url(admin_url('admin.php?page=wc-settings&tab=general'));
				$message .= ' <a href="' . $settings_link . '">WooCommerce settings</a>.';
				
				spectrocoin_admin_error_notice($message, true);
			}
		}
	
		return false;
	}

	/**
	 * Check if display logo is enabled.
	 * @return bool
	 */
	public function spectrocoin_is_display_logo_enabled()
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
		$display_logo = $this->spectrocoin_is_display_logo_enabled();

		if ($display_logo) {
			$icon = plugins_url('/../assets/images/spectrocoin-logo.svg', __FILE__);
			$icon_html = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('SpectroCoin logo', 'spectrocoin-accepting-bitcoin') . '" />';
			return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
		} else {
			return '';
		}
	}

	/**
	 * Generate admin settings form.
	 */
	public function admin_options()
	{
		?>
		<div class="header">
			<div class="header-flex header-flex-1">
				<?php
				printf(
					'<a class="logo-link" href="%1$s" target="_blank"><img class="spectrocoin-logo" src="%2$s" alt="%3$s"></a>',
					esc_url('https://spectrocoin.com/'),
					esc_url(plugins_url('/../assets/images/spectrocoin-logo.svg', __FILE__)),
					esc_attr__('SpectroCoin logo', 'spectrocoin-accepting-bitcoin')
				);
				?>
			</div>
			<div class="header-flex header-flex-2">
				<?php
				printf(
					'<img class="header-image" src="%1$s" alt="%2$s">',
					esc_url(plugins_url('/../assets/images/card_phone_top.svg', __FILE__)),
					esc_attr__('Header Image', 'spectrocoin-accepting-bitcoin')
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
						<?php esc_html_e('Introduction', 'spectrocoin-accepting-bitcoin'); ?>
					</h4>
					</p>
					<p>
						<?php
						esc_html_e('The Spectroin plugin allows seamless integration of payment gateways into your WordPress website. To get started, you\'ll need to obtain the essential credentials: Merchant ID, Project ID, and Private Key. These credentials are required to enable secure transactions between your website and the payment gateway. Follow the step-by-step tutorial below to acquire these credentials:', 'spectrocoin-accepting-bitcoin');
						?>
					</p>
					<ul>
						<li>
							<span>1. </span>
							<?php printf('<a href="%s" target="_blank">%s</a> %s', esc_url('https://auth.spectrocoin.com/signup'), esc_html__('Sign up', 'spectrocoin-accepting-bitcoin'), esc_html__('for a Spectroin Account.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>2. </span>
							<?php printf('<a href="%s" target="_blank">%s</a> %s', esc_url('https://auth.spectrocoin.com/login'), esc_html__('Log in', 'spectrocoin-accepting-bitcoin'), esc_html__('to your Spectroin account.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>3. </span>
							<?php printf('%s <b><a href="%s" target="_blank">%s</a></b> %s', esc_html__('On the dashboard, locate the', 'spectrocoin-accepting-bitcoin'), esc_url('https://spectrocoin.com/en/merchants/projects'), esc_html__('Business', 'spectrocoin-accepting-bitcoin'), esc_html__('tab and click on it.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>4. </span>
							<?php printf('%s <b><a href="%s" target="_blank">%s</a>.</b>', esc_html__('Click on', 'spectrocoin-accepting-bitcoin'), esc_url('https://spectrocoin.com/en/merchants/projects/new'), esc_html__('New project', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>5. </span>
							<?php esc_html_e('Fill in the project details and select desired settings (settings can be changed).', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>6. </span>
							<?php printf('%s <b>%s</b> %s', esc_html__('The', 'spectrocoin-accepting-bitcoin'), esc_html__('Private Key', 'spectrocoin-accepting-bitcoin'), esc_html__('can be obtained by switching on the Public key radio button (Private key won\'t be visible in the settings window, and it will have to be regenerated in settings). Copy or download the newly generated private key.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>7. </span>
							<?php esc_html_e('Click Submit.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>8. </span>
							<?php esc_html_e('Copy and paste the Merchant ID and Project ID.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>9. </span>
							<?php esc_html_e('Generate a test product. Create a test page on your WordPress website with a payment form connected to the Spectroin payment gateway. Perform a trial transaction using the test payment gateway (Test mode can be activated in project settings) to validate the integration\'s functionality. Verify the transaction details on the Spectroin dashboard to ensure it was successfully processed.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<br>
						<li><b>
								<?php esc_html_e('Note:', 'spectrocoin-accepting-bitcoin'); ?>
							</b>
							<?php esc_html_e('Keep in mind that if you want to use the business services of SpectroCoin, your account has to be verified.', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
					</ul>
				</div>
				<?php
				printf(
					'<div class="contact-information">%1$s<br>%2$s <a href="skype:spectrocoin_merchant?chat">%3$s</a> &middot; <a href="mailto:%4$s">%5$s</a></div>',
					esc_html__('Accept Bitcoin through the SpectroCoin and receive payments in your chosen currency.', 'spectrocoin-accepting-bitcoin'),
					esc_html__('Still have questions? Contact us via', 'spectrocoin-accepting-bitcoin'),
					esc_html__('skype: spectrocoin_merchant', 'spectrocoin-accepting-bitcoin'),
					esc_html__('email: ', 'spectrocoin-accepting-bitcoin'),
					esc_html__(sanitize_email("merchant@spectrocoin.com"), 'spectrocoin-accepting-bitcoin')
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function spectrocoin_generate_form_fields()
	{
		return array(
			'enabled' => array(
				'title' => esc_html__('Enable/Disable', 'spectrocoin-accepting-bitcoin'),
				'type' => 'checkbox',
				'label' => esc_html__('Enable SpectroCoin', 'spectrocoin-accepting-bitcoin'),
				'default' => 'no'
			),
			'title' => array(
				'title' => esc_html__('Title', 'spectrocoin-accepting-bitcoin'),
				'type' => 'Text',
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
			'merchant_id' => array(
				'title' => esc_html__('Merchant Id', 'spectrocoin-accepting-bitcoin'),
				'type' => 'text',
			),
			'project_id' => array(
				'title' => esc_html__('Project Id', 'spectrocoin-accepting-bitcoin'),
				'type' => 'text'
			),
			'private_key' => array(
				'title' => esc_html__('Private key', 'spectrocoin-accepting-bitcoin'),
				'type' => 'textarea',
				'description' => esc_html__('private key.', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
			),
			'order_status' => array(
				'title' => esc_html__('Order status', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
				'description' => esc_html__('Order status after payment has been received. Custom order statuses will appear in the list.', 'spectrocoin-accepting-bitcoin'),
				'type' => 'select',
				'default' => 'Completed',
				'options' => $this->all_order_statuses
			),
			'display_logo' => array(
				'title' => esc_html__('Display logo', 'spectrocoin-accepting-bitcoin'),
				'description' => esc_html__('This controls the display of SpectroCoin logo in the checkout page', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
				'type' => 'checkbox',
				'label' => esc_html__('Enable', 'spectrocoin-accepting-bitcoin'),
				'default' => 'yes'
			),
		);
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
		$currency = $order->get_currency();
		$request = $this->new_request($order, $total, $currency);
		$response = $this->scClient->spectrocoin_create_order($request);
		if ($response instanceof SpectroCoin_ApiError) {
			self::spectrocoin_log("SpectroCoin error: Failed to create payment for order {$order_id}. Response message: {$response->getMessage()}. Response code: {$response->getCode()}");
			error_log("SpectroCoin error: Failed to create payment for order {$order_id}. Response message: {$response->getMessage()}. Response code: {$response->getCode()}");
			return array(
				'result' => 'failure',
				'messages' => $response->getMessage()
			);
		}
		$order->update_status('on-hold', __('Waiting for SpectroCoin payment', 'spectrocoin-accepting-bitcoin'));
		wc_reduce_stock_levels($order_id);
		$woocommerce->cart->empty_cart();
		return array(
			'result' => 'success',
			'redirect' => $response->getRedirectUrl()
		);
	}

	/**
	 * Used to process callbacks from SpectroCoin
	 * Callback is parsed to spectrocoin_process_callback method, which handles sanitization and validation.
	 * If callback is valid, then order status is updated.
	 * If callback is invalid, then error message is logged and order fails.
	 */
	public function callback()
	{
		$expected_keys = ['userId', 'merchantApiId', 'merchantId', 'apiId', 'orderId', 'payCurrency', 'payAmount', 'receiveCurrency', 'receiveAmount', 'receivedAmount', 'description', 'orderRequestId', 'status', 'sign'];

		$post_data = [];

		foreach ($expected_keys as $key) {
			if (isset($_POST[$key])) {
				$post_data[$key] = $_POST[$key];
			}
		}

		$callback = $this->scClient->spectrocoin_process_callback($post_data);
	
		if ($callback) {
			$order_id = $this->spectrocoin_parse_order_id($callback->getOrderId());
			$status = $callback->getStatus();

			$order = wc_get_order($order_id);
			if ($order) {
				switch ($status) {
					case (1): // new
					case (2): // pending
						$order->update_status('pending');
						self::spectrocoin_log("Order {$order_id} status updated to pending");
						break;
					case (3): // paid
						$order->update_status($this->order_status);
						break;
					case (4): // failed
                        $order->update_status('failed');
                        self::spectrocoin_log("Order {$order_id} status updated to failed");
                        break;
					case (5): // expired
						$order->update_status('failed');
                        self::spectrocoin_log("Order {$order_id} has expired, status updated to failed");
						break;
					case (6): // test
						$order->update_status($this->order_status);
						break;
				}
				echo esc_html__('*ok*', 'spectrocoin-accepting-bitcoin');
				exit;
			} else {
				self::spectrocoin_log("Order '{$order_id}' not found!");
				echo esc_html__('order not found', 'spectrocoin-accepting-bitcoin');
				exit;
			}
		} else {
			self::spectrocoin_log("Sent callback is invalid");
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
		return new SpectroCoin_CreateOrderRequest(
			$order->get_id() . "-" . $this->spectrocoin_random_str(5),
			self::$pay_currency,
			null,
			$receive_currency,
			$total,
			"Order #{$order->get_id()}",
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
	private function spectrocoin_parse_order_id($order_id)
	{
		return explode('-', $order_id)[0];
	}

	private function spectrocoin_check_currency()
  	{	
		$jsonFile = file_get_contents(plugin_dir_path( __FILE__ ) . '/../SCMerchantClient/data/acceptedCurrencies.JSON'); 
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
	private function spectrocoin_random_str($length)
	{
		return substr(md5(rand(1, pow(2, 16))), 0, $length);
	}

	
}