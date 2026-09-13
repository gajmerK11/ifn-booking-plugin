<?php
/**
 * The enquiry content model, and the one routine that stores an enquiry.
 *
 * Schema-first, like the archive and package models: the field list here is what
 * the form renders, what the handler validates and what the details box prints,
 * so the three cannot drift apart. Adding a field is a line in
 * iflynepal_enquiry_fields() and nothing else.
 *
 * The field shape is the one the hand-off document fixes — Name, Email,
 * Message, Package, Status, Date — which is also what WP Travel Engine's free
 * Enquiries feature collects. Nothing beyond it is invented here: a phone
 * number, a country or a party size are all plausible and none of them has been
 * asked for.
 *
 * Where each value lives is deliberate:
 *
 *  - the message is the post content, so the admin's own search finds an
 *    enquiry by what it says;
 *  - the title is generated, so the list table reads without opening a row;
 *  - the date is the post date, because that is what the list table sorts and
 *    filters on already;
 *  - everything else is protected meta.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The fields one enquiry holds.
 *
 * `required` is enforced by the handler, not by the browser alone: the form is
 * not the only thing that can post to the endpoint.
 *
 * @since 1.0.0
 *
 * @return array<string,array<string,mixed>> Field key => definition.
 */
function iflynepal_enquiry_fields() {
	$fields = array(
		'name'    => array(
			'label'        => __( 'Your name', 'iflynepal' ),
			'type'         => 'text',
			'required'     => true,
			'autocomplete' => 'name',
		),
		'email'   => array(
			'label'        => __( 'Email address', 'iflynepal' ),
			'type'         => 'email',
			'required'     => true,
			'autocomplete' => 'email',
		),
		'message' => array(
			'label'    => __( 'Your message', 'iflynepal' ),
			'type'     => 'textarea',
			'required' => true,
			'help'     => __( 'Tell us what you are planning — dates, group size, anything you would like to know.', 'iflynepal' ),
		),
	);

	/**
	 * Filters the enquiry field list.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $fields Field key => definition.
	 */
	return (array) apply_filters( 'iflynepal_enquiry_fields', $fields );
}

/**
 * The full meta key for one enquiry field.
 *
 * @since 1.0.0
 *
 * @param string $key Field key.
 * @return string Meta key.
 */
function iflynepal_enquiry_meta_key( $key ) {
	return IFLYNEPAL_ENQUIRY_META_PREFIX . sanitize_key( $key );
}

/**
 * One stored value from an enquiry.
 *
 * The message is read from the post content rather than meta — it is stored
 * there, and asking for it by the same field key everywhere else uses keeps the
 * details box and the list table from having to know the difference.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Enquiry post ID.
 * @param string $key     Field key.
 * @return string
 */
function iflynepal_enquiry_field( $post_id, $key ) {
	$post_id = (int) $post_id;

	if ( 'message' === $key ) {
		return (string) get_post_field( 'post_content', $post_id );
	}

	return (string) get_post_meta( $post_id, iflynepal_enquiry_meta_key( $key ), true );
}

/**
 * Sanitizes one submitted value by its declared type.
 *
 * @since 1.0.0
 *
 * @param string $value Raw submitted value, already unslashed.
 * @param string $type  Field type.
 * @return string Clean value.
 */
function iflynepal_enquiry_sanitize_value( $value, $type ) {
	if ( 'email' === $type ) {
		return sanitize_email( $value );
	}

	if ( 'textarea' === $type ) {
		return sanitize_textarea_field( $value );
	}

	return sanitize_text_field( $value );
}

/**
 * The title one enquiry is filed under.
 *
 * Generated rather than asked for. The visitor is writing a message, not naming
 * a record, and the list table needs a row to say who it is from and what it is
 * about — which is exactly the two things the visitor has already given.
 *
 * @since 1.0.0
 *
 * @param string $name       Sender's name.
 * @param int    $package_id Package the enquiry is about, or 0.
 * @return string
 */
