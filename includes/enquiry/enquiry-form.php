<?php
/**
 * The public enquiry form — its handler, its notices and where it is rendered.
 *
 * The submission path is the theme's own contact-form pattern, which is the
 * WordPress-native one: a form posting to admin-post.php, a nonce, a honeypot,
 * capability-free public access through the `nopriv` action, per-field
 * sanitizing, and a redirect back to the page with a status argument so a
 * refresh cannot resubmit. The one difference from the theme's is that this one
 * **stores** the enquiry as well as emailing it, which is the whole point of the
 * screen it feeds.
 *
 * The record is written before anything is sent. An email that fails — a
 * misconfigured SMTP on the host, a rejected recipient — must not be able to
 * lose a sales enquiry, so the store is the source of truth and the mail is a
 * notification about something already saved.
 *
 * ⚠ A full-page cache is live on production (Cloudflare, edge TTL 2h). The
 * nonce printed into this form is cached with the page, and a WordPress nonce
 * for a logged-out visitor is valid for 24 hours with a 12-hour tick — so the
 * edge TTL must stay comfortably under that, or a visitor can be served a form
 * whose nonce has already expired and the submission is rejected with nothing on
 * screen to explain why. Two hours is fine; a day is not.
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
const IFLYNEPAL_ENQUIRY_ACTION = 'iflynepal_enquiry_submit';

/**
 * Returns the visitor to the page they came from with a status.
 *
 * The referer is validated rather than trusted — an unvalidated redirect out of
 * a POST handler is an open redirect, and this one is reachable by anybody.
 * `wp_validate_redirect()` refuses any host but this one and falls back to the
 * home page.
 *
 * @since 1.0.0
 *
 * @param string $status Result key.
 * @return void
 */
function iflynepal_enquiry_redirect( $status ) {
	$fallback = home_url( '/' );
	$referer  = wp_get_referer();
	$url      = $referer ? wp_validate_redirect( $referer, $fallback ) : $fallback;
	$url      = remove_query_arg( 'iflynepal_enquiry', $url );

	wp_safe_redirect( add_query_arg( 'iflynepal_enquiry', sanitize_key( $status ), $url ) . '#iflynepal-enquiry' );
	exit;
}

/**
 * Validates, stores and forwards one public enquiry.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_handle_enquiry_form() {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

	if ( 'POST' !== $method ) {
		iflynepal_enquiry_redirect( 'invalid' );
	}

	$nonce = isset( $_POST['iflynepal_enquiry_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['iflynepal_enquiry_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, IFLYNEPAL_ENQUIRY_ACTION ) ) {
		iflynepal_enquiry_redirect( 'expired' );
	}

	/*
	 * A submission that fills the hidden field is a bot. It is answered with
	 * the success status rather than an error: an error tells the sender which
	 * check caught them, and the next attempt comes back without the tell.
	 */
	if ( ! empty( $_POST['website'] ) ) {
		iflynepal_enquiry_redirect( 'success' );
	}

	$values = array();

	foreach ( iflynepal_enquiry_fields() as $key => $field ) {
		$raw            = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line by the field's declared type.
		$type           = isset( $field['type'] ) ? $field['type'] : 'text';
		$values[ $key ] = iflynepal_enquiry_sanitize_value( is_string( $raw ) ? $raw : '', $type );

		if ( empty( $field['required'] ) ) {
			continue;
		}

		if ( 'email' === $type ? ! is_email( $values[ $key ] ) : '' === trim( $values[ $key ] ) ) {
			iflynepal_enquiry_redirect( 'invalid' );
		}
	}

	$package_id = isset( $_POST['package_id'] ) ? absint( wp_unslash( $_POST['package_id'] ) ) : 0;

	/*
	 * The package is checked rather than taken on trust. It reaches the handler
	 * as a number in a hidden field, so a forged one would otherwise file an
	 * enquiry against any post on the site — including a private one, whose
	 * title the list table would then print.
	 */
	if ( $package_id && ( IFLYNEPAL_PACKAGE_POST_TYPE !== get_post_type( $package_id ) || 'publish' !== get_post_status( $package_id ) ) ) {
		$package_id = 0;
	}

	$values['source'] = (string) wp_get_referer();

	$stored = iflynepal_enquiry_store( $values, $package_id );

	if ( is_wp_error( $stored ) ) {
		iflynepal_enquiry_redirect( 'error' );
	}

	iflynepal_enquiry_redirect( 'success' );
}
add_action( 'admin_post_' . IFLYNEPAL_ENQUIRY_ACTION, 'iflynepal_handle_enquiry_form' );
add_action( 'admin_post_nopriv_' . IFLYNEPAL_ENQUIRY_ACTION, 'iflynepal_handle_enquiry_form' );

