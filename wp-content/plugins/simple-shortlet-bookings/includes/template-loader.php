<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load custom single template for shortlets
 * DISABLED: Using the_content filter instead to avoid script loading issues
 */
/*
function ssb_load_single_template($template)
{
    if (is_singular('shortlet')) {
        $plugin_template = SSB_PATH . 'templates/single-shortlet.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter('single_template', 'ssb_load_single_template');
*/
