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
	 * Loads the length-options control's behaviour, on this screen only.
	 *
	 * The hook fires on every admin page, so the screen is checked rather than
	 * assumed — `$hook` carries the suffix add_submenu_page() returned, which is
	 * the only reliable way to name a screen that lives under a post type's own
	 * menu.
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
			'iflynepal-settings-durations',
			IFLYNEPAL_BOOKING_URL . 'assets/js/admin/settings-durations.js',
			array(),
			iflynepal_booking_asset_version( 'assets/js/admin/settings-durations.js' ),
			true
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

		foreach ( iflynepal_booking_settings_schema() as $key => $field ) {
			/*
			 * 🔴 The is_string() guard is right for a text field and wrong for a
			 * list one — a repeater posts an array, and treating it as "not a
			 * string, so empty" would wipe the length options on every save of
			 * this screen, including a save that never touched them. The type
			 * decides which shape is expected, through the same predicate the
			 * getter uses.
			 */
			if ( iflynepal_booking_setting_is_list( $field['type'] ) ) {
				$raw = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();
			} else {
				$raw = isset( $value[ $key ] ) && is_string( $value[ $key ] ) ? $value[ $key ] : '';
			}

			$clean[ $key ] = iflynepal_booking_sanitize_setting( $raw, $field['type'] );
		}

		return $clean;
	}

	/**
	 * Draws the trip-finder's length options as a row per bucket.
	 *
	 * Two selects rather than two number fields, which is what was asked for and
	 * is also the stronger control: the numbers on offer are the lengths this
	 * business actually sells, so a boundary cannot be set at a duration no
	 * package has, and there is nothing to mistype. The wording is worked out
	 * from the numbers and shown beside the row, so what the picker will say is
	 * on screen before it is saved.
	 *
	 * The row markup is a `<template>`, not a JavaScript string — the same
	 * contract the archive and package repeaters follow, so a saved row and a new
	 * one are the same markup, and a template's inputs are never submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Base input name, e.g. option[trip_finder_durations].
	 * @param array  $rows Stored rows.
	 * @return void
	 */
	private function render_durations( $name, $rows ) {
		$choices = iflynepal_trip_finder_duration_choices();
		$days    = $choices['days'];

		if ( ! $rows ) {
			$rows = iflynepal_trip_finder_default_durations();
		}
		?>
		<div class="iflynepal-durations"
			data-iflynepal-durations
			data-iflynepal-durations-max="<?php echo esc_attr( (string) IFLYNEPAL_TRIP_FINDER_MAX_DURATIONS ); ?>">

			<div class="iflynepal-durations__list" data-iflynepal-durations-list>
				<?php foreach ( array_values( $rows ) as $index => $row ) : ?>
					<?php
					$this->render_duration_row(
						$name,
						(string) $index,
						$days,
						isset( $row['min'] ) ? (int) $row['min'] : 0,
						( isset( $row['max'] ) && null !== $row['max'] ) ? (int) $row['max'] : null
					);
					?>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button iflynepal-durations__add" data-iflynepal-durations-add>
				<?php esc_html_e( '+ Add option', 'iflynepal' ); ?>
			</button>

			<?php
			/*
			 * A new row opens on the shortest length rather than on a blank, so
			 * pressing Add and then Save can never store a row that means
			 * nothing. __INDEX__ is rewritten from the row's position the moment
			 * it is added.
			 *
			 * It opens as a *closed* range, not as "and over", and that is worth
			 * not undoing. Only one row can be open-ended — the sanitizer keeps
			 * the highest and drops the rest — so a new row defaulting to open
			 * would look perfectly fine on screen and then quietly disappear on
			 * save whenever a longer open-ended row already existed. A closed
			 * range is always kept, so what was added is what comes back.
			 */
			?>
			<template data-iflynepal-durations-template>
				<?php $this->render_duration_row( $name, '__INDEX__', $days, (int) reset( $days ), (int) reset( $days ) ); ?>
			</template>
		</div>

		<?php if ( ! $choices['derived'] ) : ?>
			<p class="cc-help cc-warn">
				<?php esc_html_e( 'No published package has a Trip duration (days) yet, so these dropdowns are showing a general 1–30 instead of your own lengths. Set a duration on a package and they will follow the catalogue.', 'iflynepal' ); ?>
			</p>
		<?php else : ?>
			<p class="cc-help">
				<?php
				printf(
					/* translators: 1: shortest package length in days, 2: longest. */
					esc_html__( 'Your published packages run from %1$d to %2$d days, so those are the numbers on offer.', 'iflynepal' ),
					(int) reset( $days ),
					(int) end( $days )
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * One row of the length-options control.
	 *
	 * Pulled out of render_durations() because it is drawn twice — once per
	 * saved row, and once inside the `<template>` a new row is cloned from. Two
	 * copies of this markup is how the two quietly stop matching.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $name  Base input name.
	 * @param string   $index Row index, or the __INDEX__ placeholder.
	 * @param int[]    $days  Day numbers to offer.
	 * @param int      $min   Selected lowest day count.
	 * @param int|null $max   Selected highest, or null for open-ended.
	 * @return void
	 */
	private function render_duration_row( $name, $index, $days, $min, $max ) {
		$base = $name . '[' . $index . ']';
		?>
		<div class="iflynepal-durations__row" data-iflynepal-durations-row>
			<span class="iflynepal-durations__n" data-iflynepal-durations-number><?php echo esc_html( $index ); ?></span>

			<label class="iflynepal-durations__leg">
				<span><?php esc_html_e( 'From', 'iflynepal' ); ?></span>
				<select name="<?php echo esc_attr( $base . '[min]' ); ?>">
					<?php foreach ( $days as $day ) : ?>
						<option value="<?php echo esc_attr( (string) $day ); ?>" <?php selected( $min, $day ); ?>>
							<?php echo esc_html( (string) $day ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="iflynepal-durations__leg">
				<span><?php esc_html_e( 'to', 'iflynepal' ); ?></span>
				<select name="<?php echo esc_attr( $base . '[max]' ); ?>">
					<?php
					/*
					 * The empty value is the open-ended row — "and over" — and
					 * is stored as null rather than as a very large number, so
					 * a package longer than anything currently published still
					 * matches it. Only one row can be open-ended; the sanitizer
					 * keeps the highest and drops the rest.
					 */
					?>
					<option value="" <?php selected( null === $max, true ); ?>><?php esc_html_e( 'and over', 'iflynepal' ); ?></option>
					<?php foreach ( $days as $day ) : ?>
						<option value="<?php echo esc_attr( (string) $day ); ?>" <?php selected( $max, $day ); ?>>
							<?php echo esc_html( (string) $day ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<span class="iflynepal-durations__preview" data-iflynepal-durations-preview>
				<?php echo esc_html( iflynepal_trip_finder_duration_label( $min, $max ) ); ?>
			</span>

			<button type="button" class="button-link iflynepal-durations__remove" data-iflynepal-durations-remove>
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
				.iflynepal-settings textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; }
				.iflynepal-settings .cc-help { margin: 6px 0 0; color: #646970; }
				.iflynepal-settings .cc-preview { margin-top: 6px; padding: 8px 12px; background: #f6f7f7; border-radius: 4px; word-break: break-all; }
				.iflynepal-settings .cc-warn { color: #8a4b00; }
				.iflynepal-settings .cc-field--wide { max-width: 760px; }
				.iflynepal-durations__row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; background: #fff; margin-bottom: 8px; }
				.iflynepal-durations__n { min-width: 20px; color: #646970; font-weight: 600; }
				.iflynepal-durations__leg { display: flex; align-items: center; gap: 6px; margin: 0; }
				.iflynepal-durations__leg > span { color: #646970; }
				.iflynepal-durations__leg select { min-width: 96px; }
				.iflynepal-durations__preview { margin-left: auto; padding: 3px 10px; border-radius: 999px; background: #f0f5ff; color: #04347d; font-weight: 600; }
				.iflynepal-durations__remove { color: #b32d2e; text-decoration: none; font-size: 18px; line-height: 1; }
				.iflynepal-durations__remove[disabled] { color: #c3c4c7; cursor: not-allowed; }
				.iflynepal-durations__add { margin-top: 2px; }
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
					<div class="cc-field<?php echo iflynepal_booking_setting_is_list( $field['type'] ) ? ' cc-field--wide' : ''; ?>">
						<?php
						/*
						 * A list setting has no single control for a label to
						 * point at, so it is labelled as a group instead. A
						 * `for` naming an element that does not exist is worse
						 * than no `for` at all: a screen reader follows it and
						 * lands nowhere.
						 */
						?>
						<?php if ( iflynepal_booking_setting_is_list( $field['type'] ) ) : ?>
							<label><?php echo esc_html( $field['label'] ); ?></label>
						<?php else : ?>
							<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<?php endif; ?>

						<?php if ( 'textarea' === $field['type'] ) : ?>
							<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
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
						<?php elseif ( 'durations' === $field['type'] ) : ?>
							<?php $this->render_durations( $name, is_array( $value ) ? $value : array() ); ?>
						<?php else : ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" inputmode="numeric">
						<?php endif; ?>

						<p class="cc-help"><?php echo esc_html( $field['help'] ); ?></p>
					</div>
				<?php endforeach; ?>

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
