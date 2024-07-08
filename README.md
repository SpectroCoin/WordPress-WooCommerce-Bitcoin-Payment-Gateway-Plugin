# SpectroCoin Wordpress Crypto Payment Plugin

Integrate cryptocurrency payments seamlessly into your Wordpress store with the [SpectroCoin Crypto Payment Plugin](https://spectrocoin.com/plugins/accept-bitcoin-wordpress-woocommerce.html). This extension facilitates the acceptance of a variety of cryptocurrencies, enhancing payment options for your customers. Easily configure and implement secure transactions for a streamlined payment process on your Wordpress website.

## Installation

0. We strongly recommend downloading the plugin from [Wordpress](https://wordpress.org/plugins/spectrocoin-accepting-bitcoin/). In case you are downloading it from github, please follow the installation steps below.</br>
1. Download latest release from github.
2. Extract and upload plugin folder to your Wordpress <em>/wp-content/plugins/</em> directory.<br />
   OR<br>
   From Wordpress admin dashboard navigate tp **"Plugins"** -> **"Add New"** -> **"Upload Plugin"**. -> Upload <em>spectrocoin.zip</em>.</br>
3. Go to **"Plugins"** -> **"Installed Plugins"** -> Locate installed plugin and click **"Activate"** -> **"Settings"**.

## Setting up

1. **[Sign up](https://auth.spectrocoin.com/signup)** for a SpectroCoin Account.
2. **[Log in](https://auth.spectrocoin.com/login)** to your SpectroCoin account.
3. On the dashboard, locate the **[Business](https://spectrocoin.com/en/merchants/projects)** tab and click on it.
4. Click on **[New project](https://spectrocoin.com/en/merchants/projects/new)**.
5. Fill in the project details and select desired settings (settings can be changed).
6. Click **"Submit"**.
7. Copy and paste the "Project id".
8. Click on the user icon in the top right and navigate to **[Settings](https://test.spectrocoin.com/en/settings/)**. Then click on **[API](https://test.spectrocoin.com/en/settings/api)** and choose **[Create New API](https://test.spectrocoin.com/en/settings/api/create)**.
9. Add "API name", in scope groups select **"View merchant preorders"**, **"Create merchant preorders"**, **"View merchant orders"**, **"Create merchant orders"**, **"Cancel merchant orders"** and click **"Create API"**.
10. Copy and store "Client id" and "Client secret". Save the settings.

**Note:** Keep in mind that if you want to use the business services of SpectroCoin, your account has to be verified.

## Test order creation on localhost

We gently suggest trying out the plugin in a server environment, as it will not be capable of receiving callbacks from SpectroCoin if it will be hosted on localhost. To successfully create an order on localhost for testing purposes, <b>change these 3 lines in <em>SCMechantClient.php spectrocoinCreateOrder() function</em></b>:

`'callbackUrl' => $request->getCallbackUrl()`, <br>
`'successUrl' => $request->getSuccessUrl()`, <br>
`'failureUrl' => $request->getFailureUrl()`

<b>To</b>

`'callbackUrl' => 'http://localhost.com'`, <br>
`'successUrl' => 'http://localhost.com'`, <br>
`'failureUrl' => 'http://localhost.com'`

Don't forget to change it back when migrating website to public.

## Debugging

If you get "Something went wrong. Please contact us to get assistance." message during checkout process, please navigate to **"WooCommerce"** -> **"Status"** -> **"Logs"** and check **"spectrocoin"** log file for more information. If the logs are not helpful or do not display, please contact us and provide the log file details so that we can help.

## Changelog

### 2.0.0 ()

_Updated_: Order creation API endpoint has been updated for enhanced performance and security.

_Removed_: Private key functionality and merchant ID requirement have been removed to streamline integration.

_Added_: OAuth functionality introduced for authentication, requiring Client ID and Client Secret for secure API access.

_Fixed_: Changed save button class to prevent conflicts with other buttons.

### 1.5.0 MINOR (02/05/2024):

_Added_: Compatibility with the new block-based checkout functionality introduced in WooCommerce 8.3.

_Fixed_: Deprecated functions/methods/variables.

_Removed_: Empty instructions variable, if needed, it will be added in future versions.

_Fixed_: Compatibility with "High-Performance Order Storage" introduced in WooCommerce 8.2.

_Added_: Test mode checkbox. When enabled, if order callback is received, then test order will be set to selected order status (by default - "Completed"). Also SpectroCoin payment option will be visible only for admin user.

_Added_: Messages related with order processing to order notes.

_Fixed_: "Failed" status with failed and expired orders.

### 1.4.1 PATCH (01/26/2024):

_Removed_: Plugin dependency from plugin directory names

_Fixed_: Fatal error for new installations

### 1.4.0 MINOR (01/03/2024):

This update is significant to plugin's security and stability. The posibility of errors during checkout is minimized, reduced posibility of XSS and SQL injection attacks.

_Migrated_: Since HTTPful is no longer maintained, we migrated to GuzzleHttp. In this case /vendor directory was added which contains GuzzleHttp dependencies.

_Added_: Settings field sanitization.

_Added_: Settings field validation. In this case we minimized possible error count during checkout, SpectroCoin won't appear in checkout until settings validation is passed.

_Added_: Admin notice in admin plugin settings for all fields validation.

_Added_: Escaping all output variables with appropriate functions.

_Added_: "spectrocoin\_" prefix to functiton names.

_Added_: "SpectroCoin\_" prefix to class names.

_Added_: Validation and Sanitization when request payload is created.

_Added_: Validation and Sanitization when callback is received.

_Added_: Components class "SpectroCoin_ValidationUtil" for specific validation functions.

_Added_: Logging to Wordpress log when errors occur.

_Added_: Logging to WooCommerce status log when errors occur.

_Fixed_: is_available() function sometimes returned false, even if all settings were correct.

_Optimised_: Removed the The whole $\_POST stack processing. Now only needed callback keys is being processed.

_Updated_: Removed hardcoded notice display from admin_options() function.

_Updated_: spectrocoin_admin_error_notice() function, added additional parameter to allow hyperlink display. Also the notice will be displayed once and won't be displayed in other admin screens except SpectroCoin settings.

### 1.3.0 MINOR (10/04/2023):

_Fixed_: Replaced hardcoded order statuses in plugin settings.

_Added_: Custom order statuses created manually or using plugins will appear in SpectroCoin settings menu.

_Added_: During checkout, if error is occured, now client will see the error code and message instead of generic error message.

_Added:_ Now plugin checks the FIAT currency, if it is not supported by SpectroCoin, payment will not be available.

_Added:_ Added admin notice in admin plugin settings to notify that shop currency is not supported by SpectroCoin.

### 1.2.0 MINOR (09/10/2023):

_Added_: Implemented plugin string internationalization, for plugin translation to various languages.

_Added_: Included two additional links within admin window connecting to official wordpress.org website to easily rate, leave feedback and report bugs.

_Tested_: Tested and checked compatibility with Wordpress 6.3 and WooCommerce 8.0.1

_Modified_: Added style changes in settings window

_For Developers_: Added documentation with parameters and return variables before every function

### 1.1.0 MINOR (07/31/2023):

_Added_: Included a new option in admin menu, to display or not the SpectroCoin logo during checkout.

### 1.0.0 MAJOR (07/31/2023):

_Added_: Included a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

_Updated:_ Implemented an "if" statement to handle compatibility with older PHP versions (PHP 8 and below) for the function openssl_free_key($public_key_pem). This change is necessary as PHP 8
deprecates openssl_free_key and now automatically destroys the key instance when it goes out of scope. (Source: https://stackoverflow.com/questions/69559775/php-openssl-free-key-deprecated)

_Improved:_ In the WC_Gateway_Spectrocoin class, made changes to prevent deprecated messages related to the creation of dynamic properties. The properties (merchant_id, protected_id, private_key, and order_status) are now explicitly declared as protected, and getter functions are added to ensure better encapsulation. This update is particularly important for PHP version 8.2 and above.

_Added:_ Specified a dependency on the WooCommerce plugin for the SpectroCoin plugin. The SpectroCoin plugin now requires WooCommerce to be installed and active on the site. If the user deletes or deactivates WooCommerce, a notice will be displayed, and the SpectroCoin plugin will be deactivated automatically.

_Added:_ Enhanced the style of the admin's payment settings window to match the design of SpectroCoin.com, providing a more cohesive user experience.

_Added:_ Introduced an informative message on the admin page, guiding users on how to obtain the mandatory credentials required for using the SpectroCoin plugin effectively. This addition helps users easily find the necessary information for setup and configuration.

## Contact

This client has been developed by SpectroCoin.com If you need any further support regarding our services you can contact us via:

E-mail: merchant@spectrocoin.com </br>
Skype: spectrocoin_merchant </br>
[Web](https://spectrocoin.com) </br>
[X (formerly Twitter)](https://twitter.com/spectrocoin) </br>
[Facebook](https://www.facebook.com/spectrocoin/)
