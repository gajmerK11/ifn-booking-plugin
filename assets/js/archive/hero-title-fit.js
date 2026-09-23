/**
 * Shrinks a package-type/category archive hero's heading to whatever font-size
 * — up to catalogue.css's own clamp() ceiling — keeps it inside two lines.
 *
 * catalogue.css sizes the heading for the viewport, not for the term's own
 * heading length, so a short one sits well inside two lines at that size
 * while a long one runs to three or four. There is no way to ask CSS alone
 * for "the largest size that still fits N lines" — line count is exactly the
 * one thing font-size and text-wrap interact to produce, which is why this
 * has to measure.
 *
 * Measures on a detached, off-screen CLONE of the heading's own wrapper
 * rather than reading the live heading's rendered geometry back. Three other
 * approaches were tried first and each reads this particular heading wrong:
 *
 *  - scrollHeight ÷ line-height, on the live heading: the ratio sits at
 *    2.2-2.3 whether the heading is genuinely 2 lines or 4 — Math.round()
 *    reads that as "2" almost regardless of the real count, so the shrink
 *    loop's own stop condition was true before it ever ran.
 *  - Range.getClientRects(), grouped by row: the shine-effect <em> and the
 *    word-by-word entrance spans (assets/js/archive/reveal.js wraps every
 *    word in its own span for that stagger) are inline-block, and each
 *    contributes its own rect a few px off the surrounding text's baseline —
 *    real rows get split into two, and the loop over-shrinks every time.
 *  - A <canvas> measureText() simulation of the browser's own line-break:
 *    accurate for one word at a time, but letter-spacing here is negative
 *    (-0.02em), and summing many separately-measured word/space fragments
 *    each carries its own trailing letter-spacing penalty that a single
 *    continuous run does not — over a ten-word heading that alone under-
 *    measured the real rendered width by around 50px.
 *
 * A clone sidesteps every one of those, because it never reads the live
 * heading's own rendered boxes at all — it is handed a plain-text copy of
 * the same content (word-spans and the shine <em> decoration stripped back
 * to the text they wrap) and left to lay itself out fresh in the browser's
 * own engine, off-screen. What comes back is what the browser genuinely
 * thinks that text does at that size, not an approximation of it.
 *
 * The clone keeps the heading's own class names and its wrapper's, rather
 * than having its width or font copied over property by property — a
 * property list is exactly the kind of thing that quietly stops matching
 * the first time catalogue.css changes near this heading. Carrying the real
 * classes means every rule that would apply to the live heading applies to
 * the clone the same way, automatically, because it is the same cascade.
 *
 * An editor's own `<br>` in the Heading field (Package Types admin screen,
 * carried through by iflynepal_booking_kses_text()) is not fought here — the
 * clone reflects it like any other line break, so a manual break is just one
 * more constraint the browser's own layout measures against and fits
 * around, however many an editor adds or wherever they put them.
 *
 * @package IFly_Nepal_Booking
 * @since   1.0.0
 */

( function () {
	'use strict';

	var heading = document.querySelector( '.iflynepal-hero__title' );

	if ( ! heading ) {
		return;
	}

	var MAX_LINES = 2;

	/*
	 * A floor rather than a target: below this a heading simply keeps
	 * whatever line count it has rather than shrinking to something
	 * unreadably small chasing two rows that never arrive. Set well under
	 * the mobile clamp's own fixed 46px so it only ever bites on a
	 * genuinely long heading, not a normal one.
	 */
	var MIN_FONT_SIZE = 28;

	/*
	 * Flattens an element's current child nodes down to plain text, `<br>`
	 * and `<em>` — dropping every other wrapper (the word-stagger `<span>`
	 * reveal.js adds) down to just the text it holds, by walking into it
	 * rather than keeping it. This is what the clone is built from, so its
	 * layout reflects only what actually affects wrapping: the words, the
	 * forced breaks and the one differently-styled run, none of the
	 * decoration that made measuring the live heading unreliable.
	 *
	 * @param {Node} root
	 * @return {string}
	 */
	function plainMarkup( root ) {
		var html = '';

		root.childNodes.forEach( function ( node ) {
			if ( Node.TEXT_NODE === node.nodeType ) {
				html += node.textContent;
			} else if ( 'BR' === node.nodeName ) {
				html += '<br>';
			} else if ( 'EM' === node.nodeName ) {
				html += '<em>' + node.textContent + '</em>';
			} else if ( Node.ELEMENT_NODE === node.nodeType ) {
				html += plainMarkup( node );
			}
		} );

		return html;
	}

	function fit() {
		// Clears any size a previous call left, so this always measures against
		// catalogue.css's own clamp() value for the current viewport.
		heading.style.removeProperty( 'font-size' );

		var wrapper = heading.closest( '.iflynepal-hero__copy' ) || heading.parentElement;

		if ( ! wrapper ) {
			return;
		}

		var clone = wrapper.cloneNode( true );

		clone.style.position   = 'absolute';
		clone.style.visibility = 'hidden';
		clone.style.left       = '-99999px';
		clone.style.top        = '0';
		clone.style.transform  = 'none';

		var cloneHeading = clone.querySelector( '.iflynepal-hero__title' );

		if ( ! cloneHeading ) {
			return;
		}

		// Only the heading's own width is needed — the lead paragraph and the
		// buttons beside it in this wrapper cost a reflow for nothing here.
		Array.prototype.slice.call( clone.children ).forEach( function ( child ) {
			if ( child !== cloneHeading ) {
				clone.removeChild( child );
			}
		} );

		cloneHeading.innerHTML = plainMarkup( heading );

		document.body.appendChild( clone );

		/*
		 * See the identical function in the theme's own hero-title-fit.js: the
		 * heading's height never lands on a clean N × line-height, so this
		 * rounds the ratio rather than comparing it against a strict
		 * threshold. Reliable here in a way it was not on the live heading,
		 * because the clone carries none of the inline-block decoration that
		 * threw that reading off.
		 */
		function linesAt( size ) {
			cloneHeading.style.setProperty( 'font-size', size + 'px', 'important' );

			var lineHeight = parseFloat( getComputedStyle( cloneHeading ).lineHeight );

			return Math.round( cloneHeading.scrollHeight / lineHeight );
		}

		var fontSize = parseFloat( getComputedStyle( heading ).fontSize );

		while ( linesAt( fontSize ) > MAX_LINES && fontSize > MIN_FONT_SIZE ) {
			fontSize -= 1;
		}

		document.body.removeChild( clone );

		/*
		 * catalogue.css sets this heading's font-size with !important — it has
		 * to, to reliably outrank the theme's own .iflynepal-hero__title rule
		 * of equal specificity. An inline !important is the one thing that
		 * outranks a stylesheet !important, so the size set here has to carry
		 * it too, or it is silently overridden straight back to the clamp().
		 */
		heading.style.setProperty( 'font-size', fontSize + 'px', 'important' );
	}

	fit();

	var resizeTimer;

	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( fit, 150 );
	} );
}() );
