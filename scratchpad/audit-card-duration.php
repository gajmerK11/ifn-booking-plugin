<?php
/**
 * Read-only audit of the Package Card box's free-text Duration field.
 *
 * This is the field printed on the card ("3 WEEKS", "5 TO 15 DAYS"), as
 * distinct from the numeric Trip duration (days) on the Dates and price panel.
 * Prints every published package's value verbatim so the wordings that have to
 * be parsed are the real ones rather than ones anybody imagined.
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
		'orderby'     => 'title',
		'order'       => 'ASC',
	)
);

printf( "%-48s %-24s %-8s\n", 'PACKAGE', 'CARD DURATION (free text)', 'DAYS' );
echo str_repeat( '-', 92 ), "\n";

$wordings = array();

foreach ( $packages as $package ) {
	$text = trim( (string) iflynepal_package_field( $package->ID, 'duration' ) );
	$days = trim( (string) iflynepal_package_field( $package->ID, 'duration_days' ) );

	if ( '' !== $text ) {
		$wordings[ $text ] = isset( $wordings[ $text ] ) ? $wordings[ $text ] + 1 : 1;
	}

	printf(
		"%-48s %-24s %-8s\n",
		mb_substr( html_entity_decode( $package->post_title ), 0, 46 ),
		'' === $text ? '—' : mb_substr( $text, 0, 22 ),
		'' === $days ? '—' : $days
	);
}

echo "\ndistinct card wordings, most common first:\n";
arsort( $wordings );

foreach ( $wordings as $text => $n ) {
	printf( "  %-28s x%d\n", $text, $n );
}

printf( "\n%d published packages, %d with a card duration.\n", count( $packages ), array_sum( $wordings ) );
