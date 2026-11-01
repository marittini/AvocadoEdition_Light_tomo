<?php
if (!defined('_GNUBOARD_')) exit;
include_once G5_LIB_PATH.'/emoticon.lib.php'; // 이모티콘(관리자 > 이모티콘관리)

/*
 * 방명록 (guestbook)
 *
 * 원본: new_memo (늘 님) → 방명록 스킨 (사도세자 님, 아보카도 라이트). PHP 8.4·라이트 TOMO에 맞게 다시 작성.
 * 목록 화면 한 장에서 쓰기 · 읽기 · 댓글 · 수정을 모두 한다. 저장·삭제·권한 확인은 그누보드 기본 처리(bbs/)를 그대로 쓴다.
 * 본문은 일반 텍스트(HTML 없음). 주소 자동 링크와 #해시태그(검색)만 바꿔 보여 준다.
 * 색·글꼴은 지정하지 않는다. 디자인 설정(기본 박스·입력폼·버튼·포인트 색·기본 글꼴)을 그대로 따른다.
 */

function gb_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gb_url(string $bo_table, array $query = [], string $hash = ''): string
{
    $query = array_filter($query, static fn($v) => $v !== '' && $v !== null && $v !== 0);
    return G5_BBS_URL.'/board.php?'.http_build_query(['bo_table' => $bo_table] + $query, '', '&amp;').($hash !== '' ? '#'.$hash : '');
}

/**
 * 저장된 일반 텍스트 → 화면용 HTML.
 * 원문은 손대지 않고 보여 줄 때만 바꾸므로, 수정할 때 링크가 겹겹이 늘어나는 문제가 생기지 않는다.
 */
function gb_format(string $raw, string $bo_table): string
{
    $html = conv_content($raw, 0); // 이스케이프 + 줄바꿈 + 주소 자동 링크
    $parts = preg_split('/(<a\b[^>]*>.*?<\/a>|<[^>]+>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
        if ($i % 2) continue; // 태그와 링크 안쪽은 그대로
        $part = preg_replace_callback('/(^|[\s(;])#([0-9A-Za-z가-힣_]{1,40})/u', static function ($m) use ($bo_table) {
            return $m[1].'<a href="'.gb_url($bo_table, ['sfl' => 'wr_content', 'stx' => '#'.$m[2]]).'" class="gb-tag">#'.$m[2].'</a>';
        }, $part);
        $parts[$i] = $part;
    }
    return emoticon_html(implode('', $parts)); // 이모티콘 코드 → 이미지(관리자 > 이모티콘관리)
}

/** 비밀글: 관리자 · 회원 본인 · 비밀번호를 확인한 사람(그누보드 기본 세션)만. */
function gb_can_read(array $row): bool
{
    global $is_admin, $member, $bo_table;
    if (strpos((string)($row['wr_option'] ?? ''), 'secret') === false) return true;
    if ($is_admin) return true;
    if (!empty($member['mb_id']) && $member['mb_id'] === ($row['mb_id'] ?? '')) return true;
    return (bool)get_session('ss_secret_'.$bo_table.'_'.($row['wr_num'] ?? ''));
}

/** 글이 목록 몇 쪽에 있는지(공지 제외, 최신순). */
function gb_page_of(string $write_table, array $wr, int $rows): int
{
    $n = (int)(sql_fetch("SELECT COUNT(*) AS n FROM `{$write_table}` WHERE wr_is_comment = 0 AND wr_reply = '' AND wr_num < ".(int)$wr['wr_num'])['n'] ?? 0);
    return intdiv($n, max(1, $rows)) + 1;
}

/** 저장 후 목록의 그 글로 돌아간다. $open = 댓글을 펼친 채로. */
function gb_return(string $bo_table, string $write_table, int $wr_id, bool $open = false): void
{
    global $board;
    $wr = sql_fetch("SELECT wr_id, wr_num FROM `{$write_table}` WHERE wr_id = {$wr_id}");
    if (empty($wr['wr_id'])) goto_url(str_replace('&amp;', '&', gb_url($bo_table)));
    $rows = get_board_page_rows($board);
    $page = gb_page_of($write_table, $wr, $rows);
    goto_url(str_replace('&amp;', '&', gb_url($bo_table, ['page' => $page > 1 ? $page : 0], 'gb-'.$wr_id.($open ? '-c' : ''))));
}

/** 스킨 CSS와 디자인 설정의 포인트 색. */
function gb_assets(string $skin_url): void
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
    if (!preg_match('/[;{}<>]/', $color)) add_stylesheet('<style>.gb{--gb-point:'.$color.';}</style>', 1);
}

function gb_js(string $skin_url): string
{
    return '<script src="'.$skin_url.'/guestbook.js?v='.substr(md5_file(__DIR__.'/guestbook.js'), 0, 10).'" defer></script>';
}

