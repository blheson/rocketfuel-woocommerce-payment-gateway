# RocketFuel - RocketFuel Payment Method for Woocommerce
RocketFuel Payment Method 2.0.3 for Woocommerce
Requires at least: 5.5
Tested up to: 5.9
Stable tag: 3.1.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

# Install


* Go to your shop admin panel.
* Go to "Plugins" -> "Add Plugins".
* Click on "Upload Plugin" button and browse and select plugin zip file.
* After installation activate plugin.
* Enter "Merchant ID" (provided in RocketFuel merchant UI for registered merchants) in the Woocommerce payment tab.
* Enter "Public Key" (provided in RocketFuel).
* Copy a RocketFuel callback URL and save settings
* Go to your RocketFuel merchant account
* Click "Edit" in the bottom left corner. A window will pop up.
* Paste callback URL and click "Save".

# Changelog

2.0.1 Added overlay on checkout page.
2.0.2 Allow admin to set order status for payment confirmation
      Allow users to trigger iframe after closing
      Fixed iframe trigger button style. 
      Added return to checkout button on iframe trigger modal
      Fixed pending and on hold order issue
2.0.3 Changed title in readme
2.0.4 Fixed woocommerce thankyou page overlay styling for consistent display across theme.
2.0.5 Add transaction id to orders
	  Thankyou page overlay allows users to see order summary
2.1.5 Added Single Sign on
2.1.6 Added Test Environments
2.1.6.1 Fixed double first name issue
2.1.6.2 Remove filler for lastname
2.1.6.3 Sync rkfl sdk
2.1.6.4 Added Multiple Currency support
2.1.6.5 Add Shipping to line item
2.1.6.6 Sync rkfl and add sandbox
3.0.0 Move Iframe to Checkout page
      Support for Woocommerce Subscription Plugin - Payment Method Autorenewal.
      Support for payment autorenewal for subscription orders.
3.1.0.2 Revert new changes to overlay flow
3.1.0.2 Zero shipping removed
3.2.0 Add Subscription support
