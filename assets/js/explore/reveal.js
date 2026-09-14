/**
 * The [iflynepal_type_explorer] results page's scroll reveal.
 *
 * Same IntersectionObserver technique as assets/js/archive/annotation.js
 * uses for the ink-mark underline, deliberately not GSAP — see the note
 * above [data-iflynepal-fade] in assets/css/catalogue.css for why this page
 * does not just reuse the archive's own reveal.js.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var items = document.querySelectorAll( '[data-iflynepal-fade]' );

	if ( ! items.length ) {
		return;
	}

	if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
		items.forEach( function ( item ) {
			item.classList.add( 'is-revealed' );
		} );

		return;
	}

	var observer = new IntersectionObserver( function ( entries, obs ) {
		entries.forEach( function ( entry ) {
			if ( ! entry.isIntersecting ) {
				return;
			}

			entry.target.classList.add( 'is-revealed' );
			obs.unobserve( entry.target );
		} );
	}, { threshold: 0.2, rootMargin: '0px 0px -10% 0px' } );

	items.forEach( function ( item ) {
		observer.observe( item );
	} );
}() );
