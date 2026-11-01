<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * 캐릭터 · 페어 프로필 (otaku)
 *
 * 글 한 편 = 페어 한 쌍(두 사람)의 프로필. 탭: 프로필 · 관계 · 연표.
 * AU = 원본 글의 답글. 읽기 화면에서 원본 / AU 버튼으로 바꿔 본다.
 * 프로필 내용은 스킨 전용 테이블에 JSON으로 저장한다.
 * 첨부 칸: 0 인물 1 사진, 1 인물 2 사진, 2 목록 썸네일, 3 인물 1 미니, 4 인물 2 미니.
 * 색·글꼴은 지정하지 않는다. 디자인 설정(기본 박스·버튼·포인트 색·기본 글꼴)을 그대로 따른다.
 */

const OTAKU_SLOTS = ['photo' => [0, 1], 'cover' => 2, 'mini' => [3, 4]];
const OTAKU_LIMITS = ['facts' => 30, 'intro' => 20, 'rels' => 40, 'timeline' => 100];

function otaku_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 줄바꿈을 살린 일반 텍스트(HTML 없음). */
function otaku_text($value): string
{
    return nl2br(otaku_e($value), false);
}

function otaku_table(): string
{
    static $ready = null;
    $table = G5_TABLE_PREFIX.'otaku_data';
    if ($ready === null) {
        $ready = (bool)sql_query("DESC `{$table}`", false);
        if (!$ready) {
            sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `bo_table` varchar(20) NOT NULL DEFAULT '',
                `wr_id` int(11) NOT NULL DEFAULT 0,
                `od_data` mediumtext NOT NULL,
                `od_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`bo_table`, `wr_id`)
            ) DEFAULT CHARSET=utf8mb4", false);
            $ready = (bool)sql_query("DESC `{$table}`", false);
        }
    }
    return $ready ? $table : '';
}

function otaku_blank_person(): array
{
    return ['name' => '', 'ruby' => '', 'color' => '', 'line' => '', 'quote' => '', 'facts' => [], 'intro' => [], 'rels' => []];
}

function otaku_blank(): array
{
    return ['title' => '', 'marks' => 'double', 'qstyle' => 'highlight', 'qpos' => 'bottom', 'people' => array_fill(0, 2, otaku_blank_person()), 'bond' => '', 'timeline' => []];
}

function otaku_load(string $bo_table, int $wr_id): array
{
    $data = otaku_blank();
    $t = otaku_table();
    if (!$t || $wr_id < 1) return $data;
    $row = sql_fetch("SELECT od_data FROM `{$t}` WHERE bo_table='".sql_real_escape_string($bo_table)."' AND wr_id={$wr_id}", false);
    $json = json_decode((string)($row['od_data'] ?? ''), true);
    if (!is_array($json)) return $data;
    $data = array_replace($data, $json);
    foreach ($data['people'] as $i => $p) $data['people'][$i] = array_replace(otaku_blank_person(), is_array($p) ? $p : []);
    while (count($data['people']) < 2) $data['people'][] = otaku_blank_person();
    $data['people'] = array_slice(array_values($data['people']), 0, 2);
    return $data;
}

function otaku_save(string $bo_table, int $wr_id, array $data): void
{
    $t = otaku_table();
    if (!$t || $wr_id < 1) return;
    $json = sql_real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    sql_query("REPLACE INTO `{$t}` SET bo_table='".sql_real_escape_string($bo_table)."', wr_id={$wr_id}, od_data='{$json}', od_update='".G5_TIME_YMDHIS."'");
}

function otaku_cleanup(string $bo_table, string $write_table): void
{
    $t = otaku_table();
    if (!$t) return;
    sql_query("DELETE d FROM `{$t}` d LEFT JOIN `{$write_table}` w ON w.wr_id=d.wr_id
        WHERE d.bo_table='".sql_real_escape_string($bo_table)."' AND w.wr_id IS NULL", false);
}

/** POST 값 하나를 길이 제한한 일반 텍스트로. */
function otaku_clean($value, int $max): string
{
    if (!is_string($value)) return '';
    $value = trim(str_replace("\r\n", "\n", stripslashes($value)));
    return preg_match('//u', $value) ? mb_substr($value, 0, $max) : '';
}

/** 같은 이름의 배열 필드 묶음을 행 목록으로. $source는 $_POST 안의 배열(예: $_POST['p'][0]). */
function otaku_rows($source, string $prefix, array $fields, int $limit): array
{
    $source = is_array($source) ? $source : [];
    $first = array_key_first($fields);
    $count = is_array($source[$prefix.'_'.$first] ?? null) ? count($source[$prefix.'_'.$first]) : 0;
    $rows = [];
    for ($i = 0; $i < $count && count($rows) < $limit; $i++) {
        $row = [];
        $filled = false;
        foreach ($fields as $name => $max) {
            $raw = $source[$prefix.'_'.$name][$i] ?? '';
            $row[$name] = $max === 'int' ? max(0, (int)$raw) : otaku_clean($raw, $max);
            if ($max === 'int' ? $row[$name] > 0 : $row[$name] !== '') $filled = true;
        }
        if ($filled) $rows[] = $row;
    }
    return $rows;
}

/**
 * 분류(모아 보기). 게시판 설정에서 분류를 켜고 목록을 적었으면 그것을, 아니면 기본 '자캐 · 드림'.
 * 분류는 원본 글의 ca_name에 저장하고, AU는 원본 분류를 따른다.
 */
function otaku_categories(array $board): array
{
    if (!empty($board['bo_use_category']) && trim((string)($board['bo_category_list'] ?? '')) !== '') {
        $list = array_values(array_filter(array_map('trim', explode('|', $board['bo_category_list'])), static fn($c) => $c !== '' && $c !== '공지'));
        if ($list) return $list;
    }
    return ['자캐', '드림'];
}

/** 한마디를 감싸는 따옴표: double “ ” / corner 「 」 / none. */
const OTAKU_MARKS = ['double' => ['“', '”', '“ ”'], 'corner' => ['「', '」', '「 」'], 'none' => ['', '', '없음']];

function otaku_marks($value): string
{
    return isset(OTAKU_MARKS[$value]) ? (string)$value : 'double';
}

/** 한마디 모양: plain 아무것도 없음 / highlight 형광펜 / bubble 말풍선. */
const OTAKU_QSTYLES = ['plain' => '없음', 'highlight' => '하이라이트', 'bubble' => '말풍선'];

function otaku_qstyle($value): string
{
    return isset(OTAKU_QSTYLES[$value]) ? (string)$value : 'highlight';
}

/** 한마디 자리: top 이름 위 / bottom 한 줄 소개 아래(기본). */
const OTAKU_QPOS = ['top' => '이름 위', 'bottom' => '한 줄 소개 아래'];

function otaku_qpos($value): string
{
    return isset(OTAKU_QPOS[$value]) ? (string)$value : 'bottom';
}

/** 한마디: 따옴표 + 고른 모양(형광펜 · 말풍선은 지향색, 없으면 포인트 색 · 옅은 회색). */
function otaku_quote_html(string $quote, $marks, $style = 'highlight'): string
{
    [$open, $close] = OTAKU_MARKS[otaku_marks($marks)];
    $text = otaku_e($open.$quote.$close);
    return match (otaku_qstyle($style)) {
        'plain' => '<span class="otaku-q">'.$text.'</span>',
        'bubble' => '<span class="otaku-bubble">'.$text.'</span>',
        default => '<mark class="otaku-hl">'.$text.'</mark>',
    };
}

/** 지향색: #rrggbb만 받는다(그 밖의 값은 빈 문자열). */
function otaku_color($value): string
{
    $value = strtolower(trim((string)$value));
    if (preg_match('/^#?([0-9a-f]{3})$/', $value, $m)) $value = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
    if (preg_match('/^#?([0-9a-f]{6})$/', $value, $m)) return '#'.$m[1];
    return '';
}

/** 지향색 위에 올릴 글자색: 밝은 색이면 검정, 어두운 색이면 흰색. */
function otaku_color_text(string $hex): string
{
    $hex = ltrim(otaku_color($hex), '#');
    if ($hex === '') return '';
    [$r, $g, $b] = array_map(static function ($c) {
        $c = hexdec($c) / 255;
        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }, str_split($hex, 2));
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b > 0.4 ? '#222' : '#fff';
}

/** 지향색 CSS 변수(style 속성 안에 그대로 쓴다). 색이 없으면 빈 문자열. */
function otaku_color_style(array $person): string
{
    $color = otaku_color($person['color'] ?? '');
    return $color === '' ? '' : '--otaku-person:'.$color.';--otaku-person-text:'.otaku_color_text($color).';';
}

/** 이름(+ 루비). 루비가 있으면 이름 위에 작게 읽는 법을 단다. */
function otaku_name_html(array $person): string
{
    $name = otaku_e($person['name'] ?? '');
    $ruby = trim((string)($person['ruby'] ?? ''));
    return $ruby === '' ? $name : '<ruby>'.$name.'<rp>(</rp><rt>'.otaku_e($ruby).'</rt><rp>)</rp></ruby>';
}

/** 여러 이름을 이어 붙이되, 한 사람의 이름은 중간에서 줄이 바뀌지 않게 묶는다. */
function otaku_names_html(array $people): string
{
    $names = array_filter(array_map(static fn($p) => otaku_e($p['name'] ?? ''), $people), static fn($n) => $n !== '');
    return implode(' <span class="otaku-sep">·</span> ', array_map(static fn($n) => '<span class="otaku-nm">'.$n.'</span>', $names));
}

/** 페어 표시 이름: 페어 이름이 있으면 그것, 없으면 두 사람 이름. */
function otaku_title(array $data): string
{
    return $data['title'] !== '' ? $data['title'] : implode(' · ', array_column($data['people'], 'name'));
}

/** 이 게시판의 원본 글(관계 연결 대상): [wr_id => 제목]. */
function otaku_entries(string $write_table): array
{
    $out = [];
    $res = sql_query("SELECT wr_id, wr_subject FROM `{$write_table}` WHERE wr_is_comment=0 AND wr_reply='' AND wr_option NOT LIKE '%secret%' ORDER BY wr_subject");
    while ($row = sql_fetch_array($res)) $out[(int)$row['wr_id']] = get_text($row['wr_subject']);
    return $out;
}

/** 첨부 칸 번호(0~4) => 이미지. 비공개 파일 보호 경로(archive_file.php)를 쓴다. */
function otaku_photos(string $bo_table, int $wr_id): array
{
    $out = [];
    foreach (get_file($bo_table, $wr_id) as $key => $f) {
        if (!is_int($key) || $key > 4 || empty($f['file']) || !in_array((int)($f['image_type'] ?? 0), [1, 2, 3, 18], true)) continue;
        $out[$key] = ['no' => $key, 'file' => $f['file'], 'name' => $f['source'],
            'url' => G5_BBS_URL.'/archive_file.php?archive_kind=file&amp;archive_path='.rawurlencode($bo_table.'/'.$f['file'])];
    }
    return $out;
}

function otaku_thumb(string $bo_table, array $photo, int $w, int $h): string
{
    include_once G5_LIB_PATH.'/thumbnail.lib.php';
    $path = G5_DATA_PATH.'/file/'.$bo_table;
    $thumb = thumbnail(basename($photo['file']), $path, $path, $w, $h, false, true);
    return $thumb ? G5_BBS_URL.'/archive_file.php?archive_kind=file&amp;archive_path='.rawurlencode($bo_table.'/'.$thumb) : $photo['url'];
}

/** 비밀글은 작성자·관리자·비밀번호 확인 후에만. */
function otaku_can_read(array $post): bool
{
    global $is_admin, $member, $bo_table;
    if ($is_admin || (!empty($member['mb_id']) && ($post['mb_id'] ?? '') === $member['mb_id'])) return true;
    if (strpos($post['wr_option'] ?? '', 'secret') === false) return true;
    return (bool)get_session('ss_secret_'.$bo_table.'_'.($post['wr_num'] ?? ''));
}

function otaku_url(string $bo_table, array $query = []): string
{
    return G5_BBS_URL.'/board.php?'.http_build_query(['bo_table' => $bo_table] + array_filter($query), '', '&amp;');
}

/** 스킨 CSS와 디자인 설정의 포인트 색(현재 탭 밑줄·링크 강조용). */
function otaku_assets(string $skin_url): void
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
    if (!preg_match('/[;{}<>]/', $color)) add_stylesheet('<style>.otaku{--otaku-point:'.$color.';}</style>', 1);
}

function otaku_js(string $skin_url, string $file): string
{
    return '<script src="'.$skin_url.'/'.$file.'?v='.substr(md5_file(__DIR__.'/'.$file), 0, 10).'" defer></script>';
}
