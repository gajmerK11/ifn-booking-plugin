<?php
/**
 * The connect-request content model, and the one routine that stores one.
 *
 * Schema-first, like the enquiry, the archive and the package models: the field
 * list here is what the form renders, what the handler validates and what the
 * details box prints, so the three cannot drift apart. Adding a field is a line
 * in iflynepal_connect_fields() and nothing else.
 *
 * The fields are the ones the client asked for — name, WhatsApp number, email,
 * which package type they want to know about, and their enquiry. Nothing beyond
 * them is invented.
 *
 * Where each value lives is the same arrangement the enquiry uses, for the same
 * reasons: the enquiry text is the post content, so the admin's own search finds
 * a request by what it says; the title is generated, so a list row reads without
 * being opened; the date is the post date, which the list table already sorts
 * and filters on; everything else is protected meta.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The fields one connect request holds.
 *
 * `required` is enforced by the handler, not by the browser alone: the form is
 * not the only thing that can post to the endpoint.
 *
 * Which three are required is a judgement rather than a client instruction: a
 * name and one way of answering are the least that makes a lead workable, and
 * the office answers these by WhatsApp and by email both. The package type and
 * the enquiry text are optional on purpose — a visitor who does not yet know
 * which kind of trip they want is exactly the visitor this form is for, and
 * refusing them is refusing the lead. Flip either one by adding
 * `'required' => true` here; nothing else needs changing.
 *
 * @since 1.0.0
 *
 * @return array<string,array<string,mixed>> Field key => definition.
 */
function iflynepal_connect_fields() {
	$fields = array(
		'name'         => array(
			'label'        => __( 'Name', 'iflynepal' ),
			'type'         => 'text',
			'required'     => true,
			'autocomplete' => 'name',
			'placeholder'  => __( 'Enter your name', 'iflynepal' ),
		),
		'whatsapp'     => array(
			'label'        => __( 'Your WhatsApp number', 'iflynepal' ),
			'type'         => 'tel',
			'required'     => true,
			'autocomplete' => 'tel',
			'placeholder'  => __( 'Enter your WhatsApp number', 'iflynepal' ),
			'help'         => __( 'Include your country code so we can reach you.', 'iflynepal' ),
		),
		'email'        => array(
			'label'        => __( 'Your email', 'iflynepal' ),
			'type'         => 'email',
			'required'     => true,
			'autocomplete' => 'email',
			'placeholder'  => __( 'Enter your email', 'iflynepal' ),
		),
		'package_type' => array(
			'label'    => __( 'Which package type would you like to know about?', 'iflynepal' ),
			'type'     => 'package_type',
			'required' => false,
			'empty'    => __( 'Not sure yet — tell me about everything', 'iflynepal' ),
		),
		'message'      => array(
			'label'       => __( 'Your enquiry', 'iflynepal' ),
			'type'        => 'textarea',
			'required'    => false,
			'placeholder' => __( 'Dates, group size, anything you would like to know.', 'iflynepal' ),
		),
	);

	/**
	 * Filters the connect-request field list.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $fields Field key => definition.
	 */
	return (array) apply_filters( 'iflynepal_connect_fields', $fields );
}

/**
 * The package types offered in the form's dropdown.
 *
 * Every term in the taxonomy, parents and their categories alike, flattened
 * into one list carrying the depth each one sits at so the control can indent
 * them. A visitor who already knows they want Ayurvedic Retreats should be able
 * to say so; one who only knows "a retreat" picks the parent.
 *
 * Unconditional — `hide_empty => false` — for the reason the hero's trip finder
 * gives: this is a question about interest, not a filter over a rendered grid, so
 * a type with nothing published yet is still a real answer. A category the
 * office has not written up is still one somebody can be curious about.
 *
 * @since 1.0.0
 *
 * @return array[] Each with 'id', 'label' and 'depth', in hierarchical order.
 */
