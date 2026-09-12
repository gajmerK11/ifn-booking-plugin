<?php
/**
 * One-off: forget which package meta boxes a user has opened or closed.
 *
 * Boxes on the package editor start closed, but only for a user who has never
 * toggled one — after that their own list is what WordPress honours, which is
 * the point (nobody wants to re-open the same box on every package). The catch
 * is testing the default: anyone who has ever touched a box already has a list
 * and will not see it. This clears that list, putting the account back to how a
 * new editor finds the screen.
 *
 * Dry run by default. Pass --apply to write, and a login or e-mail to limit it
 * to one account (default: every user who has a stored list).
 *
 *   PHPDIR="C:/Users/asus/AppData/Roaming/Local/lightning-services/php-8.2.27+1/bin/win64"
 *   "$PHPDIR/php.exe" -d extension_dir="$PHPDIR/ext" -d extension=mysqli \
 *       -d extension=mbstring scratchpad/reset-box-state.php --apply
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

$args  = (array) $argv;
$apply = in_array( '--apply', $args, true );
$who   = '';

foreach ( array_slice( $args, 1 ) as $arg ) {
	if ( 0 !== strpos( $arg, '--' ) ) {
		$who = $arg;
	}
}

$key   = 'closedpostboxes_' . IFLYNEPAL_PACKAGE_POST_TYPE;
$users = array();

if ( '' !== $who ) {
	$user = is_email( $who ) ? get_user_by( 'email', $who ) : get_user_by( 'login', $who );

	if ( ! $user ) {
		exit( "No such user: {$who}\n" );
	}

	$users = array( $user );
} else {
	$users = get_users( array( 'meta_key' => $key ) ); // phpcs:ignore WordPress.DB.SlowMetaQuery.SlowMetaQuery
}

printf( "%s — %d user(s) with a stored list\n\n", $apply ? 'APPLYING' : 'DRY RUN', count( $users ) );

foreach ( $users as $user ) {
	$stored = get_user_meta( $user->ID, $key, true );

	printf(
		"  %s (#%d): %s\n",
		$user->user_login,
		$user->ID,
		is_array( $stored ) ? ( implode( ', ', $stored ) ?: '(all open)' ) : 'nothing stored'
	);

	if ( $apply ) {
		delete_user_meta( $user->ID, $key );
	}
}

echo $apply ? "\nCleared. Reload a package editor to see the default.\n" : "\nNothing was written. Re-run with --apply.\n";
