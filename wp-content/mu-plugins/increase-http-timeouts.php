<?php
/**
 * Plugin Name: Increase HTTP Timeouts
 * Description: Increases HTTP timeouts for plugin/theme downloads to prevent cURL errors
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Increase HTTP request timeout for large downloads
 */
add_filter('http_request_timeout', function($timeout) {
    return 600; // 10 minutes
}, 999);

/**
 * Increase HTTP request args timeout
 */
add_filter('http_request_args', function($args) {
    $args['timeout'] = 600; // 10 minutes
    return $args;
}, 999);

