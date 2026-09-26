<?php
/**
 * Registers the Packages templates' static UI strings with Polylang, and the
 * helper the templates use to fetch each one's translation.
 *
 * These are hand-written interface strings (button labels, aria-labels,
 * eyebrows, empty states) baked into the plugin's templates — not editor
 * content, which is why they go through Polylang's per-string translation
 * table rather than post/term translation. Registration only takes effect in
 * wp-admin (Polylang builds the registry from PLL_Admin_Base), same
 * constraint as the theme's Customizer string registrations.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns a string's translation for the current language, or the string
 * itself when Polylang is not active.
 *
 * @since 1.0.0
 *
 * @param string $string The original (English) string, exactly as registered.
 * @return string
 */
function iflynepal_pkg_t( $string ) {
	return function_exists( 'pll__' ) ? pll__( $string ) : $string;
}

/**
 * Picks a singular or plural registered string and returns its translation,
 * mirroring _n() for the strings this file registers in pairs.
 *
 * @since 1.0.0
 *
 * @param string $single The singular form, exactly as registered.
 * @param string $plural The plural form, exactly as registered.
 * @param int    $number The count deciding which form to use.
 * @return string
 */
function iflynepal_pkg_tn( $single, $plural, $number ) {
	return iflynepal_pkg_t( 1 === (int) $number ? $single : $plural );
}

