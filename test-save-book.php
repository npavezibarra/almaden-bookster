<?php
require_once('../../../wp-load.php');
$book_id = 7;
$nonce = wp_create_nonce( 'almaden_save_book_nonce_' . $book_id );
$_POST = array(
    'action' => 'almaden_save_book',
    'book_id' => $book_id,
    'nonce' => $nonce,
    'title' => 'Test',
    'chapters' => '[]'
);
almaden_bookster_save_book_ajax();
