<?php
/**
 * The bridge to the payment gateway — Easy PayPal & Stripe Button.
 *
 * 🔴 No payment logic is implemented here and none should be. The gateway plugin
 * owns the buttons, the PayPal and card flows, the redirect and the money; this
 * file's whole job is to decide *which* of its buttons belongs on a package and
 * to put it on the page. Nothing about an amount, a currency, a card or an
 * account passes through this plugin or this server — which is also what keeps
 * the PCI surface at nothing (§5.4.1 of the hand-off document).
 *
 * The contract with that plugin is deliberately small, and it is all here rather
 * than spread through the templates, so the day it changes there is one file to
 * read:
 *
 *   - the shortcode is `[wpecpp id="123"]`;
 *   - a button is a post of type `wpplugin_pp_button`, its title the button's
 *     name and its price in `wpplugin_paypal_button_price`;
 *   - the plugin is `wp-ecommerce-paypal/wp-ecommerce-paypal.php`.
 *
 * Everything is guarded on that plugin being active. It is a separate plugin
 * with its own life, and a visitor must never be shown the raw text of a
 * shortcode nobody registered — which is exactly what `do_shortcode()` leaves
 * behind when the plugin that owns it is switched off.
 *
 * ⚠ This plugin deliberately does NOT declare `Requires Plugins:` for it. Core
 * would then deactivate the whole catalogue the moment the gateway is switched
 * off — taking the packages, the archives and the enquiry form down with it over
 * a button. The dependency is real but it is not fatal: without the gateway a
 * package simply shows no pay button, and the editor and the settings screen
 * both say why.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The gateway's shortcode tag.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PAYMENT_SHORTCODE = 'wpecpp';

/**
 * The post type the gateway stores its buttons in.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PAYMENT_BUTTON_TYPE = 'wpplugin_pp_button';

/**
 * The gateway plugin's file, for the admin links.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PAYMENT_PLUGIN = 'wp-ecommerce-paypal/wp-ecommerce-paypal.php';

/**
 * Whether the gateway is installed, active and ready to render a button.
 *
 * Asked of the shortcode and the post type rather than of the plugin list: those
 * are the two things actually used, and they answer correctly for a renamed
 * folder, a must-use install, or a fork — where a hardcoded plugin path would
 * report the gateway missing while its buttons worked perfectly.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function iflynepal_payment_gateway_active() {
	return shortcode_exists( IFLYNEPAL_PAYMENT_SHORTCODE ) && post_type_exists( IFLYNEPAL_PAYMENT_BUTTON_TYPE );
}

/**
 * Whether the gateway is on disk at all, however it is registered.
 *
 * Only used to word the admin's message — "install it" and "switch it on" are
 * different instructions, and telling somebody to install what they already have
 * wastes their afternoon.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function iflynepal_payment_gateway_installed() {
	if ( iflynepal_payment_gateway_active() ) {
		return true;
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return array_key_exists( IFLYNEPAL_PAYMENT_PLUGIN, get_plugins() );
}

/**
 * The published payment buttons, newest first.
 *
 * Read live rather than cached: an editor creates a button in one tab and
 * expects to choose it in the next, and the list is a handful of posts.
 *
 * @since 1.0.0
 *
 * @return array<int,string> Button post ID => label, with the price when it has one.
 */
function iflynepal_payment_buttons() {
	if ( ! iflynepal_payment_gateway_active() ) {
		return array();
	}

	$buttons = get_posts(
		array(
			'post_type'              => IFLYNEPAL_PAYMENT_BUTTON_TYPE,
			'post_status'            => 'publish',
			'numberposts'            => 100,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'suppress_filters'       => false,
			'update_post_term_cache' => false,
		)
	);

	$list = array();

	foreach ( $buttons as $button ) {
		$price = get_post_meta( $button->ID, 'wpplugin_paypal_button_price', true );
		$label = '' !== (string) $button->post_title ? $button->post_title : __( '(untitled button)', 'iflynepal' );

		if ( '' !== (string) $price ) {
			/* translators: 1: the button's name, 2: the amount the button charges. */
			$label = sprintf( __( '%1$s — %2$s', 'iflynepal' ), $label, $price );
		}

		$list[ $button->ID ] = $label;
	}

	return $list;
}

/**
 * The payment button chosen for one package, if it is still usable.
 *
 * A stored ID is checked rather than trusted. A button can be deleted or
 * unpublished long after it was picked, and the gateway's own shortcode answers
 * that case by printing "This payment button was removed. Please contact the
 * website owner." into the page — a sentence no visitor should ever meet on a
 * price card. Checked here, the button is simply absent and the page reads as a
 * package nobody has wired up yet.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID.
 * @return int Button post ID, or 0.
 */
function iflynepal_package_payment_button_id( $post_id ) {
	$button_id = (int) iflynepal_package_field( $post_id, 'booking_button' );

	if ( ! $button_id || ! iflynepal_payment_gateway_active() ) {
		return 0;
	}

	if ( IFLYNEPAL_PAYMENT_BUTTON_TYPE !== get_post_type( $button_id ) || 'publish' !== get_post_status( $button_id ) ) {
		return 0;
	}

	return $button_id;
}

