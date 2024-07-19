<?php

declare (strict_types=1);

namespace SpectroCoin\Includes;

use SpectroCoin\SCMerchantClient\SCMerchantClient;
use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Enum\OrderStatusEnum;
use SpectroCoin\SCMerchantClient\Exception\ApiError;
use SpectroCoin\SCMerchantClient\Exception\GenericError;
use SpectroCoin\Includes\SpectroCoinLogger;
use SpectroCoin\SCMerchantClient\Http\OrderCallback;

use WC_Payment_Gateway;
use WC_Logger;
use WC_Order;

use Exception;
use InvalidArgumentException;

use GuzzleHttp\Exception\GuzzleException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

class SpectroCoinGateway extends WC_Payment_Gateway
{
    private $sc_merchant_client;
    public $id;
    public $has_fields;

    public $order_button_text;
    public $method_title;
    public $method_description;
    public $supports;
    public $title;
    public $description;
    protected $client_id;
    protected $project_id;
    protected $client_secret;
    protected $order_status;

	public $form_fields = array();
    private $all_order_statuses;

	public $spectroCoinBlocksGateway;
	private $wc_logger;
	
	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		
		$this->id = 'spectrocoin';
		$this->has_fields = false;
		$this->method_title = esc_html__('SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->method_description = esc_html__('Take payments via SpectroCoin. Accept more than 30 cryptocurrencies, such as ETH, BTC, and USDT.', 'spectrocoin');

		$this->order_button_text = esc_html__('Pay with SpectroCoin', 'spectrocoin-accepting-bitcoin');
		$this->supports = array('products');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->client_id = $this->get_option('client_id');
		$this->project_id = $this->get_option('project_id');
		$this->client_secret = $this->get_option('client_secret');
		$this->order_status = $this->get_option('order_status');
		$this->all_order_statuses = wc_get_order_statuses();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        $this->initializeSCClient();
		$this->init_settings();
		$this->wc_logger = new WC_Logger();
		$this->form_fields = $this->generateFormFields();
	}

	/**
	 * Function which is called when SpectroCoin settings is saved.
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();
		if ($saved) {
			$this->reloadSettings();
			$this->validateSettings(true);
		}
	
		return $saved;
	}
	
	/**
	 * Initializes the SpectroCoin API client if credentials are valid.
	 */
    private function initializeSCClient() {
        $this->sc_merchant_client = new SCMerchantClient(
			$this->project_id,
            $this->client_id,
            $this->client_secret,
        );
        add_action('woocommerce_api_' . CONFIG::CALLBACK_NAME , array($this, 'callback'));
		
	}

	/**
	 * Reloads settings from database.
	 */
	private function reloadSettings() {
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->project_id = $this->get_option('project_id');
		$this->client_id = $this->get_option('client_id');
		$this->client_secret = $this->get_option('client_secret');
		$this->order_status = $this->get_option('order_status');
	}

	/**
	 * Check validity of credentials in settings.
	 * @param bool $display_notice If true, then error notices will be displayed Default = true.
	 * @return bool
	 */
	public function validateSettings($display_notice = true){
		$is_valid = true;

		if (empty($this->client_id)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Client ID is empty');
				error_log('SpectroCoin Error: Client ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->project_id)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Project ID is empty');
				error_log('SpectroCoin Error: Project ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->client_secret)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Client Secret is empty');
				error_log('SpectroCoin Error: Client Secret is empty');
			}
			$is_valid = false;
		}

