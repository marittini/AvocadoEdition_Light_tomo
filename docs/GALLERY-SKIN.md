# 갤러리 · 글 보관함 (`custom_skin/gallery`)

Rewrite of 사도세자's `gallery_thumb_mac 2.1` (갤러리_mac_os, based on 대하's thumbnail gallery) for PHP 8.4 / Light TOMO,
restyled to the calm look of the other custom skins, and extended so novels can be archived next to art.
Source: https://www.postype.com/@sasasasado/post/14563010

## What it does

- **Folders**: each board category is a folder that opens and closes (closed by default, like the original's 접힘 patch).
  Open folders are remembered in the browser. Without categories, or while searching, it is a normal paged grid.
- **Two kinds of post** (`wr_2`): 그림 (art) shows a square thumbnail; 글 (text) shows a paper card of the same size
  with the opening lines and a character count (spaces excluded).
- **Viewer**: clicking a card opens the post over the list. It loads the core view page, so read level, secret and
  member-only checks are the core's. The URL changes to the post's URL (shareable), Back or Esc closes it.
  If the core page answers with a password or login screen, the browser goes there instead.
- **Reading**: same-category 이전 글 / 다음 글 (older → newer, so serial chapters read in order). Text posts use a narrower
  measure and larger line height.
- **Privacy**: 전체공개 / 멤버공개 (`wr_secret=1`) / 보호글 (`wr_protect`, bcrypt) / 비밀글 (`wr_option` secret).
  Protected posts open through `protect.php`. Old plaintext passwords from the original skin still work.
- **Files**: attachment thumbnails are served through `bbs/archive_file.php`. `custom_skin/gallery` is added to its
  privacy-skin list so member-only and protected rules apply to attachments too.
- **Write**: type, category, privacy, title, source (`wr_1`), site editor, images. Art posts may have no text.

## Changes from the original

| Original | Now |
| --- | --- |
| Own `view.php` loaded by AJAX, skipping core permission checks | Core view page, fetched and shown in place |
| Protected-post password stored as plain text | bcrypt hash, plain-text values still accepted |
| `ALTER TABLE` on every save | Column created once when the super admin opens the write page |
| mac window chrome (traffic-light buttons, gradient), Font Awesome, hardcoded fonts | Plain folders, Material Icons, site design settings |
| `<?` short tags, unescaped output, raw `$_GET` in SQL | PHP 8.4-clean, escaped, integer ids |
| No request check | Skin session token on posts and comments |

## Files

| File | Role |
| --- | --- |
| `gallery.lib.php` | Kinds, privacy check/labels, password check, thumbnails, excerpt and character count, assets, token |
| `list.skin.php` | Folders or paged grid, cards, search, the viewer container |
| `gallery.js` | Folder toggles (remembered), viewer (fetch, history, Esc, prev/next), comment edit |
| `view.skin.php` | Post (also the viewer's source): meta, images, body, files, prev/next, actions, comments |
| `view_comment.skin.php` | Slim comments with inline edit |
| `write.skin.php` | Write form |
| `write_update.head.skin.php` / `write_update.skin.php` | Token, type, source, privacy |
| `write_comment_update.head.skin.php` | Token, no comments on posts the user can't read |
| `protect.php` | Protected-post password page |

## Design

No colours or fonts are set. Paper = `.theme-box`, inputs = `.frm_input`, buttons = `.ui-btn` / `.point` / `.etc`.
Hover lines use the point colour. Hairlines are `currentColor` mixed to 16%. Korean breaks by word.

## Test

```
TEST_URL=http://127.0.0.1:8080 TEST_USER=admin TEST_PASSWORD=... TEST_MEMBER=<member id> TEST_IMAGE=/path/to/image.jpg \
TEST_SQL="mysql --default-character-set=utf8mb4 -uroot g5" node tests/gallery_smoke.cjs
```
