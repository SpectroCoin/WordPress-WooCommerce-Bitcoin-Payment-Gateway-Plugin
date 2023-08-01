## SpectroCoin Bitcoin Merchant plugin for WordPress WooCommerce

This is <a href = "https://spectrocoin.com/en/plugins/accept-bitcoin-wordpress-woocommerce.html" target ="_blank">SpectroCoin Bitcoin Payment Module for WordPress</a>. This extenstion allows to easily accept bitcoins (and other cryptocurrencies such as DASH) at your WordPress or WooCommerce website. You can view <a href = "https://www.youtube.com/watch?v=OTbLlI7sF8U" target ="_blank">a how to integrate bitcoin payments for WordPress tutorial</a>.
To succesfully use this plugin, you have to have a SpectroCoin Bitcoin wallet. You can get it <a href = "https://spectrocoin.com/en/bitcoin-wallet.html" target ="_blank"></a>. Also you have to create a merchant project to get Merchant and Project IDs, to do so create a new merchant project <a href = "https://spectrocoin.com/en/merchant/api/create.html" target ="_blank">here</a>.

**INSTALLATION**

1. Upload plugin directory to the `/wp-content/plugins/spectrocoin` directory
   <br />
   OR
   <br />
   Go to Plugins -> Add New -> search for "spectrocoin" OR click Upload Plugin and upload .zip file.
   <br />
   OR
   <br />
   Navigate to WordPress directory and run:
   <br />
   `composer require spectrocoin/woocommerce-merchant`
   <br />
2. Generate private and public keys

   1. Automatically<br />

   Go to <a href = "https://spectrocoin.com/" target ="_blank">SpectroCoin</a> -> <a href = "https://spectrocoin.com/en/merchant/api/list.html" target ="_blank">Project list</a>
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

0. The plugin will only work with WooCommerce installed and activated.
1. Activate the plugin through the Plugins -> Installed Plugins -> Activate(SpectroCoin Bitcoin Payment Gateway)
2. Click on Settings OR go to WooCommerce -> Settings -> Payments -> SpectroCoin.
3. Enter your <b>Merchant ID</b>, <b>Project ID</b>, <b>Private key</b>.

**HOW TO GET CREDENTIALS**

1. <a href="https://auth.spectrocoin.com/signup" target="_blank">Sign up</a> for a Spectroin Account.
2. <a href="https://auth.spectrocoin.com/login" target="_blank">Log in</a> to your Spectroin account.
3. On the dashboard, locate the <b>"<a href = "https://spectrocoin.com/en/merchants/projects" target="_blank">Business<a></a>"</b> tab and click on it.
4. Click on <b>"<a href = "https://spectrocoin.com/en/merchants/projects/new" target="_blank">New project</a>."</b>
5. Fill in the project details and select desired settings (settings can be changed).
6. The <b>Private Key</b> can be obtained by switching on the Public key radio button (Private key won't be visible in the settings window, and it will have to be regenerated in settings). Copy or download the newly generated private key.
7. Click <b>"Submit"</b>.
8. Copy and paste the Merchant ID and Project ID.
9. Generate a test product. Create a test page on your WordPress website with a payment form connected to the Spectroin payment gateway. Perform a trial transaction using the test payment gateway (Test mode can be activated in project settings) to validate the integration's functionality. Verify the transaction details on the Spectroin dashboard to ensure it was successfully processed.

**INFORMATION**

This plugin has been developed by SpectroCoin.com
If you need any further support regarding our services you can contact us via:<br />
E-mail: [info@spectrocoin.com](mailto:info@spectrocoin.com)<br />
Phone: <a href="tel:+3726838000">+372 683 8000</a><br />
Skype: [spectrocoin_merchant](skype:spectrocoin_merchant)<br />
Web: <a href = "https://spectrocoin.com" target ="_blank">https://spectrocoin.com</a><br />
Twitter: <a href = "https://twitter.com/spectrocoin" target ="_blank">@spectrocoin</a><br />
Facebook: <a href = "https://www.facebook.com/spectrocoin" target ="_blank">SpectroCoin</a><br />
