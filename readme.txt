=== Wordfence2Cloudflare ===
Contributors: ITCS
Tags: Wordfence, Cloudflare, Security, Wordpress Security, Firewall
Requires at least: 5.2
Requires PHP: 7.4
Tested up to: 6.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin takes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list.

== Description ==

This plugin enhances the security of your WordPress website by seamlessly integrating Wordfence and Cloudflare. It automatically synchronizes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list, providing an additional layer of protection against malicious traffic.

Features:

Automatic IP Synchronization: The plugin periodically checks for new blocked IPs in Wordfence and automatically adds them to Cloudflare's blocked IP list. This ensures that known malicious IPs are effectively blocked at the Cloudflare level, reducing the burden on your server.

Customizable Settings: The plugin allows you to configure various settings, including Cloudflare credentials (email and zone ID), blocked hits threshold, block scope (domain specific or entire account), and cron interval. These settings can be adjusted according to your specific security requirements.

Manual Process Trigger: You can manually trigger the synchronization process with a single click, giving you control over when the synchronization occurs. This can be helpful in urgent situations or when you want to ensure immediate synchronization of blocked IPs.

Security Benefits:

Enhanced IP Blocking: By combining the powerful IP blocking capabilities of Wordfence and Cloudflare, this plugin strengthens your website's defense against malicious traffic. It ensures that blocked IPs identified by Wordfence are effectively blocked at the Cloudflare level, preventing them from reaching your server.

Secure Cloudflare Key Storage: The plugin securely stores your Cloudflare key using WordPress's built-in options table. The key is encrypted and can only be accessed by authorized processes, providing an additional layer of protection for your key.

Reduced Server Load: Offloading the blocking of malicious IPs to Cloudflare reduces the load on your server, improving its performance and responsiveness. This is particularly beneficial during DDoS attacks or when dealing with a large number of blocked IPs.

Customizable Security Settings: The plugin offers flexible settings that allow you to tailor the security measures to your specific needs. You can adjust the blocked hits threshold and choose between domain-specific or account-wide blocking, providing granular control over the IP blocking process.

Seamless Integration: The plugin seamlessly integrates with your existing Wordfence and Cloudflare configurations. It leverages the APIs provided by both services, ensuring smooth and reliable synchronization of blocked IPs without any manual intervention.

By utilizing the combined power of Wordfence and Cloudflare, this plugin helps safeguard your WordPress website from malicious IPs more effectively. It automates the synchronization process, reduces server load, and provides customizable security settings, all while ensuring the secure storage of your Cloudflare key. With Wordfence to Cloudflare, you can enhance the security posture of your website and protect it from a wide range of security threats.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/customize-product-delivery-date` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WTC Settings screen to configure the plugin

== Frequently Asked Questions ==

= Does this plugin require any other plugins? =

Yes, this plugin requires the Wordfence security plugin and an active account in Cloudflare.

= Does this plugin support any other languages? =

No, this plugin does not support any other languages.

== Screenshots ==

1. The plugin main settings page
2. The email settings page
3. The orders with delivery dates table
4. The delivery date column in the woocommerce products page
5. The min and max settings in the woocommerce products bulk actions
6. The min and max settings in the products page
7. The order delivery datepicker for products in the checkout page
8. The order delivery datepicker for orders in the checkout page

== Upgrade Notice ==

= 1.0.1 =
1. Added sorting functionality for the "Orders with Delivery Dates" table.
2. Added notice for past orders too in the "Orders with Delivery Dates" table
3. Fixed an issue with the client name in the "Orders with Delivery Dates" table

= 1.1.0 =
1. Introducing the pro version
2. Added an extra option to show the orders' available dates to the checkout page
3. Added support for a delivery date by product (pro feature) 
4. Manage products delivery dates massively from the woocommerce product page (pro feature) 
5. Split orders functionality. The orders can be split into separate orders based on the delivery date (pro feature) 
6. Email notification to customers when their orders are split. (pro feature)
7. The email notification message can be modified in the plugin's respective admin settings (pro feature)

= 1.1.1 = 
1. Redesigned the Orders with Delivery Dates table with new features. 
2. Added an export to excel functionality for the Orders with Delivery Dates. 
3. Added the ability to dynamically change the delivery date of an order or a product from the table. 
4. New sorting, filtering, and dynamic search functionality. 
5. Added an option to make the delivery date required on the checkout page.
5. Redesigned the Order Delivery Date Settings page.   

== Changelog ==

= 1.0.0 =
* Initial release.

= Version 1.0.1 =
1. Added sorting functionality for the "Orders with Delivery Dates" table.
2. Added notice for past orders too in the "Orders with Delivery Dates" table
3. Fixed an issue with the client name in the "Orders with Delivery Dates" table

= Version 1.1.0 =
1. Introducing the pro version
2. Added an extra option to show the orders' available dates to the checkout page
3. Added support for a delivery date by product (pro feature) 
4. Manage products delivery dates massively from the woocommerce product page (pro feature) 
5. Split orders functionality. The orders can be split into separate orders based on the delivery date (pro feature) 
6. Email notification to customers when their orders are split. (pro feature)
7. The email notification message can be modified in the plugin's respective admin settings (pro feature)

= Version 1.1.1 = 
1. Redesigned the Orders with Delivery Dates table with new features. 
2. Added an export to excel functionality for the Orders with Delivery Dates. 
3. Added the ability to dynamically change the delivery date of an order or a product from the table. 
4. New sorting, filtering, and dynamic search functionality. 
5. Added an option to make the delivery date required on the checkout page.
5. Redesigned the Order Delivery Date Settings page.   


= Version 1.1.5 = 
This is an important security fix

== Roadmap ==

The following list outlines the upcoming features for our plugin:

1. Improved user experience with a new, intuitive user interface
2. Addition of new features, such as a time slots for deliveries
3. Order management system, through which the admin will be able to set a maximum number of deliveries per time slot

We welcome feedback on our roadmap and would love to hear your ideas and suggestions. Please let us know how we can improve our plugin to better suit your needs.

Donate to support Product Delivery Date

If you would like to support the development of Product Delivery Date, please consider making a donation. Your support will help us to continue to improve the plugin and make it better for everyone.