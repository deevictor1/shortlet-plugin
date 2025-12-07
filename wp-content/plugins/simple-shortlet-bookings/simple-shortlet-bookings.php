<?php
/**
 * Plugin Name: Simple Shortlet Bookings
 * Description: Lightweight booking system for a small number of shortlets (apartments, rooms).
 * Version: 1.0.1
 * Author: Dada Victor
 * Author URI: https://dadavictor.com
 * Text Domain: simple-shortlet-bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin path and url constants
define('SSB_PATH', plugin_dir_path(__FILE__));
define('SSB_URL', plugin_dir_url(__FILE__));

/**
 * Enqueue frontend styles
 */
function ssb_enqueue_styles()
{
    wp_enqueue_style(
        'ssb-frontend-styles',
        SSB_URL . 'assets/css/style.css',
        [], // No dependencies - load after everything
        '1.0.1' // Version for cache busting
    );
}
// Load with high priority (999) to ensure it loads after Elementor styles
add_action('wp_enqueue_scripts', 'ssb_enqueue_styles', 999);

function ssb_enqueue_scripts()
{
    // Always enqueue carousel
    wp_enqueue_script(
        'ssb-carousel',
        SSB_URL . 'assets/js/carousel.js',
        [],
        '1.0.1',
        true
    );

    // Enqueue Flatpickr for booking forms
    wp_enqueue_style(
        'flatpickr-css',
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
        [],
        '4.6.13'
    );
    wp_enqueue_script(
        'flatpickr-js',
        'https://cdn.jsdelivr.net/npm/flatpickr',
        [],
        '4.6.13',
        true
    );
    
    // Enqueue SweetAlert2 for beautiful notifications
    if (is_singular('shortlet')) {
        wp_enqueue_style(
            'sweetalert2-css',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            [],
            '11.0.0'
        );
        wp_enqueue_script(
            'sweetalert2-js',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            [],
            '11.0.0',
            true
        );
    }

    // Enqueue Leaflet (OpenStreetMap) for location maps
    if (is_singular('shortlet')) {
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );
        wp_enqueue_script(
            'ssb-maps',
            SSB_URL . 'assets/js/maps.js',
            ['leaflet-js', 'jquery'],
            '1.0.0',
            true
        );
        // Localize script for AJAX
        wp_localize_script('ssb-maps', 'ssbMapsData', array(
            'nonce' => wp_create_nonce('ssb_geocode_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
}
add_action('wp_enqueue_scripts', 'ssb_enqueue_scripts');

/**
 * Enqueue admin scripts for media uploader and carousel
 */
function ssb_enqueue_admin_scripts($hook)
{
    global $post_type;

    // Enqueue media uploader on shortlet edit screens
    if ($post_type === 'shortlet') {
        wp_enqueue_media();
    }

    // Enqueue carousel styles and scripts on booking edit screens
    if ($post_type === 'shortlet_booking') {
        wp_enqueue_style(
            'ssb-frontend-styles',
            SSB_URL . 'assets/css/style.css',
            [],
            '1.0.0'
        );
        wp_enqueue_script(
            'ssb-carousel',
            SSB_URL . 'assets/js/carousel.js',
            [],
            '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'ssb_enqueue_admin_scripts');

/**
 * Load plugin files
 */
function ssb_load_files()
{
    require_once SSB_PATH . 'includes/post-types.php';
    require_once SSB_PATH . 'includes/helpers.php';
    require_once SSB_PATH . 'includes/email-templates.php';
    require_once SSB_PATH . 'includes/booking-form.php';
    require_once SSB_PATH . 'includes/meta-boxes.php';
    require_once SSB_PATH . 'includes/admin-filters.php';
    require_once SSB_PATH . 'includes/shortlet-list.php';
    require_once SSB_PATH . 'includes/booking-calendar.php';
    require_once SSB_PATH . 'includes/template-loader.php';
}
add_action('plugins_loaded', 'ssb_load_files');


/**
 * Configure PHPMailer for HTML emails
 */
function ssb_configure_phpmailer($phpmailer) {
    // Always enable HTML emails
    $phpmailer->isHTML(true);
    
    // Only configure SMTP if explicitly set (for development/testing)
    if (defined('WP_SMTP_HOST') && defined('WP_SMTP_PORT') && defined('WP_DEBUG') && WP_DEBUG) {
        $phpmailer->isSMTP();
        $phpmailer->Host = WP_SMTP_HOST;
        $phpmailer->Port = WP_SMTP_PORT;
        $phpmailer->SMTPAuth = false;
        $phpmailer->SMTPAutoTLS = false;
    }
}
add_action('phpmailer_init', 'ssb_configure_phpmailer');