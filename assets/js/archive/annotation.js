/**
 * The hand-drawn underline in the package grid heading.
 *
 * It sweeps itself in the first time the heading is scrolled to.
 *
 * Vanilla, with no GSAP. The theme loads GSAP and ScrollTrigger only on the
 * templates that animate, and the whole of the work here is one
 * IntersectionObserver — a good deal less code than the library it would
 * take to avoid writing it.
 *
 * Decoration only: it is not the only route to any information, and it stops
 * dead under prefers-reduced-motion.
 */
( function () {
	'use strict';

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var marks = document.querySelectorAll( '.iflynepal-ink-mark' );

	if ( ! marks.length ) {
		return;
	}

	if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
		// No observer, or motion is unwelcome: show the finished stroke.
		Array.prototype.forEach.call( marks, function ( mark ) {
			mark.classList.add( 'is-drawn' );
		} );

		return;
	}

	var observer = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( ! entry.isIntersecting ) {
				return;
			}

			entry.target.classList.add( 'is-drawn' );

			// Draws once. A stroke that redrew on every pass would read as a glitch.
			observer.unobserve( entry.target );
		} );
	}, { rootMargin: '0px 0px -14% 0px' } );

	Array.prototype.forEach.call( marks, function ( mark ) {
		observer.observe( mark );
	} );
}() );
