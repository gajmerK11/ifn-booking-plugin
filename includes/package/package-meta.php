<?php
/**
 * The facts a single package carries.
 *
 * The card in the design is not a title and an excerpt. It shows a short type
 * pill, a duration, a place or a suitability note, a from-price, and a line of
 * copy that slides up on hover — and the single package page repeats all of it.
 * None of that is post content, so none of it can be typed into the editor: it
 * is structured data about the package and it lives in post meta.
 *
 * Same schema-first shape as the archive content model: one declarative list
 * that the meta box renders from, the save routine sanitizes from, and the
 * templates read from, so the three cannot drift apart.
 *
 * Fixed departure dates are here too. They are informational only — a line of
 * upcoming dates next to the booking button. There is deliberately no capacity,
 * slot or availability logic attached to them anywhere in this plugin, and none
 * should be added: overbooking is handled by the office, offline. Dates are
 * stored as one ISO date per line and sorted, filtered and counted at render
 * time, never stored derived.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prefix every package detail meta key carries.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_META_PREFIX = '_iflynepal_package_';

/**
 * The facts stored against a package.
 *
 * Every field a package can hold, whether or not a meta box currently draws it.
 * A field's `box` names the editing surface it belongs to; an empty `box` is a
 * field with no home on the editor yet — the templates still read it and the
 * stored values are still there, but nothing offers it to an editor. That is a
 * deliberate holding state, not an oversight: see
 * iflynepal_package_fields_for_box().
 *
 * @since 1.0.0
 *
 * @return array[] Field definitions keyed by schema key.
 */
