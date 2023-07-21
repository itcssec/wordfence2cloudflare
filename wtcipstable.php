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

         /// Handle checkbox selection and deletion from Cloudflare
        $('#wtc-delete-selected-cloudflare').on('click', function() {
            var selectedIds = [];
            var selectedIPs = [];
            $('.wtc-delete-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
                selectedIPs.push($(this).siblings('.wtc-ip-address').val());
            });

            if (selectedIds.length > 0) {
                if (confirm('Are you sure you want to delete the selected records from Cloudflare?')) {
                    // Perform AJAX request to delete the selected records from Cloudflare
                    $.ajax({
                        url: ajaxurl, // WordPress AJAX URL
                        type: 'POST',
                        data: {
                            action: 'wtc_delete_ips_cloudflare',
                            ids: selectedIds,
                            ips: selectedIPs
                        },
                        success: function(response) {
                            // Set the notice type and message as URL parameters
                            var noticeType = response.success ? response.data.type : 'error';
                            var noticeMessage = response.success ? response.data.message : 'An error occurred while deleting the records from Cloudflare. Please try again.';
                            window.location.href = window.location.href + '&wtc_notice_type=' + encodeURIComponent(noticeType) + '&wtc_notice_message=' + encodeURIComponent(noticeMessage);
                        },
                        error: function(xhr, status, error) {
                            // Display error message
                            console.error(xhr.responseText);
                            alert('An error occurred while deleting the records from Cloudflare. Please try again.');
                        }
                    });
                }
            } else {
                alert('Please select at least one record to delete from Cloudflare.');
            }
        });

        // After page reload, display the admin notice if present
        var noticeType = getURLParameter('wtc_notice_type');
        var noticeMessage = getURLParameter('wtc_notice_message');
        if (noticeType && noticeMessage) {
            $('.notice').remove(); // Remove any existing notices
            $('div.wrap').prepend("<div class='notice notice-" + noticeType + " is-dismissible'><p>" + noticeMessage + "</p></div>");
        }

        // Function to get URL parameter by name
        function getURLParameter(name) {
            var param = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.search);
            return param ? decodeURIComponent(param[1]) : null;
        }
    });
    </script>
    <?php
}


