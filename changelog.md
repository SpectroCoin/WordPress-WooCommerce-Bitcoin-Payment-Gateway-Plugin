## Version 1.0.0 MAJOR (07/31/2023):

_Fixed:_ Corrected a typo in the plugin's description. Changed "aplugin" to "a plugin" for better clarity.

_Added_: Included a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

_Updated:_ Implemented an "if" statement to handle compatibility with older PHP versions (PHP 8 and below) for the function openssl_free_key($public_key_pem). This change is necessary as PHP 8
deprecates openssl_free_key and now automatically destroys the key instance when it goes out of scope. (Source: https://stackoverflow.com/questions/69559775/php-openssl-free-key-deprecated)

_Improved:_ In the WC_Gateway_Spectrocoin class, made changes to prevent deprecated messages related to the creation of dynamic properties. The properties (merchant_id, protected_id, private_key, and order_status) are now explicitly declared as protected, and getter functions are added to ensure better encapsulation. This update is particularly important for PHP version 8.2 and above.

_Added:_ Specified a dependency on the WooCommerce plugin for the SpectroCoin plugin. The SpectroCoin plugin now requires WooCommerce to be installed and active on the site. If the user deletes or deactivates WooCommerce, a notice will be displayed, and the SpectroCoin plugin will be deactivated automatically.

_Added:_ Enhanced the style of the admin's payment settings window to match the design of SpectroCoin.com, providing a more cohesive user experience.

_Added:_ Introduced an informative message on the admin page, guiding users on how to obtain the mandatory credentials required for using the SpectroCoin plugin effectively. This addition helps users easily find the necessary information for setup and configuration.

## Version 1.1.0 MINOR (07/31/2023):

_Added_: Included a link to access SpectroCoin plugin settings directly from the plugin page. This enhancement provides users with easier access to the configuration options.

## Version 1.2.0 MINOR (07/31/2023):

_Added_: Implemented plugin string internationalization, for plugin translation to various languages.

_Added_: Included two additional links within admin window connecting to official wordpress.org website to easily rate, leave feedback and report bugs.
