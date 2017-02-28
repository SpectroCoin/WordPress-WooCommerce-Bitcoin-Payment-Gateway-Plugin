SpectroCoin Bitcoin Merchant plugin for WordPress WooCommerce
---------------
This is [SpectroCoin Bitcoin Payment Module for WordPress](https://spectrocoin.com/en/plugins/accept-bitcoin-wordpress-woocommerce.html). This extenstion allows to easily accept bitcoins (and other cryptocurrencies such as DASH) at your WordPress or WooCommerce website. You can view [a how to integrate bitcoin payments for WordPress tutorial](https://www.youtube.com/watch?v=OTbLlI7sF8U).

To succesfully use this plugin, you have to have a SpectroCoin Bitcoin wallet. You can get it [here](https://spectrocoin.com/en/bitcoin-wallet.html). Also you have to create a merchant project to get Merchant and Project IDs, to do so create a new merchant project [here](https://spectrocoin.com/en/merchant/api/create.html).

**INSTALLATION**

1. Upload plugin directory to the `/wp-content/plugins/` directory
2. Generate private and public keys
	1. Automatically<br />
	
	Go to [SpectroCoin](https://spectrocoin.com/) -> [Project list](https://spectrocoin.com/en/merchant/api/list.html)
	click on your project, then select "Edit Project and then click "Generate" (next to Public key field), as a result you will get an automatically generated private key, download and save it. The matching Public key will be generated automatically and added to your project.
	
	2. Manually<br />
    	
	Private key:
    ```shell
    # generate a 2048-bit RSA private key
    openssl genrsa -out "C:\private" 2048
	
    ```
    <br />
    	Public key:
    ```shell
    # output public key portion in PEM format
    openssl rsa -in "C:\private" -pubout -outform PEM -out "C:\public"
    ```
	<br />

	Do not forget to add new Public key to your project by pasting it into Public key field under "Edit project" section. 
  
3. Save/change private key to wp-content\plugins\spectrocoin\keys as "private_key"

**CONFIGURATION**

1. Activate the plugin through the WooCommerce -> Settings -> Checkout -> SpectroCoin
2. Enter your Merchant ID and Project ID

**INFORMATION** 

This plugin has been developed by SpectroCoin.com
If you need any further support regarding our services you can contact us via:<br />
E-mail: [info@spectrocoin.com](mailto:info@spectrocoin.com)<br />
Phone: +442037697306<br />
Skype: [spectrocoin_merchant](skype:spectrocoin_merchant)<br />
Web: [https://spectrocoin.com](https://spectrocoin.com)<br />
Twitter: [@spectrocoin](https://twitter.com/spectrocoin)<br />
Facebook: [SpectroCoin](https://www.facebook.com/spectrocoin)<br />
