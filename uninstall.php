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
 * Deliberately a no-op, and it should stay one for the content this plugin now
 * holds. Packages, package types, enquiries and connect requests are the business's
 * own records — a catalogue somebody wrote, and messages from real people — and
 * deleting a plugin is not a decision to destroy them. WordPress keeps posts and
 * their meta when a post type stops being registered, so reinstalling brings
 * every one of them back.
 *
 * As other storage lands, remove only what the plugin invented, e.g.:
 *   - delete_option( 'iflynepal_booking_settings' );
 *   - drop a custom bookings table, if one is ever created
 * Guard anything that destroys content behind an explicit opt-in setting.
 */
