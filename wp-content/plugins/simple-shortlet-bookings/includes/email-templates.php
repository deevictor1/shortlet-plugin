<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get HTML email template wrapper
 */
function ssb_get_email_template($content, $title = '') {
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html($title ?: $site_name); ?></title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f4;">
        <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f4f4; padding: 20px;">
            <tr>
                <td align="center">
                    <table role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
                        <!-- Header -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); padding: 30px 20px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                    <?php echo esc_html($site_name); ?>
                                </h1>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style="padding: 30px 20px;">
                                <?php echo $content; ?>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                    <?php echo esc_html($site_name); ?> | 
                                    <a href="<?php echo esc_url($site_url); ?>" style="color: #2563eb; text-decoration: none;"><?php echo esc_html($site_url); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Generate admin booking notification email
 */
function ssb_get_admin_booking_email($booking_data) {
    $shortlet_title = $booking_data['shortlet_title'];
    $guest_name = $booking_data['guest_name'];
    $guest_email = $booking_data['guest_email'];
    $guest_phone = $booking_data['guest_phone'] ?? '';
    $check_in = $booking_data['check_in'];
    $check_out = $booking_data['check_out'];
    $nights = $booking_data['nights'] ?? 0;
    $total_price = $booking_data['total_price'] ?? 0;
    $currency = $booking_data['currency'] ?? 'USD';
    $booking_id = $booking_data['booking_id'] ?? '';
    
    $content = '
        <h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px;">' . esc_html__('New Booking Received', 'simple-shortlet-bookings') . '</h2>
        
        <div style="background-color: #f9fafb; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <p style="margin: 0 0 10px 0; color: #374151; font-size: 16px; font-weight: 600;">' . esc_html__('Booking Details', 'simple-shortlet-bookings') . '</p>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px; width: 40%;">' . esc_html__('Shortlet', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px; font-weight: 600;">' . esc_html($shortlet_title) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Guest Name', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html($guest_name) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Email', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">
                    <a href="mailto:' . esc_attr($guest_email) . '" style="color: #2563eb; text-decoration: none;">' . esc_html($guest_email) . '</a>
                </td>
            </tr>';
    
    if ($guest_phone) {
        $content .= '
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Phone', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">
                    <a href="tel:' . esc_attr($guest_phone) . '" style="color: #2563eb; text-decoration: none;">' . esc_html($guest_phone) . '</a>
                </td>
            </tr>';
    }
    
    $content .= '
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Check-in', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html(date_i18n(get_option('date_format'), strtotime($check_in))) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Check-out', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html(date_i18n(get_option('date_format'), strtotime($check_out))) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Nights', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html($nights) . '</td>
            </tr>';
    
    if ($total_price > 0) {
        $content .= '
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Total Price', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 16px; font-weight: 600;">' . esc_html(ssb_format_currency($total_price, $currency)) . '</td>
            </tr>';
    }
    
    $content .= '
        </table>';
    
    if ($booking_id) {
        $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
        $content .= '
            <div style="text-align: center; margin-top: 30px;">
                <a href="' . esc_url($edit_link) . '" style="display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">' . esc_html__('View Booking in Admin', 'simple-shortlet-bookings') . '</a>
            </div>';
    }
    
    return ssb_get_email_template($content, __('New Booking Notification', 'simple-shortlet-bookings'));
}

/**
 * Generate guest confirmation email
 */
function ssb_get_guest_confirmation_email($booking_data) {
    $shortlet_title = $booking_data['shortlet_title'];
    $guest_name = $booking_data['guest_name'];
    $check_in = $booking_data['check_in'];
    $check_out = $booking_data['check_out'];
    $nights = $booking_data['nights'] ?? 0;
    $total_price = $booking_data['total_price'] ?? 0;
    $currency = $booking_data['currency'] ?? 'USD';
    $status = $booking_data['status'] ?? 'pending';
    
    $status_text = [
        'pending' => __('Pending Confirmation', 'simple-shortlet-bookings'),
        'approved' => __('Confirmed', 'simple-shortlet-bookings'),
        'rejected' => __('Rejected', 'simple-shortlet-bookings'),
        'cancelled' => __('Cancelled', 'simple-shortlet-bookings')
    ];
    
    $status_color = [
        'pending' => '#f59e0b',
        'approved' => '#10b981',
        'rejected' => '#ef4444',
        'cancelled' => '#ef4444'
    ];
    
    $content = '
        <h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px;">' . esc_html__('Booking Confirmation', 'simple-shortlet-bookings') . '</h2>
        
        <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.6;">
            ' . sprintf(esc_html__('Hello %s,', 'simple-shortlet-bookings'), esc_html($guest_name)) . '<br><br>
            ' . esc_html__('Thank you for your booking request. We have received your reservation and will process it shortly.', 'simple-shortlet-bookings') . '
        </p>
        
        <div style="background-color: #f9fafb; border-left: 4px solid ' . esc_attr($status_color[$status] ?? '#2563eb') . '; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <p style="margin: 0; color: #374151; font-size: 14px;">
                <strong>' . esc_html__('Status:', 'simple-shortlet-bookings') . '</strong> 
                <span style="color: ' . esc_attr($status_color[$status] ?? '#2563eb') . '; font-weight: 600;">' . esc_html($status_text[$status] ?? $status) . '</span>
            </p>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px; width: 40%;">' . esc_html__('Shortlet', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px; font-weight: 600;">' . esc_html($shortlet_title) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Check-in', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html(date_i18n(get_option('date_format'), strtotime($check_in))) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Check-out', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . esc_html(date_i18n(get_option('date_format'), strtotime($check_out))) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Duration', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 14px;">' . sprintf(esc_html(_n('%d night', '%d nights', $nights, 'simple-shortlet-bookings')), $nights) . '</td>
            </tr>';
    
    if ($total_price > 0) {
        $content .= '
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">' . esc_html__('Total Price', 'simple-shortlet-bookings') . ':</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 16px; font-weight: 600;">' . esc_html(ssb_format_currency($total_price, $currency)) . '</td>
            </tr>';
    }
    
    $content .= '
        </table>
        
        <p style="margin: 20px 0 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
            ' . esc_html__('We will contact you shortly to confirm your booking. If you have any questions, please don\'t hesitate to reach out.', 'simple-shortlet-bookings') . '
        </p>';
    
    return ssb_get_email_template($content, __('Booking Confirmation', 'simple-shortlet-bookings'));
}

/**
 * Send HTML email
 */
function ssb_send_html_email($to, $subject, $html_content, $headers = []) {
    $default_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    ];
    
    $headers = array_merge($default_headers, $headers);
    
    // Ensure PHPMailer sends HTML content properly
    add_action('phpmailer_init', function($phpmailer) use ($html_content) {
        $phpmailer->isHTML(true);
        // Ensure the body is set (sometimes wp_mail doesn't pass it correctly)
        if (!empty($html_content)) {
            $phpmailer->Body = $html_content;
            // Create plain text fallback
            $phpmailer->AltBody = wp_strip_all_tags($html_content);
        }
    }, 999);
    
    $result = wp_mail($to, $subject, $html_content, $headers);
    
    // Remove the action to avoid conflicts with other emails
    remove_all_actions('phpmailer_init', 999);
    
    return $result;
}

