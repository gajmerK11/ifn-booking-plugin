/**
 * Card filtering on a package type archive.
 *
 * Every card is already in the page; this only hides the ones that do not match
 * the chosen category. That is why the grid is complete and correct with
 * JavaScript off, and why the filter row is only drawn when there is a child
 * category worth choosing between.
 *
 * Cards are hidden with a class that sets display:none, which takes them out of
 * the accessibility tree as well as out of the layout.
 *
 * The grid reflows with GSAP's Flip when it is available: the position of every
 * card is recorded, the classes are switched, and the survivors are animated
 * from where they used to sit to wherever the new grid has put them. Without it
 * the survivors jump between slots the instant a filter is pressed — the one
 * moment in the design where the motion visibly broke. Flip is an enhancement
 * on an enhancement: with it absent the cards simply appear and disappear.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var row = document.querySelector( '.iflynepal-filter-row' );
	var grid = document.querySelector( '.iflynepal-cards' );

	if ( ! row || ! grid ) {
		return;
	}

	var buttons = Array.prototype.slice.call( row.querySelectorAll( '.iflynepal-filter-btn' ) );
	var cards = Array.prototype.slice.call( grid.querySelectorAll( '.iflynepal-card' ) );

	/*
	 * The "view all <category>" links under the grid. One per filter button,
	 * matched on the same slug, and there is none for "all" — so pressing All
	 * matches none of them and the block closes, which is the state the page
	 * ships in.
	 */
	var foot = document.querySelector( '.iflynepal-listing__foot' );
	var links = foot ? Array.prototype.slice.call( foot.querySelectorAll( '.iflynepal-listing__all' ) ) : [];

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var canFlip = ! reduced
		&& typeof window.gsap !== 'undefined'
		&& typeof window.Flip !== 'undefined';

	if ( canFlip ) {
		window.gsap.registerPlugin( window.Flip );
	}

	function matches( card, slug ) {
		var categories = ( card.dataset.categories || '' ).split( ' ' );

		return 'all' === slug || categories.indexOf( slug ) !== -1;
	}

	function hideUnmatched( slug ) {
		cards.forEach( function ( card ) {
			card.classList.toggle( 'is-hidden', ! matches( card, slug ) );
		} );
	}

	function press( slug ) {
		buttons.forEach( function ( button ) {
			var active = button.dataset.filter === slug;

			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );

		var current = null;

		links.forEach( function ( link ) {
			var isCurrent = link.dataset.filter === slug;

			link.classList.toggle( 'is-current', isCurrent );

			if ( isCurrent ) {
				current = link;
			}
		} );

		if ( foot ) {
			foot.classList.toggle( 'is-open', null !== current );
		}
	}

	function apply( slug ) {
		press( slug );

		if ( ! canFlip ) {
			hideUnmatched( slug );

			return;
		}

		var gsap = window.gsap;

		/*
		 * A card under the cursor is mid-lift, and that transform would be
		 * recorded into the state and then carried through the whole reflow.
		 */
		gsap.set( cards, { clearProps: 'transform' } );

		var state = window.Flip.getState( cards, { props: 'opacity' } );

		/*
		 * The height the grid is leaving, measured before anything is hidden.
		 * Flip lifts the leavers out of the flow on the first frame, so without
		 * this the grid loses a whole row of height instantly and every section
		 * below the catalogue is yanked up the page while the cards are still
		 * travelling. Animating the grid's own height over the same duration is
		 * what keeps the page under the cards still.
		 */
		var from = grid.offsetHeight;

		hideUnmatched( slug );

		/*
		 * Read once, after the classes are switched and before the height is
		 * pinned: forcing a height and then measuring would measure the height
		 * that was just forced.
		 */
		var to = grid.offsetHeight;

		if ( from !== to ) {
			gsap.fromTo(
				grid,
				{ height: from },
				{
					height: to,
					duration: 0.62,
					ease: 'power3.inOut',
					onComplete: function () {
						/*
						 * Handed back to the grid's own auto height, or the next
						 * viewport change would be resizing a pinned pixel value.
						 */
						gsap.set( grid, { clearProps: 'height' } );
					}
				}
			);
		}

		window.Flip.from( state, {
			duration: 0.62,
			ease: 'power3.inOut',
			scale: true,
			/*
			 * The leavers are lifted out of the flow at once, so the survivors
			 * can move into space the leavers are still visually occupying.
			 */
			absolute: true,
			stagger: 0.035,
			onEnter: function ( elements ) {
				return gsap.fromTo(
					elements,
					{ opacity: 0, scale: 0.94, y: 14 },
					{
						opacity: 1,
						scale: 1,
						y: 0,
						duration: 0.5,
						ease: 'power2.out',
						stagger: 0.045,
						delay: 0.08
					}
				);
			},
			onLeave: function ( elements ) {
				elements.forEach( function ( element ) {
					element.classList.add( 'is-leaving' );
				} );

				return gsap.to( elements, {
					opacity: 0,
					scale: 0.94,
					duration: 0.32,
					ease: 'power2.in',
					onComplete: function () {
						elements.forEach( function ( element ) {
							element.classList.remove( 'is-leaving' );
						} );
					}
				} );
			},
			onComplete: function () {
				/*
				 * Flip leaves inline transforms behind; clearing them hands the
				 * cards back to the CSS hover, which owns them the rest of the
				 * time. Opacity is set rather than cleared, because clearing it
				 * would drop a card back onto the reveal gate's opacity:0 and
				 * it would vanish.
				 */
				gsap.set( cards, { clearProps: 'transform', opacity: 1 } );

				if ( typeof window.ScrollTrigger !== 'undefined' ) {
					window.ScrollTrigger.refresh();
				}
			}
		} );
	}

	/*
	 * The block's height is still opening when the reflow's own refresh runs,
	 * so everything below it would be measured against a page height it is
	 * about to stop having. Refreshing again on the way out of the transition
	 * measures the settled page.
	 */
	if ( foot ) {
		foot.addEventListener( 'transitionend', function ( event ) {
			if ( event.target === foot && 'grid-template-rows' === event.propertyName && typeof window.ScrollTrigger !== 'undefined' ) {
				window.ScrollTrigger.refresh();
			}
		} );
	}

	row.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.iflynepal-filter-btn' );

		if ( button ) {
			apply( button.dataset.filter || 'all' );
		}
	} );
}() );
