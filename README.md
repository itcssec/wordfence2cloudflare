# Wordfence to Cloudflare
Description: This plugin enhances the security of your WordPress website by seamlessly integrating Wordfence and Cloudflare. It automatically synchronizes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list, providing an additional layer of protection against malicious traffic.

# Features:

Automatic IP Synchronization: The plugin periodically checks for new blocked IPs in Wordfence and automatically adds them to Cloudflare's blocked IP list. This ensures that known malicious IPs are effectively blocked at the Cloudflare level, reducing the burden on your server.

Customizable Settings: The plugin allows you to configure various settings, including Cloudflare credentials (email and zone ID), blocked hits threshold, block scope (domain specific or entire account), and cron interval. These settings can be adjusted according to your specific security requirements.

Manual Process Trigger: You can manually trigger the synchronization process with a single click, giving you control over when the synchronization occurs. This can be helpful in urgent situations or when you want to ensure immediate synchronization of blocked IPs.

# Security Benefits:

Enhanced IP Blocking: By combining the powerful IP blocking capabilities of Wordfence and Cloudflare, this plugin strengthens your website's defense against malicious traffic. It ensures that blocked IPs identified by Wordfence are effectively blocked at the Cloudflare level, preventing them from reaching your server.

Secure Cloudflare Key Storage: The plugin securely stores your Cloudflare key using WordPress's built-in options table. The key is encrypted and can only be accessed by authorized processes, providing an additional layer of protection for your key.

Reduced Server Load: Offloading the blocking of malicious IPs to Cloudflare reduces the load on your server, improving its performance and responsiveness. This is particularly beneficial during DDoS attacks or when dealing with a large number of blocked IPs.

Customizable Security Settings: The plugin offers flexible settings that allow you to tailor the security measures to your specific needs. You can adjust the blocked hits threshold and choose between domain-specific or account-wide blocking, providing granular control over the IP blocking process.

Seamless Integration: The plugin seamlessly integrates with your existing Wordfence and Cloudflare configurations. It leverages the APIs provided by both services, ensuring smooth and reliable synchronization of blocked IPs without any manual intervention.

By utilizing the combined power of Wordfence and Cloudflare, this plugin helps safeguard your WordPress website from malicious IPs more effectively. It automates the synchronization process, reduces server load, and provides customizable security settings, all while ensuring the secure storage of your Cloudflare key. With Wordfence to Cloudflare, you can enhance the security posture of your website and protect it from a wide range of security threats.

# Installation:

Upload the wordfence-to-cloudflare directory to your /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
Activate the plugin through the 'Plugins' screen in WordPress.
Use the Settings -> Wordfence to Cloudflare screen to configure the plugin.

# Frequently Asked Questions:

Can I manually trigger the IP synchronization process?
Yes, the plugin provides a button to manually trigger the synchronization process. This can be helpful in urgent situations or when you want to ensure immediate synchronization of blocked IPs.

Where is my Cloudflare key stored?
Your Cloudflare key is securely stored in the WordPress's built-in options table. It is encrypted and can only be accessed by authorized processes.

Can I customize the security settings of the plugin?
Yes, the plugin offers flexible settings that allow you to adjust the blocked hits threshold, choose between domain-specific or account-wide blocking, set the cron interval, and enter your Cloudflare credentials.

How does the plugin reduce the server load?
By offloading the task of blocking malicious IPs to Cloudflare, the plugin reduces the load on your server. This can improve its performance and responsiveness, particularly during DDoS attacks or when dealing with a large number of blocked IPs.

What are the requirements to use this plugin?
This plugin requires an active Wordfence and Cloudflare account. You also need to have access to your Cloudflare API key and Zone ID to configure the plugin.