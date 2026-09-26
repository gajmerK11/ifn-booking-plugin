/**
 * The single package page.
 *
 * Behaviour for the design's interactive parts: the gallery lightbox, the sticky
 * section nav with its reading progress, the itinerary's view switch and
 * accordions, the calendar booker and the mobile booking bar. The
 * similar-packages section is a static card grid, the same one the catalogue
 * uses, and needs nothing here.
 *
 * Everything here is an enhancement over a page that is already complete. With
 * this file absent the photographs, the whole itinerary, the price, what is
 * included and every FAQ are all in the document and readable.
 *
 * THE CALENDAR HOLDS NOTHING. Any date can be picked, the total is arithmetic in
 * the browser, and there is no availability, capacity or reservation behind any
 * of it — the client's explicit and repeated instruction for this plugin. What
 * the booker produces is a quotation, not a booking.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var root = document.querySelector( '.iflynepal-package' );

	if ( ! root ) {
		return;
	}

	var strings = window.iflynepalPackage || {};
	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function id( name ) {
		return document.getElementById( 'ifnpkg-' + name );
	}

	/* --------------------------------------------------------- header height */

	/*
	 * --ifnpkg-hdr (package.css) is a constant tuned to the theme header's
	 * usual height, and everything that clears the header — the page's own
	 * top padding, the sticky side nav and price card, the section-nav
	 * click scroll — is calc()'d from it. A real header even a little
	 * taller or shorter than that guess (signed in with the admin bar
	 * present, a font swap, anything) leaves either a gap or, as here, page
	 * content riding up under the header instead of clearing it. The
	 * header's own real height, read off the page itself and kept in sync
	 * on resize, replaces the guess everywhere at once because every one
	 * of those rules reads the same custom property.
	 */
	( function syncHeaderHeight() {
		var header = document.getElementById( 'iflynepal-header' );

		if ( ! header ) {
			return;
		}

		function sync() {
			document.documentElement.style.setProperty( '--ifnpkg-hdr', header.getBoundingClientRect().height + 'px' );
		}

		sync();
		window.addEventListener( 'resize', sync );
		window.addEventListener( 'load', sync );
	}() );

	/* -------------------------------------------------------- featured video */

	( function featuredVideo() {
		var wrap = root.querySelector( '[data-iflynepal-video]' );

		if ( ! wrap ) {
			return;
		}

		var media = wrap.querySelector( '[data-iflynepal-video-media]' );
		var toggle = wrap.querySelector( '[data-iflynepal-video-toggle]' );

		if ( ! media || ! toggle ) {
			return;
		}

		/*
		 * The button follows the video, never the other way about. Autoplay is a
		 * request, not a guarantee — a browser saving data, or one honouring a
		 * reduced-motion setting, simply does not start — so the state is read
		 * off the element's own play and pause events. Setting the icon at the
		 * moment of the click would show a pause bar over a video that never
		 * began.
		 */
		function sync() {
			var playing = ! media.paused && ! media.ended;
			var label = playing ? toggle.dataset.labelPause : toggle.dataset.labelPlay;

			wrap.classList.toggle( 'iflynepal-pkg-is-playing', playing );
			toggle.setAttribute( 'aria-pressed', playing ? 'true' : 'false' );

			if ( label ) {
				toggle.setAttribute( 'aria-label', label );
			}
		}

		toggle.addEventListener( 'click', function () {
			if ( media.paused || media.ended ) {
				/*
				 * play() rejects when the browser refuses — an unhandled rejection
				 * in the console reads as a broken player. There is nothing to do
				 * about a refusal but leave the button showing play, which sync()
				 * on the pause event already does.
				 */
				var started = media.play();

				if ( started && 'function' === typeof started.catch ) {
					started.catch( sync );
				}

				return;
			}

			media.pause();
		} );

		media.addEventListener( 'play', sync );
		media.addEventListener( 'pause', sync );
		media.addEventListener( 'ended', sync );

		sync();
	}() );

	/* ---------------------------------------------------------- map title */

	( function mapTitleFit() {
		var heading = document.querySelector( '#ifnpkg-map h2' );

		if ( ! heading ) {
			return;
		}

		/*
		 * The map heading is an editor's own free text (map_heading), so it
		 * has no fixed length the way "Overview" or "FAQs" do — the design's
		 * band heading is sized to read as a single line, and a long one
		 * (e.g. "Everest Base Camp Trek Map & Elevation") otherwise wraps to
		 * two at the clamp()'d size .iflynepal-package h2 shares site-wide.
		 * Shrinking only this heading, not that shared rule, keeps every
		 * other band's heading at its full size.
		 */
		var MIN_FONT_SIZE = 18;

		function fit() {
			heading.style.removeProperty( 'font-size' );
			heading.style.whiteSpace = 'nowrap';

			var fontSize = parseFloat( getComputedStyle( heading ).fontSize );

			while ( heading.scrollWidth > heading.clientWidth && fontSize > MIN_FONT_SIZE ) {
				fontSize -= 1;
				heading.style.setProperty( 'font-size', fontSize + 'px' );
			}

			/*
			 * Still too long at the floor size: an ordinary two-line wrap
			 * reads better than either clipping or a title shrunk unreadably
			 * small.
			 */
			if ( heading.scrollWidth > heading.clientWidth ) {
				heading.style.whiteSpace = 'normal';
			}
		}

		fit();

		var resizeTimer;
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( fit, 150 );
		} );
	}() );

	/* -------------------------------------------------------------- price */

	( function priceFit() {
		var rows = root.querySelectorAll( '.iflynepal-pkg-price-from' );

		if ( ! rows.length ) {
			return;
		}

		/*
		 * Same technique as mapTitleFit() above, aimed at the price figure
		 * instead of a heading: the row is fixed to one line (CSS
		 * flex-wrap:nowrap, every child flex-shrink:0), sized for English's
		 * short "/ person". A translation's unit word can run longer — French
		 * "/ personne" — with nothing left in the row able to give, so it
		 * overflowed past the price card's own overflow:hidden and read as
		 * clipped text. Shrinking the figure (not the unit label, which is
		 * already the smaller of the two) buys the row back its width.
		 */
		var MIN_FONT_SIZE = 22;

		function fit( row ) {
			var figure = row.querySelector( 'strong' );

			if ( ! figure ) {
				return;
			}

			figure.style.removeProperty( 'font-size' );

			var fontSize = parseFloat( getComputedStyle( figure ).fontSize );

			while ( row.scrollWidth > row.clientWidth && fontSize > MIN_FONT_SIZE ) {
				fontSize -= 1;
				figure.style.setProperty( 'font-size', fontSize + 'px' );
			}
		}

		function fitAll() {
			Array.prototype.forEach.call( rows, fit );
		}

		fitAll();

		var resizeTimer;
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( fitAll, 150 );
		} );
	}() );

	/* ------------------------------------------------------------ lightbox */

	( function gallery() {
		var box = id( 'lightbox' );
		var source = id( 'photos' );
		var tiles = root.querySelectorAll( '.iflynepal-pkg-g-tile, .iflynepal-pkg-g-all, .iflynepal-pkg-map-photo' );

		if ( ! box || ! source || ! tiles.length ) {
			return;
		}

		var photos = Array.prototype.map.call( source.content.querySelectorAll( 'span' ), function ( item ) {
			return {
				full: item.dataset.full,
				thumb: item.dataset.thumb,
				alt: item.dataset.alt || ''
			};
		} );

		if ( ! photos.length ) {
			return;
		}

		var img = id( 'lb-img' );
		var cap = id( 'lb-cap' );
		var count = id( 'lb-count' );
		var thumbs = id( 'lb-thumbs' );
		var figure = img.parentElement;
		var current = 0;
		var lastFocus = null;

		/*
		 * The photo is already the full-size original — lightbox.php's own
		 * `data-full` is wp_get_attachment_image_url( ..., 'full' ) — so
		 * "zoom" needs no second, larger image to load: it is the same file,
		 * just no longer scaled down to fit the stage. is-zoomed lifts that
		 * scaling in the stylesheet and turns on scrolling to pan it.
		 */
		img.addEventListener( 'click', function () {
			figure.classList.toggle( 'is-zoomed' );
		} );

		photos.forEach( function ( photo, index ) {
			var button = document.createElement( 'button' );
			var thumb = document.createElement( 'img' );

			button.type = 'button';
			button.setAttribute( 'aria-label', ( strings.showPhoto || 'Show photo %d' ).replace( '%d', index + 1 ) );
			thumb.src = photo.thumb;
			thumb.alt = '';
			thumb.loading = 'lazy';

			button.appendChild( thumb );
			button.addEventListener( 'click', function () {
				show( index );
			} );

			thumbs.appendChild( button );
		} );

		function show( index ) {
			current = ( index + photos.length ) % photos.length;

			// A new photo opens fit-to-screen; carrying the last one's zoom
			// over would pan straight to whatever corner it was scrolled to.
			figure.classList.remove( 'is-zoomed' );

			img.src = photos[ current ].full;
			img.alt = photos[ current ].alt;
			cap.textContent = photos[ current ].alt;
			count.textContent = ( current + 1 ) + ' / ' + photos.length;

			Array.prototype.forEach.call( thumbs.children, function ( thumb, n ) {
				thumb.setAttribute( 'aria-current', n === current ? 'true' : 'false' );

				if ( n !== current ) {
					return;
				}

				/*
				 * Keep the marked thumbnail in view. The strip scrolls once there
				 * are more photographs than fit, and paging with the arrows would
				 * otherwise mark one that has scrolled off the end.
				 */
				thumbs.scrollBy( {
					left: thumb.getBoundingClientRect().left
						- thumbs.getBoundingClientRect().left
						- ( ( thumbs.clientWidth - thumb.offsetWidth ) / 2 ),
					behavior: 'smooth'
				} );
			} );
		}

		function open( index ) {
			lastFocus = document.activeElement;
			box.hidden = false;

			/*
			 * The page behind a modal must not scroll under it. The class goes on
			 * <body>, which is where the stylesheet's rule is: `overflow: hidden`
			 * there is propagated to the viewport as long as <html> is visible,
			 * which is what makes this the ordinary way to lock a page.
			 */
			document.body.classList.add( 'iflynepal-pkg-lb-lock' );
			show( index );

			/*
			 * The panel is opacity:0 until `is-open`, so un-hiding it alone opens
			 * a transparent sheet over the whole page — every gallery tile read as
			 * doing nothing, and the page stopped taking clicks until Escape.
			 *
			 * The class is added on the next frame, not this one. Setting `hidden`
			 * and the class together gives the browser one style resolution to do
			 * both in, so there is no earlier value to animate from and the fade
			 * is skipped.
			 */
			requestAnimationFrame( function () {
				box.classList.add( 'iflynepal-pkg-is-open' );
			} );

			id( 'lb-close' ).focus();
		}

		function close() {
			box.classList.remove( 'iflynepal-pkg-is-open' );
			document.body.classList.remove( 'iflynepal-pkg-lb-lock' );

			// `hidden` waits for the fade out; the stylesheet's transition is .35s.
			window.setTimeout( function () {
				box.hidden = true;
			}, 300 );

			if ( lastFocus ) {
				lastFocus.focus();
			}
		}

		Array.prototype.forEach.call( tiles, function ( tile ) {
			/*
			 * A tile with no data-index is the featured video, which plays where
			 * it is rather than opening anything — and its own controls are inside
			 * it, so a lightbox on click would fire every time somebody pressed
			 * pause.
			 */
			if ( ! tile.hasAttribute( 'data-index' ) ) {
				return;
			}

			tile.addEventListener( 'click', function () {
				open( parseInt( tile.dataset.index, 10 ) || 0 );
			} );
		} );

		id( 'lb-close' ).addEventListener( 'click', close );
		id( 'lb-prev' ).addEventListener( 'click', function () {
			show( current - 1 );
		} );
		id( 'lb-next' ).addEventListener( 'click', function () {
			show( current + 1 );
		} );

		box.addEventListener( 'click', function ( event ) {
			// The backdrop closes; the photograph and the buttons do not.
			if ( event.target === box || event.target.classList.contains( 'iflynepal-pkg-lb-stage' ) ) {
				close();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( box.hidden ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				close();
			} else if ( 'ArrowLeft' === event.key ) {
				show( current - 1 );
			} else if ( 'ArrowRight' === event.key ) {
				show( current + 1 );
			}
		} );
	}() );

	/* ------------------------------------------------------------- sharing */

	( function share() {
		var wrap = id( 'share' );

		if ( ! wrap ) {
			return;
		}

		var copy = wrap.querySelector( '[data-share="copy"]' );

		if ( ! copy || ! navigator.clipboard ) {
			return;
		}

		copy.addEventListener( 'click', function () {
			navigator.clipboard.writeText( wrap.dataset.url || window.location.href ).then( function () {
				copy.classList.add( 'iflynepal-pkg-is-copied' );

				window.setTimeout( function () {
					copy.classList.remove( 'iflynepal-pkg-is-copied' );
				}, 1600 );
			} );
		} );
	}() );

	/* ------------------------------------------- section nav + booking bar */

	( function sectionNav() {
		var nav = id( 'side-nav' );
		var bar = id( 'book-bar' );
		var card = id( 'price-card' );
		var links = nav ? Array.prototype.slice.call( nav.querySelectorAll( 'a' ) ) : [];
		var sections = links.map( function ( link ) {
			return document.getElementById( link.getAttribute( 'data-iflynepal-scroll' ) );
		} ).filter( Boolean );

		/*
		 * A nav link scrolls to a precisely measured spot rather than
		 * trusting html's scroll-padding-top (package.css) to have the
		 * right numbers for the moment it is clicked: --ifnpkg-hdr and
		 * --ifnpkg-tabs are constants tuned to the header's and the docked
		 * nav strip's usual heights, and either one a little taller or
		 * shorter than its guess — a font that hasn't finished loading, a
		 * border or padding the constant did not account for, a browser
		 * zoom level — is exactly enough to leave a sliver of the section
		 * above peeking out. This reads both elements' real, current
		 * height off the page itself instead, so it is right regardless of
		 * why either constant might not be.
		 *
		 * --ifnpkg-tabs is still what says WHETHER the nav is currently the
		 * docked strip (nonzero, below ~1100px — see .iflynepal-pkg-side-nav
		 * in package.css) or the desktop sidebar (zero): the sidebar's own
		 * height is the whole column's, not a strip to clear, so it must
		 * never be added in even though `nav` is the same element and
		 * `position:sticky` either way.
		 */
		var header = document.getElementById( 'iflynepal-header' );

		function scrollOffset() {
			var docked = parseFloat( getComputedStyle( document.documentElement ).getPropertyValue( '--ifnpkg-tabs' ) ) > 0;
			var hdr = header ? header.getBoundingClientRect().height : 0;
			var tabs = docked && nav ? nav.getBoundingClientRect().height : 0;

			/*
			 * Landing a few pixels PAST flush — tucking the target's own
			 * top edge behind the bar rather than trying to stop exactly at
			 * it. Every section but the first carries a 1px border-top
			 * divider (.iflynepal-pkg-t-section) right at that edge, and
			 * "exactly at it" is razor-thin: the least measurement error in
			 * either direction either leaves that hairline showing in open
			 * space or a sliver of the section above it. Landing short is
			 * safe on this side, because it disappears under the bar's own
			 * solid background instead of being visible either way — and
			 * there is 64px of the section's own top padding before its
			 * heading to spend on that margin before hiding anything a
			 * visitor would actually read.
			 */
			return hdr + tabs - 10;
		}

		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				var target = document.getElementById( link.getAttribute( 'data-iflynepal-scroll' ) );

				if ( ! target ) {
					return;
				}

				// Handled here in full: the global anchor-scroll.js glide is not also needed.
				event.preventDefault();
				event.stopPropagation();

				window.scrollTo( {
					top: target.getBoundingClientRect().top + window.scrollY - scrollOffset(),
					behavior: reduced ? 'auto' : 'smooth'
				} );
			} );
		} );

		function onScroll() {
			if ( sections.length ) {
				/*
				 * The section whose top has most recently passed the docking
				 * line is the one being read. Measured on scroll rather than
				 * with an observer because the answer is "which is nearest",
				 * which an observer cannot give directly.
				 */
				var line = 140;
				var active = 0;

				sections.forEach( function ( section, index ) {
					if ( section.getBoundingClientRect().top <= line ) {
						active = index;
					}
				} );

				links.forEach( function ( link, index ) {
					var on = index === active;

					link.classList.toggle( 'iflynepal-pkg-is-active', on );

					if ( on ) {
						link.setAttribute( 'aria-current', 'true' );
					} else {
						link.removeAttribute( 'aria-current' );
					}
				} );
			}

			if ( bar && card ) {
				/*
				 * The bar appears once the price card has scrolled away, and
				 * hides again over the booker itself — where the same numbers
				 * and the same button are already on screen.
				 */
				var booker = id( 'booker' );
				var past = card.getBoundingClientRect().bottom < 0;
				var atBooker = booker && booker.getBoundingClientRect().top < window.innerHeight && booker.getBoundingClientRect().bottom > 0;
				var on = past && ! atBooker;

				bar.classList.toggle( 'iflynepal-pkg-is-on', on );
				bar.setAttribute( 'aria-hidden', on ? 'false' : 'true' );

				var action = bar.querySelector( 'a' );

				if ( action ) {
					// Out of the tab order while it is off screen.
					action.tabIndex = on ? 0 : -1;
				}
			}
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll );
		onScroll();
	}() );

	/* ----------------------------------------------------------- itinerary */

	( function itinerary() {
		var full = id( 'panel-full' );
		var short = id( 'panel-short' );
		var tabFull = id( 'tab-full' );
		var tabShort = id( 'tab-short' );
		var expand = id( 'expand-all' );
		var days = full ? Array.prototype.slice.call( full.querySelectorAll( '.iflynepal-pkg-day' ) ) : [];

		days.forEach( function ( day ) {
			var toggle = day.querySelector( '.iflynepal-pkg-day-toggle' );

			if ( ! toggle ) {
				return;
			}

			toggle.addEventListener( 'click', function () {
				var open = day.classList.toggle( 'iflynepal-pkg-is-open' );

				toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				syncExpand();
			} );
		} );

		function syncExpand() {
			if ( ! expand ) {
				return;
			}

			var allOpen = days.every( function ( day ) {
				return day.classList.contains( 'iflynepal-pkg-is-open' );
			} );

			expand.setAttribute( 'aria-expanded', allOpen ? 'true' : 'false' );
			expand.textContent = allOpen ? ( strings.collapseAll || 'Collapse all' ) : ( strings.expandAll || 'Expand all' );
		}

		if ( expand ) {
			expand.addEventListener( 'click', function () {
				var open = 'true' !== expand.getAttribute( 'aria-expanded' );

				days.forEach( function ( day ) {
					day.classList.toggle( 'iflynepal-pkg-is-open', open );
					day.querySelector( '.iflynepal-pkg-day-toggle' ).setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				} );

				syncExpand();
			} );
		}

		if ( ! tabFull || ! tabShort || ! short ) {
			return;
		}

		var segFill = id( 'seg-fill' );

		/*
		 * The fill is one surface that glides from one button's rect to the
		 * other's rather than each button fading its own background in and
		 * out — the "water flow" the switch is meant to read as. skipTransition
		 * is for the very first paint, so the pill appears already in place
		 * instead of growing in from nothing.
		 */
		function moveFill( button, skipTransition ) {
			if ( ! segFill ) {
				return;
			}

			if ( skipTransition ) {
				segFill.style.transition = 'none';
			}

			segFill.style.width = button.offsetWidth + 'px';
			segFill.style.transform = 'translateX(' + button.offsetLeft + 'px)';

			if ( skipTransition ) {
				segFill.offsetHeight; // eslint-disable-line no-unused-expressions -- forces the reflow that makes the transition:none above actually apply before it is cleared.
				segFill.style.transition = '';
			}
		}

		function select( wantFull ) {
			tabFull.setAttribute( 'aria-selected', wantFull ? 'true' : 'false' );
			tabShort.setAttribute( 'aria-selected', wantFull ? 'false' : 'true' );
			tabFull.tabIndex = wantFull ? 0 : -1;
			tabShort.tabIndex = wantFull ? -1 : 0;
			full.hidden = ! wantFull;
			short.hidden = wantFull;
			moveFill( wantFull ? tabFull : tabShort );

			if ( expand ) {
				expand.hidden = ! wantFull;
			}
		}

		moveFill( tabShort, true );
		window.addEventListener( 'resize', function () {
			moveFill( 'true' === tabFull.getAttribute( 'aria-selected' ) ? tabFull : tabShort, true );
		} );

		tabFull.addEventListener( 'click', function () {
			select( true );
		} );
		tabShort.addEventListener( 'click', function () {
			select( false );
		} );

		/*
		 * Left and right move between tabs, which is what a tablist owes a
		 * keyboard. The directions follow the order the tabs are rendered in —
		 * short itinerary first, day by day second — so right goes to day by day.
		 */
		[ tabShort, tabFull ].forEach( function ( tab ) {
			tab.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowRight' === event.key ) {
					select( true );
					tabFull.focus();
				} else if ( 'ArrowLeft' === event.key ) {
					select( false );
					tabShort.focus();
				}
			} );
		} );

		syncExpand();
	}() );

	/* -------------------------------------------------------------- booker */

	( function booker() {
		var wrap = id( 'booker' );

		if ( ! wrap ) {
			return;
		}

		var grid = id( 'cal-grid' );
		var label = id( 'cal-month' );
		var quick = id( 'cal-quick' );
		var price = parseFloat( wrap.dataset.price ) || 0;
		var currency = wrap.dataset.currency || 'USD';
		/*
		 * The trip's length in days, set on the package. The run is what the
		 * visitor picks: a start date, and this many days from it. Floored at one
		 * so a package with nothing typed in the field still picks a single day
		 * rather than a run of none.
		 */
		var days = Math.max( 1, parseInt( wrap.dataset.days, 10 ) || 1 );
		/*
		 * The group-size price ladder, read from the same list the price card
		 * was rendered from rather than scraped back out of it: the card is a
		 * view of this, not the source of it. Empty on a package priced at one
		 * rate for everybody, in which case priceFor() below always answers the
		 * flat price and nothing about the summary changes.
		 *
		 * Parsed defensively — a malformed attribute costs the ladder, not the
		 * whole booker, and a booker that throws here would stop the calendar
		 * drawing at all.
		 */
		var tiers = ( function () {
			try {
				var parsed = JSON.parse( wrap.dataset.tiers || '[]' );

				return Array.isArray( parsed ) ? parsed : [];
			} catch ( e ) {
				return [];
			}
		}() );
		var tierRows = [].slice.call( document.querySelectorAll( '#ifnpkg-price-tiers .iflynepal-pkg-price-tier' ) );
		var today = new Date();
		var view = new Date( today.getFullYear(), today.getMonth(), 1 );
		var chosen = null;
		/*
		 * Client-directed, 13 Sep 2026: nobody is assumed. A visitor has to say
		 * how many people are travelling, the same way they have to say when —
		 * neither is a sensible default to pick for them, and the booking
		 * button stays locked until both are answered (see total() below).
		 */
		var pax = 0;

		today.setHours( 0, 0, 0, 0 );

		function money( amount ) {
			return currency + ' ' + amount.toFixed( 2 ).replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		}

		/*
		 * The page's own language, not the visitor's browser locale —
		 * toLocaleDateString( undefined, … ) reads the latter, which stays
		 * English for a French visitor on an English-language OS, silently
		 * disagreeing with the French text everywhere else on the page.
		 * document.documentElement.lang is the html lang="fr-FR" WordPress
		 * already sets from Polylang's current language, so this simply
		 * agrees with it instead of guessing again.
		 */
		var pageLocale = document.documentElement.lang || undefined;

		function longDate( date ) {
			return date.toLocaleDateString( pageLocale, { day: 'numeric', month: 'short', year: 'numeric' } );
		}

		/**
		 * The last day of the run that starts on a given date.
		 *
		 * Derived every time it is wanted and never stored: a stored end date is
		 * a second copy of the same fact, and the two come apart the first time
		 * somebody changes the duration on the package.
		 *
		 * setDate() past the end of a month rolls into the next one by itself, so
		 * a five-day run from 30 January ends on 3 February with no arithmetic of
		 * ours — and it handles leap days and the ends of years the same way.
		 *
		 * @param {Date} start First day of the run.
		 * @return {Date} Last day of the run.
		 */
		function runEnd( start ) {
			var last = new Date( start.getTime() );

			last.setDate( last.getDate() + days - 1 );

			return last;
		}

		/**
		 * Whether a date can start a run.
		 *
		 * The one rule is that a trip cannot begin in the past. There is nothing
		 * else to test: no availability, no capacity and no inventory exists
		 * behind this calendar, by instruction — what it produces is a quotation.
		 *
		 * @param {Date} date Candidate start date.
		 * @return {boolean} True when it may be picked.
		 */
		function selectable( date ) {
			return date >= today;
		}

		function draw() {
			var year = view.getFullYear();
			var month = view.getMonth();
			var first = new Date( year, month, 1 );
			// How many days this month has — `days` is the trip's length, not this.
			var count = new Date( year, month + 1, 0 ).getDate();
			/* Monday-first, which is how the design's grid reads. */
			var lead = ( first.getDay() + 6 ) % 7;

			label.textContent = view.toLocaleDateString( pageLocale, { month: 'long', year: 'numeric' } );
			grid.textContent = '';

			/*
			 * `iflynepal-pkg-dow`, which is the class the stylesheet carries.
			 * This read `cal-dow` and so the weekday row had no styles at all —
			 * the kind of miss that looks like a missing rule rather than a
			 * misspelt one.
			 */
			( strings.weekdays || [ 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' ] ).forEach( function ( name ) {
				var head = document.createElement( 'span' );

				head.className = 'iflynepal-pkg-dow';
				head.textContent = name;
				grid.appendChild( head );
			} );

			for ( var blank = 0; blank < lead; blank++ ) {
				grid.appendChild( document.createElement( 'span' ) );
			}

			var last = chosen ? runEnd( chosen ) : null;

			for ( var day = 1; day <= count; day++ ) {
				( function ( dayNumber ) {
					var date = new Date( year, month, dayNumber );
					var cell = document.createElement( 'button' );

					cell.type = 'button';
					cell.className = 'iflynepal-pkg-cal-day';
					cell.textContent = dayNumber;

					if ( date.getTime() === today.getTime() ) {
						cell.classList.add( 'iflynepal-pkg-is-today' );
					}

					/*
					 * A date in the past is the only one that is refused, and
					 * that is arithmetic rather than availability: nothing else
					 * is checked, because nothing else is known.
					 */
					if ( ! selectable( date ) ) {
						cell.disabled = true;
					} else {
						cell.addEventListener( 'click', function () {
							/*
							 * Every click sets the START of the run. The end is
							 * derived from the trip's duration, so there is no
							 * second pick to make and no way to ask for more days
							 * than the package is: clicking inside a highlighted
							 * run simply moves the run to that day.
							 */
							chosen = date;
							draw();
							total();
						} );
					}

					if ( chosen && date.getTime() === chosen.getTime() ) {
						cell.classList.add( 'iflynepal-pkg-is-start' );
						cell.setAttribute( 'aria-current', 'date' );
					}

					if ( last && date.getTime() === last.getTime() ) {
						cell.classList.add( 'iflynepal-pkg-is-end' );
					}

					// The days between the two ends, which is what shows the length.
					if ( chosen && last && date > chosen && date < last ) {
						cell.classList.add( 'iflynepal-pkg-is-range' );
					}

					grid.appendChild( cell );
				}( day ) );
			}

			/*
			 * Nothing before this month can be picked, so the way back to it is
			 * closed rather than left to be pressed into a grid of dead cells.
			 */
			id( 'cal-prev' ).disabled =
				view.getFullYear() === today.getFullYear() && view.getMonth() === today.getMonth();
		}

		/*
		 * Which rung a traveller count falls on, and what a head costs there.
		 * A count outside every rung still gets one: below the first rung's
		 * floor — including the zero everybody starts at — the first rung
		 * stands, and above the last rung's ceiling the last one does. The same
		 * rule as iflynepal_package_price_tier_for_pax() in PHP, because both
		 * answer the same question about the same ladder.
		 */
		function tierFor( count ) {
			var i;
			var last;

			if ( ! tiers.length ) {
				return null;
			}

			for ( i = 0; i < tiers.length; i++ ) {
				if ( count >= tiers[ i ].from && ( 0 === tiers[ i ].to || count <= tiers[ i ].to ) ) {
					return i;
				}
			}

			last = tiers[ tiers.length - 1 ];

			return last.to > 0 && count > last.to ? tiers.length - 1 : 0;
		}

		function priceFor( count ) {
			var i = tierFor( count );

			return null === i ? price : parseFloat( tiers[ i ].price ) || price;
		}

		/* Moves the large figure on the price card onto the rung now being quoted. */
		function markTier( count ) {
			var active = tierFor( count );

			tierRows.forEach( function ( row, i ) {
				row.classList.toggle( 'iflynepal-pkg-is-active', i === active );
			} );
		}

		function total() {
			var start = id( 'sum-start' );
			var end = id( 'sum-end' );
			var each = id( 'sum-each' );
			var paxOut = id( 'sum-pax' );
			var sum = id( 'sum-total' );
			var button = id( 'book-btn' );
			var note = id( 'book-note' );
			var pay = id( 'pay-aside' );
			var payNote = id( 'pay-note' );
			var paxMinus = id( 'pax-minus' );
			/*
			 * Both answered, not just one. A date with nobody travelling, or a
			 * traveller count with no date, is not a bookable trip either way —
			 * derived fresh on every call rather than stored, because pax can
			 * now go back down to zero and the button has to re-lock with it.
			 */
			var ready = Boolean( chosen ) && pax > 0;

			/*
			 * The rate is the one the group qualifies for, not the package's
			 * headline: with a ladder filled in, per person and the total both
			 * move as travellers are added, and the card above moves with them.
			 */
			var rate = priceFor( pax );

			markTier( pax );

			each.textContent = money( rate );
			paxOut.textContent = '× ' + pax;
			sum.textContent = money( rate * pax );

			if ( paxMinus ) {
				paxMinus.disabled = pax <= 0;
			}

			if ( chosen ) {
				start.textContent = longDate( chosen );
				end.textContent = longDate( runEnd( chosen ) );
				start.classList.remove( 'iflynepal-pkg-is-empty' );
				end.classList.remove( 'iflynepal-pkg-is-empty' );
			}

			/*
			 * Every element below is guarded rather than assumed: `button` and
			 * `note` are absent once a package is wired to a payment button
			 * (the gateway's own button replaces the inert Book now and its
			 * note), and `pay`/`payNote` are absent on a package with no
			 * payment button configured at all. Reading a property off null
			 * throws, and that throw would take the whole booker with it, so
			 * picking a date or a traveller count would stop updating the
			 * summary.
			 */
			if ( ready ) {
				if ( button ) {
					button.removeAttribute( 'aria-disabled' );
				}

				if ( note ) {
					note.textContent = strings.bookNote || note.textContent;
				}

				if ( pay ) {
					pay.classList.remove( 'iflynepal-pkg-is-locked' );
					pay.removeAttribute( 'aria-disabled' );
				}

				if ( payNote ) {
					payNote.hidden = true;
				}
			} else {
				if ( button ) {
					button.setAttribute( 'aria-disabled', 'true' );
				}

				if ( pay ) {
					pay.classList.add( 'iflynepal-pkg-is-locked' );
					pay.setAttribute( 'aria-disabled', 'true' );
				}

				if ( payNote ) {
					payNote.hidden = false;
				}
			}
		}

		id( 'cal-prev' ).addEventListener( 'click', function () {
			view.setMonth( view.getMonth() - 1 );
			draw();
		} );

		id( 'cal-next' ).addEventListener( 'click', function () {
			view.setMonth( view.getMonth() + 1 );
			draw();
		} );

		id( 'pax-minus' ).addEventListener( 'click', function () {
			pax = Math.max( 0, pax - 1 );
			id( 'pax-out' ).textContent = pax;
			total();
		} );

		id( 'pax-plus' ).addEventListener( 'click', function () {
			pax = pax + 1;
			id( 'pax-out' ).textContent = pax;
			total();
		} );

		/* The next four Saturdays, as shortcuts rather than as offers. */
		if ( quick ) {
			var cursor = new Date( today.getTime() );

			cursor.setDate( cursor.getDate() + ( ( 6 - cursor.getDay() + 7 ) % 7 || 7 ) );

			for ( var n = 0; n < 4; n++ ) {
				( function ( date ) {
					var button = document.createElement( 'button' );

					button.type = 'button';
					button.textContent = date.toLocaleDateString( pageLocale, { day: 'numeric', month: 'short' } );
					button.addEventListener( 'click', function () {
						chosen = date;
						view = new Date( date.getFullYear(), date.getMonth(), 1 );
						draw();
						total();
					} );

					quick.appendChild( button );
				}( new Date( cursor.getTime() ) ) );

				cursor.setDate( cursor.getDate() + 7 );
			}
		}

		draw();
		total();
	}() );
}() );
