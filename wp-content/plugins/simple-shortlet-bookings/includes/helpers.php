<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if a date range is available for a given shortlet
 */
function ssb_is_range_available($shortlet_id, $check_in, $check_out) {
    $check_in  = sanitize_text_field($check_in);
    $check_out = sanitize_text_field($check_out);

    $new_start = strtotime($check_in);
    $new_end   = strtotime($check_out);

    if (!$new_start || !$new_end || $new_end <= $new_start) {
        return false;
    }

    $args = [
        'post_type'      => 'shortlet_booking',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => '_shortlet_id',
                'value' => $shortlet_id,
            ],
            [
                'key'     => '_status',
                'value'   => 'cancelled',
                'compare' => '!=',
            ],
        ],
        'fields' => 'ids',
    ];

    $bookings = get_posts($args);

    foreach ($bookings as $booking_id) {
        $start = strtotime(get_post_meta($booking_id, '_check_in', true));
        $end   = strtotime(get_post_meta($booking_id, '_check_out', true));

        // Overlap: new_start < existing_end AND new_end > existing_start
        if ($new_start < $end && $new_end > $start) {
            return false;
        }
    }

    return true;
}

/**
 * Get the best available image URL for a given attachment ID
 * Tries preferred size first, then falls back to larger sizes if needed
 * 
 * @param int $attachment_id The attachment ID
 * @param string $preferred_size Preferred image size (e.g., 'large', 'medium')
 * @return string|false Image URL or false if not found
 */
function ssb_get_best_image_url($attachment_id, $preferred_size = 'large') {
    if (!$attachment_id) {
        return false;
    }
    
    // Try preferred size first
    $url = wp_get_attachment_image_url($attachment_id, $preferred_size);
    if ($url) {
        return $url;
    }
    
    // Fallback order: full -> large -> medium -> thumbnail
    $fallback_sizes = ['full', 'large', 'medium', 'thumbnail'];
    
    foreach ($fallback_sizes as $size) {
        if ($size === $preferred_size) {
            continue; // Already tried
        }
        $url = wp_get_attachment_image_url($attachment_id, $size);
        if ($url) {
            return $url;
        }
    }
    
    // Last resort: get original file URL
    $url = wp_get_attachment_url($attachment_id);
    return $url ? $url : false;
}

/**
 * Calculate booking total price based on dates and shortlet rates
 * 
 * @param int $shortlet_id The shortlet ID
 * @param string $check_in Check-in date (Y-m-d format)
 * @param string $check_out Check-out date (Y-m-d format)
 * @return array Array with 'total', 'nights', 'rate_type', 'currency', 'breakdown'
 */
function ssb_calculate_booking_price($shortlet_id, $check_in, $check_out) {
    $nightly_rate = floatval(get_post_meta($shortlet_id, '_ssb_nightly_rate', true));
    $weekly_rate = floatval(get_post_meta($shortlet_id, '_ssb_weekly_rate', true));
    $currency = get_post_meta($shortlet_id, '_ssb_currency', true) ?: 'USD';
    
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $nights = $check_in_date->diff($check_out_date)->days;
    
    $total = 0;
    $rate_type = 'nightly';
    $breakdown = [];
    
    if ($nights <= 0) {
        return [
            'total' => 0,
            'nights' => 0,
            'rate_type' => 'nightly',
            'currency' => $currency,
            'breakdown' => []
        ];
    }
    
    // Calculate using weekly rate if available and applicable
    if ($weekly_rate > 0 && $nights >= 7) {
        $weeks = floor($nights / 7);
        $remaining_nights = $nights % 7;
        
        if ($weeks > 0) {
            $weekly_total = $weeks * $weekly_rate;
            $total += $weekly_total;
            $breakdown[] = [
                'type' => 'weekly',
                'quantity' => $weeks,
                'rate' => $weekly_rate,
                'subtotal' => $weekly_total
            ];
        }
        
        // Use nightly rate for remaining nights
        if ($remaining_nights > 0 && $nightly_rate > 0) {
            $nightly_total = $remaining_nights * $nightly_rate;
            $total += $nightly_total;
            $breakdown[] = [
                'type' => 'nightly',
                'quantity' => $remaining_nights,
                'rate' => $nightly_rate,
                'subtotal' => $nightly_total
            ];
            $rate_type = 'mixed';
        } else {
            $rate_type = 'weekly';
        }
    } elseif ($nightly_rate > 0) {
        // Use only nightly rate
        $total = $nights * $nightly_rate;
        $breakdown[] = [
            'type' => 'nightly',
            'quantity' => $nights,
            'rate' => $nightly_rate,
            'subtotal' => $total
        ];
        $rate_type = 'nightly';
    }
    
    return [
        'total' => round($total, 2),
        'nights' => $nights,
        'rate_type' => $rate_type,
        'currency' => $currency,
        'breakdown' => $breakdown
    ];
}

/**
 * Format currency amount
 */
function ssb_format_currency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'NGN' => '₦'
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}
