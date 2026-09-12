<?php
/**
 * What the single-package template needs to know.
 *
 * The template asks questions — which sections does this package have, what
 * goes in the at-a-glance table, where does the breadcrumb lead — and the
 * answers are derived here from the schema and the stored fields, never stored
 * alongside them. A stored answer is an answer that goes stale the first time an
 * editor changes the field it was derived from.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The sections this package actually has, in page order.
 *
 * This is what the sticky side nav is drawn from, so it has to agree with what
 * the template renders. Both read it, which is what keeps a nav link from
 * pointing at a section that was never written.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array[] Section key => label, icon and whether it is the first.
 */
function iflynepal_package_page_sections( $post_id ) {
	$post_id = (int) $post_id;

	$candidates = array(
		'overview'  => array(
			'label'  => __( 'Overview', 'iflynepal' ),
			'icon'   => 'doc',
			'filled' => '' !== iflynepal_package_field( $post_id, 'overview_intro' )
				|| '' !== iflynepal_package_field( $post_id, 'overview_body' )
				|| array() !== iflynepal_package_field_lines( $post_id, 'highlights' ),
		),
		'itinerary' => array(
			'label'  => __( 'Itinerary', 'iflynepal' ),
			'icon'   => 'route',
			'filled' => array() !== iflynepal_package_cards( $post_id, 'itinerary_days' ),
		),
		'dates'     => array(
			'label'  => __( 'Dates & prices', 'iflynepal' ),
			'icon'   => 'cal',
			'filled' => '' !== iflynepal_package_field( $post_id, 'price_amount' ),
		),
		'packing'   => array(
			'label'  => __( 'Packing & map', 'iflynepal' ),
			'icon'   => 'bag',
			'filled' => array() !== iflynepal_package_field_lines( $post_id, 'packing_items' )
				|| '' !== iflynepal_package_field( $post_id, 'map_embed' ),
		),
		'faqs'      => array(
			'label'  => __( 'FAQs', 'iflynepal' ),
			'icon'   => 'help',
			'filled' => array() !== iflynepal_package_cards( $post_id, 'faq_items' ),
		),
		'similar'   => array(
			'label'  => __( 'Similar packages', 'iflynepal' ),
			'icon'   => 'lotus',
			'filled' => array() !== iflynepal_package_related( $post_id ),
		),
	);

	$sections = array();

	foreach ( $candidates as $key => $section ) {
		if ( ! $section['filled'] ) {
			continue;
		}

		$sections[ $key ] = array(
			'label' => $section['label'],
			'icon'  => $section['icon'],
			'first' => empty( $sections ),
		);
	}

	return $sections;
}

/**
 * The at-a-glance rows that have been filled in.
 *
 * The icons are fixed to the rows rather than chosen by an editor: the design
 * draws one per fact, and a row with no icon is a row the layout has no slot
 * for. That is also why this is seven declared fields and not a repeater.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array[] Rows of label, value and icon.
 */
function iflynepal_package_glance( $post_id ) {
	$map = array(
		'glance_destination' => array( __( 'Destination', 'iflynepal' ), 'pin' ),
		'glance_duration'    => array( __( 'Duration', 'iflynepal' ), 'clock' ),
		'glance_activities'  => array( __( 'Activities', 'iflynepal' ), 'lotus' ),
		'glance_meals'       => array( __( 'Meals', 'iflynepal' ), 'bowl' ),
		'glance_stay'        => array( __( 'Accommodation', 'iflynepal' ), 'bed' ),
		'glance_group'       => array( __( 'Group size', 'iflynepal' ), 'group' ),
		'glance_level'       => array( __( 'Experience level', 'iflynepal' ), 'level' ),
		'glance_best_time'   => array( __( 'Best time', 'iflynepal' ), 'sun' ),
		'glance_checkin'     => array( __( 'Check-in', 'iflynepal' ), 'door' ),
	);

	$rows = array();

	foreach ( $map as $key => $row ) {
		$value = iflynepal_package_field( $post_id, $key );

		if ( '' === $value ) {
			continue;
		}

		$rows[] = array(
			'label' => $row[0],
			'value' => $value,
			'icon'  => $row[1],
		);
	}

	return $rows;
}

/**
 * The breadcrumb, walked up the package's own type path.
 *
 * The same ancestry the URL is built from, so the trail and the address bar
 * cannot disagree.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return void
 */
function iflynepal_package_the_breadcrumb( $post_id ) {
	$term      = iflynepal_package_primary_type( $post_id );
	$separator = '<svg class="iflynepal-pkg-ico iflynepal-pkg-sep" aria-hidden="true"><use href="#ifnpkg-i-right"/></svg>';
	$crumbs    = array(
		'<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'iflynepal' ) . '</a>',
	);

	if ( $term instanceof WP_Term ) {
		$trail   = array_reverse( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) );
		$trail[] = $term->term_id;

		foreach ( $trail as $term_id ) {
			$ancestor = get_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY );
			$link     = $ancestor instanceof WP_Term ? get_term_link( $ancestor ) : '';

			if ( ! $ancestor instanceof WP_Term || is_wp_error( $link ) ) {
				continue;
			}

			$crumbs[] = '<a href="' . esc_url( $link ) . '">' . esc_html( $ancestor->name ) . '</a>';
		}
	}

	$crumbs[] = '<span aria-current="page">' . esc_html( get_the_title( $post_id ) ) . '</span>';
	?>
	<nav class="iflynepal-pkg-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'iflynepal' ); ?>">
		<?php
		// Every crumb is escaped as it is built above; the separator is static markup.
		echo implode( $separator, $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</nav>
	<?php
}

