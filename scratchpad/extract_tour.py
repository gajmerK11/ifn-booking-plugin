# -*- coding: utf-8 -*-
"""Pulls the Tour single design's copy into JSON, so the seeder plants the
designer's words rather than mine.

Mapping note: the Tour design writes a day as prose paragraphs with an
Overnight/Altitude/Travel foot. The package model — which stays exactly the
Retreat one, by instruction — has a day title, a sub-line, a short summary and a
timeline of `time | what happens` rows. Prose paragraphs become timeline rows,
and the row's left-hand cell takes the stage the paragraph itself names (Morning,
Afternoon, Evening) or the day's own movement where it names none. Nothing is
invented beyond those one-word labels; the design's foot line is appended to the
day's sub-line so the altitude and the overnight stop are not lost.
"""

import io, json, re, html

SRC = r"C:\Users\asus\Downloads\iFlyNepal\nepal-tour\nepal-tour-package-single-design.html"
OUT = r"C:\Users\asus\AppData\Local\Temp\claude\C--Users-asus-Local-Sites-iflynepal-app-public\dcc87243-ce04-4d76-bbca-39fcee51e236\scratchpad\tour-single.json"

s = io.open(SRC, encoding='utf-8', errors='replace').read()
body = s[s.find('</style>'):]

def text(h):
    h = re.sub(r'<br\s*/?>', ' ', h)
    h = re.sub(r'<[^>]+>', '', h)
    return html.unescape(re.sub(r'\s+', ' ', h)).strip()

def keep_em(h):
    h = re.sub(r'<span class="accent">(.*?)</span>', r'<em>\1</em>', h, flags=re.S)
    h = re.sub(r'<span class="w">(.*?)</span>', r'\1', h, flags=re.S)
    h = re.sub(r'<(?!/?em\b)[^>]+>', '', h)
    return html.unescape(re.sub(r'\s+', ' ', h)).strip()

out = {}

m = re.search(r'<h1[^>]*>(.*?)</h1>', body, re.S)
out['heading'] = keep_em(m.group(1)) if m else ''

# At a glance — <div class="glance-item">…<small>label</small><strong>value</strong>
out['glance'] = [
    [text(a), text(b)]
    for a, b in re.findall(r'<div class="glance-item".*?<small>(.*?)</small>\s*<strong>(.*?)</strong>', body, re.S)
]

# Overview
m = re.search(r'<p class="intro"[^>]*>(.*?)</p>', body, re.S)
out['overview_intro'] = text(m.group(1)) if m else ''
m = re.search(r'<div class="prose[^"]*"[^>]*>(.*?)</div>', body, re.S)
out['overview_body'] = '\n\n'.join(text(p) for p in re.findall(r'<p[^>]*>(.*?)</p>', m.group(1), re.S)) if m else ''

m = re.search(r'<ul class="check-list[^"]*"[^>]*>(.*?)</ul>', body, re.S)
out['highlights'] = [text(li) for li in re.findall(r'<li[^>]*>(.*?)</li>', m.group(1), re.S)] if m else []

for key, pattern in (
    ('itinerary_heading', r'id="itinerary".{0,900}?<h2[^>]*>(.*?)</h2>'),
    ('dates_heading',     r'id="dates".{0,900}?<h2[^>]*>(.*?)</h2>'),
    ('packing_heading',   r'id="packing".{0,900}?<h2[^>]*>(.*?)</h2>'),
    ('map_heading',       r'id="map".{0,1400}?<h2[^>]*>(.*?)</h2>'),
    ('faq_heading',       r'id="faqs".{0,900}?<h2[^>]*>(.*?)</h2>'),
):
    m = re.search(pattern, body, re.S)
    out[key] = keep_em(m.group(1)) if m else ''

for key, pattern in (
    ('dates_lead', r'id="dates".{0,900}?<p class="lead"[^>]*>(.*?)</p>'),
    ('faq_lead',   r'id="faqs".{0,900}?<p class="lead"[^>]*>(.*?)</p>'),
):
    m = re.search(pattern, body, re.S)
    out[key] = text(m.group(1)) if m else ''

# Short itinerary: Day n, title, sub-line
out['short'] = [
    {'num': text(n), 'title': text(t), 'sub': text(p)}
    for n, t, p in re.findall(
        r'<div class="sum-day">.*?<b>(.*?)</b>.*?<h3>(.*?)</h3>\s*<p>(.*?)</p>', body, re.S)
]

# Day by day
STAGE = (
    ('in the morning', 'Morning'), ('early morning', 'Early morning'), ('this morning', 'Morning'),
    ('in the afternoon', 'Afternoon'), ('by afternoon', 'Afternoon'),
    ('in the evening', 'Evening'), ('at night', 'Evening'), ('overnight', 'Evening'),
)

