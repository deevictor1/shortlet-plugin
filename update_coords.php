<?php
require_once('wp-load.php');
$id = 12;
update_post_meta($id, '_ssb_latitude', '6.6433');
update_post_meta($id, '_ssb_longitude', '3.2866');
echo "Coordinates updated.\n";
