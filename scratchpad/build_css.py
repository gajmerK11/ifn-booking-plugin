"""Generate the package stylesheet from the approved design file.

Transcribed rather than re-typed: every value stays exactly as the design has it,
and only the names change. Doing it by hand over 1,098 lines is how a 24px
becomes a 22px.

What changes:
  * class names       .foo            -> .iflynepal-pkg-foo
  * custom properties --navy          -> --ifnpkg-navy
  * ids in selectors  #dates          -> #ifnpkg-dates
  * the page root     main { }        -> .iflynepal-package { }
  * bare elements     img { }         -> :where(.iflynepal-package) img { }

Two things about the scoping are load-bearing, and both were found by the page
rendering wrong rather than by reading the output:

  * `:where()`, not a bare descendant.  `.iflynepal-package h3` is specificity
    (0,1,1) and beats the design's own `.sub-h` (0,1,0) — so scoping an element
    rule silently promotes it above every class rule written to override it, and
    the sub-heading came out 24px/600 instead of 21px/700.  `:where()` weighs
    nothing, so a scoped `h3` stays (0,0,1) exactly as the design has it.

  * `main` is the page root, not something inside it.  The template's root
    element IS `<main class="iflynepal-package">`, so a descendant selector
    matches nothing and the design's `padding-top` (clearing the fixed header)
    and `overflow-x: clip` (containing the mist bands' -100vw bleed) were both
    dead.  The second showed as a full viewport of horizontal scroll.

The variables stay on `:root`.  They were on `.iflynepal-package` to stop a
plugin sheet repainting the site, but the `--ifnpkg-` prefix is what does that,
and the location broke every responsive override: the design retunes `--navw`,
`--aside`, `--gap`, `--hdr` and `--tabs` from `:root` inside media queries, and a
declaration on the closer `.iflynepal-package` won every one of them.  The tab
strip below 1100px was `min-height: var(--tabs)` = 0.

What is dropped, and why:
  * the header, nav and footer rules  - the theme owns the site chrome
  * body / ::selection resets         - ditto, and a plugin must not restyle body
  * the paper-grain overlay           - site chrome, same call as the archive

`html` is kept: its two declarations are `scroll-behavior` and the
`scroll-padding-top` that stops an anchor jump landing underneath the fixed
header, which is this page's sticky section nav and nothing to do with chrome.
"""
import io, re, os

SP = os.path.dirname(os.path.abspath(__file__))
src = io.open(os.path.join(SP, 'design.css'), encoding='utf-8').read()

# ---------------------------------------------------------------- drop blocks

def drop_range(text, start_marker, end_marker):
    i = text.index(start_marker)
    j = text.index(end_marker, i)
    return text[:i] + text[j:]

# header + nav: everything from the header comment to `main{`
src = drop_range(src, '/* ---------- header:', '    main{padding-top:var(--hdr);overflow-x:clip}')
# footer: from its comment to the mobile booking bar comment
src = drop_range(src, '/* ---------- footer ---', '/* ---------- mobile booking bar')

# The day card's foot - the accommodation and meals notes on an itinerary day.
# Both were removed from the content model on the client's instruction, so the
# design's rules for them have nothing to style. Dropped here rather than after
# the fact, or every re-run reinstates them.
for rule in (
    re.compile(r'\s*\.day-foot\s*\{[^}]*\}'),
    re.compile(r'\s*\.day-foot\s+[^{,]*\{[^}]*\}'),
):
    src = rule.sub('', src)

# The element resets the theme already owns, plus the body grain. `html` is not
# in this list - see the module docstring.
for block in (
    re.compile(r'\s*\*\{box-sizing:border-box\}'),
    re.compile(r'\s*body\{[^}]*\}'),
    re.compile(r'\s*body::after\{[^}]*\}'),
    re.compile(r'\s*::selection\{[^}]*\}'),
):
    src = block.sub('', src, count=1)

# ------------------------------------------------------------------- renames

