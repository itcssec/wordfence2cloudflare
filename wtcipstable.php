<?php
// Render Blocked IPs Tab Content
function wtc_render_ips_tab_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wtc_blocked_ips';

    $ips = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <h2>Blocked IPs</h2>

    <table class="wp-list-table widefat fixed striped">
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
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ips as $ip) : ?>
                <tr>
                    <td><?php echo $ip->id; ?></td>
                    <td><?php echo $ip->blockedTime; ?></td>
                    <td><?php echo $ip->blockedHits; ?></td>
                    <td><?php echo $ip->ip; ?></td>
                    <td><?php echo $ip->countryCode; ?></td>
                    <td><?php echo $ip->countryName; ?></td>
                    <td><?php echo $ip->whoisEntry; ?></td>
                    <td><?php echo $ip->wafStatus; ?></td>
                    <td><?php echo $ip->cfResponse; ?></td>
                    <td><?php echo $ip->isSent; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
