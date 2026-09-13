<?php
/**
 * Renames every testimonial into the flat "Testimonial N" sequence.
 *
 * The titles used to name the page a review was assigned to — "Home Testimonial
 * 1" — and the title is now only an identifier: one sequence across the post
 * type, with the list table's "Shown on" column carrying where each appears.
 *
 * Reviews rename themselves the next time they are saved, so this is only for
 * the ones nobody is about to open. Numbered by post ID ascending, which is the
 * order they were created in, so the sequence has no gaps.
 *
 * Dry run by default; pass --apply to write. Safe to run twice.
 *
 * A theme concern living in the plugin's scratchpad because that is where this
 * project keeps its one-off scripts (P18).
 *
 * Usage:
 *   php rename-testimonials.php [--apply]
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );

require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply = in_array( '--apply', $argv, true );

$reviews = get_posts(
	array(
		'post_type'   => 'ifly_testimonial',
		'post_status' => 'any',
		'numberposts' => -1,
		'orderby'     => 'ID',
		'order'       => 'ASC',
	)
);

if ( ! $reviews ) {
	echo "No testimonials found.\n";
	exit;
}

$number  = 0;
$changed = 0;

foreach ( $reviews as $review ) {
	++$number;

	$title = 'Testimonial ' . $number;

	if ( $review->post_title === $title ) {
		printf( "  #%d  %-32s unchanged\n", $review->ID, $review->post_title );
		continue;
	}

	++$changed;

	printf( "  #%d  %-32s -> %s\n", $review->ID, $review->post_title, $title );

	if ( ! $apply ) {
		continue;
	}

	wp_update_post(
		array(
			'ID'         => $review->ID,
			'post_title' => $title,
			'post_name'  => sanitize_title( $title ),
		)
	);
}

printf( "\n%d of %d would change.%s\n", $changed, count( $reviews ), $apply ? ' Written.' : ' Dry run — pass --apply to write.' );