days = []
# `day is-open` and `day is-peak` are days too — matching the bare class dropped
# the arrival day and the summit day, and every day after shifted up a number.
for m in re.finditer(r'<article class="day[^"]*".*?</article>', body, re.S):
    d = m.group(0)
    title = re.search(r'<span class="dt-title"[^>]*>(.*?)</span>', d, re.S)
    sub = re.search(r'<span class="dt-sub"[^>]*>(.*?)</span>', d, re.S)
    prose = re.findall(r'<div class="day-prose"[^>]*>(.*?)</div>', d, re.S)
    paras = [text(p) for p in re.findall(r'<p[^>]*>(.*?)</p>', ' '.join(prose), re.S)] if prose else []
    foot_block = re.search(r'<div class="day-foot"[^>]*>(.*?)</div>\s*(?:</div>|</article>)', d, re.S)
    foot = [text(x) for x in re.findall(r'<span[^>]*>(.*?)</span>', foot_block.group(1), re.S)] if foot_block else []

    rows = []
    for para in paras:
        low = para.lower()
        label = ''
        for needle, word in STAGE:
            if needle in low[:90]:
                label = word
                break
        rows.append([label, para])

    days.append({
        'title': text(title.group(1)) if title else '',
        'sub': text(sub.group(1)) if sub else '',
        'foot': [f for f in foot if f],
        'rows': rows,
    })
out['days'] = days

# Price and the aside
m = re.search(r'<span class="eyebrow"[^>]*>(.*?)</span>\s*<div class="price-from"', body, re.S)
out['price_eyebrow'] = text(m.group(1)) if m else ''
m = re.search(r'<div class="price-from"[^>]*>(.*?)</div>', body, re.S)
raw_price = text(m.group(1)) if m else ''
out['price_raw'] = raw_price
mm = re.search(r'([\d,]+)\.?(\d{2})?', raw_price.replace('From', ''))
out['price_amount'] = mm.group(1).replace(',', '') if mm else ''
out['price_currency'] = 'USD' if 'USD' in raw_price else ''
out['price_points'] = [text(li) for li in re.findall(r'<ul class="check-list"[^>]*>(?:.*?)</ul>', body, re.S)[:1]]
m = re.search(r'<p class="price-foot"[^>]*>(.*?)</p>', body, re.S)
out['price_foot'] = text(m.group(1)) if m else ''

# Included / not included
cols = []
for c in re.finditer(r'<div class="price-col"[^>]*>(.*?)</div>\s*(?=<div class="price-col"|</div>)', body, re.S):
    blk = c.group(1)
    head = re.search(r'<h3[^>]*>(.*?)</h3>', blk, re.S)
    items = [text(li) for li in re.findall(r'<li[^>]*>(.*?)</li>', blk, re.S)]
    if head and items:
        cols.append({'head': text(head.group(1)), 'items': items})
out['price_cols'] = cols

# Packing — <li><svg/><span><b>Category</b>text</span>
out['packing_items'] = [
    (text(b) + ' — ' + text(t)) if text(b) else text(t)
    for b, t in re.findall(r'<li[^>]*>\s*<svg.*?</svg>\s*<span>(?:<b>(.*?)</b>)?(.*?)</span>\s*</li>', body, re.S)
]

# Map
m = re.search(r'<iframe[^>]+src="([^"]+)"', body)
out['map_embed'] = html.unescape(m.group(1)) if m else ''
m = re.search(r'id="map".{0,2500}?<strong>(.*?)</strong>', body, re.S)
out['map_place'] = text(m.group(1)) if m else ''

# FAQs — plain <details><summary>
faq_area = body[body.find('class="faq-list"'):]
out['faqs'] = [
    {'q': text(q), 'a': text(a)}
    for q, a in re.findall(r'<details[^>]*>\s*<summary[^>]*>(.*?)</summary>(.*?)</details>', faq_area, re.S)
]

io.open(OUT, 'w', encoding='utf-8').write(json.dumps(out, ensure_ascii=False, indent=1))

print('heading   :', out['heading'])
print('glance    :', len(out['glance']), out['glance'])
print('intro     :', out['overview_intro'][:80], '...')
print('body      :', len(out['overview_body']), 'chars')
print('highlights:', len(out['highlights']))
print('short     :', len(out['short']), '| days:', len(out['days']))
if out['days']:
    d = out['days'][1]
    print('  day 2   :', d['title'], '|', d['sub'], '|', len(d['rows']), 'rows', [r[0] for r in d['rows']], '| foot', d['foot'])
print('price     :', out['price_amount'], out['price_currency'], '| eyebrow:', out['price_eyebrow'][:50])
print('cols      :', [(c['head'], len(c['items'])) for c in out['price_cols']])
print('packing   :', len(out['packing_items']), out['packing_items'][:1])
print('map       :', out['map_place'], out['map_embed'][:60])
print('faqs      :', len(out['faqs']), out['faqs'][0]['q'][:60] if out['faqs'] else '')
print('headings  :', {k: out[k] for k in ('itinerary_heading', 'dates_heading', 'packing_heading', 'map_heading', 'faq_heading')})
print('\nwritten:', OUT)
