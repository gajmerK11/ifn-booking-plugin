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
			'filled' => array() !== iflynepal_package_cards( $post_id, 'itinerary_days' )
				|| array() !== iflynepal_package_cards( $post_id, 'itinerary_weeks' ),
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
 * Turns a `type="time"` value ("14:00") into a 12-hour display ("2:00 PM").
 *
 * The field stores the browser's own 24-hour HH:MM so it stays a real,
 * sortable time rather than a free-typed string; the 12-hour form is a
 * display choice, made only here, at the one place it's read.
 *
 * @since 1.0.0
 *
 * @param string $value Raw HH:MM value from the field.
 * @return string 12-hour display, or the original value if it isn't HH:MM.
 */
function iflynepal_package_format_time( $value ) {
	$timestamp = strtotime( (string) $value );

	if ( ! preg_match( '/^\d{2}:\d{2}$/', (string) $value ) || false === $timestamp ) {
		return $value;
	}

	return date_i18n( 'g:i A', $timestamp );
}

/**
 * The at-a-glance rows that have been filled in.
 *
 * The icons are fixed to the rows rather than chosen by an editor: the design
 * draws one per fact, and a row with no icon is a row the layout has no slot
 * for. That is also why this is ten declared fields and not a repeater.
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
		'glance_altitude'    => array( __( 'Max altitude', 'iflynepal' ), 'peak' ),
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

		if ( 'glance_checkin' === $key ) {
			$value = iflynepal_package_format_time( $value );
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

		<?php
		/*
		 * Instagram and TikTok have no share-intent URL the way Facebook, X
		 * and WhatsApp do — neither platform accepts an external link to
		 * pre-fill into a post. These two point at the company's own
		 * profile instead, sharing the same Customizer-configured URLs (and
		 * icon glyphs) as the footer's follow row — see
		 * iflynepal_footer_social_networks() and iflynepal_footer_socials()
		 * — so a network with no URL set there quietly has no button here
		 * either, the same as it has no button in the footer.
		 */
		$iflynepal_share_socials = array_filter(
			iflynepal_footer_socials(),
			static function ( $iflynepal_social ) {
				return in_array( $iflynepal_social['slug'], array( 'instagram', 'tiktok' ), true );
			}
		);

		foreach ( $iflynepal_share_socials as $iflynepal_social ) :
			?>
			<a class="iflynepal-pkg-share-btn" data-share="<?php echo esc_attr( $iflynepal_social['slug'] ); ?>" target="_blank" rel="noopener"
				<?php echo iflynepal_booking_anchor_attr( $iflynepal_social['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?>
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: social network name. */ __( 'Visit us on %s', 'iflynepal' ), $iflynepal_social['label'] ) ); ?>">
				<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="<?php echo esc_attr( $iflynepal_social['path'] ); ?>"/></svg>
			</a>
		<?php endforeach; ?>

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
 * Whether a rich-text field's raw value already carries markup a wp_editor()
 * field would have saved — the one thing that tells this reader apart from a
 * `textarea` field's plain-text past.
 *
 * Any tag counts, not only a block one: a one-line "Opening paragraph" with a
 * bold word and a link in it is exactly the reduced toolbar's job and never
 * gets a wrapping <p> from a single line with no Enter pressed, so gating on
 * block tags alone left inline-only markup looking like legacy text and sent
 * it through esc_html() — which is what printed the tags themselves on the
 * page instead of applying them. A legacy value cannot produce a false
 * positive here: sanitize_textarea_field() stripped every tag a `textarea`
 * field ever saved, so a real `<` followed by a tag name is only possible on
 * a value this field's own wp_kses_post() wrote.
 *
 * @since 1.0.0
 *
 * @param string $value Raw field value.
 * @return bool
 */
function iflynepal_package_is_rich_html( $value ) {
	return (bool) preg_match( '/<[a-z][^>]*>/i', (string) $value );
}

