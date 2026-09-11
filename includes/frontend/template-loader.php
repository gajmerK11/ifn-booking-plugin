<?php
/**
 * Which file renders a package or package type URL.
 *
 * A theme gets its templates for free: WordPress walks the template hierarchy
 * through the theme directory and the first file that exists wins. A plugin is
 * not on that walk at all, so /retreat-nepal/ falls all the way through to the
 * theme's index.php, which is a bare loop, and the archive renders as the body
 * text of whatever packages happen to be filed under the term.
 *
 * The `template_include` filter is the last word on the file that loads, and it
 * is where a plugin joins the hierarchy. Two rules it has to respect:
 *
 *  1. The theme still wins. A theme that ships its own
 *     taxonomy-iflynepal_package_type.php means to override the plugin, so
 *     locate_template() is asked first and its answer is returned untouched.
 *  2. Only the URLs this plugin owns are claimed. Anything else is returned
 *     exactly as it came in — the filter runs on every request on the site.
 *
 * Note this is a plugin, so get_template_part() is not available to the
 * templates: it resolves against the theme directory only. The equivalent here
 * is iflynepal_booking_get_part(), below, which does the same job (find a file,
 * expose $args to it, allow a theme override) against the plugin's own
 * templates/ directory.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Directory inside a theme where an override template may be placed.
 *
 * A theme overriding the whole archive puts the file at its own root, the way
 * WordPress expects. A theme overriding just one section of it puts the file
 * under this sub-directory, mirroring the plugin's own tree.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_BOOKING_THEME_DIR = 'ifn-booking';

/**
 * Points a package or package type request at the plugin's template.
 *
 * @since 1.0.0
 *
 * @param string $template Template path WordPress resolved.
 * @return string Template path to load.
 */
function iflynepal_booking_template_include( $template ) {
	$candidates = iflynepal_booking_template_candidates();

	if ( empty( $candidates ) ) {
		return $template;
	}

	// The theme's own file wins, and WordPress has already found it if it exists.
	$from_theme = locate_template( $candidates );

	if ( $from_theme ) {
		return $from_theme;
	}

	foreach ( $candidates as $candidate ) {
		$path = IFLYNEPAL_BOOKING_DIR . 'templates/' . $candidate;

		if ( is_readable( $path ) ) {
			return $path;
		}
	}

	return $template;
}
add_filter( 'template_include', 'iflynepal_booking_template_include' );

/**
 * Template file names for the current request, most specific first.
 *
 * Mirrors the shape of the core template hierarchy so a theme can override with
 * the file name it would already expect to use.
 *
 * @since 1.0.0
 *
 * @return string[] Candidate file names, empty when this plugin owns nothing here.
 */
function iflynepal_booking_template_candidates() {
	if ( is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		$term       = get_queried_object();
		$candidates = array();

		if ( $term instanceof WP_Term ) {
			$candidates[] = 'taxonomy-' . IFLYNEPAL_PACKAGE_TAXONOMY . '-' . $term->slug . '.php';
		}

		$candidates[] = 'taxonomy-' . IFLYNEPAL_PACKAGE_TAXONOMY . '.php';

		return $candidates;
	}

	if ( is_post_type_archive( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return array( 'archive-' . IFLYNEPAL_PACKAGE_POST_TYPE . '.php' );
	}

	if ( is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return array( 'single-' . IFLYNEPAL_PACKAGE_POST_TYPE . '.php' );
	}

	return array();
}

/**
 * Renders one template part from the plugin, with a theme override.
 *
 * The plugin equivalent of get_template_part(): same reuse-by-argument
 * contract, so a part is never copied to vary it.
 *
 * @since 1.0.0
 *
 * @param string $slug Part path relative to templates/, without the extension.
 * @param array  $args Variables exposed to the part as $args.
 * @return void
 */
function iflynepal_booking_get_part( $slug, $args = array() ) {
	$relative = ltrim( (string) $slug, '/' ) . '.php';
	$path     = locate_template( array( IFLYNEPAL_BOOKING_THEME_DIR . '/' . $relative ) );

	if ( ! $path ) {
		$path = IFLYNEPAL_BOOKING_DIR . 'templates/' . $relative;
	}

	if ( ! is_readable( $path ) ) {
		return;
	}

	load_template( $path, false, $args );
}
