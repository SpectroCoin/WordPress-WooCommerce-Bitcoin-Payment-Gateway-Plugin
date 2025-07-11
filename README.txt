
=== SpectroCoin Payment Extension for WooCommerce ===
Contributors: spectrocoin
Donate link: https://spectrocoin.com/en/
Tags: woocommerce bitcoin plugin, crypto payments, accept cryptocurrencies, bitcoin payment gateway, spectrocoin payment gateway
Requires at least: 6.2
Tested up to: 6.8.1
Stable tag: 2.1.0
WC requires at least: 7.4
WC tested up to: 9.9.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SpectroCoin Payments for WooCommerce is a Wordpress plugin that allows to accept cryptocurrencies at WooCommerce-powered online stores.

== Description ==

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

3. From Wordpress admin dashboard, navigate to **Plugins** -> **Add New** -> **Upload Plugin**. -> Upload the `spectrocoin.zip` file.

4. Activate the plugin through the WordPress dashboard -> Plugins -> Activate.
When page reloads click on "Settings".

5. Enter your Client ID, Client Secret, and Project ID (see How to get API credentials below).

== How to get API credentials ==

1. **Sign up** for a SpectroCoin Account.

2. **Log in** to your SpectroCoin account.

3. On the dashboard, locate the **Business** tab and click on it.

4. Click on **New project** and fill in the project details.

5. Submit the project and copy the "Project ID".

6. Navigate to **Settings** -> **API** and create a new API by providing an API name. Select the following scope groups:
   - "View merchant preorders"
   - "Create merchant preorders"
   - "View merchant orders"
   - "Create merchant orders"
   - "Cancel merchant orders"

7. Copy and store the Client ID and Client Secret securely.

== Testing & Callbacks ==

Order callbacks notify your server when an order’s status transitions to PAID, EXPIRED, or FAILED.

1. Enable **Test Mode** in your SpectroCoin project settings.

2. Simulate payment statuses:
   - **PAID**: Updates the order as **Completed**.
   - **EXPIRED**: Updates the order as **Failed**.

3. Ensure your `callbackUrl` is publicly accessible (local servers like `localhost` will not work).

4. Check the **Order History** and callback logs on SpectroCoin to verify callback success.

== Debugging ==

For troubleshooting, navigate to **WooCommerce** -> **Status** -> **Logs** and check the **plugin-spectrocoin** log file. If additional support is needed, contact SpectroCoin and provide the log details.

== Screenshots ==

1. Plugin settings page.
2. SpectroCoin checkout option.
3. Plugin settings window.
4. Payment window example.

== Remove plugin ==

1. Navigate to WordPress dashboard -> Plugins -> Installed Plugins.
2. Search for SpectroCoin plugin and click "Deactivate".
3. Click "Delete".

== Changelog ==

### 2.0.0 (11/02/2025)
- Major update with new OAuth authentication using Client ID and Client Secret.
- Deprecated private key and merchant ID methods.
- Improved API endpoints for performance and security.
- Adherence to PSR-12 coding standards and enhanced plugin stability.

== Contact ==

For support, contact us via:  
E-mail: merchant@spectrocoin.com  
Skype: [spectrocoin_merchant](https://join.skype.com/invite/iyXHU7o08KkW)  
[Web](https://spectrocoin.com)  
[X (formerly Twitter)](https://twitter.com/spectrocoin)  
[Facebook](https://www.facebook.com/spectrocoin/)
