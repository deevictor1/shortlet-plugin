<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Shortlet post type
 */
function ssb_register_shortlet_cpt()
{
    $labels = [
        'name' => __('Shortlets', 'simple-shortlet-bookings'),
        'singular_name' => __('Shortlet', 'simple-shortlet-bookings'),
        'add_new' => __('Add New Shortlet', 'simple-shortlet-bookings'),
        'add_new_item' => __('Add New Shortlet', 'simple-shortlet-bookings'),
        'edit_item' => __('Edit Shortlet', 'simple-shortlet-bookings'),
        'new_item' => __('New Shortlet', 'simple-shortlet-bookings'),
        'view_item' => __('View Shortlet', 'simple-shortlet-bookings'),
        'search_items' => __('Search Shortlets', 'simple-shortlet-bookings'),
        'not_found' => __('No shortlets found', 'simple-shortlet-bookings'),
        'not_found_in_trash' => __('No shortlets found in trash', 'simple-shortlet-bookings'),
        'menu_name' => __('Shortlets', 'simple-shortlet-bookings'),
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'shortlets'],
        'menu_icon' => 'dashicons-admin-home',
    ];

    register_post_type('shortlet', $args);
}
add_action('init', 'ssb_register_shortlet_cpt');

/**
 * Register Shortlet Booking post type
 */
function ssb_register_booking_cpt()
{
    $labels = [
        'name' => __('Shortlet Bookings', 'simple-shortlet-bookings'),
        'singular_name' => __('Shortlet Booking', 'simple-shortlet-bookings'),
        'add_new' => __('Add Booking', 'simple-shortlet-bookings'),
        'add_new_item' => __('Add New Booking', 'simple-shortlet-bookings'),
        'edit_item' => __('Edit Booking', 'simple-shortlet-bookings'),
        'new_item' => __('New Booking', 'simple-shortlet-bookings'),
        'view_item' => __('View Booking', 'simple-shortlet-bookings'),
        'search_items' => __('Search Bookings', 'simple-shortlet-bookings'),
        'not_found' => __('No bookings found', 'simple-shortlet-bookings'),
        'not_found_in_trash' => __('No bookings found in trash', 'simple-shortlet-bookings'),
        'menu_name' => __('Shortlet Bookings', 'simple-shortlet-bookings'),
    ];

    $args = [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-calendar-alt',
    ];

    register_post_type('shortlet_booking', $args);
}
add_action('init', 'ssb_register_booking_cpt');

/**
 * Hide featured image on single shortlet pages (we use carousel instead)
 */
function ssb_hide_single_shortlet_featured_image($html, $post_id, $post_thumbnail_id, $size, $attr)
{
    if (is_singular('shortlet') && get_post_type($post_id) === 'shortlet') {
        return '';
    }
    return $html;
}
add_filter('post_thumbnail_html', 'ssb_hide_single_shortlet_featured_image', 10, 5);

/**
 * Remove wpautop from shortlet content to prevent breaking our HTML structure
 */
function ssb_remove_wpautop_for_shortlets($content) {
    if (is_singular('shortlet') && in_the_loop() && is_main_query()) {
        remove_filter('the_content', 'wpautop');
    }
    return $content;
}
add_filter('the_content', 'ssb_remove_wpautop_for_shortlets', 1);

/**
 * Filter single shortlet content to add beautiful details page layout
 */
