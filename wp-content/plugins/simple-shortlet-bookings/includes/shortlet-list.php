<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [shortlet_list]
 * Lists all published shortlets in a simple grid.
 *
 * Usage: [shortlet_list]
 */
function ssb_shortlet_list_shortcode($atts)
{
    $atts = shortcode_atts([
        'posts_per_page' => -1, // all
    ], $atts, 'shortlet_list');

    $query = new WP_Query([
        'post_type' => 'shortlet',
        'post_status' => 'publish',
        'posts_per_page' => (int) $atts['posts_per_page'],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    if (!$query->have_posts()) {
        return '<div class="ssb-empty-state"><p>' . esc_html__('No shortlets available at the moment.', 'simple-shortlet-bookings') . '</p></div>';
    }

    ob_start();
    ?>
    <div class="ssb-shortlet-list">
        <?php while ($query->have_posts()):
            $query->the_post();
            $gallery_ids = get_post_meta(get_the_ID(), '_ssb_gallery_ids', true);
            $gallery_ids = $gallery_ids ? explode(',', $gallery_ids) : [];
            $gallery_ids = array_filter(array_map('intval', $gallery_ids));

            // Include featured image as first image if gallery exists, or use only featured image
            $all_images = [];
            if (has_post_thumbnail()) {
                $all_images[] = get_post_thumbnail_id();
            }
            // Add gallery images (excluding featured image if it's in gallery)
            foreach ($gallery_ids as $img_id) {
                if (!in_array($img_id, $all_images)) {
                    $all_images[] = $img_id;
                }
            }
            ?>
            <div class="ssb-shortlet-item">
                <?php if (!empty($all_images)): ?>
                    <div class="ssb-shortlet-thumb">
                        <div class="ssb-carousel" data-carousel-id="shortlet-<?php echo esc_attr(get_the_ID()); ?>">
                            <div class="ssb-carousel-container">
                                <?php foreach ($all_images as $index => $img_id):
                                    // Use large size for better quality, fallback to full if large doesn't exist
                                    $img_url = wp_get_attachment_image_url($img_id, 'large');
                                    if (!$img_url) {
                                        $img_url = wp_get_attachment_image_url($img_id, 'full');
                                    }
                                    $img_full = wp_get_attachment_image_url($img_id, 'full');
                                    ?>
                                    <div class="ssb-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <a href="<?php the_permalink(); ?>">
                                            <img src="<?php echo esc_url($img_url); ?>"
                                                srcset="<?php echo esc_attr(wp_get_attachment_image_srcset($img_id, 'large')); ?>"
                                                sizes="(max-width: 768px) 100vw, 400px" data-full="<?php echo esc_url($img_full); ?>"
                                                alt="<?php echo esc_attr(get_the_title()); ?>" class="ssb-shortlet-image"
                                                loading="lazy">
                                        </a>
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
                <?php endif; ?>

                <h3 class="ssb-shortlet-title" style="font-size: 1.275rem !important; font-weight: 600 !important; line-height: 1.3 !important; margin: 0 !important; padding: 1.5rem 1.5rem 0.5rem !important;">
                    <a href="<?php the_permalink(); ?>" style="font-size: inherit !important; font-weight: inherit !important;">
                        <?php the_title(); ?>
                    </a>
                </h3>

                <?php
                $nightly_rate = get_post_meta(get_the_ID(), '_ssb_nightly_rate', true);
                $currency = get_post_meta(get_the_ID(), '_ssb_currency', true) ?: 'USD';
                if ($nightly_rate):
                    ?>
                    <div class="ssb-shortlet-price">
                        <span
                            class="ssb-price-amount"><?php echo esc_html(ssb_format_currency(floatval($nightly_rate), $currency)); ?></span>
                        <span class="ssb-price-label"><?php esc_html_e('per night', 'simple-shortlet-bookings'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="ssb-shortlet-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                </div>

                <p>
                    <a href="<?php the_permalink(); ?>" class="ssb-shortlet-link">
                        <?php esc_html_e('View details & book', 'simple-shortlet-bookings'); ?>
                    </a>
                </p>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('shortlet_list', 'ssb_shortlet_list_shortcode');
