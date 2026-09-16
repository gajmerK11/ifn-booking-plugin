<?php
/**
 * The homepage hero's trip-finder picker, answered from the plugin's side.
 *
 * Same shape as includes/frontend/homepage-reasons.php and
 * homepage-departures.php: the theme owns the hero's markup, its layout and
 * the picker control itself, and asks one filter for the two facts it cannot
 * know on its own — which top-level package types exist, and where the
 * results for a visitor's selection are shown. The theme never learns what a
 * package_type term is.
 *
 * The results page is the `[iflynepal_type_explorer]` shortcode
 * (includes/frontend/type-explorer.php) — an ordinary editor-owned Page,
 * chosen once at Packages > Settings rather than assumed at a fixed slug, so
 * the picker degrades to simply not rendering until that choice is made
 * instead of linking to a page that may not exist.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The top-level package types offered in the picker.
 *
 * Every top-level term, unconditionally — unlike the archive's own filter row
 * (iflynepal_archive_filter_terms()), which only offers a button once a
 * package on the page actually answers to it. That rule exists to stop a
 * filter button from emptying the grid it sits above; this picker links out
 * to each type's own archive rather than filtering anything in place, so a
 * type with nothing published yet is still a real category worth offering —
 * the same reasoning the site's main navigation already follows.
 *
 * @since 1.0.0
 *
 * @return array[] Each with 'slug' and 'label', in taxonomy order.
 */
function iflynepal_trip_finder_types() {
	if ( ! taxonomy_exists( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return array();
	}

	$top_level = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'parent'     => 0,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $top_level ) || ! $top_level ) {
		return array();
	}

	$types = array();

	foreach ( $top_level as $term ) {
		$types[] = array(
			'slug'  => $term->slug,
			'label' => $term->name,
		);
	}

	return $types;
}

/**
 * The URL the picker's form submits to.
 *
 * Read through iflynepal_booking_setting() rather than get_option()
 * directly, the same reasoning as every other setting in this plugin: a row
 * written by anything but the settings form is still sanitized on the way
 * out.
 *
 * Empty until an editor has chosen a page at Packages > Settings, or once
 * that page has been trashed or unpublished out from under the setting — a
 * link to a 404 is worse than no picker at all, and this is what
 * iflynepal_has_trip_finder() gates the whole section on.
 *
 * @since 1.0.0
 *
 * @return string Permalink, or '' when unconfigured.
 */
function iflynepal_trip_finder_url() {
	$page_id = absint( iflynepal_booking_setting( 'trip_finder_page' ) );

	if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
		return '';
	}

	$permalink = get_permalink( $page_id );

	return $permalink ? $permalink : '';
}

/**
 * The most length options the picker will carry.
 *
 * A cap rather than a limit anybody is likely to reach: the control is a
 * dropdown a visitor reads in one glance, and a list long enough to scroll has
 * stopped being a quick answer to "how long have you got". Enforced on save as
 * well as in the browser — the settings form is not the only thing that can
 * write this option.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_TRIP_FINDER_MAX_DURATIONS = 8;

/**
 * The buckets the picker falls back to when none has been configured.
 *
 * Taken from the design file's own `<select id="days">` verbatim — 3–5 / 6–9 /
 * 10–14 / 15+ — which is what the picker carried before it was configurable, so
 * a site that never opens the setting sees exactly what it saw before.
 *
 * `max` is null for the open-ended top bucket: "15 or more days" has no ceiling
 * to compare against, and the explore page reads a null max as "no upper bound"
 * rather than as "no bucket".
 *
 * @since 1.0.0
 *
 * @return array<int,array{min:int,max:int|null}>
 */
function iflynepal_trip_finder_default_durations() {
	return array(
		array(
			'min' => 3,
			'max' => 5,
		),
		array(
			'min' => 6,
			'max' => 9,
		),
		array(
			'min' => 10,
			'max' => 14,
		),
		array(
			'min' => 15,
			'max' => null,
		),
	);
}

