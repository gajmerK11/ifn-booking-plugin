<?php
/**
 * Read-only check of the card-Duration parser and the buckets it produces.
 *
 * Runs every wording on this site plus a set of awkward ones, and prints which
 * buckets each package would be offered under.
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$buckets = iflynepal_trip_finder_durations();

echo "buckets: ";

foreach ( $buckets as $bucket ) {
	printf( '%s[%s..%s] ', $bucket['key'], $bucket['min'], null === $bucket['max'] ? '' : $bucket['max'] );
}

echo "\n\n";

$synthetic = array(
	'3 Weeks',
	'2 to 4 weeks',
	'15 days',
	'3-30 DAYS',
	'1 to 3 days',
	'14 to 16 days',
	'7 to 24 days',
	'2 to 4 days',
	'one month',
	'2 months',
	'10–14 days',
	'10 — 14 days',
	'5/7 days',
	'Flexible',
	'Year-round',
	'',
	'10-3 days',
	'<b>4 weeks</b>',
);

printf( "%-22s %-14s %s\n", 'TEXT', 'DAYS SPAN', 'BUCKETS' );
echo str_repeat( '-', 74 ), "\n";

foreach ( $synthetic as $text ) {
	$range = iflynepal_parse_duration_text( $text );
	$keys  = array();

	if ( null !== $range ) {
		foreach ( $buckets as $bucket ) {
			if ( iflynepal_duration_range_overlaps( $range, $bucket ) ) {
				$keys[] = $bucket['key'];
			}
		}
	}

	printf(
		"%-22s %-14s %s\n",
		'' === $text ? '(empty)' : mb_substr( $text, 0, 20 ),
		null === $range ? '—' : $range['min'] . '..' . $range['max'],
		$keys ? implode( ' ', $keys ) : '(none)'
	);
}

echo "\n\nEvery published package:\n";
printf( "%-46s %-16s %-11s %s\n", 'PACKAGE', 'CARD TEXT', 'DAYS SPAN', 'BUCKETS' );
echo str_repeat( '-', 108 ), "\n";

$packages = get_posts(
	array(
		'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby'     => 'title',
		'order'       => 'ASC',
	)
);

$unplaced = 0;

foreach ( $packages as $package ) {
	$text  = trim( (string) iflynepal_package_field( $package->ID, 'duration' ) );
	$range = iflynepal_package_duration_range( $package->ID );
	$keys  = iflynepal_archive_duration_keys( $package->ID, $buckets );

	if ( ! $keys ) {
		++$unplaced;
	}

	printf(
		"%-46s %-16s %-11s %s\n",
		mb_substr( html_entity_decode( $package->post_title ), 0, 44 ),
		'' === $text ? '—' : mb_substr( $text, 0, 14 ),
		null === $range ? '—' : $range['min'] . '..' . $range['max'],
		$keys ? implode( ' ', $keys ) : '(none)'
	);
}

printf( "\n%d of %d packages are under no duration bucket.\n", $unplaced, count( $packages ) );
