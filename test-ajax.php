<?php
require_once('../../../wp-load.php');
$nonce = wp_create_nonce( 'almaden_save_settings_nonce_7' );
echo $nonce;
