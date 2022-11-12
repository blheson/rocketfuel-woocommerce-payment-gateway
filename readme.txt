=== RocketFuel - RocketFuel Payment Method for Woocommerce ===
Contributors: rockectfuel
Requires at least: 5.8
Tested up to: 6.0
Requires PHP: 7.2
Stable tag: 3.2.1.15
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
Accept Crypto payment anywhere

== Install ==

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
* If your website theme does not follow standard woocommerce structure, there's possibility that the plug-in will not work as expected

== Screenshots ==

1. banner-1544x500.png	
2. icon-128x128.png

== Frequently Asked Questions ==

= Where can I find Rocketfuel documentation and user guides? =
For help setting up and configuring Rocketfuel, please refer to [Getting Started](https://docs.rocketfuelblockchain.com).

== Changelog ==
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
3.2.1 Add support for custom place order button text
3.2.1.3 Support for new wordpress version
3.2.1.6 BUG FIX: Fix hidden place order button
3.2.1.7 BUG FIX: SSO
3.2.1.8 Fix same name in shipping address
3.2.1.9 Fix place order button conflict with other payment method
3.2.1.10 FIX: JS script not loading 
3.2.1.11 FIX: Iframe drag 