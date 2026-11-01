# pixiv 소설 글자 수 · 클릭 효과 · 이모티콘 관리

## 1. pixiv: 소설 글자 수

`custom_skin/pixiv` list cards and series episode thumbnails show the character count of novel works (spaces excluded,
HTML tags and entities stripped) in the bottom-right thumbnail badge, the same place and style as `2장` on image works,
e.g. `10,000자`. Secret works do not show it.

- `pixiv.lib.php`: `pixiv_char_count()`; `pixiv_series_works()` also selects `wr_content`.
- `list.skin.php`, `series.skin.php`, `style.css`.
- `view_comment.skin.php`: PHP 8 warnings for undefined `$c_id` / `$c_wr_content` fixed (the edit text is now escaped).

## 2. 클릭 효과 (원 + 효과음)

Reference: https://www.postype.com/@0penhe4rt/post/20916421

A ring spreads from the pressed point and fades, and a short sound plays. Front pages only (not the admin).

- `tail.click_effect.php`: settings at the top — on/off, sound file (`sound/click.mp3`, `''` for no sound), volume,
  colour (`''` = design setting's point colour), size, speed. Included from `tail.php` and from `index.php`
  (the outer frame with the menu).
- `js/click-effect.js`: `pointerdown` in the capture phase (works before a link navigates), primary button only,
  four audio elements in rotation so quick clicks are not cut off, blocked playback ignored.
- `css/click-effect.css`: `pointer-events: none`, `transform: scale` animation; hidden for `prefers-reduced-motion`
  (the sound still plays).
- `sound/click.mp3`: the provided click sound.

## 3. 관리자 > 게시판관리 > 이모티콘관리

Reference: https://www.postype.com/@0penhe4rt/post/20566472

Light shipped without the admin emoticon pages, without `$g5['emoticon_table']` in `data/dbconfig.php`, and without
`css/style.emoticon.css` (used by the mmb and ottreview emoticon popups).

- `adm/admin.menu300.php`: `300700 이모티콘관리` (permission `emoticon_admin`).
- `adm/emoticon_list.php`: add (code + image with preview), list with inline code edit, select edit/delete.
- `adm/emoticon_form_update.php`, `adm/emoticon_list_update.php`: admin token and permission checks; codes are 1–30
  characters without spaces, quotes, `<>`, `&` or `\`, and must be unique; images must really be gif/png/jpg/webp
  (`getimagesize`), up to 2MB, and are saved as `data/emoticon/<date>_<random>.<ext>`; deleting removes the file.
- `adm/emoticon.lib.php`: shared helpers; creates the table (utf8mb4) when it is missing.
- `extend/community.config.php`: defines `$g5['emoticon_table']` when `dbconfig.php` lacks it.
- `install/install_db.php`: new installs write the line into `dbconfig.php`.
- `css/style.emoticon.css`: the popup style.

## Test

```
TEST_URL=... TEST_USER=admin TEST_PASSWORD=... TEST_MEMBER=<member id> TEST_IMAGE=/path/to/image.png node tests/emoticon_admin_smoke.cjs
TEST_URL=... TEST_USER=admin TEST_PASSWORD=... node tests/click_effect_smoke.cjs
TEST_URL=... TEST_USER=admin TEST_PASSWORD=... TEST_IMAGE=... node tests/pixiv_board_smoke.cjs   # includes the list character count
```
