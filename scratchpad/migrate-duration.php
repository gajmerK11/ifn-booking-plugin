<?php
/**
 * One-off: move a package's stored `nights` onto the new `duration_days`.
 *
 * The Dates and price panel used to hold a Nights field, which the calendar
 * added to the start date to get the end. It now holds Trip duration in DAYS,
 * because that is how the length is written everywhere else on the page and
 * everywhere the client sells it — and a five-day trip should be typed as 5.
 *
 * The conversion is `days = nights + 1`: a trip of four nights is five days,
 * counting the day you arrive and the day you leave. A package that already has
 * a duration is left alone, so this is safe to run twice.
 *
 * Dry run by default. Pass --apply to write.
 *
 *   PHPDIR="C:/Users/asus/AppData/Roaming/Local/lightning-services/php-8.2.27+1/bin/win64"
 *   "$PHPDIR/php.exe" -d extension_dir="$PHPDIR/ext" -d extension=mysqli \
 *       -d extension=mbstring scratchpad/migrate-duration.php --apply
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'CLI only.' );
}

define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );

$_SERVER['HTTP_HOST']   = 'iflynepal.local';
$_SERVER['REQUEST_URI'] = '/';

require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply    = in_array( '--apply', (array) $argv, true );
$old_key  = iflynepal_package_meta_key( 'nights' );
$new_key  = iflynepal_package_meta_key( 'duration_days' );
$packages = get_posts(
	array(
		'post_type'      => IFLYNEPAL_PACKAGE_POST_TYPE,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

printf( "%s — %d packages\n\n", $apply ? 'APPLYING' : 'DRY RUN', count( $packages ) );

$moved   = 0;
$skipped = 0;

foreach ( $packages as $package_id ) {
	$nights = get_post_meta( $package_id, $old_key, true );

	if ( '' === $nights ) {
		continue;
	}

	$existing = get_post_meta( $package_id, $new_key, true );

	if ( '' !== $existing ) {
		printf( "  #%d has a duration already (%s days) — leaving it, dropping nights\n", $package_id, $existing );

		if ( $apply ) {
			delete_post_meta( $package_id, $old_key );
		}

		++$skipped;

		continue;
	}

	$days = max( 1, (int) $nights + 1 );

	printf( "  #%d  %s nights -> %d days\n", $package_id, $nights, $days );

	if ( $apply ) {
		update_post_meta( $package_id, $new_key, (string) $days );
		delete_post_meta( $package_id, $old_key );
	}

	++$moved;
}

printf( "\n%d moved, %d already had a duration.\n", $moved, $skipped );

if ( ! $apply ) {
	echo "Nothing was written. Re-run with --apply.\n";
}