function iflynepal_package_detail_fields() {
	$fields = array(
		'pill'            => array(
			'box'   => 'card',
			'label' => __( 'Card label', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'The small badge on the card image, e.g. "Yoga" or "Ayurveda".', 'iflynepal' ),
		),
		'duration'        => array(
			'box'   => 'card',
			'label' => __( 'Duration', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'Written as it should read, e.g. "3–30 days".', 'iflynepal' ),
		),
		'suitability'     => array(
			'box'   => 'card',
			'label' => __( 'Place or suitability', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'The second fact on the card, e.g. "Kathmandu" or "Beginner friendly".', 'iflynepal' ),
		),
		'price'           => array(
			'box'   => 'card',
			'label' => __( 'Price', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'Written exactly as it should read, e.g. "From US$425". Shown as typed — no currency conversion happens here.', 'iflynepal' ),
		),
		'peek'            => array(
			'box'   => 'card',
			'label' => __( 'Hover summary', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'One or two lines revealed over the card image. Left empty, the card shows no summary — the package\'s own text is never used here.', 'iflynepal' ),
		),
		'departures'      => array(
			'box'   => '',
			'label' => __( 'Fixed departure dates', 'iflynepal' ),
			'type'  => 'dates',
			'help'  => __( 'One date per line, as YYYY-MM-DD. Past dates are dropped automatically. Leave empty for a package that runs year-round.', 'iflynepal' ),
		),
		'booking_button'  => array(
			'box'   => 'payment',
			'label' => __( 'Payment button', 'iflynepal' ),
			'type'  => 'button',
			'help'  => __( 'Which Easy PayPal & Stripe button the Book now panel pays with. The amount, the currency and the payment methods all live on the button itself — and the button only appears on the page once that plugin has a PayPal or Stripe account connected.', 'iflynepal' ),
		),

		/*
		 * Two homepage sections, both opt-in per package, both fields sitting
		 * in one small side box above the Package Types taxonomy box.
		 *
		 * "Upcoming journeys" needs both of its fields to be ticked and dated:
		 * a tick with no month has no chip to file the card under, and a month
		 * with no tick is a package nobody asked to feature. See
		 * iflynepal_package_shows_on_homepage().
		 *
		 * "A few good reasons" needs only its own tick — the section's copy is
		 * "a curated mix ... not an endless catalogue", so showing every
		 * published package there by default would be exactly the catalogue
		 * that line is promising visitors this is not.
		 */
		'show_homepage'   => array(
			'box'   => 'homepage',
			'label' => __( 'Show in "Upcoming journeys"', 'iflynepal' ),
			'type'  => 'checkbox',
			'help'  => __( 'Shows this package in the "Upcoming journeys" rail on the front page, card style included.', 'iflynepal' ),
		),
		'available_month' => array(
			'box'   => 'homepage',
			'label' => __( 'When available?', 'iflynepal' ),
			'type'  => 'month',
			'help'  => __( 'The month this package next runs. Becomes one of the month filters on the homepage rail — pick it there to see this package.', 'iflynepal' ),
		),
		'show_reasons'    => array(
			'box'   => 'homepage',
			'label' => __( 'Show in "A few good reasons"', 'iflynepal' ),
			'type'  => 'checkbox',
			'help'  => __( 'Shows this package in the front page\'s curated card grid, filed under its own package type there.', 'iflynepal' ),
		),

		/*
		 * Client-directed, 13 Sep 2026: taken off the Payment box. Not deleted —
		 * a field whose box no screen draws is stored and still rendered, just
		 * not editable (the same holding state §5.3l put Highlights, Fixed
		 * departure dates and the Booking shortcode into when they left the
		 * Package Details box). This is the field behind scope item 3 (§5.1):
		 * the static "confirmed within 5–6 days" line for Trekking and
		 * Volunteering packages, informational only. Where it should live
		 * instead is P22's question now, alongside `departures`.
		 */
		'buffer_notice'   => array(
			'box'   => '',
			'label' => __( 'Confirmation notice', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'The static line beside the booking button, e.g. "Trekking and volunteering bookings are confirmed within 5–6 days." Informational only — nothing is delayed or enforced.', 'iflynepal' ),
		),
		'booking'         => array(
			'box'   => 'payment',
			'label' => __( 'Booking shortcode', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'Only if the button above cannot express what this package needs — an inline [wpecpp name="…" price="…"], or the shortcode of another gateway. Ignored while a button is chosen, and never printed if nothing on the site registers it.', 'iflynepal' ),
		),
	);

	/*
	 * The single-package model is declared in its own file and merged in here
	 * rather than kept apart, so there is still one schema: one sanitizer, one
	 * save routine and one place that answers "what can a package hold".
	 */
	$fields = array_merge( $fields, iflynepal_package_details_fields() );

	/**
	 * Filters the per-package detail fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array[] $fields Field definitions keyed by schema key.
	 */
	return apply_filters( 'iflynepal_package_detail_fields', $fields );
}

/**
 * The fields one meta box draws.
 *
 * A box renders from this and saves from it, and the two must be the same list.
 * Scoping only the rendering would be a data-loss bug rather than a tidy-up: the
 * save walks its list and treats a field the form did not submit as emptied, so
 * a box that drew five fields and saved nine would delete the other four on the
 * first Update — and those four hold the confirmation notice, the departure
 * dates and the booking shortcode. Same reasoning as the archive screen's
 * fields_for_term(), and the same mistake it was written to avoid.
 *
 * @since 1.0.0
 *
 * @param string $box Box key, e.g. 'card'.
 * @return array[] Field definitions keyed by schema key, in schema order.
 */
function iflynepal_package_fields_for_box( $box ) {
	$fields = array();

	foreach ( iflynepal_package_detail_fields() as $key => $field ) {
		$field_box = isset( $field['box'] ) ? $field['box'] : '';

		if ( $box === $field_box ) {
			$fields[ $key ] = $field;
		}
	}

	return $fields;
}

/**
 * The full meta key for a package detail field.
 *
 * @since 1.0.0
 *
 * @param string $key Schema key, e.g. 'price'.
 * @return string Meta key.
 */
function iflynepal_package_meta_key( $key ) {
	return IFLYNEPAL_PACKAGE_META_PREFIX . $key;
}

