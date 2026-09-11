/**
 * The prev/next controls on the departures rail.
 *
 * The rail is a scroll container in CSS, so it already works by touch, trackpad
 * and keyboard on its own. This adds the two arrow buttons and nothing else, and
 * hides them when everything already fits — a control that does nothing is worse
 * than no control.
 */
( function () {
	'use strict';

	var rail = document.querySelector( '.iflynepal-departures__rail' );
	var nav = document.querySelector( '.iflynepal-rail-nav' );

	if ( ! rail || ! nav ) {
		return;
	}

	function step() {
		var card = rail.querySelector( '.iflynepal-departure' );

		return card ? card.getBoundingClientRect().width + 20 : rail.clientWidth * 0.8;
	}

	function sync() {
		nav.hidden = rail.scrollWidth <= rail.clientWidth + 1;
	}

	nav.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.iflynepal-rail-btn' );

		if ( ! button ) {
			return;
		}

		rail.scrollBy( {
			left: 'prev' === button.dataset.rail ? -step() : step(),
			behavior: 'smooth'
		} );
	} );

	window.addEventListener( 'resize', sync );
	sync();
}() );
