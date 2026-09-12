<?php
/**
 * How the package editor's meta boxes start out.
 *
 * Every box on the screen is closed the first time an editor opens a package,
 * so the screen is a short list of headings rather than a page and a half of
 * controls. A box opens when its own toggle is pressed.
 *
 * The word doing the work is FIRST. WordPress already remembers which boxes a
 * user has left closed, per user and per post type, in the `closedpostboxes_*`
 * user option — and that memory is the user's, not the plugin's. So this does
 * not force the class on: it supplies the DEFAULT for that option, and only
 * while the user has no stored preference at all. The moment somebody opens or
 * closes a box, WordPress writes their list and this stops applying, which is
 * what keeps an editor from having to re-open the same box on every package.
 *
 * That is also why this hooks the option rather than `postbox_classes_*`: a
 * filter that adds `closed` to the classes runs on every render and would win
 * over the user's own choice for ever.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Starts every package meta box collapsed until an editor says otherwise.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Box_State {

	/**
	 * Boxes that are never closed by default.
	 *
	 * Publish holds the Update button. A closed Publish box is a screen with no
	 * way to save what has just been typed into it, which is not a tidier screen
	 * but a broken one.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const ALWAYS_OPEN = array( 'submitdiv' );

	/**
	 * Hooks the default into the option WordPress reads.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter(
			'get_user_option_closedpostboxes_' . IFLYNEPAL_PACKAGE_POST_TYPE,
			array( $this, 'closed_by_default' )
		);
	}

	/**
	 * Which boxes are closed when the user has never said.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $closed Stored list of closed box ids, or false when unset.
	 * @return mixed Stored list untouched, or every box on the screen.
	 */
	public function closed_by_default( $closed ) {
		/*
		 * An array — even an empty one — is a user who has toggled something and
		 * whose preference is now on record. `false` is a user who never has.
		 */
		if ( is_array( $closed ) ) {
			return $closed;
		}

		$ids = $this->box_ids();

		return empty( $ids ) ? $closed : $ids;
	}

	/**
	 * Every meta box registered on the package screen, bar the ones that must stay open.
	 *
	 * Read from the global rather than from a list kept here: core's own boxes,
	 * the taxonomy boxes and anything a future plugin adds are all on this
	 * screen too, and a hand-written list would quietly stop covering them.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Meta box ids.
	 */
	private function box_ids() {
		global $wp_meta_boxes;

		$screen = IFLYNEPAL_PACKAGE_POST_TYPE;

		if ( empty( $wp_meta_boxes[ $screen ] ) || ! is_array( $wp_meta_boxes[ $screen ] ) ) {
			return array();
		}

		$ids = array();

		foreach ( $wp_meta_boxes[ $screen ] as $contexts ) {
			if ( ! is_array( $contexts ) ) {
				continue;
			}

			foreach ( $contexts as $boxes ) {
				if ( ! is_array( $boxes ) ) {
					continue;
				}

				foreach ( $boxes as $box_id => $box ) {
					// A removed box is stored as false and is not on the screen at all.
					if ( ! $box || in_array( $box_id, self::ALWAYS_OPEN, true ) ) {
						continue;
					}

					$ids[] = $box_id;
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}
}

new IFly_Nepal_Package_Box_State();