/**
 * The share row.
 *
 * Plain links to each network's share endpoint — no third-party script, no
 * tracking pixel, and nothing that needs the visitor to be signed in for the
 * page to finish loading.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return void
 */
function iflynepal_package_the_share( $post_id ) {
	$url   = get_permalink( $post_id );
	$title = get_the_title( $post_id );
	?>
	<div class="iflynepal-pkg-share" id="ifnpkg-share" data-url="<?php echo esc_url( $url ); ?>">
		<span class="iflynepal-pkg-share-label"><?php esc_html_e( 'Share this:', 'iflynepal' ); ?></span>

		<a class="iflynepal-pkg-share-btn" data-share="facebook" target="_blank" rel="noopener"
			href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ); ?>"
			aria-label="<?php esc_attr_e( 'Share on Facebook', 'iflynepal' ); ?>">
			<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-7.5H16l.4-3H13.5V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21z"/></svg>
		</a>

		<a class="iflynepal-pkg-share-btn" data-share="x" target="_blank" rel="noopener"
			href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ) ); ?>"
			aria-label="<?php esc_attr_e( 'Share on X', 'iflynepal' ); ?>">
			<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.3 3.5h3l-6.6 7.5 7.8 9.5h-6.1l-4.8-6-5.5 6H2.1l7-8L1.6 3.5h6.2l4.3 5.5zm-1 15.3h1.7L7 5.1H5.2z"/></svg>
		</a>

		<a class="iflynepal-pkg-share-btn" data-share="whatsapp" target="_blank" rel="noopener"
			href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ) ); ?>"
			aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'iflynepal' ); ?>">
			<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8a9.1 9.1 0 0 0-7.9 13.7L2.8 21.2l4.8-1.3A9.1 9.1 0 1 0 12 2.8zm0 16.6c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A7.5 7.5 0 1 1 12 19.4zm4.1-5.6c-.2-.1-1.3-.7-1.6-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 2 .8 2.4.6 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.5-.3z"/></svg>
		</a>

		<a class="iflynepal-pkg-share-btn" data-share="email"
			href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ) ); ?>"
			aria-label="<?php esc_attr_e( 'Share by email', 'iflynepal' ); ?>">
			<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-mail"/></svg>
		</a>

		<button class="iflynepal-pkg-share-btn" data-share="copy" type="button" aria-label="<?php esc_attr_e( 'Copy link', 'iflynepal' ); ?>">
			<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-link"/></svg>
			<span class="iflynepal-pkg-share-toast" role="status"><?php esc_html_e( 'Link copied', 'iflynepal' ); ?></span>
		</button>
	</div>
	<?php
}

/**
 * One itinerary day's timeline, as stops to render.
 *
 * The timeline is a repeater of `time` / `what happens` rows, and was a textarea
 * of `time | what happens` lines before that. Both are read, by the one helper
 * that knows about both shapes — see iflynepal_package_timeline_rows().
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw timeline field.
 * @return array[] Stops of 'time' and 'text'.
 */
function iflynepal_package_timeline( $value ) {
	return iflynepal_package_timeline_rows( $value );
}

/**
 * A textarea split into paragraphs on blank lines.
 *
 * @since 1.0.0
 *
 * @param string $value Raw field value.
 * @return string[] Paragraphs, in order.
 */
function iflynepal_package_paragraphs( $value ) {
	$blocks = preg_split( '/\R{2,}/', (string) $value );

	return array_values( array_filter( array_map( 'trim', (array) $blocks ), 'strlen' ) );
}

/**
 * Packages to show in the similar-packages rail.
 *
 * The other packages filed under the same primary type, newest first, this one
 * left out. Not "related" by anything cleverer: the taxonomy is the only
 * statement anyone has made about which packages belong together.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @param int $limit   Optional. How many at most.
 * @return WP_Post[] Packages.
 */
function iflynepal_package_related( $post_id, $limit = 8 ) {
	$term = iflynepal_package_primary_type( $post_id );

	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'        => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => (int) $limit,
			'post__not_in'     => array( (int) $post_id ),
			'suppress_filters' => false,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => IFLYNEPAL_PACKAGE_TAXONOMY,
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => true,
				),
			),
		)
	);
}

/**
 * A heading field, with its accent tag kept.
 *
 * @since 1.0.0
 *
 * @param string $value Stored heading.
 * @return void
 */
function iflynepal_package_the_heading( $value ) {
	echo wp_kses( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses escapes.
		$value,
		array(
			'em'   => array(),
			'span' => array( 'class' => array() ),
		)
	);
}
