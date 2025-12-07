<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add booking calendar page to admin menu
 */
function ssb_add_booking_calendar_page() {
    add_submenu_page(
        'edit.php?post_type=shortlet_booking',
        __('Booking Calendar', 'simple-shortlet-bookings'),
        __('Calendar', 'simple-shortlet-bookings'),
        'edit_posts',
        'ssb-booking-calendar',
        'ssb_booking_calendar_page'
    );
}
add_action('admin_menu', 'ssb_add_booking_calendar_page');

/**
 * Booking calendar page content
 */
function ssb_booking_calendar_page() {
    wp_enqueue_style('ssb-frontend-styles', SSB_URL . 'assets/css/style.css', [], '1.0.0');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Booking Calendar', 'simple-shortlet-bookings'); ?></h1>
        
        <div id="ssb-calendar-container" style="margin: 20px 0;">
            <div style="margin-bottom: 20px;">
                <label for="ssb-calendar-month"><?php esc_html_e('Month:', 'simple-shortlet-bookings'); ?></label>
                <select id="ssb-calendar-month" style="margin-right: 10px;">
                    <?php
                    $current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
                    for ($i = 1; $i <= 12; $i++) {
                        printf(
                            '<option value="%d"%s>%s</option>',
                            $i,
                            selected($current_month, $i, false),
                            date('F', mktime(0, 0, 0, $i, 1))
                        );
                    }
                    ?>
                </select>
                
                <label for="ssb-calendar-year"><?php esc_html_e('Year:', 'simple-shortlet-bookings'); ?></label>
                <select id="ssb-calendar-year" style="margin-right: 10px;">
                    <?php
                    $current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
                    for ($i = $current_year - 1; $i <= $current_year + 1; $i++) {
                        printf(
                            '<option value="%d"%s>%d</option>',
                            $i,
                            selected($current_year, $i, false),
                            $i
                        );
                    }
                    ?>
                </select>
                
                <button type="button" id="ssb-calendar-refresh" class="button"><?php esc_html_e('Refresh', 'simple-shortlet-bookings'); ?></button>
            </div>
            
            <div id="ssb-calendar" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <!-- Calendar will be rendered here -->
            </div>
        </div>
    </div>
    
    <style>
    .ssb-calendar-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ssb-calendar-table th,
    .ssb-calendar-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
        vertical-align: top;
        min-height: 100px;
    }
    .ssb-calendar-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
    }
    .ssb-calendar-day {
        min-height: 80px;
        position: relative;
    }
    .ssb-calendar-day-number {
        font-weight: 600;
        margin-bottom: 5px;
        color: #111827;
    }
    .ssb-calendar-booking {
        font-size: 11px;
        padding: 3px 5px;
        margin: 2px 0;
        border-radius: 3px;
        cursor: pointer;
        display: block;
        text-decoration: none;
        color: white;
    }
    .ssb-calendar-booking.pending {
        background-color: #f59e0b;
    }
    .ssb-calendar-booking.approved {
        background-color: #10b981;
    }
    .ssb-calendar-booking.cancelled {
        background-color: #ef4444;
        opacity: 0.6;
    }
    .ssb-calendar-other-month {
        background-color: #f9fafb;
        color: #9ca3af;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        function loadCalendar() {
            var month = $('#ssb-calendar-month').val();
            var year = $('#ssb-calendar-year').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ssb_get_calendar_data',
                    month: month,
                    year: year
                },
                success: function(response) {
                    if (response.success) {
                        renderCalendar(response.data);
                    }
                }
            });
        }
        
        function renderCalendar(data) {
            var calendar = $('#ssb-calendar');
            var month = parseInt($('#ssb-calendar-month').val());
            var year = parseInt($('#ssb-calendar-year').val());
            
            var firstDay = new Date(year, month - 1, 1);
            var lastDay = new Date(year, month, 0);
            var daysInMonth = lastDay.getDate();
            var startingDayOfWeek = firstDay.getDay();
            
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                             'July', 'August', 'September', 'October', 'November', 'December'];
            var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            
            var html = '<h2>' + monthNames[month - 1] + ' ' + year + '</h2>';
            html += '<table class="ssb-calendar-table">';
            html += '<thead><tr>';
            dayNames.forEach(function(day) {
                html += '<th>' + day + '</th>';
            });
            html += '</tr></thead><tbody><tr>';
            
            // Empty cells for days before month starts
            for (var i = 0; i < startingDayOfWeek; i++) {
                html += '<td class="ssb-calendar-other-month"><div class="ssb-calendar-day"></div></td>';
            }
            
            // Days of the month
            for (var day = 1; day <= daysInMonth; day++) {
                var dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var dayBookings = data[dateStr] || [];
                
                html += '<td>';
                html += '<div class="ssb-calendar-day">';
                html += '<div class="ssb-calendar-day-number">' + day + '</div>';
                
                dayBookings.forEach(function(booking) {
                    var statusClass = booking.status || 'pending';
                    var title = booking.shortlet_title || 'Booking';
                    html += '<a href="' + booking.edit_link + '" class="ssb-calendar-booking ' + statusClass + '" title="' + 
                           (booking.guest_name || '') + ' - ' + title + '">' + 
                           (title.length > 15 ? title.substring(0, 15) + '...' : title) + '</a>';
                });
                
                html += '</div>';
                html += '</td>';
                
                if ((day + startingDayOfWeek) % 7 === 0 && day < daysInMonth) {
                    html += '</tr><tr>';
                }
            }
            
            // Fill remaining cells
            var remainingCells = 7 - ((daysInMonth + startingDayOfWeek) % 7);
            if (remainingCells < 7) {
                for (var i = 0; i < remainingCells; i++) {
                    html += '<td class="ssb-calendar-other-month"><div class="ssb-calendar-day"></div></td>';
                }
            }
            
            html += '</tr></tbody></table>';
            calendar.html(html);
        }
        
        $('#ssb-calendar-refresh, #ssb-calendar-month, #ssb-calendar-year').on('change click', function() {
            loadCalendar();
        });
        
        loadCalendar();
    });
    </script>
    <?php
}

