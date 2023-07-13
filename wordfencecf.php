<?php
/*
Plugin Name: Wordfence to Cloudflare
Description: This plugin takes blocked IPs from Wordfence and adds them to a specified list in Cloudflare.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Create the admin page
function wtc_admin_menu() {
	add_menu_page( 'Wordfence to Cloudflare', 'Wordfence to Cloudflare', 'manage_options', 'wtc', 'wtc_admin_page' );
}
add_action( 'admin_menu', 'wtc_admin_menu' );

// Display the admin page
function wtc_admin_page() {
	?>
	<div class="wrap">
		<h2>Wordfence to Cloudflare</h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'wtc_settings' ); ?>
			<?php do_settings_sections( 'wtc_settings' ); ?>
			<table class="form-table">
				<tr valign="top">
				<th scope="row">Cloudflare Email</th>
				<td><input type="text" name="cloudflare_email" value="<?php echo esc_attr( get_option('cloudflare_email') ); ?>" /></td>
				</tr>
				 
				<tr valign="top">
				<th scope="row">Cloudflare Key</th>
				<td><input type="text" name="cloudflare_key" value="<?php echo esc_attr( get_option('cloudflare_key') ); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Cloudflare List Name</th>
				<td><input type="text" name="cloudflare_list_name" value="<?php echo esc_attr( get_option('cloudflare_list_name') ); ?>" /></td>
				</tr>
			</table>
			
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// Register and store the settings
function wtc_settings() {
	register_setting( 'wtc_settings', 'cloudflare_email' );
	register_setting( 'wtc_settings', 'cloudflare_key' );
	register_setting( 'wtc_settings', 'cloudflare_list_name' );
}
add_action( 'admin_init', 'wtc_settings' );

// The function that will run when the plugin is activated
function wtc_activate() {
	global $wpdb;
	$blocked_ips = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wfBlocks7", OBJECT );

	add_ips_to_cloudflare( $blocked_ips );
}
register_activation_hook( __FILE__, 'wtc_activate' );

// Add the blocked IPs to Cloudflare
function add_ips_to_cloudflare( $blocked_ips ) {
	$url = 'https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules';
	$email = get_option('cloudflare_email');
	$key = get_option('cloudflare_key');

	foreach ( $blocked_ips as $ip ) {
		$response = wp_remote_post( $url, [
			'headers' => [
				'X-Auth-Email' => $email,
				'X-Auth-Key'   => $key,
				'Content-Type' => 'application/json'
			],
			'body'    => json_encode( [
				'mode'      => 'block',
				'configuration' => [
					'target' => 'ip',
					'value'  => $ip->ip
				],
				'notes'     => 'Blocked by Wordfence'
			] )
		] );

		if ( is_wp_error( $response ) ) {
			// handle the error
			error_log( print_r( $response->get_error_message(), true ) );
		}
	}
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'wtc_add_ips_to_cloudflare' ) ) {
	wp_schedule_event( time(), 'hourly', 'wtc_add_ips_to_cloudflare' );
}

// Hook into that action that'll fire every hour
add_action( 'wtc_add_ips_to_cloudflare', 'wtc_activate' );

?>
