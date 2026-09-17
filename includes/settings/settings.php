<?php
/**
 * The plugin's own settings, and the WhatsApp link built from them.
 *
 * One option row holding every field rather than one row per field: it is a
 * single form saved in one go, and one autoloaded lookup on the front end
 * instead of several. Same reasoning as the theme's External Testimonial Links
 * screen, which this is modelled on.
 *
 * The WhatsApp number is site-wide because the business has one — a number typed
 * onto every package is the same number typed forty times, and the day it
 * changes it changes in forty places. A package may still override it, for a
 * partner-run trip answered by somebody else's phone.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The option every plugin setting lives in.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_BOOKING_OPTION = 'iflynepal_booking_settings';

/**
 * The settings the plugin holds, with their labels, types and defaults.
 *
 * Declarative like every other model here: the screen renders from this, the
 * sanitizer walks it, and the front end reads through the same keys, so a
 * setting cannot exist on one side and not the other.
 *
 * @since 1.0.0
 *
 * @return array<string,array<string,mixed>> Setting key => definition.
 */
function iflynepal_booking_settings_schema() {
	$settings = array(
		'notification_email'    => array(
			'label'   => __( 'Send form submissions to', 'iflynepal' ),
			'type'    => 'email',
			'default' => '',
			'help'    => __( 'The inbox that is told about every Connect With Us request, package enquiry and Contact page message. Left empty, they go to the office address on the Contact page, and failing that to the site administrator.', 'iflynepal' ),
		),
		'whatsapp_number'       => array(
			'label'   => __( 'WhatsApp number', 'iflynepal' ),
			'type'    => 'digits',
			'default' => '',
			'help'    => __( 'Country code first, digits only — no +, no spaces, no dashes. Nepal example: 9779812345678. Left empty, no WhatsApp button is shown anywhere.', 'iflynepal' ),
		),
		'whatsapp_message'      => array(
			'label'   => __( 'WhatsApp default message', 'iflynepal' ),
			'type'    => 'textarea',
			'default' => __( 'Hello iFly Nepal, I would like to know more about {package}.', 'iflynepal' ),
			'help'    => __( 'What the visitor\'s chat opens with, already typed for them. {package} is replaced with the package name — or with the site name on a page that is not a package.', 'iflynepal' ),
		),
		'trip_finder_durations' => array(
			'label'   => __( 'Trip finder length options', 'iflynepal' ),
			'type'    => 'durations',
			'default' => iflynepal_trip_finder_default_durations(),
			'help'    => __( 'The choices the hero picker offers for how long a visitor has. Each row is one option, and its wording is worked out from the numbers, so there is nothing to type. The numbers on offer run from your shortest published package to your longest: a length no package has is not a length worth offering.', 'iflynepal' ),
		),
		'trip_finder_page'      => array(
			'label'   => __( 'Trip finder results page', 'iflynepal' ),
			'type'    => 'page',
			'default' => '0',
			'help'    => __(
				'The Page holding the [ iflynepal_type_explorer ] shortcode . The homepage hero\'s "I want to" picker links here with the visitor\'s chosen trip types. Left unset, the picker is not shown at all.',
				'iflynepal'
			),
		),
	);

	/**
	 * Filters the plugin's settings schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $settings Setting key => definition.
	 */
	return (array) apply_filters( 'iflynepal_booking_settings_schema', $settings );
}

/**
 * One setting's stored value, or its default.
 *
 * @since 1.0.0
 *
 * @param string $key Setting key.
 * @return string
 */
function iflynepal_booking_setting( $key ) {
	$schema = iflynepal_booking_settings_schema();

	if ( ! isset( $schema[ $key ] ) ) {
		return '';
	}

	$stored = get_option( IFLYNEPAL_BOOKING_OPTION, array() );

	/*
	 * The default stands in only when the setting has never been saved, not
	 * whenever it is empty: an editor who clears the message means to send no
	 * message, and a default that reappears on the next page load is a field
	 * that cannot be cleared. The form prefills with the default, so a stored
	 * empty value is always a deliberate one.
	 */
	$raw = ( is_array( $stored ) && array_key_exists( $key, $stored ) )
		? $stored[ $key ]
		: $schema[ $key ]['default'];

	/*
	 * 🔴 Not every setting is a string any more. The cast below is what keeps a
	 * number or a message honest, and it is exactly wrong for a setting whose
	 * value is a list — `(string) array()` is the word "Array" and a PHP notice
	 * with it. The type decides which of the two this is, in one place, rather
	 * than each caller remembering.
	 */
	$value = iflynepal_booking_setting_is_list( $schema[ $key ]['type'] ) ? $raw : (string) $raw;

	/*
	 * Sanitized on the way out as well as on the way in. register_setting()'s
	 * callback only runs for a save through options.php, so a row written by
	 * anything else — an import, WP-CLI, a migration, a hand-edited database —
	 * would otherwise reach the front end as typed and put spaces and a plus
	 * sign inside a URL.
	 */
	return iflynepal_booking_sanitize_setting( $value, $schema[ $key ]['type'] );
}

