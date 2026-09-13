<?php
/**
 * The Enquiry meta box — what the visitor sent, as a plain form view.
 *
 * Every value is printed read-only, and deliberately not as a control. The
 * earlier version drew each value in a bordered grey box, which reads as a
 * disabled input: it invites a click, it says "this ought to be editable", and
 * it fills the screen with furniture for a record nobody can change. A label
 * beside its value in core's own form table says the same thing in half the
 * space and never pretends to be a field.
 *
 * An enquiry is a record of a conversation, not a draft — editing what somebody
 * wrote and then answering it is a way of losing the thing this screen exists to
 * hold. The only decision on the screen is the follow-up status, which is the
 * office's own note rather than the visitor's, and it has a box of its own in the
 * sidebar: see class-ifly-nepal-enquiry-status-box.php.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shows one enquiry, read-only.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Enquiry_Details_Box {

	/**
	 * Meta box ID.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_enquiry_details';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	/**
	 * Registers the box as the main column of the enquiry screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Enquiry', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_ENQUIRY_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Prints the enquiry.
	 *
	 * `form-table` is core's own two-column layout — the one the Settings
	 * screens use — so the rows line up with the rest of the admin and inherit
	 * its responsive collapse at narrow widths with nothing to maintain here.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The enquiry being viewed.
	 * @return void
	 */
	public function render( $post ) {
		$package_id = iflynepal_enquiry_package_id( $post->ID );
		$source     = iflynepal_enquiry_field( $post->ID, 'source' );
		$email      = iflynepal_enquiry_field( $post->ID, 'email' );
		?>
		<style>
			/*
			 * Only what core's form table does not already give: the message
			 * keeps the line breaks the visitor typed, and the rows are ruled so
			 * a long message and the row under it do not run together.
			 */
			.iflynepal-enquiry-view th { width: 140px; padding: 14px 10px 14px 0; }
			.iflynepal-enquiry-view td { padding: 14px 0; }
			.iflynepal-enquiry-view tr + tr th,
			.iflynepal-enquiry-view tr + tr td { border-top: 1px solid #f0f0f1; }
			.iflynepal-enquiry-view .iflynepal-enquiry-message { margin: 0; white-space: pre-wrap; line-height: 1.7; }
			.iflynepal-enquiry-view .iflynepal-enquiry-muted { color: #646970; }
		</style>

		<table class="form-table iflynepal-enquiry-view" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Name', 'iflynepal' ); ?></th>
					<td><?php echo esc_html( iflynepal_enquiry_field( $post->ID, 'name' ) ); ?></td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Email', 'iflynepal' ); ?></th>
					<td>
						<?php if ( is_email( $email ) ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
						<?php else : ?>
							<span class="iflynepal-enquiry-muted">&mdash;</span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Package', 'iflynepal' ); ?></th>
					<td>
						<?php if ( $package_id ) : ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $package_id ) ); ?>"><?php echo esc_html( get_the_title( $package_id ) ); ?></a>
						<?php else : ?>
							<span class="iflynepal-enquiry-muted"><?php esc_html_e( 'No package — sent from the catalogue', 'iflynepal' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Message', 'iflynepal' ); ?></th>
					<td><p class="iflynepal-enquiry-message"><?php echo esc_html( iflynepal_enquiry_field( $post->ID, 'message' ) ); ?></p></td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Received', 'iflynepal' ); ?></th>
					<td>
						<?php
						echo esc_html(
							get_post_time(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								false,
								$post,
								true
							)
						);
						?>
					</td>
				</tr>

				<?php if ( '' !== $source ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sent from', 'iflynepal' ); ?></th>
						<td><a href="<?php echo esc_url( $source ); ?>"><?php echo esc_html( $source ); ?></a></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}

new IFly_Nepal_Enquiry_Details_Box();
