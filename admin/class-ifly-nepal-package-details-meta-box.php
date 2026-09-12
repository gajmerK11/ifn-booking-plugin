<?php
/**
 * The Package Card meta box.
 *
 * Renders and saves the `card` fields declared in
 * includes/package/package-meta.php — the card label, the duration, the place
 * or suitability note, the price and the hover summary. Those are the facts the
 * card in the catalogue is built from, which is what the box is named for.
 *
 * The schema holds four more fields — the highlights, the confirmation notice,
 * the fixed departure dates and the booking shortcode — and this box does not
 * draw them. They are stored and the single-package template still renders
 * them; they simply have no editing surface until it is decided where they
 * belong. Both the rendering and the save are scoped to the same list, because
 * a save that walked the whole schema would read the four it did not draw as
 * emptied and delete them on the first Update.
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
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'card';

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
			__( 'Package Card', 'iflynepal' ),
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

		echo '<div class="iflynepal-package-fields">';
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( iflynepal_package_fields_for_box( self::BOX ) as $key => $field ) {
			$id    = self::ID . '_' . $key;
			$value = iflynepal_package_field( $post->ID, $key );

			/*
			 * The type travels onto the row, so which fields claim the whole
			 * row is decided by the schema rather than by a list of keys kept
			 * in the stylesheet — the same rule the term screen follows.
			 */
			printf(
				'<tr class="iflynepal-package-field iflynepal-package-field--%s">',
				esc_attr( $field['type'] )
			);
			printf(
				'<th scope="row"><label for="%1$s">%2$s</label></th>',
				esc_attr( $id ),
				esc_html( $field['label'] )
			);
			echo '<td>';

			if ( 'text' === $field['type'] ) {
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( self::ID . '[' . $key . ']' ),
					esc_attr( $value )
				);
			} else {
				printf(
					'<textarea rows="%1$d" id="%2$s" name="%3$s">%4$s</textarea>',
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
		echo '</div>';

		$this->print_styles();
	}

	/**
	 * The box's own presentation.
	 *
	 * The label sits on its own line above a full-width control rather than in
	 * core's narrow left-hand column, and the panel is a responsive grid so the
	 * short facts pair up instead of running down a single column of half-empty
	 * inputs. Both are the CloudColleague meta-box pattern, and both match the
	 * Edit Package Type screen — the two admin surfaces of this plugin should
	 * read as the same screen.
	 *
	 * Printed inline from the render callback, which only ever runs on this post
	 * type's editor. It is scoped to `.iflynepal-package-fields` all the same:
	 * an unscoped `.form-table` rule would restyle Publish, Excerpt and every
	 * other box on the screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function print_styles() {
		?>
		<style>
			/*
			 * Direct-child selectors throughout. A descendant selector would
			 * reach into any table nested inside a field later and block-ify it
			 * — which is exactly how the comparison grid on the term screen was
			 * flattened into a single column of inputs once already.
			 */
			.iflynepal-package-fields > .form-table,
			.iflynepal-package-fields > .form-table > tbody,
			.iflynepal-package-fields > .form-table > tbody > tr,
			.iflynepal-package-fields > .form-table > tbody > tr > th,
			.iflynepal-package-fields > .form-table > tbody > tr > td {
				display: block;
				width: auto;
			}

			.iflynepal-package-fields > .form-table {
				margin: 0;
			}

			/*
			 * auto-fill, so the column count follows the width the box actually
			 * has — the editor is narrower with the sidebar open than without,
			 * and narrower again on a laptop.
			 */
			.iflynepal-package-fields > .form-table > tbody {
				display: grid;
				gap: 22px 32px;
				grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr));
			}

			.iflynepal-package-fields > .form-table > tbody > tr {
				margin: 0;
			}

			.iflynepal-package-fields > .form-table > tbody > tr > th {
				padding: 0 0 8px;
				color: #1d2327;
				font-weight: 600;
				text-align: left;
			}

			.iflynepal-package-fields > .form-table > tbody > tr > td {
				padding: 0;
			}

			/* Prose runs the full width; the short facts pair up. */
			.iflynepal-package-field--textarea,
			.iflynepal-package-field--lines,
			.iflynepal-package-field--dates {
				grid-column: 1 / -1;
			}

			.iflynepal-package-fields .form-table input[type="text"],
			.iflynepal-package-fields .form-table textarea {
				width: 100%;
				padding: 10px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				box-sizing: border-box;
				line-height: 1.5;
			}

			.iflynepal-package-fields .form-table textarea {
				min-height: 110px;
			}

			.iflynepal-package-fields .form-table .description {
				margin: 6px 0 0;
			}
		</style>
		<?php
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

		foreach ( iflynepal_package_fields_for_box( self::BOX ) as $key => $field ) {
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
