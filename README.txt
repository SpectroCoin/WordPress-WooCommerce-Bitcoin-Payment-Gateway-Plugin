=== Bitcoin Payment Extension for WP WooCommerce ===
Contributors: SpectroCoin, spectrocoin.com
Donate link: https://spectrocoin.com/en/
Tags: woocommerce bitcoin plugin, crypto payments, accept cryptocurrencies, bitcoin payment gateway, spectrocoin payment gateway, secure payments, settle payments in fiat, altcoin support
Requires at least: 6.1
Tested up to: 6.2.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Bitcoin Payments for WooCommerce is a Wordpress plugin that allows to accept bitcoins at WooCommerce-powered online stores.

== Description ==

[youtube https://www.youtube.com/watch?v=q__DdVhD5RQ&t=166s]

Welcome to the world of seamless cryptocurrency transactions on your WooCommerce site! With SpectroCoin's innovative WordPress Bitcoin payment plugin, you can now accept a wide range of cryptocurrencies, including BTC, ETH, USDT, and over 25 other popular digital assets, right on your online store.

Why is this the perfect solution for WooCommerce users? If you are already using WooCommerce, integrating our plugin is the recommended method for venturing into the world of crypto payments. It opens up a whole new realm of possibilities, allowing customers to make secure and hassle-free purchases on your site.

==Benefits==

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

Version 1.0.0 (07/31/2023):

Fixed: Corrected a typo in the plugin's description. Changed "aplugin" to "a plugin" for better clarity.

Added: Included a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

Updated: Implemented an "if" statement to handle compatibility with older PHP versions (PHP 8 and below) for the function openssl_free_key($public_key_pem). This change is necessary as PHP 8
deprecates openssl_free_key and now automatically destroys the key instance when it goes out of scope. (Source: https://stackoverflow.com/questions/69559775/php-openssl-free-key-deprecated)

Improved: In the WC_Gateway_Spectrocoin class, made changes to prevent deprecated messages related to the creation of dynamic properties. The properties (merchant_id, protected_id, private_key, and order_status) are now explicitly declared as protected, and getter functions are added to ensure better encapsulation. This update is particularly important for PHP version 8.2 and above.

Added: Specified a dependency on the WooCommerce plugin for the SpectroCoin plugin. The SpectroCoin plugin now requires WooCommerce to be installed and active on the site. If the user deletes or deactivates WooCommerce, a notice will be displayed, and the SpectroCoin plugin will be deactivated automatically.

Added: Enhanced the style of the admin's payment settings window to match the design of SpectroCoin.com, providing a more cohesive user experience.

Added: Introduced an informative message on the admin page, guiding users on how to obtain the mandatory credentials required for using the SpectroCoin plugin effectively. This addition helps users easily find the necessary information for setup and configuration.

Version 1.1.0 (07/31/2023):

Added: Included a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

Version 1.2.0 MINOR (07/31/2023):

Added: Implemented plugin string internationalization, for plugin translation to various languages.

Added: Included two additional links within admin window connecting to official wordpress.org website to easily rate, leave feedback and report bugs.

Tested: Tested and checked compatibility with Wordpress 6.3 and WooCommerce 8.0.1

Modified: Added style changes in settings window

For Developers: Added documentation with parameters and return variables before every function

== Frequently Asked Questions ==

Can I use this plug-in on more than one sites?
Absolutely.

Which cryptocurrencies does your payment gateway support?
Currently our payment gateway supports more than 30 different cryptocurrencies that are based on ERC-20 network. 

List of currently supported merchant currencies: Bitcoin (BTC), Ether (ETH), Tether (USDT), USD Coin (USDC), Aave (AAVE), Basic Attention Token (BAT), Banker (BNK), Binance (BUSD), Chiliz (CHZ), Compound (COMP), Dai (DAI), DASH (DASH), Enjin Coin (ENJ), The Graph (GRT), Holo (HOT), Chainlink (LINK), Decentraland (MANA), Mask Network (MASK), Polygon (MATIC), Maker (MKR), The Sandbox (SAND), Shiba Inu (SHIB), Synthetic (SNX), SushiSwap (SUSHI), True USD (TUSD), UMA (UMA), Uniswap (UNI), PAX Dollar (USDP), Wrapped BTC (WBTC), NEM (XEM), 0x (ZRX).

Can I accept Euro (EUR) or Dollar (USD) using Spectrocoin’s payment gateway?
No, our payment gateway allows users to accept cryptocurrencies only. Though, our merchants can automatically convert all payments that are received via our payment gateway to any other supported currencies on Spectrocoin (FIAT currencies included).

What fees do I have to pay in order to use your cryptocurrency payment gateway?
Every merchant payment will be counted up to 1%. The percentage depends on the type of business and the frequency of payments. There are no other setup or maintenance fees when using our payment gateway!

I want to start accepting cryptocurrencies on my website, what are the steps that I have to take?
In order to integrate our crypto payment gateway our clients have to go through a few simple steps: create an account, verify it, create a merchant project and use its details to connect it to the website where you want to start accepting cryptocurrencies. All of the steps explained in more detail can be found here: https://spectrocoin.com/en/faqs/merchants-and-payment-processing/how-can-i-register-as-a-merchant.html 

If you have any questions please contact us at info@spectrocoin.com or skype us spectrocoin_merchant and We'll be happy to answer them.