/**
 * A `wysiwyg` field's saved HTML, ready to print as-is.
 *
 * Honours two shapes at once: a legacy plain block of text (the shape
 * `sanitize_textarea_field()` left a `textarea` field in, one paragraph per
 * blank line, no markup at all) and the HTML a wp_editor() field now saves.
 * The rich shape is printed exactly as authored, tags and all — a
 * paragraph-by-paragraph reparse used to guess at boundaries and dropped
 * anything that wasn't a bare `<p>` (a bulleted sub-list, say), which is
 * exactly the formatting this field exists to let an editor keep.
 *
 * The editor's reduced (`teeny`) toolbar does not wrap each Enter press in
 * its own `<p>` — it leaves a bare inline run (`<strong>…</strong>`) with a
 * blank line after it, confirmed by reading a saved field's raw value
 * straight out of postmeta. Two runs with nothing block-level between them
 * sit on the same line in a browser no matter how the HTML source is
 * spaced. `wpautop()` is the exact filter core runs on `post_content` for
 * this same shape of input, and it already knows to leave real blocks (the
 * `<ul>` the Bulleted-list button produces) alone rather than double-wrap
 * them. There is no migration script — opening a package and pressing
 * Update is what moves a field to the new shape, the same rule §5.3n's
 * itinerary reader already uses.
 *
 * @since 1.0.0
 *
 * @param string $value Raw field value, already wp_kses_post()'d at save time.
 * @return string Safe-to-echo HTML, or '' when nothing is set.
 */
function iflynepal_package_rich_html( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( ! iflynepal_package_is_rich_html( $value ) ) {
		return implode(
			'',
			array_map(
				static function ( $paragraph ) {
					return '<p>' . esc_html( $paragraph ) . '</p>';
				},
				iflynepal_package_paragraphs( $value )
			)
		);
	}

	return wpautop( $value );
}

/**
 * A `wysiwyg` field's lines, ready to print one list item per row.
 *
 * Same dual-shape reasoning as iflynepal_package_rich_html(), plus a
 * third shape of its own: the editor's Bulleted-list button produces `<li>`
 * rather than `<p>`, and either is a valid way to type one highlight per
 * row, so `<li>` is tried first. A row typed with a plain Enter rather than
 * the list button is the same bare-inline-run shape iflynepal_package_rich_html()
 * documents, so it goes through the same wpautop() pass before the `<p>`
 * match is tried — the `<li>` pass needs no such help, since a real list
 * item is already a block.
 *
 * @since 1.0.0
 *
 * @param string $value Raw field value.
 * @return string[] Inner HTML of each line, already safe to echo.
 */
function iflynepal_package_rich_lines( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return array();
	}

	if ( ! iflynepal_package_is_rich_html( $value ) ) {
		$lines = preg_split( '/\R/', $value );

		return array_values( array_filter( array_map( 'esc_html', array_map( 'trim', (array) $lines ) ), 'strlen' ) );
	}

	foreach ( array( 'li', 'p' ) as $tag ) {
		$haystack = 'p' === $tag ? wpautop( $value ) : $value;

		if ( preg_match_all( '#<' . $tag . '[^>]*>(.*?)</' . $tag . '>#is', $haystack, $matches ) && ! empty( $matches[1] ) ) {
			return array_values(
				array_filter(
					array_map(
						static function ( $inner ) {
							return wp_kses_post( trim( $inner ) );
						},
						$matches[1]
					),
					'strlen'
				)
			);
		}
	}

	return array( wp_kses_post( $value ) );
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

/**
 * The itinerary's altitude profile, as chart geometry ready to draw.
 *
 * Neither the retreat nor the tour design ever needed this — it is new for
 * trekking, and it is opt-in the same way everything else here is: a package
 * fills in Elevation on as many or as few days as it has a reliable number
 * for, and the chart plots exactly those, skipping the rest. Below two points
 * there is no line to draw, so nothing is returned and the section is left off
 * entirely, note included — a single dot is not a profile.
 *
 * The geometry is worked out here rather than baked into the template so that
 * a 3-day trek and a 24-day one both produce a chart that fills the same box:
 * the axis step is picked from the actual spread of elevations on the day
 * (see iflynepal_package_altitude_axis_step()), not hardcoded to one trek's
 * numbers the way the design's own mock-up is.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array Empty when there are fewer than two usable points, otherwise
 *               'points' (each with x, y, day, metres), 'gridlines' (each with
 *               y and metres) and the peak index.
 */