		$this->title = sanitize_text_field($this->get_option('title'));
		if (empty($this->title)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Title cannot be empty');
				error_log('SpectroCoin Error: Title cannot be empty');
			}
			$is_valid = false;
		}

		$this->description = sanitize_textarea_field($this->get_option('description'));

		$this->enabled = sanitize_text_field($this->get_option('enabled'));
		if (!in_array($this->enabled, ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for enabled status');
				error_log('SpectroCoin Error: Invalid value for enabled status');
			}
			$is_valid = false;
		}

		$this->order_status = sanitize_text_field($this->get_option('order_status'));
		if (!array_key_exists($this->order_status, $this->all_order_statuses)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid order status');
				error_log('SpectroCoin Error: Invalid order status');
			}
			$is_valid = false;
		}

		if (!in_array(sanitize_text_field($this->get_option('display_logo')), ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for display logo status');
				error_log('SpectroCoin Error: Invalid value for display logo status');
			}
			$is_valid = false;
		}

		if (!in_array(sanitize_text_field($this->get_option('test_mode')), ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for test mode');
				error_log('SpectroCoin Error: Invalid value for test mode');
			}
			$is_valid = false;
		}
		return $is_valid;
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
		if (!function_exists('is_plugin_active') || !is_plugin_active(Config::getPluginFolderName() . '/spectrocoin.php') || $this->enabled !== 'yes') {
			return false;
		}
	
		if (!$this->checkFiatCurrency()) {
			$currency = esc_html(get_woocommerce_currency());
			$message = "{$currency} currency is not accepted by SpectroCoin. Please change your currency in the WooCommerce settings.";
			error_log("SpectroCoin Error: {$message}");
			
			$settings_link = esc_url(admin_url('admin.php?page=wc-settings&tab=general'));
			$this->displayAdminErrorNotice("{$message} <a href='{$settings_link}'>WooCommerce settings</a>.", true);
			return false;
		}
	
		if (!$this->validateSettings(false)) {
			return false;
		}
	
		if ($this->sc_merchant_client === null) {
			$this->initializeSCClient();
		}
	
		if ($this->sc_merchant_client === null) {
			return false;
		}
	
		if ($this->isTestModeEnabled() && !current_user_can('manage_options')) {
			return false;
		}

		return true;
	}
	

	/**
	 * Check if display logo is enabled.
	 * @return bool
	 */
	public function isDisplayLogoEnabled()
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
		$display_logo = $this->isDisplayLogoEnabled();

		if ($display_logo) {
			$icon = plugins_url('/../assets/images/spectrocoin-logo.svg', __FILE__);
			$icon_html = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('SpectroCoin logo', 'spectrocoin-accepting-bitcoin') . '" />';
			return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
		} else {
			return '';
		}
	}

	/**
	 * Check if test mode is enabled.
	 * @return bool
	 */
	public function isTestModeEnabled()
	{
		return $this->get_option('test_mode');
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
						esc_html_e('The SpectroCoin plugin allows seamless integration of payment gateways into your WordPress website. To get started, you\'ll need to obtain the essential credentials: "Project ID", "Client ID", and "Client Secret". These credentials are required to enable secure transactions between your website and the payment gateway. Follow the step-by-step tutorial below to acquire these credentials:', 'spectrocoin-accepting-bitcoin');
						?>
					</p>
					<ul>
						<li>
							<span>1. </span>
							<?php printf('<a href="%s" target="_blank"><strong>%s</strong></a> %s', esc_url('https://auth.spectrocoin.com/signup'), esc_html__('Sign up', 'spectrocoin-accepting-bitcoin'), esc_html__('for a SpectroCoin Account.', 'spectrocoin-accepting-bitcoin')); ?>
						</li>
						<li>
							<span>2. </span>
							<?php printf('<a href="%s" target="_blank"><strong>%s</strong></a> %s', esc_url('https://auth.spectrocoin.com/login'), esc_html__('Log in', 'spectrocoin-accepting-bitcoin'), esc_html__('to your SpectroCoin account.', 'spectrocoin-accepting-bitcoin')); ?>
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
							<?php esc_html_e('Click "Submit".', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>7. </span>
							<?php esc_html_e('Copy and paste the "Project ID".', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>8. </span>
							<?php
							echo esc_html__('Click on the user icon in the top right and navigate to ', 'spectrocoin-accepting-bitcoin') .
								'<strong><a href="' . esc_url('https://spectrocoin.com/en/settings/') . '">' .
								esc_html__('Settings', 'spectrocoin-accepting-bitcoin') .
								'</a></strong>' .
								esc_html__('. Then click on ', 'spectrocoin-accepting-bitcoin') .
								'<strong><a href="' . esc_url('https://spectrocoin.com/en/settings/api') . '">' .
								esc_html__('API', 'spectrocoin-accepting-bitcoin') .
								'</a></strong>' .
								esc_html__(' and choose ', 'spectrocoin-accepting-bitcoin') .
								'<strong><a href="' . esc_url('https://spectrocoin.com/en/settings/api/create') . '">' .
								esc_html__('Create New API', 'spectrocoin-accepting-bitcoin') .
								'</a></strong>.';
							?>
						</li>
						<li>
							<span>9. </span>
							<?php esc_html_e('Add "API name", in scope groups select "View merchant preorders", "Create merchant preorders", "View merchant orders", "Create merchant orders", "Cancel merchant orders" and click "Create API".', 'spectrocoin-accepting-bitcoin'); ?>
						</li>
						<li>
							<span>10. </span>
							<?php esc_html_e('Copy ant store "Client ID" and "Client Secret". Please be aware that the "Client Secret" will be showed once, so it should be stored safely. Lastly, save the settings.', 'spectrocoin-accepting-bitcoin'); ?>
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
	public function generateFormFields()
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

	/**
	 * Process the payment and return the result.
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$order = new WC_Order($order_id);

		$order_data = [
			'orderId' => (string)$order->get_id(),
			'description' => "Order #{$order_id}",
			'receiveAmount' => (float)$order->get_total(),
			'receiveCurrencyCode' => $order->get_currency(),
			'callbackUrl' => 'http://localhost.com',
			'successUrl' => 'http://localhost.com',
			'failureUrl' => 'http://localhost.com'
		];

		$response = $this->sc_merchant_client->createOrder($order_data);
		$order->update_status('on-hold', __('Waiting for SpectroCoin payment', 'spectrocoin-accepting-bitcoin'));

		if ($response instanceof ApiError || $response instanceof GenericError) {
			$error_message = "SpectroCoin error: Failed to create payment for order {$order_id}. Response message: {$response->getMessage()}";
			return $this->handleFailedOrder($order, $error_message);
		}

		return $this->handleSuccessOrder($order, $response->getRedirectUrl());
	}

	private function retryCreateOrder(){
		
	}

	private function handleFailedOrder(WC_Order $order, string $error_message){
		$order->update_status('failed', __($error_message, 'spectrocoin-accepting-bitcoin'));
		$this->wc_logger->log('error', $error_message);
		return array(
			'result'   => 'failed',
			'redirect' => ''
		);
	}

	private function handleSuccessOrder(WC_Order $order, string $redirect_url){
		global $woocommerce;
		$order->update_status('success');
		wc_reduce_stock_levels($order->get_id());
		$woocommerce->cart->empty_cart();
		return array(
			'result' => 'success',
			'redirect' => $redirect_url
		);
	}

	/**
	 * Used to process callbacks from SpectroCoin
	 * Callback is parsed to spectrocoin_process_callback method, which handles sanitization and validation.
	 * If callback is valid, then order status is updated.
	 * If callback is invalid, then error message is logged and order fails.
	 */
	public function callback(): void
	{
		try {
			$order_callback = $this->initCallbackFromPost();

			if (!$order_callback) {
				$this->wc_logger->log('error', "Sent callback is invalid");
				echo esc_html__('Invalid callback', 'spectrocoin-accepting-bitcoin');
				exit;
			}

			$order_id = $this->parseOrderId($order_callback->getOrderId());
			$status = $order_callback->getStatus();

			$order = wc_get_order($order_id);
			if ($order) {
				switch ($status) {
					case OrderStatusEnum::New->value:
					case OrderStatusEnum::Pending->value:
						$order->update_status('Pending payment');
						break;
					case OrderStatusEnum::Paid->value:
						$order->update_status($this->order_status);
						break;
					case OrderStatusEnum::Failed->value:
						$order->update_status('failed');
						break;
					case OrderStatusEnum::Expired->value:
						$order->update_status($this->order_status);
						break;
				}
				echo esc_html__('*ok*', 'spectrocoin-accepting-bitcoin');
				exit;
			} else {
				$this->wc_logger->log('error', "Order '{$order_id}' not found!");
				echo esc_html__('order not found', 'spectrocoin-accepting-bitcoin');
				exit;
			}
		} catch (GuzzleException $e) {
			$this->wc_logger->log('error', "Callback API error: {$e->getMessage()}");
			echo esc_html__('Callback API error', 'spectrocoin-accepting-bitcoin');
			exit;
		} catch (InvalidArgumentException $e) {
			$this->wc_logger->log('error', "Error processing callback: {$e->getMessage()}");
			echo esc_html__('Error processing callback', 'spectrocoin-accepting-bitcoin');
			exit;
		} catch (Exception $e) {
			$this->wc_logger->log('error', "Error processing callback: {$e->getMessage()}");
			echo esc_html__('Error processing callback', 'spectrocoin-accepting-bitcoin');
			exit;
		}
	}

	/**
	 * Initializes the callback data from POST request.
	 * 
	 * @return OrderCallback|null Returns an OrderCallback object if data is valid, null otherwise.
	 */
	private function initCallbackFromPost(): ?OrderCallback
	{
		$expected_keys = ['userId', 'merchantApiId', 'merchantId', 'apiId', 'orderId', 'payCurrency', 'payAmount', 'receiveCurrency', 'receiveAmount', 'receivedAmount', 'description', 'orderRequestId', 'status', 'sign'];

		$callback_data = [];
		foreach ($expected_keys as $key) {
			if (isset($_POST[$key])) {
				$callback_data[$key] = $_POST[$key];
			}
		}

		if (empty($callback_data)) {
			$this->wc_logger->log('error', "No data received in callback");
			return null;
		}

		return new OrderCallback($callback_data);
	}

	/**
	 * Parse order id from SpectroCoin callback
	 * @param string $order_id
	 * @return string
	 */
	private function parseOrderId($order_id)
	{
		return explode('-', $order_id)[0];
	}

	/**
	 * Check if currency is accepted by SpectroCoin
	 * Function compares current currency with accepted currencies from Config class
	 * @return bool
	 */
	private function checkFiatCurrency(): bool
	{
		$current_currency_iso_code = get_woocommerce_currency();
		return in_array($current_currency_iso_code, Config::ACCEPTED_FIAT_CURRENCIES);
	}

	/**
     * Display error message in admin settings
     * @param string $message Error message
     * @param bool $allow_hyperlink Allow hyperlink in error message
     */
    public static function displayAdminErrorNotice($message, $allow_hyperlink = false) {
        static $displayed_messages = array();

        $allowed_html = $allow_hyperlink ? array(
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
            ),
        ) : array();

        $processed_message = wp_kses($message, $allowed_html);

        $current_page = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

        if (!empty($processed_message) && !in_array($processed_message, $displayed_messages) && $current_page == "spectrocoin") {
            array_push($displayed_messages, $processed_message);
            ?>
            <div class="notice notice-error">
                <p><?php echo __("SpectroCoin Error: ", 'spectrocoin-accepting-bitcoin') . $processed_message; // Using $processed_message directly ?></p>
            </div>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    var notices = document.querySelectorAll('.notice-error');
                    notices.forEach(function(notice) {
                        notice.style.display = 'block';
                    });
                });
            </script>
            <?php
        }
    }
}