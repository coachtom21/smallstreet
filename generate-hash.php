<?php
require_once( 'wp-load.php' );  // make sure this path is correct
global $wp_hasher;

if ( empty( $wp_hasher ) ) {
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $wp_hasher = new PasswordHash( 8, true );
}

$hash = $wp_hasher->HashPassword( 'testTest123!@#' );
echo $hash;
