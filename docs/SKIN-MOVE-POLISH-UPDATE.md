# 스킨 폴더 정리 · 웹폰트 순서 · 이모티콘 크기 · 스킨별 손질

All changes are PHP 8.4 compatible and keep the design rules of the custom skins: no fixed colours or fonts in
front skins, `.theme-box` / `.ui-btn` / `.frm_input`, the point colour, Korean word breaking, mobile layouts.

## 1. Skin folders: `skin/board/custom_skin/<name>` → `skin/board/<name>`

The seven custom skins (`bbscategory`, `diary`, `gallery`, `guestbook`, `otaku`, `ottreview`, `pixiv`) now live
directly in `skin/board/`.

- `extend/skin_move.extend.php` moves existing boards automatically. On the first page view after the update, every
  board whose PC or mobile skin is `custom_skin/<name>` is switched to `<name>` (only when the new folder exists).
  The board being opened in that same request is fixed in place too, so nothing breaks while it runs.
- `adm/admin.lib.php`: the skin list no longer shows a `custom_skin/<name>` entry when `<name>` already exists.
- `bbs/archive_file.php`: private-file rules accept both the new and the old skin names.
- Paths in skin files, READMEs, docs and tests were updated.

**After uploading:** open any page once (as anyone), then the old `skin/board/custom_skin/` folder on the server can
be deleted. The update ZIP does not delete it by itself.

## 2. Webfonts: order and a tidy list (관리자 > 디자인 설정 > 웹폰트)

- Each registered font is one folded line: name, family, a sample (`가나다 Aa 123`) drawn in that font, and an
  **사용 중** badge when a design setting uses it. Click a line to open its fields.
- **↑ / ↓** move a font. The saved order is the order of the font dropdowns in the design settings.
- With two or more fonts: a count, **찾기** (filters by name / family / URL) and **모두 펼치기 / 모두 접기**.
- Files: `adm/tomo_fonts.inc.php`, `adm/js/tomo-fonts.js`, `adm/css/tomo-settings.css`.

## 3. Emoticons everywhere

- **Fixed size** (관리자 > 게시판관리 > 이모티콘관리 > 표시 크기): height in px (0 = original size). It applies to every
  `img.emoticon` on the site — posts, comments, all skins — through one style in `head.sub.php`.
  Stored in `cf_tomo_emoticon_size` (added automatically).
- **Comments on any skin**: `bbs/view_comment.php` turns emoticon codes into images, and when the comment form is
  shown an **이모티콘** button is added under the comment box automatically (skins that already have one keep theirs).
- **Disappearing emoticons fixed** (diary and other skins using the shared image resizer): `js/viewimageresize.js`
  set `width="0"` on images that had not loaded yet, so emoticons vanished until a cached reload. It now skips
  emoticons and images that are not loaded, and re-measures when they load. Emoticons are no longer lazy-loaded.
- Files: `lib/emoticon.lib.php`, `adm/emoticon_list.php`, `adm/emoticon_size_update.php`, `bbs/view_comment.php`,
  `js/emoticon-picker.js`, `css/emoticon-picker.css`, `extend/community.config.php`, `js/viewimageresize.js`.

## 4. Edit / delete icons

`M` / `X` text buttons became a pen (`edit`) and a trash can (`delete`) icon: `mmb` log and comment headers,
`bbscategory` post view. The OTT review attachment remove button uses a trash can too.

## 5. Home: centred on mobile

On narrow screens the main page content is centred vertically like on PC (`css/archive-responsive.css`).

## 6. ucho_menu

- The sub-menu closes when the mouse leaves, also after clicking the icon and on the first PC visit with BGM
  (clicking left focus on the button, which kept it open). Clicking a sub-menu link closes it as well.
  Keyboard users still open it by focusing the icon.
- Icons without a sub-menu (홈, 로그인, …) show their name as a speech bubble with a tail, like the sub-menus.
  Colour, font, size and corners still come from 디자인 설정 > 메뉴 툴팁; the tail takes the same background.

## 6-1. Mobile menu size

디자인 설정 > 메뉴 디자인 > 아이콘 줄에 **모바일 메뉴 글자 크기**(px, 10–40)를 추가했습니다. 휴대폰 · 태블릿에서 펼치는
메뉴(`#mo_header`)의 글자가 그 크기로 나오고 아이콘도 같은 비율로 따라갑니다. 비우면 예전처럼 아이콘 크기의 1.2배입니다.
Stored in `menu_icon.cs_etc_14`; drawn by `adm/design_form_css.php`. Test: `tests/mobile_menu_size_smoke.cjs`.

## 6-2. pixiv: one page at a time on PC

The page layer used to resize to every image, so its width jumped between pages. It is now sized once for the whole work
(the largest page, scaled to the screen decides) and each page is scaled inside it. `skin/board/pixiv/viewer.js`;
`tests/pixiv_board_smoke.cjs` now uses a tall and a wide page and checks the width stays the same.

## 7. otaku (pair profiles)

- **Name reading (ruby)**: optional 이름 읽는 법 per person, shown small above the name.
- **지향색** per person (colour picker or `#rrggbb`, can be cleared). Used for the mini character speech bubble and the
  quote highlighter. Bubble text is black or white, whichever reads better on that colour.
- **Quote style** (chosen per post in the write form, `qstyle`): 없음 (plain text), 하이라이트 (default: a highlighter
  stroke on the lower half of the text, per line, in the 지향색 or the point colour) or 말풍선 (a speech bubble filled with
  the 지향색 with readable text colour, light grey when no colour, tail pointing up to the name).
  The quote marks are chosen per post in the write form: “ ” (default), 「 」 or none (`marks` in the profile data;
  AU posts keep the original's choice).
- **Mini bubble**: hovering a mini character on the list shows that person's 한마디.
- **Intro text** is justified.
- **Mobile**: profile and relation tabs show one person at a time, with name buttons; the choice is shared between
  the two tabs.
- **Profile photos** are no longer links to the full-size file (opening it in a new tab bypassed the right-click
  protection).
- **Line breaks**: list names wrap between people, not inside a name; only the title line leaves room for the minis.
  The quote uses balanced lines.

## 8. diary

- No fixed fonts any more: it follows the site font (design settings, registered webfonts).
- The write page is wider (960px) with less paper padding, and the editor toolbar wraps instead of scrolling.
- Emoticons: see 3.

## 9. gallery

- Card titles are centred under the image.
- While a post is open, the list boxes behind it and the site menu are hidden, so the semi-transparent paper shows no
  boxes through it and the close button never sits under the menu (also on PC, where the menu is in the outer frame).

## 10. OTT review

- **Thumbnail focus**: on the write page, once a thumbnail exists (cover image, thumbnail link, first image link or
  first attachment), click or drag on the image — or use the arrow keys — to choose the part shown in the list. The
  small preview shows the crop. Stored in `wr_8` as `x,y` percentages; empty = centre. Also used for the thread hero.
- **Emoticons**: button under the body editor and in the comment form; editor posts now render emoticon codes too.

## Tests

New: `tests/font_order_smoke.cjs`, `tests/ucho_menu_smoke.cjs`.
Extended: `tests/emoticon_skins_smoke.cjs` (OTT review body/comment/focus, diary emoticon width),
`tests/emoticon_admin_smoke.cjs`, `tests/otaku_smoke.cjs`, `tests/gallery_smoke.cjs`, skin paths in all tests.
