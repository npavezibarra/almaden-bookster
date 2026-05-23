<?php
require_once('../../../wp-load.php');
global $wpdb;
$table_name = $wpdb->prefix . 'almaden_book_settings';
$wpdb->query("ALTER TABLE $table_name ADD COLUMN chapter_title_line_height float DEFAULT 1.2 NOT NULL;");
echo "Column added.";
