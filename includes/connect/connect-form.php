<?php
/**
 * The "Connect With Us" tab — its handler, its notices and where it is drawn.
 *
 * The submission path is the same WordPress-native one the enquiry form and the
 * theme's contact form use: a form posting to admin-post.php, a nonce, a
 * honeypot, public access through the `nopriv` action, per-field sanitizing, a
 * validated referer redirect — an unvalidated one out of a public POST handler
 * is an open redirect — and a status argument so a refresh cannot resubmit.
 *
 * The record is written before anything is sent. An email that fails — a
 * misconfigured SMTP on the host, a rejected recipient — must not be able to
 * lose a lead, so the store is the source of truth and the mail is a
 * notification about something already saved.
 *
 * ⚠ A full-page cache is live on production (Cloudflare, edge TTL 2h). The
 * nonce printed into this form is cached with the page, and a WordPress nonce
 * for a logged-out visitor is valid for 24 hours with a 12-hour tick — so the
 * edge TTL has to stay comfortably under that, or a visitor is served a form
 * whose nonce has already expired and the submission is rejected with nothing on
 * screen to explain why. Two hours is fine; a day is not. Same note as the
 * enquiry form, and it applies harder here: this form is on the landing page,
 * which is the most-cached URL on the site.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The action name the form posts under.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_CONNECT_ACTION = 'iflynepal_connect_submit';

/**
 * Whether the connect tab is drawn on the current request.
 *
 * The landing page only, which is what was asked for. It is a filter rather
 * than a hardcoded condition because "site-wide, like the WhatsApp button"
 * is a plausible next request and should be one line rather than an edit to
 * this file — return true for whatever a site wants.
 *
 * Never in the admin, never on a feed, and never on a REST or admin-post
 * request: wp_footer does not fire there, but the enqueue hooks this shares a
 * condition with can.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function iflynepal_connect_should_render() {
	$render = is_front_page() && ! is_admin() && ! is_feed();

	/**
	 * Filters whether the connect tab is drawn on this request.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $render Whether to draw it.
	 */
	return (bool) apply_filters( 'iflynepal_connect_should_render', $render );
}

/**
 * Returns the visitor to the page they came from with a status.
 *
 * `wp_validate_redirect()` refuses any host but this one and falls back to the
 * home page. The hash puts them back at the panel, which the script then
 * reopens so the result is in front of them rather than at the foot of the page.
 *
 * @since 1.0.0
 *
 * @param string $status Result key.
 * @return void
 */
function iflynepal_connect_redirect( $status ) {
	$fallback = home_url( '/' );
	$referer  = wp_get_referer();
	$url      = $referer ? wp_validate_redirect( $referer, $fallback ) : $fallback;
	$url      = remove_query_arg( 'iflynepal_connect', $url );

	wp_safe_redirect( add_query_arg( 'iflynepal_connect', sanitize_key( $status ), $url ) . '#iflynepal-connect-panel' );
	exit;
}

/**
 * Validates, stores and forwards one connect request.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_handle_connect_form() {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

	if ( 'POST' !== $method ) {
		iflynepal_connect_redirect( 'invalid' );
	}

	$nonce = isset( $_POST['iflynepal_connect_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['iflynepal_connect_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, IFLYNEPAL_CONNECT_ACTION ) ) {
		iflynepal_connect_redirect( 'expired' );
	}

	/*
	 * A submission that fills the hidden field is a bot. It is answered with
	 * the success status rather than an error: an error tells the sender which
	 * check caught them, and the next attempt comes back without the tell.
	 */
	if ( ! empty( $_POST['website'] ) ) {
		iflynepal_connect_redirect( 'success' );
	}

	$values = array();

	foreach ( iflynepal_connect_fields() as $key => $field ) {
		$raw            = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line by the field's declared type.
		$type           = isset( $field['type'] ) ? $field['type'] : 'text';
		$values[ $key ] = iflynepal_connect_sanitize_value( is_string( $raw ) ? $raw : '', $type );

		if ( empty( $field['required'] ) ) {
			continue;
		}

		if ( ! iflynepal_connect_value_is_usable( $values[ $key ], $type ) ) {
			iflynepal_connect_redirect( 'invalid' );
		}
	}

	$values['source'] = (string) wp_get_referer();

	$stored = iflynepal_connect_store( $values );

	if ( is_wp_error( $stored ) ) {
		iflynepal_connect_redirect( 'error' );
	}

	iflynepal_connect_redirect( 'success' );
}
add_action( 'admin_post_' . IFLYNEPAL_CONNECT_ACTION, 'iflynepal_handle_connect_form' );
add_action( 'admin_post_nopriv_' . IFLYNEPAL_CONNECT_ACTION, 'iflynepal_handle_connect_form' );

/**
 * Whether one clean value is enough to satisfy a required field.
 *
 * Split out because "not empty" is the wrong test for two of the types. An
 * address has to be an address, or the office has no way of answering it. And a
 * phone number that has been stripped of everything but its punctuation — `+()`
 * survives the tel sanitizer — is a non-empty string with no number in it, so
 * the digits are counted rather than the characters. Seven is the shortest
 * national number in use anywhere; a country code makes any real WhatsApp
 * number longer than that.
 *
 * @since 1.0.0
 *
 * @param string $value Clean value.
 * @param string $type  Field type.
 * @return bool
 */