/**
 * One stored package detail.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Package.
 * @param string $key     Schema key.
 * @return string Stored value, empty string when unset.
 */
function iflynepal_package_field( $post_id, $key ) {
	return (string) get_post_meta( (int) $post_id, iflynepal_package_meta_key( $key ), true );
}

/**
 * A `lines` package field as a list.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Package.
 * @param string $key     Schema key.
 * @return string[] Non-empty lines, in order.
 */
function iflynepal_package_field_lines( $post_id, $key ) {
	$lines = preg_split( '/\R/', iflynepal_package_field( $post_id, $key ) );

	return array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
}

/**
 * Sanitizes one package detail according to its type.
 *
 * @since 1.0.0
 *
 * @param mixed  $value Raw submitted value, already unslashed.
 * @param string $type  Field type.
 * @param array  $field Optional. Field definition, for the types that need it.
 * @return string Value as it should be stored.
 */
function iflynepal_package_sanitize_value( $value, $type, $field = array() ) {
	switch ( $type ) {
		case 'checkbox':
			return $value ? '1' : '';

		case 'month':
			return iflynepal_package_sanitize_month( $value );

		case 'dates':
			return implode( "\n", iflynepal_package_sanitize_dates( $value ) );

		case 'cards':
			return iflynepal_archive_sanitize_cards( $value, $field );

		case 'gallery':
			return iflynepal_package_sanitize_gallery( $value, $field );

		case 'button':
		case 'image':
		case 'video':
			/*
			 * All three store a post ID, never a URL or a name. For the payment
			 * button that is what keeps the price out of this plugin: the ID
			 * names a button the gateway owns, and the amount is read from that
			 * button by the gateway at render time.
			 */
			return (string) absint( $value );

		case 'url':
			return esc_url_raw( trim( (string) $value ) );

		case 'rich':
			/*
			 * Headings carry an accent word an editor wraps in <em>, so this one
			 * cannot be sanitize_text_field: that strips the tag silently and the
			 * accent quietly stops working with nothing on screen to say why.
			 */
			return iflynepal_booking_kses_text( $value );

		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $value );

		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Keeps only the lines of a dates field that are real calendar dates.
 *
 * Checked with checkdate() rather than a regex, because 2026-02-30 matches any
 * reasonable pattern and is still not a day. Duplicates are dropped and the
 * result is sorted, so the stored value is always in the order it reads in.
 *
 * @since 1.0.0
 *
 * @param string $value Raw textarea value.
 * @return string[] Valid Y-m-d dates, unique and ascending.
 */
function iflynepal_package_sanitize_dates( $value ) {
	$dates = array();

	foreach ( preg_split( '/\R/', (string) $value ) as $line ) {
		$line = trim( $line );

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $line, $parts ) ) {
			continue;
		}

		if ( ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			continue;
		}

		$dates[ $line ] = $line;
	}

	sort( $dates );

	return $dates;
}

/**
 * Keeps a "when available" value only when it is a real month and year.
 *
 * Stored as the HTML month input's own YYYY-MM shape, which is also the shape
 * that sorts correctly as a plain string — no parsing is needed to put two of
 * these in date order.
 *
 * @since 1.0.0
 *
 * @param string $value Raw submitted value, e.g. '2026-09'.
 * @return string The value, or an empty string when it is not YYYY-MM.
 */
function iflynepal_package_sanitize_month( $value ) {
	$value = trim( (string) $value );

	if ( ! preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $value ) ) {
		return '';
	}

	return $value;
}

/**
 * Whether a package belongs on the homepage's "Upcoming journeys" rail.
 *
 * Both the tick and a month are required, not either alone: a ticked package
 * with no month has no chip to file its card under on the rail, and a month
 * typed against an unticked package is a package nobody asked to feature.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return bool
 */
function iflynepal_package_shows_on_homepage( $post_id ) {
	return '1' === iflynepal_package_field( $post_id, 'show_homepage' )
		&& '' !== iflynepal_package_field( $post_id, 'available_month' );
}

