<?php

declare(strict_types=1);

namespace SpectroCoin\Includes;

use SpectroCoin\SCMerchantClient\SCMerchantClient;
use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Enum\OrderStatus;
use SpectroCoin\SCMerchantClient\Exception\ApiError;
use SpectroCoin\SCMerchantClient\Exception\GenericError;
use SpectroCoin\SCMerchantClient\Http\OrderCallback;
use SpectroCoin\SCMerchantClient\Utils;

use WC_Payment_Gateway;
use WC_Logger;
use WC_Order;

use Exception;
use InvalidArgumentException;

use GuzzleHttp\Exception\RequestException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

class SpectroCoinGateway extends WC_Payment_Gateway
{
	public $id;
	public $has_fields;
	public $order_button_text;
	public $method_title;
	public $method_description;
	public $supports;
	public $title;
	public $description;
	public $form_fields = array();

	private SCMerchantClient $sc_merchant_client;
	protected string $client_id;
	protected string $project_id;
	protected string $client_secret;
	protected string $order_status;
	private array $all_order_statuses;
	private WC_Logger $wc_logger;

	private static $renew_notice_displayed = false;

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

        add_action('admin_notices', array($this, 'renew_keys_notice')); // (REMOVE THIS WHEN UPDATE NOTICE IS NOT NEEDED)
	}

	/**
	 * Function which is called when SpectroCoin settings is saved.
	 */
	public function process_admin_options(): bool
	{
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
	private function initializeSCClient(): void
	{
		$this->sc_merchant_client = new SCMerchantClient(
			$this->project_id,
			$this->client_id,
			$this->client_secret,
		);
		add_action('woocommerce_api_' . CONFIG::CALLBACK_NAME, array($this, 'callback'));

	}

	/**
	 * Reloads settings from database.
	 */
	private function reloadSettings(): void
	{
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
	public function validateSettings(bool $display_notice = true): bool
	{
		$is_valid = true;

		if (empty($this->client_id)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Client ID is empty');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Client ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->project_id)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Project ID is empty');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Project ID is empty');
			}
			$is_valid = false;
		}

		if (empty($this->client_secret)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Client Secret is empty');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Client Secret is empty');
			}
			$is_valid = false;
		}

		$this->title = sanitize_text_field($this->get_option('title'));
		if (empty($this->title)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Title cannot be empty');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Title cannot be empty');
			}
			$is_valid = false;
		}

		$this->description = sanitize_textarea_field($this->get_option('description'));

		$this->enabled = sanitize_text_field($this->get_option('enabled'));
		if (!in_array($this->enabled, ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for enabled status');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Invalid value for enabled status');
			}
			$is_valid = false;
		}

		$this->order_status = sanitize_text_field($this->get_option('order_status'));
		if (!array_key_exists($this->order_status, $this->all_order_statuses)) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid order status');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Invalid order status');
			}
			$is_valid = false;
		}

		if (!in_array(sanitize_text_field($this->get_option('display_logo')), ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for display logo status');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Invalid value for display logo status');
			}
			$is_valid = false;
		}

		if (!in_array(sanitize_text_field($this->get_option('hide_from_checkout')), ['yes', 'no'])) {
			if ($display_notice) {
				$this->displayAdminErrorNotice('Invalid value for test mode');
				$this->wc_logger->log('warning', 'SpectroCoin settings validation warning: Invalid value for test mode');
			}
			$is_valid = false;
		}
		return $is_valid;
	}

	/**
	 * Function to toggle the display of the SpectroCoin payment option
	 * The payment method will be displayed when the following conditions are met:
	 * 1. The SpectroCoin plugin is enabled.
	 * 2. The SpectroCoin plugin is configured with valid credentials.
	 * 3. The FIAT currency is accepted by SpectroCoin. Accepted currencies are: "EUR", "USD", "PLN", "CHF", "SEK", "GBP", "AUD", "CAD", "CZK", "DKK", "NOK".
	 * 4. The "hide from checkout" admin option is disabled or the current user is an admin.
	 * @return bool
	 */
	public function is_available(): bool
	{
		if (!function_exists('is_plugin_active') || !is_plugin_active(Utils::getPluginFolderName() . '/spectrocoin.php') || $this->enabled !== 'yes') {
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

		if ($this->isHideFromCheckoutEnabled() && !current_user_can('manage_options')) {
			return false;
		}

		return true;
	}

	/**
	 * Check if "display logo" admin option is enabled.
	 * @return bool
	 */
	public function isDisplayLogoEnabled(): bool
	{
		if ($this->get_option('display_logo') == "yes") {
			return true;
		}
		return false;
	}

	/**
	 * Check if "hide from checkout" admin option enabled.
	 * @return bool
	 */
	public function isHideFromCheckoutEnabled(): bool
	{
		if ($this->get_option('hide_from_checkout') == "yes") {
			return true;
		}
		return false;
	}

	/**
	 * Generate admin settings form.
	 */
	public function admin_options(): void
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
					esc_url('mailto:merchant@spectrocoin.com'),
					esc_html('merchant@spectrocoin.com')
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 * @return array
	 */
	public function generateFormFields(): array
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
				'default' => 'wc-completed',
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
			'hide_from_checkout' => array(
				'title' => esc_html__('Hide from checkout', 'spectrocoin-accepting-bitcoin'),
				'description' => esc_html__('When enabled SpectroCoin payment option will be visible only for admin user.', 'spectrocoin-accepting-bitcoin'),
				'desc_tip' => true,
				'type' => 'checkbox',
				'label' => esc_html__('Enable', 'spectrocoin-accepting-bitcoin'),
				'default' => 'no'
			)
		);
	}

	/**
	 * Process the payment and return the result.
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment($order_id): array
	{
		$order = new WC_Order($order_id);

		$order_data = [
			'orderId' => $order->get_id() . "-" . Utils::generateRandomStr(6),
			'description' => "Order #{$order_id} from " . get_site_url(),
			'receiveAmount' => $order->get_total(),
			'receiveCurrencyCode' => $order->get_currency(),
			'callbackUrl' => get_site_url(null, '?wc-api=' . Config::CALLBACK_NAME),
			'successUrl' => $this->get_return_url($order),
			'failureUrl' => $this->get_return_url($order)
		];

		$response = $this->sc_merchant_client->createOrder($order_data);

		if ($response instanceof ApiError || $response instanceof GenericError) {
			$error_message = "SpectroCoin error: Failed to create payment for order {$order_id}. Response message: {$response->getMessage()}";
			$order->update_status('failed', __($error_message, 'spectrocoin-accepting-bitcoin'));
			$this->wc_logger->log('error', $error_message);
			return array(
				'result' => 'failed',
				'redirect' => ''
			);
		}

		$order->update_status('pending', __('Waiting for SpectroCoin payment', 'spectrocoin-accepting-bitcoin'));
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
	public function callback(): void
	{
		try {
			global $woocommerce;
			$order_callback = $this->initCallbackFromPost();

			if (!$order_callback) {
				$this->wc_logger->log('error', "Sent callback is invalid");
				http_response_code(400); // Bad Request
				echo esc_html__('Invalid callback', 'spectrocoin-accepting-bitcoin');
				exit;
			}

			$order_id = explode('-', ($order_callback->getOrderId()))[0];
			$status = $order_callback->getStatus();
			$order = wc_get_order($order_id);
			if ($order) {
				switch ($status) {
					case OrderStatus::New ->value:
					case OrderStatus::Pending->value:
						$order->update_status('pending');
						break;
					case OrderStatus::Paid->value:
						$woocommerce->cart->empty_cart();
						$order->payment_complete();
						$order->update_status($this->order_status);
						break;
					case OrderStatus::Failed->value:
						$order->update_status('failed');
						break;
					case OrderStatus::Expired->value:
						$order->update_status('failed');
						break;
				}
				http_response_code(200); // OK
				echo esc_html__('*ok*', 'spectrocoin-accepting-bitcoin');
				exit;
			} else {
				$this->wc_logger->log('error', "Order '{$order_id}' not found!");
				http_response_code(404); // Not Found
				echo esc_html__("Order '{$order_id}' not found!", 'spectrocoin-accepting-bitcoin');
				exit;
			}
		} catch (RequestException $e) {
			$this->wc_logger->log('error', "Callback API error: {$e->getMessage()}");
			http_response_code(500); // Internal Server Error
			echo esc_html__('Callback API error', 'spectrocoin-accepting-bitcoin');
			exit;
		} catch (InvalidArgumentException $e) {
			$this->wc_logger->log('error', "Error processing callback: {$e->getMessage()}");
			http_response_code(400); // Bad Request
			echo esc_html__('Error processing callback', 'spectrocoin-accepting-bitcoin');
			exit;
		} catch (Exception $e) {
			$this->wc_logger->log('error', "Error processing callback: {$e->getMessage()}");
			http_response_code(500); // Internal Server Error
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
	 * Display error message in spectrocoin admin settings
	 * @param string $message Error message
	 * @param bool $allow_hyperlink Allow hyperlink in error message
	 */
	public static function displayAdminErrorNotice(string $message, bool $allow_hyperlink = false): void
	{
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
				<p><?php echo __("SpectroCoin Error: ", 'spectrocoin-accepting-bitcoin') . $processed_message; // Using $processed_message directly ?>
				</p>
			</div>
			<script type="text/javascript">
				document.addEventListener("DOMContentLoaded", function () {
					var notices = document.querySelectorAll('.notice-error');
					notices.forEach(function (notice) {
						notice.style.display = 'block';
					});
				});
			</script>
			<?php
		}
	}

   /**
     * Show notice if client_id and client_secret are missing and notice has not been addressed.
	 * (REMOVE THIS WHEN UPDATE NOTICE IS NOT NEEDED)
     */
    public function renew_keys_notice() {
        // Check if the notice should be displayed
		if (self::$renew_notice_displayed) {
            return;
        }
		if (empty($this->project_id)){ // this prevents from displaying notice to new installations
			return;
		}
        if (empty($this->client_id) || empty($this->client_secret)) {
            self::$renew_notice_displayed = true; // prevent double notice display
            ?>
            <div class="notice notice-warning is-dismissible" data-notice="spectrocoin_credentials">
                <p>
                    <?php _e('Action Required: Your SpectroCoin plugin needs to be configured to function properly.', 'spectrocoin-accepting-bitcoin'); ?>
                </p>
                <p>
                    <?php _e('The new SpectroCoin API requires you to provide a valid Client ID and Client Secret in the plugin settings. Without these credentials, the plugin will not be able to process payments.', 'spectrocoin-accepting-bitcoin'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=spectrocoin')); ?>" class="button button-primary">
                        <?php _e('Go to SpectroCoin Settings', 'spectrocoin-accepting-bitcoin'); ?>
                    </a>
                </p>
                <p>
                    <?php _e('Need help? Refer to the plugin documentation or contact SpectroCoin support for assistance.', 'spectrocoin-accepting-bitcoin'); ?>
                </p>
            </div>
            <script type="text/javascript">
                jQuery(document).on('click', '.notice[data-notice="spectrocoin_credentials"] .notice-dismiss', function () {
                    jQuery.post(ajaxurl, { action: 'spectrocoin_dismiss_notice' });
                });
            </script>
            <?php
        }
	}
}