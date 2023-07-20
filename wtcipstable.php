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
                    <td><input type="checkbox" class="wtc-delete-checkbox" value="<?php echo $ip->id; ?>"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="wtc-delete-selected" class="button button-primary">Delete Selected</button>

    <script>
    jQuery(document).ready(function($) {
        // Initialize DataTables
        var table = $('#wtc-ips-table').DataTable();

        // Add search, paging, and sorting functionality
        table
            .order([[0, 'desc']]) // Sort by ID column in descending order by default
            .search('').draw(); // Perform initial search (empty search string)

        // Handle checkbox selection and deletion
        $('#wtc-delete-selected').on('click', function() {
            var selectedIds = [];
            $('.wtc-delete-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                if (confirm('Are you sure you want to delete the selected records?')) {
                    // Perform AJAX request to delete the selected records
                    $.ajax({
                        url: ajaxurl, // WordPress AJAX URL
                        type: 'POST',
                        data: {
                            action: 'wtc_delete_ips',
                            ids: selectedIds
                        },
                        success: function(response) {
                            // Reload the page after successful deletion
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            // Display error message
                            console.error(xhr.responseText);
                            alert('An error occurred while deleting the records. Please try again.');
                        }
                    });
                }
            } else {
                alert('Please select at least one record to delete.');
            }
        });
    });
    </script>
    <?php
}

