=== Secapay Gateway for WooCommerce ===
Contributors: sci
Tags: ecommerce, payment gateway, wordpress, woocommerce, secapay, sci.ng
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 0.0.1
License: 

Secapay Gateway for accepting payments on your WooCommerce Store.


== Description ==

Secapay Form integration is the easiest way to start processing online payments. It can take as little as 20 minutes to set up and is by far the quickest way to integrate Secapay.

Form integration is designed to pass transaction details from your website to Secapay to carry out a transaction and redirect users back to your site. Outsourcing your payment processing in this way means that no sensitive data is collected, stored or transferred from your site.

This Plugin allows you to accept Secapay Payments removing the need for you to maintain highly secure encrypted databases, obtain digital certificates and invest in high-level PCI DSS compliance.

== Installation ==

1. Download the latest secapay plugin release(leave it as a zip file).
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Download and install woocommerce plugin and activate it same way as the secapay plugin.
6. Register with Secapay.com and create a button to receive payment on your site.
7. You can create a button through your dashboard. If you are trying to
receive money for your business, you’re advised to create a ‘Business button’.
8. Click the code symbol -- ‘<>’ for the button you just generated. You’ll
see something similar to: https://demo.secapay.com/pay?button=1&amount=50. The button id for this button is 1.
9. Copy the button id of the button you just created.



== Configuration ==

1. Go to WooCommerce settings->checkout->Secapay Form. 
2. In Secapay Form, enable Secapay. 
3. Paste the button ID gotten from secapay.com into the button ID field.
4. Save Changes and your plugin is ready for use.



== Changelog ==

= 0.0.1 =
* Initial Release