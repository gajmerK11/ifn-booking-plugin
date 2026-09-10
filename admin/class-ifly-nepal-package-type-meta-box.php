<?php
/**
 * The Primary Package Type meta box.
 *
 * A package can be filed under more than one package type, but it has exactly
 * one URL. This box is where an editor says which of the types a package holds
 * is the one its URL is built from — the difference between
 * /retreat-nepal/ayurvedic-retreats/21-day-ayurvedic-detox and
 * /trek/everest-region/21-day-ayurvedic-detox for a package filed under both.
 *
 * Left on Automatic, the deepest type wins, which is the right answer for the
 * ordinary case of a package filed under "Retreat" and "Retreat > Ayurvedic
 * Retreats" together. The choice only has to be made by hand when a package
 * sits in two unrelated branches.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lets an editor designate the package type a package's permalink is built from.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Type_Meta_Box {

	/**
	 * Meta box ID, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_primary_package_type';

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
	 * Registers the box on the package editor screen.
	 *
	 * Sits in the sidebar directly under the Package Types checklist, because
	 * it is a follow-on question to the choices made there and reads as
	 * nonsense anywhere else.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Primary Package Type', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * A term's full name path, for display.
	 *
	 * "Retreat › Ayurvedic Retreats" rather than "Ayurvedic Retreats", so two
	 * categories with the same name under different types are told apart.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Term.
	 * @return string Name path.
	 */
	private function term_label( $term ) {
		$names = array( $term->name );

		foreach ( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, IFLYNEPAL_PACKAGE_TAXONOMY );

			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				array_unshift( $names, $ancestor->name );
			}
		}

		return implode( ' › ', $names );
	}

	/**
	 * Draws the chooser.
	 *
	 * The options are the types the package is currently saved under, not every
	 * type that exists: designating a type the package is not in would produce a
	 * URL whose category segment does not contain the package. Types ticked in
	 * the checklist but not yet saved are not offered until the package is saved
	 * once, which is also when the permalink they would change is recalculated.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		$terms = get_the_terms( $post->ID, IFLYNEPAL_PACKAGE_TAXONOMY );

		if ( ! $terms || is_wp_error( $terms ) ) {
			echo '<p>' . esc_html__( 'Choose one or more package types, then update the package. The type its URL is built from can be set here afterwards.', 'iflynepal' ) . '</p>';

			return;
		}

		$current = (int) get_post_meta( $post->ID, IFLYNEPAL_PACKAGE_PRIMARY_TYPE_META, true );
		$auto    = iflynepal_package_primary_type( $post->ID );
		?>
		<p>
			<label>
				<input type="radio" name="<?php echo esc_attr( self::ID ); ?>" value="0" <?php checked( 0, $current ); ?>>
				<?php
				printf(
					/* translators: %s: the package type chosen automatically, as a name path. */
					esc_html__( 'Automatic — %s', 'iflynepal' ),
					'<strong>' . esc_html( $auto ? $this->term_label( $auto ) : '' ) . '</strong>'
				);
				?>
			</label>
		</p>
		<?php foreach ( $terms as $term ) : ?>
			<p>
				<label>
					<input type="radio" name="<?php echo esc_attr( self::ID ); ?>" value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php checked( (int) $term->term_id, $current ); ?>>
					<?php echo esc_html( $this->term_label( $term ) ); ?>
				</label>
			</p>
		<?php endforeach; ?>
		<p class="description">
			<?php esc_html_e( 'Decides the category path in this package’s URL. Automatic uses the most specific type the package is filed under.', 'iflynepal' ); ?>
		</p>
		<?php
	}

	/**
	 * Stores the choice.
	 *
	 * Runs on save_post, which fires after WordPress has written the package's
	 * terms, so the submitted term can be checked against what the package is
	 * actually filed under rather than against what it held before this save.
	 *
	 * A choice that no longer holds — the term was unticked in the same save, or
	 * deleted — is discarded rather than stored, so the meta never names a term
	 * the permalink cannot use.
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

		$chosen = isset( $_POST[ self::ID ] ) ? absint( wp_unslash( $_POST[ self::ID ] ) ) : 0;

		if ( $chosen && ! has_term( $chosen, IFLYNEPAL_PACKAGE_TAXONOMY, $post_id ) ) {
			$chosen = 0;
		}

		if ( $chosen ) {
			update_post_meta( $post_id, IFLYNEPAL_PACKAGE_PRIMARY_TYPE_META, $chosen );

			return;
		}

		delete_post_meta( $post_id, IFLYNEPAL_PACKAGE_PRIMARY_TYPE_META );
	}
}

new IFly_Nepal_Package_Type_Meta_Box();