/**
 * A package's "when available" value, as stored.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return string YYYY-MM, or an empty string when unset.
 */
function iflynepal_package_available_month( $post_id ) {
	return iflynepal_package_field( $post_id, 'available_month' );
}

/**
 * A package's "when available" value, as a short chip label.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return string e.g. 'Sep 2026', or an empty string when unset.
 */
function iflynepal_package_available_month_label( $post_id ) {
	return iflynepal_booking_month_label( iflynepal_package_available_month( $post_id ) );
}

/**
 * Turns a stored YYYY-MM value into its short display label.
 *
 * Shared by the package accessor above and the archive-wide query in
 * includes/frontend/homepage-departures.php, so the two cannot read the same
 * value two different ways.
 *
 * @since 1.0.0
 *
 * @param string $value YYYY-MM, or empty.
 * @return string e.g. 'Sep 2026', or an empty string when $value is not usable.
 */
function iflynepal_booking_month_label( $value ) {
	if ( '' === $value ) {
		return '';
	}

	$timestamp = strtotime( $value . '-01' );

	return $timestamp ? date_i18n( 'M Y', $timestamp ) : '';
}

/**
 * One itinerary day's timeline, whatever shape it is stored in.
 *
 * The timeline used to be a textarea of `time | what happens` lines and is now a
 * repeater of rows. Both shapes are read here rather than migrated, for two
 * reasons: a day that has not been re-saved still publishes exactly what it
 * published before, and the admin control draws the legacy lines as rows, so
 * opening a day and pressing Update is the migration. Nothing is lost by never
 * doing it.
 *
 * @since 1.0.0
 *
 * @param mixed $value Stored timeline — a list of rows, or legacy line text.
 * @return array[] Stops, each with 'time' and 'text', in order.
 */
function iflynepal_package_timeline_rows( $value ) {
	$stops = array();

	if ( is_array( $value ) ) {
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$time = isset( $row['time'] ) ? trim( (string) $row['time'] ) : '';
			$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';

			if ( '' === $time && '' === $text ) {
				continue;
			}

			$stops[] = array(
				'time' => $time,
				'text' => $text,
			);
		}

		return $stops;
	}

	foreach ( preg_split( '/\R/', (string) $value ) as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		// Split once only: a description may well contain another pipe.
		$parts = explode( '|', $line, 2 );

		$stops[] = array(
			'time' => trim( $parts[0] ),
			'text' => isset( $parts[1] ) ? trim( $parts[1] ) : '',
		);
	}

	return $stops;
}

/**
 * Cleans a submitted timeline repeater into the rows that get stored.
 *
 * Same contract as the card repeater it sits inside: the posted numbering is an
 * artefact of how HTML names inputs and is discarded, the survivors are
 * re-indexed from zero, an entirely empty row is dropped, and the cap is
 * enforced here as well as in the browser because the form is not the only thing
 * that can post to this screen.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw submitted rows, already unslashed.
 * @param array $part  Part definition, carrying 'max'.
 * @return array[] Rows, each with 'time' and 'text'.
 */
function iflynepal_package_sanitize_timeline( $value, $part = array() ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$max   = isset( $part['max'] ) ? (int) $part['max'] : 0;
	$rows  = array();
	$stops = iflynepal_package_timeline_rows( $value );

	foreach ( $stops as $stop ) {
		$rows[] = array(
			'time' => sanitize_text_field( $stop['time'] ),
			'text' => sanitize_textarea_field( $stop['text'] ),
		);

		if ( $max > 0 && count( $rows ) >= $max ) {
			break;
		}
	}

	return $rows;
}

/**
 * A package's featured video, ready to put in a <video> element.
 *
 * The attachment is looked up on every read rather than trusted from the stored
 * ID: a video deleted from the media library later would otherwise leave the
 * page asking the browser for a file that is not there, and no save happens in
 * between to notice. Anything that is not a video attachment is treated as no
 * video at all, so the featured image goes on being what shows.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array Empty when there is no usable video, otherwise 'url' and 'mime'.
 */
