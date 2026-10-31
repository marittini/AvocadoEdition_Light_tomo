# 방명록 (`custom_skin/guestbook`)

Rewrite of the Avocado Light guestbook skin `new_memo_l1` (by 사도세자, based on 늘's new_memo) for PHP 8.4 / Light TOMO,
restyled to the calm look used by the other custom skins.
Source: https://www.postype.com/@sasasasado/post/14331108

## What it does

Everything happens on the list page: write, read, edit, delete, comment.

- **Comments**: open to anyone with the board's comment level, newest at the bottom. Each entry has a "댓글 N" toggle. After commenting,
  deleting or editing a comment, the page returns to that entry (on the right page) with its comments open.
- **Secret posts**: follow the board's secret setting. Admins and the member who wrote it can read it. A guest post opens after the
  core password check.
- **Guests**: name, password and captcha. Editing or deleting goes through the core password page.
- **Emoticons**: uses the site emoticon table (managed from the mmb skin). An "이모티콘" picker inserts the code at the cursor.
- **Text**: content is stored as plain text. On display it gets URL auto-links, `#hashtag` (searches the board, no board-type
  setting needed) and `[[text]]` (point colour) highlighting.

## Changes from the original

| Original | Now |
| --- | --- |
| Stored the secret-post password in a plain cookie | Core `password.php` / `password_check.php` session |
| Links multiplied on every edit (`<a>` saved back into the text) | Raw text kept; links are added only when shown |
| `ALTER TABLE` on every list row (`inc.list_main.php`) | Removed |
| Copies of core `write.php`, `write_comment_update.php`, `view_comment.php` | Core handlers + skin hooks |
| Font Awesome kit from an external CDN | Site Material Icons |
| `<?` short tags, undefined indexes, `$g5[path]` | PHP 8.4-clean |
| No request check | Skin session token on every post and comment |
| One captcha per form (broken with several on a page) | One captcha moved to the form in use |

## Files

| File | Role |
| --- | --- |
| `guestbook.lib.php` | Helpers: formatting, emoticons, secret check, token, the shared post/comment form, return-to-entry redirect |
| `list.skin.php` | The page: compose box, entries, comments, search, the shared captcha and emoticon panel |
| `guestbook.js` | Toggles, inline edit, emoticon insert, moving captcha/emoticon panel, `#gb-ID-c` open-on-load |
| `write.skin.php` | Standalone form (guest edit after the password check, or a direct `write.php` visit) |
| `view.skin.php` | Redirects a single-post URL to that entry on the list |
| `write_update.head.skin.php` | Token, required content, subject from the first line, plain text only |
| `write_comment_update.head.skin.php` | Token, required content, no comments on a secret post the user can't read |
| `write_update.skin.php`, `write_comment_update.skin.php`, `delete_comment.tail.skin.php` | Return to the entry |

## Design

No colours or fonts are set. Paper = `.theme-box`, inputs = `.frm_input`, buttons = `.ui-btn` / `.point` / `.etc`.
Notice label, hashtags and `[[text]]` use the point colour. Hairlines are `currentColor` mixed to 16%. Korean breaks by word.

## Test

```
TEST_URL=http://127.0.0.1:8080 TEST_USER=admin TEST_PASSWORD=... TEST_MEMBER=<member id> TEST_SQL="mysql -uroot g5" \
TEST_SESSION_DIR=/path/to/data/session node tests/guestbook_smoke.cjs
```

The test turns off the posting delay, and with `TEST_SESSION_DIR` also opens a members-only site, while it runs; both are restored at the end.
