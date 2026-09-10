<?php
/**
 * Activation / deactivation behaviour for the iFly Nepal Booking plugin.
 *
 * Callbacks are registered from the main plugin file at top level (never from
 * inside another hook) so WordPress can find them during (de)activation.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run once when the plugin is activated.
 *
 * Activation runs on a request where `init` has already fired, so the package
 * post type and its taxonomy are not registered yet. They are registered here
 * by hand first, because both of the steps that follow depend on them existing:
 * seeding the default terms needs the taxonomy, and flushing rewrite rules is
 * pointless before the rules the post type and taxonomy contribute are in the
 * rule set.
 *
 * Flushing is expensive and belongs here — once, on activation — never on
 * `init` on every request.
 *
 * @return void
 */
function iflynepal_booking_activate() {
	iflynepal_register_package_content_types();
	iflynepal_booking_seed_package_types();
	flush_rewrite_rules();
}

/**
 * Run once when the plugin is deactivated.
 *
 * Drops the plugin's rewrite rules from the cached rule set. Does not delete any
 * content or options — that belongs in uninstall.php.
 *
 * @return void
 */
function iflynepal_booking_deactivate() {
	flush_rewrite_rules();
}