function iflynepal_package_video( $post_id ) {
	$attachment_id = (int) iflynepal_package_field( $post_id, 'featured_video' );

	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return array();
	}

	$mime = (string) get_post_mime_type( $attachment_id );

	if ( 0 !== strpos( $mime, 'video/' ) ) {
		return array();
	}

	$url = wp_get_attachment_url( $attachment_id );

	if ( ! $url ) {
		return array();
	}

	return array(
		'id'   => $attachment_id,
		'url'  => $url,
		'mime' => $mime,
	);
}

/**
 * Upcoming fixed departures for a package.
 *
 * Derived on every read: which dates are still ahead depends on today, so a
 * stored "next departure" would be wrong by the following morning. Uses the
 * site's timezone, not the server's, or a date rolls over at the wrong hour.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @param int $limit   Most dates to return. 0 for all of them.
 * @return string[] Y-m-d dates from today onwards, ascending.
 */
function iflynepal_package_upcoming_departures( $post_id, $limit = 0 ) {
	$today = current_time( 'Y-m-d' );
	$dates = array();

	foreach ( iflynepal_package_field_lines( $post_id, 'departures' ) as $date ) {
		if ( $date >= $today ) {
			$dates[] = $date;
		}
	}

	sort( $dates );

	return $limit > 0 ? array_slice( $dates, 0, (int) $limit ) : $dates;
}

/**
 * A gallery field as a stored list of attachment IDs.
 *
 * IDs, never URLs: a URL breaks the day the site changes domain, and it throws
 * away the generated sizes and the alt text that came with the upload. The value
 * arrives from the form as one comma-separated string, which is what a hidden
 * input can carry.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw submitted value.
 * @param array $field Field definition, for its 'max'.
 * @return string Comma-separated attachment IDs.
 */
function iflynepal_package_sanitize_gallery( $value, $field = array() ) {
	$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	$max = isset( $field['max'] ) ? (int) $field['max'] : 0;

	/*
	 * Capped on save as well as in the browser: the form is not the only thing
	 * that can post to this screen.
	 */
	if ( $max > 0 && count( $ids ) > $max ) {
		$ids = array_slice( $ids, 0, $max );
	}

	return implode( ',', $ids );
}

/**
 * A stored card repeater on a package, ready to loop over in a template.
 *
 * Every declared part is present on every row, so a template can read a part
 * that was added to the schema after a row was saved without testing for it.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Package.
 * @param string $key     Schema key.
 * @return array[] Rows, each keyed by the field's declared parts.
 */
function iflynepal_package_cards( $post_id, $key ) {
	$fields = iflynepal_package_detail_fields();
	$field  = isset( $fields[ $key ] ) ? $fields[ $key ] : array();
	$rows   = get_post_meta( (int) $post_id, iflynepal_package_meta_key( $key ), true );

	if ( ! is_array( $rows ) || empty( $field['parts'] ) ) {
		return array();
	}

	$clean = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$card = array();

		foreach ( $field['parts'] as $part_key => $part ) {
			$card[ $part_key ] = isset( $row[ $part_key ] ) ? $row[ $part_key ] : '';
		}

		$clean[] = $card;
	}

	return $clean;
}

/**
 * A package's gallery as attachment IDs.
 *
 * Each ID is checked against the media library on read rather than on save: an
 * attachment deleted later would otherwise leave the page rendering an empty
 * figure, and no save happens in between to clean it up.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return int[] Attachment IDs that still exist, in order.
 */
function iflynepal_package_gallery( $post_id ) {
	$ids  = array_filter( array_map( 'absint', explode( ',', iflynepal_package_field( $post_id, 'gallery' ) ) ) );
	$live = array();

	foreach ( $ids as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			$live[] = $id;
		}
	}

	return $live;
}

