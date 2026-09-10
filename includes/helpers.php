<?php
/**
 * Shared helpers for the iFly Nepal Booking plugin.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The inline markup a headline is allowed to carry.
 *
 * The designs set one or two words of a heading in italic or in the accent
 * colour, which means the editor has to be able to wrap part of their own
 * sentence. Restricted to inline emphasis and a line break: an editor marking up
 * a phrase is expected, an editor pasting a script tag or a layout div is not.
 *
 * Deliberately a copy of the theme's iflynepal_kses_text() rather than a call to
 * it. The plugin's content has to keep working if the theme is switched, and a
 * theme function is gone the moment that happens.
 *
 * @since 1.0.0
 *
 * @param string $value Raw stored value.
 * @return string Filtered markup, safe to echo.
 */
function iflynepal_booking_kses_text( $value ) {
	return wp_kses(
		(string) $value,
		array(
			'span'   => array( 'class' => array() ),
			'em'     => array(),
			'strong' => array(),
			'br'     => array(),
		)
	);
}

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
