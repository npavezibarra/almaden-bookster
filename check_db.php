<?php
require_once('../../../wp-load.php');
global $wpdb;
$table_name = $wpdb->prefix . 'almaden_book_settings';
$columns = $wpdb->get_col("DESCRIBE $table_name", 0);
echo json_encode($columns);
