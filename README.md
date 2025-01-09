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

We gently suggest trying out the plugin in a server environment, as it will not be capable of receiving callbacks from SpectroCoin if it will be hosted on localhost. To successfully create an order on localhost for testing purposes, <b>change these 3 lines in <em>CreateOrderRequest.php</em></b>:

`$this->callbackUrl = isset($data['callbackUrl']) ? Utils::sanitizeUrl($data['callbackUrl']) : null;`, <br>
`$this->successUrl = isset($data['successUrl']) ? Utils::sanitizeUrl($data['successUrl']) : null;`, <br>
`$this->failureUrl = isset($data['failureUrl']) ? Utils::sanitizeUrl($data['failureUrl']) : null;`

<b>To</b>

`$this->callbackUrl = "https://localhost.com/";`, <br>
`$this->successUrl = "https://localhost.com/";`, <br>
`$this->failureUrl = "https://localhost.com/";`

Don't forget to change it back when migrating website to public.

## Debugging

If you get "Something went wrong. Please contact us to get assistance." message during checkout process, please navigate to **"WooCommerce"** -> **"Status"** -> **"Logs"** and check **"plugin-spectrocoin"** log file for more information. If the logs are not helpful or do not display, please contact us and provide the log file details so that we can assist.

## Contact

This client has been developed by SpectroCoin.com If you need any further support regarding our services you can contact us via:

E-mail: merchant@spectrocoin.com </br>
Skype: spectrocoin_merchant </br>
[Web](https://spectrocoin.com) </br>
[X (formerly Twitter)](https://twitter.com/spectrocoin) </br>
[Facebook](https://www.facebook.com/spectrocoin/)
