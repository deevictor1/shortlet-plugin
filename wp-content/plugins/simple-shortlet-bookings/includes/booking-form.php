<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [shortlet_booking_form]
 * Use inside a Shortlet post content.
 */
function ssb_booking_form_shortcode()
{
    if (!is_singular('shortlet')) {
        return '<p>' . esc_html__('Booking form can only be used on a shortlet page.', 'simple-shortlet-bookings') . '</p>';
    }

    global $post;
    $shortlet_id = $post->ID;
    $output = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ssb_booking_nonce'])) {
        if (!wp_verify_nonce($_POST['ssb_booking_nonce'], 'ssb_booking')) {
            $output .= '<div class="ssb-message ssb-message-error" data-swal-type="error" data-swal-message="' . esc_attr__('Security check failed. Please reload the page and try again.', 'simple-shortlet-bookings') . '" style="display:none;"></div>';
        } else {
            $name = sanitize_text_field($_POST['ssb_name'] ?? '');
            $email = sanitize_email($_POST['ssb_email'] ?? '');
            $phone = sanitize_text_field($_POST['ssb_phone'] ?? '');
            $check_in = sanitize_text_field($_POST['ssb_check_in'] ?? '');
            $check_out = sanitize_text_field($_POST['ssb_check_out'] ?? '');

            if ($name && $email && $phone && $check_in && $check_out) {
                if (ssb_is_range_available($shortlet_id, $check_in, $check_out)) {
                    $booking_id = wp_insert_post([
                        'post_type' => 'shortlet_booking',
                        'post_title' => 'Booking for ' . get_the_title($shortlet_id),
                        'post_status' => 'publish',
                    ]);

                    if ($booking_id) {
                        // Calculate price
                        $price_info = ssb_calculate_booking_price($shortlet_id, $check_in, $check_out);

                        update_post_meta($booking_id, '_shortlet_id', $shortlet_id);
                        update_post_meta($booking_id, '_guest_name', $name);
                        update_post_meta($booking_id, '_guest_email', $email);
                        update_post_meta($booking_id, '_guest_phone', $phone);
                        update_post_meta($booking_id, '_check_in', $check_in);
                        update_post_meta($booking_id, '_check_out', $check_out);
                        update_post_meta($booking_id, '_status', 'pending');
                        update_post_meta($booking_id, '_total_price', $price_info['total']);
                        update_post_meta($booking_id, '_price_breakdown', $price_info['breakdown']);
                        update_post_meta($booking_id, '_nights', $price_info['nights']);
                        
                        // Get and save currency
                        $currency = get_post_meta($shortlet_id, '_ssb_currency', true) ?: 'USD';
                        update_post_meta($booking_id, '_currency', $currency);

                        // Send emails
                        $booking_data = [
                            'booking_id' => $booking_id,
                            'shortlet_title' => get_the_title($shortlet_id),
                            'guest_name' => $name,
                            'guest_email' => $email,
                            'guest_phone' => $phone,
                            'check_in' => $check_in,
                            'check_out' => $check_out,
                            'nights' => $price_info['nights'],
                            'total_price' => $price_info['total'],
                            'currency' => $currency,
                            'status' => 'pending'
                        ];

                        // Send admin email
                        $admin_email = get_option('admin_email');
                        $admin_subject = sprintf(__('New booking for %s', 'simple-shortlet-bookings'), get_the_title($shortlet_id));
                        $admin_html = ssb_get_admin_booking_email($booking_data);
                        ssb_send_html_email($admin_email, $admin_subject, $admin_html);

                        // Send guest email
                        $guest_subject = sprintf(__('Booking Confirmation - %s', 'simple-shortlet-bookings'), get_the_title($shortlet_id));
                        $guest_html = ssb_get_guest_confirmation_email($booking_data);
                        ssb_send_html_email($email, $guest_subject, $guest_html);

                        // Store success message in data attribute for SweetAlert
                        $success_message = __('Booking request submitted successfully! We will contact you soon.', 'simple-shortlet-bookings');
                        $output .= '<div class="ssb-message ssb-message-success" data-swal-type="success" data-swal-message="' . esc_attr($success_message) . '" style="display:none;"></div>';
                    } else {
                        $output .= '<div class="ssb-message ssb-message-error" data-swal-type="error" data-swal-message="' . esc_attr__('Error creating booking. Please try again.', 'simple-shortlet-bookings') . '" style="display:none;"></div>';
                    }
                } else {
                    $output .= '<div class="ssb-message ssb-message-error" data-swal-type="error" data-swal-message="' . esc_attr__('Selected dates are not available. Please choose different dates.', 'simple-shortlet-bookings') . '" style="display:none;"></div>';
                }
            } else {
                $output .= '<div class="ssb-message ssb-message-error" data-swal-type="error" data-swal-message="' . esc_attr__('Please fill in all required fields.', 'simple-shortlet-bookings') . '" style="display:none;"></div>';
            }
        }
    }

    ob_start();
    ?>
    <!-- add flatpickr CSS/JS (CDN) and the calendar + inputs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <form method="post" class="ssb-booking-form">
        <?php wp_nonce_field('ssb_booking', 'ssb_booking_nonce'); ?>

        <div class="ssb-form-group">
            <label><?php esc_html_e('Name', 'simple-shortlet-bookings'); ?> *</label>
            <div class="ssb-input-wrapper">
                <span class="ssb-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </span>
                <input type="text" name="ssb_name" required
                    placeholder="<?php esc_attr_e('Your full name', 'simple-shortlet-bookings'); ?>">
            </div>
        </div>

        <div class="ssb-form-group">
            <label><?php esc_html_e('Email', 'simple-shortlet-bookings'); ?> *</label>
            <div class="ssb-input-wrapper">
                <span class="ssb-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </span>
                <input type="email" name="ssb_email" required
                    placeholder="<?php esc_attr_e('your@email.com', 'simple-shortlet-bookings'); ?>">
            </div>
        </div>

        <div class="ssb-form-group">
            <label><?php esc_html_e('Phone', 'simple-shortlet-bookings'); ?> *</label>
            <div class="ssb-input-wrapper">
                <span class="ssb-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
                </span>
                <input type="text" name="ssb_phone" required
                    placeholder="08023456789">
            </div>
        </div>

        <!-- calendar picker (select range here) -->
        <div class="ssb-form-group">
            <label><?php esc_html_e('Select dates', 'simple-shortlet-bookings'); ?> *</label>
            <div class="ssb-input-wrapper">
                <span class="ssb-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </span>
                <input type="text" id="ssb-date-picker-<?php echo esc_attr($shortlet_id); ?>" class="ssb-date-picker"
                    data-shortlet-id="<?php echo esc_attr($shortlet_id); ?>"
                    placeholder="<?php esc_attr_e('Check-in → Check-out', 'simple-shortlet-bookings'); ?>" readonly>
            </div>
        </div>

        <!-- Hidden inputs populated by the calendar picker -->
        <input type="hidden" name="ssb_check_in" required>
        <input type="hidden" name="ssb_check_out" required>

        <!-- Pricing Display -->
        <?php
        $nightly_rate = get_post_meta($shortlet_id, '_ssb_nightly_rate', true);
        $weekly_rate = get_post_meta($shortlet_id, '_ssb_weekly_rate', true);
        $currency = get_post_meta($shortlet_id, '_ssb_currency', true) ?: 'USD';
        if ($nightly_rate || $weekly_rate):
            ?>
            <div id="ssb-pricing-display"
                style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border-radius: 8px; border-left: 4px solid #2563eb; display: none;">
                <p style="margin: 0 0 10px 0; font-weight: 600; color: #111827;">
                    <?php esc_html_e('Booking Summary', 'simple-shortlet-bookings'); ?></p>
                <div id="ssb-pricing-details" style="color: #374151; font-size: 14px;">
                    <!-- Pricing will be calculated and displayed here -->
                </div>
                <div id="ssb-total-price"
                    style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 18px; font-weight: 600; color: #111827;">
                    <!-- Total will be displayed here -->
                </div>
            </div>
        <?php endif; ?>

        <p>
            <button type="submit"><?php esc_html_e('Request Booking', 'simple-shortlet-bookings'); ?></button>
        </p>
    </form>

    <?php
    // Build JavaScript as a single string to avoid wpautop issues
    $shortlet_id_js = json_encode((int) $shortlet_id);
    $ajax_url_js = json_encode(admin_url('admin-ajax.php'));
    $nightly_rate_js = $nightly_rate ? json_encode(floatval($nightly_rate)) : '0';
    $weekly_rate_js = $weekly_rate ? json_encode(floatval($weekly_rate)) : '0';
    $currency_js = json_encode($currency);
    $weeks_label = json_encode(__('Week(s)', 'simple-shortlet-bookings'));
    $nights_label = json_encode(__('Night(s)', 'simple-shortlet-bookings'));
    $total_label = json_encode(__('Total:', 'simple-shortlet-bookings'));

    $picker_id = 'ssb-date-picker-' . $shortlet_id;
    // Build JavaScript code - all on one line to prevent wpautop from breaking it
    $js_code = "(function(){var pickerId='" . $picker_id . "';var shortletId=" . $shortlet_id_js . ";var ajaxUrl=" . $ajax_url_js . ";var nightlyRate=" . $nightly_rate_js . ";var weeklyRate=" . $weekly_rate_js . ";var currency=" . $currency_js . ";var weeksLabel=" . $weeks_label . ";var nightsLabel=" . $nights_label . ";var totalLabel=" . $total_label . ";function initFlatpickr(){if(typeof flatpickr==='undefined'){setTimeout(initFlatpickr,200);return;}var pickerEl=document.getElementById(pickerId);if(!pickerEl)return;function fetchBookedRanges(cb){var url=ajaxUrl+'?action=ssb_get_booked_dates&shortlet_id='+shortletId;fetch(url,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(res){if(res&&res.success&&Array.isArray(res.data)){cb(null,res.data);}else{cb(new Error('No data'));}}).catch(function(err){cb(err);});}fetchBookedRanges(function(err,ranges){var disabled=[];if(!err&&ranges.length){ranges.forEach(function(range){var start=new Date(range.start);var end=new Date(range.end);for(var d=new Date(start);d<=end;d.setDate(d.getDate()+1)){disabled.push(new Date(d).toISOString().slice(0,10));}});}var currencySymbols={'USD':'$','EUR':'€','GBP':'£','NGN':'₦'};var currencySymbol=currencySymbols[currency]||currency+' ';function calculatePrice(checkIn,checkOut){if(!checkIn||!checkOut)return null;var start=new Date(checkIn);var end=new Date(checkOut);var nights=Math.ceil((end-start)/(1000*60*60*24));if(nights<=0)return null;var total=0;var breakdown=[];if(weeklyRate>0&&nights>=7){var weeks=Math.floor(nights/7);var remainingNights=nights%7;if(weeks>0){var weeklyTotal=weeks*weeklyRate;total+=weeklyTotal;breakdown.push({type:'weekly',quantity:weeks,rate:weeklyRate,subtotal:weeklyTotal});}if(remainingNights>0&&nightlyRate>0){var nightlyTotal=remainingNights*nightlyRate;total+=nightlyTotal;breakdown.push({type:'nightly',quantity:remainingNights,rate:nightlyRate,subtotal:nightlyTotal});}}else if(nightlyRate>0){total=nights*nightlyRate;breakdown.push({type:'nightly',quantity:nights,rate:nightlyRate,subtotal:total});}return{total:Math.round(total*100)/100,nights:nights,breakdown:breakdown};}function updatePricingDisplay(priceInfo){var pricingDisplay=document.getElementById('ssb-pricing-display');var pricingDetails=document.getElementById('ssb-pricing-details');var totalPrice=document.getElementById('ssb-total-price');if(!pricingDisplay||!priceInfo)return;if(priceInfo.total>0){pricingDisplay.style.display='block';var detailsHtml='';if(priceInfo.breakdown.length>0){priceInfo.breakdown.forEach(function(item){var typeLabel=item.type==='weekly'?weeksLabel:nightsLabel;detailsHtml+='<div style=\"margin-bottom:5px;\">';detailsHtml+=currencySymbol+item.rate.toFixed(2)+' × '+item.quantity+' '+typeLabel;detailsHtml+=' = '+currencySymbol+item.subtotal.toFixed(2);detailsHtml+='</div>';});}pricingDetails.innerHTML=detailsHtml;totalPrice.innerHTML=totalLabel+' '+currencySymbol+priceInfo.total.toFixed(2);}else{pricingDisplay.style.display='none';}}flatpickr('#'+pickerId,{mode:'range',dateFormat:'Y-m-d',disable:disabled,onChange:function(selectedDates,dateStr,instance){if(selectedDates.length===2){var inStr=instance.formatDate(selectedDates[0],'Y-m-d');var outStr=instance.formatDate(selectedDates[1],'Y-m-d');var inField=document.querySelector('input[name=\"ssb_check_in\"]');var outField=document.querySelector('input[name=\"ssb_check_out\"]');if(inField)inField.value=inStr;if(outField)outField.value=outStr;var priceInfo=calculatePrice(inStr,outStr);updatePricingDisplay(priceInfo);}else{var pricingDisplay=document.getElementById('ssb-pricing-display');if(pricingDisplay)pricingDisplay.style.display='none';}}});});}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',function(){setTimeout(initFlatpickr,300);});}else{setTimeout(initFlatpickr,300);}})();";
    // Output script without any line breaks to prevent wpautop from processing it
    echo '<script type="text/javascript">' . $js_code . '</script>';
    
    // Add SweetAlert2 notification handler - check for messages and show alerts
    // Use a data attribute approach to pass the picker ID safely
    ?>
    <script type="text/javascript">
    (function() {
        function showSwalAlert() {
            if (typeof Swal === 'undefined') {
                setTimeout(showSwalAlert, 200);
                return;
            }
            var successMsg = document.querySelector('.ssb-message-success[data-swal-type]');
            var errorMsg = document.querySelector('.ssb-message-error[data-swal-type]');
            
            if (successMsg) {
                var msg = successMsg.getAttribute('data-swal-message');
                if (msg) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: msg,
                        confirmButtonColor: '#2563eb',
                        confirmButtonText: 'OK',
                        width: '500px',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function() {
                        successMsg.remove();
                        var form = document.querySelector('.ssb-booking-form');
                        if (form) {
                            form.reset();
                            var pickerId = <?php echo json_encode($picker_id); ?>;
                            var datePicker = document.getElementById(pickerId);
                            if (datePicker && typeof flatpickr !== 'undefined') {
                                try {
                                    var fp = flatpickr('#' + pickerId);
                                    if (fp && fp.clear) {
                                        fp.clear();
                                    }
                                } catch(e) {}
                            }
                            var pricingDisplay = document.getElementById('ssb-pricing-display');
                            if (pricingDisplay) {
                                pricingDisplay.style.display = 'none';
                            }
                        }
                    });
                } 
            }
            
            if (errorMsg) {
                var msg = errorMsg.getAttribute('data-swal-message');
                if (msg) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'OK',
                        width: '500px',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function() {
                        errorMsg.remove();
                    });
                }
            }
            
            // Removed console.log for production
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(showSwalAlert, 800);
        });
        } else {
            setTimeout(showSwalAlert, 800);
        }
    })();
    </script>
    <?php
    
    $form_html = ob_get_clean();

    // Clean up any <p> tags that wpautop might have added around script tags
    $form_html = preg_replace('/<p>\s*<script[^>]*>/i', '<script', $form_html);
    $form_html = preg_replace('/<\/script>\s*<\/p>/i', '</script>', $form_html);

    return $output . $form_html;
}
add_shortcode('shortlet_booking_form', 'ssb_booking_form_shortcode');


add_action('wp_ajax_nopriv_ssb_get_booked_dates', 'ssb_get_booked_dates');
add_action('wp_ajax_ssb_get_booked_dates', 'ssb_get_booked_dates');

function ssb_get_booked_dates()
{
    $shortlet_id = isset($_GET['shortlet_id']) ? (int) $_GET['shortlet_id'] : 0;
    if (!$shortlet_id) {
        wp_send_json_error('missing shortlet_id', 400);
    }

    $args = [
        'post_type' => 'shortlet_booking',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_shortlet_id',
                'value' => $shortlet_id,
                'compare' => '=',
            ],
            [
                'key' => '_status',
                'value' => 'cancelled',
                'compare' => '!=',
            ],
        ],
    ];

    $posts = get_posts($args);
    $ranges = [];

    foreach ($posts as $p) {
        $start = get_post_meta($p->ID, '_check_in', true);
        $end = get_post_meta($p->ID, '_check_out', true);
        if ($start && $end) {
            $ranges[] = [
                'start' => date('Y-m-d', strtotime($start)),
                'end' => date('Y-m-d', strtotime($end)),
            ];
        }
    }

    wp_send_json_success($ranges);
}
