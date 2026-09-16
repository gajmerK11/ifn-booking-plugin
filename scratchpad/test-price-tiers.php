<?php
/**
 * Puts a group-size price ladder on one package so the price card can be
 * checked on a real page, and takes it off again.
 *
 * Reversible by design: --setup remembers whatever was stored under the key
 * before it wrote (usually nothing) and --restore puts that back, so this
 * leaves no test data behind on a site the client also edits.
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$mode = isset( $argv[1] ) ? $argv[1] : '';
$slug = isset( $argv[2] ) ? $argv[2] : '';

$package = $slug ? get_page_by_path( $slug, OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE ) : null;

if ( ! $package ) {
	$found   = get_posts(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_key'    => iflynepal_package_meta_key( 'price_amount' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	$package = $found ? $found[0] : null;
}

if ( ! $package ) {
	echo "No package found.\n";

	exit( 1 );
}

$key    = iflynepal_package_meta_key( 'price_tiers' );
$backup = $key . '_test_backup';

if ( '--setup' === $mode ) {
	update_post_meta( $package->ID, $backup, get_post_meta( $package->ID, $key, true ) );
	update_post_meta(
		$package->ID,
		$key,
		array(
			array(
				'pax_from' => '1',
				'pax_to'   => '12',
				'was'      => '540',
				'price'    => '502',
			),
			array(
				'pax_from' => '13',
				'pax_to'   => '',
				'was'      => '502',
				'price'    => '455',
			),
		)
	);
	echo "Tiers written to #{$package->ID} {$package->post_title}\n";
	echo get_permalink( $package->ID ), "\n";

	exit( 0 );
}

if ( '--restore' === $mode ) {
	$was = get_post_meta( $package->ID, $backup, true );

	if ( '' === $was || array() === $was ) {
		delete_post_meta( $package->ID, $key );
	} else {
		update_post_meta( $package->ID, $key, $was );
	}

	delete_post_meta( $package->ID, $backup );
	echo "Restored #{$package->ID}\n";

	exit( 0 );
}

/* No flag: report what is stored and what the readers make of it. */
echo $package->post_title, ' — ', get_permalink( $package->ID ), "\n";

foreach ( iflynepal_package_price_tiers( $package->ID ) as $tier ) {
	printf( "  %-10s from=%-3d to=%-3d price=%-8s was=%s\n", $tier['label'], $tier['from'], $tier['to'], $tier['price'], $tier['was'] );
}

foreach ( array( 0, 1, 12, 13, 40 ) as $pax ) {
	$tier = iflynepal_package_price_tier_for_pax( iflynepal_package_price_tiers( $package->ID ), $pax );
	printf( "  pax=%-3d -> %s\n", $pax, $tier ? $tier['label'] . ' @ ' . $tier['price'] : 'no tiers' );
}
