<?php
/**
 * Read-only audit: what the Duration facet has to work with.
 *
 * Prints every published package's Trip duration (days), its free-text card
 * label, and whether its itinerary is day-paced or week-paced — the three
 * facts any "days vs weeks" split would have to be built on.
 *
 * Run with Local's own PHP binary; see §10.7 of the project context.
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$packages = get_posts(
	array(
		'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
		'post_status' => 'publish',
		'numberposts' => -1,
	)
);

printf( "%-52s %-6s %-22s %-6s %-6s %s\n", 'PACKAGE', 'DAYS', 'CARD LABEL', 'DCARD', 'WCARD', 'TYPE' );
echo str_repeat( '-', 130 ), "\n";

$with_days  = 0;
$with_weeks = 0;
$no_days    = 0;
$values     = array();

foreach ( $packages as $package ) {
	$days  = iflynepal_package_field( $package->ID, 'duration_days' );
	$label = iflynepal_package_field( $package->ID, 'duration_label' );
	$dcard = count( iflynepal_package_cards( $package->ID, 'itinerary_days' ) );
	$wcard = count( iflynepal_package_cards( $package->ID, 'itinerary_weeks' ) );

	$terms = get_the_terms( $package->ID, IFLYNEPAL_PACKAGE_TAXONOMY );
	$type  = is_array( $terms ) ? implode( ',', wp_list_pluck( $terms, 'slug' ) ) : '-';

	if ( '' === trim( (string) $days ) ) {
		++$no_days;
	} else {
		$values[] = (int) $days;
	}

	if ( $dcard ) {
		++$with_days;
	}

	if ( $wcard ) {
		++$with_weeks;
	}

	printf(
		"%-52s %-6s %-22s %-6d %-6d %s\n",
		mb_substr( $package->post_title, 0, 50 ),
		'' === trim( (string) $days ) ? '-' : $days,
		mb_substr( (string) $label, 0, 20 ),
		$dcard,
		$wcard,
		mb_substr( $type, 0, 28 )
	);
}

echo "\n";
printf( "published packages ......... %d\n", count( $packages ) );
printf( "with duration_days ......... %d\n", count( $values ) );
printf( "without duration_days ...... %d  (excluded from the Duration facet entirely)\n", $no_days );
printf( "day-paced itinerary ........ %d\n", $with_days );
printf( "week-paced itinerary ....... %d\n", $with_weeks );

if ( $values ) {
	sort( $values );
	printf( "duration_days values ....... %s\n", implode( ', ', $values ) );
	printf( "range ...................... %d to %d days\n", min( $values ), max( $values ) );
}

echo "\nConfigured trip-length buckets (Packages > Settings):\n";

foreach ( iflynepal_trip_finder_durations() as $bucket ) {
	printf( "  %-14s %s\n", $bucket['key'], $bucket['label'] );
}