/**
 * The day numbers the settings screen offers in its dropdowns.
 *
 * Every whole day from the shortest published package to the longest, so a
 * boundary can be drawn anywhere sensible but never at a length this business
 * does not sell. Derived on every read rather than stored: a catalogue gains and
 * loses packages, and a stored list of choices would go stale the first time one
 * was published.
 *
 * ⚠ Only packages carrying a Trip duration (days) value count, because only they
 * can ever match a bucket. On this site most do not yet — the seeded sample
 * packages have none — so the range is narrower than the catalogue looks.
 *
 * Falls back to 1–30 when nothing has a duration at all. That is not the
 * catalogue's answer and does not pretend to be: without it the screen would
 * offer an empty dropdown, and the picker could not be configured until somebody
 * had published a package with a duration — a dead end of exactly the kind the
 * rest of this plugin avoids. The screen says which of the two it is showing.
 *
 * @since 1.0.0
 *
 * @return array{days:int[],derived:bool} The choices, and whether they came from the catalogue.
 */
function iflynepal_trip_finder_duration_choices() {
	$ids = get_posts(
		array(
			'post_type'              => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status'            => 'publish',
			'numberposts'            => -1,
			'fields'                 => 'ids',
			'meta_key'               => iflynepal_package_meta_key( 'duration_days' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the packages carrying this key are the point of the query.
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	$days = array();

	foreach ( $ids as $id ) {
		$value = absint( iflynepal_package_field( $id, 'duration_days' ) );

		if ( $value > 0 ) {
			$days[] = $value;
		}
	}

	if ( ! $days ) {
		return array(
			'days'    => range( 1, 30 ),
			'derived' => false,
		);
	}

	return array(
		'days'    => range( min( $days ), max( $days ) ),
		'derived' => true,
	);
}

/**
 * The day count at or above which a bucket is worded in weeks rather than days.
 *
 * Two weeks. Below it a trip is a number of days and everybody counts it that
 * way; at and above it nobody does — the client's own cards already say
 * "2 TO 4 WEEKS" on the volunteering and homestay packages, and a filter that
 * answers that card with "15+ days" is asking the visitor to do the division.
 *
 * Move it with the iflynepal_trip_finder_weeks_from filter rather than by
 * editing this, and set it above every bucket to turn weeks off entirely.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_TRIP_FINDER_WEEKS_FROM = 14;

/**
 * The day count from which a bucket reads in weeks.
 *
 * @since 1.0.0
 *
 * @return int Days. A bucket whose lowest day is at or above this reads in weeks.
 */
function iflynepal_trip_finder_weeks_from() {
	/**
	 * Filters the day count at which duration wording switches to weeks.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Default two weeks.
	 */
	return max( 1, (int) apply_filters( 'iflynepal_trip_finder_weeks_from', IFLYNEPAL_TRIP_FINDER_WEEKS_FROM ) );
}

/**
 * Which unit a bucket is spoken in.
 *
 * The same threshold the wording uses, exposed on its own so the archive's
 * Duration facet can group its options by unit without re-deriving the rule and
 * without parsing the label back out — a label is translated, and a translated
 * label does not contain the word "week".
 *
 * @since 1.0.0
 *
 * @param int $min A bucket's lowest day count.
 * @return string 'days' or 'weeks'.
 */
function iflynepal_trip_finder_duration_unit( $min ) {
	return (int) $min >= iflynepal_trip_finder_weeks_from() ? 'weeks' : 'days';
}

/**
 * One bucket's wording.
 *
 * Generated from its numbers rather than typed, which is the whole point of the
 * control: a label somebody writes by hand is a label that can say "3–5 days"
 * over a bucket that matches six to nine, and nothing on the screen would ever
 * say so.
 *
 * Short buckets read in days and long ones in weeks, from one threshold — see
 * iflynepal_trip_finder_weeks_from(). The unit is wording only: the stored
 * numbers, the `key`, the `?days=` query string and everything that matches a
 * package against a bucket stay in days throughout. So this changes no data,
 * needs no migration, and cannot put two units into the same comparison.
 *
 * 🔴 The two are one facet on purpose. A package has exactly one length, so a
 * separate Days control and Weeks control combined with AND — "10–14 days" and
 * "3 weeks" at once — could only ever empty the grid. One list, worded in
 * whichever unit each row calls for, is the same offer without the dead end.
 *
 * An en dash between the two numbers, not a hyphen — it is a range, and it is
 * what the design file uses.
 *
 * @since 1.0.0
 *
 * @param int      $min Lowest day count.
 * @param int|null $max Highest, or null for open-ended.
 * @return string
 */
function iflynepal_trip_finder_duration_label( $min, $max ) {
	$min = (int) $min;

	if ( $min >= iflynepal_trip_finder_weeks_from() ) {
		return iflynepal_trip_finder_duration_label_weeks( $min, $max );
	}

	if ( null === $max ) {
		/* translators: %d: the lowest number of days in an open-ended bucket, e.g. "15+ days". */
		return sprintf( __( '%d+ days', 'iflynepal' ), $min );
	}

	if ( (int) $max === $min ) {
		/* translators: %d: a number of days. */
		return sprintf( _n( '%d day', '%d days', $min, 'iflynepal' ), $min );
	}

	return sprintf(
		/* translators: 1: lowest number of days, 2: highest. The separator is an en dash. */
		__( '%1$d–%2$d days', 'iflynepal' ),
		$min,
		(int) $max
	);
}

/**
 * A long bucket's wording, in weeks.
 *
 * 🔴 The rounding is not the same at both ends, and the difference is the
 * difference between a true label and a false one.
 *
 * An open bucket floors: "15 days or more" is every trip of at least two whole
 * weeks, so it reads "2+ weeks". Rounding up would give "3+ weeks" over a
 * bucket that holds fifteen-day trips, which is simply untrue — and a filter
 * label that overstates its own floor is worse than one that is a little loose.
 *
 * A closed bucket rounds both ends to the nearest week, because it has a real
 * ceiling to be honest about and "2–4 weeks" over 15–28 days reads the way the
 * client's own cards already do.
 *
 * @since 1.0.0
 *
 * @param int      $min Lowest day count, at or above the weeks threshold.
 * @param int|null $max Highest, or null for open-ended.
 * @return string
 */
function iflynepal_trip_finder_duration_label_weeks( $min, $max ) {
	if ( null === $max ) {
		/* translators: %d: the lowest whole number of weeks in an open-ended bucket, e.g. "2+ weeks". */
		return sprintf( __( '%d+ weeks', 'iflynepal' ), (int) floor( $min / 7 ) );
	}

	$low  = (int) round( $min / 7 );
	$high = (int) round( (int) $max / 7 );

	if ( $high <= $low ) {
		/* translators: %d: a number of weeks. */
		return sprintf( _n( '%d week', '%d weeks', $low, 'iflynepal' ), $low );
	}

	return sprintf(
		/* translators: 1: lowest number of weeks, 2: highest. The separator is an en dash. */
		__( '%1$d–%2$d weeks', 'iflynepal' ),
		$low,
		$high
	);
}

/**
 * The trip-length buckets offered in the picker's "I have" field, and the
 * numeric range each one matches a package's Trip duration (days) field
 * against.
 *
 * Configured at Packages > Settings and read through iflynepal_booking_setting(),
 * so a row written by anything but that form is still sanitized on the way out —
 * the same rule every other setting in this plugin follows.
 *
 * The `key` is derived from the numbers rather than stored. It is what appears in
 * the `?days=` query string and what iflynepal_explore_selected_duration()
 * matches a request against, so deriving it means a bucket an editor has just
 * changed stops answering to its old key — which is correct: the old key named a
 * range that no longer exists, and honouring it would return packages the URL
 * does not describe.
 *
 * Declared in exactly one place, so the hero picker and the explore page's
 * matching cannot disagree about what a bucket means.
 *
 * @since 1.0.0
 *
 * @return array[] Each with 'key', 'label', 'min' and 'max' (int|null).
 */
function iflynepal_trip_finder_durations() {
	$rows = iflynepal_booking_setting( 'trip_finder_durations' );

	if ( ! is_array( $rows ) || ! $rows ) {
		return array();
	}

	$buckets = array();

	foreach ( $rows as $row ) {
		$min = isset( $row['min'] ) ? absint( $row['min'] ) : 0;

		if ( $min < 1 ) {
			continue;
		}

		$max = ( isset( $row['max'] ) && null !== $row['max'] && '' !== $row['max'] ) ? absint( $row['max'] ) : null;

		$buckets[] = array(
			'key'   => null === $max ? $min . '-plus' : $min . '-' . $max,
			'label' => iflynepal_trip_finder_duration_label( $min, $max ),
			'unit'  => iflynepal_trip_finder_duration_unit( $min ),
			'min'   => $min,
			'max'   => $max,
		);
	}

	return $buckets;
}

/**
 * The price brackets the homepage picker offers, across the whole catalogue.
 *
 * A sibling of iflynepal_trip_finder_durations(), and deliberately built a
 * different way. Trip lengths are editor-configured, because "a week" means the
 * same thing whatever is in the catalogue. A price bracket does not: it only
 * means something against the prices this business actually charges, so these
 * are derived from every published package exactly as the archive's own Budget
 * facet derives its brackets from the packages on one page.
 *
 * Same machinery, wider input: iflynepal_archive_budget_terms() over the whole
 * catalogue rather than over one archive's grid. A site that publishes one
 * package, or several all at one price, gets no brackets and the picker leaves
 * the field out — a control offering a single choice is not a choice.
 *
 * ⚠ The keys are therefore NOT the keys any one archive uses. Both are built
 * from a price spread, and the spread of the whole catalogue is not the spread
 * of a category within it. That is why this only ever reaches /explore/, which
 * validates against this same function, and why an archive's own ?budget=
 * carry-over validates against the archive's own options and drops what it does
 * not recognise.
 *
 * Cached for the request. The homepage prints the picker and the explore page
 * reads it back on the same load, and the query behind it is every published
 * package.
 *
 * @since 1.0.0
 *
 * @return array[] Brackets, each with 'key', 'label', 'min' and 'max'.
 */
function iflynepal_trip_finder_budgets() {
	static $budgets = null;

	if ( null !== $budgets ) {
		return $budgets;
	}

	$budgets = iflynepal_archive_budget_terms(
		get_posts(
			array(
				'post_type'              => IFLYNEPAL_PACKAGE_POST_TYPE,
				'post_status'            => 'publish',
				'numberposts'            => -1,
				'update_post_term_cache' => false,
			)
		)
	);

	return $budgets;
}

/**
 * Answers the theme's "what does the trip-finder picker need" filter.
 *
 * @since 1.0.0
 *
 * @param array $payload Carries 'types', 'url', 'durations' and 'budgets', all
 *                        empty by default.
 * @return array Same shape, filled in.
 */
function iflynepal_booking_homepage_trip_finder( $payload ) {
	$payload              = is_array( $payload ) ? $payload : array();
	$payload['types']     = iflynepal_trip_finder_types();
	$payload['url']       = iflynepal_trip_finder_url();
	$payload['durations'] = iflynepal_trip_finder_durations();
	$payload['budgets']   = iflynepal_trip_finder_budgets();

	return $payload;
}
add_filter( 'iflynepal_homepage_trip_finder', 'iflynepal_booking_homepage_trip_finder' );
