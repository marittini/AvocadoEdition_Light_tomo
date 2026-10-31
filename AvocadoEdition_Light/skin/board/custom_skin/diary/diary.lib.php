<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * 다이어리 게시판 (custom_skin/diary)
 * 원본: diary_avocado (아보카도 에디션 퍼스널 일기 스킨)
 * 아보카도 에디션 라이트 TOMO / PHP 8.4 이식본. 목록형만 남기고 공통 DHTML 에디터를 사용한다.
 *
 * 연도는 글의 작성 시각(wr_datetime)으로 나눈다. 글이 있는 연도는 2026, 2027, 2028…
 * 모두 자동으로 나타나며, 답글 구조를 쓰지 않는다(그누보드 답글은 한 글에 26개까지만 가능).
 */

function diary_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 글이 있는 연도와 월별 글 수: [연도 => [월 => 수]], 최신 연도부터. */
function diary_calendar(string $write_table): array
{
    $map = [];
    $res = sql_query("SELECT YEAR(wr_datetime) AS y, MONTH(wr_datetime) AS m, COUNT(*) AS n
        FROM `{$write_table}` WHERE wr_is_comment = 0 GROUP BY y, m");
    while ($row = sql_fetch_array($res)) $map[(int)$row['y']][(int)$row['m']] = (int)$row['n'];
    krsort($map);
    return $map;
}

/** [시작, 끝) 범위. 달력 확장 없이 계산한다. */
function diary_range(int $year, int $month): array
{
    if ($month) {
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to = $month === 12 ? sprintf('%04d-01-01 00:00:00', $year + 1) : sprintf('%04d-%02d-01 00:00:00', $year, $month + 1);
    } else {
        $from = sprintf('%04d-01-01 00:00:00', $year);
        $to = sprintf('%04d-01-01 00:00:00', $year + 1);
    }
    return [$from, $to];
}

function diary_weekday(string $datetime): string
{
    return ['일', '월', '화', '수', '목', '금', '토'][(int)date('w', strtotime($datetime))];
}

function diary_url(string $bo_table, array $query = []): string
{
    return G5_BBS_URL.'/board.php?'.http_build_query(['bo_table' => $bo_table] + array_filter($query), '', '&amp;');
}

/**
 * 디자인 설정(관리자 > 디자인 설정)의 색을 CSS 변수로 내보낸다.
 * 종이(본문 상자)는 .theme-box, 버튼은 .ui-btn 계열을 그대로 쓰고,
 * 여기서는 강조선에 쓸 포인트 색만 넘긴다.
 */
function diary_theme_vars(): string
{
    global $g5;
    static $done = false;
    if ($done) return '';
    $done = true;
    $row = sql_fetch("SELECT cs_value, cs_etc_1 FROM {$g5['css_table']} WHERE cs_name = 'color_point'", false);
    $hex = trim((string)($row['cs_value'] ?? ''));
    if (!preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i', $hex)) return '';
    $color = function_exists('hex2rgba') ? hex2rgba($hex, $row['cs_etc_1'] ?? false) : $hex;
    if (preg_match('/[;{}<>]/', $color)) return '';
    return '<style>.diary{--diary-point:'.$color.';}</style>';
}

function diary_assets(string $skin_url): void
{
    add_stylesheet('<link rel="stylesheet" href="'.$skin_url.'/style.css?v='.substr(md5_file(__DIR__.'/style.css'), 0, 10).'">', 0);
    add_stylesheet(diary_theme_vars(), 1);
}
