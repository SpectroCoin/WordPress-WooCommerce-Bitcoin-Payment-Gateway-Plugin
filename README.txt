=== SpectroCoin Payment Extension for WooCommerce ===
Contributors: spectrocoin
Donate link: https://spectrocoin.com/en/
Tags: woocommerce bitcoin plugin, crypto payments, accept cryptocurrencies, bitcoin payment gateway, spectrocoin payment gateway
Requires at least: 6.2
Tested up to: 6.6.1
Stable tag: 1.5.1
WC requires at least: 7.4
WC tested up to: 9.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SpectroCoin Payments for WooCommerce is a Wordpress plugin that allows to accept cryptocurrencies at WooCommerce-powered online stores.

== Description ==

[youtube https://www.youtube.com/watch?v=q__DdVhD5RQ&t=166s]

Welcome to the world of seamless cryptocurrency transactions on your WooCommerce site! With SpectroCoin's innovative WordPress payment plugin, you can now accept a wide range of cryptocurrencies, including BTC, ETH, USDT, and over 25 other popular digital assets, right on your online store.

Why is this the perfect solution for WooCommerce users? If you are already using WooCommerce, integrating our plugin is the recommended method for venturing into the world of crypto payments. It opens up a whole new realm of possibilities, allowing customers to make secure and hassle-free purchases on your site.

Plugin is now compatible with the new block-based cart, checkout, and order confirmation functionality introduced in WooCommerce 8.3.

== Benefits ==

1. Accept Multiple Cryptocurrencies: Easily add BTC, ETH, USDT, and 25+ other cryptocurrencies to your WooCommerce site.
2. Seamlessly integrate the plugin into your existing WooCommerce store without any hassle.
3. Attract customers from around the world with secure crypto payment options.
4. Avoid price volatility by settling payments in EUR, USD, GBP, or 20+ supported fiat currencies.
5. Ensure safe and secure crypto transactions for you and your customers.
6. Offer a wide range of payment methods to appeal to tech-savvy customers.
7. Embrace the growing trend of crypto payments, building trust among your audience.
8. Receive prompt assistance from SpectroCoin's exceptional customer support team.

== Installation ==

1. Install WooCommerce plugin and configure your store (if you haven't done so already - http://wordpress.org/plugins/woocommerce/).

2. Download SpectroCoin plugin or upload SpectroCoin plugin directory to the `/wp-content/plugins/` directory.

3. Generate private key (Automatically via SpectroCoin.com)

See detailed guide "How to get API credentials" below.

4. Generate private key (Manually using OpenSSL)
    1. Private key:
    ```shell
    # generate a 2048-bit RSA private key
    openssl genrsa -out "C:\private" 2048
    ```
5. Activate the plugin through the WordPress dashboard -> Plugins -> Activate.
When page reloads click on "Settings".

6. Enter your Merchant Id, Project Id and Private key (see How to get API credentials).

== How to get API credentials ==

1. Sign up for a Spectroin Account.

2. Log in to your Spectroin account.

3. On the dashboard, locate the "Business" tab and click on it.

4. Click on "New project."

5. Fill in the project details and select desired settings (settings can be changed).

6. The Private Key can be obtained by switching on the Public key radio button (Private key won't be visible in the settings window, and it will have to be regenerated in settings). Copy or download the newly generated private key.

7. Click "Submit".

8. Copy and paste the Merchant ID and Project ID.

9. Generate a test product. Create a test page on your WordPress website with a payment form connected to the Spectroin payment gateway. Perform a trial transaction using the test payment gateway (Test mode can be activated in project settings) to validate the integration's functionality. Verify the transaction details on the Spectroin dashboard to ensure it was successfully processed.

Note: Keep in mind that if you want to use the business services of SpectroCoin, your account has to be verified.

== Screenshots ==

1. How to access plugin settings.
2. SpectroCoin checkout option.
3. SpectroCoin Plugin settings window.
4. Payment window.
5. Payment window.

== Remove plugin ==

1. WordPress dashboard -> Plugin -> Installed Plugins.
2. Search for SpectroCoin plugin and click "Deactivate".
3. Click "Delete".

== Changelog ==

Version 1.5.1 (09/05/2024):

Fixed dynamic string Internationalization.

Removed "Test" order, now when test mode enabled, returned callback status will "PAID" or "EXPIRED", depends which is chosen in merchant project settings.

Adjusted string from "test mode" to "hide from checkout" in plugin settings.

Fixed a bud related with the payment method not displaying in checkout due to "test mode".

### 1.5.0 (02/05/2024)

Added Compatibility with the new block-based checkout functionality introduced in WooCommerce 8.3.

Fixed Deprecated functions/methods/variables.

Removed Empty instructions variable, if needed, it will be added in future versions.

Fixed Compatibility with "High-Performance Order Storage" introduced in WooCommerce 8.2.

Added Test mode checkbox. When enabled, if order callback is received, then test order will be set to selected order status (by default - "Completed"). Also SpectroCoin payment option will be visible only for admin user.

Added Messages related with order processing to order notes.

Fixed "Failed" status with failed and expired orders.

### 1.4.1 (01/26/2024)

Removed Plugin dependency from plugin directory names

Fixed Fatal error for new installations

### 1.4.0 (01/03/2024)

This update is significant to plugin's security and stability. The posibility of errors during checkout is minimized, reduced posibility of XSS and SQL injection attacks.

Migrated to GuzzleHttp since HTTPful is no longer maintained. In this case /vendor directory was added which contains GuzzleHttp dependencies.

Added Settings field sanitization.

Added Settings field validation. In this case we minimized possible error count during checkout, SpectroCoin won't appear in checkout until settings validation is passed.

Added Admin notice in admin plugin settings for all fields validation.

Added Escaping all output variables with appropriate functions.

Added "spectrocoin\_" prefix to functiton names.

Added "SpectroCoin\_" prefix to class names.

Added validation and sanitization when request payload is created.

Added validation and sanitization when callback is received.

Added components class "SpectroCoin_ValidationUtil" for specific validation functions.

Added logging to Wordpress log when errors occur.

Added logging to WooCommerce status log when errors occur.

Fixed is_available() function behaviour, when sometimes it returned false, even if all settings were correct.

Optimised the The whole $\_POST stack processing. Now only needed callback keys is being processed.

Updated hardcoded notice display from admin_options() function.

Updated spectrocoin_admin_error_notice() function, added additional parameter to allow hyperlink display. Also the notice will be displayed once and won't be displayed in other admin screens except SpectroCoin settings.

### 1.3.0 (10/04/2023)

Fixed hardcoded order statuses in plugin settings.

Added Custom order statuses created manually or using plugins will appear in SpectroCoin settings menu.

Added a new function, when during checkout, if error is occured, now client will see the error code and message instead of generic error message.

Added plugin checks the FIAT currency, if it is not supported by SpectroCoin, payment will not be available.

Added admin notice in admin plugin settings to notify that shop currency is not supported by SpectroCoin.

### 1.2.0 (09/10/2023)

Added plugin string internationalization, for plugin translation to various languages.

Added two additional links within admin window connecting to official wordpress.org website to easily rate, leave feedback and report bugs.

Updated style changes in settings window

For Developers Added documentation with parameters and return variables before every function

### 1.1.0 (07/31/2023)

Added a new option in admin menu, to display or not the SpectroCoin logo during checkout.

### 1.0.0 (07/31/2023)

Added a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

Implemented an "if" statement to handle compatibility with older PHP versions (PHP 8 and below) for the function openssl_free_key($public_key_pem). This change is necessary as PHP 8
deprecates openssl_free_key and now automatically destroys the key instance when it goes out of scope. (Source https//stackoverflow.com/questions/69559775/php-openssl-free-key-deprecated)

Improved the WC_Gateway_Spectrocoin class, made changes to prevent deprecated messages related to the creation of dynamic properties. The properties (merchant_id, protected_id, private_key, and order_status) are now explicitly declared as protected, and getter functions are added to ensure better encapsulation. This update is particularly important for PHP version 8.2 and above.

Added a dependency on the WooCommerce plugin for the SpectroCoin plugin. The SpectroCoin plugin now requires WooCommerce to be installed and active on the site. If the user deletes or deactivates WooCommerce, a notice will be displayed, and the SpectroCoin plugin will be deactivated automatically.

Enhanced the style of the admin's payment settings window to match the design of SpectroCoin.com, providing a more cohesive user experience.

Added an informative message on the admin page, guiding users on how to obtain the mandatory credentials required for using the SpectroCoin plugin effectively. This addition helps users easily find the necessary information for setup and configuration.
