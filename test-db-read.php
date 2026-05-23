<?php
require_once('../../../wp-load.php');
global $wpdb;
$table_name = $wpdb->prefix . 'almaden_book_settings';
$settings = $wpdb->get_row( "SELECT * FROM $table_name WHERE book_id = 7", ARRAY_A );
print_r($settings);