/**
 * Whether a setting of this type holds a list rather than a scalar.
 *
 * One function rather than a comparison repeated in the getter, the sanitizer
 * and the settings screen: the three have to agree about which types are lists,
 * and three copies of that judgement is three places for a fourth type to be
 * forgotten.
 *
 * @since 1.0.0
 *
 * @param string $type Declared type.
 * @return bool
 */
function iflynepal_booking_setting_is_list( $type ) {
	return 'durations' === $type;
}

/**
 * Cleans one submitted setting by its declared type.
 *
 * A number is reduced to its digits rather than rejected for carrying a `+` or
 * the spaces a phone number is normally written with: an editor types the number
 * the way they know it, and wa.me wants the digits. Rejecting the familiar form
 * would be a screen that refuses correct input.
 *
 * @since 1.0.0
 *
 * @param string $value Raw value, already unslashed.
 * @param string $type  Declared type.
 * @return string Clean value.
 */
function iflynepal_booking_sanitize_setting( $value, $type ) {
	if ( 'durations' === $type ) {
		return iflynepal_booking_sanitize_durations( $value );
	}

	if ( 'digits' === $type ) {
		$digits = preg_replace( '/[^0-9]/', '', (string) $value );

		/*
		 * Leading zeros are stripped, and that is not cosmetic: wa.me wants the
		 * country code alone, while people write the same number as +977…,
		 * 00977… or 0977… depending on where they are standing. No country code
		 * begins with a zero, so a leading one is always a dialling prefix this
		 * URL must not carry — and a number that keeps it opens a chat with
		 * nobody, silently.
		 */
		return ltrim( $digits, '0' );
	}

	if ( 'page' === $type ) {
		return (string) absint( $value );
	}

	/*
	 * An address that is not one is stored as empty rather than as typed.
	 * sanitize_email() returns '' for anything it cannot make an address of,
	 * and an empty recipient is one the resolver below can fall back from —
	 * a half-typed one would be a notification sent nowhere, silently.
	 */
	if ( 'email' === $type ) {
		return sanitize_email( $value );
	}

	if ( 'textarea' === $type ) {
		return sanitize_textarea_field( $value );
	}

	return sanitize_text_field( $value );
}

/**
 * Cleans the trip-finder's length options.
 *
 * Stored as a list of `{ min, max }` rows, `max` null for the open-ended one.
 * Every row is rebuilt from two integers rather than trusted as submitted: the
 * control offers selects, so nothing legitimate can arrive malformed, but the
 * option row is reachable by an import, WP-CLI or a hand-edited database, and a
 * bad row here reaches the homepage.
 *
 * Four rules, each of which stops a row that would otherwise publish a picker
 * option nothing can ever match:
 *
 *  1. A row with no usable `min` is dropped. There is no sensible reading of
 *     "from nothing to five days".
 *  2. A `max` below its `min` is raised to it rather than swapped or dropped —
 *     a one-day bucket is a coherent thing to ask for, and silently reversing
 *     somebody's two numbers is a screen that disagrees with what they chose.
 *  3. Rows are sorted by `min`, so the picker reads shortest to longest
 *     whatever order they were added in. Nobody should have to drag rows to fix
 *     a list whose correct order is arithmetic.
 *  4. Only the open-ended row with the highest `min` survives. Two of them mean
 *     the lower one swallows everything the higher one was for, and the higher
 *     one can then never match anything.
 *
 * Overlaps are deliberately left alone. The picker is a single-select, so two
 * options that both cover nine days is a redundancy rather than a fault, and
 * refusing to save it would block a site mid-edit.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw rows.
 * @return array<int,array{min:int,max:int|null}> Clean rows, re-indexed from zero.
 */
function iflynepal_booking_sanitize_durations( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$rows = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$min = isset( $row['min'] ) ? absint( $row['min'] ) : 0;

		if ( $min < 1 ) {
			continue;
		}

		/*
		 * An empty max is the open-ended row — "15 days and over" — and is
		 * stored as null rather than as 0 or as a very large number, because
		 * iflynepal_explore_duration_matches() reads null as "no upper bound".
		 * A 0 there would read as a ceiling of zero days and match nothing.
		 */
		$max = ( isset( $row['max'] ) && '' !== $row['max'] ) ? absint( $row['max'] ) : null;

		if ( null !== $max && $max < $min ) {
			$max = $min;
		}

		$rows[] = array(
			'min' => $min,
			'max' => $max,
		);
	}

	usort(
		$rows,
		static function ( $a, $b ) {
			return $a['min'] <=> $b['min'];
		}
	);

	$seen_open = false;

	foreach ( array_reverse( array_keys( $rows ) ) as $i ) {
		if ( null !== $rows[ $i ]['max'] ) {
			continue;
		}

		if ( $seen_open ) {
			unset( $rows[ $i ] );
			continue;
		}

		$seen_open = true;
	}

	return array_slice( array_values( $rows ), 0, IFLYNEPAL_TRIP_FINDER_MAX_DURATIONS );
}

