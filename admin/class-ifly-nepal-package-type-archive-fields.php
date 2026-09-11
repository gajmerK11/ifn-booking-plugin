<?php
/**
 * The archive content fields on the Edit Package Type screen.
 *
 * WordPress has no add_meta_box() on a term screen — the term editor is a form,
 * not a meta box canvas — so the fields are hooked into the form itself with
 * {$taxonomy}_edit_form_fields and saved on edited_{$taxonomy}. That pair is the
 * term equivalent of add_meta_box() plus save_post, and it is what every plugin
 * that puts fields on a category screen uses.
 *
 * Fields are drawn on the Edit screen only, not the Add screen. A new type is
 * created by typing a name; filling in ten sections of marketing copy for a term
 * that does not exist yet is not how anybody works, and it would put a very long
 * form above the term list.
 *
 * The sections are collapsed by default, because the whole model is well over a
 * hundred fields and an editor is normally coming here to change one of them.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders and stores the package type archive content model.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Type_Archive_Fields {

	/**
	 * Nonce action and field base.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE = 'iflynepal_archive_fields';

	/**
	 * Hooks the fields into the term editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( IFLYNEPAL_PACKAGE_TAXONOMY . '_edit_form_fields', array( $this, 'render' ), 10, 1 );
		add_action( 'edited_' . IFLYNEPAL_PACKAGE_TAXONOMY, array( $this, 'save' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the media picker on the term editor screen only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( 'term.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || IFLYNEPAL_PACKAGE_TAXONOMY !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'iflynepal-archive-fields',
			IFLYNEPAL_BOOKING_URL . 'assets/js/admin/archive-fields.js',
			array(),
			iflynepal_booking_asset_version( 'assets/js/admin/archive-fields.js' ),
			true
		);

		wp_localize_script(
			'iflynepal-archive-fields',
			'iflynepalArchiveFields',
			array(
				'chooseTitle' => __( 'Choose image', 'iflynepal' ),
				'chooseUse'   => __( 'Use this image', 'iflynepal' ),
			)
		);
	}

	/**
	 * Draws every section as a collapsed group of fields.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Term being edited.
	 * @return void
	 */
	public function render( $term ) {
		wp_nonce_field( self::NONCE . '_save', self::NONCE . '_nonce' );

		$this->render_styles();
		?>
		<tr class="form-field">
			<td colspan="2" class="iflynepal-archive">
				<h2><?php esc_html_e( 'Archive page content', 'iflynepal' ); ?></h2>
				<p class="description">
					<?php
					printf(
						/* translators: %s: the archive URL this content appears on. */
						esc_html__( 'The copy that wraps the package cards on %s. Every field is optional — a section with nothing in it is left off the page.', 'iflynepal' ),
						'<code>' . esc_html( get_term_link( $term ) ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inline.
					);
					?>
				</p>

				<?php
				/*
				 * Only the sections this term's archive can actually render. A
				 * category page is the hero and the grid, so showing an editor a
				 * Booking plans box on one would be an invitation to write copy
				 * that never appears anywhere.
				 */
				foreach ( iflynepal_package_type_archive_sections_for_term( $term ) as $section_key => $section ) :
					?>
					<details class="iflynepal-archive__section">
						<summary><?php echo esc_html( $section['label'] ); ?></summary>
						<?php if ( '' !== $section['description'] ) : ?>
							<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
						<?php endif; ?>
						<table class="form-table" role="presentation">
							<tbody>
							<?php foreach ( $section['fields'] as $key => $field ) : ?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $this->input_id( $key ) ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
									</th>
									<td><?php $this->render_control( $term->term_id, $key, $field ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</details>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * The DOM id for a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Schema key.
	 * @return string DOM id.
	 */
	private function input_id( $key ) {
		return 'iflynepal-archive-' . str_replace( '_', '-', $key );
	}

	/**
	 * Draws one control, chosen by the field's type.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id Term being edited.
	 * @param string $key     Schema key.
	 * @param array  $field   Field definition.
	 * @return void
	 */
	private function render_control( $term_id, $key, $field ) {
		$id    = $this->input_id( $key );
		$name  = iflynepal_archive_meta_key( $key );
		$value = iflynepal_archive_field( $term_id, $key );

		switch ( $field['type'] ) {
			case 'image':
				$this->render_image_control( $id, $name, (int) $value );
				break;

			case 'textarea':
			case 'lines':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text">%4$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					'lines' === $field['type'] ? 5 : 3,
					esc_textarea( $value )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( '1', $value, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() returns a fixed attribute string.
					esc_html__( 'Yes', 'iflynepal' )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="large-text" inputmode="url">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_url( $value )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="large-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}

		if ( '' !== $field['help'] ) {
			echo '<p class="description">' . wp_kses(
				$field['help'],
				array(
					'em'     => array(),
					'strong' => array(),
					'code'   => array(),
				)
			) . '</p>';
		}
	}

	/**
	 * Draws the media picker.
	 *
	 * The attachment ID is what is stored, never a URL: a URL breaks the day the
	 * site moves domain, and it loses the alt text and the generated sizes that
	 * a responsive, dimensioned image needs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id            DOM id.
	 * @param string $name          Field name.
	 * @param int    $attachment_id Stored attachment.
	 * @return void
	 */
	private function render_image_control( $id, $name, $attachment_id ) {
		$image = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';
		?>
		<span class="iflynepal-archive__media" data-iflynepal-media>
			<span class="iflynepal-archive__preview" data-iflynepal-media-preview>
				<?php
				// Built by wp_get_attachment_image(), which escapes its own output.
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</span>
			<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-iflynepal-media-value>
			<button type="button" class="button" data-iflynepal-media-select><?php esc_html_e( 'Choose image', 'iflynepal' ); ?></button>
			<button type="button" class="button-link" data-iflynepal-media-remove<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'iflynepal' ); ?></button>
		</span>
		<?php
	}

	/**
	 * The handful of rules the fields need.
	 *
	 * Printed inline rather than shipped as a stylesheet, the same way the
	 * theme's meta boxes do it: a few declarations that apply on one screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_styles() {
		?>
		<style>
			.iflynepal-archive__section {
				margin: 0 0 8px;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				background: #fff;
			}

			.iflynepal-archive__section > summary {
				padding: 10px 12px;
				font-weight: 600;
				cursor: pointer;
			}

			.iflynepal-archive__section > .description,
			.iflynepal-archive__section > .form-table {
				padding: 0 12px 12px;
			}

			.iflynepal-archive__section .form-table th {
				width: 200px;
				padding: 10px 10px 10px 0;
				font-weight: 400;
			}

			.iflynepal-archive__media {
				display: flex;
				align-items: center;
				gap: 12px;
			}

			.iflynepal-archive__preview img {
				display: block;
				width: 64px;
				height: 64px;
				object-fit: cover;
				border-radius: 3px;
			}
		</style>
		<?php
	}

	/**
	 * Stores every field.
	 *
	 * The nonce check is not only a permission check here — it is what stops the
	 * whole model being wiped by any other code that legitimately calls
	 * wp_update_term(). edited_{$taxonomy} fires for those too, and without a
	 * submitted form there are no field values to read, so every field would be
	 * saved empty.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term being saved.
	 * @return void
	 */
	public function save( $term_id ) {
		$nonce_key = self::NONCE . '_nonce';

		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), self::NONCE . '_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		/*
		 * Scoped to the sections this term renders, matching what was drawn.
		 * It matters for the checkbox branch below, which reads an absent field
		 * as unticked rather than skipping it: walking the whole schema here
		 * would clear the plan highlight flags of any term that has just been
		 * re-parented into a category, on the save that re-parented it.
		 */
		foreach ( iflynepal_package_type_archive_fields_for_term( $term_id ) as $key => $field ) {
			$meta_key = iflynepal_archive_meta_key( $key );

			if ( 'checkbox' === $field['type'] ) {
				$value = isset( $_POST[ $meta_key ] ) ? '1' : '';
			} elseif ( isset( $_POST[ $meta_key ] ) ) {
				$raw   = wp_unslash( $_POST[ $meta_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line, by field type.
				$value = iflynepal_archive_sanitize_value( $raw, $field['type'] );
			} else {
				continue;
			}

			if ( '' === $value || '0' === $value ) {
				delete_term_meta( $term_id, $meta_key );

				continue;
			}

			update_term_meta( $term_id, $meta_key, $value );
		}
	}
}

new IFly_Nepal_Package_Type_Archive_Fields();