/**
 * A package's group-size price tiers, ready to render or to price with.
 *
 * Stored rows are free text, because nothing on a package is enforced on save.
 * Everything a tier is *used* for is arithmetic, so the reading happens here,
 * once: a row without a usable price or a usable starting group size is not a
 * tier at all and is dropped rather than rendered as a blank line or priced at
 * zero. Rows come back sorted by group size however they were typed, so the
 * ladder on the page always climbs.
 *
 * `to` is 0 for an open-ended top tier ("13 or more"), which is the ordinary
 * shape of the last rung rather than a missing value.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array[] Tiers, each with 'from', 'to', 'price', 'was' and 'label'.
 */
function iflynepal_package_price_tiers( $post_id ) {
	$tiers = array();

	foreach ( iflynepal_package_cards( $post_id, 'price_tiers' ) as $row ) {
		$from  = max( 1, (int) preg_replace( '/[^0-9]/', '', (string) $row['pax_from'] ) );
		$to    = (int) preg_replace( '/[^0-9]/', '', (string) $row['pax_to'] );
		$price = (float) preg_replace( '/[^0-9.]/', '', (string) $row['price'] );
		$was   = (float) preg_replace( '/[^0-9.]/', '', (string) $row['was'] );

		if ( $price <= 0 ) {
			continue;
		}

		/*
		 * A top limit below the bottom one is a typo, and the two readings of
		 * it — an empty band, or the numbers swapped — are both guesses. The
		 * tier is left open-ended instead, which is the one reading that
		 * cannot price anybody out of a group they are actually in.
		 */
		if ( $to > 0 && $to < $from ) {
			$to = 0;
		}

		$tiers[] = array(
			'from'  => $from,
			'to'    => $to,
			'price' => $price,
			/* A "was" at or below the price is not a discount, so it is not shown as one. */
			'was'   => $was > $price ? $was : 0.0,
			'label' => iflynepal_package_price_tier_label( $from, $to ),
		);
	}

	usort(
		$tiers,
		static function ( $a, $b ) {
			return $a['from'] <=> $b['from'];
		}
	);

	return $tiers;
}

/**
 * How one tier's group size reads on the page.
 *
 * @since 1.0.0
 *
 * @param int $from Smallest group in the tier.
 * @param int $to   Largest, or 0 for no upper limit.
 * @return string Label, e.g. "1–12 pax".
 */
function iflynepal_package_price_tier_label( $from, $to ) {
	if ( $to > 0 && $to !== $from ) {
		/* translators: 1: smallest group size, 2: largest group size. */
		return sprintf( __( '%1$d–%2$d pax', 'iflynepal' ), $from, $to );
	}

	if ( $to > 0 ) {
		/* translators: %d: group size. */
		return sprintf( __( '%d pax', 'iflynepal' ), $from );
	}

	/* translators: %d: smallest group size in an open-ended tier. */
	return sprintf( __( '%d+ pax', 'iflynepal' ), $from );
}

/**
 * The tier a given number of travellers falls in.
 *
 * A count outside every tier still gets one, because the page has to quote
 * *some* price: below the first tier's floor — including the zero the
 * calculator starts at by design — the first tier stands, and above the last
 * tier's ceiling the last one does. Falling back to the first in both
 * directions would quote a group of thirty the small-group rate, which is the
 * one answer a ladder exists to rule out.
 *
 * @since 1.0.0
 *
 * @param array[] $tiers Tiers from iflynepal_package_price_tiers().
 * @param int     $pax   How many are travelling.
 * @return array|null The matching tier, or null when there are no tiers.
 */
function iflynepal_package_price_tier_for_pax( $tiers, $pax ) {
	if ( empty( $tiers ) ) {
		return null;
	}

	$pax  = (int) $pax;
	$last = $tiers[ count( $tiers ) - 1 ];

	foreach ( $tiers as $tier ) {
		if ( $pax >= $tier['from'] && ( 0 === $tier['to'] || $pax <= $tier['to'] ) ) {
			return $tier;
		}
	}

	return $pax > $last['to'] && $last['to'] > 0 ? $last : $tiers[0];
}
