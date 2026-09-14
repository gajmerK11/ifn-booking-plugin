<?php
/**
 * The package's Homepage box.
 *
 * Three fields, in the sidebar, above the Package Types taxonomy box: a tick
 * and a month for the "Upcoming journeys" rail, and a separate tick for the
 * "A few good reasons" card grid. Both sections are opt-in per package — see
 * includes/package/package-meta.php for why neither defaults to "every
 * published package".
 *
 * Registered at priority 'high' in the 'side' context, which is what puts it
 * above the taxonomy box: WordPress renders 'side' boxes high, then core,
 * then default, then low, and the Package Types box is core's own — the
 * standard priority for a hierarchical taxonomy's meta box.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Puts a package on the homepage — the departures rail, the reasons grid, or both.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Homepage_Box {

	/**
	 * Meta box ID, the form field prefix, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_homepage';

	/**
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'homepage';

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
	 * Registers the box above the Package Types taxonomy box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Homepage', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Draws the two ticks and the month field.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$show_homepage = '1' === iflynepal_package_field( $post->ID, 'show_homepage' );
		$show_reasons  = '1' === iflynepal_package_field( $post->ID, 'show_reasons' );
		$month         = iflynepal_package_field( $post->ID, 'available_month' );
		$fields        = iflynepal_package_fields_for_box( self::BOX );
		?>
		<p class="iflynepal-homepage-group">Upcoming journeys</p>

		<p class="iflynepal-homepage-field">
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( self::ID . '[show_homepage]' ); ?>"
					value="1"
					<?php checked( $show_homepage ); ?>
				/>
				<?php echo esc_html( $fields['show_homepage']['label'] ); ?>
			</label>
			<?php if ( ! empty( $fields['show_homepage']['help'] ) ) : ?>
				<span class="description"><?php echo esc_html( $fields['show_homepage']['help'] ); ?></span>
			<?php endif; ?>
		</p>

		<p class="iflynepal-homepage-field">
			<label for="<?php echo esc_attr( self::ID . '_available_month' ); ?>">
				<?php echo esc_html( $fields['available_month']['label'] ); ?>
			</label>
			<input
				type="month"
				id="<?php echo esc_attr( self::ID . '_available_month' ); ?>"
				name="<?php echo esc_attr( self::ID . '[available_month]' ); ?>"
				value="<?php echo esc_attr( $month ); ?>"
			/>
			<?php if ( ! empty( $fields['available_month']['help'] ) ) : ?>
				<span class="description"><?php echo esc_html( $fields['available_month']['help'] ); ?></span>
			<?php endif; ?>
		</p>

		<p class="iflynepal-homepage-group">A few good reasons</p>

		<p class="iflynepal-homepage-field">
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( self::ID . '[show_reasons]' ); ?>"
					value="1"
					<?php checked( $show_reasons ); ?>
				/>
				<?php echo esc_html( $fields['show_reasons']['label'] ); ?>
			</label>
			<?php if ( ! empty( $fields['show_reasons']['help'] ) ) : ?>
				<span class="description"><?php echo esc_html( $fields['show_reasons']['help'] ); ?></span>
			<?php endif; ?>
		</p>

		<style>
			.iflynepal-homepage-group {
				margin: 0 0 8px;
				color: #1d2327;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.04em;
			}

			.iflynepal-homepage-group:not(:first-child) {
				margin-top: 16px;
				padding-top: 14px;
				border-top: 1px solid #dcdcde;
			}

			.iflynepal-homepage-field {
				margin: 0 0 12px;
			}

			.iflynepal-homepage-field input[type="month"] {
				display: block;
				width: 100%;
				margin-top: 4px;
			}

			.iflynepal-homepage-field .description {
				display: block;
				margin-top: 4px;
				color: #646970;
				font-size: 12px;
			}
		</style>
		<?php
	}

	/**
	 * Saves both ticks and the month.
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
			$submitted = array();
		}

		foreach ( iflynepal_package_fields_for_box( self::BOX ) as $key => $field ) {
			/*
			 * An unticked checkbox is not submitted at all — that absence is
			 * read as '', which iflynepal_package_sanitize_value() turns into
			 * "unchecked" for a checkbox field, the correct outcome rather
			 * than a bug to guard against.
			 */
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

new IFly_Nepal_Package_Homepage_Box();
