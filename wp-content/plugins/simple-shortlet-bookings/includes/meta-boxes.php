<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta box for booking details
 */
function ssb_add_booking_meta_box() {
    add_meta_box(
        'ssb_booking_details',
        __('Booking Details', 'simple-shortlet-bookings'),
        'ssb_booking_meta_box_callback',
        'shortlet_booking',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ssb_add_booking_meta_box');

function ssb_booking_meta_box_callback($post) {
    wp_nonce_field('ssb_save_booking_meta', 'ssb_booking_meta_nonce');

    $shortlet_id = get_post_meta($post->ID, '_shortlet_id', true);
    $guest_name  = get_post_meta($post->ID, '_guest_name', true);
    $guest_email = get_post_meta($post->ID, '_guest_email', true);
    $guest_phone = get_post_meta($post->ID, '_guest_phone', true);
    $check_in    = get_post_meta($post->ID, '_check_in', true);
    $check_out   = get_post_meta($post->ID, '_check_out', true);
    $status      = get_post_meta($post->ID, '_status', true) ?: 'pending';

    $shortlet_title = $shortlet_id ? get_the_title($shortlet_id) : __('Unknown shortlet', 'simple-shortlet-bookings');
    
    // Get shortlet images for carousel
    $all_images = [];
    if ($shortlet_id) {
        // Get featured image
        $featured_id = get_post_thumbnail_id($shortlet_id);
        if ($featured_id) {
            $all_images[] = $featured_id;
        }
        
        // Get gallery images
        $gallery_ids = get_post_meta($shortlet_id, '_ssb_gallery_ids', true);
        if ($gallery_ids) {
            $gallery_ids = explode(',', $gallery_ids);
            $gallery_ids = array_filter(array_map('intval', $gallery_ids));
            foreach ($gallery_ids as $img_id) {
                if (!in_array($img_id, $all_images)) {
                    $all_images[] = $img_id;
                }
            }
        }
    }
    ?>
    <div style="margin-bottom: 20px;">
        <p><strong><?php esc_html_e('Shortlet:', 'simple-shortlet-bookings'); ?></strong> 
        <?php if ($shortlet_id) : ?>
            <a href="<?php echo esc_url(get_edit_post_link($shortlet_id)); ?>" target="_blank"><?php echo esc_html($shortlet_title); ?></a>
        <?php else : ?>
            <?php echo esc_html($shortlet_title); ?>
        <?php endif; ?>
        </p>
        
        <?php if (!empty($all_images)) : ?>
            <div class="ssb-booking-gallery" style="margin: 15px 0;">
                <div class="ssb-carousel" data-carousel-id="booking-<?php echo esc_attr($post->ID); ?>" style="max-width: 600px;">
                    <div class="ssb-carousel-container" style="height: 300px;">
                        <?php foreach ($all_images as $index => $img_id) : 
                            // Use large size for admin, fallback to full
                            $img_url = wp_get_attachment_image_url($img_id, 'large');
                            if (!$img_url) {
                                $img_url = wp_get_attachment_image_url($img_id, 'full');
                            }
                            $img_full = wp_get_attachment_image_url($img_id, 'full');
                        ?>
                            <div class="ssb-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo esc_url($img_url); ?>" 
                                     srcset="<?php echo esc_attr(wp_get_attachment_image_srcset($img_id, 'large')); ?>"
                                     sizes="600px"
                                     data-full="<?php echo esc_url($img_full); ?>" 
                                     alt="<?php echo esc_attr($shortlet_title); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($all_images) > 1) : ?>
                        <button class="ssb-carousel-prev" aria-label="<?php esc_attr_e('Previous image', 'simple-shortlet-bookings'); ?>">‹</button>
                        <button class="ssb-carousel-next" aria-label="<?php esc_attr_e('Next image', 'simple-shortlet-bookings'); ?>">›</button>
                        <div class="ssb-carousel-dots">
                            <?php foreach ($all_images as $index => $img_id) : ?>
                                <button class="ssb-carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        data-slide="<?php echo esc_attr($index); ?>"
                                        aria-label="<?php echo esc_attr(sprintf(__('Go to slide %d', 'simple-shortlet-bookings'), $index + 1)); ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <p style="color: #666; font-style: italic;"><?php esc_html_e('No images available for this shortlet.', 'simple-shortlet-bookings'); ?></p>
        <?php endif; ?>
    </div>

    <p>
        <label><?php esc_html_e('Guest name', 'simple-shortlet-bookings'); ?></label><br>
        <input type="text" name="ssb_guest_name" value="<?php echo esc_attr($guest_name); ?>" class="regular-text">
    </p>

    <p>
        <label><?php esc_html_e('Guest email', 'simple-shortlet-bookings'); ?></label><br>
        <input type="email" name="ssb_guest_email" value="<?php echo esc_attr($guest_email); ?>" class="regular-text">
    </p>

    <p>
        <label><?php esc_html_e('Guest phone', 'simple-shortlet-bookings'); ?></label><br>
        <input type="text" name="ssb_guest_phone" value="<?php echo esc_attr($guest_phone); ?>" class="regular-text">
    </p>

    <p>
        <label><?php esc_html_e('Check in date', 'simple-shortlet-bookings'); ?></label><br>
        <input type="date" name="ssb_check_in" value="<?php echo esc_attr($check_in); ?>">
    </p>

    <p>
        <label><?php esc_html_e('Check out date', 'simple-shortlet-bookings'); ?></label><br>
        <input type="date" name="ssb_check_out" value="<?php echo esc_attr($check_out); ?>">
    </p>

    <p>
        <label><?php esc_html_e('Status', 'simple-shortlet-bookings'); ?></label><br>
        <select name="ssb_status">
            <option value="pending"  <?php selected($status, 'pending');  ?>><?php esc_html_e('Pending', 'simple-shortlet-bookings'); ?></option>
            <option value="approved" <?php selected($status, 'approved'); ?>><?php esc_html_e('Approved', 'simple-shortlet-bookings'); ?></option>
            <option value="rejected" <?php selected($status, 'rejected'); ?>><?php esc_html_e('Rejected', 'simple-shortlet-bookings'); ?></option>
            <option value="cancelled"<?php selected($status, 'cancelled');?>><?php esc_html_e('Cancelled', 'simple-shortlet-bookings'); ?></option>
        </select>
    </p>
    <?php
}

/**
 * Save booking meta
 */
function ssb_save_booking_meta($post_id) {
    if (!isset($_POST['ssb_booking_meta_nonce']) || !wp_verify_nonce($_POST['ssb_booking_meta_nonce'], 'ssb_save_booking_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'shortlet_booking') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        '_guest_name'  => 'ssb_guest_name',
        '_guest_email' => 'ssb_guest_email',
        '_guest_phone' => 'ssb_guest_phone',
        '_check_in'    => 'ssb_check_in',
        '_check_out'   => 'ssb_check_out',
        '_status'      => 'ssb_status',
    ];

    $old_status = get_post_meta($post_id, '_status', true) ?: 'pending';

    foreach ($fields as $meta_key => $field_name) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    // Get new status - always check POST first, then fallback to old status
    $new_status = isset($_POST['ssb_status']) ? sanitize_text_field($_POST['ssb_status']) : $old_status;
    
    // Always update status to ensure it's saved (even if it's the same)
    update_post_meta($post_id, '_status', $new_status);
    
    // Send email notification if status changed
    if ($new_status !== $old_status && in_array($new_status, ['approved', 'rejected', 'cancelled'])) {
        $shortlet_id = get_post_meta($post_id, '_shortlet_id', true);
        $guest_email = get_post_meta($post_id, '_guest_email', true);
        $guest_name = get_post_meta($post_id, '_guest_name', true);
        $check_in = get_post_meta($post_id, '_check_in', true);
        $check_out = get_post_meta($post_id, '_check_out', true);
        $nights = get_post_meta($post_id, '_nights', true) ?: 0;
        $total_price = get_post_meta($post_id, '_total_price', true) ?: 0;
        $currency = get_post_meta($post_id, '_currency', true) ?: 'USD';
        
        if ($guest_email && $shortlet_id) {
            $guest_email_data = [
                'shortlet_title' => get_the_title($shortlet_id),
                'guest_name' => $guest_name,
                'check_in' => $check_in,
                'check_out' => $check_out,
                'nights' => $nights,
                'total_price' => $total_price,
                'currency' => $currency,
                'status' => $new_status
            ];
            
            $subject = sprintf(__('Booking Update - %s', 'simple-shortlet-bookings'), get_the_title($shortlet_id));
            $email_html = ssb_get_guest_confirmation_email($guest_email_data);
            ssb_send_html_email($guest_email, $subject, $email_html);
        }
    }
}
add_action('save_post', 'ssb_save_booking_meta');

/**
 * Add location meta box for shortlets
 */
function ssb_add_shortlet_location_meta_box() {
    add_meta_box(
        'ssb_shortlet_location',
        __('Location & Map', 'simple-shortlet-bookings'),
        'ssb_shortlet_location_meta_box_callback',
        'shortlet',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ssb_add_shortlet_location_meta_box');

function ssb_shortlet_location_meta_box_callback($post) {
    wp_nonce_field('ssb_save_shortlet_location', 'ssb_shortlet_location_nonce');
    
    $address = get_post_meta($post->ID, '_ssb_address', true);
    $latitude = get_post_meta($post->ID, '_ssb_latitude', true);
    $longitude = get_post_meta($post->ID, '_ssb_longitude', true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="ssb_address"><?php esc_html_e('Address', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <input type="text" name="ssb_address" id="ssb_address" value="<?php echo esc_attr($address); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('Full address of the shortlet (e.g., 123 Main Street, City, Country).', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ssb_latitude"><?php esc_html_e('Latitude', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <input type="text" name="ssb_latitude" id="ssb_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text" placeholder="e.g., 6.5244">
                <p class="description"><?php esc_html_e('Latitude coordinate for map display. You can find this on Google Maps.', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ssb_longitude"><?php esc_html_e('Longitude', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <input type="text" name="ssb_longitude" id="ssb_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text" placeholder="e.g., 3.3792">
                <p class="description"><?php esc_html_e('Longitude coordinate for map display. You can find this on Google Maps.', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
    </table>
    <p>
        <button type="button" id="ssb-geocode-address" class="button button-secondary">
            <?php esc_html_e('Get Coordinates from Address', 'simple-shortlet-bookings'); ?>
        </button>
        <span class="description" style="margin-left: 10px;"><?php esc_html_e('Click to automatically get coordinates from the address using OpenStreetMap (free, no API key required).', 'simple-shortlet-bookings'); ?></span>
    </p>
    
    <script>
    jQuery(document).ready(function($) {
        // Ensure ajaxurl is defined (for admin context)
        if (typeof ajaxurl === 'undefined') {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        }
        
        // Rate limiting: track last request time (Nominatim allows 1 request per second)
        var lastGeocodeTime = 0;
        
        $('#ssb-geocode-address').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var address = $('#ssb_address').val();
            
            if (!address || address.trim() === '') {
                alert('<?php echo esc_js(__('Please enter an address first.', 'simple-shortlet-bookings')); ?>');
                return;
            }
            
            // Check rate limiting (wait at least 1 second between requests)
            var now = Date.now();
            var timeSinceLastRequest = now - lastGeocodeTime;
            if (timeSinceLastRequest < 1000) {
                var waitTime = Math.ceil((1000 - timeSinceLastRequest) / 1000);
                alert('<?php echo esc_js(__('Please wait a moment before trying again. The geocoding service allows 1 request per second.', 'simple-shortlet-bookings')); ?>');
                return;
            }
            
            lastGeocodeTime = now;
            $button.prop('disabled', true).text('<?php echo esc_js(__('Getting coordinates...', 'simple-shortlet-bookings')); ?>');
            
            // Use server-side AJAX handler to avoid browser header restrictions
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ssb_geocode_address',
                    address: address,
                    nonce: '<?php echo wp_create_nonce('ssb_geocode_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var lat = parseFloat(response.data.lat);
                        var lon = parseFloat(response.data.lon);
                        
                        if (!isNaN(lat) && !isNaN(lon)) {
                            $('#ssb_latitude').val(lat.toFixed(6));
                            $('#ssb_longitude').val(lon.toFixed(6));
                            alert('<?php echo esc_js(__('Coordinates retrieved successfully!', 'simple-shortlet-bookings')); ?>');
                        } else {
                            alert('<?php echo esc_js(__('Could not parse coordinates from response.', 'simple-shortlet-bookings')); ?>');
                        }
                    } else {
                        var message = response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Address not found. Please check the address and try again, or enter coordinates manually.', 'simple-shortlet-bookings')); ?>';
                        alert(message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Geocoding error:', status, error, xhr);
                    var errorMsg = '<?php echo esc_js(__('Error retrieving coordinates. Please try again or enter coordinates manually.', 'simple-shortlet-bookings')); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    alert(errorMsg);
                },
                complete: function() {
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Get Coordinates from Address', 'simple-shortlet-bookings')); ?>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for geocoding addresses
 */
function ssb_geocode_address_handler() {
    // Allow both admin and frontend geocoding
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'ssb_geocode_nonce')) {
        wp_send_json_error(['message' => __('Security check failed', 'simple-shortlet-bookings')]);
    }
    
    $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    
    if (empty($address)) {
        wp_send_json_error(['message' => __('Please provide an address.', 'simple-shortlet-bookings')]);
    }
    
    // Use OpenStreetMap Nominatim API (free, no API key required)
    $encoded_address = urlencode($address);
    // Add addressdetails=0 for faster response
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . $encoded_address . '&limit=1&addressdetails=0';
    
    // Get site email for User-Agent (Nominatim requires contact info)
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    // Make request with proper User-Agent header (required by Nominatim)
    // Nominatim requires a proper User-Agent with contact information
    $user_agent = 'SimpleShortletBookings/1.0 (WordPress Plugin; Contact: ' . $admin_email . '; Site: ' . $site_name . ')';
    
    $args = [
        'timeout' => 15,
        'headers' => [
            'User-Agent' => $user_agent,
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US,en;q=0.9'
        ],
        'sslverify' => true,
        'redirection' => 5
    ];
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Log for debugging (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('SSB Geocode - URL: ' . $url);
        error_log('SSB Geocode - Response Code: ' . $response_code);
        error_log('SSB Geocode - Response Body: ' . substr($body, 0, 500));
    }
    
    // Check for HTTP errors
    if ($response_code !== 200) {
        $error_message = '';
        if ($response_code === 403) {
            $error_message = __('Geocoding service blocked the request (403). This may be due to rate limiting. Please wait a moment and try again, or enter coordinates manually.', 'simple-shortlet-bookings');
        } elseif ($response_code === 429) {
            $error_message = __('Too many requests to geocoding service. Please wait a few seconds and try again, or enter coordinates manually.', 'simple-shortlet-bookings');
        } else {
            $error_message = sprintf(__('Geocoding service returned error code %d. Please try again later, or enter coordinates manually.', 'simple-shortlet-bookings'), $response_code);
        }
        wp_send_json_error(['message' => $error_message, 'code' => $response_code]);
    }
    
    // Check if response is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => __('Invalid response from geocoding service. Please try again.', 'simple-shortlet-bookings')]);
    }
    
    // Check if data is empty or not an array
    if (!$data || !is_array($data) || empty($data)) {
        wp_send_json_error(['message' => __('Address not found. Please check the address and try again, or enter coordinates manually.', 'simple-shortlet-bookings')]);
    }
    
    $result = $data[0];
    $lat = isset($result['lat']) ? floatval($result['lat']) : null;
    $lon = isset($result['lon']) ? floatval($result['lon']) : null;
    
    // Try alternative field names if standard ones don't exist
    if ($lat === null && isset($result['latitude'])) {
        $lat = floatval($result['latitude']);
    }
    if ($lon === null && isset($result['longitude'])) {
        $lon = floatval($result['longitude']);
    }
    
    if ($lat === null || $lon === null || $lat === 0 || $lon === 0) {
        // Log the actual response for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SSB Geocode - Result structure: ' . print_r($result, true));
        }
        wp_send_json_error(['message' => __('Could not parse coordinates from response. Please try a more specific address.', 'simple-shortlet-bookings')]);
    }
    
    wp_send_json_success([
        'lat' => $lat,
        'lon' => $lon
    ]);
}
add_action('wp_ajax_ssb_geocode_address', 'ssb_geocode_address_handler');
add_action('wp_ajax_nopriv_ssb_geocode_address', 'ssb_geocode_address_handler');

/**
 * Save shortlet location meta
 */
function ssb_save_shortlet_location($post_id) {
    if (!isset($_POST['ssb_shortlet_location_nonce']) || !wp_verify_nonce($_POST['ssb_shortlet_location_nonce'], 'ssb_save_shortlet_location')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'shortlet') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        '_ssb_address' => 'ssb_address',
        '_ssb_latitude' => 'ssb_latitude',
        '_ssb_longitude' => 'ssb_longitude',
    ];

    foreach ($fields as $meta_key => $field_name) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
}
add_action('save_post', 'ssb_save_shortlet_location');

/**
 * Add pricing meta box for shortlets
 */
function ssb_add_shortlet_pricing_meta_box() {
    add_meta_box(
        'ssb_shortlet_pricing',
        __('Pricing & Rates', 'simple-shortlet-bookings'),
        'ssb_shortlet_pricing_meta_box_callback',
        'shortlet',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ssb_add_shortlet_pricing_meta_box');

function ssb_shortlet_pricing_meta_box_callback($post) {
    wp_nonce_field('ssb_save_shortlet_pricing', 'ssb_shortlet_pricing_nonce');
    
    $nightly_rate = get_post_meta($post->ID, '_ssb_nightly_rate', true);
    $weekly_rate = get_post_meta($post->ID, '_ssb_weekly_rate', true);
    $currency = get_post_meta($post->ID, '_ssb_currency', true) ?: 'USD';
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="ssb_currency"><?php esc_html_e('Currency', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <select name="ssb_currency" id="ssb_currency">
                    <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
                    <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
                    <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP (£)</option>
                    <option value="NGN" <?php selected($currency, 'NGN'); ?>>NGN (₦)</option>
                </select>
                <p class="description"><?php esc_html_e('Select the currency for this shortlet.', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ssb_nightly_rate"><?php esc_html_e('Nightly Rate', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <input type="number" name="ssb_nightly_rate" id="ssb_nightly_rate" value="<?php echo esc_attr($nightly_rate); ?>" step="0.01" min="0" class="regular-text">
                <p class="description"><?php esc_html_e('Price per night. Leave empty if not applicable.', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ssb_weekly_rate"><?php esc_html_e('Weekly Rate', 'simple-shortlet-bookings'); ?></label>
            </th>
            <td>
                <input type="number" name="ssb_weekly_rate" id="ssb_weekly_rate" value="<?php echo esc_attr($weekly_rate); ?>" step="0.01" min="0" class="regular-text">
                <p class="description"><?php esc_html_e('Price per week (7 nights). Leave empty if not applicable.', 'simple-shortlet-bookings'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save shortlet pricing meta
 */
function ssb_save_shortlet_pricing($post_id) {
    if (!isset($_POST['ssb_shortlet_pricing_nonce']) || !wp_verify_nonce($_POST['ssb_shortlet_pricing_nonce'], 'ssb_save_shortlet_pricing')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'shortlet') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        '_ssb_nightly_rate' => 'ssb_nightly_rate',
        '_ssb_weekly_rate' => 'ssb_weekly_rate',
        '_ssb_currency' => 'ssb_currency',
    ];

    foreach ($fields as $meta_key => $field_name) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            if ($meta_key === '_ssb_nightly_rate' || $meta_key === '_ssb_weekly_rate') {
                $value = floatval($value);
                update_post_meta($post_id, $meta_key, $value > 0 ? $value : '');
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        } else {
            if ($meta_key === '_ssb_currency') {
                update_post_meta($post_id, $meta_key, 'USD');
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}
add_action('save_post', 'ssb_save_shortlet_pricing');

/**
 * Add gallery meta box for shortlets
 */
function ssb_add_shortlet_gallery_meta_box() {
    add_meta_box(
        'ssb_shortlet_gallery',
        __('Shortlet Gallery Images', 'simple-shortlet-bookings'),
        'ssb_shortlet_gallery_meta_box_callback',
        'shortlet',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ssb_add_shortlet_gallery_meta_box');

function ssb_shortlet_gallery_meta_box_callback($post) {
    wp_nonce_field('ssb_save_shortlet_gallery', 'ssb_shortlet_gallery_nonce');
    
    $gallery_ids = get_post_meta($post->ID, '_ssb_gallery_ids', true);
    $gallery_ids = $gallery_ids ? explode(',', $gallery_ids) : [];
    $gallery_ids = array_filter(array_map('intval', $gallery_ids));
    
    ?>
    <div id="ssb-gallery-container">
        <input type="hidden" id="ssb-gallery-ids" name="ssb_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>">
        <div id="ssb-gallery-preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0;">
            <?php foreach ($gallery_ids as $img_id) : 
                $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                if ($img_url) :
            ?>
                <div class="ssb-gallery-item" data-id="<?php echo esc_attr($img_id); ?>" style="position: relative; width: 150px; height: 150px; border: 2px solid #ddd; border-radius: 4px; overflow: hidden;">
                    <img src="<?php echo esc_url($img_url); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" class="ssb-remove-image" data-id="<?php echo esc_attr($img_id); ?>" style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1;">×</button>
                </div>
            <?php 
                endif;
            endforeach; ?>
        </div>
        <button type="button" id="ssb-add-gallery-images" class="button button-secondary">
            <?php esc_html_e('Add Gallery Images', 'simple-shortlet-bookings'); ?>
        </button>
        <p class="description">
            <?php esc_html_e('Add multiple images for this shortlet. These will be displayed in a carousel on the listing and detail pages.', 'simple-shortlet-bookings'); ?>
        </p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var galleryFrame;
        var $container = $('#ssb-gallery-preview');
        var $input = $('#ssb-gallery-ids');

        $('#ssb-add-gallery-images').on('click', function(e) {
            e.preventDefault();

            if (galleryFrame) {
                galleryFrame.open();
                return;
            }

            galleryFrame = wp.media({
                title: '<?php echo esc_js(__('Select Gallery Images', 'simple-shortlet-bookings')); ?>',
                button: {
                    text: '<?php echo esc_js(__('Add Images', 'simple-shortlet-bookings')); ?>'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });

            galleryFrame.on('select', function() {
                var selection = galleryFrame.state().get('selection');
                var ids = $input.val() ? $input.val().split(',') : [];
                
                selection.each(function(attachment) {
                    var id = attachment.id;
                    if (ids.indexOf(id.toString()) === -1) {
                        ids.push(id);
                        var url = attachment.attributes.sizes && attachment.attributes.sizes.thumbnail ? 
                                  attachment.attributes.sizes.thumbnail.url : 
                                  attachment.attributes.url;
                        $container.append(
                            '<div class="ssb-gallery-item" data-id="' + id + '" style="position: relative; width: 150px; height: 150px; border: 2px solid #ddd; border-radius: 4px; overflow: hidden;">' +
                            '<img src="' + url + '" style="width: 100%; height: 100%; object-fit: cover;">' +
                            '<button type="button" class="ssb-remove-image" data-id="' + id + '" style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1;">×</button>' +
                            '</div>'
                        );
                    }
                });
                
                $input.val(ids.join(','));
            });

            galleryFrame.open();
        });

        $(document).on('click', '.ssb-remove-image', function() {
            var $item = $(this).closest('.ssb-gallery-item');
            var id = $(this).data('id');
            var ids = $input.val() ? $input.val().split(',') : [];
            ids = ids.filter(function(val) { return val != id; });
            $input.val(ids.join(','));
            $item.remove();
        });
    });
    </script>
    <?php
}

/**
 * Save shortlet gallery meta
 */
function ssb_save_shortlet_gallery($post_id) {
    if (!isset($_POST['ssb_shortlet_gallery_nonce']) || !wp_verify_nonce($_POST['ssb_shortlet_gallery_nonce'], 'ssb_save_shortlet_gallery')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'shortlet') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['ssb_gallery_ids'])) {
        $gallery_ids = sanitize_text_field($_POST['ssb_gallery_ids']);
        update_post_meta($post_id, '_ssb_gallery_ids', $gallery_ids);
    } else {
        delete_post_meta($post_id, '_ssb_gallery_ids');
    }
}
add_action('save_post', 'ssb_save_shortlet_gallery');