function ssb_single_shortlet_gallery($content)
{
    if (!is_singular('shortlet') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    global $post;
    $shortlet_id = $post->ID;

    $gallery_ids = get_post_meta($shortlet_id, '_ssb_gallery_ids', true);
    $gallery_ids = $gallery_ids ? explode(',', $gallery_ids) : [];
    $gallery_ids = array_filter(array_map('intval', $gallery_ids));

    $all_images = [];
    if (has_post_thumbnail()) {
        $all_images[] = get_post_thumbnail_id();
    }
    foreach ($gallery_ids as $img_id) {
        if (!in_array($img_id, $all_images)) {
            $all_images[] = $img_id;
        }
    }

    $nightly_rate = get_post_meta($shortlet_id, '_ssb_nightly_rate', true);
    $weekly_rate = get_post_meta($shortlet_id, '_ssb_weekly_rate', true);
    $currency = get_post_meta($shortlet_id, '_ssb_currency', true) ?: 'USD';
    $address = get_post_meta($shortlet_id, '_ssb_address', true);
    $latitude = get_post_meta($shortlet_id, '_ssb_latitude', true);
    $longitude = get_post_meta($shortlet_id, '_ssb_longitude', true);

    ob_start();
    ?>
    <div class="ssb-single-shortlet-wrapper">
        <!-- Hero Gallery Section - Constrained Width -->
        <?php if (!empty($all_images)): ?>
            <div class="ssb-single-gallery-container">
                <div class="ssb-single-gallery">
                    <div class="ssb-carousel" data-carousel-id="single-shortlet-<?php echo esc_attr($shortlet_id); ?>"
                        data-autoplay="false">
                        <div class="ssb-carousel-container">
                            <?php foreach ($all_images as $index => $img_id):
                                $img_url = wp_get_attachment_image_url($img_id, 'full');
                                ?>
                                <div class="ssb-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo esc_url($img_url); ?>"
                                        srcset="<?php echo esc_attr(wp_get_attachment_image_srcset($img_id, 'full')); ?>"
                                        sizes="(max-width: 1280px) 100vw, 1280px" alt="<?php echo esc_attr(get_the_title()); ?>"
                                        loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($all_images) > 1): ?>
                            <button class="ssb-carousel-prev"
                                aria-label="<?php esc_attr_e('Previous image', 'simple-shortlet-bookings'); ?>">‹</button>
                            <button class="ssb-carousel-next"
                                aria-label="<?php esc_attr_e('Next image', 'simple-shortlet-bookings'); ?>">›</button>
                            <div class="ssb-carousel-dots">
                                <?php foreach ($all_images as $index => $img_id): ?>
                                    <button class="ssb-carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>"
                                        data-slide="<?php echo esc_attr($index); ?>"
                                        aria-label="<?php echo esc_attr(sprintf(__('Go to slide %d', 'simple-shortlet-bookings'), $index + 1)); ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content Area -->
        <div class="ssb-single-content-wrapper" style="max-width: 1280px !important; margin: 0 auto !important; padding: 0 1.5rem 4rem !important; width: 100% !important; box-sizing: border-box !important;">
            <!-- Full Width Content Section -->
            <div class="ssb-single-main-content">
                <!-- Title Header -->
                <div class="ssb-single-header">
                    <h1 class="ssb-single-title"><?php the_title(); ?></h1>
                    <?php if ($address): ?>
                        <div class="ssb-single-location-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span><?php echo esc_html($address); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pricing Display -->
                <?php if ($nightly_rate): ?>
                    <div class="ssb-price-display">
                        <span
                            class="ssb-price-amount"><?php echo esc_html(ssb_format_currency(floatval($nightly_rate), $currency)); ?></span>
                        <span class="ssb-price-unit"><?php esc_html_e('per night', 'simple-shortlet-bookings'); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Content (remove booking form shortcode if present) -->
                <div class="ssb-single-description">
                    <?php
                    // Remove booking form shortcode from content to avoid duplication
                    $clean_content = preg_replace('/\[shortlet_booking_form[^\]]*\]/', '', $content);
                    echo $clean_content;
                    ?>
                </div>
            </div>

            <!-- Form and Map Section - Side by Side -->
            <div class="ssb-form-map-wrapper" style="display: grid !important; gap: 3rem !important; align-items: start !important; margin-top: 3rem !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important;">
                <!-- Booking Form - 2/3 width -->
                <div class="ssb-booking-section" style="margin-top: 0; padding-top: 0; border-top: none; width: 100%; box-sizing: border-box;">
                    <h2 class="ssb-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?php esc_html_e('Book this property', 'simple-shortlet-bookings'); ?>
                    </h2>
                    <?php echo do_shortcode('[shortlet_booking_form]'); ?>
                </div>

                <!-- Map - 1/3 width -->
                <div class="ssb-map-section" style="position: sticky; top: 2rem; align-self: start; width: 100%; box-sizing: border-box;">
                    <?php if ($address || ($latitude && $longitude)): ?>
                        <div class="ssb-location-section">
                            <h2 class="ssb-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php esc_html_e('Location', 'simple-shortlet-bookings'); ?>
                            </h2>
                            <?php if ($address): ?>
                                <div class="ssb-address">
                                    <div class="ssb-address-label"><?php esc_html_e('Address', 'simple-shortlet-bookings'); ?></div>
                                    <div class="ssb-address-value"><?php echo esc_html($address); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($latitude && $longitude): ?>
                                <div class="ssb-map-container" id="ssb-map-<?php echo esc_attr($shortlet_id); ?>"
                                    data-lat="<?php echo esc_attr($latitude); ?>" data-lng="<?php echo esc_attr($longitude); ?>"
                                    data-address="<?php echo esc_attr($address); ?>">
                                    <div class="ssb-map-loading"><?php esc_html_e('Loading map...', 'simple-shortlet-bookings'); ?>
                                    </div>
                                </div>
                            <?php elseif ($address): ?>
                                <div class="ssb-map-container" id="ssb-map-<?php echo esc_attr($shortlet_id); ?>"
                                    data-address="<?php echo esc_attr($address); ?>" data-geocode="true">
                                    <div class="ssb-map-loading"><?php esc_html_e('Loading map...', 'simple-shortlet-bookings'); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $gallery_html = ob_get_clean();
    
    // Clean up any <p> tags that might have been added
    $gallery_html = preg_replace('/<p>\s*<div/i', '<div', $gallery_html);
    $gallery_html = preg_replace('/<\/div>\s*<\/p>/i', '</div>', $gallery_html);
    $gallery_html = preg_replace('/<p>\s*<!--/i', '<!--', $gallery_html);
    $gallery_html = preg_replace('/-->\s*<\/p>/i', '-->', $gallery_html);
    $gallery_html = preg_replace('/<p>\s*<\/p>/i', '', $gallery_html);
    $gallery_html = preg_replace('/<\/p>\s*<div/i', '<div', $gallery_html);

    return $gallery_html;
}
add_filter('the_content', 'ssb_single_shortlet_gallery', 5);
