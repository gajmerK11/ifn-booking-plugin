<?php
/**
 * The Package Details meta box.
 *
 * Renders and saves the structured facts declared in
 * includes/package/package-meta.php — the card label, duration, price and the
 * rest — plus the fixed departure dates.
 *
 * Nothing here enforces anything. Departure dates are a list an editor types
 * and the front end prints; there is no capacity attached to a date, no seat
 * count, and no check that a booking falls on one. That is the client's stated
 * requirement, repeated across two meetings, and not an omission to fix.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Edits the structured facts a package carries.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Details_Meta_Box {

	/**
	 * Meta box ID, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_details';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . IFLYNEPAL_PACKAGE_POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Registers the box below the editor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Package details', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Draws the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( iflynepal_package_detail_fields() as $key => $field ) {
			$id    = self::ID . '_' . $key;
			$value = iflynepal_package_field( $post->ID, $key );

			echo '<tr>';
			printf(
				'<th scope="row"><label for="%1$s">%2$s</label></th>',
				esc_attr( $id ),
				esc_html( $field['label'] )
			);
			echo '<td>';

			if ( 'text' === $field['type'] ) {
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( self::ID . '[' . $key . ']' ),
					esc_attr( $value )
				);
			} else {
				printf(
					'<textarea class="large-text" rows="%1$d" id="%2$s" name="%3$s">%4$s</textarea>',
					'dates' === $field['type'] || 'lines' === $field['type'] ? 5 : 3,
					esc_attr( $id ),
					esc_attr( self::ID . '[' . $key . ']' ),
					esc_textarea( $value )
				);
			}

			if ( '' !== $field['help'] ) {
				printf( '<p class="description">%s</p>', esc_html( $field['help'] ) );
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Saves the submitted fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post being saved.
	 * @return void
	 */
	public function save( $post_id ) {
		$nonce_key = self::ID . '_nonce';

		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), self::ID . '_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitized per field below, by the type the schema declares.
		$submitted = isset( $_POST[ self::ID ] ) ? wp_unslash( $_POST[ self::ID ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $submitted ) ) {
			return;
		}

		foreach ( iflynepal_package_detail_fields() as $key => $field ) {
			$raw   = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			$value = iflynepal_package_sanitize_value( $raw, $field['type'] );

			if ( '' === $value ) {
				delete_post_meta( $post_id, iflynepal_package_meta_key( $key ) );

				continue;
			}

			update_post_meta( $post_id, iflynepal_package_meta_key( $key ), $value );
		}
	}
}

new IFly_Nepal_Package_Details_Meta_Box();
