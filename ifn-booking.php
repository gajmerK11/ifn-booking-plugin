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
 *   includes/enquiry/class-ifly-nepal-enquiry-store.php       enquiry storage
 *   includes/enquiry/class-ifly-nepal-enquiry-form.php        front-end enquiry handler
 *   includes/departures/package-departures-meta.php           fixed departure dates
 *   includes/whatsapp/whatsapp-link.php                       click-to-chat
 *   includes/payment/class-ifly-nepal-paypal-listener.php     PayPal webhook / IPN capture
 *   includes/rest/route-base.php + includes/rest/route-*.php  iflynepal/v1 routes
 *   admin/class-ifly-nepal-package-type-meta-box.php          (done)                   (is_admin only)
 *   admin/class-ifly-nepal-bookings-screen.php                Bookings admin view      (is_admin only)
 *   includes/frontend/enqueue.php + template loading          archive / single templates
 */

require_once IFLYNEPAL_BOOKING_DIR . 'includes/helpers.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/cpt/package-cpt.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/rewrites/package-rewrites.php';
require_once IFLYNEPAL_BOOKING_DIR . 'includes/lifecycle.php';

if ( is_admin() ) {
	require_once IFLYNEPAL_BOOKING_DIR . 'admin/class-ifly-nepal-package-type-meta-box.php';
}

register_activation_hook( IFLYNEPAL_BOOKING_FILE, 'iflynepal_booking_activate' );
register_deactivation_hook( IFLYNEPAL_BOOKING_FILE, 'iflynepal_booking_deactivate' );
