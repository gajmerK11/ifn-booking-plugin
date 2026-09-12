<?php
/**
 * The package Gallery meta box.
 *
 * Sits in the sidebar under Featured image, because it is the same job: the
 * featured image is the first and largest photograph in the design's gallery
 * grid, and these are the rest of it. Keeping them apart, one in the sidebar and
 * one in a panel halfway down the page, would hide the fact that they are one
 * set of pictures.
 *
 * Images are stored as attachment IDs, comma separated — never URLs. A URL
 * breaks the day the site changes domain and throws away the generated sizes and
 * the alt text the upload came with.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Picks the photographs the package page's gallery is built from.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Gallery_Box {

	/**
	 * Meta box ID, the form field name, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_gallery';

	/**
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'gallery';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . IFLYNEPAL_PACKAGE_POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the picker on the package editor only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( IFLYNEPAL_PACKAGE_POST_TYPE !== get_post_type() ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'iflynepal-package-gallery',
			IFLYNEPAL_BOOKING_URL . 'assets/js/admin/package-gallery.js',
			array(),
			iflynepal_booking_asset_version( 'assets/js/admin/package-gallery.js' ),
			true
		);

		wp_localize_script(
			'iflynepal-package-gallery',
			'iflynepalPackageGallery',
			array(
				'chooseTitle' => __( 'Add images', 'iflynepal' ),
				'chooseUse'   => __( 'Add to gallery', 'iflynepal' ),
				'remove'      => __( 'Remove image', 'iflynepal' ),
			)
		);
	}

	/**
	 * Registers the box in the sidebar, under Featured image.
	 *
	 * `low` priority is what puts it below the featured image box rather than
	 * above it — core registers that one at `low` too, and boxes of equal
	 * priority sit in registration order, with core's registered first.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Gallery', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'side',
			'low'
		);
	}

	/**
	 * Draws the picker.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$fields = iflynepal_package_fields_for_box( self::BOX );
		$field  = isset( $fields['gallery'] ) ? $fields['gallery'] : array();
		$max    = isset( $field['max'] ) ? (int) $field['max'] : 0;
		$ids    = iflynepal_package_gallery( $post->ID );
		?>
		<div class="iflynepal-gallery" data-iflynepal-gallery data-max="<?php echo esc_attr( (string) $max ); ?>">
			<?php
			/*
			 * One hidden input carries the whole list. The alternative — an input
			 * per image — has to be renumbered on every add, remove and reorder,
			 * and a gap in the numbering is a gap in the posted array.
			 */
			?>
			<input type="hidden" name="<?php echo esc_attr( self::ID ); ?>" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-iflynepal-gallery-value />

			<ul class="iflynepal-gallery__grid" data-iflynepal-gallery-list>
				<?php foreach ( $ids as $id ) : ?>
					<li class="iflynepal-gallery__item" data-iflynepal-gallery-item data-id="<?php echo esc_attr( (string) $id ); ?>">
						<?php
						// Built by wp_get_attachment_image(), which escapes its own output.
						echo wp_get_attachment_image( $id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						<button type="button" class="iflynepal-gallery__remove" data-iflynepal-gallery-remove aria-label="<?php esc_attr_e( 'Remove image', 'iflynepal' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<p>
				<button type="button" class="button" data-iflynepal-gallery-add><?php esc_html_e( 'Add images', 'iflynepal' ); ?></button>
			</p>

			<?php if ( isset( $field['help'] ) && '' !== $field['help'] ) : ?>
				<p class="description"><?php echo esc_html( $field['help'] ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.iflynepal-gallery__grid {
				display: grid;
				gap: 6px;
				grid-template-columns: repeat(3, 1fr);
				margin: 0 0 10px;
			}

			.iflynepal-gallery__item {
				position: relative;
				margin: 0;
				/* The thumbnails are the handle for dragging one into place. */
				cursor: grab;
			}

			.iflynepal-gallery__item.is-dragging {
				opacity: 0.4;
			}

			.iflynepal-gallery__item img {
				display: block;
				width: 100%;
				height: 72px;
				border: 1px solid #dcdcde;
				border-radius: 3px;
				object-fit: cover;
			}

			.iflynepal-gallery__remove {
				position: absolute;
				top: 2px;
				right: 2px;
				width: 20px;
				height: 20px;
				padding: 0;
				border: 0;
				border-radius: 50%;
				background: rgba(0, 0, 0, 0.62);
				color: #fff;
				font-size: 14px;
				line-height: 20px;
				cursor: pointer;
			}

			.iflynepal-gallery__empty {
				margin: 0 0 10px;
				color: #646970;
			}
		</style>
		<?php
	}

	/**
	 * Saves the submitted list.
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

		$fields = iflynepal_package_fields_for_box( self::BOX );
		$field  = isset( $fields['gallery'] ) ? $fields['gallery'] : array();

		// Sanitized by iflynepal_package_sanitize_value() on the next line.
		$raw   = isset( $_POST[ self::ID ] ) ? wp_unslash( $_POST[ self::ID ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = iflynepal_package_sanitize_value( $raw, 'gallery', $field );

		if ( '' === $value ) {
			delete_post_meta( $post_id, iflynepal_package_meta_key( 'gallery' ) );

			return;
		}

		update_post_meta( $post_id, iflynepal_package_meta_key( 'gallery' ), $value );
	}
}

new IFly_Nepal_Package_Gallery_Box();
