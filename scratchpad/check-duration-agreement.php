<?php
/**
 * Read-only check that the two places a visitor can ask "how long?" now agree.
 *
 * For every package type and every trip-length bucket, compares the set the
 * archive's own Duration facet would show against the set /explore/ returns for
 * the same bucket. They read the same field through the same parser now, so any
 * difference is a bug rather than a design choice.
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$buckets = iflynepal_trip_finder_durations();
$types   = get_terms(
	array(
		'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
		'parent'     => 0,
		'hide_empty' => false,
	)
);

$mismatches = 0;

foreach ( $types as $type ) {
	$packages = iflynepal_archive_packages( $type->term_id, 100 );

	if ( ! $packages ) {
		continue;
	}

	printf( "\n%s (%d packages)\n", $type->name, count( $packages ) );
	echo str_repeat( '-', 66 ), "\n";

	foreach ( $buckets as $bucket ) {
		/* What the archive's facet would show: cards carrying this bucket key. */
		$archive = array();

		foreach ( $packages as $package ) {
			if ( in_array( $bucket['key'], iflynepal_archive_duration_keys( $package->ID, $buckets ), true ) ) {
				$archive[] = $package->ID;
			}
		}

		/* What /explore/ returns, with the per-type display cap lifted. */
		$explore = wp_list_pluck( iflynepal_explore_packages_for_term( $type->term_id, 999, $bucket ), 'ID' );

		sort( $archive );
		sort( $explore );

		$agree = ( $archive === $explore );

		if ( ! $agree ) {
			++$mismatches;
		}

		printf(
			"  %-12s %-14s archive=%-3d explore=%-3d %s\n",
			$bucket['key'],
			$bucket['label'],
			count( $archive ),
			count( $explore ),
			$agree ? 'agree' : 'MISMATCH: ' . implode( ',', array_diff( $archive, $explore ) ) . ' / ' . implode( ',', array_diff( $explore, $archive ) )
		);
	}
}

printf( "\n%s\n", 0 === $mismatches ? 'All buckets agree on both pages.' : $mismatches . ' MISMATCHES.' );
