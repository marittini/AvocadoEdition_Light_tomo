# 페어 프로필 게시판 (`otaku`)

A board skin for pair characters. One post holds one pair (two people), split into the 프로필 · 관계 · 연표 tabs.
An AU is stored as a reply to the original, and the view page switches between 원본 and AU in place.

## Files

| File | Role |
| --- | --- |
| `otaku.lib.php` | Shared helpers: the skin table `{prefix}otaku_data` (created lazily), load/save/cleanup, the attachment-slot map `OTAKU_SLOTS`, and the point colour CSS variable |
| `list.skin.php` | Original posts only, shown as thumbnail cards (3 columns on PC, 1 on mobile), with the mini characters standing on the point-colour line |
| `view.skin.php` / `view.js` | 원본 + readable AUs, tabs per version, AU switch keeps the current tab and updates the URL; an AU URL redirects to its original with `&au=` |
| `write.skin.php` / `write.js` | Tabbed editor, row add/preset/move/remove, auto-growing text areas, image previews |
| `write_update.head.skin.php` | Session token check, input cleaning and limits, relation links limited to public originals on the same board, subject from the pair name, plain-text search content |
| `write_update.skin.php`, `delete*.tail.skin.php` | Save JSON after insert; delete orphaned rows |

## Attachment slots

| Slot | Use |
| --- | --- |
| 0, 1 | Person 1 / 2 photo (an AU without its own photo uses the original's) |
| 2 | List thumbnail (16:9) |
| 3, 4 | Person 1 / 2 mini character (shown at natural size, max 64px) |

The board's upload count must be 5 or more.

## Design

No colours or fonts are set. Paper = `.theme-box`, buttons = `.ui-btn` / `.point` / `.etc`. The current-tab underline and the
thumbnail line use the design setting's point colour. Hairlines are `currentColor` mixed to 16%. Korean text breaks by word (`keep-all`).
The site's global `* {transition: all .3s}` is turned off on the tabs so the underline moves at the same moment as the panel.

## Names, colour, quote bubble (update)

- Each person has an optional **이름 읽는 법** (ruby above the name) and a **지향색** (`#rrggbb`, optional).
- List: hovering a mini character shows that person's 한마디 in a speech bubble filled with the 지향색; the text is black or
  white by contrast. No colour = a dark grey bubble. View: the quote gets a thin line in the 지향색.
- Intro text is justified. Profile photos are not links any more (the full-size file in a new tab bypassed the right-click protection).
- Mobile: the profile and relation tabs show one person at a time with name buttons; the choice is shared by both tabs.
- List names never break inside one person's name; the space for the minis is kept on the title line only. Quotes use balanced lines.

## Test

```
TEST_URL=http://127.0.0.1:8080 TEST_USER=admin TEST_PASSWORD=... TEST_IMAGE=/path/to/any.jpg TEST_SQL="mysql -uroot g5" node tests/otaku_smoke.cjs
```

It covers pair creation with all five image slots, row reorder and removal, relation links, the timeline, a forged-request rejection,
a text timeline entry ("초등학교 6학년"), AU copy, photo fallback, AU switching, the AU URL redirect, the list cards (no AU count) and column count, and the AU delete cleanup.
It also checks the name ruby, the 지향색 picker, the mini-character quote bubble (colour and readable text colour), that profile photos are not links,
justified intro text, the mobile person tabs (shared between profile and relation) and that list names are kept whole.