/**
 * Emails the office about one stored enquiry.
 *
 * Hooked to the store's own action, so the record exists before this runs and a
 * failed send loses nothing. The reply-to is the visitor, so answering the
 * notification answers them.
 *
 * The recipient is whatever iflynepal_notification_recipient() resolves to —
 * the plugin's own setting, the Contact page's office address, or the site
 * administrator — so every form on the site reaches one inbox.
 *
 * @since 1.0.0
 *
 * @param int                  $post_id    The stored enquiry.
 * @param array<string,string> $values     The clean submitted values.
 * @param int                  $package_id Package the enquiry is about, or 0.
 * @return void
 */
function iflynepal_enquiry_notify( $post_id, $values, $package_id ) {
	$recipient = iflynepal_notification_recipient( $post_id );

	if ( '' === $recipient ) {
		return;
	}

	$name    = isset( $values['name'] ) ? $values['name'] : '';
	$email   = isset( $values['email'] ) ? $values['email'] : '';
	$message = isset( $values['message'] ) ? $values['message'] : '';
	$package = $package_id ? get_the_title( $package_id ) : __( 'No package — sent from the catalogue', 'iflynepal' );

	/* translators: %s: the sender's name. */
	$subject = sprintf( __( 'New package enquiry from %s', 'iflynepal' ), $name );

	$body = implode(
		"\n",
		array(
			/* translators: %s: the sender's name. */
			sprintf( __( 'Name: %s', 'iflynepal' ), $name ),
			/* translators: %s: the sender's email address. */
			sprintf( __( 'Email: %s', 'iflynepal' ), $email ),
			/* translators: %s: the package the enquiry is about. */
			sprintf( __( 'Package: %s', 'iflynepal' ), $package ),
			'',
			__( 'Message:', 'iflynepal' ),
			$message,
			'',
			/* translators: %s: the admin URL of the stored enquiry. */
			sprintf( __( 'Open in the admin: %s', 'iflynepal' ), get_edit_post_link( $post_id, 'raw' ) ),
		)
	);

	$headers = array( sprintf( 'Reply-To: %1$s <%2$s>', $name, $email ) );

	wp_mail( $recipient, $subject, $body, $headers );
}
add_action( 'iflynepal_enquiry_stored', 'iflynepal_enquiry_notify', 10, 3 );

/**
 * The notice to show after a submission, if the page was redirected back to.
 *
 * @since 1.0.0
 *
 * @return array{type:string,message:string}|null The notice, or null.
 */
function iflynepal_enquiry_notice() {
	// A status the handler put in the URL itself; there is no state change here to protect.
	$status = isset( $_GET['iflynepal_enquiry'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$notices = array(
		'success' => array(
			'type'    => 'success',
			'message' => __( 'Thank you. Your enquiry has reached us and we will be in touch shortly.', 'iflynepal' ),
		),
		'invalid' => array(
			'type'    => 'error',
			'message' => __( 'Please check the required fields and try again.', 'iflynepal' ),
		),
		'expired' => array(
			'type'    => 'error',
			'message' => __( 'This form had been open too long to be sent safely. Please try again.', 'iflynepal' ),
		),
		'error'   => array(
			'type'    => 'error',
			'message' => __( 'Your enquiry could not be saved. Please email or call us instead.', 'iflynepal' ),
		),
	);

	return isset( $notices[ $status ] ) ? $notices[ $status ] : null;
}

/**
 * Renders the enquiry form at the end of a catalogue page.
 *
 * In the footer because it is an overlay: the script turns it into a modal, and
 * a modal sitting in the middle of the reading order is a modal that has to be
 * tabbed through to reach the content behind it.
 *
 * It is printed on every catalogue template, not only on a package, because the
 * archives are where a visitor who has not chosen yet is standing — an enquiry
 * from there simply carries no package.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_enquiry_render_form() {
	if ( ! iflynepal_booking_is_package_template() ) {
		return;
	}

	iflynepal_booking_get_part(
		'parts/enquiry-form',
		array(
			'package_id' => is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ? get_queried_object_id() : 0,
		)
	);
}
add_action( 'wp_footer', 'iflynepal_enquiry_render_form' );