/**
 * The finished payment button for one package.
 *
 * Two ways in, and the order matters. A chosen button wins, because it is the
 * one the editor picked from a list of things that exist. The Booking shortcode
 * field is the escape hatch under it — a second gateway, a campaign button, an
 * inline `[wpecpp name="…" price="…"]` — and is run only when it actually
 * contains a shortcode this site has registered, so a half-typed one or a
 * shortcode belonging to a plugin that has since been removed prints nothing
 * rather than printing itself.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID.
 * @return string Rendered markup, or '' when there is nothing to show.
 */
function iflynepal_package_payment_markup( $post_id ) {
	$post_id = (int) $post_id;

	$button_id = iflynepal_package_payment_button_id( $post_id );

	if ( $button_id ) {
		return iflynepal_payment_payable( do_shortcode( sprintf( '[%1$s id="%2$d"]', IFLYNEPAL_PAYMENT_SHORTCODE, $button_id ) ) );
	}

	$custom = trim( (string) iflynepal_package_field( $post_id, 'booking' ) );

	if ( '' === $custom ) {
		return '';
	}

	if ( ! iflynepal_payment_shortcode_is_registered( $custom ) ) {
		return '';
	}

	$rendered = do_shortcode( $custom );

	/*
	 * 🔴 A registered tag is not enough, and this was a real hole: `[wpecpp id="`
	 * — a paste that lost its closing bracket — names a tag this site knows, so
	 * the check above passes, and do_shortcode() then leaves the broken text
	 * exactly as it found it. The result was the raw string printed onto the
	 * price card of a live package.
	 *
	 * Rendering is therefore judged by whether anything actually changed. It
	 * covers the malformed paste, the tag whose plugin has gone, and the tag
	 * that renders nothing at all, without this file having to enumerate them.
	 */
	if ( trim( $rendered ) === trim( $custom ) ) {
		return '';
	}

	return iflynepal_payment_payable( $rendered );
}

/**
 * Keeps rendered gateway output only when it can actually take a payment.
 *
 * 🔴 The gateway answers two of its own failures by printing a sentence into the
 * page: "(Please enter your Payment methods data on the settings pages.)" when no
 * PayPal or Stripe account is connected, and "No payment methods are enabled for
 * this button." when a button has both switched off. Both are notes to the site's
 * owner, and both would otherwise be published — in the Book now panel of a live
 * package, where a visitor reads them instead of paying.
 *
 * The test is what the markup contains rather than what it says, so it does not
 * break when that copy is reworded or translated: anything that can take money
 * carries a form, a link, a button or a script, and a message carries none of
 * them. When there is nothing payable the panel falls back to the inert Book now
 * control, which at least explains itself.
 *
 * @since 1.0.0
 *
 * @param string $markup Rendered shortcode output.
 * @return string The markup, or '' when it holds no payment control.
 */
function iflynepal_payment_payable( $markup ) {
	/*
	 * The tag name has to end where the match ends — a space, a slash or the
	 * closing bracket — or <a also matches <address. Written as a character
	 * class rather than , which is one stray escape away from being written
	 * into the file as a literal backspace byte and matching nothing at all.
	 * That is exactly what happened here once, and it suppressed every button.
	 */
	return preg_match( '/<(form|a|button|script)[\s>\/]/i', (string) $markup ) ? (string) $markup : '';
}

/**
 * Whether a pasted value is a shortcode this site knows how to run.
 *
 * The tag is read out of the text and looked up, rather than the text being
 * matched against the gateway's own tag: the field is documented as taking any
 * payment shortcode, and the check that matters is "will something render this",
 * not "is this the plugin I expected".
 *
 * @since 1.0.0
 *
 * @param string $value Pasted shortcode text.
 * @return bool
 */
function iflynepal_payment_shortcode_is_registered( $value ) {
	if ( ! preg_match( '/\[([a-zA-Z0-9_-]+)/', (string) $value, $match ) ) {
		return false;
	}

	return shortcode_exists( $match[1] );
}

/**
 * Whether one package has anything to pay with.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID.
 * @return bool
 */
function iflynepal_package_has_payment( $post_id ) {
	return '' !== iflynepal_package_payment_markup( $post_id );
}

/**
 * The admin URL for the gateway's button list.
 *
 * The gateway registers its buttons with `show_in_menu => false`, so there is no
 * menu item to send an editor to; the list table is still there at its ordinary
 * URL, which is what this returns.
 *
 * @since 1.0.0
 *
 * @param bool $add_new True for the Add New screen instead of the list.
 * @return string
 */
function iflynepal_payment_buttons_admin_url( $add_new = false ) {
	return admin_url(
		( $add_new ? 'post-new.php?post_type=' : 'edit.php?post_type=' ) . IFLYNEPAL_PAYMENT_BUTTON_TYPE
	);
}
