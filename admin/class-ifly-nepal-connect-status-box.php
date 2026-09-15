<?php
/**
 * The Follow-up box — the one thing on a connect request screen that can be
 * changed.
 *
 * Out of the Connect Request box on purpose, for the reason the enquiry's own
 * status box records: everything in there is what a visitor wrote and is printed
 * read-only, and a single editable control at the foot of that run reads as one
 * more field of the record rather than as the office's own note.
 *
 * The states, their colours and the pill renderer are the enquiry's — see
 * iflynepal_enquiry_statuses(). Only the meta key differs, because the two lists
 * are separate records.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Takes one connect request's follow-up status.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Connect_Status_Box {

	/**
	 * Meta box ID, the field's name, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_connect_status';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . IFLYNEPAL_CONNECT_POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Registers the box in the sidebar.
	 *
	 * `default` priority rather than `high`, so it sits under Publish rather
	 * than above it: Update is what an editor reaches for after changing this,
	 * and a box that pushes the button down the page puts the two out of order.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Follow-up', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_CONNECT_POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Prints the control.
	 *
	 * Radios rather than a select, and each label carries the same pill the list
	 * table draws, from the same renderer — so the colour chosen here is the
	 * colour that will be scanned for there.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The request being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$current = iflynepal_connect_status( $post->ID );
		?>
		<div class="iflynepal-enq-status">
			<?php foreach ( iflynepal_enquiry_statuses() as $key => $status ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( self::ID ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?>>
					<?php
					// Finished, escaped markup from the shared pill renderer.
					echo iflynepal_enquiry_status_pill( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</label>
			<?php endforeach; ?>

			<p class="iflynepal-enq-note"><?php esc_html_e( 'Where this conversation has got to. Shown in the Follow-up column of the Connect Requests list.', 'iflynepal' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Saves the status.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The request being saved.
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

		$status   = isset( $_POST[ self::ID ] ) ? sanitize_key( wp_unslash( $_POST[ self::ID ] ) ) : '';
		$statuses = iflynepal_enquiry_statuses();

		/*
		 * A value that is not on offer is discarded rather than stored. The
		 * radios cannot produce one; a forged post can, and a status nothing
		 * recognises reads back as the first one anyway — so storing it would
		 * only leave a row nobody can explain and a pill that draws blank.
		 */
		if ( ! isset( $statuses[ $status ] ) ) {
			return;
		}

		update_post_meta( $post_id, iflynepal_connect_meta_key( 'status' ), $status );
	}
}

new IFly_Nepal_Connect_Status_Box();
