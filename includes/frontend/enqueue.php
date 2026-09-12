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

/*
 * Priority 20, so the file is printed after the theme's own stylesheet rather
 * than before it. Plugins load before themes, so at the default priority this
 * runs first and the catalogue's rules lose every tie with the theme's — which
 * shows up as a handful of values quietly reverting and nothing to explain it.
 */
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_catalogue_styles', 20 );

/**
 * Enqueues the single-package stylesheet and its behaviour.
 *
 * Only on a package. The sheet is a transcription of that one design and has
 * nothing to style anywhere else on the site.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_package_assets() {
	if ( ! is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return;
	}

	wp_enqueue_style(
		'iflynepal-package',
		IFLYNEPAL_BOOKING_URL . 'assets/css/package.css',
		array( 'iflynepal-catalogue' ),
		iflynepal_booking_asset_version( 'assets/css/package.css' )
	);

	wp_enqueue_script(
		'iflynepal-package',
		IFLYNEPAL_BOOKING_URL . 'assets/js/package/package.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/package/package.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'iflynepal-package',
		'iflynepalPackage',
		array(
			/* translators: %d: the photograph's number in the gallery. */
			'showPhoto'   => __( 'Show photo %d', 'iflynepal' ),
			'expandAll'   => __( 'Expand all', 'iflynepal' ),
			'collapseAll' => __( 'Collapse all', 'iflynepal' ),
			'bookNote'    => __( 'Prices are a quotation. Nothing is reserved until you hear from us.', 'iflynepal' ),
			'weekdays'    => array(
				/* translators: Weekday initials, Monday first, two letters each. */
				__( 'Mo', 'iflynepal' ),
				__( 'Tu', 'iflynepal' ),
				__( 'We', 'iflynepal' ),
				__( 'Th', 'iflynepal' ),
				__( 'Fr', 'iflynepal' ),
				__( 'Sa', 'iflynepal' ),
				__( 'Su', 'iflynepal' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_package_assets', 20 );

/**
 * Docks the theme's header from the first frame on a package page.
 *
 * The header is transparent until hero.js docks it on scroll, and a package page
 * has no hero to be transparent over — white type on a white gallery. The class
 * is the theme's own, so the docked appearance is the theme's rather than a
 * second copy of it here, and it is added in the head so the header is never
 * painted in the wrong state first.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_dock_header() {
	if ( ! is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return;
	}
	?>
	<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var header = document.getElementById( 'iflynepal-header' );

			if ( header ) {
				header.classList.add( 'is-docked' );
			}
		} );
	</script>
	<?php
}
add_action( 'wp_head', 'iflynepal_booking_dock_header' );

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
	if ( is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return true;
	}

	/*
	 * A package page has no hero. The design opens on a white photo gallery
	 * directly under the header, so claiming one here would leave the header
	 * transparent and its white type invisible on white. The docked state is
	 * asked for instead, in the head script below.
	 */
	if ( is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return false;
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
	}

	/*
	 * Nothing for a package page. Its LCP image is the gallery's lead
	 * photograph, which the template marks fetchpriority="high" itself — a
	 * second preload of the same file from the theme's hero path would be a
	 * second request for it.
	 */

	if ( ! $attachment_id ) {
		return $url;
	}

	$src = wp_get_attachment_image_url( $attachment_id, 'full' );

	return $src ? $src : $url;
}
add_filter( 'iflynepal_pre_current_hero_image_url', 'iflynepal_booking_hero_image_url' );

/**
 * Tells the theme this archive renders its testimonial section.
 *
 * The archive draws the reviews with the theme's own reusable template part —
 * the component the design was ported from — and that part needs the theme's
 * carousel script to become a carousel rather than a scaled-up row.
 *
 * The theme decides whether to load that script by asking whether any review is
 * assigned to the page being viewed. A term archive is not a page and no review
 * can be assigned to one, so the answer is always no here however many reviews
 * exist. This says yes on exactly the archives that do render the band: the
 * section has to apply to the term, and its heading has to be written, which is
 * the same opt-in the template part itself uses.
 *
 * @since 1.0.0
 *
 * @param bool $has_testimonials Whether the theme thinks a section is rendered.
 * @return bool
 */
function iflynepal_booking_has_testimonials( $has_testimonials ) {
	if ( ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return $has_testimonials;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return $has_testimonials;
	}

	if ( ! in_array( 'testimonials', iflynepal_booking_archive_sections( $term ), true ) ) {
		return $has_testimonials;
	}

	if ( '' === iflynepal_archive_field( $term->term_id, 'testimonials_eyebrow' ) ) {
		return $has_testimonials;
	}

	return true;
}
add_filter( 'iflynepal_has_testimonials', 'iflynepal_booking_has_testimonials' );

/**
 * The theme's GSAP handles, when the theme has put them on this page.
 *
 * The archive carries the theme's hero component, so the theme already loads
 * GSAP and ScrollTrigger here and the plugin has no business loading a second
 * copy. Asked for rather than assumed: under another theme the handles do not
 * exist, and a script listing a missing dependency is silently never printed —
 * which would take the card filter down with the animation it only decorates.
 *
 * @since 1.0.0
 *
 * @return string[] Whichever of the two handles are on the page.
 */
function iflynepal_booking_gsap_handles() {
	$handles = array();

	foreach ( array( 'iflynepal-gsap', 'iflynepal-gsap-scrolltrigger' ) as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
			$handles[] = $handle;
		}
	}

	return $handles;
}

