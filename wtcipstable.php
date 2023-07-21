<?php
// Render Blocked IPs Tab Content
function wtc_render_ips_tab_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $ips = $wpdb->get_results("SELECT * FROM $table_name");

    // Render the table HTML
    ?>
    <h2>Blocked IPs</h2>

    <table id="wtc-ips-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Blocked Time</th>
                <th>Blocked Hits</th>
                <th>IP</th>
                <th>Country Code</th>
                <th>Country Name</th>
                <th>Whois Entry</th>
                <th>WAF Status</th>
                <th>CF Response</th>
                <th>Is Sent</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ips as $ip) : ?>
                <tr>
                    <td><?php echo esc_html($ip->id); ?></td>
                    <td><?php echo esc_html($ip->blockedTime); ?></td>
                    <td><?php echo esc_html($ip->blockedHits); ?></td>
                    <td><?php echo esc_html($ip->ip); ?></td>
                    <td><?php echo esc_html($ip->countryCode); ?></td>
                    <td><?php echo esc_html($ip->countryName); ?></td>
                    <td><?php echo esc_html($ip->whoisEntry); ?></td>
                    <td><?php echo esc_html($ip->wafStatus); ?></td>
                    <td><?php echo esc_html($ip->cfResponse); ?></td>
                    <td><?php echo esc_html($ip->isSent); ?></td>
                    <td>
                        <input type="checkbox" class="wtc-delete-checkbox" value="<?php echo $ip->id; ?>">
                        <input type="hidden" class="wtc-ip-address" value="<?php echo $ip->ip; ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="wtc-delete-selected" class="button button-primary">Delete Selected</button>
    <button id="wtc-delete-selected-cloudflare" class="button button-primary">Delete Selected (Cloudflare)</button>

    <?php
}
// Enqueue the JavaScript file for the admin page
add_action('admin_enqueue_scripts', 'wtc_enqueue_admin_scripts');
function wtc_enqueue_admin_scripts() {
    // Enqueue the DataTables library (assuming you haven't already done it)
    wp_enqueue_script('dataTables', 'https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js', array('jquery'), '1.10.25', true);

    // Enqueue your custom script that contains the wtc_ips_tab_nonce variable
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'js/custom-script.js', array('jquery'), '1.0', true);

    // Localize the nonce value to make it available in the custom script
    wp_localize_script('custom-script', 'wtc_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'wtc_ips_tab_nonce' => wp_create_nonce('wtc_ips_tab_action')
    ));
}
