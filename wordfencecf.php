<?php
/*
Plugin Name: Wordfence2Cloudflare
Description: This plugin takes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list.
Version: 1.1
Author: ITCS
Author URI: https://itcybersecurity.gr/
License: GPLv2 or later
Text Domain: wordfence2cloudflare
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
function wtc_menu() {
    add_options_page(
        'WTC Settings', 
        'WTC Settings', 
        'manage_options', 
        'wtc-settings', 
        'wtc_render_admin_page'
    );
}
add_action('admin_menu', 'wtc_menu');

// Create the admin page
// Create the admin page
function wtc_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Wordfence to Cloudflare</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wtc-settings-group');
            do_settings_sections( 'wtc-settings' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Cloudflare Email</th>
                    <td><input type="text" name="cloudflare_email" value="<?php echo esc_attr( get_option('cloudflare_email') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cloudflare Key</th>
                    <td><input type="password" name="cloudflare_key" value="<?php echo esc_attr( get_option('cloudflare_key') ); ?>" /></td>
                </tr>
                 <tr valign="top">
                    <th scope="row">Cloudflare ZoneID</th>
                    <td><input type="text" min="1" name="cloudflare_zone_id" value="<?php echo esc_attr( get_option('cloudflare_zone_id') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Blocked Hits Threshold</th>
                    <td><input type="number" min="1" name="blocked_hits_threshold" value="<?php echo esc_attr( get_option('blocked_hits_threshold', 1) ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Block Scope</th>
                    <td>
                        <select name="block_scope">
                            <option value="domain" <?php selected('domain', get_option('block_scope')); ?>>Domain Specific</option>
                            <option value="account" <?php selected('account', get_option('block_scope')); ?>>Entire Account</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cron Interval</th>
                    <td>
                        <select name="cron_interval">
                            <option value="5min" <?php selected( get_option('cron_interval'), '5min' ); ?>>Every 5 Minutes</option>
                            <option value="hourly" <?php selected( get_option('cron_interval'), 'hourly' ); ?>>1 hour</option>
                            <option value="twicedaily" <?php selected( get_option('cron_interval'), 'twicedaily' ); ?>>12 hours</option>
                            <option value="daily" <?php selected( get_option('cron_interval'), 'daily' ); ?>>24 hours</option>
                        </select>
                    </td>
                </tr>
            </table>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Last Cron Run:</th>
                    <td><?php echo date('Y-m-d H:i:s', get_option('wtc_last_processed_time')); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row">IPs Processed:</th>
                    <td><?php echo get_option('wtc_processed_ips_count'); ?></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=wtc_run_process')); ?>">
            <?php wp_nonce_field('wtc_run_process_action', 'wtc_run_process_nonce'); ?>
            <button type="submit" name="wtc_run_process" class="button button-primary">Run Process</button>
        </form>
        <br></br>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wtc_clear_data_action', 'wtc_clear_data_nonce'); ?>
            <input type="hidden" name="action" value="wtc_clear_data">
            <button type="submit" name="wtc_clear_data" class="button button-secondary">Clear Data</button>
        </form>
    </div>
    <?php
}


function wtc_clear_data() {
    if (isset($_POST['wtc_clear_data']) && check_admin_referer('wtc_clear_data_action', 'wtc_clear_data_nonce')) {
        delete_option('wtc_last_processed_time');
        delete_option('wtc_processed_ips_count');
        wp_redirect(admin_url('admin.php?page=wtc-settings'));
        exit;
    }
}
add_action('admin_post_wtc_clear_data', 'wtc_clear_data');





// Initialize settings only on our options page
// Initialize settings
function wtc_options_init() {
    // Register settings
    register_setting( 'wtc-settings-group', 'cloudflare_email' ); 
    register_setting( 'wtc-settings-group', 'cloudflare_key' );  
    register_setting( 'wtc-settings-group', 'cloudflare_zone_id' );  
    register_setting('wtc-settings-group', 'blocked_hits_threshold');  
    register_setting('wtc-settings-group', 'block_scope');
    register_setting( 'wtc-settings-group', 'cron_interval' );
}
add_action('admin_init', 'wtc_options_init');

function wtc_handle_option_update() {
    $current_screen = get_current_screen();

    if ($current_screen->id !== "toplevel_page_wtc-settings") {
        return;
    }

    // If the options are set, update the wp-config.php file
    if ( get_option('cloudflare_email') && get_option('cloudflare_key') ) {
        wtc_update_wp_config( get_option('cloudflare_email'), get_option('cloudflare_key') );
    }
}

add_action('admin_enqueue_scripts', 'wtc_handle_option_update');

// Update wp-config.php on settings update
function update_wp_config_on_save( $option_name ) {
    // If the updated options are 'cloudflare_email' or 'cloudflare_key', update the wp-config.php file
    if ( 'cloudflare_email' == $option_name || 'cloudflare_key' == $option_name ) {
        if ( get_option('cloudflare_email') && get_option('cloudflare_key') ) {
            wtc_update_wp_config( get_option('cloudflare_email'), get_option('cloudflare_key') );
        }
    }

    // If the updated option is 'cron_interval', reschedule the cron event
    if ( 'cron_interval' == $option_name ) {
        // Unschedule the previous cron event if it exists
        wp_clear_scheduled_hook('wtc_check_new_blocked_ips');

        // Schedule the cron job with the new interval
        $cron_interval = get_option('cron_interval');
        if (!empty($cron_interval)) {
            wp_schedule_event(time(), $cron_interval, 'wtc_check_new_blocked_ips');
        }
    }
}
add_action( 'updated_option', 'update_wp_config_on_save' );





// The function that will run when the plugin is activated
function wtc_activate() {
	
	// Unschedule the previous cron event if it exists
    wp_clear_scheduled_hook('wtc_check_new_blocked_ips');

    // Schedule the cron job with the new interval
    $cron_interval = get_option('cron_interval');
    if (!empty($cron_interval)) {
        wp_schedule_event(time(), $cron_interval, 'wtc_check_new_blocked_ips');
    }
	
	// Schedule the function to run every 5 minutes
	//if (!wp_next_scheduled('wtc_check_new_blocked_ips')) {
	//	wp_schedule_event(time(), '5min', 'wtc_check_new_blocked_ips');
	//}
}

// Hook into the activation of the plugin
register_activation_hook(__FILE__, 'wtc_activate');

// Hook into the 'wtc_add_ips_to_cloudflare' action that'll fire according to the cron schedule
//add_action('wtc_add_ips_to_cloudflare', 'add_ips_to_cloudflare');

add_action('wtc_check_new_blocked_ips', 'wtc_check_new_blocked_ips');

// Create a function to check new blocked IPs every 5 minutes
function wtc_check_new_blocked_ips() {
    global $wpdb;
        $threshold = get_option('blocked_hits_threshold', 1);
        $last_processed_time = get_option('wtc_last_processed_time', 0); // Default to 0 if not set

        // Convert last processed time to the same format as the blockedTime column
        $last_processed_time_formatted = date('Y-m-d H:i:s', $last_processed_time);

        $blocked_ips = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wfblocks7 WHERE blockedTime > UNIX_TIMESTAMP('{$last_processed_time_formatted}') AND blockedHits >= {$threshold}",
            OBJECT
        );

        error_log("SQL Query: " . $wpdb->last_query);

        // Run the process
        $processed_ips_count = count($blocked_ips);
        if($blocked_ips) {
            error_log("Blocked IPs: " . print_r($blocked_ips, true)); // Debug statement
            add_ips_to_cloudflare( $blocked_ips ); // Pass $blocked_ips as an argument
            update_option('wtc_last_processed_time', time());
            update_option('wtc_processed_ips_count', $processed_ips_count);
        }else{
            error_log("No New Blocked IPs Found");
        }

}


// Add the blocked IPs to Cloudflare
function add_ips_to_cloudflare($blocked_ips) {
    $email = get_option('cloudflare_email');
    $key = get_option('cloudflare_key');
    $block_scope = get_option('block_scope', 'domain'); // Default to 'domain' if not set
    $zone_id = get_option('cloudflare_zone_id');
	
	$timezone = get_option('timezone_string'); // Get the WordPress timezone string
	 if (empty($timezone)) {
			$timezone = 'UTC'; // Set a default timezone if the retrieved value is empty
		}

		try {
			$current_datetime = new DateTime('now', new DateTimeZone($timezone));
		} catch (Exception $e) {
			error_log('Failed to create DateTime object: ' . $e->getMessage());
			return;
		}

    foreach($blocked_ips as $ip) {
        $ip_address = inet_ntop($ip->IP);
        if(filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
            error_log('Invalid IP address: ' . $ip_address);
            continue;
        }

        if($block_scope == 'domain') {
            // Domain Specific
            $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/firewall/access_rules/rules";
        } else {
            // Entire Account
            $api_url = "https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules";
        }

        $response = wp_remote_post($api_url, [
            'headers' => [
                'X-Auth-Email' => $email,
                'X-Auth-Key' => $key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'mode' => 'block',
                'configuration' => [
                    'target' => 'ip', // This should be 'ip' for both domain specific and entire account.
                    'value' => $ip_address
                ],
                'notes' => 'Blocked by WordfenceCloudflare plugin' . " " . $current_datetime->format('Y-m-d H:i:s') // Include the current date and time in the notes
            ])
        ]);

        if(is_wp_error($response)) {
            error_log('Failed to create access rule: ' . $response->get_error_message());
            continue;
        }

        $body = json_decode($response['body'], true);
        if(!isset($body['success']) || !$body['success']) {
            error_log('Failed to create access rule: ' . print_r($body, true));
            continue;
        }
    }
}


//add_action('wtc_add_ips_to_cloudflare', 'add_ips_to_cloudflare');


function wtc_run_process_manually() {
    if ( isset( $_POST['wtc_run_process'] ) ) {
        global $wpdb;
        $threshold = get_option('blocked_hits_threshold', 1);
        $last_processed_time = get_option('wtc_last_processed_time', 0); // Default to 0 if not set

        // Convert last processed time to the same format as the blockedTime column
        $last_processed_time_formatted = date('Y-m-d H:i:s', $last_processed_time);

        $blocked_ips = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wfblocks7 WHERE blockedTime > UNIX_TIMESTAMP('{$last_processed_time_formatted}') AND blockedHits >= {$threshold}",
            OBJECT
        );

        error_log("SQL Query: " . $wpdb->last_query);

        // Run the process
        $processed_ips_count = count($blocked_ips);
        if($blocked_ips) {
            error_log("Blocked IPs: " . print_r($blocked_ips, true)); // Debug statement
            add_ips_to_cloudflare( $blocked_ips ); // Pass $blocked_ips as an argument
            update_option('wtc_last_processed_time', time());
            update_option('wtc_processed_ips_count', $processed_ips_count);
        }else{
            error_log("No New Blocked IPs Found");
        }

        // Then redirect back to prevent form resubmission on refresh
        wp_redirect( add_query_arg( 'page', 'wtc-settings', admin_url( 'options-general.php' ) ) );
        exit;
    }
}
add_action( 'admin_post_wtc_run_process', 'wtc_run_process_manually' );




// The function that will run when the plugin is deactivated
function wtc_deactivate() {
    // Unschedule the function from running every 5 minutes
    $scheduled_timestamp = wp_next_scheduled('wtc_check_new_blocked_ips');
    if ($scheduled_timestamp) {
        wp_unschedule_event($scheduled_timestamp, 'wtc_check_new_blocked_ips');
    }
}
register_deactivation_hook(__FILE__, 'wtc_deactivate');

function wtc_update_wp_config( $cloudflare_email, $cloudflare_key ) {
    // Config file path
    $config_file = ABSPATH . 'wp-config.php';

    // Check if the file is writable
    if ( is_writable( $config_file ) ) {
        // Get the current contents
        $config_contents = file_get_contents( $config_file );

        // Prepare the new lines to add
        $new_lines = "\ndefine( 'CLOUDFLARE_EMAIL', '{$cloudflare_email}' );";
        $new_lines .= "\ndefine( 'CLOUDFLARE_KEY', '{$cloudflare_key}' );\n";

        // Check if the constants are already in the file
        if ( strpos( $config_contents, "CLOUDFLARE_EMAIL" ) !== false && strpos( $config_contents, "CLOUDFLARE_KEY" ) !== false ) {
            // Constants already exist, so replace them
            $config_contents = preg_replace("/define\(\s*'CLOUDFLARE_EMAIL',\s*'.*'\s*\);/", "define( 'CLOUDFLARE_EMAIL', '{$cloudflare_email}' );", $config_contents);
            $config_contents = preg_replace("/define\(\s*'CLOUDFLARE_KEY',\s*'.*'\s*\);/", "define( 'CLOUDFLARE_KEY', '{$cloudflare_key}' );", $config_contents);
        } else {
            // Constants do not exist, so add them before "That's all, stop editing!"
            $config_contents = str_replace( "/* That's all, stop editing! Happy publishing. */", $new_lines . "/* That's all, stop editing! Happy publishing. */", $config_contents );
        }

        // Write the new contents to the file
        file_put_contents( $config_file, $config_contents );
    } else {
        error_log( 'The wp-config.php file is not writable.' );
    }
}

// Add 5 minutes interval to cron schedules
function wtc_add_cron_interval( $schedules ) {
    $schedules['5min'] = array(
        'interval' => 5*60,
        'display'  => __( 'Every 5 Minutes', 'textdomain' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'wtc_add_cron_interval' );

?>