/**
 * AJAX handler for calendar data
 */
function ssb_get_calendar_data() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    $bookings = get_posts([
        'post_type' => 'shortlet_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_status',
                'value' => 'cancelled',
                'compare' => '!='
            ]
        ]
    ]);
    
    $calendar_data = [];
    
    foreach ($bookings as $booking) {
        $check_in = get_post_meta($booking->ID, '_check_in', true);
        $check_out = get_post_meta($booking->ID, '_check_out', true);
        $status = get_post_meta($booking->ID, '_status', true) ?: 'pending';
        $shortlet_id = get_post_meta($booking->ID, '_shortlet_id', true);
        $guest_name = get_post_meta($booking->ID, '_guest_name', true);
        
        if (!$check_in || !$check_out) continue;
        
        $start = new DateTime($check_in);
        $end = new DateTime($check_out);
        $end->modify('-1 day'); // Exclude check-out day
        
        $current = clone $start;
        while ($current <= $end) {
            $date_key = $current->format('Y-m-d');
            $date_month = intval($current->format('n'));
            $date_year = intval($current->format('Y'));
            
            if ($date_month === $month && $date_year === $year) {
                if (!isset($calendar_data[$date_key])) {
                    $calendar_data[$date_key] = [];
                }
                
                $calendar_data[$date_key][] = [
                    'id' => $booking->ID,
                    'shortlet_title' => $shortlet_id ? get_the_title($shortlet_id) : 'Unknown',
                    'guest_name' => $guest_name,
                    'status' => $status,
                    'edit_link' => admin_url('post.php?post=' . $booking->ID . '&action=edit')
                ];
            }
            
            $current->modify('+1 day');
        }
    }
    
    wp_send_json_success($calendar_data);
}
add_action('wp_ajax_ssb_get_calendar_data', 'ssb_get_calendar_data');

