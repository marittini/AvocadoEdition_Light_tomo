<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * 갤러리 · 글 보관함 (custom_skin/gallery)
 *
 * 원본: 사도세자 「[아보카도 에디션] 갤러리 스킨_mac」(gallery_thumb_mac 2.1, 대하 님의 썸네일형 갤러리 개조)
 *       https://www.postype.com/@sasasasado/post/14563010
 * 라이트 TOMO · PHP 8.4에 맞게 다시 작성. 분류(카테고리)를 접는 폴더로 보여 주고, 그림과 글(소설)을 함께 보관한다.
 *  - 종류(wr_2): art 그림 / text 글. 글은 목록에서 제목 · 첫 줄 · 글자 수 카드로 보인다.
 *  - 출처(wr_1).
 *  - 공개 범위: 전체공개 / 멤버공개(wr_secret=1) / 보호글(wr_protect, 비밀번호 해시) / 비밀글(wr_option secret).
 *  - 본문 보기는 그누보드 기본 view.php를 거친다(권한 확인을 건너뛰지 않음). 목록에서는 그 화면을 팝업으로 띄운다.
 * 색 · 글꼴은 지정하지 않는다. 디자인 설정(기본 박스 · 버튼 · 입력폼 · 포인트 색 · 기본 글꼴)을 그대로 따른다.
 */

const GL_KINDS = ['art' => '그림', 'text' => '글'];

function gl_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gl_url(string $bo_table, array $query = []): string
{
    $query = array_filter($query, static fn($v) => $v !== '' && $v !== null && $v !== 0);
    return G5_BBS_URL.'/board.php?'.http_build_query(['bo_table' => $bo_table] + $query, '', '&amp;');
}

function gl_kind(array $row): string
{
    return ($row['wr_2'] ?? '') === 'text' ? 'text' : 'art';
}

/** 공개 범위 읽기 권한(본인 · 관리자는 통과). */
function gl_can_read(array $post): bool
{
    global $is_admin, $member, $is_member, $bo_table;
    if ($is_admin || (!empty($member['mb_id']) && ($post['mb_id'] ?? '') === $member['mb_id'])) return true;
    $unlocked = (bool)get_session('ss_secret_'.$bo_table.'_'.($post['wr_num'] ?? ''));
    if (!empty($post['wr_protect'])) return $unlocked;
    if (strpos((string)($post['wr_option'] ?? ''), 'secret') !== false) return $unlocked;
    if (!empty($post['wr_secret'])) return (bool)$is_member;
    return true;
}

/** 'protect' | 'secret' | 'member' | '' */
function gl_visibility(array $post): string
{
    if (!empty($post['wr_protect'])) return 'protect';
    if (!empty($post['wr_secret'])) return 'member';
    if (strpos((string)($post['wr_option'] ?? ''), 'secret') !== false) return 'secret';
    return '';
}

const GL_VISIBILITY = ['' => '전체공개', 'member' => '멤버공개', 'protect' => '보호글', 'secret' => '비밀글'];
const GL_VISIBILITY_ICON = ['member' => 'group', 'protect' => 'key', 'secret' => 'lock'];

function gl_password_matches(string $password, string $stored): bool
{
    if ($stored === '') return false;
    if (password_get_info($stored)['algo'] !== null) return password_verify($password, $stored);
    return hash_equals($stored, $password); // 원본 스킨이 평문으로 저장한 보호글 호환
}

function gl_has_protect_column(string $write_table): bool
{
    static $cache = [];
    if (!isset($cache[$write_table])) {
        $col = sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);
        $cache[$write_table] = !empty($col['Field']);
    }
    return $cache[$write_table];
}

/** 본문 HTML → 일반 텍스트(목록 미리보기 · 글자 수용). */
function gl_plain(string $content): string
{
    $text = preg_replace('~<(br|/p|/div|/li|/h[1-6])\b[^>]*>~i', "\n", $content);
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace("/[ \t\x{00A0}]+/u", ' ', preg_replace("/\n\s*\n+/u", "\n", $text)));
}

/** 공백을 뺀 글자 수. */
function gl_char_count(string $content): int
{
    return mb_strlen(preg_replace('/\s+/u', '', gl_plain($content)));
}

function gl_excerpt(string $content, int $len = 90): string
{
    $line = preg_replace('/\s+/u', ' ', gl_plain($content));
    return mb_strimwidth($line, 0, $len * 2, '…', 'UTF-8');
}

/**
 * 목록 썸네일 주소. 첨부 이미지는 비공개 파일 보호 경로(archive_file.php)로,
 * 첨부가 없으면 본문(에디터) 첫 이미지의 그누보드 기본 썸네일을 쓴다.
 */
function gl_thumb(string $bo_table, int $wr_id, int $size = 400): string
{
    global $g5;
    $row = sql_fetch("SELECT bf_file FROM {$g5['board_file_table']} WHERE bo_table = '".sql_real_escape_string($bo_table)."' AND wr_id = {$wr_id} AND (bf_type BETWEEN 1 AND 3 OR bf_type = 18) ORDER BY bf_no LIMIT 1", false);
    if (!empty($row['bf_file'])) {
        $path = G5_DATA_PATH.'/file/'.$bo_table;
        $name = thumbnail($row['bf_file'], $path, $path, $size, $size, false, true) ?: $row['bf_file'];
        return G5_BBS_URL.'/archive_file.php?archive_kind=file&archive_path='.rawurlencode($bo_table.'/'.$name);
    }
    $thumb = get_list_thumbnail($bo_table, $wr_id, $size, $size, false, true);
    return (string)($thumb['src'] ?? '');
}

/** 스킨 CSS와 디자인 설정의 포인트 색. */
function gl_assets(string $skin_url): void
{
    global $g5;
    add_stylesheet('<link rel="stylesheet" href="'.$skin_url.'/style.css?v='.substr(md5_file(__DIR__.'/style.css'), 0, 10).'">', 0);
    static $done = false;
    if ($done) return;
    $done = true;
    $row = sql_fetch("SELECT cs_value, cs_etc_1 FROM {$g5['css_table']} WHERE cs_name='color_point'", false);
    $hex = trim((string)($row['cs_value'] ?? ''));
    if (!preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i', $hex)) return;
    $color = function_exists('hex2rgba') ? hex2rgba($hex, $row['cs_etc_1'] ?? false) : $hex;
    if (!preg_match('/[;{}<>]/', $color)) add_stylesheet('<style>.gl{--gl-point:'.$color.';}</style>', 1);
}

function gl_js(string $skin_url): string
{
    return '<script src="'.$skin_url.'/gallery.js?v='.substr(md5_file(__DIR__.'/gallery.js'), 0, 10).'" defer></script>';
}

/** 쓰기 요청 확인용 세션 토큰(그누보드 기본 토큰 검사가 꺼져 있어 스킨에서 확인). */
function gl_token(): string
{
    if (empty($_SESSION['gl_write_token'])) $_SESSION['gl_write_token'] = bin2hex(random_bytes(32));
    return $_SESSION['gl_write_token'];
}
