# PHP 8.4 호환 확인

Everything requested in this series of updates (pixiv · bbscategory · diary · otaku · guestbook · gallery · ottreview list/view ·
ucho_menu BGM · auto login · mobile rows per page · click effect · emoticon admin) was checked for PHP 8.4.

## How it was checked

1. **Static scan** of all PHP files in those skins and every core file changed for them, for constructs removed or deprecated
   in PHP 8.0–8.4: implicitly nullable parameter types, `E_STRICT`, `lcg_value`, `mysqli_ping/refresh/kill`,
   csv functions without `$escape`, `trigger_error(E_USER_ERROR)`, `mysql_*`/`each`/`create_function`, `utf8_encode/decode`,
   `strftime`, `FILTER_SANITIZE_STRING`, `${}` interpolation, short open tags, `$str{0}` offsets, case-insensitive `define`,
   magic quotes, session `sid_*` ini, dynamic properties. Only the `mysql_*` fallback branch in `lib/common.lib.php`
   (never taken: `G5_MYSQLI_USE` is true) and one dynamic property (`str_encrypt::$length`, fixed) were found.
2. **Runtime with every error level on**: the core sets `error_reporting()` without deprecations in `common.php`, so a test
   copy of the site was run with `error_reporting(E_ALL)` (log only), and all smoke tests were run against it:
   guestbook, otaku, diary, gallery, login/page rows, click effect, emoticon admin, bbscategory, pixiv series, pixiv board.

The local runtime is PHP 8.3; PHP 8.4 itself could not be installed in the test environment. The static scan covers the
8.4-only changes, and the runtime run covers every deprecation up to 8.3.

## Result

No warnings, notices or deprecations from the requested skins and files, and no fatal errors anywhere.
Fixed along the way (behaviour unchanged):

| File | Fix |
| --- | --- |
| `lib/common.lib.php` | `str_encrypt::$length` declared; `html_symbol`, `clean_xss_tags`, `check_url_host` accept null; `get_uniqid` float cast; `get_email_address` missing match; `upload` file extension by reference; `delete_point` with no point record |
| `plugin/kcaptcha/kcaptcha.lib.php` | `$keystring` declared; integer pixel coordinates and colours; `$html` initialised (guest forms in guestbook/gallery) |
| `adm/board_form.php`, `adm/board_form_update.php` | New-board defaults for every column; unchecked checkboxes as empty values; `$reaonly` typo |
| `extend/community.config.php` | `get_site_content()` with no stored content |
| `index.php`, `skin/member/basic/login.skin.php`, `skin/member/basic/password.skin.php` | Undefined `$index_url`, null `$url`, undefined `$comment_id` |
| `skin/board/custom_skin/pixiv/view_comment.skin.php` | Undefined `$c_id`, `$c_wr_content` (now escaped) |

Other core files (`common.php`, `bbs/write_update.php`, `bbs/view.php`, `head.sub.php`, `adm/admin.lib.php`, …) still log
PHP 8 warnings such as undefined variables. They are not fatal, they affect every board, and they were not part of these
requests.