/**
 * Prints the reveal gate in the head.
 *
 * Every rule that hides a block before it is revealed is scoped under this
 * class, so it has to land before the page paints or the blocks flash in and
 * are then wound back. A visitor with JavaScript off never gets the class and so
 * is never shown hidden copy — the same gate the theme's hero uses, for the same
 * reason.
 *
 * assets/js/archive/reveal.js takes the class off again when it finds nothing to
 * play the blocks forward with.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_anim_gate() {
	if ( ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return;
	}

	wp_register_script( 'iflynepal-catalogue-gate', '', array(), IFLYNEPAL_BOOKING_VERSION, false );
	wp_enqueue_script( 'iflynepal-catalogue-gate' );
	wp_add_inline_script(
		'iflynepal-catalogue-gate',
		'document.documentElement.classList.add("iflynepal-catalogue-anim");'
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_anim_gate' );

/**
 * Enqueues the archive's motion on a type archive.
 *
 * Hooked late so the theme's own enqueue has already run: plugins load before
 * themes, so at the default priority this would ask whether GSAP is on the page
 * before the theme has had the chance to put it there.
 *
 * Flip is the one vendor file the plugin ships. It is what lets the card grid
 * reflow when a filter is pressed instead of the survivors jumping between
 * slots, and the theme does not carry it. Same version as the theme's GSAP —
 * a plugin built against a different one is a bug waiting for an upgrade.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_archive_scripts() {
	if ( ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return;
	}

	$gsap = iflynepal_booking_gsap_handles();
	$flip = array();

	if ( $gsap ) {
		wp_enqueue_script(
			'iflynepal-gsap-flip',
			IFLYNEPAL_BOOKING_URL . 'assets/js/vendor/Flip.min.js',
			array( 'iflynepal-gsap' ),
			'3.15.0',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		$flip = array( 'iflynepal-gsap-flip' );
	}

	wp_enqueue_script(
		'iflynepal-archive-reveal',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/reveal.js',
		$gsap,
		iflynepal_booking_asset_version( 'assets/js/archive/reveal.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_enqueue_script(
		'iflynepal-package-filters',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/filters.js',
		$flip,
		iflynepal_booking_asset_version( 'assets/js/archive/filters.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	/*
	 * The rail scrolls on its own; this only wires the two buttons, which the
	 * markup renders disabled for exactly that reason.
	 */
	wp_enqueue_script(
		'iflynepal-departures-rail',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/departures-rail.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/archive/departures-rail.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

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
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_archive_scripts', 20 );
