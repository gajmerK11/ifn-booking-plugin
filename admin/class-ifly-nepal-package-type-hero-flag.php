<?php
/**
 * The "Show in hero search" tick on a package type.
 *
 * The homepage hero's Activities picker used to list every top-level package
 * type. The client wants only some of them — the retreat kinds — so each type
 * now carries a tick, and the picker lists the ticked ones. Nothing ticked
 * means the old behaviour (every top-level type), so the picker is never empty
 * by accident. The reading side is iflynepal_trip_finder_types() in
 * includes/frontend/homepage-trip-finder.php.
 *
 * The tick is on both the Add and the Edit screen, and shown as a column on the
 * list so the current choice can be read at a glance. It is a small field of its
 * own rather than one more entry in the archive content model: that model is
 * collapsed, sits on the Edit screen only, and is scoped to the sections a term
 * can render — none of which describes a hero setting.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders and stores the hero-search tick.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Type_Hero_Flag {

	/**
	 * Nonce action and field name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE = 'iflynepal_hero_flag';

	/**
	 * Form field name for the checkbox.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const FIELD = 'iflynepal_show_in_hero_search';

	/**
	 * Hooks the field into the term screens and the list table.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$taxonomy = IFLYNEPAL_PACKAGE_TAXONOMY;

		add_action( $taxonomy . '_add_form_fields', array( $this, 'render_add' ) );
		add_action( $taxonomy . '_edit_form_fields', array( $this, 'render_edit' ), 5, 1 );
		add_action( 'created_' . $taxonomy, array( $this, 'save' ), 10, 1 );
		add_action( 'edited_' . $taxonomy, array( $this, 'save' ), 10, 1 );

		add_filter( 'manage_edit-' . $taxonomy . '_columns', array( $this, 'columns' ) );
		add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'column_content' ), 10, 3 );

		add_action( 'wp_ajax_iflynepal_toggle_hero_flag', array( $this, 'ajax_toggle' ) );
		add_action( 'admin_print_footer_scripts-edit-tags.php', array( $this, 'print_script' ) );
	}

	/**
	 * Sets or clears the flag on a term and on its translations.
	 *
	 * Translations are kept in step so the list shows what the hero does: the
	 * hero reads a term as ticked when any of its translations is, so a flag
	 * cleared on one language alone would silently stay on in the other.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $term_id Term ID.
	 * @param bool $on      Whether the term is shown in the hero search.
	 * @return void
	 */
	private function set_flag( $term_id, $on ) {
		$ids = array( (int) $term_id );

		if ( function_exists( 'pll_get_term_translations' ) ) {
			$ids = array_unique( array_merge( $ids, array_map( 'intval', array_values( (array) pll_get_term_translations( $term_id ) ) ) ) );
		}

		foreach ( $ids as $id ) {
			if ( $on ) {
				update_term_meta( $id, IFLYNEPAL_TRIP_FINDER_TERM_META, '1' );
			} else {
				delete_term_meta( $id, IFLYNEPAL_TRIP_FINDER_TERM_META );
			}
		}
	}

	/**
	 * Saves a column checkbox the moment it is ticked.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_toggle() {
		check_ajax_referer( self::NONCE . '_toggle', 'nonce' );

		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;

		if ( ! $term_id || ! current_user_can( 'edit_term', $term_id ) ) {
			wp_send_json_error( null, 403 );
		}

		$term = get_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY );

		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( null, 404 );
		}

		$this->set_flag( $term_id, ! empty( $_POST['checked'] ) && '1' === $_POST['checked'] );

		wp_send_json_success();
	}

	/**
	 * The script behind the column checkboxes, on the Package Types list only.
	 *
	 * Delegated from the document so rows WordPress adds afterwards (Add New,
	 * Quick Edit, paging) work without re-binding.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function print_script() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-' . IFLYNEPAL_PACKAGE_TAXONOMY !== $screen->id ) {
			return;
		}
		?>
		<script>
		( function () {
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( self::NONCE . '_toggle' ) ); ?>;
			var failed = <?php echo wp_json_encode( __( 'Could not save. Please try again.', 'iflynepal' ) ); ?>;

			document.addEventListener( 'change', function ( event ) {
				var box = event.target;

				if ( ! box.classList || ! box.classList.contains( 'iflynepal-hero-flag' ) ) {
					return;
				}

				var want = box.checked;
				var body = new URLSearchParams( {
					action: 'iflynepal_toggle_hero_flag',
					nonce: nonce,
					term_id: box.getAttribute( 'data-term-id' ),
					checked: want ? '1' : '0'
				} );

				box.disabled = true;

				fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
					.then( function ( response ) { return response.json(); } )
					.then( function ( result ) {
						if ( ! result || ! result.success ) {
							throw new Error( 'save failed' );
						}
					} )
					.catch( function () {
						box.checked = ! want;
						window.alert( failed );
					} )
					.then( function () {
						box.disabled = false;
					} );
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * The checkbox and its explanation, shared by both screens.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $checked Whether the box starts ticked.
	 * @return void
	 */
	private function control( $checked ) {
		// No referer field: the term form already carries one, and a second would shadow it.
		wp_nonce_field( self::NONCE . '_save', self::NONCE . '_nonce', false );
		?>
		<label for="<?php echo esc_attr( self::FIELD ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( self::FIELD ); ?>" name="<?php echo esc_attr( self::FIELD ); ?>" value="1"<?php checked( $checked ); ?>>
			<?php esc_html_e( 'Show this in the hero section search', 'iflynepal' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Ticked types are listed under "Activities" in the homepage hero search. If none is ticked, every top-level type is listed.', 'iflynepal' ); ?>
		</p>
		<?php
	}

	/**
	 * The Add Package Type form.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_add() {
		?>
		<div class="form-field">
			<?php $this->control( false ); ?>
		</div>
		<?php
	}

	/**
	 * The Edit Package Type form.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Term being edited.
	 * @return void
	 */
	public function render_edit( $term ) {
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Hero section search', 'iflynepal' ); ?></th>
			<td><?php $this->control( iflynepal_trip_finder_term_is_flagged( $term->term_id ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Stores the tick.
	 *
	 * The nonce is not only a permission check: created_/edited_ also fire for
	 * quick edit and for any code calling wp_update_term(), none of which submit
	 * this field. Without a submitted form an absent checkbox would read as
	 * "unticked" and clear the flag on every such save.
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

		$this->set_flag( $term_id, isset( $_POST[ self::FIELD ] ) );
	}

	/**
	 * Adds the "Hero search" column, just before the post count.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $columns Existing columns.
	 * @return string[]
	 */
	public function columns( $columns ) {
		$out = array();

		foreach ( $columns as $key => $label ) {
			if ( 'posts' === $key ) {
				$out['iflynepal_hero_search'] = esc_html__( 'Hero search', 'iflynepal' );
			}

			$out[ $key ] = $label;
		}

		if ( ! isset( $out['iflynepal_hero_search'] ) ) {
			$out['iflynepal_hero_search'] = esc_html__( 'Hero search', 'iflynepal' );
		}

		return $out;
	}

	/**
	 * Fills the "Hero search" column with a checkbox that saves on change.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content     Current content.
	 * @param string $column_name Column key.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function column_content( $content, $column_name, $term_id ) {
		if ( 'iflynepal_hero_search' !== $column_name ) {
			return $content;
		}

		if ( ! current_user_can( 'edit_term', (int) $term_id ) ) {
			return iflynepal_trip_finder_term_is_flagged( $term_id ) ? '&#10003;' : '&mdash;';
		}

		return sprintf(
			'<input type="checkbox" class="iflynepal-hero-flag" data-term-id="%1$d" aria-label="%2$s"%3$s>',
			(int) $term_id,
			esc_attr__( 'Show in hero search', 'iflynepal' ),
			checked( iflynepal_trip_finder_term_is_flagged( $term_id ), true, false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() returns a fixed attribute string.
		);
	}
}

new IFly_Nepal_Package_Type_Hero_Flag();
