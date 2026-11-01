<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * 말머리 BBS 게시판 (bbscategory)
 * 원본: 뀰 「[아보카도 에디션] 말머리 BBS 게시판」(bbs_custum_category)
 *       사도세자 bbs_2 기반, 지비 데코 BBS·잉 갠꾸팁 참고
 * 아보카도 에디션 라이트 TOMO / PHP 8.4 호환 이식본.
 */

function bbscat_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** "말머리) 제목" → ['말머리', '제목']. 말머리가 없으면 ['', 제목]. */
function bbscat_split_subject(string $subject): array
{
    if (preg_match('/^\s*([^)\r\n]{1,40}?)\s*\)\s*(\S.*)$/su', $subject, $m)) return [trim($m[1]), $m[2]];
    return ['', $subject];
}

/** 목록·본문 제목: 말머리를 배지로 표시. $subject는 이미 HTML 이스케이프된 값. */
function bbscat_subject_html(string $escaped_subject): string
{
    [$genre, $rest] = bbscat_split_subject($escaped_subject);
    if ($genre === '') return '<span class="title">'.$escaped_subject.'</span>';
    return '<span class="genre">'.$genre.'</span><span class="title">'.$rest.'</span>';
}

/** 게시판에 쓰인 말머리와 글 수(댓글 제외). 게시판 설정(분류)은 건드리지 않는다. */
function bbscat_categories(string $write_table): array
{
    $out = [];
    $res = sql_query("SELECT ca_name, COUNT(*) AS cnt FROM `{$write_table}` WHERE wr_is_comment = 0 AND ca_name <> '' GROUP BY ca_name ORDER BY ca_name");
    while ($row = sql_fetch_array($res)) $out[$row['ca_name']] = (int)$row['cnt'];
    return $out;
}

/** 전체공개 / 멤버공개(wr_secret=1) / 보호글(wr_protect) / 비밀글(wr_option secret) 읽기 권한. */
function bbscat_can_read(array $post): bool
{
    global $is_admin, $member, $is_member, $bo_table;
    if ($is_admin || (!empty($member['mb_id']) && ($post['mb_id'] ?? '') === $member['mb_id'])) return true;
    $unlocked = (bool)get_session('ss_secret_'.$bo_table.'_'.($post['wr_num'] ?? ''));
    if (!empty($post['wr_protect'])) return $unlocked;
    if (strpos($post['wr_option'] ?? '', 'secret') !== false) return $unlocked;
    if (!empty($post['wr_secret'])) return (bool)$is_member;
    return true;
}

function bbscat_password_matches(string $password, string $stored): bool
{
    if ($stored === '') return false;
    if (password_get_info($stored)['algo'] !== null) return password_verify($password, $stored);
    return hash_equals($stored, $password); // 이전 스킨에서 평문으로 저장된 보호글 호환
}

function bbscat_has_protect_column(string $write_table): bool
{
    $col = sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);
    return !empty($col['Field']);
}

/**
 * 디자인 설정(관리자 > 디자인 설정)의 색을 CSS 변수로 내보낸다.
 * 스킨 CSS는 이 변수와 .theme-box / .ui-btn 클래스만 사용하므로
 * 디자인 설정을 바꾸면 스킨 색도 함께 바뀐다.
 */