function iflynepal_connect_interest_choices() {
	if ( ! taxonomy_exists( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	/*
	 * Walked from the roots down rather than listed flat and sorted by name: the
	 * taxonomy is hierarchical, and a child printed away from its parent tells a
	 * visitor nothing about which kind of trip it belongs to.
	 */
	$children = array();

	foreach ( $terms as $term ) {
		$children[ (int) $term->parent ][] = $term;
	}

	return iflynepal_connect_walk_interest( $children, 0, 0 );
}

/**
 * Flattens one level of the package-type tree, deepest-first within each branch.
 *
 * Split out of iflynepal_connect_interest_choices() because it calls itself, and
 * a recursive closure inside a function is harder to read than a named function
 * that takes what it needs.
 *
 * @since 1.0.0
 *
 * @param array<int,WP_Term[]> $children Terms grouped by parent term ID.
 * @param int                  $parent_id Parent term ID to render the children of.
 * @param int                  $depth    How deep this level sits.
 * @return array[] Each with 'id', 'label' and 'depth'.
 */
function iflynepal_connect_walk_interest( $children, $parent_id, $depth ) {
	if ( ! isset( $children[ $parent_id ] ) ) {
		return array();
	}

	$choices = array();

	foreach ( $children[ $parent_id ] as $term ) {
		$choices[] = array(
			'id'    => (int) $term->term_id,
			'label' => $term->name,
			'depth' => $depth,
		);

		$choices = array_merge( $choices, iflynepal_connect_walk_interest( $children, (int) $term->term_id, $depth + 1 ) );
	}

	return $choices;
}

/**
 * The full meta key for one connect-request field.
 *
 * @since 1.0.0
 *
 * @param string $key Field key.
 * @return string Meta key.
 */
function iflynepal_connect_meta_key( $key ) {
	return IFLYNEPAL_CONNECT_META_PREFIX . sanitize_key( $key );
}

/**
 * One stored value from a connect request.
 *
 * The enquiry text is read from the post content rather than meta — it is
 * stored there, and asking for it by the same field key everywhere else uses
 * keeps the details box and the list table from having to know the difference.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Connect request post ID.
 * @param string $key     Field key.
 * @return string
 */
function iflynepal_connect_field( $post_id, $key ) {
	$post_id = (int) $post_id;

	if ( 'message' === $key ) {
		return (string) get_post_field( 'post_content', $post_id );
	}

	return (string) get_post_meta( $post_id, iflynepal_connect_meta_key( $key ), true );
}

/**
 * Sanitizes one submitted value by its declared type.
 *
 * A phone number is kept as the visitor typed it, minus anything that is not a
 * digit, a space or one of the handful of characters people write numbers with.
 * It is *displayed* in that familiar form and reduced to bare digits only when a
 * wa.me link is built from it — deriving on read rather than storing a second
 * copy, the same rule the departure dates and the calendar's end date follow.
 *
 * @since 1.0.0
 *
 * @param string $value Raw submitted value, already unslashed.
 * @param string $type  Field type.
 * @return string Clean value.
 */
function iflynepal_connect_sanitize_value( $value, $type ) {
	$value = (string) $value;

	if ( 'email' === $type ) {
		return sanitize_email( $value );
	}

	if ( 'textarea' === $type ) {
		return sanitize_textarea_field( $value );
	}

	if ( 'tel' === $type ) {
		$clean = preg_replace( '/[^0-9+()\- ]/', '', $value );

		return trim( (string) $clean );
	}

	if ( 'package_type' === $type ) {
		$term_id = absint( $value );

		/*
		 * Checked against the taxonomy rather than taken on trust. The value
		 * reaches the handler from a visitor-controlled select, so a forged one
		 * would otherwise file a request against any term on the site — a
		 * post tag, say — whose name the list table would then print as a
		 * package type.
		 */
		if ( ! $term_id || ! term_exists( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
			return '';
		}

		return (string) $term_id;
	}

	return sanitize_text_field( $value );
}

/**
 * The HTML input type one field type is drawn as.
 *
 * Mapped rather than printed straight, because the schema's type names what the
 * value *is* and the attribute names what the browser should *do* with it —
 * they agree for email and tel and would not for a type added later. A type the
 * map does not know falls back to `text`, which is always a usable control,
 * rather than being printed into the attribute unchecked.
 *
 * @since 1.0.0
 *
 * @param string $type Field type from the schema.
 * @return string An HTML input type.
 */
function iflynepal_connect_input_type( $type ) {
	$types = array(
		'email' => 'email',
		'tel'   => 'tel',
		'text'  => 'text',
	);

	return isset( $types[ $type ] ) ? $types[ $type ] : 'text';
}

/**
 * The title one connect request is filed under.
 *
 * Generated rather than asked for: the visitor is sending a message, not naming
 * a record, and the list needs a row to say who it is from and what they were
 * curious about — which is exactly the two things they have already given.
 *
 * @since 1.0.0
 *
 * @param string $name    Sender's name.
 * @param int    $term_id Package type term the request is about, or 0.
 * @return string
 */
function iflynepal_connect_title( $name, $term_id ) {
	$name = '' === trim( $name ) ? __( 'Unnamed visitor', 'iflynepal' ) : $name;
	$term = $term_id ? get_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY ) : null;

	if ( ! $term || is_wp_error( $term ) ) {
		return $name;
	}

	/* translators: 1: the sender's name, 2: the package type they asked about. */
	return sprintf( __( '%1$s — %2$s', 'iflynepal' ), $name, $term->name );
}

/**
 * Stores one connect request.
 *
 * The only place that writes one, so the shape of a stored row is described in
 * exactly one function — and every value is sanitized here by its declared type
 * as well as in the handler. The handler has to do it to be able to validate
 * what it has; doing it again here means a second caller (a future REST route,
 * an import, a test) cannot write a row the front end would have rejected.
 *
 * `wp_slash()` wraps the title and the content because wp_insert_post()
 * unslashes what it is given — without it a name like O'Brien loses its quote.
 *
 * @since 1.0.0
 *
 * @param array<string,string> $values Clean field values, keyed as iflynepal_connect_fields().
 * @return int|WP_Error The new post ID, or the insert error.
 */
function iflynepal_connect_store( $values ) {
	$fields = iflynepal_connect_fields();
	$clean  = array();

	foreach ( $fields as $key => $field ) {
		$raw           = isset( $values[ $key ] ) && is_string( $values[ $key ] ) ? $values[ $key ] : '';
		$clean[ $key ] = iflynepal_connect_sanitize_value( $raw, isset( $field['type'] ) ? $field['type'] : 'text' );
	}

	$name    = isset( $clean['name'] ) ? $clean['name'] : '';
	$message = isset( $clean['message'] ) ? $clean['message'] : '';
	$term_id = isset( $clean['package_type'] ) ? (int) $clean['package_type'] : 0;

	$post_id = wp_insert_post(
		array(
			'post_type'    => IFLYNEPAL_CONNECT_POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => wp_slash( iflynepal_connect_title( $name, $term_id ) ),
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

		update_post_meta( $post_id, iflynepal_connect_meta_key( $key ), $value );
	}

	$statuses = array_keys( iflynepal_enquiry_statuses() );

	update_post_meta( $post_id, iflynepal_connect_meta_key( 'status' ), isset( $statuses[0] ) ? $statuses[0] : 'new' );

	/*
	 * The page the request was sent from. The tab is on the landing page today
	 * and the render rule is filterable, so where it came from is worth
	 * recording rather than assumed. Stored raw as a URL, never rendered as a
	 * link without escaping.
	 */
	$source = isset( $values['source'] ) ? esc_url_raw( $values['source'] ) : '';

	if ( '' !== $source ) {
		update_post_meta( $post_id, iflynepal_connect_meta_key( 'source' ), $source );
	}

	/**
	 * Fires once a connect request has been stored.
	 *
	 * The notification email hangs off this rather than being called from the
	 * handler, so a failed send can never lose the record: the row is already
	 * written by the time anything is sent.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $post_id The new request's post ID.
	 * @param array<string,string> $clean   The sanitized submitted values.
	 */
	do_action( 'iflynepal_connect_stored', $post_id, $clean );

	return $post_id;
}

/**
 * The package type one request is about.
 *
 * @since 1.0.0
 *
 * @param int $post_id Connect request post ID.
 * @return int Term ID, or 0.
 */
function iflynepal_connect_interest_id( $post_id ) {
	return (int) get_post_meta( (int) $post_id, iflynepal_connect_meta_key( 'package_type' ), true );
}

/**
 * The name of the package type one request is about.
 *
 * Resolved on every read rather than stored beside the ID: a term that has been
 * renamed since should read as its new name, and a term that has been deleted
 * should read as nothing rather than as a name that no longer exists anywhere.
 *
 * @since 1.0.0
 *
 * @param int $post_id Connect request post ID.
 * @return string Term name, or '' when none was chosen or the term has gone.
 */
function iflynepal_connect_interest_label( $post_id ) {
	$term_id = iflynepal_connect_interest_id( $post_id );

	if ( ! $term_id ) {
		return '';
	}

	$term = get_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY );

	return ( $term && ! is_wp_error( $term ) ) ? (string) $term->name : '';
}

/**
 * A click-to-chat URL for the number one visitor left.
 *
 * The opposite direction from iflynepal_whatsapp_url(), which opens a chat with
 * the office: this opens one with the sender, which is how most of these will be
 * answered. It reuses the settings sanitizer for the digits so both directions
 * agree on what a number is — including the leading-zero rule, which is the one
 * that silently opens a chat with nobody when it is got wrong.
 *
 * @since 1.0.0
 *
 * @param int $post_id Connect request post ID.
 * @return string The URL, or '' when the stored number has no digits in it.
 */
function iflynepal_connect_chat_url( $post_id ) {
	$digits = iflynepal_booking_sanitize_setting( iflynepal_connect_field( $post_id, 'whatsapp' ), 'digits' );

	return '' === $digits ? '' : 'https://wa.me/' . $digits;
}
