## Changelog

### 1.5.1 (09/05/2024)

Fixed dynamic string Internationalization.

Removed "Test" order, now when test mode enabled, returned callback status will "PAID" or "EXPIRED", depends which is chosen in merchant project settings.

Adjusted string from "test mode" to "hide from checkout" in plugin settings.

Fixed a bug related with the payment method not displaying in checkout due to "test mode".

### 1.5.0 (02/05/2024)

_Added_ Compatibility with the new block-based checkout functionality introduced in WooCommerce 8.3.

_Fixed_ Deprecated functions/methods/variables.

_Removed_ Empty instructions variable, if needed, it will be added in future versions.

_Fixed_ Compatibility with "High-Performance Order Storage" introduced in WooCommerce 8.2.

_Added_ Test mode checkbox. When enabled, if order callback is received, then test order will be set to selected order status (by default - "Completed"). Also SpectroCoin payment option will be visible only for admin user.

_Added_ Messages related with order processing to order notes.

_Fixed_ "Failed" status with failed and expired orders.

### 1.4.1 (01/26/2024)

_Removed_ Plugin dependency from plugin directory names

_Fixed_ Fatal error for new installations

### 1.4.0 (01/03/2024)

This update is significant to plugin's security and stability. The posibility of errors during checkout is minimized, reduced posibility of XSS and SQL injection attacks.

_Migrated_ to GuzzleHttp since HTTPful is no longer maintained. In this case /vendor directory was added which contains GuzzleHttp dependencies.

_Added_ Settings field sanitization.

_Added_ Settings field validation. In this case we minimized possible error count during checkout, SpectroCoin won't appear in checkout until settings validation is passed.

_Added_ Admin notice in admin plugin settings for all fields validation.

_Added_ Escaping all output variables with appropriate functions.

_Added_ "spectrocoin\_" prefix to functiton names.

_Added_ "SpectroCoin\_" prefix to class names.

_Added_ validation and sanitization when request payload is created.

_Added_ validation and sanitization when callback is received.

_Added_ components class "SpectroCoin_ValidationUtil" for specific validation functions.

_Added_ logging to Wordpress log when errors occur.

_Added_ logging to WooCommerce status log when errors occur.

_Fixed_ is_available() function behaviour, when sometimes it returned false, even if all settings were correct.

_Optimised_ the The whole $\_POST stack processing. Now only needed callback keys is being processed.

_Updated_ hardcoded notice display from admin_options() function.

_Updated_ spectrocoin_admin_error_notice() function, added additional parameter to allow hyperlink display. Also the notice will be displayed once and won't be displayed in other admin screens except SpectroCoin settings.

### 1.3.0 (10/04/2023)

_Fixed_ hardcoded order statuses in plugin settings.

_Added_ Custom order statuses created manually or using plugins will appear in SpectroCoin settings menu.

_Added_ a new function, when during checkout, if error is occured, now client will see the error code and message instead of generic error message.

_Added_ plugin checks the FIAT currency, if it is not supported by SpectroCoin, payment will not be available.

_Added_ admin notice in admin plugin settings to notify that shop currency is not supported by SpectroCoin.

### 1.2.0 (09/10/2023)

_Added_ plugin string internationalization, for plugin translation to various languages.

_Added_ two additional links within admin window connecting to official wordpress.org website to easily rate, leave feedback and report bugs.

_Updated_ style changes in settings window

_For Developers_ Added documentation with parameters and return variables before every function

### 1.1.0 (07/31/2023)

_Added_ a new option in admin menu, to display or not the SpectroCoin logo during checkout.

### 1.0.0 (07/31/2023)

_Added_ a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

_Implemented_ an "if" statement to handle compatibility with older PHP versions (PHP 8 and below) for the function openssl_free_key($public_key_pem). This change is necessary as PHP 8
deprecates openssl_free_key and now automatically destroys the key instance when it goes out of scope. (Source https//stackoverflow.com/questions/69559775/php-openssl-free-key-deprecated)

_Improved_ the WC_Gateway_Spectrocoin class, made changes to prevent deprecated messages related to the creation of dynamic properties. The properties (merchant_id, protected_id, private_key, and order_status) are now explicitly declared as protected, and getter functions are added to ensure better encapsulation. This update is particularly important for PHP version 8.2 and above.

_Added_ a dependency on the WooCommerce plugin for the SpectroCoin plugin. The SpectroCoin plugin now requires WooCommerce to be installed and active on the site. If the user deletes or deactivates WooCommerce, a notice will be displayed, and the SpectroCoin plugin will be deactivated automatically.

_Enhanced_ the style of the admin's payment settings window to match the design of SpectroCoin.com, providing a more cohesive user experience.

_Added_ an informative message on the admin page, guiding users on how to obtain the mandatory credentials required for using the SpectroCoin plugin effectively. This addition helps users easily find the necessary information for setup and configuration.
