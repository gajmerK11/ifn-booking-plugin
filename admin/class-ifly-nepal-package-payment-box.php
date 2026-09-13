<?php
/**
 * The package Payment box — which Easy PayPal & Stripe button Book now pays with.
 *
 * This closes the half of P22 that mattered: the booking shortcode and the
 * confirmation notice were stored and rendered but had no editing surface at
 * all, so nobody could wire a package up to the gateway without a database
 * client.
 *
 * The control is a list of the gateway's own buttons rather than a box to paste
 * a shortcode into. An editor picks "Ayurvedic retreat — 320" from the buttons
 * they made; nothing has to be copied, and a mistyped shortcode — the failure
 * that would otherwise print `[wpecpp id="12` onto a price card — cannot be
 * expressed. The paste box is still underneath for the cases a list cannot cover.
 *
 * No payment logic lives here or anywhere else in this plugin. See the header of
 * includes/payment/payment-buttons.php.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chooses the payment button for one package.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Payment_Box {

	/**
	 * Meta box ID, the field name prefix, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_payment';

	/**
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'payment';

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
	 * Registers the box in the sidebar.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Payment', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Draws the box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$chosen  = (int) iflynepal_package_field( $post->ID, 'booking_button' );
		$buttons = iflynepal_payment_buttons();
		$fields  = iflynepal_package_fields_for_box( self::BOX );
		?>
		<style>
			.iflynepal-pay-box p.description { margin: 4px 0 14px; }
			.iflynepal-pay-box label.iflynepal-pay-label { display: block; margin-bottom: 4px; font-weight: 600; color: #1d2327; }
			.iflynepal-pay-box select,
			.iflynepal-pay-box textarea { width: 100%; }
			.iflynepal-pay-box textarea.iflynepal-pay-code { font-family: Consolas, Monaco, monospace; font-size: 12px; }
			.iflynepal-pay-box .iflynepal-pay-warn { padding: 8px 10px; border-left: 3px solid #d63638; background: #fcf0f1; margin: 0 0 12px; }
			.iflynepal-pay-box .iflynepal-pay-links { margin: 0 0 14px; }
		</style>

		<div class="iflynepal-pay-box">
			<?php if ( ! iflynepal_payment_gateway_active() ) : ?>
				<p class="iflynepal-pay-warn">
					<?php if ( iflynepal_payment_gateway_installed() ) : ?>
						<?php esc_html_e( 'Easy PayPal & Stripe Button is installed but switched off, so this package can take no payment. Activate it on the Plugins screen.', 'iflynepal' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Easy PayPal & Stripe Button is not installed, so this package can take no payment. Install and activate it, then create a button.', 'iflynepal' ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php
			/*
			 * Only the fields this box declares, and in schema order — the same
			 * list the save routine walks, because a box that draws five fields
			 * and saves six deletes the sixth on the first Update.
			 */
			foreach ( $fields as $key => $field ) :
				$id   = self::ID . '_' . $key;
				$name = self::ID . '[' . $key . ']';
				?>
				<p>
					<label class="iflynepal-pay-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>

					<?php if ( 'button' === $field['type'] ) : ?>
						<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" <?php disabled( ! iflynepal_payment_gateway_active() ); ?>>
							<option value="0"><?php esc_html_e( '— No payment button —', 'iflynepal' ); ?></option>

							<?php foreach ( $buttons as $button_id => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $button_id ); ?>" <?php selected( $chosen, $button_id ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>

							<?php
							/*
							 * A chosen button that is no longer on offer — deleted,
							 * or moved to draft — is still listed and still selected,
							 * so opening a package and pressing Update cannot quietly
							 * unset it. The front end checks it separately and simply
							 * renders nothing while it is unusable.
							 */
							?>
							<?php if ( $chosen && ! isset( $buttons[ $chosen ] ) ) : ?>
								<option value="<?php echo esc_attr( (string) $chosen ); ?>" selected>
									<?php
									printf(
										/* translators: %d: the payment button's ID. */
										esc_html__( 'Button #%d — deleted or unpublished', 'iflynepal' ),
										(int) $chosen
									);
									?>
								</option>
							<?php endif; ?>
						</select>
					<?php elseif ( 'textarea' === $field['type'] ) : ?>
						<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" rows="3" class="<?php echo 'booking' === $key ? 'iflynepal-pay-code' : ''; ?>"><?php echo esc_textarea( iflynepal_package_field( $post->ID, $key ) ); ?></textarea>
					<?php else : ?>
						<input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( iflynepal_package_field( $post->ID, $key ) ); ?>">
					<?php endif; ?>
				</p>

				<p class="description"><?php echo esc_html( $field['help'] ); ?></p>

				<?php if ( 'button' === $field['type'] && iflynepal_payment_gateway_active() ) : ?>
					<p class="iflynepal-pay-links">
						<a href="<?php echo esc_url( iflynepal_payment_buttons_admin_url( true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a button', 'iflynepal' ); ?></a>
						&nbsp;·&nbsp;
						<a href="<?php echo esc_url( iflynepal_payment_buttons_admin_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage buttons', 'iflynepal' ); ?></a>
					</p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Saves the box.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The package being saved.
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

		foreach ( iflynepal_package_fields_for_box( self::BOX ) as $key => $field ) {
			/*
			 * The select is disabled while the gateway is off, and a disabled
			 * control submits nothing. Without this the stored button would be
			 * wiped by the first Update made while the plugin happened to be
			 * deactivated — the package would come back wired to nothing once it
			 * was switched on again.
			 */
			if ( 'button' === $field['type'] && ! isset( $submitted[ $key ] ) ) {
				continue;
			}

			$raw   = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			$value = iflynepal_package_sanitize_value( $raw, $field['type'] );

			if ( '' === $value || '0' === $value ) {
				delete_post_meta( $post_id, iflynepal_package_meta_key( $key ) );

				continue;
			}

			update_post_meta( $post_id, iflynepal_package_meta_key( $key ), $value );
		}
	}
}

new IFly_Nepal_Package_Payment_Box();
