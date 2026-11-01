<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/otaku.lib.php';

$otaku_token = $_POST['otaku_token'] ?? '';
if (!is_string($otaku_token) || empty($_SESSION['otaku_write_token']) || !hash_equals($_SESSION['otaku_write_token'], $otaku_token)) {
    alert('글쓰기 요청을 확인할 수 없습니다. 작성 화면을 다시 열어 주세요.');
}
if (!otaku_table()) alert('프로필 저장 테이블을 만들 수 없습니다. DB 권한을 확인해 주세요.');

$otaku_is_au = $w === 'r' || ($w === 'u' && ($wr['wr_reply'] ?? '') !== '');
if ($w === 'r' && ($wr['wr_reply'] ?? '') !== '') alert('AU는 원본 글에만 추가할 수 있습니다.');

// 분류(자캐 · 드림 등): 원본 글은 고른 값, AU는 원본 글의 분류
$otaku_cats = otaku_categories($board);
if ($otaku_is_au) {
    $otaku_root = sql_fetch("SELECT ca_name FROM `{$write_table}` WHERE wr_num='".(int)$wr['wr_num']."' AND wr_reply='' AND wr_is_comment=0");
    $otaku_cat = (string)($otaku_root['ca_name'] ?? '');
} else {
    $otaku_cat = trim(stripslashes((string)($_POST['ca_name'] ?? '')));
}
$ca_name = in_array($otaku_cat, $otaku_cats, true) ? sql_real_escape_string($otaku_cat) : '';

$otaku_entries = otaku_entries($write_table);
$otaku_data = otaku_blank();
$otaku_data['title'] = otaku_clean($_POST['otaku_title'] ?? '', 60);
$otaku_data['marks'] = otaku_marks($_POST['otaku_marks'] ?? '');
$otaku_data['qstyle'] = otaku_qstyle($_POST['otaku_qstyle'] ?? '');
$otaku_data['qpos'] = otaku_qpos($_POST['otaku_qpos'] ?? '');
foreach ($otaku_data['people'] as $i => $blank) {
    $src = $_POST['p'][$i] ?? [];
    $person = [
        'name' => otaku_clean($src['name'] ?? '', 40),
        'ruby' => otaku_clean($src['ruby'] ?? '', 60),
        'color' => otaku_color(otaku_clean($src['color'] ?? '', 7)),
        'line' => otaku_clean($src['line'] ?? '', 80),
        'quote' => otaku_clean($src['quote'] ?? '', 300),
        'facts' => otaku_rows($src, 'fact', ['k' => 30, 'v' => 200], OTAKU_LIMITS['facts']),
        'intro' => otaku_rows($src, 'intro', ['t' => 30, 'b' => 4000], OTAKU_LIMITS['intro']),
        'rels' => otaku_rows($src, 'rel', ['to' => 'int', 'name' => 40, 'kind' => 30, 'text' => 300], OTAKU_LIMITS['rels']),
    ];
    foreach ($person['rels'] as $k => $rel) {
        if ($rel['to'] && !isset($otaku_entries[$rel['to']])) $person['rels'][$k]['to'] = 0; // 이 게시판의 공개 원본 글만 연결
    }
    if ($person['name'] === '') alert('인물 '.($i + 1).'의 이름을 입력해 주세요.');
    $otaku_data['people'][$i] = $person;
}
$otaku_data['bond'] = otaku_clean($_POST['otaku_bond'] ?? '', 2000);
$otaku_data['timeline'] = otaku_rows($_POST, 'tl', ['d' => 30, 't' => 100, 'b' => 1000, 'who' => 4], OTAKU_LIMITS['timeline']);
$otaku_data['timeline'] = array_values(array_filter($otaku_data['timeline'], static fn($r) => $r['d'] !== '' || $r['t'] !== '' || $r['b'] !== ''));
foreach ($otaku_data['timeline'] as $k => $row) {
    if (!in_array($row['who'], ['', '0', '1', 'all'], true)) $otaku_data['timeline'][$k]['who'] = '';
}

// 제목: 원본은 페어 이름(없으면 두 사람 이름). AU는 입력한 AU 이름.
if (!$otaku_is_au) $wr_subject = sql_real_escape_string(otaku_title($otaku_data));
// 검색에 걸리도록 본문에는 프로필 글을 일반 텍스트로 모아 둔다(화면에는 쓰지 않음).
$otaku_search = [$otaku_data['title']];
foreach ($otaku_data['people'] as $p) {
    $otaku_search[] = $p['name'].' '.$p['line'].' '.$p['quote'];
    foreach ($p['intro'] as $s) $otaku_search[] = $s['t'].' '.$s['b'];
}
$wr_content = sql_real_escape_string(mb_substr(implode("\n", $otaku_search), 0, 60000));
$html = '';
