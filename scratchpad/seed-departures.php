<?php
define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = 'iflynepal.local';
require 'C:/Users/asus/Local Sites/iflynepal/app/public/wp-load.php';

$term = get_term_by( 'slug', 'retreat-nepal', 'iflynepal_package_type' );
$key  = iflynepal_archive_meta_key( 'departure_cards' );
$imgs = get_posts( array( 'post_type' => 'attachment', 'numberposts' => 4, 'fields' => 'ids' ) );

$existing = get_term_meta( $term->term_id, $key, true );
echo 'existing cards: ' . ( is_array( $existing ) ? count( $existing ) : 0 ) . "\n";

if ( in_array( '--seed', $argv, true ) ) {
	$cards = array(
		array( 'pill' => '2 left', 'date' => '3 Oct to 8 Oct 2026', 'title' => '6-Day Ayurvedic Wellness, Pokhara', 'duration' => '6 days', 'price' => '$960', 'image' => $imgs[0] ),
		array( 'pill' => '5 left', 'date' => '12 Oct to 19 Oct 2026', 'title' => '1-Week Meditation & Monastery, Kathmandu', 'duration' => '7 days', 'price' => '$1,200', 'image' => $imgs[1] ),
		array( 'pill' => '1 left', 'date' => '2 Nov to 9 Nov 2026', 'title' => '7-Day Shamanism Retreat, Kathmandu', 'duration' => '7 days', 'price' => '$1,500', 'image' => $imgs[2] ),
		array( 'pill' => '6 left', 'date' => '20 Dec to 27 Dec 2026', 'title' => 'Retreat in Mustang & Pokhara, family friendly', 'duration' => '8 days', 'price' => '$1,800', 'image' => $imgs[3] ),
	);
	update_term_meta( $term->term_id, $key, $cards );
	echo "seeded " . count( $cards ) . "\n";
}

if ( in_array( '--drop', $argv, true ) ) {
	delete_term_meta( $term->term_id, $key );
	echo "dropped\n";
}