# Custom properties first: --foo: and var(--foo)
# Lookbehind, or the `--lead` in a BEM class name is renamed as if it were a
# custom property and the rule lands on a selector no markup carries.
src = re.sub(r'(?<![\w-])--([a-z0-9-]+)', lambda m: '--ifnpkg-' + m.group(1), src)

# Class names. Only in selectors - a .foo inside a url() or a content string
# would be caught by a blind replace, so the value side of a declaration is left
# alone by splitting each rule at its opening brace.
CLASS = re.compile(r'\.(-?[_a-zA-Z][\w-]*)')

# Ids travel too. The template prefixes every id it writes, so a design rule
# hung off one (`#dates .t-head`, the clearance the handwritten note sits in)
# matches nothing unless this renames with it.
ID = re.compile(r'#([_a-zA-Z][\w-]*)')

def rename_selector(sel):
    sel = CLASS.sub(lambda m: '.iflynepal-pkg-' + m.group(1), sel)

    return ID.sub(lambda m: '#ifnpkg-' + m.group(1), sel)

# Bare element selectors that need scoping to the page.
ELEMENTS = {
    'img', 'a', 'button', 'input', 'select', 'h1', 'h2', 'h3', 'h4', 'p', 'ul',
    'ol', 'li', 'table', 'th', 'td', 'iframe', 'figure', 'figcaption', 'time',
    'details', 'summary', 'small', 'strong', 'b', 'sup', 'output', 'main',
    'section', 'aside', 'article', 'nav',
}

def scope_selector(sel):
    parts = []
    for one in sel.split(','):
        one = one.strip()
        if not one:
            continue
        head = re.split(r'[ >+~:\[]', one, maxsplit=1)[0]
        if head == 'main':
            # The page root is the <main>, so this is the same element, not a
            # descendant of it. A descendant selector here matches nothing and
            # takes the header clearance and the overflow containment with it.
            one = '.iflynepal-package' + one[len('main'):]
        elif head in ELEMENTS:
            # :where() so scoping adds no specificity - see the docstring.
            one = ':where(.iflynepal-package) ' + one
        parts.append(one)
    return ',\n'.join(parts)

out = []
for chunk in re.split(r'(\{|\})', src):
    out.append(chunk)

# Walk the sheet, rewriting only selector text (depth 0, and inside @media).
result = []
depth = 0
buffer = ''
i = 0
tokens = re.split(r'([{}])', src)
for token in tokens:
    if token == '{':
        sel = buffer
        stripped = sel.strip()
        if stripped.startswith('@'):
            result.append(sel)
        else:
            lead = sel[:len(sel) - len(sel.lstrip())]
            result.append(lead + scope_selector(rename_selector(stripped)))
        result.append('{')
        buffer = ''
        depth += 1
    elif token == '}':
        result.append(buffer)
        result.append('}')
        buffer = ''
        depth -= 1
    else:
        buffer = token
result.append(buffer)

css = ''.join(result)

# The keyframe names travel with everything else.
css = re.sub(r'@keyframes\s+([\w-]+)', lambda m: '@keyframes iflynepal-pkg-' + m.group(1), css)
css = re.sub(r'animation:([^;}]*)', lambda m: m.group(0), css)

