<?php
/**
 * Front-end assets for the package templates.
 *
 * Loaded only on the templates that need them, the way the theme enqueues:
 * neither script has anything to do on the rest of the site and a site-wide
 * enqueue would spend the performance budget on pages that never use it.
 *
 * The catalogue's own stylesheet ships with the plugin, as plain CSS with no
 * build step. It began life inside the theme's Tailwind source, which worked but
 * had two costs worth recording so the decision is not quietly reversed: a
 * plugin template could not be styled without rebuilding and committing the
 * theme, and a browser holding an older main.css rendered the catalogue as raw
 * stacked markup while the rest of the page looked correct — a silent failure
 * that reads like a template bug. Its own file has its own cache lifetime and
 * keeps the catalogue's appearance when the theme is switched, which is the same
 * reason the catalogue lives in a plugin at all.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is one of the plugin's own templates.
 *
 * @since 1.0.0
 *
 * @return bool True on a package, a package archive or a package type archive.
 */
function iflynepal_booking_is_package_template() {
	return is_tax( IFLYNEPAL_PACKAGE_TAXONOMY )
		|| is_post_type_archive( IFLYNEPAL_PACKAGE_POST_TYPE )
		|| is_singular( IFLYNEPAL_PACKAGE_POST_TYPE );
}

/**
 * Enqueues the catalogue stylesheet on the plugin's own templates.
 *
 * Conditional, like every enqueue in the theme: these rules have nothing to
 * style on the rest of the site, and loading them everywhere would spend the
 * performance budget on pages that never use them.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_catalogue_styles() {
	if ( ! iflynepal_booking_is_package_template() ) {
		return;
	}

	wp_enqueue_style(
		'iflynepal-catalogue',
		IFLYNEPAL_BOOKING_URL . 'assets/css/catalogue.css',
		array(),
		iflynepal_booking_asset_version( 'assets/css/catalogue.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_catalogue_styles' );

/**
 * Tells the theme these templates carry a hero.
 *
 * The theme's header is transparent over a hero and docks to solid once the page
 * scrolls, and it decides which it is from iflynepal_has_hero(). That function
 * can only know about the theme's own templates, so without this the package
 * archives would render a hero image under a transparent header — and the pages
 * with no hero image would put white nav text on white.
 *
 * Filtered rather than edited into the theme so the knowledge of which plugin
 * templates have heroes stays in the plugin. Guarded on the filter existing:
 * under another theme nothing calls it and nothing breaks.
 *
 * @since 1.0.0
 *
 * @param bool $has_hero Whether the theme thinks a hero is rendered.
 * @return bool
 */
function iflynepal_booking_has_hero( $has_hero ) {
	if ( is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) || is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return true;
	}

	return $has_hero;
}
add_filter( 'iflynepal_has_hero', 'iflynepal_booking_has_hero' );

/**
 * The hero image of the current package template, for the theme's LCP preload.
 *
 * The pair to the filter above: the theme preloads whatever this resolves to, so
 * returning nothing here while claiming a hero would preload the front page's
 * image on a retreat archive.
 *
 * @since 1.0.0
 *
 * @param string $url Empty by default.
 * @return string Image URL, or the input untouched.
 */
function iflynepal_booking_hero_image_url( $url ) {
	$attachment_id = 0;

	if ( is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$attachment_id = absint( iflynepal_archive_field( $term->term_id, 'hero_image' ) );
		}
	} elseif ( is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		$attachment_id = (int) get_post_thumbnail_id( get_queried_object_id() );
	}

	if ( ! $attachment_id ) {
		return $url;
	}

	$src = wp_get_attachment_image_url( $attachment_id, 'full' );

	return $src ? $src : $url;
}
add_filter( 'iflynepal_pre_current_hero_image_url', 'iflynepal_booking_hero_image_url' );

/**
 * Enqueues the card filter on a type archive.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_filters() {
	if ( ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return;
	}

	wp_enqueue_script(
		'iflynepal-package-filters',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/filters.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/archive/filters.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_filters' );

/**
 * Enqueues the grid heading's underline and handwritten note on a type archive.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_annotation() {
	if ( ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return;
	}

	wp_enqueue_script(
		'iflynepal-archive-annotation',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/annotation.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/archive/annotation.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_annotation' );
