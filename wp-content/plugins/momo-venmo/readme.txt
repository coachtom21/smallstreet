=== Checkout with Venmo on Woocommerce ===
Contributors: theafricanboss
Donate Link: https://gurastores.com/get-cash
Tags: venmo, paypal, finance, payments, money, transfer, receive, send, money transfer, usa, mobile money, cash, momo, woocommerce
Stable tag: 4.2
Requires PHP: 5.0
Requires at least: 4.0
Tested up to: 6.3
WC requires at least: 5.0.0
WC tested up to: 8.0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The top finance app in the App Store now available on WordPress. Receive Venmo payments on your website with WooCommerce + Venmo

== Description ==

**Introducing the official Pay with Venmo by PayPal**
**Introducing automated order status updates for PRO users: on-hold to processing when you receive the order amount**

= More information =

For more details about this woocommerce extension, **please visit [The African Boss](https://theafricanboss.com/venmo)**
See available screenshots or the store example of [Gura Stores](https://gurastores.com/test/) for visual details.

= PRO or customized version =

Please reach out to theafricanboss@gmail.com for a custom version of this plugin.
Visit [The African Boss](https://theafricanboss.com/venmo) for more details

= Demo =

An example of the plugin in use is the following store:

[Gura Stores](https://gurastores.com/test/)

This plugin displays a Venmo link

See the screenshots or the store example of [Gura Stores](https://gurastores.com/test/) for visual details.

== Screenshots ==

1. Checkout page view for customers using the Venmo Link payment method enabled.
2. Checkout page view for customers using the Pay with Venmo payment by PayPal method enabled.
3. Plugin settings for the Venmo Link payment method and this information will be displayed to your customers
4. Plugin settings for the Pay with Venmo payment by PayPal method
5. Thank you page after placing the order via the Venmo Link payment method
6. This is where the Venmo link brings the customer with a prefilled order amount, id, and note

== Installation ==

= From Dashboard ( WordPress admin ) =

- Go to Plugins -> Add New
- Search for ‘Checkout with Venmo on Woocommerce’
- Click on Install Now
- Activate the plugin through the “Plugins” menu in WordPress.

= Using cPanel or FTP =

- Download ‘Checkout with Venmo on Woocommerce’ from [The African Boss](https://theafricanboss.com/venmo)
- Unzip momo-venmo.zip’ file and
- Upload momo-venmo folder to the “/wp-content/plugins/” directory.
- Activate the plugin through the “Plugins” menu in WordPress.

= After Plugin Activation =

Find and click Venmo in your admin dashboard left sidebar to access Venmo settings

**or**

Go to ‘Woocommerce > Settings > Payments’ screen to configure the plugin

Also _you can visit_ the [plugin page](https://theafricanboss.com/venmo) for further setup instructions.

== Frequently Asked Questions ==

= Venmo Link Vs. Pay with Venmo =

The major difference between the two payment methods is that Venmo Link is a link to your Venmo @username (e.g venmo.com/username) while Pay with Venmo is the official integration by PayPal that needs to be set up with your PayPal account.
Notice that Pay with Venmo requires a PayPal business developer account while Venmo Link requires a Venmo account.

Check out the full side-by-side comparison table at [The African Boss](https://paypal.theafricanboss.com)

= Does this Venmo plugin integrate with the payment APIs? =

Yes, Venmo integrates the official Pay with Venmo end-to-end payment by PayPal since v4.0.

Also there is an extra payment method added that provides a quick and easy way to display to your customers your Venmo @username and to link them to it.
It only displays your Venmo @username to the customer and redirects them to it so that the off site Venmo transaction can take place.

Please check screenshots for more details on what is reported.

= Do I need a Venmo account or a PayPal business account to use this plugin? =

Yes, you need either a Venmo account or a PayPal business account. This plugin comes with 2 payment methods for Venmo. One requires a PayPal business account and the other requires a Venmo account.

= What is emailreceipts.io and do I need to integrate it? =

emailreceipts.io is a service that allows you to track Venmo payments and update order statuses automatically.
The integration also helps send automated emails to your customers when their order status changes.
If you do not want to use emailreceipts.io, you will have to manually update the order status in your Woocommerce admin dashboard.

= Support =

**Premium Support**

Users with a valid Checkout with Venmo on Woocommerce PRO license receive Priority Support, directly from the plugin developer! [Find out more!](https://theafricanboss.com/venmo)

**Community Support for users of the Free version**

For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/momo-venmo). Please search through the forums first, and only [create a new topic](https://wordpress.org/support/plugin/momo-venmo#new-post) if you don't find an existing answer. Thank you!

= Languages and Localization =

Also compatible with Translation plugins (like Loco, WPML, etc) meaning you can translate the Checkout, Thank you page and Email notices

= SMS for Woocommerce compatibility =

Also using our SMS for Woocommerce plugin, you can send personalized bulk email and SMS notifications for orders still on-hold with order information and more

== Usage ==

After activating the plugin, add your Venmo information such as your Venmo, Venmo name, Venmo email, Venmo phone number in the plugin settings to start receiving payments instantly.

**Unlock more great features for you and your customers and priority support with a PRO license. [Upgrade](https://theafricanboss.com/venmo)**

== Upgrade Notice ==

= 4.2 =
This update is a security, stability, maintenance, and compatibility release. Updating is highly recommended.

== Changelog ==

- Using wp_remote for the PayPal customers/payments/refunds API
- Using PayPal Payments API & Refunds API & Customer API for Venmo Pay
- Now processing payments and refunds with the PayPal API
// - Improved WC_Venmo_Cashapp class methods, html, and error displays
- Added saving additional order meta_data (ppr_id,ppr_status,ppr_order_id)


= 4.2 Aug 15, 2023 =

- Refactored code across plugin for better performance
- Updated to using payment_url, qrcode_url, qrcode_html functions
- Updated WC_Venmo_Update_Order: verify signature, find order
- Updated WC_Venmo_Update_Order: update order: update_order, post_title, post_content, response_code
- Added saving additional order meta_data (pp_authorization_id,pp_order_id,pp_id,pp_status,pp_receipt,pp_endpoint,ppr_issue_refund,ppr_endpoint)
- Display default plugin settings and rearranged settings values
- Updated display_venmo,display_venmo_logo_button settings for the redesigned and enhanced checkout and thank you page designs
- Fixed PayPal merchant account locations_errors & better locations error handling
- Better emailreceipts.io onboarding with accountid,accountname,accountemail before connecting
- Removed no longer applicable settings
- Integrated debug logs and added them to validate_fields,process_payment,...
- Added the PayPal payment receipt and transactions URL on the order note
- Enable PayPal debug and woo logs
- Removed the NEW badge on older menu items
- Removed the cloudmailin integration
- Improved plugin settings and their descriptions
- Improved find_order function for emailreceipts.io
- Improved receipt post type post_content
- Introduced checkout designs in PRO
- Added do_not_checkout in case of missing plugin settings
- copy.js enqueued after 'jquery', 'wc_venmo_qrcode'
- Fixed thank you page CSS for design 1-2
- Updated copy.js and qrcode.js
- str_contains to str_pos for php compatibility
- wpautop & wptexturize the input
- echo , vs echo .
- Updated Freemius to v2.5.10
- Updated Woocommerce and Wordpress compatibility

= 4.1 Mar 15, 2023 =
- Refactored code across plugin for better performance
- Better Venmo Pay integration
- Better variables and functions
- Better enqueued scripts
- Better thank you page customizations
- Better checkout page customizations
- Better email notification
- Better order note
- Updated Freemius, Woocommerce and Wordpress compatibility

= 3.2-4.0 Nov 20 - Jan 15, 2023 =
- Integrated the offical PayPal Pay with Venmo Button as a second payment option
- Side-by-side comparison table of Venmo Link Vs. Pay with Venmo
- New checkout page design for PRO users
- Better Venmo automated order status updates processing
- Better process Venmo receipts HTTP response statuses for emailreceipts.io
- Enable/Disable order note
- Edit order note in PRO
- Pay with Venmo button styling options for PRO users
- More details on emailreceipts.io for onboarding free users
- When no order id is submitted, search through the 5 most recent Venmo orders on-hold and match by amount or customer username
- Better display for default plugin settings values
- Updated Freemius, Woocommerce and Wordpress compatibility

= 3.1 Sep 1, 2022 =
- Redesigned how information is displayed to the customer on the checkout and thank you pages
- Added capturing Venmo @username as order meta data
- Integrated emailreceipts.io to track Venmo receipts and update order statuses automatically
- Automated order status updates for PRO users
- Better Venmo automated processing
- When no order id is sent, check recent orders for a match of amount and payment method
- Reduce order stock inventory option
- Tutorial on how to update order statuses automatically for PRO users
- Bulk email and SMS notifications for orders still on-hold using SMS for Woocommerce plugin
- New logo with Venmo logo and QR code in one
- Moved to Freemius for automatic updates
- Updated Woocommerce and Wordpress compatibility

= 3.0 Mar 15, 2022 =
- SMS for Woocommerce compatible
- Internalization of the plugin checkout, thankyou and email
- Better Venmo URL encoding
- Updated help links
- Updated Woocommerce and Wordpress compatibility

= 2.2 December 5, 2021 =
- Updated from woocommerce_before_thankyou to woocommerce_thankyou_payment-method-id for compatibility with thank you page customizer plugins
- Moved menu order to below woocommerce menu - position 56
- Fixed error bug that disallowed upgrade/downgrade due to global constants structure in free MOMO<PAYMENT>PRO_ while in paid, MOMO<PAYMENT>_PRO_
- Fixed admin_url functions with issues
- Added ! $sent_to_admin / $sent_to_admin = false to email instructions
- Replaced woocommerce_email_before_order_table hook by woocommerce_email_order_details
- Updated Woocommerce and Wordpress compatibility

= 2.1.1 September 9, 2021 =
- Updated width and height attributes for logo-qr

= 2.1 September 7, 2021 =
- Added .logo-qr class that overwrites theme CSS for the button and QR code
- Changed the wording and removed "shipping and delivery" to include digital woocommerce sellers
- Removed version date

= 2.0.1 August 30, 2021 =
- Fixed order order_id occurences

= 2.0 August 27, 2021 =

- Remove @ at the beginning in venmo username
- Sharing payment methods with free versions to keep data across
- Fixed 'if functions for on-hold and check payment methods' placement
- Improved deactivate free plugins when PRO activated
Smooth upgrade from free to PRO
- PRO invitation admin notice when using free plugin
- Fixed bootstrap CSS enqueued on menu pages
- Added .momo-*** class to checkout CSS to apply custom CSS to payment icons and QR codes
Removed content from assets/css/checkout that was forcing 35px size on some themes
Added important height to force 100px in size of QR code and buttons on checkout and thank you page
Added setup plugin link to wp_die when upgrading from free to PRO plugin
- Better settings links on plugins page
- Removed review notice asking for reviews
- Better installation instructions
- renamed PRO versions to [payment_name PRO]
- Added free and paid recommended menus in sidebar with colors
- Fixed menu buttons in PRO plugin

= 1.2 August 1, 2021 =

- Added the Venmo note that defaults to 'checkout at your_site.com'
- Updated Venmo note occurences in email, checkout page, and thank you page
- Added test button to settings page to see what customers see when they click the button or run the QR code
- Updated checkout icon
- Added settings links to plugins page
- Added setup plugin link to wp_die when upgrading from free to PRO plugin
- Fixed menu buttons in PRO plugin
- Send email with payment info if order is on-hold
- Fixed bootstrap CSS enqueued on menu pages
- Removed content from assets/css/checkout that was forcing 35px size on some themes
- Added height, max-height, width, max-width to force 100px in size of QR code and buttons on checkout and thank you page
- Added .paym_link class to assets/css/checkout to remove any underline from themes on the QR code or button

= 1.1 June 15, 2021 =

- Added wp_die to deactivate plugin when the PRO version is active
- Emails will be sent with the note from now on only if the order is still on-hold
- Name change from 'MOMO Venmo' to 'Checkout with Venmo on Woocommerce'
- Updated links of assets in recommended and tutorials links

= 1.0 May 15, 2021 =

- Initial Release

<?php code();?>
