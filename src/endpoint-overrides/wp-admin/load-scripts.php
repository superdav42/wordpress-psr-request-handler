<?php
use WordPressPsr\Headers;
use WordPressPsr\PrematureExitException;

/** Set ABSPATH for execution */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

$protocol = $_SERVER['SERVER_PROTOCOL'];
if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ), true ) ) {
	$protocol = 'HTTP/1.0';
}

$load = $_GET['load'];
if ( is_array( $load ) ) {
	ksort( $load );
	$load = implode( '', $load );
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
$load = array_unique( explode( ',', $load ) );

if ( empty( $load ) ) {
	Headers::add_header( "$protocol 400 Bad Request" );
	throw new PrematureExitException();
}

$expires_offset = 31536000; // 1 year.
$out            = '';

$wp_scripts = new WP_Scripts();
wp_default_scripts( $wp_scripts );
wp_default_packages_vendor( $wp_scripts );
wp_default_packages_scripts( $wp_scripts );

if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $wp_version ) {
	Headers::add_header( "$protocol 304 Not Modified" );
	throw new PrematureExitException();
}

foreach ( $load as $handle ) {
	if ( ! array_key_exists( $handle, $wp_scripts->registered ) ) {
		continue;
	}

	$path = ABSPATH . $wp_scripts->registered[ $handle ]->src;
	$out .= file_get_contents( $path ) . "\n";
}

Headers::add_header( "Etag: $wp_version" );
Headers::add_header( 'Content-Type: application/javascript; charset=UTF-8' );
Headers::add_header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
Headers::add_header( "Cache-Control: public, max-age=$expires_offset" );

echo $out;
