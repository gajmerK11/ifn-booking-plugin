<?php
/**
 * Puts the site into a state where the Duration facet has both day-length and
 * week-length buckets to draw, so the Days/Weeks split can be seen on a real
 * page, then puts it back exactly as it was.
 *
 * The dev catalogue's longest package is 15 days, so no week bucket can ever
 * match and the split never renders — which is correct behaviour and useless
 * for checking the thing works. This writes a mixed bucket list and lengthens
 * two packages, and stores what it overwrote so --restore is exact rather than
 * a guess at what the values used to be.
 *
 *   php scratchpad/test-week-buckets.php --setup
 *   php scratchpad/test-week-buckets.php --restore
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

const BACKUP_OPTION = '_iflynepal_week_bucket_test_backup';

$mode = in_array( '--restore', $argv, true ) ? 'restore' : ( in_array( '--setup', $argv, true ) ? 'setup' : '' );

if ( '' === $mode ) {
	echo "Pass --setup or --restore.\n";
	exit( 1 );
}

$settings_option = 'iflynepal_booking_settings';

if ( 'setup' === $mode ) {
	if ( get_option( BACKUP_OPTION ) ) {
		echo "A backup already exists — run --restore first, or this would overwrite it.\n";
		exit( 1 );
	}

	/*
	 * Three packages on ONE archive, so that archive has a short bucket and a
	 * long one to draw at the same time — the only state in which the
	 * Days/Weeks split is supposed to appear at all.
	 */
	$targets = get_posts(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => 3,
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'tax_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => IFLYNEPAL_PACKAGE_TAXONOMY,
					'field'            => 'slug',
					'terms'            => 'trekking-nepal',
					'include_children' => true,
				),
			),
		)
	);

	if ( count( $targets ) < 3 ) {
		printf( "Only %d packages under trekking-nepal; need 3.\n", count( $targets ) );
		exit( 1 );
	}

	$lengths = array( '4', '21', '35' );

	$backup = array(
		'settings' => get_option( $settings_option ),
		'packages' => array(),
	);

	$key = iflynepal_package_meta_key( 'duration_days' );

	foreach ( $targets as $i => $package ) {
		$backup['packages'][ $package->ID ] = get_post_meta( $package->ID, $key, true );
		update_post_meta( $package->ID, $key, $lengths[ $i ] );
		printf( "package #%d \"%s\" -> %s days\n", $package->ID, $package->post_title, $lengths[ $i ] );
	}

	$settings                            = (array) get_option( $settings_option, array() );
	$settings['trip_finder_durations']   = array(
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
			'max' => 13,
		),
		array(
			'min' => 14,
			'max' => 27,
		),
		array(
			'min' => 28,
			'max' => null,
		),
	);
	update_option( $settings_option, $settings );
	update_option( BACKUP_OPTION, $backup, false );

	echo "\nbuckets now:\n";

	foreach ( iflynepal_trip_finder_durations() as $bucket ) {
		printf( "  %-10s %-14s (%s)\n", $bucket['key'], $bucket['label'], $bucket['unit'] );
	}

	echo "\nSetup done. Run --restore when finished.\n";
	exit( 0 );
}

$backup = get_option( BACKUP_OPTION );

if ( ! $backup ) {
	echo "No backup stored — nothing to restore.\n";
	exit( 1 );
}

$key = iflynepal_package_meta_key( 'duration_days' );

foreach ( $backup['packages'] as $post_id => $value ) {
	if ( '' === $value || null === $value ) {
		delete_post_meta( $post_id, $key );
		printf( "package #%d -> meta deleted (had none)\n", $post_id );
	} else {
		update_post_meta( $post_id, $key, $value );
		printf( "package #%d -> %s\n", $post_id, $value );
	}
}

if ( false === $backup['settings'] ) {
	delete_option( $settings_option );
} else {
	update_option( $settings_option, $backup['settings'] );
}

delete_option( BACKUP_OPTION );

echo "\nbuckets restored to:\n";

foreach ( iflynepal_trip_finder_durations() as $bucket ) {
	printf( "  %-10s %-14s (%s)\n", $bucket['key'], $bucket['label'], $bucket['unit'] );
}

echo "\nRestore done.\n";