/** 쓰기 요청 확인용 세션 토큰(그누보드 기본 토큰 검사가 꺼져 있어 스킨에서 확인). */
function gb_token(): string
{
    if (empty($_SESSION['gb_token'])) $_SESSION['gb_token'] = bin2hex(random_bytes(32));
    return $_SESSION['gb_token'];
}

function gb_check_token(): void
{
    $t = $_POST['gb_token'] ?? '';
    if (!is_string($t) || empty($_SESSION['gb_token']) || !hash_equals($_SESSION['gb_token'], $t)) {
        alert('요청을 확인할 수 없습니다. 방명록을 새로 고친 뒤 다시 써 주세요.');
    }
}

/**
 * 글 · 댓글 입력 폼.
 * $o: kind(post|comment), w(''|u|c|cu), wr_id, comment_id, content, secret(bool), notice(bool), cancel(bool), id
 */
function gb_form(array $o): string
{
    global $bo_table, $board, $is_member, $is_admin, $page;
    $kind = $o['kind'] ?? 'post';
    $w = $o['w'] ?? '';
    $is_new = $w === '' || $w === 'c';
    $action = G5_BBS_URL.($kind === 'post' ? '/write_update.php' : '/write_comment_update.php');
    $h = '<form class="gb-form gb-form-'.$kind.'" method="post" action="'.$action.'" autocomplete="off" data-gb-form'
        .(!empty($o['id']) ? ' id="'.gb_e($o['id']).'"' : '').'>'
        .'<input type="hidden" name="w" value="'.gb_e($w).'">'
        .'<input type="hidden" name="bo_table" value="'.gb_e($bo_table).'">'
        .'<input type="hidden" name="gb_token" value="'.gb_e(gb_token()).'">'
        .'<input type="hidden" name="page" value="'.(int)$page.'">';
    if ($kind === 'post') {
        static $uid = null; // 한 화면에 폼이 여러 개라도 한 번만 만든다
        if ($uid === null) $uid = get_uniqid();
        $h .= '<input type="hidden" name="uid" value="'.gb_e($uid).'">'
            .'<input type="hidden" name="wr_id" value="'.(int)($o['wr_id'] ?? 0).'">'
            .'<input type="hidden" name="wr_subject" value="방명록">';
    } else {
        $h .= '<input type="hidden" name="wr_id" value="'.(int)($o['wr_id'] ?? 0).'">'
            .'<input type="hidden" name="comment_id" value="'.(int)($o['comment_id'] ?? 0).'">';
        // 회원도 빈 이름 칸을 보낸다(그누보드 댓글 처리가 PHP 8에서 경고를 내지 않게). 이름은 회원 정보로 채워진다.
        if ($is_member) $h .= '<input type="hidden" name="wr_name" value="">';
    }
    $label = $kind === 'post' ? '방명록 내용' : '댓글 내용';
    $h .= '<label class="sound_only" for="'.gb_e(($o['id'] ?? 'gb').'-text').'">'.$label.'</label>'
        .'<textarea name="wr_content" id="'.gb_e(($o['id'] ?? 'gb').'-text').'" class="frm_input gb-text" rows="'.($kind === 'post' && $is_new ? 3 : 1).'" required data-gb-grow'
        .' placeholder="'.($kind === 'post' ? '남기실 말을 적어 주세요.' : '댓글').'">'.gb_e($o['content'] ?? '').'</textarea>';

    $h .= '<div class="gb-form-bar"><div class="gb-form-opts">';
    if ($is_new && !$is_member) {
        $h .= '<input type="text" name="wr_name" class="frm_input gb-guest" maxlength="20" required placeholder="이름" aria-label="이름">'
            .'<input type="password" name="wr_password" class="frm_input gb-guest" maxlength="20" required placeholder="비밀번호" aria-label="비밀번호">';
    }
    if ($kind === 'post') {
        $use = (int)$board['bo_use_secret'];
        if ($use === 2 && !$is_admin) $h .= '<input type="hidden" name="secret" value="secret">';
        elseif ($use || $is_admin) $h .= '<label class="gb-check"><input type="checkbox" name="secret" value="secret"'.(!empty($o['secret']) ? ' checked' : '').'> 비밀글</label>';
        if ($is_admin) $h .= '<label class="gb-check"><input type="checkbox" name="notice" value="1"'.(!empty($o['notice']) ? ' checked' : '').'> 공지</label>';
    }
    $h .= '</div><div class="gb-form-btns">';
    $h .= emoticon_button('textarea');
    if (!empty($o['cancel'])) $h .= '<button type="button" class="ui-btn small" data-gb-cancel>취소</button>';
    $h .= '<button type="submit" class="ui-btn small point">'.($is_new ? '남기기' : '저장').'</button>';
    $h .= '</div></div><div class="gb-slot" data-gb-slot></div></form>';
    return $h;
}
