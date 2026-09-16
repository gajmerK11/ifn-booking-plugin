<?php
/**
 * Read-only check: what every plausible bucket now reads as, and whether the
 * wording is true of every day inside the bucket.
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$cases = array(
	array( 1, 1 ),
	array( 3, 5 ),
	array( 6, 9 ),
	array( 10, 13 ),
	array( 10, 14 ),
	array( 14, 14 ),
	array( 14, 20 ),
	array( 14, 28 ),
	array( 15, 28 ),
	array( 21, 42 ),
	array( 28, 56 ),
	array( 12, null ),
	array( 14, null ),
	array( 15, null ),
	array( 21, null ),
	array( 30, null ),
);

printf( "threshold: %d days\n\n", iflynepal_trip_finder_weeks_from() );
printf( "%-14s %-18s %s\n", 'RANGE (days)', 'LABEL', 'TRUE OF EVERY DAY IN RANGE?' );
echo str_repeat( '-', 78 ), "\n";

foreach ( $cases as $case ) {
	list( $min, $max ) = $case;
	$label = iflynepal_trip_finder_duration_label( $min, $max );

	$verdict = 'n/a (days)';

	if ( false !== strpos( $label, 'week' ) ) {
		/*
		 * A weeks label must not claim a floor the bucket does not have: every
		 * day in the range has to be at least the stated lowest number of whole
		 * weeks.
		 */
		preg_match( '/(\d+)/', $label, $m );
		$claimed = (int) $m[1];
		$verdict = ( floor( $min / 7 ) >= $claimed ) ? 'yes' : 'NO — overstates its floor';
	}

	printf(
		"%-14s %-18s %s\n",
		null === $max ? $min . '+' : $min . '-' . $max,
		$label,
		$verdict
	);
}

echo "\nLive buckets on this site (Packages > Settings):\n";

foreach ( iflynepal_trip_finder_durations() as $bucket ) {
	printf(
		"  key=%-12s min=%-3d max=%-4s  %s\n",
		$bucket['key'],
		$bucket['min'],
		null === $bucket['max'] ? 'none' : $bucket['max'],
		$bucket['label']
	);
}
