/**
 * Card filtering on a package type archive.
 *
 * Every card is already in the page; this only hides the ones that do not
 * match the chosen combination. That is why the grid is complete and correct
 * with JavaScript off, and why a facet is only drawn when there is something
 * in it worth choosing between.
 *
 * There can be up to three facets — Activity, Duration, Budget — each its own
 * single-select group with its own "All", read from the card's
 * data-categories, data-duration and data-budget attributes. A card is shown
 * only when it satisfies every facet currently narrowed away from "All": the
 * three combine with AND, not OR, so picking "Yoga" and "6–14 days" narrows
 * to cards that are both, not the union of the two.
 *
 * Activity and Duration match by inclusion. data-categories carries a package's
 * whole ancestor chain (a package three levels down still answers to the
 * category two levels up); data-duration carries every length bucket the
 * package's span reaches, because a package's length is a range read off the
 * Duration field on its Package Card. Budget matches by equality: a package has
 * one price and so falls in exactly one bucket, decided server-side by
 * iflynepal_archive_budget_key().
 *
 * Cards are hidden with a class that sets display:none, which takes them out
 * of the accessibility tree as well as out of the layout. The switch is
 * instant — no animation of any kind, on the cards or on the grid around them.
 *
 * 🔴 This used to reflow with GSAP's Flip: record every card's position,
 * switch the classes, animate the survivors from where they used to sit to
 * their new slot, with the grid's own height separately tweened so the page
 * beneath it held still while that happened. Client-directed removal after
 * several rounds of chasing motion it kept producing regardless of what was
 * tuned — cards visibly lifting from below on entry, the grid itself dipping
 * and springing back on a filter pair that changes no cards at all. Both were
 * genuine mechanisms of `absolute: true`, which pulls every tracked card out
 * of normal flow for the length of its own animation whether or not that card
 * actually moves, and each fix for one symptom left another. A plain, instant
 * class toggle has none of that machinery and therefore nothing left to
 * misfire: the grid reflows once, natively, the moment the classes change.
 *
 * ---
 *
 * Four things came with the move from a filter row to the left-hand rail, and
 * all four exist because a rail shows every facet at once — which is its point,
 * and which is also what makes a combination that matches nothing reachable in
 * one press rather than three:
 *
 *  - **Counts are recomputed against the live selection.** The server prints
 *    each option's share of the whole grid, which is the right number for the
 *    page as it first loads and the only one a visitor without JavaScript will
 *    ever see. Once a facet is narrowed it stops being right: "6–14 days (4)"
 *    beside an Activity of Yoga means four of *all* packages, not four of the
 *    yoga ones. Every option is re-counted as though it alone were pressed, on
 *    top of whatever the *other* facets currently say. Activity is drawn as
 *    pills and shows no count at all (client-directed); the arithmetic still
 *    runs for it, because it is what decides the disabled state below.
 *  - **An option that would empty the grid is disabled**, which is the same
 *    rule iflynepal_archive_filter_terms() applies server-side — a control
 *    that can only ever produce an empty grid is not offered — carried over to
 *    combinations, which the server cannot know about because nothing is
 *    selected until the page is in a browser. The currently pressed option is
 *    never disabled, whatever it counts: a visitor has to be able to see and
 *    undo the choice that emptied the grid.
 *  - **The empty message** is the backstop for the case the rule above should
 *    make unreachable. It is not dead code: a card can fail every facet at once
 *    if content is mid-edit, and a blank space where the cards were is the one
 *    outcome that leaves the visitor nothing to do next.
 *  - **The rail collapses on a narrow viewport**, where a full-height column of
 *    facets would be the whole first screen. The server renders it open, so the
 *    enhancement is the closing rather than the opening: with this script off,
 *    every facet is simply visible.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var grid = document.querySelector( '.iflynepal-cards' );

	/*
	 * Groups are collected from the whole page, not from one panel. Activity
	 * is a row of pills above the grid and the range facets are rows in the
	 * rail beside it, so there are two panels on a type archive and one on a
	 * category with only one of the two. Every group found combines with every
	 * other, wherever it is drawn — the AND is over the groups, and it never
	 * cared which box they sit in.
	 */
	var groups = Array.prototype.slice.call( document.querySelectorAll( '.iflynepal-filter-group' ) );

	if ( ! grid || ! groups.length ) {
		return;
	}

	var cards = Array.prototype.slice.call( grid.querySelectorAll( '.iflynepal-card' ) );

	/* Null on an archive whose only facet is the pill row — there is no rail. */
	var details = document.querySelector( '.iflynepal-facets' );
	var badge = document.querySelector( '[data-iflynepal-active-count]' );
	var empty = document.querySelector( '[data-iflynepal-empty]' );
	var clears = Array.prototype.slice.call( document.querySelectorAll( '[data-iflynepal-clear]' ) );

	/*
	 * The "view all <category>" links under the grid track the Activity facet
	 * only — there is no term archive to link to for a duration or a budget
	 * bucket, so Duration and Budget never touch this block.
	 */
	var foot = document.querySelector( '.iflynepal-listing__foot' );
	var links = foot ? Array.prototype.slice.call( foot.querySelectorAll( '.iflynepal-listing__all:not(.iflynepal-listing__reveal)' ) ) : [];
	var reveal = document.querySelector( '[data-iflynepal-reveal]' );
	var revealLabel = document.querySelector( '[data-iflynepal-reveal-label]' );

	/*
	 * How many matching cards the grid shows before the rest are put behind the
	 * foot. Announced by the template on a type archive and absent on a
	 * category archive, which shows its whole branch — so Infinity here is the
	 * category case and not a missing value.
	 */
	var cap = parseInt( grid.dataset.iflynepalCap, 10 );

	if ( ! ( cap > 0 ) ) {
		cap = Infinity;
	}

	/*
	 * Set by the reveal button and cleared by any change to the filters. The
	 * cap is about how much of a set to show at once; choosing a different set
	 * is a new question, and answering it with the previous answer's "show me
	 * everything" still applied would quietly turn the cap off for good.
	 */
	var revealed = false;

	/**
	 * The facet each group controls, e.g. "categories", "duration", "budget".
	 *
	 * @param {Element} group A .iflynepal-filter-group.
	 * @return {string}
	 */
	function facetOf( group ) {
		return group.getAttribute( 'data-facet' ) || '';
	}

	/**
	 * The option currently pressed in a group.
	 *
	 * @param {Element} group A .iflynepal-filter-group.
	 * @return {string} The active button's data-filter, or "all".
	 */
	function filterOf( group ) {
		var current = group.querySelector( '.iflynepal-filter-btn.is-active' );

		return current ? current.dataset.filter || 'all' : 'all';
	}

	/**
	 * Whether a card's value for a facet satisfies the chosen filter.
	 *
	 * Two of the three are space-separated lists and are matched by inclusion.
	 * data-categories carries a package's whole ancestor chain, so it answers
	 * to every category above its own. data-duration carries every length
	 * bucket its span reaches into, because a package's length is a range —
	 * "7 to 24 days" is the norm on this catalogue — and a trek that can be
	 * walked in ten days belongs under ten-to-fourteen as well as under
	 * fifteen-plus.
	 *
	 * data-budget is a single key: a package has one price, not a range of
	 * them, so it falls in exactly one bucket and is matched by equality.
	 *
	 * @param {Element} card   A .iflynepal-card.
	 * @param {string}  facet  "categories", "duration" or "budget".
	 * @param {string}  filter The active button's data-filter value.
	 * @return {boolean}
	 */
	function cardMatches( card, facet, filter ) {
		if ( 'all' === filter ) {
			return true;
		}

		var value = card.dataset[ facet ] || '';

		if ( 'categories' === facet || 'duration' === facet ) {
			return value.split( ' ' ).indexOf( filter ) !== -1;
		}

		return value === filter;
	}

	/**
	 * How many cards satisfy a whole set of facet/filter pairs at once.
	 *
	 * @param {Array} selection Entries of { facet, filter }.
	 * @return {number}
	 */
	function countMatching( selection ) {
		return cards.filter( function ( card ) {
			return selection.every( function ( entry ) {
				return cardMatches( card, entry.facet, entry.filter );
			} );
		} ).length;
	}

	/**
	 * What every group is currently narrowed to.
	 *
	 * @return {Array} Entries of { facet, filter }, one per group.
	 */
	function selection() {
		return groups.map( function ( group ) {
			return {
				facet: facetOf( group ),
				filter: filterOf( group ),
			};
		} );
	}

	/**
	 * Rewrites every option's count and disables the ones that would empty the
	 * grid, against what the *other* facets are currently narrowed to.
	 *
	 * A group's own current choice is excluded from its options' arithmetic —
	 * an option is counted as though pressing it replaced whatever that group
	 * holds now, which is exactly what pressing it does.
	 *
	 * @param {Array} active The current selection.
	 * @return {void}
	 */
	function recount( active ) {
		groups.forEach( function ( group ) {
			var facet = facetOf( group );
			var others = active.filter( function ( entry ) {
				return entry.facet !== facet;
			} );

			Array.prototype.forEach.call(
				group.querySelectorAll( '.iflynepal-filter-btn' ),
				function ( button ) {
					var hypothetical = others.concat( [ { facet: facet, filter: button.dataset.filter || 'all' } ] );
					var total = countMatching( hypothetical );
					var slot = button.querySelector( '.iflynepal-filter-btn__count' );

					/*
					 * Activity is drawn as pills and prints no count, so there
					 * is nothing to write for it — the arithmetic still runs,
					 * because it is what decides the disabled state below.
					 */
					if ( slot ) {
						slot.textContent = String( total );
					}

					/*
					 * The pressed option keeps working whatever it counts, or
					 * a visitor who has just emptied the grid cannot press
					 * their way back out of it.
					 */
					var dead = 0 === total && ! button.classList.contains( 'is-active' );

					button.disabled = dead;
					button.classList.toggle( 'is-empty', dead );
				}
			);
		} );
	}

	/**
	 * Re-hides the grid against every group's currently active button, then
	 * brings the counts, the badge, the reset and the empty message into step
	 * with it.
	 *
	 * @return {void}
	 */
	function apply() {
		var active = selection();
		var matched = 0;
		var shown = 0;

		cards.forEach( function ( card ) {
			var matchesAll = active.every( function ( entry ) {
				return cardMatches( card, entry.facet, entry.filter );
			} );

			card.classList.toggle( 'is-hidden', ! matchesAll );

			if ( ! matchesAll ) {
				card.classList.remove( 'is-capped' );
				return;
			}

			matched += 1;

			/*
			 * Counted in DOM order, so the six that survive are the first six
			 * of the current match rather than the first six of the grid —
			 * narrowing to a category whose packages sit at the end of the
			 * list would otherwise show none of them.
			 *
			 * A separate class from is-hidden on purpose: a card can be behind
			 * the cap and still match, and the two answers are undone at
			 * different moments. Collapsing them into one class means the
			 * reveal button cannot tell what it is meant to bring back.
			 */
			var overCap = ! revealed && matched > cap;

			card.classList.toggle( 'is-capped', overCap );

			if ( ! overCap ) {
				shown += 1;
			}
		} );

		var narrowed = active.filter( function ( entry ) {
			return 'all' !== entry.filter;
		} ).length;

		recount( active );
		updateFoot( active, matched - shown );

		if ( badge ) {
			badge.textContent = String( narrowed );
			badge.hidden = 0 === narrowed;
		}

		clears.forEach( function ( button ) {
			/*
			 * The reset inside the empty message is the only thing in it, so
			 * it follows the message rather than this rule — hiding it would
			 * leave the visitor a dead end with no way out.
			 */
			if ( empty && empty.contains( button ) ) {
				return;
			}

			button.hidden = 0 === narrowed;
		} );

		if ( empty ) {
			empty.hidden = 0 !== shown;
		}

		grid.hidden = 0 === shown;

		refreshReveals();
		fitFacetsTitle();
	}

	/*
	 * The facets panel title ("Find your trip") shares its row with the Clear
	 * all reset, which appears only once a filter is active — a translation
	 * longer than the English can fit alongside empty space but wrap to two
	 * lines the moment that reset button claims part of the row. Same
	 * technique as mapTitleFit()/priceFit() in package.js: shrink the title
	 * only, not the reset beside it, until the row is one line again.
	 */
	var FACETS_TITLE_MIN_FONT_SIZE = 12;

	function fitFacetsTitle() {
		var head = document.querySelector( '.iflynepal-facets__head' );
		var title = head ? head.querySelector( '.iflynepal-facets__title' ) : null;

		if ( ! head || ! title ) {
			return;
		}

		title.style.removeProperty( 'font-size' );
		// Wrapping to a second line never overflows the row horizontally, so
		// scrollWidth below would never see it as too wide — nowrap turns
		// "wraps" into "overflows", which is what the shrink loop can measure.
		title.style.whiteSpace = 'nowrap';

		var fontSize = parseFloat( getComputedStyle( title ).fontSize );

		while ( head.scrollWidth > head.clientWidth && fontSize > FACETS_TITLE_MIN_FONT_SIZE ) {
			fontSize -= 1;
			title.style.setProperty( 'font-size', fontSize + 'px' );
		}

		// Still too long at the floor size: an ordinary two-line wrap reads
		// better than either clipping or a title shrunk unreadably small.
		if ( head.scrollWidth > head.clientWidth ) {
			title.style.whiteSpace = 'normal';
		}
	}

	/**
	 * Re-places the scroll-reveal triggers after the grid has changed shape.
	 *
	 * 🔴 Without this, a card brought back by the reveal button stays at
	 * opacity 0 for good. assets/js/archive/reveal.js hands every card to a
	 * ScrollTrigger.batch that reads each one's position once, at setup; a card
	 * behind the cap is display:none at that moment, so it has no position to
	 * read and its trigger can never be satisfied. Measured: five of eight
	 * cards sat at opacity 0 after pressing the button, occupying full-height
	 * boxes with nothing drawn in them.
	 *
	 * Filtering alone needs it too — hiding four cards moves everything below
	 * them hundreds of pixels up, past start points worked out before the move.
	 *
	 * refresh() re-measures and fires anything now in view, which is exactly
	 * the behaviour wanted: a revealed card already on screen animates in, and
	 * one still below the fold waits its turn as it always did. Guarded,
	 * because ScrollTrigger is the theme's and is only on the page where the
	 * theme put it — with no GSAP, reveal.js has already taken the gate off and
	 * there is nothing here to place.
	 *
	 * @return {void}
	 */
	function refreshReveals() {
		if ( window.ScrollTrigger && 'function' === typeof window.ScrollTrigger.refresh ) {
			window.ScrollTrigger.refresh();
		}
	}

	/**
	 * Presses one button within its own group, and updates the "view all"
	 * foot block when the group pressed is Activity.
	 *
	 * @param {Element} group  The group the button belongs to.
	 * @param {Element} button The button pressed.
	 * @return {void}
	 */
	function press( group, button ) {
		var slug = button.dataset.filter || 'all';

		Array.prototype.forEach.call(
			group.querySelectorAll( '.iflynepal-filter-btn' ),
			function ( candidate ) {
				var isActive = candidate === button;

				candidate.classList.toggle( 'is-active', isActive );
				candidate.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
			}
		);

		/*
		 * The foot is not touched here. What it shows depends on the whole
		 * selection and on how many cards the cap is holding back, neither of
		 * which this function knows; apply() works both out and owns it.
		 */
	}

	/**
	 * Builds the query string a "view all" link carries to a category archive.
	 *
	 * Duration and Budget travel with the visitor so the narrowing survives the
	 * jump. Activity does not: it *is* the destination, and repeating it as a
	 * parameter would be the page filtering itself down to the only thing it
	 * contains.
	 *
	 * `days` rather than `duration`, because that is the spelling the hero
	 * picker and /explore/ already use and there should be one name for the
	 * question across the site.
	 *
	 * @param {Array} active The current selection.
	 * @return {string} A query string beginning with "?", or "".
	 */
	function carriedQuery( active ) {
		var parts = [];

		active.forEach( function ( entry ) {
			if ( 'all' === entry.filter || 'categories' === entry.facet ) {
				return;
			}

			var name = 'duration' === entry.facet ? 'days' : entry.facet;

			parts.push( encodeURIComponent( name ) + '=' + encodeURIComponent( entry.filter ) );
		} );

		return parts.length ? '?' + parts.join( '&' ) : '';
	}

	/**
	 * Decides what sits under the grid: a link out to a category archive, the
	 * button that reveals the rest in place, or nothing.
	 *
	 * The two are alternatives, not a pair. With a category chosen there is a
	 * real archive holding every one of its packages, so the visitor is sent
	 * there and the link carries their Duration and Budget with it. With
	 * Activity on "All" there is no such page — the fullest listing of this
	 * type is the one they are already on — so the rest are revealed here.
	 *
	 * @param {Array}  active The current selection.
	 * @param {number} behind How many matching cards the cap is holding back.
	 * @return {void}
	 */
	function updateFoot( active, behind ) {
		if ( ! foot ) {
			return;
		}

		var category = 'all';

		active.forEach( function ( entry ) {
			if ( 'categories' === entry.facet ) {
				category = entry.filter;
			}
		} );

		var query = carriedQuery( active );
		var current = null;

		links.forEach( function ( link ) {
			var isCurrent = link.dataset.filter === category;

			link.classList.toggle( 'is-current', isCurrent );

			if ( isCurrent ) {
				current = link;

				/*
				 * The bare permalink is kept on the element and the query
				 * rebuilt from it every time, rather than appended to whatever
				 * is in href now — which after two presses would be the first
				 * press's parameters with the second's stuck on the end.
				 */
				if ( ! link.dataset.href ) {
					link.dataset.href = link.getAttribute( 'href' );
				}

				link.setAttribute( 'href', link.dataset.href + query );
			}
		} );

		var showReveal = reveal && null === current && behind > 0;

		if ( reveal ) {
			reveal.hidden = ! showReveal;

			if ( showReveal && revealLabel ) {
				var template = behind === 1
					? revealLabel.dataset.one || 'Show 1 more package'
					: revealLabel.dataset.many || 'Show %s more packages';

				revealLabel.textContent = template.replace( '%s', String( behind ) );
			}
		}

		foot.classList.toggle( 'is-open', null !== current || showReveal );
	}

	/**
	 * Puts every group back on its own "All".
	 *
	 * @return {void}
	 */
	function clearAll() {
		revealed = false;

		groups.forEach( function ( group ) {
			var all = group.querySelector( '.iflynepal-filter-btn[data-filter="all"]' );

			if ( all ) {
				press( group, all );
			}
		} );

		apply();
	}

	groups.forEach( function ( group ) {
		group.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '.iflynepal-filter-btn' );

			if ( ! button || button.disabled ) {
				return;
			}

			revealed = false;
			press( group, button );
			apply();
		} );
	} );

	if ( reveal ) {
		reveal.addEventListener( 'click', function () {
			revealed = true;
			apply();
		} );
	}

	clears.forEach( function ( button ) {
		button.addEventListener( 'click', clearAll );
	} );

	/*
	 * The rail is shut on a narrow viewport and open on a wide one, tracked
	 * live rather than set once on load: a tablet turned on its side crosses
	 * this line without reloading, and a rail left shut on a screen with room
	 * for it reads as a missing feature.
	 *
	 * 1024px is the same line catalogue.css folds the layout on. The media
	 * query is the single source of the breakpoint on the CSS side; this is
	 * the one place the number is repeated, because a script cannot read a
	 * media query's own text back out of a stylesheet.
	 */
	if ( details && window.matchMedia ) {
		var narrow = window.matchMedia( '(max-width: 1024px)' );

		var syncRail = function ( query ) {
			details.open = ! query.matches;
		};

		syncRail( narrow );

		if ( narrow.addEventListener ) {
			narrow.addEventListener( 'change', syncRail );
		} else if ( narrow.addListener ) {
			/* Safari before 14. */
			narrow.addListener( syncRail );
		}
	}

	/*
	 * Run once on load. Nothing is narrowed yet, so no card moves — what this
	 * pass is for is the parts the server could not print: the counts are
	 * already right, but the badge, the reset and the empty message all have
	 * to start in a state that agrees with them rather than with their markup.
	 */
	apply();

	var facetsTitleResizeTimer;
	window.addEventListener( 'resize', function () {
		clearTimeout( facetsTitleResizeTimer );
		facetsTitleResizeTimer = setTimeout( fitFacetsTitle, 150 );
	} );
}() );
