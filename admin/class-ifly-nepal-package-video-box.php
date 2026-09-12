<?php
/**
 * The package Featured Video meta box.
 *
 * Sits in the sidebar directly under Featured image, because it is the same
 * decision made twice: what the top of the package page opens on. Choose a video
 * and the page plays it; choose none and the featured image is what shows. The
 * featured image is never optional — it is what the archive cards, the search
 * results and every share card use, and none of those can play a video.
 *
 * The attachment ID is stored, never a URL: a URL breaks the day the site
 * changes domain, and the ID is what lets the front end ask the media library
 * for the file's real MIME type rather than guessing it from an extension.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Picks the video the package page opens on.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Video_Box {

	/**
	 * Meta box ID, the form field name, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_video';

	/**
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'video';

	/**
	 * Which schema field it draws.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const FIELD = 'featured_video';

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
	 * Registers the box in the sidebar, under Featured image.
	 *
	 * `low` priority is what puts it below the featured image box rather than
	 * above it, and registering before the Gallery box is what puts it above
	 * that one: boxes of equal priority sit in registration order.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Featured Video', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'side',
			'low'
		);
	}

	/**
	 * Draws the picker.
	 *
	 * The markup contract is the shared admin script's — the same one every image
	 * picker in the plugin uses — with one attribute added to say this one asks
	 * the media library for videos.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$fields        = iflynepal_package_fields_for_box( self::BOX );
		$field         = isset( $fields[ self::FIELD ] ) ? $fields[ self::FIELD ] : array();
		$attachment_id = (int) iflynepal_package_field( $post->ID, self::FIELD );
		$url           = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
		?>
		<div class="iflynepal-video" data-iflynepal-media
			data-iflynepal-media-type="video"
			data-iflynepal-media-title="<?php esc_attr_e( 'Choose video', 'iflynepal' ); ?>"
			data-iflynepal-media-button="<?php esc_attr_e( 'Use this video', 'iflynepal' ); ?>">

			<span class="iflynepal-video__preview" data-iflynepal-media-preview>
				<?php if ( $url ) : ?>
					<video src="<?php echo esc_url( $url ); ?>" controls preload="metadata"></video>
				<?php endif; ?>
			</span>

			<input type="hidden" name="<?php echo esc_attr( self::ID ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-iflynepal-media-value />

			<p>
				<button type="button" class="button" data-iflynepal-media-select><?php esc_html_e( 'Choose video', 'iflynepal' ); ?></button>
				<button type="button" class="button-link" data-iflynepal-media-remove<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'iflynepal' ); ?></button>
			</p>

			<?php if ( isset( $field['help'] ) && '' !== $field['help'] ) : ?>
				<p class="description"><?php echo esc_html( $field['help'] ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.iflynepal-video__preview video {
				display: block;
				width: 100%;
				margin-bottom: 8px;
				border: 1px solid #dcdcde;
				border-radius: 3px;
			}
		</style>
		<?php
	}

	/**
	 * Saves the chosen attachment.
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

		$raw   = isset( $_POST[ self::ID ] ) ? wp_unslash( $_POST[ self::ID ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = iflynepal_package_sanitize_value( $raw, 'video' );

		if ( '' === $value || '0' === $value ) {
			delete_post_meta( $post_id, iflynepal_package_meta_key( self::FIELD ) );

			return;
		}

		update_post_meta( $post_id, iflynepal_package_meta_key( self::FIELD ), $value );
	}
}

new IFly_Nepal_Package_Video_Box();
