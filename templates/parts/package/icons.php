<?php
/**
 * The package page's icon sprite.
 *
 * One inline <svg> holding every icon the design uses, referenced with
 * <use href="#ifnpkg-i-name">. Inline rather than a sprite file because a file
 * cannot be referenced by <use> across origins without a fetch, and these are
 * a few hundred bytes that belong to one template.
 *
 * The paths are the design's, unchanged. The ids are prefixed, because an id is
 * global to the document and the theme may carry its own.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="iflynepal-pkg-sprite" width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
	<defs>
		<symbol id="ifnpkg-i-doc" viewBox="0 0 24 24"><path d="M6.5 3.5h8l3.5 3.5v13.5h-11.5z"/><path d="M14.5 3.5V7H18M9.5 11.5h5M9.5 15h5"/></symbol>
		<symbol id="ifnpkg-i-route" viewBox="0 0 24 24"><circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M8.2 18H15a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h6.8"/></symbol>
		<symbol id="ifnpkg-i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.6a2.5 2.5 0 0 1 4.8 1c0 1.7-2.4 2-2.4 3.6M12 17h.01"/></symbol>
		<symbol id="ifnpkg-i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"/></symbol>
		<symbol id="ifnpkg-i-x" viewBox="0 0 24 24"><path d="M7 7l10 10M17 7L7 17"/></symbol>
		<symbol id="ifnpkg-i-arrow" viewBox="0 0 24 24"><path d="M4 12h15M13 6l6 6-6 6"/></symbol>
		<symbol id="ifnpkg-i-left" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></symbol>
		<symbol id="ifnpkg-i-right" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></symbol>
		<symbol id="ifnpkg-i-down" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></symbol>
		<symbol id="ifnpkg-i-pin" viewBox="0 0 24 24"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></symbol>
		<symbol id="ifnpkg-i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></symbol>
		<symbol id="ifnpkg-i-lotus" viewBox="0 0 24 24"><path d="M12 19c-3.8 0-7-2.6-8-6 2.4-.3 5 .6 8 3.4 3-2.8 5.6-3.7 8-3.4-1 3.4-4.2 6-8 6z"/><path d="M12 16.4C10 14 9.4 10.6 12 6c2.6 4.6 2 8-0 10.4z"/></symbol>
		<symbol id="ifnpkg-i-bowl" viewBox="0 0 24 24"><path d="M3.5 11h17c0 4.4-3.8 8-8.5 8s-8.5-3.6-8.5-8z"/><path d="M9 7.5c0-1.2 1-1.2 1-2.5M13 7.5c0-1.2 1-1.2 1-2.5"/></symbol>
		<symbol id="ifnpkg-i-bed" viewBox="0 0 24 24"><path d="M3 18v-9M3 14h18v4M21 14v-2.5A2.5 2.5 0 0 0 18.5 9H11v5"/><circle cx="7" cy="11.5" r="1.6"/></symbol>
		<symbol id="ifnpkg-i-group" viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><circle cx="16.5" cy="9.5" r="2.4"/><path d="M16 14c2.3 0 4 1.4 4.5 4"/></symbol>
		<symbol id="ifnpkg-i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6 7 7M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4"/></symbol>
		<symbol id="ifnpkg-i-cal" viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/></symbol>
		<symbol id="ifnpkg-i-door" viewBox="0 0 24 24"><path d="M5 20V5.5A1.5 1.5 0 0 1 6.5 4h8A1.5 1.5 0 0 1 16 5.5V20M3 20h18M12.5 12.5h.01M16 8h3v12"/></symbol>
		<symbol id="ifnpkg-i-grid" viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></symbol>
		<symbol id="ifnpkg-i-link" viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></symbol>
		<symbol id="ifnpkg-i-mail" viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="M4.5 7l7.5 6 7.5-6"/></symbol>
		<symbol id="ifnpkg-i-shield" viewBox="0 0 24 24"><path d="M12 3.5l7 2.6v5.4c0 4.4-3 7.8-7 9-4-1.2-7-4.6-7-9V6.1z"/><path d="M9 12l2.2 2.2L15.5 10"/></symbol>
		<symbol id="ifnpkg-i-bag" viewBox="0 0 24 24"><rect x="4" y="7.5" width="16" height="12.5" rx="2.5"/><path d="M9 7.5V6a3 3 0 0 1 6 0v1.5"/></symbol>
		<symbol id="ifnpkg-i-shirt" viewBox="0 0 24 24"><path d="M8.5 4 4 6.5l1.8 3.6L8 9v11h8V9l2.2 1.1L20 6.5 15.5 4c-.5 1.4-1.9 2.3-3.5 2.3S9 5.4 8.5 4z"/></symbol>
		<symbol id="ifnpkg-i-shoe" viewBox="0 0 24 24"><path d="M3.5 16.5V9l5 1.5 3.5 3 6.5 1.2c1.2.2 2 1.2 2 2.4v.4h-17z"/><path d="M3.5 19h17"/></symbol>
		<symbol id="ifnpkg-i-bottle" viewBox="0 0 24 24"><path d="M10 3h4v3l1.5 2.5V20a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1V8.5L10 6z"/><path d="M8.5 12h7"/></symbol>
		<symbol id="ifnpkg-i-glasses" viewBox="0 0 24 24"><circle cx="7" cy="14" r="3.5"/><circle cx="17" cy="14" r="3.5"/><path d="M10.5 14h3M3.5 13l1.5-6M20.5 13 19 7"/></symbol>
		<symbol id="ifnpkg-i-id" viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><circle cx="9" cy="11" r="2"/><path d="M6 15.5c.5-1.3 1.6-2 3-2s2.5.7 3 2M14.5 10h3M14.5 13h3"/></symbol>
		<symbol id="ifnpkg-i-pill" viewBox="0 0 24 24"><rect x="3" y="8.5" width="18" height="7" rx="3.5" transform="rotate(-35 12 12)"/><path d="M9.6 8.6l4.1 5.8"/></symbol>
		<symbol id="ifnpkg-i-scarf" viewBox="0 0 24 24"><path d="M6 4h12l-2 6H8z"/><path d="M8 10l-1 10h4l1-7M16 10l1 6"/></symbol>
		<symbol id="ifnpkg-i-leaf" viewBox="0 0 24 24"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14"/><path d="M5 19l7-7"/></symbol>
		<?php
		/*
		 * Not one of the design's icons: the Highlights heading is a later
		 * addition and the design has no mark for it. Drawn to the same
		 * 24-unit box and the same 1.7 stroke as the rest of the set.
		 */
		?>
		<?php
		/*
		 * Experience level. Not a design icon either: the design's set has no mark
		 * for it, so this is drawn to the same 24-unit box and the same 1.7 stroke
		 * as the rest — three rising bars, which is the ordinary way of saying a
		 * scale without committing to how many steps it has.
		 */
		?>
		<symbol id="ifnpkg-i-level" viewBox="0 0 24 24"><path d="M6 19v-4M12 19v-8M18 19V7"/></symbol>
		<symbol id="ifnpkg-i-spark" viewBox="0 0 24 24"><path d="M12 3.5l2.1 5.2 5.4 1.9-5.4 1.9-2.1 5.2-2.1-5.2-5.4-1.9 5.4-1.9z"/><path d="M18.5 16.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7z"/></symbol>
	</defs>
</svg>
