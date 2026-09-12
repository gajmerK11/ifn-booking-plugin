/**
 * The upcoming-departures rail.
 *
 * The rail is a real scrolling region and works on its own — touch, wheel,
 * trackpad and keyboard all scroll it with this file absent. What this adds is
 * the pair of buttons on the heading's baseline and the loop, which is why the
 * buttons are rendered disabled and enabled here: with no JavaScript they are
 * never dead controls.
 *
 * The loop is the mechanism the design describes and the theme's "People behind
 * the journey" row already uses: the run of cards is duplicated once, and when
 * the rail has travelled the width of one run it is moved back by exactly that
 * width. Because the cards under the viewport at that moment are identical to
 * the ones it jumps to, nothing visibly moves — so the rail runs forever in
 * either direction with no end to hit and no button to disable.
 *
 * Two things that make or break it:
 *
 *  - The jump must be instant. That is why the stylesheet does not set
 *    scroll-behavior: smooth on the rail; the glide is asked for per press, so
 *    the repositioning stays a jump rather than being animated into a visible
 *    rewind.
 *  - The jump must happen after a press has finished animating, not during it.
 *    Moving scrollLeft mid-glide cancels the glide and the rail stops dead, so
 *    the wrap waits for scrolling to settle.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var rail = document.querySelector( '.iflynepal-departure-rail' );
	var nav = document.querySelector( '.iflynepal-rail-nav' );

	if ( ! rail || ! nav ) {
		return;
	}

	var prev = nav.querySelector( '.iflynepal-rail-btn--prev' );
	var next = nav.querySelector( '.iflynepal-rail-btn--next' );
	var cards = Array.prototype.slice.call( rail.querySelectorAll( '.iflynepal-departure' ) );

	if ( ! prev || ! next || cards.length < 2 ) {
		/*
		 * A single card cannot be stepped past or looped, so the buttons stay as
		 * the markup left them: disabled.
		 */
		return;
	}

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var runWidth = 0;
	var looping = false;
	var settleTimer = null;

	/*
	 * The distance one full run of cards occupies, including the gap that
	 * follows the last of them — which is what the first clone is offset by.
	 * Measured rather than calculated from the stylesheet's 314px, so the card
	 * width can change without this file knowing.
	 */
	function measure() {
		var first = cards[ 0 ];
		var clone = rail.querySelector( '.iflynepal-departure--clone' );

		runWidth = clone ? clone.offsetLeft - first.offsetLeft : 0;
	}

	/* One card plus the gap to the next, for a single press. */
	function step() {
		var first = cards[ 0 ].getBoundingClientRect();
		var second = cards[ 1 ].getBoundingClientRect();

		return Math.round( second.left - first.left ) || Math.round( first.width );
	}

	/*
	 * The duplicate run. Clones are decoration for the loop, not content: they
	 * are hidden from assistive technology, and the reveal attribute is stripped
	 * so the animation gate — which has already run by now — cannot leave them
	 * sitting at opacity 0 forever.
	 *
	 * The inline style goes with it. A card caught mid-reveal carries whatever
	 * transform and opacity the tween had written at that instant, and cloning
	 * copies those values as a permanent inline style that no tween will ever
	 * come back to clear — verified in a DOM dump, where every clone had frozen
	 * 26px below its row. The stylesheet is the only thing that should be
	 * positioning a clone.
	 */
	function clone() {
		cards.forEach( function ( card ) {
			var copy = card.cloneNode( true );

			copy.classList.add( 'iflynepal-departure--clone' );
			copy.setAttribute( 'aria-hidden', 'true' );
			copy.removeAttribute( 'data-iflynepal-anim' );
			copy.removeAttribute( 'style' );

			rail.appendChild( copy );
		} );
	}

	/*
	 * The rail is kept inside one run's worth of track — from runWidth up to but
	 * not including 2 x runWidth — and moved back by exactly one run whenever it
	 * leaves that band. The cards either side of each boundary are the same
	 * cards, so the jump is invisible.
	 *
	 * The band is what makes this work, and getting it wrong is what made the
	 * buttons look broken. Wrapping at `>= runWidth` and `<= 0` puts the two
	 * boundaries back to back: the opening position satisfies the first test, so
	 * the rail was sent to 0, which immediately satisfied the second, which sent
	 * it back — a standoff that swallowed every press, because each glide landed
	 * inside a boundary and was pulled straight back to where it started. One
	 * full run of clearance between the two tests is what stops them fighting.
	 */
	function wrap() {
		if ( ! runWidth ) {
			return;
		}

		if ( rail.scrollLeft >= runWidth * 2 ) {
			rail.scrollLeft -= runWidth;
		} else if ( rail.scrollLeft < runWidth ) {
			rail.scrollLeft += runWidth;
		}
	}

	/*
	 * scrollend is the event for "the user or a smooth scroll has finished", and
	 * where it is missing a short idle timer says the same thing. Both are here
	 * because wrapping mid-glide kills the glide.
	 */
	function onSettled( handler ) {
		if ( 'onscrollend' in window ) {
			rail.addEventListener( 'scrollend', handler );

			return;
		}

		rail.addEventListener( 'scroll', function () {
			window.clearTimeout( settleTimer );
			settleTimer = window.setTimeout( handler, 140 );
		}, { passive: true } );
	}

	function glide( direction ) {
		rail.scrollBy( {
			left: direction * step(),
			behavior: reduced ? 'auto' : 'smooth'
		} );
	}

	function start() {
		clone();
		measure();

		if ( ! runWidth ) {
			return;
		}

		/*
		 * Three runs of track, not two. The rail opens one run in and wraps at
		 * two, and it can only ever scroll as far as scrollWidth - clientWidth —
		 * so reaching the forward boundary at all needs a third run to scroll
		 * into. With two, the rail simply runs out of track before the wrap point
		 * and the loop quietly stops looping. Runs are added until the far
		 * boundary is reachable; the ceiling guards against a pathological case
		 * (one narrow card on a very wide screen) turning into thousands of
		 * nodes.
		 */
		while ( rail.scrollWidth - rail.clientWidth < runWidth * 2 && rail.children.length < 60 ) {
			clone();
		}

		looping = true;

		/*
		 * The rail opens one run in, which is the bottom of the band: there is a
		 * whole run behind it for the first press of Previous, and a whole run
		 * ahead before the forward wrap.
		 */
		rail.scrollLeft = runWidth;

		prev.disabled = false;
		next.disabled = false;

		onSettled( wrap );
	}

	prev.addEventListener( 'click', function () {
		glide( -1 );
	} );

	next.addEventListener( 'click', function () {
		glide( 1 );
	} );

	window.addEventListener( 'resize', function () {
		if ( ! looping ) {
			return;
		}

		/*
		 * Card widths and the gap change with the viewport, so the run does too.
		 * Re-measured before the rail is put back to the start of the second run
		 * — carrying an old offset across a resize is what leaves the loop
		 * jumping a card sideways.
		 */
		measure();
		rail.scrollLeft = runWidth;
	} );

	start();
}() );