/* ---------------------------------------------------------------- whatsapp */

/**
 * The WhatsApp number to use on one page.
 *
 * The package's own number wins, the site-wide one stands in. Both are reduced
 * to digits on the way in, so nothing here has to think about how they were
 * typed.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID, or 0 for a page that is not a package.
 * @return string Digits, or '' when no number is configured.
 */
function iflynepal_whatsapp_number( $post_id = 0 ) {
	$number = '';

	if ( $post_id && function_exists( 'iflynepal_package_field' ) ) {
		$number = iflynepal_booking_sanitize_setting( iflynepal_package_field( $post_id, 'whatsapp_number' ), 'digits' );
	}

	if ( '' === $number ) {
		$number = iflynepal_booking_setting( 'whatsapp_number' );
	}

	/**
	 * Filters the WhatsApp number for a page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $number  Digits, or '' when none is configured.
	 * @param int    $post_id Package post ID, or 0.
	 */
	return (string) apply_filters( 'iflynepal_whatsapp_number', $number, $post_id );
}

/**
 * The click-to-chat URL for one page.
 *
 * `wa.me` rather than `api.whatsapp.com`: it is the short form WhatsApp
 * documents, and it opens the installed app on a phone and WhatsApp Web on a
 * desktop without the query string having to say which.
 *
 * The message is built here rather than stored per package, because it is the
 * same sentence every time with one word changed — and a stored copy per package
 * is forty sentences to re-edit when the wording changes.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID, or 0 for a page that is not a package.
 * @return string The URL, or '' when no number is configured.
 */
function iflynepal_whatsapp_url( $post_id = 0 ) {
	$number = iflynepal_whatsapp_number( $post_id );

	if ( '' === $number ) {
		return '';
	}

	$message = iflynepal_booking_setting( 'whatsapp_message' );
	$package = $post_id ? get_the_title( $post_id ) : '';

	/*
	 * On an archive there is no package to name, so the site's own name stands
	 * in — "…know more about iFly Nepal." Dropping the token instead leaves
	 * "…know more about." with a full stop after nothing, which is the first
	 * thing the visitor reads in the chat they are about to send.
	 */
	$message = str_replace( '{package}', '' !== $package ? $package : get_bloginfo( 'name' ), $message );

	$url = 'https://wa.me/' . $number;

	if ( '' !== $message ) {
		$url .= '?text=' . rawurlencode( html_entity_decode( $message, ENT_QUOTES, 'UTF-8' ) );
	}

	return $url;
}

/* ----------------------------------------------------------- notifications */

/**
 * Who is told about a form submission.
 *
 * One resolver for all three of the site's forms — the Connect With Us drawer,
 * the package enquiry, and the theme's Contact page — because they are one
 * inbox in practice, and three copies of this fallback chain is three places
 * for the day it changes to be half-applied.
 *
 * The chain, in order:
 *
 *  1. The plugin setting, which is the field an editor can reach.
 *  2. The office address on the theme's Contact page, which is what these used
 *     to resolve to and is still the right answer when nothing is set here —
 *     the address already published to visitors is the one the office reads.
 *  3. The site administrator, so a submission is never silently dropped on a
 *     site where neither has been filled in.
 *
 * @since 1.0.0
 *
 * @param int $post_id The stored submission, or 0 when there is not one yet.
 * @return string An email address, or '' when nothing usable is configured.
 */
function iflynepal_notification_recipient( $post_id = 0 ) {
	$recipient = iflynepal_booking_setting( 'notification_email' );

	if ( ! is_email( $recipient ) && function_exists( 'iflynepal_contact_plain' ) ) {
		$recipient = sanitize_email( iflynepal_contact_plain( 'office_email' ) );
	}

	if ( ! is_email( $recipient ) ) {
		$recipient = sanitize_email( get_option( 'admin_email' ) );
	}

	/**
	 * Filters who is told about a new enquiry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $recipient Email address.
	 * @param int    $post_id   The stored submission, or 0.
	 */
	$recipient = (string) apply_filters( 'iflynepal_enquiry_recipient', $recipient, $post_id );

	return is_email( $recipient ) ? $recipient : '';
}
