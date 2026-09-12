/**
 * The two pieces of motion in the package grid heading.
 *
 *  1. The hand-drawn underline sweeps itself in the first time the heading is
 *     scrolled to.
 *  2. The handwritten note beside it types an ending, holds it, deletes it and
 *     moves to the next, forever.
 *
 * Vanilla, with no GSAP. The theme loads GSAP and ScrollTrigger only on the
 * templates that animate, and the whole of the work here is one
 * IntersectionObserver and a setTimeout chain — a good deal less code than the
 * library it would take to avoid writing it.
 *
 * Both effects are decoration. Neither is the only route to any information,
 * the note is aria-hidden, and both stop dead under prefers-reduced-motion.
 */
( function () {
	'use strict';

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ------------------------------------------------------- the underline */

	var marks = document.querySelectorAll( '.iflynepal-ink-mark' );

	if ( marks.length ) {
		if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
			// No observer, or motion is unwelcome: show the finished stroke.
			Array.prototype.forEach.call( marks, function ( mark ) {
				mark.classList.add( 'is-drawn' );
			} );
		} else {
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
		}
	}

	/* ------------------------------------------------ the handwritten note */

	var note = document.querySelector( '.iflynepal-annot' );

	if ( ! note ) {
		return;
	}

	var staticEl = note.querySelector( '.iflynepal-annot__static' );
	var wordEl = note.querySelector( '.iflynepal-annot__word' );
	var words = [];

	try {
		words = JSON.parse( note.dataset.words || '[]' );
	} catch ( e ) {
		words = [];
	}

	if ( ! staticEl || ! wordEl || ! words.length ) {
		return;
	}

	/*
	 * The tail box has to be wide enough for the LONGEST ending, or the arrow
	 * beside it steps along as a word grows. Guessing that width in em means
	 * guessing at Caveat's metrics, and a guess even slightly short shows as a
	 * shift on every cycle. So measure: render each ending into a hidden probe
	 * carrying the same typography and reserve the widest result.
	 *
	 * Re-run on resize, because the note steps down a font size at the 1000px
	 * breakpoint, and again once the webfont has loaded — Caveat and the
	 * fallback stack do not measure the same.
	 */
	function reserve() {
		var cs = window.getComputedStyle( wordEl );
		var probe = document.createElement( 'span' );
		var widest = 0;

		probe.style.cssText = 'position:absolute;left:-9999px;top:0;visibility:hidden;white-space:pre';
		probe.style.fontFamily = cs.fontFamily;
		probe.style.fontSize = cs.fontSize;
		probe.style.fontWeight = cs.fontWeight;
		probe.style.fontStyle = cs.fontStyle;
		probe.style.letterSpacing = cs.letterSpacing;
		document.body.appendChild( probe );

		words.forEach( function ( word ) {
			probe.textContent = word;
			widest = Math.max( widest, probe.getBoundingClientRect().width );
		} );

		document.body.removeChild( probe );

		// A whole pixel up, so sub-pixel rounding cannot claw the gap back.
		wordEl.style.minWidth = Math.ceil( widest ) + 1 + 'px';
	}

	reserve();
	window.addEventListener( 'resize', reserve );

	if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
		document.fonts.ready.then( reserve ).catch( function () {} );
	}

	// Still, or only one ending to show: leave the first one sitting there.
	if ( reduced || words.length < 2 ) {
		wordEl.textContent = words[ 0 ];

		return;
	}

	var TYPE_MS = 65;
	var DELETE_MS = 40;
	var HOLD_MS = 1800;

	function typeInto( el, text, done ) {
		var i = 0;

		( function step() {
			el.textContent = text.slice( 0, i );

			if ( i >= text.length ) {
				done();

				return;
			}

			i++;
			window.setTimeout( step, TYPE_MS );
		}() );
	}

	function deleteFrom( el, text, done ) {
		var i = text.length;

		( function step() {
			el.textContent = text.slice( 0, i );

			if ( i <= 0 ) {
				done();

				return;
			}

			i--;
			window.setTimeout( step, DELETE_MS );
		}() );
	}

	function cycle( index ) {
		var word = words[ index % words.length ];

		typeInto( wordEl, word, function () {
			window.setTimeout( function () {
				deleteFrom( wordEl, word, function () {
					cycle( index + 1 );
				} );
			}, HOLD_MS );
		} );
	}

	/*
	 * The fixed part types once and then stays, exactly as the design has it —
	 * it is never touched again, not its text and not its box, which is what
	 * keeps everything to the left of the ending still while a word grows.
	 *
	 * Read off the markup rather than written here, so the copy stays the
	 * editor's and the trailing non-breaking space travels with it.
	 */
	var staticText = staticEl.textContent;

	wordEl.textContent = '';
	staticEl.textContent = '';

	typeInto( staticEl, staticText, function () {
		cycle( 0 );
	} );
}() );
