<?php
require_once('wp-load.php');
$id = 12;
$meta = get_post_meta($id);
print_r($meta);
