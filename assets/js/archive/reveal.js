/**
 * Scroll reveals on a package type archive, and the one measurement the reason
 * cards need.
 *
 * Three jobs, in the order they matter:
 *
 *  1. Measure each reason card's paragraph, so it can open to its own height
 *     rather than to a shared ceiling. This is CSS-driven and has to happen
 *     whether or not anything animates, so it runs before every other guard.
 *  2. Take the reveal gate off when there is nothing to play the elements
 *     forward with — no GSAP, or a stated preference against motion. The gate
 *     is what makes them transparent; leaving it on with no tween coming would
 *     leave the page blank below the hero.
 *  3. Reveal the blocks as they are scrolled to, and tilt the handwritten note
 *     into place.
 *
 * GSAP and ScrollTrigger are the theme's, already on the page: the archive
 * carries the theme's hero component and the theme loads both for it.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var root = document.documentElement;
	var GATE = 'iflynepal-catalogue-anim';

	/* ------------------------------------------ the reason cards' open height
	 *
	 * Each reason opens to its own height rather than to a shared ceiling. The
	 * paragraph is clipped to max-height:0, but scrollHeight still reports the
	 * full laid-out content, so nothing has to be unclipped to measure it — no
	 * reflow the eye can catch, and it is safe to run while a card is open.
	 *
	 * Re-measured on resize, because the line count changes with the column
	 * width, and after the webfonts land, because Poppins wraps differently
	 * from the fallback it replaces.
	 */

	var reasons = Array.prototype.slice.call(
		document.querySelectorAll( '.iflynepal-benefit__body p' )
	);

	if ( reasons.length ) {
		var measureReasons = function () {
			reasons.forEach( function ( paragraph ) {
				paragraph.style.setProperty( '--peek-h', paragraph.scrollHeight + 'px' );
			} );
		};

		measureReasons();

		if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
			document.fonts.ready.then( measureReasons ).catch( function () {} );
		}

		var reasonTimer;

		window.addEventListener( 'resize', function () {
			window.clearTimeout( reasonTimer );
			reasonTimer = window.setTimeout( measureReasons, 150 );
		} );
	}

	/* ----------------------------------------------------------- the gate */

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var hasGsap = typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined';

	if ( ! hasGsap || reduced ) {
		root.classList.remove( GATE );

		return;
	}

	/*
	 * Failsafe. Every hiding rule is scoped under the gate, so if anything
	 * below throws, dropping the class hands the blocks back to CSS and the
	 * page reads normally rather than staying blank.
	 */
	window.addEventListener( 'error', function () {
		root.classList.remove( GATE );
	} );

	var gsap = window.gsap;
	var ScrollTrigger = window.ScrollTrigger;

	gsap.registerPlugin( ScrollTrigger );

	/* --------------------------------------------------------- the reveals */

	var reveals = gsap.utils.toArray( '[data-iflynepal-anim]' );

	if ( reveals.length ) {
		gsap.set( reveals, { y: 26 } );

		// Batched, so blocks that come into view together animate together.
		ScrollTrigger.batch( reveals, {
			start: 'top 88%',
			once: true,
			interval: 0.12,
			batchMax: 6,
			onEnter: function ( batch ) {
				/*
				 * clearProps on the transform is the point: the tween finishes
				 * by writing an inline transform, and an inline transform
				 * outranks the :hover rules the cards rely on — so once a card
				 * had revealed, its lift would never fire again. Opacity has to
				 * stay inline, because the gate rule sets it to 0.
				 */
				gsap.to( batch, {
					opacity: 1,
					y: 0,
					duration: 0.85,
					stagger: 0.08,
					overwrite: true,
					clearProps: 'transform'
				} );
			}
		} );
	}

	/* ------------------------------------------------ the handwritten note */

	gsap.utils.toArray( '.iflynepal-annot' ).forEach( function ( note ) {
		gsap.fromTo(
			note,
			{ opacity: 0, rotate: -6, y: 10 },
			{
				opacity: 1,
				rotate: 0,
				y: 0,
				duration: 0.8,
				ease: 'back.out(1.5)',
				scrollTrigger: { trigger: note, start: 'top 92%', once: true }
			}
		);
	} );

	/*
	 * Images finish loading after the triggers were placed, which moves every
	 * start point below them. One refresh once the page is complete is cheaper
	 * and steadier than watching each image.
	 */
	window.addEventListener( 'load', function () {
		ScrollTrigger.refresh();
	} );
}() );
