<?php
/**
 * Uninstall routine for the iFly Nepal Booking plugin.
 *
 * Runs only when the plugin is deleted from the Plugins screen. Keep every
 * removal explicit: delete only data this plugin created, and nothing else.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

// Exit if not called by WordPress during an uninstall.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * Nothing is stored yet. As storage lands, remove it here, e.g.:
 *   - delete_option( 'iflynepal_booking_settings' );
 *   - drop the custom enquiry / bookings table
 *   - delete package / enquiry posts if the client wants a full teardown
 * Guard destructive table work behind an explicit opt-in setting.
 */
