<?php
/**
 * The enquiry form.
 *
 * Rendered in the footer of every catalogue template and opened by any control
 * carrying data-iflynepal-enquiry-open — the "Inquire now" button on a package,
 * for one.
 *
 * 🔴 It is a real section of the page, not a hidden dialog, and that is the
 * whole design. Without JavaScript the form is simply there at the end of the
 * page and the trigger is an anchor that jumps to it, so a visitor who cannot
 * run the script can still send an enquiry — which is not true of a control that
 * is `hidden` until a script unhides it. The modal is the enhancement: an inline
 * head script adds `iflynepal-enquiry-js` to the document element, the
 * stylesheet hides the section only under that class, and enquiry.js turns it
 * into a dialog. The class is added in the head rather than on load so the form
 * is never painted at the foot of the page and then snatched away.
 *
 * Fields, their types and which are required all come from
 * iflynepal_enquiry_fields(), the same list the handler validates against, so a
 * field cannot exist on one side and not the other.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'package_id'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_package_id = isset( $args['package_id'] ) ? (int) $args['package_id'] : 0;
$iflynepal_package    = $iflynepal_package_id ? get_the_title( $iflynepal_package_id ) : '';
$iflynepal_notice     = iflynepal_enquiry_notice();
$iflynepal_fields     = iflynepal_enquiry_fields();
?>

<section class="iflynepal-enquiry" id="iflynepal-enquiry" aria-labelledby="iflynepal-enquiry-title" data-iflynepal-enquiry<?php echo $iflynepal_notice ? ' data-iflynepal-enquiry-open-now' : ''; ?>>
	<div class="iflynepal-enquiry__backdrop" data-iflynepal-enquiry-close hidden></div>

	<?php
	/*
	 * tabindex="-1" so the panel itself can take focus when the form opens. It
	 * is the panel that is focused rather than the first field, which would pop
	 * a keyboard open on a phone before the visitor has read what they are
	 * looking at, or the close button, which announces "Close" as the first
	 * thing a screen reader hears. Focusing the labelled dialog announces its
	 * title.
	 */
	?>
	<div class="iflynepal-enquiry__panel" role="dialog" aria-modal="true" aria-labelledby="iflynepal-enquiry-title" tabindex="-1">
		<button class="iflynepal-enquiry__close" type="button" data-iflynepal-enquiry-close hidden>
			<span class="screen-reader-text"><?php esc_html_e( 'Close the enquiry form', 'iflynepal' ); ?></span>
			<svg class="iflynepal-enquiry__x" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M6.5 6.5l11 11M17.5 6.5l-11 11" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
			</svg>
		</button>

		<header class="iflynepal-enquiry__head">
			<p class="iflynepal-enquiry__eyebrow"><?php esc_html_e( 'No payment needed to enquire', 'iflynepal' ); ?></p>
			<h2 class="iflynepal-enquiry__title" id="iflynepal-enquiry-title"><?php esc_html_e( 'Ask us about this journey', 'iflynepal' ); ?></h2>
			<p class="iflynepal-enquiry__lead">
				<?php esc_html_e( 'Send us a note and we will reply by email or WhatsApp, usually within a day.', 'iflynepal' ); ?>
			</p>

			<?php if ( '' !== $iflynepal_package ) : ?>
				<p class="iflynepal-enquiry__package">
					<span><?php esc_html_e( 'About', 'iflynepal' ); ?></span>
					<strong><?php echo esc_html( $iflynepal_package ); ?></strong>
				</p>
			<?php endif; ?>
		</header>

		<?php if ( $iflynepal_notice ) : ?>
			<?php
			/*
			 * tabindex="-1" so the script can move focus here when the form is
			 * reopened after a redirect: a visitor who has just submitted needs
			 * to be told what happened, and a notice nobody is focused on is a
			 * notice a screen reader never reads.
			 */
			?>
			<p class="iflynepal-enquiry__notice iflynepal-enquiry__notice--<?php echo esc_attr( $iflynepal_notice['type'] ); ?>"
				id="iflynepal-enquiry-notice"
				role="<?php echo 'error' === $iflynepal_notice['type'] ? 'alert' : 'status'; ?>"
				tabindex="-1">
				<?php echo esc_html( $iflynepal_notice['message'] ); ?>
			</p>
		<?php endif; ?>

		<form class="iflynepal-enquiry__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( IFLYNEPAL_ENQUIRY_ACTION ); ?>">
			<input type="hidden" name="package_id" value="<?php echo esc_attr( (string) $iflynepal_package_id ); ?>">
			<?php wp_nonce_field( IFLYNEPAL_ENQUIRY_ACTION, 'iflynepal_enquiry_nonce' ); ?>

			<?php
			/*
			 * The honeypot. Hidden from sight by the stylesheet and from the
			 * accessibility tree and the tab order by the attributes, so nobody
			 * using the form ever meets it and a script filling every input
			 * does. Same field name as the theme's contact form.
			 */
			?>
			<p class="iflynepal-enquiry__hp" aria-hidden="true">
				<label for="iflynepal-enquiry-website"><?php esc_html_e( 'Leave this field empty', 'iflynepal' ); ?></label>
				<input type="text" id="iflynepal-enquiry-website" name="website" tabindex="-1" autocomplete="off" value="">
			</p>

			<?php foreach ( $iflynepal_fields as $iflynepal_key => $iflynepal_field ) : ?>
				<?php
				$iflynepal_type     = isset( $iflynepal_field['type'] ) ? $iflynepal_field['type'] : 'text';
				$iflynepal_id       = 'iflynepal-enquiry-' . sanitize_key( $iflynepal_key );
				$iflynepal_required = ! empty( $iflynepal_field['required'] );
				$iflynepal_help     = isset( $iflynepal_field['help'] ) ? $iflynepal_field['help'] : '';
				$iflynepal_auto     = isset( $iflynepal_field['autocomplete'] ) ? $iflynepal_field['autocomplete'] : '';
				?>
				<p class="iflynepal-enquiry__field iflynepal-enquiry__field--<?php echo esc_attr( $iflynepal_type ); ?>">
					<label for="<?php echo esc_attr( $iflynepal_id ); ?>">
						<?php echo esc_html( $iflynepal_field['label'] ); ?>
						<?php if ( $iflynepal_required ) : ?>
							<span class="iflynepal-enquiry__req" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>

					<?php if ( 'textarea' === $iflynepal_type ) : ?>
						<textarea
							id="<?php echo esc_attr( $iflynepal_id ); ?>"
							name="<?php echo esc_attr( $iflynepal_key ); ?>"
							rows="5"
							<?php echo $iflynepal_required ? 'required' : ''; ?>
							<?php echo '' !== $iflynepal_help ? 'aria-describedby="' . esc_attr( $iflynepal_id ) . '-help"' : ''; ?>
						></textarea>
					<?php else : ?>
						<input
							type="<?php echo esc_attr( 'email' === $iflynepal_type ? 'email' : 'text' ); ?>"
							id="<?php echo esc_attr( $iflynepal_id ); ?>"
							name="<?php echo esc_attr( $iflynepal_key ); ?>"
							<?php echo '' !== $iflynepal_auto ? 'autocomplete="' . esc_attr( $iflynepal_auto ) . '"' : ''; ?>
							<?php echo $iflynepal_required ? 'required' : ''; ?>
							<?php echo '' !== $iflynepal_help ? 'aria-describedby="' . esc_attr( $iflynepal_id ) . '-help"' : ''; ?>
							value="">
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_help ) : ?>
						<span class="iflynepal-enquiry__help" id="<?php echo esc_attr( $iflynepal_id ); ?>-help"><?php echo esc_html( $iflynepal_help ); ?></span>
					<?php endif; ?>
				</p>
			<?php endforeach; ?>

			<p class="iflynepal-enquiry__actions">
				<button class="iflynepal-enquiry__submit" type="submit">
					<?php esc_html_e( 'Send enquiry', 'iflynepal' ); ?>
					<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns finished, escaped markup. ?>
				</button>
			</p>

			<?php
			/*
			 * The client asked for both ways of reaching them, so the form
			 * offers the other one rather than making a visitor close it and go
			 * looking. Nothing is shown when no number is configured at
			 * Packages > Settings.
			 */
			$iflynepal_chat = iflynepal_whatsapp_url( $iflynepal_package_id );
			?>

			<?php if ( '' !== $iflynepal_chat ) : ?>
				<p class="iflynepal-enquiry__alt">
					<?php esc_html_e( 'Prefer to chat?', 'iflynepal' ); ?>
				</p>
				<a class="iflynepal-enquiry__whatsapp" <?php echo iflynepal_booking_anchor_attr( $iflynepal_chat ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?> target="_blank" rel="noopener noreferrer">
					<svg class="iflynepal-enquiry__whatsapp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8a9.1 9.1 0 0 0-7.9 13.7L2.8 21.2l4.8-1.3A9.1 9.1 0 1 0 12 2.8zm0 16.6c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A7.5 7.5 0 1 1 12 19.4zm4.1-5.6c-.2-.1-1.3-.7-1.6-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 2 .8 2.4.6 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.5-.3z"/></svg>
					<?php esc_html_e( 'Message us on WhatsApp', 'iflynepal' ); ?>
				</a>
			<?php endif; ?>

			<p class="iflynepal-enquiry__small">
				<?php esc_html_e( 'We use your details to answer your enquiry and nothing else.', 'iflynepal' ); ?>
			</p>
		</form>
	</div>
</section>
