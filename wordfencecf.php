<?php
/*
Plugin Name: Wordfence2Cloudflare
Description: This plugin takes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list.
Version: 1.3
Author: ITCS
Author URI: https://itcybersecurity.gr/
License: GPLv2 or later
Text Domain: wordfence2cloudflare
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
include_once plugin_dir_path(__FILE__) . 'wtcipstable.php';
// Add settings link to plugin page
function wtc_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=wtc-settings') . '">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wtc_add_settings_link');

// Check if the custom table exists and create it if not
function wtc_check_custom_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'wtc_blocked_ips';

	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		wtc_create_custom_table();
	}
}
add_action('init', 'wtc_check_custom_table');

// Create custom table during plugin activation
function wtc_create_custom_table() {
	global $wpdb;
    $charset_collate = $wpdb->get_charset_collate(); 

	$table_name = $wpdb->prefix . 'wtc_blocked_ips';

	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id INT(11) NOT NULL AUTO_INCREMENT,
			blockedTime DATETIME NOT NULL,
			blockedHits INT(11) NOT NULL,
			ip VARCHAR(45) NOT NULL,
			countryCode VARCHAR(2) NOT NULL,
			countryName VARCHAR(64) NOT NULL,
			whoisEntry TEXT NOT NULL,
			wafStatus TEXT NOT NULL,
			cfResponse TEXT NOT NULL,
			isSent TINYINT(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
register_activation_hook(__FILE__, 'wtc_create_custom_table');


// Fetch blocked IPs from Wordfence and add them to the custom table
function wtc_fetch_and_store_blocked_ips() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wtc_blocked_ips';
    $threshold = get_option('blocked_hits_threshold', 1);

    $blocked_ips = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wfblocks7 WHERE blockedHits >= {$threshold}",
        OBJECT
    );

    if ($blocked_ips) {
        foreach ($blocked_ips as $ip) {
            $ip_address = inet_ntop($ip->IP);
            // Remove "::ffff:" prefix if present
            $ip_address = preg_replace('/^::ffff:/', '', $ip_address);
            
            if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
                error_log('Invalid IP address: ' . $ip_address);
                continue;
            }
			 // Check if the IP address already exists in the table
        $existing_ip = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE ip = %s", $ip_address)
        );
        
        if ($existing_ip) {
            // IP already exists, skip inserting a new record
            continue;
        }else{

			$wpdb->insert(
				$table_name,
				array(
					'blockedTime' => date('Y-m-d H:i:s', $ip->blockedTime),
					'blockedHits' => $ip->blockedHits,
					'ip' => $ip_address,
					'cfResponse' => '',
					'isSent' => 0,
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
				)
			);
		}
		}
	}
}
add_action('wtc_check_new_blocked_ips', 'wtc_fetch_and_store_blocked_ips');

// Update Cloudflare response in the custom table
function wtc_update_cloudflare_response($ip_id, $cf_response) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'wtc_blocked_ips';

	$wpdb->update(
		$table_name,
		array('cfResponse' => $cf_response),
		array('id' => $ip_id),
		array('%s'),
		array('%d')
	);
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

// Render Blocked IPs Tab
function wtc_render_ips_tab() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die('Access is not allowed.');
    }

    wtc_render_ips_tab_content();
}



// Create the admin page
function wtc_render_admin_page() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die('Access is not allowed.');
    }

    ?>
    <div class="wrap">
        <h1>Wordfence to Cloudflare</h1>

        <!-- Add Tabs -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=wtc-settings" class="nav-tab <?php echo (isset($_GET['page']) && $_GET['page'] === 'wtc-settings') ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=wtc-settings&tab=wtc-ips" class="nav-tab <?php echo (isset($_GET['page']) && $_GET['tab'] === 'wtc-ips') ? 'nav-tab-active' : ''; ?>">Blocked IPs</a>
        </h2>

        <!-- Display Tab Content -->
        <?php
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'wtc-settings';

        switch ($active_tab) {
            case 'wtc-ips':
                // Render the blocked IPs tab content
                wtc_render_ips_tab();
                break;

            default:
                // Render the settings tab content
                wtc_render_settings_tab();
                break;
        }
        ?>
    </div>
    <?php
}





// Create the admin page
function wtc_render_settings_tab() {
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
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';
        $threshold = get_option('blocked_hits_threshold', 1);
        $last_processed_time = get_option('wtc_last_processed_time', 0); // Default to 0 if not set

        // Convert last processed time to the same format as the blockedTime column
        $last_processed_time_formatted = date('Y-m-d H:i:s', $last_processed_time);

        $blocked_ips = $wpdb->get_results(
		"SELECT * FROM $table_name WHERE isSent = 0",
		OBJECT);//$wpdb->get_results(
            //"SELECT * FROM {$wpdb->prefix}wfblocks7 WHERE blockedTime > UNIX_TIMESTAMP('{$last_processed_time_formatted}') AND blockedHits >= {$threshold}",
           // OBJECT
        //);

        error_log("SQL Query: " . $wpdb->last_query);

        // Run the process
        $processed_ips_count = count($blocked_ips);
        if($blocked_ips) {
            error_log("Blocked IPs: " . print_r($blocked_ips, true)); // Debug statement
            wtc_add_ips_to_cloudflare( $blocked_ips ); // Pass $blocked_ips as an argument
            update_option('wtc_last_processed_time', time());
            update_option('wtc_processed_ips_count', $processed_ips_count);
        }else{
            error_log("No New Blocked IPs Found");
        }

}


// Add the blocked IPs to Cloudflare
// Add IPs to Cloudflare
function wtc_add_ips_to_cloudflare() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'wtc_blocked_ips';
	$email = get_option('cloudflare_email');
	$key = get_option('cloudflare_key');
	$block_scope = get_option('block_scope', 'domain');
	$zone_id = get_option('cloudflare_zone_id');

	$ips_to_send = $wpdb->get_results(
		"SELECT * FROM $table_name WHERE isSent = 0",
		OBJECT
	);

	if ($ips_to_send) {
		$api_url = ($block_scope == 'domain')
			? "https://api.cloudflare.com/client/v4/zones/{$zone_id}/firewall/access_rules/rules"
			: "https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules";

		$timezone = get_option('timezone_string');
		if (empty($timezone)) {
			$timezone = 'UTC';
		}

		try {
			$current_datetime = new DateTime('now', new DateTimeZone($timezone));
		} catch (Exception $e) {
			error_log('Failed to create DateTime object: ' . $e->getMessage());
			return;
		}

		foreach ($ips_to_send as $ip) {
			$ip_address = $ip->ip;

			$response = wp_remote_post($api_url, array(
				'headers' => array(
					'X-Auth-Email' => $email,
					'X-Auth-Key' => $key,
					'Content-Type' => 'application/json',
				),
				'body' => json_encode(array(
					'mode' => 'block',
					'configuration' => array(
						'target' => 'ip',
						'value' => $ip_address,
					),
					'notes' => 'Blocked by WordfenceCloudflare plugin' . " " . $current_datetime->format('Y-m-d H:i:s'),
				)),
			));

			if (is_wp_error($response)) {
				error_log('Failed to create access rule: ' . $response->get_error_message());
				//continue;
			}

			$body = json_decode($response['body'], true);
			
			if (!empty($body['errors'])) {
				$error = $body['errors'][0];
				$responseCode = $error['code'];
				$responseMessage = $error['message'];
				if ($responseCode == '10009' && $responseMessage == 'firewallaccessrules.api.duplicate_of_existing') {
					// You can use the response code as needed
					error_log('Response Duplicated Code: ' . $responseCode);
					wtc_update_cloudflare_response($ip->id, $response['body']);
					// Mark IP as sent
        			$wpdb->update(
        				$table_name,
        				array('isSent' => 1),
        				array('id' => $ip->id),
        				array('%d'),
        				array('%d')
        			);
        			continue;
				}
				elseif($responseCode != '10009' && $responseMessage != 'firewallaccessrules.api.duplicate_of_existing') {
				error_log('Failed to create access rule: ' . print_r($body, true));
				continue;
			}
				    
				
			}

			wtc_update_cloudflare_response($ip->id, $response['body']);

			// Mark IP as sent
			$wpdb->update(
				$table_name,
				array('isSent' => 1),
				array('id' => $ip->id),
				array('%d'),
				array('%d')
			);
		}
	}
}

//add_action('wtc_add_ips_to_cloudflare', 'add_ips_to_cloudflare');


function wtc_run_process_manually() {
    if ( isset( $_POST['wtc_run_process'] ) ) {
        global $wpdb;
        wtc_fetch_and_store_blocked_ips();
        $table_name = $wpdb->prefix . 'wtc_blocked_ips';
        $threshold = get_option('blocked_hits_threshold', 1);
        $last_processed_time = get_option('wtc_last_processed_time', 0); // Default to 0 if not set

        // Convert last processed time to the same format as the blockedTime column
        $last_processed_time_formatted = date('Y-m-d H:i:s', $last_processed_time);

        $blocked_ips = $wpdb->get_results(
		"SELECT * FROM $table_name WHERE isSent = 0",
		OBJECT);

        error_log("SQL Query: " . $wpdb->last_query);

        // Run the process
        $processed_ips_count = count($blocked_ips);
        if($blocked_ips) {
            error_log("Blocked IPs: " . print_r($blocked_ips, true)); // Debug statement
            wtc_add_ips_to_cloudflare( $blocked_ips ); // Pass $blocked_ips as an argument
            update_option('wtc_last_processed_time', time());
            update_option('wtc_processed_ips_count', $processed_ips_count);
        }else{
            error_log("No New Blocked IPs Found - Manual Process");
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

function wtc_enqueue_scripts() {
    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue DataTables library
    wp_enqueue_script('wtc-datatables', 'https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.js', array('jquery'), '1.11.3');

    // Enqueue DataTables CSS
    wp_enqueue_style('wtc-datatables-css', 'https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.css', array(), '1.11.3');
}
add_action('admin_enqueue_scripts', 'wtc_enqueue_scripts');

function wtc_delete_ips() {
    global $wpdb;
    check_ajax_referer('wtc_ips_tab_action', 'wtc_ips_tab_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Access is not allowed.');
    }

    $table_name = $wpdb->prefix . 'wtc_blocked_ips';
    $ids = $_POST['ids'];

    // Ensure $ids are integers only
    $ids = array_map('intval', $ids);
    
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $query = "DELETE FROM $table_name WHERE id IN ($placeholders)";

    // Delete the selected IPs
    $wpdb->query($wpdb->prepare($query, ...$ids));

    wp_send_json_success('Selected records deleted successfully.');
}
add_action('wp_ajax_wtc_delete_ips', 'wtc_delete_ips');


// Callback for deleting IPs from Cloudflare
function wtc_delete_ips_cloudflare() {
    // Verify the nonce before processing the request
    check_ajax_referer('wtc_ips_tab_action', 'wtc_ips_tab_nonce');
    if (!current_user_can('manage_options') || empty($_POST['ips'])) {
        wp_send_json_error(['type' => 'error', 'message' => 'Invalid request data.']);
        wp_die();
    }

    $cf_zone_id = get_option('cloudflare_zone_id');
    $cf_api_key = get_option('cloudflare_key');
    $cf_email = get_option('cloudflare_email');
    error_log("API Key: $cf_api_key, Email: $cf_email, Zone ID: $cf_zone_id");

    if (!$cf_zone_id || !$cf_api_key || !$cf_email) {
        wp_send_json_error(['type' => 'error', 'message' => 'Cloudflare credentials are not set.']);
        wp_die();
    }

    $ips_to_delete = $_POST['ips'];
    $deleted_ips = [];

    foreach ($ips_to_delete as $ip) {
        $api_url = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/firewall/access_rules/rules?configuration.value=$ip";

        $headers = [
            "Content-Type: application/json",
            "X-Auth-Email: $cf_email",
            "X-Auth-Key: $cf_api_key",
        ];

        // Get all IP access rules from Cloudflare
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $error_message = "Failed to fetch IP access rules from Cloudflare for IP: $ip - Error: $err";
            error_log($error_message); // Log the error
            continue; // Continue to the next IP
        }

        $data = json_decode($response, true);
        // Log the data received from Cloudflare
        error_log("Data received from Cloudflare for IP: $ip");
        error_log(print_r($data, true));

        if (empty($data['result'])) {
            $error_message = "No matching IP access rule found in Cloudflare for IP: $ip";
            error_log($error_message); // Log the error
            continue; // Continue to the next IP
        }

        $matchedRuleId = $data['result'][0]['id'];
        $matchedRuleType = $data['result'][0]['scope']['type'];
        error_log("Matched Rule ID: " . $matchedRuleId);
        error_log("Matched Rule Type: " . $matchedRuleType);

        // Delete the matched IP rule from Cloudflare
        if ($matchedRuleType == 'zone'){
        $delete_url = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/firewall/access_rules/rules/$matchedRuleId";
            
        }else{
        $delete_url = "https://api.cloudflare.com/client/v4/accounts/9a247a2ba7d05fcd2a72d002ffb9f73a/firewall/access_rules/rules/$matchedRuleId";
            
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $delete_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        

        $delete_response = curl_exec($curl);
        $delete_err = curl_error($curl);
       

        curl_close($curl);

        if ($delete_err) {
            $error_message = "Failed to delete IP access rule for IP: $ip from Cloudflare - Error: $delete_err";
            error_log($error_message); // Log the error
            continue; // Continue to the next IP
        }

         $delete_data = json_decode($delete_response, true);
          error_log("Data received from Delete Cloudflare for IP: $ip");
        error_log(print_r($delete_data, true));
        foreach ($delete_data['messages'] as $error) {
            error_log("Error Message: " . $error); // Log the error
        }
        if (!empty($delete_data['success']) && $delete_data['success'] === true) {
            $deleted_ips[] = $ip;
            $ids = $ip;
            global $wpdb;
            $table_name = $wpdb->prefix . 'wtc_blocked_ips';
            // Delete the selected IPs
            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE ip like '%$ip%'"));
        } else {
            $error_message = "Failed to delete IP access rule for IP: $ip from Cloudflare. Response: " . print_r($delete_data, true);
            error_log($error_message); // Log the error
        }
    }

    // If there were deleted IPs, send the response
    if (!empty($deleted_ips)) {
        wp_send_json_success(['type' => 'success', 'message' => 'IPs deleted successfully from Cloudflare.', 'deleted_ips' => $deleted_ips]);
    } else {
        wp_send_json_error(['type' => 'error', 'message' => 'No valid IP access rules were deleted from Cloudflare.']);
    }

    wp_die();
}
add_action('wp_ajax_wtc_delete_ips_cloudflare', 'wtc_delete_ips_cloudflare');





// Function to display the admin notice
function wtc_display_admin_notice() {
    if (!isset($_GET['wtc_notice'])) {
        return;
    }

    $message = sanitize_text_field($_GET['wtc_notice']);
    $type = isset($_GET['wtc_type']) ? (in_array($_GET['wtc_type'], array('error', 'updated')) ? $_GET['wtc_type'] : 'updated') : 'updated';

    // Encode for output
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo "<div class='notice notice-$type is-dismissible'><p>$message</p></div>";
}
add_action('admin_notices', 'wtc_display_admin_notice');





?>