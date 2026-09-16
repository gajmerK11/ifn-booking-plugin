<?php
/**
 * Read-only audit of the two places a price lives.
 *
 * `price` is the free text printed on the Package Card ("From $800"); the
 * budget facet reads `price_amount`, the number on the Dates and price panel.
 * Same split the Duration field turned out to have.
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

printf( "%-46s %-18s %-12s %s\n", 'PACKAGE', 'CARD price (text)', 'price_amount', 'currency' );
echo str_repeat( '-', 96 ), "\n";

$card_text = 0;
$numeric   = 0;

foreach ( $packages as $package ) {
	$text = trim( (string) iflynepal_package_field( $package->ID, 'price' ) );
	$num  = trim( (string) iflynepal_package_field( $package->ID, 'price_amount' ) );
	$cur  = trim( (string) iflynepal_package_field( $package->ID, 'price_currency' ) );

	if ( '' !== $text ) {
		++$card_text;
	}

	if ( null !== iflynepal_archive_package_price( $package->ID ) ) {
		++$numeric;
	}

	printf(
		"%-46s %-18s %-12s %s\n",
		mb_substr( html_entity_decode( $package->post_title ), 0, 44 ),
		'' === $text ? '—' : mb_substr( $text, 0, 16 ),
		'' === $num ? '—' : $num,
		'' === $cur ? '—' : $cur
	);
}

printf( "\n%d published packages\n", count( $packages ) );
printf( "  with card price text ..... %d\n", $card_text );
printf( "  with usable price_amount . %d\n", $numeric );

echo "\nSite-wide budget buckets from price_amount (what a picker could offer):\n";

foreach ( iflynepal_archive_budget_terms( $packages ) as $bucket ) {
	printf( "  %-14s %-18s min=%s max=%s\n", $bucket['key'], $bucket['label'], $bucket['min'], null === $bucket['max'] ? 'none' : $bucket['max'] );
}
