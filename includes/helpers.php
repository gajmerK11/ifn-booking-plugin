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
			'b'      => array(),
			'br'     => array(),
		)
	);
}

/**
 * Strips inline `style` attributes out of a wp_kses_post()'d wysiwyg field.
 *
 * The reduced toolbar wp_kses_post() editor fields render with can only
 * produce bold, italic, underline, a link and a list — never a `style`
 * attribute. One only shows up in saved content when an editor pasted
 * formatted text from elsewhere (Word, Docs, a browser selection), carrying
 * its source's font-family/color inline. wp_kses_post() keeps it, since
 * font-family and color are on core's safe-CSS allowlist, and that inline
 * style then outranks every site stylesheet, silently breaking the page's
 * type and color rather than inheriting it.
 *
 * @since 1.0.0
 *
 * @param string $html Already wp_kses_post()'d HTML.
 * @return string Same HTML with every `style` attribute removed.
 */
function iflynepal_booking_strip_inline_style( $html ) {
	$allowed = wp_kses_allowed_html( 'post' );

	foreach ( $allowed as $tag => $attrs ) {
		unset( $allowed[ $tag ]['style'] );
	}

	return wp_kses( (string) $html, $allowed );
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

/**
 * Builds the attributes an `<a>` needs for a stored link field.
 *
 * Deliberately a copy of the theme's iflynepal_anchor_attr() for the same
 * reason iflynepal_booking_kses_text() copies iflynepal_kses_text() above:
 * the plugin's own CTAs (archive hero/closing buttons, package asides, ...)
 * have to keep working if the theme is switched.
 *
 * A real URL prints as an ordinary `href`. An in-page anchor (`#id`) does not:
 * a hash `href` always shows the resolved target in the status bar on hover,
 * which is exactly what every hash CTA on the site used to reveal before a
 * visitor ever clicked. There is no CSS or HTML way to keep an `href` and lose
 * that preview, so an anchor gets no `href` at all: instead
 * `data-iflynepal-scroll` carries the target id and the theme's
 * assets/js/global/anchor-scroll.js does the scrolling on click (and on
 * Enter/Space, since `role="button" tabindex="0"` is what makes the element
 * focusable without one). The trade-off, accepted deliberately, is that these
 * links need JavaScript — a bare `#` (a JS hook with no scroll target, same as
 * a menu toggle) prints nothing at all rather than a dead attribute.
 *
 * @since 1.0.0
 *
 * @param string $url Stored link value.
 * @return string Attribute markup, ready to print inside an `<a ...>` tag.
 */
function iflynepal_booking_anchor_attr( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	if ( '#' === $url[0] ) {
		$target = ltrim( $url, '#' );

		if ( '' === $target ) {
			return '';
		}

		return sprintf( 'data-iflynepal-scroll="%s" role="button" tabindex="0"', esc_attr( $target ) );
	}

	return sprintf( 'href="%s"', esc_url( $url ) );
}

/**
 * `target="_blank" rel="noopener noreferrer"`, printed only for a link that
 * actually leaves the site.
 *
 * Package aside.php builds its WhatsApp button by hand and hardcodes this
 * attribute pair because it always knows it is linking to wa.me. The archive
 * hero/closing CTAs (iflynepal_archive_the_actions()) can't hardcode
 * anything — the same two buttons carry "Explore retreats" (an in-page
 * anchor), "Talk to a retreat guide" (an internal /contact-us path), "Ask
 * about Immersive dates" (also internal) and "WhatsApp a trip planner" (a
 * wa.me link), depending on which term's admin fields filled them in. Rather
 * than a field to tick per button, the host tells the difference on its own:
 * no host at all (an anchor, a relative path, tel:, mailto:) never gets it: a
 * host that isn't this site's does.
 *
 * @since 1.0.0
 *
 * @param string $url Stored link value.
 * @return string Attribute markup, or '' for an internal/non-URL link.
 */
function iflynepal_booking_external_link_attr( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url || '#' === $url[0] ) {
		return '';
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );

	if ( ! $host ) {
		return '';
	}

	$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( strtolower( $host ) === strtolower( (string) $site_host ) ) {
		return '';
	}

	return ' target="_blank" rel="noopener noreferrer"';
}
