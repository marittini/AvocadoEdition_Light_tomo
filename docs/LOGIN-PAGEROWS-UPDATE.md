# 자동로그인 · 모바일 페이지당 목록 수

## 일반 로그인 화면의 자동로그인

Reference: https://www.postype.com/@providence-frog/post/19405949

The core already supported auto login (`bbs/login_check.php` sets `ck_mb_id` / `ck_auto` for 31 days, `common.php` reads them
and skips the super admin), but only the admin login skin had the checkbox.

- `skin/member/basic/login.skin.php`: a 자동로그인 checkbox under the ID/password fields, with the same confirm notice as the
  admin login. The fields and button are wrapped in `.login-fields` so the button keeps its full height.
- `css/login.css`: `.login-fields` and `.login-auto` (small, left-aligned; colours and fonts from the design settings).
  The stylesheet link carries a content hash, so browsers pick up the change without bumping `G5_CSS_VER`.

## 모바일 페이지당 목록 수

Reference: https://www.postype.com/@aoqhrtkfkdsl/post/14381508

On mobile, `bbs/list.php` and `bbs/list.mmb.php` always used 5 rows, and the admin form had no field for
`bo_mobile_page_rows` (the update query read a missing POST value).

- `lib/common.lib.php`: `get_board_page_rows($board)` — the mobile value on mobile when it is above 0, otherwise the PC value.
- `bbs/list.php`, `bbs/list.mmb.php`, `bbs/delete_all.php`: use it. Before, `delete_all.php` compared against a mobile value
  that was usually 0, so bulk delete on mobile was refused.
- `adm/board_form.php`: 모바일 페이지당 목록 수 row (0 = follow PC) with 그룹적용 / 전체적용.
- `adm/board_form_update.php`: both values are cast to integers before saving.
- The diary, otaku and guestbook custom skins use the same function.

## Test

```
TEST_URL=http://127.0.0.1:8080 TEST_USER=admin TEST_PASSWORD=... TEST_MEMBER=<member id> TEST_SQL="mysql -uroot g5" \
node tests/login_pagerows_smoke.cjs
```
