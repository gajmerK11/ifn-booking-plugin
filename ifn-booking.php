<?php
/**
 * Plugin Name:       iFly Nepal Booking
 * Plugin URI:        https://iflynepal.com/
 * Description:        Package catalogue, enquiries, departure dates and PayPal booking capture for iFly Nepal.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            iFly Nepal
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       iflynepal
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'IFLYNEPAL_BOOKING_VERSION', '1.0.0' );
define( 'IFLYNEPAL_BOOKING_FILE', __FILE__ );
define( 'IFLYNEPAL_BOOKING_DIR', plugin_dir_path( __FILE__ ) );
define( 'IFLYNEPAL_BOOKING_URL', plugin_dir_url( __FILE__ ) );

/*
 * Load order (add a line here as each piece is built; keep it dependency-ordered):
 *
 *   includes/helpers.php                                      (done)
 *   includes/lifecycle.php                                    (done)
 *   includes/cpt/package-cpt.php                              (done)
 *   includes/rewrites/package-rewrites.php                    (done)
 *   includes/archive/package-type-archive-schema.php          (done)
 *   includes/package/package-details-schema.php               (done)
 *   includes/package/package-meta.php                         (done)
 *   includes/frontend/archive-render.php                      (done)
 *   includes/frontend/package-render.php                      (done)
 *   includes/frontend/template-loader.php                     (done)
 *   includes/frontend/enqueue.php                             (done)
 *   includes/frontend/testimonial-targets.php                 (done)
 *   includes/frontend/homepage-departures.php                 (done)
 *   includes/frontend/homepage-reasons.php                    (done)
 *   includes/frontend/type-explorer.php                       (done)
 *   includes/frontend/homepage-trip-finder.php                (done)
 *   includes/settings/settings.php                            (done)
 *   includes/enquiry/enquiry-cpt.php                          (done)
 *   includes/enquiry/enquiry-store.php                        (done)
 *   includes/enquiry/enquiry-form.php                         (done)
 *   (the WhatsApp click-to-chat link is built in includes/settings/settings.php,
 *    which is where its number and message are configured)
 *   includes/payment/payment-buttons.php                      (done)
 *   includes/payment/class-ifly-nepal-paypal-listener.php     PayPal webhook / IPN capture
 *   includes/rest/route-base.php + includes/rest/route-*.php  iflynepal/v1 routes
 *   admin/class-ifly-nepal-package-type-meta-box.php          (built, not loaded)      (is_admin only)
 *   admin/class-ifly-nepal-package-box-state.php              (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-type-archive-fields.php    (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-details-meta-box.php       (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-homepage-box.php           (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-details-box.php            (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-video-box.php              (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-gallery-box.php            (done)                   (is_admin only)
 *   admin/class-ifly-nepal-enquiry-details-box.php            (done)                   (is_admin only)
 *   admin/class-ifly-nepal-package-payment-box.php            (done)                   (is_admin only)
 *   admin/class-ifly-nepal-enquiry-status-box.php             (done)                   (is_admin only)
 *   admin/class-ifly-nepal-booking-settings.php               (done)                   (is_admin only)
 *   admin/class-ifly-nepal-bookings-screen.php                Bookings admin view      (is_admin only)
 */

require_once IFLYNEPAL_BOOKING_DIR . 'includes/helpers.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/cpt/package-cpt.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/archive/package-type-archive-schema.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/package/package-details-schema.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/package/package-meta.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/rewrites/package-rewrites.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/archive-render.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/package-render.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/template-loader.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/enqueue.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/testimonial-targets.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/homepage-departures.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/homepage-reasons.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/type-explorer.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/frontend/homepage-trip-finder.php';

/*
 * The settings come before the enquiry pieces and before anything that renders:
 * the WhatsApp link the templates ask for is built from them.
 */
require_once IFLYNEPAL_BOOKING_DIR . 'includes/settings/settings.php';

/*
 * The bridge to the payment gateway. Loaded before the templates that ask for a
 * button and before the admin box that chooses one, and guarded throughout on
 * that plugin being active — no payment logic of our own, and nothing printed
 * when the gateway is not there to print it.
 */
require_once IFLYNEPAL_BOOKING_DIR . 'includes/payment/payment-buttons.php';

/*
 * The enquiry pieces load after the front end, not before it: the form part is
 * rendered through iflynepal_booking_get_part() and printed only on the
 * templates iflynepal_booking_is_package_template() answers for, both of which
 * are declared in includes/frontend/.
 */
require_once IFLYNEPAL_BOOKING_DIR . 'includes/enquiry/enquiry-cpt.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/enquiry/enquiry-store.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/enquiry/enquiry-form.php';

require_once IFLYNEPAL_BOOKING_DIR . 'includes/lifecycle.php';

if ( is_admin() ) {
	/*
	 * admin/class-ifly-nepal-package-type-meta-box.php is deliberately not
	 * required. It draws the Primary Package Type picker, which was removed
	 * from the editor; loading it is what puts the box back, because the file
	 * instantiates itself at the bottom.
	 *
	 * Nothing about permalinks depends on it. The term a package's URL is
	 * built from is resolved by iflynepal_package_primary_type() in
	 * includes/rewrites/package-rewrites.php, which reads the stored choice
	 * when there is one and otherwise picks the deepest term the package
	 * holds, lowest term ID breaking a tie. Without the picker every package
	 * takes that automatic path, and a package that already carries a stored
	 * primary keeps honouring it.
	 */
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-box-state.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-type-archive-fields.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-details-meta-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-homepage-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-details-box.php';

	/*
	 * Registered before the Gallery box, and both at `low`, so the sidebar reads
	 * Featured image, Featured Video, Gallery: boxes of equal priority sit in
	 * registration order.
	 */
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-video-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-gallery-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-payment-box.php';

	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-enquiry-details-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-enquiry-status-box.php';
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-booking-settings.php';
}

register_activation_hook( IFLYNEPAL_BOOKING_FILE, 'iflynepal_booking_activate' );
register_deactivation_hook( IFLYNEPAL_BOOKING_FILE, 'iflynepal_booking_deactivate' );