function bbscat_theme_vars(): string
{
    global $g5;
    static $done = false;
    if ($done) return '';
    $done = true;
    $css = [];
    $res = sql_query("SELECT * FROM {$g5['css_table']}", false);
    while ($res && $cs = sql_fetch_array($res)) {
        for ($i = 0; $i <= 15; $i++) $css[$cs['cs_name']][$i] = $i === 0 ? $cs['cs_value'] : ($cs['cs_etc_'.$i] ?? '');
    }
    $rgba = static function (string $name, int $color, int $alpha) use ($css): string {
        $hex = trim((string)($css[$name][$color] ?? ''));
        if (!preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i', $hex)) return '';
        return function_exists('hex2rgba') ? hex2rgba($hex, $css[$name][$alpha] ?? false) : $hex;
    };
    $vars = [
        '--bbscat-point'      => $rgba('color_point', 0, 1),
        '--bbscat-default'    => $rgba('color_default', 0, 1),
        '--bbscat-chip-bg'    => $rgba('btn_point', 0, 1),
        '--bbscat-chip-color' => $rgba('btn_point', 2, 3),
        '--bbscat-mark-bg'    => $rgba('btn_default', 0, 1),
        '--bbscat-mark-color' => $rgba('btn_default', 2, 3),
        '--bbscat-line'       => $rgba('box_style', 4, 5),
    ];
    $out = '';
    foreach ($vars as $k => $v) if ($v !== '' && !preg_match('/[;{}<>]/', $v)) $out .= $k.':'.$v.';';
    return $out === '' ? '' : '<style>.bbscat{'.$out.'}</style>';
}

/** 안전한 http(s) 주소만 통과. */
function bbscat_safe_url(string $url): string
{
    $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (!preg_match('~^https?://~i', $url)) $url = 'http://'.ltrim($url, '/');
    if (preg_match('~[\x00-\x20\x7f"\'<>\\\\]~', $url) || !filter_var($url, FILTER_VALIDATE_URL)) return '';
    return $url;
}

/**
 * 치환자 (본문·댓글). 원본 배포글의 문법을 그대로 지원한다.
 *  - [URL]               이미지(gif·png·jpg·jpeg·bmp·webp)·유튜브·X(트위터) 임베드
 *  - URL,라벨             링크에 라벨 붙이기
 *  - 요1[내용]-제목-       접기
 *  - 제목1~7 / 소제1~7 / 기타1~7[텍스트]   텍스트 꾸미기 클래스
 * 입력은 이미 정제된 본문 HTML이며, 새로 만드는 주소와 텍스트는 모두 다시 이스케이프한다.
 */
function bbscat_replace_contents(string $html): string
{
    // [URL] 또는 [<a href="URL">..</a>] → 임베드
    $html = preg_replace_callback(
        '~\[\s*(?:<a\s[^>]*href="([^"]+)"[^>]*>[^<]*</a>|(https?://[^\s\]<]+))\s*\]~i',
        static function ($m) {
            $url = bbscat_safe_url($m[1] !== '' ? $m[1] : $m[2]);
            if ($url === '') return $m[0];
            if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|live/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,15})~i', $url, $y)) {
                return '<div class="bbscat-embed"><iframe src="https://www.youtube.com/embed/'.$y[1].'" title="YouTube" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen loading="lazy"></iframe></div>';
            }
            if (preg_match('~^https?://(?:www\.)?(?:x\.com|twitter\.com)/([A-Za-z0-9_]{1,15})/status/(\d+)~i', $url, $t)) {
                return '<blockquote class="twitter-tweet"><a href="https://twitter.com/'.$t[1].'/status/'.$t[2].'"></a></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
            }
            if (preg_match('~\.(gif|png|jpe?g|bmp|webp)(?:\?[^#]*)?$~i', $url)) {
                return '<img src="'.bbscat_e($url).'" alt="" loading="lazy" class="bbscat-img">';
            }
            return $m[0];
        },
        $html
    ) ?? $html;

    // 링크 라벨: <a href="..">URL,라벨</a>
    $html = preg_replace_callback(
        '~<a\s[^>]*href="([^"]*)"[^>]*>([^<]*)</a>~i',
        static function ($m) {
            $parts = explode(',', html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 2);
            if (count($parts) < 2) return $m[0];
            $url = bbscat_safe_url($parts[0]);
            $label = trim($parts[1]) !== '' ? trim($parts[1]) : 'LINK';
            if ($url === '') return $m[0];
            return '<a href="'.bbscat_e($url).'" target="_blank" rel="noopener noreferrer" class="other-site-link">'.bbscat_e($label).'</a>';
        },
        $html
    ) ?? $html;

    // 링크 라벨: 에디터 본문처럼 자동 링크가 없는 글자 "URL,라벨" (라벨은 공백 전까지, 원본과 같음)
    $html = preg_replace_callback(
        '~(^|>)([^<]+)~u',
        static fn($t) => $t[1].(preg_replace_callback(
            '~(?<![\w/"\'=])(https?://[^\s,<]+),([^\s<]+)~iu',
            static function ($m) {
                $url = bbscat_safe_url($m[1]);
                if ($url === '') return $m[0];
                return '<a href="'.bbscat_e($url).'" target="_blank" rel="noopener noreferrer" class="other-site-link">'.bbscat_e(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')).'</a>';
            },
            $t[2]
        ) ?? $t[2]),
        $html
    ) ?? $html;

    // 접기: 요1[내용]-제목-
    $html = preg_replace_callback(
        '`요1\[(.*?)\]-(.*?)-`su',
        static fn($m) => '<details class="bbscat-fold"><summary>'.$m[2].'</summary><div>'.$m[1].'</div></details>',
        $html
    ) ?? $html;

    // 텍스트 꾸미기
    $html = preg_replace_callback(
        '`제목6\[(.*?)\]-(.*?)-`u',
        static fn($m) => '<span class="textggu--title6" data-text="'.bbscat_e(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')).'">'.$m[1].'</span>',
        $html
    ) ?? $html;
    foreach (['제목' => 'textggu--title', '소제' => 'textggu--sub', '기타' => 'textggu--etc'] as $key => $class) {
        $html = preg_replace('`'.$key.'([1-7])\[(.*?)\]`u', '<span class="'.$class.'$1">$2</span>', $html) ?? $html;
    }
    return $html;
}
