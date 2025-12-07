<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add dropdown filter on Shortlet Booking list
 */
function ssb_restrict_bookings_by_shortlet() {
    global $typenow;

    if ($typenow !== 'shortlet_booking') {
        return;
    }

    $selected_shortlet = isset($_GET['ssb_shortlet_filter']) ? (int) $_GET['ssb_shortlet_filter'] : 0;

    $shortlets = get_posts([
        'post_type'      => 'shortlet',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (!$shortlets) {
        return;
    }

    echo '<select name="ssb_shortlet_filter">';
    echo '<option value="0">' . esc_html__('All shortlets', 'simple-shortlet-bookings') . '</option>';

    foreach ($shortlets as $shortlet) {
        printf(
            '<option value="%d"%s>%s</option>',
            $shortlet->ID,
            selected($selected_shortlet, $shortlet->ID, false),
            esc_html($shortlet->post_title)
        );
    }

    echo '</select>';
}
add_action('restrict_manage_posts', 'ssb_restrict_bookings_by_shortlet');

/**
 * Modify query when filter is used
 */
function ssb_filter_bookings_query($query) {
    global $pagenow;

    if ($pagenow !== 'edit.php') {
        return;
    }

    if (!$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');

    if ($post_type !== 'shortlet_booking') {
        return;
    }

    if (!empty($_GET['ssb_shortlet_filter'])) {
        $shortlet_id = (int) $_GET['ssb_shortlet_filter'];

        $meta_query   = (array) $query->get('meta_query');
        $meta_query[] = [
            'key'   => '_shortlet_id',
            'value' => $shortlet_id,
        ];

        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'ssb_filter_bookings_query');
