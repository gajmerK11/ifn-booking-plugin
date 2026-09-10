<?php
/**
 * Shared helpers for the iFly Nepal Booking plugin.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version string for a plugin asset.
 *
 * Mirrors the theme's iflynepal_asset_version(): use the file's mtime so a
 * deploy invalidates the browser cache without a hand-maintained version.
 *
 * @param string $relative_path Path to the asset, relative to the plugin root (e.g. 'assets/js/foo.js').
 * @return string File mtime as a string, or the plugin version if the file is missing.
 */
function iflynepal_booking_asset_version( $relative_path ) {
	$absolute = IFLYNEPAL_BOOKING_DIR . ltrim( $relative_path, '/' );

	if ( is_readable( $absolute ) ) {
		return (string) filemtime( $absolute );
	}

	return IFLYNEPAL_BOOKING_VERSION;
}