function iflynepal_package_altitude_profile( $post_id ) {
	$days   = iflynepal_package_cards( $post_id, 'itinerary_days' );
	$points = array();

	foreach ( $days as $index => $day ) {
		$raw = isset( $day['elevation'] ) ? trim( (string) $day['elevation'] ) : '';

		if ( '' === $raw ) {
			continue;
		}

		// Digits only: an editor may well type "1,400m" rather than "1400".
		$metres = (int) preg_replace( '/[^0-9]/', '', $raw );

		if ( $metres <= 0 ) {
			continue;
		}

		$points[] = array(
			'day'    => $index + 1,
			'title'  => (string) $day['title'],
			'metres' => $metres,
		);
	}

	if ( count( $points ) < 2 ) {
		return array();
	}

	// The plot box, in the chart's own 0-0-660-230 viewBox — the design's own.
	$left   = 52;
	$right  = 642;
	$top    = 40;
	$bottom = 186;

	$lowest  = $points[0]['metres'];
	$highest = $points[0]['metres'];

	foreach ( $points as $point ) {
		$lowest  = min( $lowest, $point['metres'] );
		$highest = max( $highest, $point['metres'] );
	}

	// A little headroom each side, so the line never touches the frame.
	$span       = max( $highest - $lowest, 1 );
	$padding    = $span * 0.15;
	$scale_min  = max( 0, $lowest - $padding );
	$scale_max  = $highest + $padding;
	$scale_span = max( $scale_max - $scale_min, 1 );

	$step      = iflynepal_package_altitude_axis_step( $scale_span );
	$gridlines = array();
	$value     = ceil( $scale_min / $step ) * $step;

	while ( $value <= $scale_max ) {
		$gridlines[] = array(
			'metres' => (int) $value,
			'y'      => round( $bottom - ( ( $value - $scale_min ) / $scale_span ) * ( $bottom - $top ), 1 ),
		);
		$value      += $step;
	}

	$count   = count( $points );
	$plotted = array();

	foreach ( $points as $index => $point ) {
		$x = 1 === $count ? $left : $left + ( $index / ( $count - 1 ) ) * ( $right - $left );
		$y = $bottom - ( ( $point['metres'] - $scale_min ) / $scale_span ) * ( $bottom - $top );

		$plotted[] = array(
			'x'      => round( $x, 1 ),
			'y'      => round( $y, 1 ),
			'day'    => $point['day'],
			'title'  => $point['title'],
			'metres' => $point['metres'],
		);
	}

	$peak_index = 0;

	foreach ( $plotted as $index => $point ) {
		if ( $point['metres'] > $plotted[ $peak_index ]['metres'] ) {
			$peak_index = $index;
		}
	}

	return array(
		'left'       => $left,
		'right'      => $right,
		'top'        => $top,
		'bottom'     => $bottom,
		'axis_y'     => $bottom + 26,
		'points'     => $plotted,
		'gridlines'  => $gridlines,
		'peak_index' => $peak_index,
	);
}

/**
 * A round gridline step for an altitude range.
 *
 * Picks the smallest candidate that keeps the chart to six lines or fewer, so
 * a short acclimatisation trek and a three-week expedition both read as one
 * chart rather than one having two gridlines and the other twenty.
 *
 * @since 1.0.0
 *
 * @param float $span The scaled elevation range, in metres.
 * @return int Metres between gridlines.
 */
function iflynepal_package_altitude_axis_step( $span ) {
	$candidates = array( 50, 100, 200, 250, 500, 1000, 2000, 2500, 5000, 10000 );

	foreach ( $candidates as $candidate ) {
		if ( $span / $candidate <= 6 ) {
			return $candidate;
		}
	}

	return (int) end( $candidates );
}