/**
 * Registers every static UI string the Packages templates print.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_package_pll_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$archive = 'iFlyNepal — Packages / Archive';
	$filters = 'iFlyNepal — Packages / Filters & Listing';
	$single  = 'iFlyNepal — Packages / Single';
	$booking = 'iFlyNepal — Packages / Booking & Dates';
	$itin    = 'iFlyNepal — Packages / Itinerary';
	$gallery = 'iFlyNepal — Packages / Gallery';
	$card    = 'iFlyNepal — Packages / Card';
	$forms   = 'iFlyNepal — Packages / Forms';

	// Archive.
	pll_register_string( 'Archive hero eyebrow', 'Everything we run', $archive );
	pll_register_string( 'Archive hero lead', 'Every kind of trip we run, a few from each. Open any one to see the whole list.', $archive );
	pll_register_string( 'Archive empty state', 'No packages have been published yet.', $archive );
	pll_register_string( 'Reserve button', 'Reserve', $archive );
	pll_register_string( 'Previous departures', 'Previous departures', $archive );
	pll_register_string( 'Next departures', 'Next departures', $archive );
	pll_register_string( 'Upcoming departures group label', 'Upcoming departures', $archive );

	// Filters & listing.
	pll_register_string( 'Filters button', 'Filters', $filters );
	pll_register_string( 'Facets panel title', 'Find your trip', $filters );
	pll_register_string( 'Clear all (facets)', 'Clear all', $filters );
	pll_register_string( 'Filter chip: All', 'All', $filters );
	pll_register_string( 'No matches heading', 'No packages match that combination.', $filters );
	pll_register_string( 'Clear all filters button', 'Clear all filters', $filters );
	pll_register_string( 'Show N more package (singular)', 'Show %s more package', $filters );
	pll_register_string( 'Show N more packages (plural)', 'Show %s more packages', $filters );
	pll_register_string( 'Show all matching packages', 'Show all matching packages', $filters );
	pll_register_string( 'View all N packages', 'View all %s packages', $filters );

	// Single: gallery.
	pll_register_string( 'Photo gallery label', 'Photo gallery', $gallery );
	pll_register_string( 'Play video', 'Play video', $gallery );
	pll_register_string( 'Pause video', 'Pause video', $gallery );
	pll_register_string( 'Open photo 1 of N', 'Open photo 1 of %d', $gallery );
	pll_register_string( 'Open photo M of N', 'Open photo %1$d of %2$d', $gallery );
	pll_register_string( 'View N photo (singular)', 'View %d photo', $gallery );
	pll_register_string( 'View N photos (plural)', 'View all %d photos', $gallery );
	pll_register_string( 'Close gallery', 'Close gallery', $gallery );
	pll_register_string( 'Previous photo', 'Previous photo', $gallery );
	pll_register_string( 'Next photo', 'Next photo', $gallery );
	pll_register_string( 'Show photo N (lightbox thumb aria)', 'Show photo %d', $gallery );

	// Single: layout.
	pll_register_string( 'At a glance eyebrow', 'At a glance', $single );
	pll_register_string( 'Package sections nav label', 'Package sections', $single );
	pll_register_string( 'Explore this package', 'Explore this package', $single );
	pll_register_string( 'Before you book eyebrow', 'Before you book', $single );
	pll_register_string( 'Overview section label', 'Overview', $single );
	pll_register_string( 'Highlights heading', 'Highlights', $single );
	pll_register_string( 'Packing list eyebrow', 'Packing list', $single );
	pll_register_string( 'Packing & map section nav label', 'Packing & map', $single );
	pll_register_string( 'FAQs section nav label', 'FAQs', $single );
	pll_register_string( 'Similar packages section nav label', 'Similar packages', $single );

	// Single: at-a-glance table.
	pll_register_string( 'Glance: Destination', 'Destination', $single );
	pll_register_string( 'Glance: Duration', 'Duration', $single );
	pll_register_string( 'Glance: Activities', 'Activities', $single );
	pll_register_string( 'Glance: Meals', 'Meals', $single );
	pll_register_string( 'Glance: Accommodation', 'Accommodation', $single );
	pll_register_string( 'Glance: Max altitude', 'Max altitude', $single );
	pll_register_string( 'Glance: Group size', 'Group size', $single );
	pll_register_string( 'Glance: Experience level', 'Experience level', $single );
	pll_register_string( 'Glance: Best time', 'Best time', $single );
	pll_register_string( 'Glance: Check-in', 'Check-in', $single );

	// Single: breadcrumb & share row.
	pll_register_string( 'Breadcrumb: Home crumb', 'Home', $single );
	pll_register_string( 'Breadcrumb nav aria-label', 'Breadcrumb', $single );
	pll_register_string( 'Share row label', 'Share this:', $single );
	pll_register_string( 'Share on Facebook', 'Share on Facebook', $single );
	pll_register_string( 'Share on X', 'Share on X', $single );
	pll_register_string( 'Share on WhatsApp', 'Share on WhatsApp', $single );
	pll_register_string( 'Visit us on %s (social)', 'Visit us on %s', $single );
	pll_register_string( 'Copy link button', 'Copy link', $single );
	pll_register_string( 'Link copied toast', 'Link copied', $single );

	/*
	 * At-a-glance fixed-vocabulary field options. Stored as their bare English
	 * key ("Included", "Relaxed", "Breakfast"…) regardless of language — see
	 * includes/package/package-details-schema.php — so the front end has to
	 * translate the stored key itself rather than a per-package free-text
	 * value. Registered here, once, so every package's glance table reads
	 * them the same way.
	 */
	pll_register_string( 'Glance: Accommodation included', 'Included', $single );
	pll_register_string( 'Glance: Experience level Relaxed', 'Relaxed', $single );
	pll_register_string( 'Glance: Experience level Easy', 'Easy', $single );
	pll_register_string( 'Glance: Experience level Moderate', 'Moderate', $single );
	pll_register_string( 'Glance: Experience level Hard', 'Hard', $single );
	pll_register_string( 'Glance: Meal Breakfast', 'Breakfast', $single );
	pll_register_string( 'Glance: Meal Lunch', 'Lunch', $single );
	pll_register_string( 'Glance: Meal Dinner', 'Dinner', $single );
	pll_register_string( 'Map eyebrow / fallback', 'Map', $single );
	pll_register_string( 'Open map image button', 'Open map image', $single );
	pll_register_string( 'Open in Maps link', 'Open in Maps', $single );
	pll_register_string( 'Keep exploring eyebrow', 'Keep exploring', $single );
	pll_register_string( 'Similar packages heading', 'Similar <span class="iflynepal-ink-mark">packages</span><br>you may like.', $single, true );

	// Booking: aside & dates.
	pll_register_string( 'Price and booking aside label', 'Price and booking', $booking );
	pll_register_string( 'Normal price label', 'Normal price:', $booking );
	pll_register_string( 'Tier price label', '%s price:', $booking );
	pll_register_string( 'Pax unit', 'pax', $booking );
	pll_register_string( 'From (price prefix)', 'From', $booking );
	pll_register_string( 'Person unit', 'person', $booking );
	pll_register_string( 'Pick date note (aside)', 'Pick a start date and how many are travelling below to book.', $booking );
	pll_register_string( 'Book now button', 'Book now', $booking );
	pll_register_string( 'Inquire now button', 'Inquire now', $booking );
	pll_register_string( 'Chat on WhatsApp button', 'Chat on WhatsApp', $booking );
	pll_register_string( 'Ask us about this journey heading', 'Ask us about this journey', $booking );
	pll_register_string( 'Tell us your dates enquiry text', 'Tell us your dates and group size and we will send you a price.', $booking );
	pll_register_string( 'Or chat on WhatsApp', 'Or chat on WhatsApp', $booking );
	pll_register_string( 'Speak to an expert', 'Speak to an expert', $booking );
	pll_register_string( 'Dates & prices eyebrow', 'Dates & prices', $booking );
	pll_register_string( 'Your dates, your call', 'your dates, your call', $booking );
	pll_register_string( 'Previous month', 'Previous month', $booking );
	pll_register_string( 'Next month', 'Next month', $booking );
	pll_register_string( 'Next weekends', 'Next weekends', $booking );
	pll_register_string( 'Your trip heading', 'Your trip', $booking );
	pll_register_string( 'Starts label', 'Starts', $booking );
	pll_register_string( 'Pick a date placeholder', 'Pick a date', $booking );
	pll_register_string( 'Ends label', 'Ends', $booking );
	pll_register_string( 'Travellers label', 'Travellers', $booking );
	pll_register_string( 'Remove a traveller', 'Remove a traveller', $booking );
	pll_register_string( 'Add a traveller', 'Add a traveller', $booking );
	pll_register_string( 'Trip duration label', 'Trip duration:', $booking );
	pll_register_string( 'Per person label', 'Per person', $booking );
	pll_register_string( 'Total label', 'Total', $booking );
	pll_register_string( 'Pick date note (dates)', 'Pick a start date and how many are travelling to continue.', $booking );
	pll_register_string( 'Included in the price', 'Included in the price', $booking );
	pll_register_string( 'Not included', 'Not included', $booking );
	pll_register_string( 'N day (singular)', '%d day', $booking );
	pll_register_string( 'N days (plural)', '%d days', $booking );
	pll_register_string( 'Book note (quotation disclaimer)', 'Prices are a quotation. Nothing is reserved until you hear from us.', $booking );
	pll_register_string( 'Weekday initials: Monday', 'Mo', $booking );
	pll_register_string( 'Weekday initials: Tuesday', 'Tu', $booking );
	pll_register_string( 'Weekday initials: Wednesday', 'We', $booking );
	pll_register_string( 'Weekday initials: Thursday', 'Th', $booking );
	pll_register_string( 'Weekday initials: Friday', 'Fr', $booking );
	pll_register_string( 'Weekday initials: Saturday', 'Sa', $booking );
	pll_register_string( 'Weekday initials: Sunday', 'Su', $booking );

	// Itinerary.
	pll_register_string( 'Week unit', 'Week', $itin );
	pll_register_string( 'Day unit', 'Day', $itin );
	pll_register_string( 'Itinerary eyebrow', 'Itinerary', $itin );
	pll_register_string( 'Altitude point label', 'day %1$d, %2$s metres', $itin );
	pll_register_string( 'Altitude chart description', 'Line chart of altitude by day: %s.', $itin );
	pll_register_string( 'Altitude profile eyebrow', 'Altitude profile', $itin );
	pll_register_string( 'Altitude point title', 'Day %1$d: %2$sm', $itin );
	pll_register_string( 'Itinerary view group label', 'Itinerary view', $itin );
	pll_register_string( 'Short itinerary tab', 'Short itinerary', $itin );
	pll_register_string( 'Detailed itinerary tab', 'Detailed Itinerary', $itin );
	pll_register_string( 'Expand all button', 'Expand all', $itin );
	pll_register_string( 'Collapse all button', 'Collapse all', $itin );

	// Card.
	pll_register_string( 'Explore package (aria, with title)', 'Explore %s', $card );
	pll_register_string( 'Explore button text', 'Explore', $card );

	// Connect form.
	pll_register_string( 'Connect With Us', 'Connect With Us', $forms );
	pll_register_string( 'Close', 'Close', $forms );
	pll_register_string( 'Honeypot label', 'Leave this field empty', $forms );
	pll_register_string( 'Choose one placeholder', 'Choose one', $forms );
	pll_register_string( 'Send button', 'Send', $forms );
	pll_register_string( 'Prefer to chat?', 'Prefer to chat?', $forms );
	pll_register_string( 'Message us on WhatsApp', 'Message us on WhatsApp', $forms );
	pll_register_string( 'Connect privacy note', 'We use your details to answer you and nothing else.', $forms );

	// Enquiry form.
	pll_register_string( 'Close the enquiry form', 'Close the enquiry form', $forms );
	pll_register_string( 'No payment needed to enquire', 'No payment needed to enquire', $forms );
	pll_register_string( 'Enquiry send note', 'Send us a note and we will reply by email or WhatsApp, usually within a day.', $forms );
	pll_register_string( 'About label', 'About', $forms );
	pll_register_string( 'Send enquiry button', 'Send enquiry', $forms );
	pll_register_string( 'Enquiry privacy note', 'We use your details to answer your enquiry and nothing else.', $forms );
}
add_action( 'admin_init', 'iflynepal_register_package_pll_strings', 16 );