function iflynepal_enquiry_title( $name, $package_id ) {
	$name    = '' === trim( $name ) ? __( 'Unnamed visitor', 'iflynepal' ) : $name;
	$package = $package_id ? get_the_title( $package_id ) : '';

	if ( '' === $package ) {
		return $name;
	}

	/* translators: 1: the sender's name, 2: the package the enquiry is about. */
	return sprintf( __( '%1$s — %2$s', 'iflynepal' ), $name, $package );
}

/**
 * Stores one enquiry.
 *
 * This is the only place that writes an enquiry, so the shape of a stored row is
 * described in exactly one function — and every value is sanitized here by its
 * declared type as well as in the handler. The handler has to do it to be able
 * to validate what it has; doing it again here means a second caller (a future
 * REST route, an import, a test) cannot write a row the front end would have
 * rejected.
 *
 * `wp_insert_post()` runs with `wp_slash()` around the content and the title,
 * because it unslashes what it is given — without this an apostrophe in a
 * visitor's message is stored with the slash stripped off the wrong side and a
 * name like O'Brien loses its quote.
 *
 * @since 1.0.0
 *
 * @param array<string,string> $values     Clean field values, keyed as iflynepal_enquiry_fields().
 * @param int                  $package_id Package the enquiry is about, or 0.
 * @return int|WP_Error The new post ID, or the insert error.
 */
function iflynepal_enquiry_store( $values, $package_id = 0 ) {
	$package_id = (int) $package_id;
	$fields     = iflynepal_enquiry_fields();
	$clean      = array();

	foreach ( $fields as $key => $field ) {
		$raw           = isset( $values[ $key ] ) && is_string( $values[ $key ] ) ? $values[ $key ] : '';
		$clean[ $key ] = iflynepal_enquiry_sanitize_value( $raw, isset( $field['type'] ) ? $field['type'] : 'text' );
	}

	$name    = isset( $clean['name'] ) ? $clean['name'] : '';
	$message = isset( $clean['message'] ) ? $clean['message'] : '';

	$post_id = wp_insert_post(
		array(
			'post_type'    => IFLYNEPAL_ENQUIRY_POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => wp_slash( iflynepal_enquiry_title( $name, $package_id ) ),
			'post_content' => wp_slash( $message ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	foreach ( $clean as $key => $value ) {
		if ( 'message' === $key ) {
			continue;
		}

		update_post_meta( $post_id, iflynepal_enquiry_meta_key( $key ), $value );
	}

	$statuses = array_keys( iflynepal_enquiry_statuses() );

	update_post_meta( $post_id, iflynepal_enquiry_meta_key( 'status' ), isset( $statuses[0] ) ? $statuses[0] : 'new' );

	if ( $package_id ) {
		update_post_meta( $post_id, iflynepal_enquiry_meta_key( 'package' ), $package_id );
	}

	/*
	 * The page the enquiry was sent from, which is not always the package: an
	 * archive or a category carries none, and sales still want to know where
	 * the visitor was standing. Stored raw as a URL, never rendered as a link
	 * without escaping.
	 */
	$source = isset( $values['source'] ) ? esc_url_raw( $values['source'] ) : '';

	if ( '' !== $source ) {
		update_post_meta( $post_id, iflynepal_enquiry_meta_key( 'source' ), $source );
	}

	/**
	 * Fires once an enquiry has been stored.
	 *
	 * The notification email hangs off this rather than being called from the
	 * handler, so a failed send can never lose the record: the row is already
	 * written by the time anything is sent.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $post_id    The new enquiry's post ID.
	 * @param array<string,string> $clean      The sanitized submitted values.
	 * @param int                  $package_id Package the enquiry is about, or 0.
	 */
	do_action( 'iflynepal_enquiry_stored', $post_id, $clean, $package_id );

	return $post_id;
}

/**
 * The package an enquiry is about.
 *
 * @since 1.0.0
 *
 * @param int $post_id Enquiry post ID.
 * @return int Package post ID, or 0.
 */
function iflynepal_enquiry_package_id( $post_id ) {
	return (int) get_post_meta( (int) $post_id, iflynepal_enquiry_meta_key( 'package' ), true );
}