EXTRAS = u"""

/* ---------------------------------------------------------------------------
 * Hand-written additions, appended by scratchpad/build_css.py.
 *
 * Everything above this line is transcribed from the design file. Everything
 * below it is for markup the design has no rule for, and it is held in the
 * generator's EXTRAS block so that re-running the script keeps it rather than
 * quietly dropping it.
 * ------------------------------------------------------------------------ */

/*
 * The featured video, which takes the lead tile when a package has one. It is
 * sized exactly as the lead photograph, so the gallery grid is the same grid
 * whichever of the two is playing, and it is not zoom-in: this tile opens
 * nothing, it plays.
 */
.iflynepal-pkg-g-tile--video{cursor:default}
.iflynepal-pkg-g-tile--video::after{content:none}
.iflynepal-pkg-g-video{
  display:block;
  width:100%;
  height:100%;
  object-fit:cover;
  background:var(--ifnpkg-navy);
}

/*
 * The header this page actually sits under.
 *
 * The design ships its own header at 76px, and everything that has to clear it
 * is written as `calc(var(--hdr) + …)`: the page's top padding, the sticky
 * section nav, the sticky booking aside and the scroll-padding an anchor jump
 * lands against. On the real site that bar is the theme's, and this template
 * asks for it docked from the first frame, which is 70px — 66px once the nav
 * folds at 1080px. Left at the design's number every one of those offsets is
 * six pixels out and the page carries a hairline of white under the header.
 *
 * So the transcription above keeps the design's value and this restates it as
 * the theme's. If the theme's nav height changes, this is the one place to
 * follow it.
 */
:root{--ifnpkg-hdr:70px}

@media (max-width:1080px){
  :root{--ifnpkg-hdr:66px}
}

/*
 * The accent, as an editor can actually type it.
 *
 * The design sets the accented word in a `<span class="accent">`. No editor is
 * going to type a class name into a heading field, so the schema asks for `<em>`
 * instead and every heading's help text says so — which left the accent styled
 * and never reachable, and the italic came out as plain Poppins.
 *
 * These are the design's `.accent` declarations, on the element the content
 * model actually produces. `.iflynepal-pkg-accent` stays styled by the
 * transcription above, so markup written either way looks the same.
 */
.iflynepal-package :is(h1,h2,h3) em{
  display:inline-block;
  color:var(--ifnpkg-navy);
  font-family:var(--ifnpkg-serif-alt);
  font-size:1.14em;
  font-style:italic;
  font-weight:600;
  letter-spacing:-.025em;
}

/*
 * The h1's accent is the design's own, slightly different rule — 1.1em and no
 * inline-block — and it has to win over the one above.
 */
.iflynepal-package .iflynepal-pkg-trip-title h1 em{
  display:inline;
  font-size:1.1em;
}

/*
 * The hand-drawn underline, drawn.
 *
 * The dates and similar headings mark a phrase with it, and the schema's help
 * text names the catalogue's `.iflynepal-ink-mark` because that is the class an
 * editor is told to type. The catalogue's own rule starts the stroke at
 * `scaleX(0)` and waits for the archive's reveal.js to add `.is-drawn` — a
 * script this page does not load, so the mark was invisible on every package
 * page and would have stayed that way.
 *
 * Nothing animates it here, which is what the design's own static state is.
 */
.iflynepal-package .iflynepal-ink-mark::after{transform:scaleX(1)}

/*
 * The heading of a band — packing, map, FAQs, similar. The design writes the
 * 28px as an inline style on those two `<h2>`s rather than as a rule, so there
 * is nothing for the transcription to pick up.
 */
.iflynepal-pkg-band-h{margin-bottom:28px}

/*
 * The overview body reads at the opening paragraph's size. Client-directed.
 *
 * The design sets the opening paragraph at 18px in the ink colour and drops the
 * body beneath it to 15.5px in a grey, so the first paragraph leads and the rest
 * recedes. Asked for one voice instead: the body now takes the opening
 * paragraph's size, leading and colour, and the two read as continuous prose.
 *
 * The paragraph gap goes up with the type. 16px under a 15.5px line is a clear
 * break; under a 30px line it closes up, because what separates paragraphs is
 * the space against the leading around it, not the number.
 */
.iflynepal-pkg-prose p{
  color:var(--ifnpkg-ink);
  font-size:18px;
  line-height:1.7;
}

.iflynepal-pkg-prose p + p{margin-top:20px}

/*
 * The Highlights heading, given something to be noticed by. Client-directed;
 * the design has no mark here at all, just a sub-heading like any other.
 *
 * A navy badge to the left and a hairline under the pair. The badge is the same
 * tile the at-a-glance table uses — same size, same radius, same mist ground —
 * so this reads as the page's own furniture rather than a new idea, and the
 * rule beneath it separates the heading from the ticked list the way the
 * packing list's own top rule does.
 *
 * The badge is navy on mist rather than white on navy: the ticks in the list
 * below are already navy-on-mist circles, and a solid navy tile would outweigh
 * the ten of them.
 */
.iflynepal-pkg-sub-h--badged{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:0;
  padding-bottom:16px;
  border-bottom:1px solid var(--ifnpkg-line);
}

.iflynepal-pkg-sub-h__badge{
  display:grid;
  place-items:center;
  width:38px;height:38px;
  flex:none;
  border-radius:12px;
  background:var(--ifnpkg-mist);
  color:var(--ifnpkg-navy);
}

.iflynepal-pkg-sub-h__badge .iflynepal-pkg-ico{width:20px;height:20px}

/* The list clears the rule the heading now carries. */
.iflynepal-pkg-sub-h--badged + .iflynepal-pkg-check-list{margin-top:22px}

/*
 * Highlights as a card. Client-directed, and the same treatment the calendar
 * booker gets below — the design's outline vocabulary (a hairline, a large
 * radius, a soft drop and a wider ring) with a colour in it.
 *
 * The colour is in the *outline only*. The card's ground stays white to within
 * a few values, exactly as the booker's does, and nothing inside it is tinted:
 * the badge tile and the ten tick circles keep the mist ground they have
 * everywhere else on the page. A warm fill turns the whole block into a
 * highlight and stops it reading as part of the overview it belongs to — the
 * point is an accented edge, not a coloured panel.
 *
 * Gold rather than the booker's navy because this is the one block on the page
 * that is neither a control nor a table: navy says "use me", warm says "worth
 * reading" without asking for a decision. `--ifnpkg-gold` is the design's own
 * token, already carried by the archive's featured plan card, so it is not a
 * new colour on the site.
 *
 * The line is a deeper gold than the ring, not the token itself. #F2C879 at one
 * pixel reads as a smudge rather than an edge — a border wants more of a
 * colour's darkness than a fill does.
 */
.iflynepal-pkg-highlights{
  margin-top:44px;
  padding:28px;
  border:1px solid rgba(191,145,48,.3);
  border-radius:var(--ifnpkg-r-lg);
  background:#FFFDFA;
  box-shadow:0 18px 50px rgba(74,55,10,.05),0 0 0 4px rgba(242,200,121,.1);
}

/* The card owns the spacing now; the heading's own margins would double it. */
.iflynepal-pkg-highlights .iflynepal-pkg-sub-h{margin:0}

/*
 * The calendar booker, tinted. Client-directed.
 *
 * It keeps the outline the design gives it — same 1px, same 30px radius, same
 * shadow — and that outline is simply given some colour: the navy of the line
 * token at a little more strength, over a ground that lifts off white by a
 * degree or two. Enough that the card the page is actually asking a visitor to
 * use is not the same white as the four cards above and below it, and not so
 * much that it becomes a second price card competing with the aside.
 *
 * The tint goes on the wrapper rather than the card, so the calendar half keeps
 * its white ground and only the frame and the space around the summary carry
 * the colour.
 */
.iflynepal-pkg-booker{
  border-color:rgba(5,69,167,.3);
  background:#FBFCFF;
  box-shadow:0 18px 50px rgba(4,26,64,.09),0 0 0 4px rgba(5,69,167,.05);
}

/*
 * A wider thumbnail column beside the lead photograph. Client-directed, and a
 * deliberate departure from the design.
 *
 * The design gives the gallery a fixed 320px right-hand column, which at the
 * full 1360px shell leaves each of the four thumbnails 154 wide against 210
 * tall — noticeably upright. They are asked to be wider than that.
 *
 * A larger fixed width is the wrong instrument: it holds at 1440 and starves
 * the lead photograph on the way down, and by 800px the lead would be narrower
 * than the block of thumbnails beside it. A ratio widens them at every width
 * instead and can never invert the two. 1.9 is the lead photograph's share of
 * the row against the thumbnails' 1 — lower it to widen them further. The
 * design's own proportion works out at about 3.1.
 *
 * The ratio is written into the rule rather than held in a custom property, and
 * that is not a style preference: `calc(var(--n) * 1fr)` parses, because a
 * var() cannot be checked until it is substituted, and then fails at
 * computed-value time because calc() cannot multiply a flex unit. A property
 * that fails there is not ignored the way a bad declaration is — it resolves to
 * unset, so the column list becomes `none` and every photograph stacks into one
 * full-width column.
 *
 * `minmax(0, …)` on the lead, not a bare fr, or a wide photograph sets the
 * track's minimum to its own content width and pushes the thumbnails off.
 *
 * The gallery's meta row — breadcrumb under the lead, share under the
 * thumbnails — takes the same columns, or the share row stops lining up with
 * the photographs it sits beneath.
 *
 * The booking aside further down the page is untouched: it keeps
 * `--ifnpkg-aside`, because the price card is a fixed-width card and nothing
 * about this asked to change it.
 */
.iflynepal-pkg-gallery-grid,
.iflynepal-pkg-gallery-meta{
  grid-template-columns:minmax(0,1.9fr) 1fr;
}

/*
 * Below 760px the grid stops being two columns and becomes a swipeable strip
 * of whole photographs, which is the design's own arrangement and has no
 * thumbnail column to widen. The meta row stacks there too.
 */
@media (max-width:760px){
  .iflynepal-pkg-gallery-grid{grid-template-columns:none}
  .iflynepal-pkg-gallery-meta{grid-template-columns:1fr}
}

/*
 * The at-a-glance table's filler cells.
 *
 * The design draws the grid's lines as a border on every cell and then takes
 * two of them off again: `:nth-child(4n)` loses its right border and
 * `:nth-last-child(-n+4)` its bottom. Both are really saying "the last column"
 * and "the last row", and both are only true because that mock-up holds eight
 * facts in two full rows of four. Leave a row short and the rules land on the
 * wrong cells — a rule under the fourth cell of the *top* row, and a border
 * hanging off the last fact with nothing beside it.
 *
 * Rather than rewrite the design's rules to count, the template completes the
 * final row with empty cells, which is the shape those rules already describe.
 * They carry nothing and are hidden from assistive tech: they are the frame of
 * the table, not a fact with no value.
 */
.iflynepal-pkg-glance-item--filler{background:var(--ifnpkg-paper)}

.iflynepal-pkg-glance-item--filler:hover{background:var(--ifnpkg-paper)}

/*
 * The picked run in the calendar. The design already styles the two ends of a
 * range (.cal-day.is-start / .is-end) but never the days between them, because
 * the design's calendar was a single-date picker. The trip's duration is what
 * decides the length now, so the middle needs a fill of its own: the ends stay
 * navy, the days between take the mist tint, and the corners are squared so the
 * whole run reads as one bar rather than as five separate pills.
 */
.iflynepal-pkg-cal-day.iflynepal-pkg-is-range{
  border-radius:4px;
  background:var(--ifnpkg-mist);
  color:var(--ifnpkg-navy);
}
.iflynepal-pkg-cal-day.iflynepal-pkg-is-range:hover:not(:disabled){background:var(--ifnpkg-mist)}

/* A one-day trip is its own start and its own end, and two half-rounded
 * corners each side would leave it square. */
.iflynepal-pkg-cal-day.iflynepal-pkg-is-start.iflynepal-pkg-is-end{border-radius:12px}

/*
 * The video's one control: a play/pause button in the middle of the tile. The
 * browser's own controls are off, because on a silent looping backdrop they
 * are a scrubber, a volume slider and a running duration laid across the lead
 * photograph. This is the part of them the content actually needs.
 *
 * It matches the "view all photos" button beside it — the same translucent
 * white pill, the same navy mark — so the two controls on this tile read as one
 * set.
 */
.iflynepal-pkg-g-video-btn{
  position:absolute;
  top:50%;left:50%;
  z-index:2;
  display:grid;
  place-items:center;
  width:72px;height:72px;
  margin:0;
  padding:0;
  border:0;
  border-radius:999px;
  background:rgba(255,255,255,.92);
  color:var(--ifnpkg-navy);
  cursor:pointer;
  transform:translate(-50%,-50%);
  transition:opacity .3s ease,background .3s ease,transform .3s var(--ifnpkg-ease-card-out);
}
.iflynepal-pkg-g-video-btn:hover{background:#fff;transform:translate(-50%,-50%) scale(1.06)}
.iflynepal-pkg-g-video-btn:focus-visible{outline:2px solid var(--ifnpkg-navy);outline-offset:3px}
.iflynepal-pkg-g-video-ico{width:30px;height:30px;grid-area:1/1}

/* One icon is shown at a time, and which one is the video's state, not the
 * button's: .is-playing is written by the script from the media's own events. */
.iflynepal-pkg-g-video-ico--pause{display:none}
.iflynepal-pkg-is-playing .iflynepal-pkg-g-video-ico--play{display:none}
.iflynepal-pkg-is-playing .iflynepal-pkg-g-video-ico--pause{display:block}

/*
 * The button shows on hover, and nowhere else — the tile is a photograph with a
 * video in it, not a player, so nothing sits on top of the picture until
 * somebody reaches for it.
 *
 * Faded out rather than hidden, and three details follow from that:
 *
 *   * opacity, never `visibility` or `display`. Both of those take the button
 *     out of the tab order, and a control a keyboard cannot reach is a control
 *     that does not exist — the whole of what WCAG 2.2.2 asks for here is that
 *     moving content can be stopped.
 *   * :focus-within on the tile brings it back when it is tabbed to, so it is
 *     visible at the moment it has focus rather than a ring around nothing.
 *   * the whole rule sits inside `hover: hover`. A touch screen has no hover to
 *     give, so on a phone the button is simply always there; asking a finger to
 *     hover first would leave the video unstoppable.
 */
@media (hover: hover) {
  .iflynepal-pkg-g-video-btn{opacity:0}
  .iflynepal-pkg-g-tile--video:hover .iflynepal-pkg-g-video-btn,
  .iflynepal-pkg-g-tile--video:focus-within .iflynepal-pkg-g-video-btn,
  .iflynepal-pkg-g-video-btn:focus-visible{opacity:1}
}

@media (prefers-reduced-motion:reduce){
  .iflynepal-pkg-g-video-btn{transition:none}
  .iflynepal-pkg-g-video-btn:hover{transform:translate(-50%,-50%)}
}

@media (max-width:860px){
  .iflynepal-pkg-g-video-btn{width:58px;height:58px}
  .iflynepal-pkg-g-video-ico{width:24px;height:24px}
}

/*
 * The included-in-the-price ticks are green, matching the legacy site's
 * `.tick-circle` on /trip/<slug> (#4caf50, a white mark in a filled circle).
 * Client-directed: the ticks read as a list of confirmations rather than as
 * more of the page's navy furniture.
 *
 * It is the fill that changes and nothing else — the circle keeps the design's
 * 24px, its radius and the mark's 13px/2.4 stroke, so the list's rhythm is
 * still the design's. The mark is `color`, because the icon is drawn with
 * `currentColor`; setting `fill` would miss it.
 *
 * The crossed list opposite it is the same ring in a light red — Material's
 * Red 400 rather than its 500, because the green is a confirmation and the red
 * is only a note about what the price does not cover: at full strength it reads
 * as an error beside it.
 *
 * Client-directed: the circle is an outline, not a filled disc. So the ground
 * goes back to nothing and the ring is a 1px `currentColor` border, which means
 * one declaration sets the ring and the mark together and the two can never
 * drift apart. `box-sizing` is stated rather than inherited from the theme's
 * Tailwind preflight: without it the border is added outside the design's 24px
 * and the circles sit a pixel proud of the text they line up against.
 *
 * The Highlights list in the overview is deliberately left alone and keeps its
 * mist circles: §5.3o records the client rejecting a retint inside that block,
 * on the grounds that it stops reading as part of the overview it belongs to.
 * So both rules are scoped to the lists that answer "what does the price
 * cover" — the Dates section's pair and the price card's.
 *
 * Colour is not the only thing saying which list is which: each has its own
 * heading, and the mark inside the circle is a tick or a cross. A visitor who
 * cannot separate the two hues still reads the list correctly (WCAG 1.4.1).
 */
#ifnpkg-dates .iflynepal-pkg-check-list .iflynepal-pkg-tick,
#ifnpkg-dates .iflynepal-pkg-check-list--x .iflynepal-pkg-tick,
.iflynepal-pkg-price-card .iflynepal-pkg-check-list .iflynepal-pkg-tick{
  box-sizing:border-box;
  background:none;
  border:1px solid currentColor;
}
#ifnpkg-dates .iflynepal-pkg-check-list .iflynepal-pkg-tick,
.iflynepal-pkg-price-card .iflynepal-pkg-check-list .iflynepal-pkg-tick{
  color:var(--ifnpkg-tick-green,#4CAF50);
}
#ifnpkg-dates .iflynepal-pkg-check-list--x .iflynepal-pkg-tick{
  color:var(--ifnpkg-tick-red,#EF5350);
}

/*
 * The handwritten note over the calendar, moved to the right. Client-directed:
 * the design hangs it at `left: 22%`, and it is wanted against the calendar's
 * right-hand edge instead.
 *
 * The arrow has to travel with it. It is drawn curving down and to the right,
 * which from the right-hand edge would point past the calendar at nothing, so
 * the row is reversed and the glyph mirrored — the note now reads left to right
 * with the arrow dropping down-left into the grid it is talking about. Mirroring
 * the SVG rather than redrawing its two paths keeps one copy of the shape, and
 * it is the same shape the archive's notes use.
 *
 * `right` and `left:auto` together, because the rule being overridden sets
 * `left` and a lone `right` would leave both edges pinned and stretch the note.
 * It stays clear of the summary panel below it at every width it is shown at —
 * the design already hides it under 1000px, where the booker's padding closes up.
 */
#ifnpkg-dates .iflynepal-pkg-annot--dates{
  left:auto;
  right:4%;
  flex-direction:row-reverse;
}
#ifnpkg-dates .iflynepal-pkg-annot--dates svg{
  transform:scaleX(-1);
}

/*
 * The gateway's button, in the Book now panel.
 *
 * Only the box it sits in is styled — its width, where it sits and what is under
 * it. The button itself is Easy PayPal & Stripe's own markup and is left exactly
 * as that plugin renders it: PayPal's and Stripe's buttons are brand assets with
 * their own rules, and a stylesheet that restyles them is one that breaks them
 * on the next gateway update.
 */
.iflynepal-pkg-pay {
	margin-top: 4px;
}

.iflynepal-pkg-pay > * {
	max-width: 100%;
}

.iflynepal-pkg-pay + .iflynepal-pkg-sum-note {
	margin-top: 10px;
}
"""

header = """/**
 * Single package page styles.
 *
 * Transcribed from the approved design,
 * Downloads/iFlyNepal/retreats-nepal/retreats-nepal-package-single-design.html:
 * every value is that file's, and only the names differ. It is generated rather
 * than hand-copied because re-typing 1,000 declarations is how a 24px quietly
 * becomes a 22px.
 *
 * Namespacing, and why it is not the archive's:
 *
 *   .iflynepal-pkg-*   class names, so the design's .button, .gallery and .day
 *                      cannot collide with the theme's own .iflynepal-button or
 *                      with the catalogue stylesheet.
 *   --ifnpkg-*         custom properties, declared on .iflynepal-package rather
 *                      than on :root so a plugin sheet cannot repaint the site.
 *
 * The site chrome the theme already owns is deliberately absent: the header, the
 * nav, the footer, the body resets and the paper-grain overlay. A plugin that
 * restyles those is a plugin that breaks every other page.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

"""

io.open(os.path.join(SP, 'package.css'), 'w', encoding='utf-8', newline='\n').write(
    header + css.strip() + '\n' + EXTRAS.lstrip('\n')
)
print('written', len((header + css).split('\n')), 'lines')
