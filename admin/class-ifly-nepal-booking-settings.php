<?php
/**
 * The plugin's settings screen — the WhatsApp number and the message it opens
 * a chat with.
 *
 * Sits under the Packages menu beside Enquiries rather than in Settings, where
 * nobody who edits the catalogue would look for it. Built on the Settings API,
 * so the nonce, the capability check, the save and the "Settings saved." notice
 * are core's rather than a second hand-rolled copy of them.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Draws and saves the plugin's settings.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Booking_Settings {

	/**
	 * The screen's slug, and the settings group it registers under.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PAGE = 'iflynepal-booking-settings';

	/**
	 * Hooks the screen in.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the range controls' behaviour, on this screen only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page's hook suffix.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}

		wp_enqueue_script(
			'iflynepal-settings-ranges',
			IFLYNEPAL_BOOKING_URL . 'assets/js/admin/settings-ranges.js',
			array(),
			iflynepal_booking_asset_version( 'assets/js/admin/settings-ranges.js' ),
			true
		);
	}

	/**
	 * The capability the screen is gated on.
	 *
	 * Taken from the post type rather than hard-coded, so it follows the same
	 * reasoning — and the same repair — as the menu it sits under. See the note
	 * in includes/cpt/package-cpt.php about the capabilities stripped from the
	 * roles on this install.
	 *
	 * @since 1.0.0
	 *
	 * @return string Capability name.
	 */
	private function capability() {
		$post_type = get_post_type_object( IFLYNEPAL_PACKAGE_POST_TYPE );

		return $post_type ? $post_type->cap->edit_posts : 'edit_pages';
	}

	/**
	 * Adds the screen under the Packages menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=' . IFLYNEPAL_PACKAGE_POST_TYPE,
			__( 'Enquiry & WhatsApp Settings', 'iflynepal' ),
			__( 'Settings', 'iflynepal' ),
			$this->capability(),
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Registers the option and its sanitizer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			self::PAGE,
			IFLYNEPAL_BOOKING_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Cleans the submitted form.
	 *
	 * Walks the schema rather than the submitted array, so a key the plugin does
	 * not declare can never be stored — and an empty field is stored as empty
	 * rather than skipped, or a number could never be cleared once set.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw submitted value.
	 * @return array<string,string> Sanitized option.
	 */
	public function sanitize( $value ) {
		$clean = array();

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$stored = get_option( IFLYNEPAL_BOOKING_OPTION, array() );

		foreach ( iflynepal_booking_settings_schema() as $key => $field ) {
			/*
			 * A 'page' field on a multilingual site submits one page ID per
			 * language rather than one string — see render() below — so it is
			 * sanitized as its own array rather than falling into the
			 * string-only handling every other field type shares.
			 */
			if ( 'page' === $field['type'] && iflynepal_booking_page_field_is_multilingual() ) {
				$clean[ $key ] = array();

				foreach ( pll_languages_list() as $lang ) {
					$submitted              = isset( $value[ $key ][ $lang ] ) && is_string( $value[ $key ][ $lang ] ) ? $value[ $key ][ $lang ] : '';
					$clean[ $key ][ $lang ] = iflynepal_booking_sanitize_setting( $submitted, $field['type'] );
				}

				continue;
			}

			/*
			 * A range list posts an array of rows, so the string-only guard
			 * below would read it as empty and wipe the list on every save.
			 */
			if ( iflynepal_booking_setting_is_list( $field['type'] ) ) {
				$clean[ $key ] = iflynepal_booking_sanitize_setting( isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array(), $field['type'] );
				continue;
			}

			$raw = isset( $value[ $key ] ) && is_string( $value[ $key ] ) ? $value[ $key ] : '';

			/*
			 * An API key field is never redrawn with its saved value (see
			 * render() below), so a blank submission here means "nothing
			 * typed", not "clear it" — the key this plugin already has stays
			 * put, and only an explicit tick of its own "Remove" checkbox
			 * clears it.
			 */
			if ( 'api_key' === $field['type'] ) {
				if ( ! empty( $value[ $key . '_clear' ] ) ) {
					$clean[ $key ] = '';
					continue;
				}

				if ( '' === trim( $raw ) ) {
					$clean[ $key ] = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
					continue;
				}
			}

			$clean[ $key ] = iflynepal_booking_sanitize_setting( $raw, $field['type'] );
		}

		return $clean;
	}

	/**
	 * Draws a list of range rows: the trip finder's length or budget options.
	 *
	 * The row markup is a `<template>`, so a saved row and a new one are the same
	 * markup and a template's inputs are never submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Base input name, e.g. option[trip_finder_budgets].
	 * @param array  $rows Stored rows.
	 * @param string $kind 'duration' or 'budget'.
	 * @return void
	 */
	private function render_ranges( $name, $rows, $kind ) {
		$is_budget = 'budget' === $kind;
		?>
		<div class="iflynepal-ranges"
			data-iflynepal-ranges
			data-iflynepal-ranges-max="<?php echo esc_attr( (string) IFLYNEPAL_TRIP_FINDER_MAX_RANGES ); ?>">

			<div class="iflynepal-ranges__list" data-iflynepal-ranges-list>
				<?php foreach ( array_values( $rows ) as $index => $row ) : ?>
					<?php
					$this->render_range_row(
						$name,
						(string) $index,
						$is_budget,
						isset( $row['min'] ) ? (string) (int) $row['min'] : '',
						( isset( $row['max'] ) && null !== $row['max'] ) ? (string) (int) $row['max'] : ''
					);
					?>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button iflynepal-ranges__add" data-iflynepal-ranges-add>
				<?php esc_html_e( '+ Add option', 'iflynepal' ); ?>
			</button>

			<template data-iflynepal-ranges-template>
				<?php $this->render_range_row( $name, '__INDEX__', $is_budget, '', '' ); ?>
			</template>
		</div>

		<?php if ( $rows ) : ?>
			<p class="cc-help">
				<?php
				$labels = array();

				foreach ( $is_budget ? iflynepal_trip_finder_budgets() : iflynepal_trip_finder_durations() as $bucket ) {
					$labels[] = $bucket['label'];
				}

				echo esc_html(
					sprintf(
						/* translators: %s: the options as visitors see them, separated by dots. */
						__( 'Visitors see: %s', 'iflynepal' ),
						implode( ' · ', $labels )
					)
				);
				?>
			</p>
		<?php else : ?>
			<p class="cc-help"><?php esc_html_e( 'Nothing set. The options are being worked out from your packages automatically.', 'iflynepal' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * One row of a range control.
	 *
	 * Drawn once per saved row and once inside the `<template>` a new row is
	 * cloned from, so the two cannot drift apart.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Base input name.
	 * @param string $index     Row index, or the __INDEX__ placeholder.
	 * @param bool   $is_budget Whether the row is a price range (true) or days (false).
	 * @param string $min       Lowest value.
	 * @param string $max       Highest value, or '' for open-ended.
	 * @return void
	 */
	private function render_range_row( $name, $index, $is_budget, $min, $max ) {
		$base = $name . '[' . $index . ']';
		$low  = $is_budget ? 0 : 1;
		?>
		<div class="iflynepal-ranges__row" data-iflynepal-ranges-row>
			<span class="iflynepal-ranges__n" data-iflynepal-ranges-number><?php echo esc_html( $index ); ?></span>

			<label class="iflynepal-ranges__leg">
				<span><?php echo esc_html( $is_budget ? __( 'From (USD)', 'iflynepal' ) : __( 'From (days)', 'iflynepal' ) ); ?></span>
				<input type="number" min="<?php echo esc_attr( (string) $low ); ?>" step="1" name="<?php echo esc_attr( $base . '[min]' ); ?>" value="<?php echo esc_attr( $min ); ?>">
			</label>

			<label class="iflynepal-ranges__leg">
				<span><?php esc_html_e( 'to', 'iflynepal' ); ?></span>
				<input type="number" min="<?php echo esc_attr( (string) $low ); ?>" step="1" name="<?php echo esc_attr( $base . '[max]' ); ?>" value="<?php echo esc_attr( $max ); ?>" placeholder="<?php esc_attr_e( 'and over', 'iflynepal' ); ?>">
			</label>

			<button type="button" class="button-link iflynepal-ranges__remove" data-iflynepal-ranges-remove>
				<span class="screen-reader-text"><?php esc_html_e( 'Remove this option', 'iflynepal' ); ?></span>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Draws the screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Enquiry & WhatsApp Settings', 'iflynepal' ); ?></h1>

			<style>
				.iflynepal-settings .cc-field { margin-bottom: 22px; max-width: 640px; }
				.iflynepal-settings .cc-field > label { display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; }
				.iflynepal-settings input[type="text"],
				.iflynepal-settings input[type="email"],
				.iflynepal-settings input[type="password"],
				.iflynepal-settings textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; }
				.iflynepal-settings .cc-help { margin: 6px 0 0; color: #646970; }
				.iflynepal-settings .cc-preview { margin-top: 6px; padding: 8px 12px; background: #f6f7f7; border-radius: 4px; word-break: break-all; }
				.iflynepal-settings .cc-warn { color: #8a4b00; }
				.iflynepal-settings .cc-field--wide { max-width: 760px; }
				.iflynepal-ranges__row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; background: #fff; margin-bottom: 8px; }
				.iflynepal-ranges__n { min-width: 20px; color: #646970; font-weight: 600; }
				.iflynepal-ranges__leg { display: flex; align-items: center; gap: 6px; margin: 0; }
				.iflynepal-ranges__leg > span { color: #646970; }
				.iflynepal-ranges__leg input { width: 110px; }
				.iflynepal-ranges__remove { margin-left: auto; color: #b32d2e; text-decoration: none; font-size: 18px; line-height: 1; }
			</style>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post" class="iflynepal-settings">
				<?php settings_fields( self::PAGE ); ?>

				<?php foreach ( iflynepal_booking_settings_schema() as $key => $field ) : ?>
					<?php
					$id   = 'iflynepal-booking-' . sanitize_key( $key );
					$name = IFLYNEPAL_BOOKING_OPTION . '[' . $key . ']';

					/*
					 * Read through the getter rather than straight out of the
					 * option, so the control shows the value the front end will
					 * actually use. A row written by anything but this form —
					 * an import, WP-CLI — can hold "+977 981 234 5678", and a
					 * screen that echoes it back while the site quietly uses
					 * 9779812345678 is a screen that disagrees with the site.
					 */
					$value = iflynepal_booking_setting( $key );
					?>
					<?php if ( 'api_key' === $field['type'] ) : ?>
						<h2><?php esc_html_e( 'Auto Translate', 'iflynepal' ); ?></h2>
					<?php endif; ?>

					<div class="cc-field<?php echo iflynepal_booking_setting_is_list( $field['type'] ) ? ' cc-field--wide' : ''; ?>">
						<?php if ( iflynepal_booking_setting_is_list( $field['type'] ) ) : ?>
							<label><?php echo esc_html( $field['label'] ); ?></label>
						<?php else : ?>
							<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<?php endif; ?>

						<?php if ( 'api_key' === $field['type'] ) : ?>
							<?php
							/*
							 * Never redrawn with the saved value — a page that
							 * echoes a secret back into its own HTML is a
							 * secret one "View source" away. Left blank and
							 * submitted, sanitize() above keeps whatever is
							 * already stored; only typing a new one replaces
							 * it.
							 */
							?>
							<input type="password" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" autocomplete="new-password" spellcheck="false" placeholder="<?php echo '' !== $value ? esc_attr__( 'Saved — leave blank to keep it', 'iflynepal' ) : ''; ?>">
							<p class="cc-preview">
								<?php if ( '' !== $value ) : ?>
									<?php
									printf(
										/* translators: %s: the last 4 characters of the saved key. */
										esc_html__( 'A key is saved, ending in %s.', 'iflynepal' ),
										'<strong>&hellip;' . esc_html( substr( $value, -4 ) ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
									);
									?>
								<?php else : ?>
									<strong><?php esc_html_e( 'No key saved yet.', 'iflynepal' ); ?></strong>
								<?php endif; ?>
							</p>
							<?php if ( '' !== $value ) : ?>
								<label class="cc-help">
									<input type="checkbox" name="<?php echo esc_attr( IFLYNEPAL_BOOKING_OPTION . '[' . $key . '_clear]' ); ?>" value="1">
									<?php esc_html_e( 'Remove the saved key', 'iflynepal' ); ?>
								</label>
							<?php endif; ?>
						<?php elseif ( 'duration_ranges' === $field['type'] || 'budget_ranges' === $field['type'] ) : ?>
							<?php $this->render_ranges( $name, is_array( $value ) ? $value : array(), 'budget_ranges' === $field['type'] ? 'budget' : 'duration' ); ?>
						<?php elseif ( 'textarea' === $field['type'] ) : ?>
							<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
						<?php elseif ( 'page' === $field['type'] && iflynepal_booking_page_field_is_multilingual() ) : ?>
							<?php
							$raw_value = iflynepal_booking_setting_raw( $key );
							?>
							<?php foreach ( PLL()->model->get_languages_list() as $language ) : ?>
								<?php
								$lang_id = $language->slug;

								if ( is_array( $raw_value ) && isset( $raw_value[ $lang_id ] ) ) {
									$selected = absint( $raw_value[ $lang_id ] );
								} elseif ( ! is_array( $raw_value ) && $raw_value && function_exists( 'pll_get_post' ) ) {
									/*
									 * Migrating from a single, language-blind page:
									 * seed each language's dropdown with that
									 * page's own translation, so switching to this
									 * per-language control does not blank out what
									 * was already working for at least one
									 * language.
									 */
									$selected = absint( pll_get_post( absint( $raw_value ), $lang_id ) );
								} else {
									$selected = 0;
								}
								?>
								<p class="cc-help" style="margin:0 0 4px;"><strong><?php echo esc_html( $language->name ); ?></strong></p>
								<?php
								wp_dropdown_pages(
									array(
										'name'             => $name . '[' . $lang_id . ']', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() escapes this internally.
										'id'               => $id . '-' . $lang_id, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Same as above.
										'selected'         => $selected,
										'show_option_none' => __( '— Select a page —', 'iflynepal' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() escapes this internally.
										'option_none_value' => '0',
									)
								);
								?>
							<?php endforeach; ?>
						<?php elseif ( 'page' === $field['type'] ) : ?>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => $name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() escapes this internally.
									'id'                => $id, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Same as above.
									'selected'          => absint( $value ),
									'show_option_none'  => __( '— Select a page —', 'iflynepal' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() escapes this internally.
									'option_none_value' => '0',
								)
							);
							?>
						<?php elseif ( 'email' === $field['type'] ) : ?>
							<input type="email" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" autocomplete="email" spellcheck="false">
						<?php else : ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" inputmode="numeric">
						<?php endif; ?>

						<p class="cc-help"><?php echo esc_html( $field['help'] ); ?></p>
					</div>
				<?php endforeach; ?>

				<?php
				/*
				 * What the site will actually do with the address above, read
				 * through the same resolver the forms use. With the field left
				 * empty this is the only place the fallback is visible — and
				 * "where did that enquiry go" is otherwise a question with no
				 * answer on any screen.
				 */
				$inbox = iflynepal_notification_recipient();
				?>
				<div class="cc-field">
					<label><?php esc_html_e( 'Form submissions are being sent to', 'iflynepal' ); ?></label>
					<p class="cc-preview">
						<?php if ( '' !== $inbox ) : ?>
							<strong><?php echo esc_html( $inbox ); ?></strong>
						<?php else : ?>
							<strong><?php esc_html_e( 'Nowhere — no usable address is configured.', 'iflynepal' ); ?></strong>
						<?php endif; ?>
					</p>
					<p class="cc-help"><?php esc_html_e( 'Saved submissions are kept under Enquiries whether the email arrives or not, so nothing is lost if this address is wrong.', 'iflynepal' ); ?></p>
				</div>

				<?php
				/*
				 * The live link, so the number can be checked by pressing it
				 * rather than by publishing a package and hoping. Built by the
				 * same function the front end uses, so what is shown here is
				 * what a visitor gets.
				 */
				$preview = iflynepal_whatsapp_url();
				?>
				<?php if ( '' !== $preview ) : ?>
					<div class="cc-field">
						<label><?php esc_html_e( 'This is the link visitors will open', 'iflynepal' ); ?></label>
						<p class="cc-preview"><a href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $preview ); ?></a></p>
						<p class="cc-help"><?php esc_html_e( 'On a package the name of that package is filled in where {package} sits.', 'iflynepal' ); ?></p>
					</div>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Payments', 'iflynepal' ); ?></h2>

			<?php
			/*
			 * Read-only, and outside the form: nothing here is a setting of
			 * ours. Payments are the Easy PayPal & Stripe Button plugin's job
			 * and this only reports whether that plugin is there to do it, so
			 * "why is there no Book now button" has an answer on one screen
			 * rather than three.
			 */
			$gateway_active = iflynepal_payment_gateway_active();
			?>
			<div class="iflynepal-settings">
				<div class="cc-field">
					<p class="cc-preview">
						<?php if ( $gateway_active ) : ?>
							<strong><?php esc_html_e( 'Easy PayPal & Stripe Button is active.', 'iflynepal' ); ?></strong>
							<?php
							$button_count = count( iflynepal_payment_buttons() );

							printf(
								/* translators: %d: how many published payment buttons exist. */
								esc_html( _n( '%d payment button is available to choose on a package.', '%d payment buttons are available to choose on a package.', $button_count, 'iflynepal' ) ),
								(int) $button_count
							);
							?>
						<?php elseif ( iflynepal_payment_gateway_installed() ) : ?>
							<strong><?php esc_html_e( 'Easy PayPal & Stripe Button is installed but switched off.', 'iflynepal' ); ?></strong>
							<?php esc_html_e( 'No package can take a payment until it is activated on the Plugins screen. Nothing else on the site is affected, and the buttons already chosen on packages are remembered.', 'iflynepal' ); ?>
						<?php else : ?>
							<strong><?php esc_html_e( 'Easy PayPal & Stripe Button is not installed.', 'iflynepal' ); ?></strong>
							<?php esc_html_e( 'It is what takes the payment; this plugin only places its button on a package. Install and activate it, create a button, then choose that button in the Payment box on the package.', 'iflynepal' ); ?>
						<?php endif; ?>
					</p>

					<?php if ( $gateway_active ) : ?>
						<p class="cc-help">
							<a href="<?php echo esc_url( iflynepal_payment_buttons_admin_url() ); ?>"><?php esc_html_e( 'Manage payment buttons', 'iflynepal' ); ?></a>
							&nbsp;·&nbsp;
							<a href="<?php echo esc_url( iflynepal_payment_buttons_admin_url( true ) ); ?>"><?php esc_html_e( 'Create a payment button', 'iflynepal' ); ?></a>
						</p>
					<?php endif; ?>
				</div>
			</div>

		</div>
		<?php
	}
}

new IFly_Nepal_Booking_Settings();