function iflynepal_connect_value_is_usable( $value, $type ) {
	if ( 'email' === $type ) {
		return (bool) is_email( $value );
	}

	if ( 'tel' === $type ) {
		return strlen( (string) preg_replace( '/[^0-9]/', '', $value ) ) >= 7;
	}

	return '' !== trim( (string) $value );
}

/**
 * Emails the office about one stored connect request.
 *
 * Hooked to the store's own action, so the record exists before this runs and a
 * failed send loses nothing. The reply-to is the visitor, so answering the
 * notification answers them.
 *
 * Recipient resolution is shared with the enquiry's — the Contact page's office
 * address when the iFly Nepal theme is active, the site admin address otherwise
 * — through the same `iflynepal_enquiry_recipient` filter, so a site that
 * redirects one inbox redirects both rather than discovering the second one
 * later.
 *
 * @since 1.0.0
 *
 * @param int                  $post_id The stored request.
 * @param array<string,string> $values  The clean submitted values.
 * @return void
 */
function iflynepal_connect_notify( $post_id, $values ) {
	$recipient = '';

	if ( function_exists( 'iflynepal_contact_plain' ) ) {
		$recipient = sanitize_email( iflynepal_contact_plain( 'office_email' ) );
	}

	if ( ! is_email( $recipient ) ) {
		$recipient = sanitize_email( get_option( 'admin_email' ) );
	}

	/** This filter is documented in includes/enquiry/enquiry-form.php */
	$recipient = (string) apply_filters( 'iflynepal_enquiry_recipient', $recipient, $post_id );

	if ( ! is_email( $recipient ) ) {
		return;
	}

	$name     = isset( $values['name'] ) ? $values['name'] : '';
	$email    = isset( $values['email'] ) ? $values['email'] : '';
	$whatsapp = isset( $values['whatsapp'] ) ? $values['whatsapp'] : '';
	$message  = isset( $values['message'] ) ? $values['message'] : '';
	$interest = iflynepal_connect_interest_label( $post_id );

	/* translators: %s: the sender's name. */
	$subject = sprintf( __( 'New Connect With Us request from %s', 'iflynepal' ), $name );

	$body = implode(
		"\n",
		array(
			/* translators: %s: the sender's name. */
			sprintf( __( 'Name: %s', 'iflynepal' ), $name ),
			/* translators: %s: the sender's WhatsApp number. */
			sprintf( __( 'WhatsApp: %s', 'iflynepal' ), $whatsapp ),
			/* translators: %s: the sender's email address. */
			sprintf( __( 'Email: %s', 'iflynepal' ), $email ),
			/* translators: %s: the package type the sender asked about. */
			sprintf( __( 'Interested in: %s', 'iflynepal' ), '' !== $interest ? $interest : __( 'Not specified', 'iflynepal' ) ),
			'',
			__( 'Enquiry:', 'iflynepal' ),
			'' !== $message ? $message : __( '(none given)', 'iflynepal' ),
			'',
			/* translators: %s: a WhatsApp click-to-chat link for the sender. */
			sprintf( __( 'Reply on WhatsApp: %s', 'iflynepal' ), iflynepal_connect_chat_url( $post_id ) ),
			/* translators: %s: the admin URL of the stored request. */
			sprintf( __( 'Open in the admin: %s', 'iflynepal' ), get_edit_post_link( $post_id, 'raw' ) ),
		)
	);

	$headers = array( sprintf( 'Reply-To: %1$s <%2$s>', $name, $email ) );

	wp_mail( $recipient, $subject, $body, $headers );
}
add_action( 'iflynepal_connect_stored', 'iflynepal_connect_notify', 10, 2 );

/**
 * The notice to show after a submission, if the page was redirected back to.
 *
 * @since 1.0.0
 *
 * @return array{type:string,message:string}|null The notice, or null.
 */
function iflynepal_connect_notice() {
	// A status the handler put in the URL itself; there is no state change here to protect.
	$status = isset( $_GET['iflynepal_connect'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_connect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$notices = array(
		'success' => array(
			'type'    => 'success',
			'message' => __( 'Thank you. We have your details and will be in touch shortly.', 'iflynepal' ),
		),
		'invalid' => array(
			'type'    => 'error',
			'message' => __( 'Please check your name, WhatsApp number and email, and try again.', 'iflynepal' ),
		),
		'expired' => array(
			'type'    => 'error',
			'message' => __( 'This form had been open too long to be sent safely. Please try again.', 'iflynepal' ),
		),
		'error'   => array(
			'type'    => 'error',
			'message' => __( 'Your message could not be saved. Please email or call us instead.', 'iflynepal' ),
		),
	);

	return isset( $notices[ $status ] ) ? $notices[ $status ] : null;
}

/**
 * Renders the connect tab and its panel in the footer.
 *
 * In the footer because the panel is an overlay: the script turns it into a
 * drawer, and a drawer sitting in the middle of the reading order is one that
 * has to be tabbed through to reach the content behind it.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_connect_render_form() {
	if ( ! iflynepal_connect_should_render() ) {
		return;
	}

	iflynepal_booking_get_part( 'parts/connect-form' );
}
add_action( 'wp_footer', 'iflynepal_connect_render_form' );
