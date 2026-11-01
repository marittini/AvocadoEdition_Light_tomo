<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * 링크 · 배너 게시판 (link)
 *
 * 글 한 편 = 배너 한 개. 예전 '링크 게시판(link.zip)' 스킨과 같은 칸을 써서, 게시판 스킨만 바꾸면 그대로 이어진다.
 *   wr_1      배너 이름          wr_link1  배너 그림 주소(바깥 그림)   wr_link2  눌렀을 때 갈 주소
 *   wr_2      툴팁(마우스를 올리면 뜨는 말)   wr_3 / wr_4  툴팁 바탕색 / 글자색(비우면 진한 회색 · 흰 글자)
 *   wr_5      '1' = 내 배너(맨 위, 배너 코드 복사)        wr_10  순서(작을수록 앞)
 *   첨부 0번  배너 그림 파일(있으면 그림 주소보다 먼저)
 * 게시판 여분 필드: bo_1 = PC 한 줄 개수(0 = 원래 크기대로), bo_2 = 휴대폰 한 줄 개수, bo_3 = 'home' 이면 홈 화면에도 보임.
 * 색과 글꼴은 지정하지 않는다(디자인 설정의 상자 · 버튼 · 포인트 색). 툴팁 색만 배너마다 고를 수 있다.
 */

function lk_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** http(s) 주소나 사이트 안 주소(/로 시작)만. 그 밖(javascript: 등)은 빈 문자열. */
function lk_url($value): string
{
    $value = trim(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
    if ($value === '' || preg_match('/[\x00-\x1f\x7f"<>\\\\]/', $value)) return '';
    if (preg_match('~^https?://[^\s/$.?#][^\s]*$~i', $value) || preg_match('~^/(?!/)[^\s]*$~', $value)) return $value;
    return '';
}

function lk_color($value): string
{
    $value = strtolower(trim((string)$value));
    if (preg_match('/^#?([0-9a-f]{3})$/', $value, $m)) $value = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
    return preg_match('/^#?([0-9a-f]{6})$/', $value, $m) ? '#'.$m[1] : '';
}

/** 게시판 표시 설정. */
function lk_settings(array $board): array
{
    return [
        'pc' => max(0, min(8, (int)($board['bo_1'] ?? 0))),
        'mobile' => max(0, min(4, (int)($board['bo_2'] ?? 0))),
        'home' => trim((string)($board['bo_3'] ?? '')) === 'home',
    ];
}

/** 첨부 0번 그림(있으면). 공개 배너라 데이터 폴더 주소를 그대로 쓴다. */
function lk_files(string $bo_table, array $ids): array
{
    global $g5;
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) return [];
    $out = [];
    $res = sql_query("SELECT wr_id, bf_file FROM {$g5['board_file_table']} WHERE bo_table = '".sql_real_escape_string($bo_table)."' AND bf_no = 0 AND wr_id IN (".implode(',', $ids).") AND bf_file <> '' AND bf_type IN (1, 2, 3, 18)");
    while ($row = sql_fetch_array($res)) $out[(int)$row['wr_id']] = G5_DATA_URL.'/file/'.$bo_table.'/'.rawurlencode($row['bf_file']);
    return $out;
}

/** 배너 목록(순서대로). 비밀글은 관리자 · 글쓴이에게만. */
function lk_banners(string $bo_table, string $write_table, string $category = ''): array
{
    global $is_admin, $member;
    $where = "wr_is_comment = 0 AND wr_reply = ''";
    if ($category !== '') $where .= " AND ca_name = '".sql_real_escape_string($category)."'";
    $rows = [];
    $res = sql_query("SELECT wr_id, wr_num, ca_name, wr_option, mb_id, wr_subject, wr_1, wr_2, wr_3, wr_4, wr_5, wr_10, wr_link1, wr_link2 FROM `{$write_table}` WHERE {$where} ORDER BY (wr_10 + 0), wr_id");
    while ($row = sql_fetch_array($res)) {
        if (strpos((string)$row['wr_option'], 'secret') !== false && !$is_admin && (empty($member['mb_id']) || $member['mb_id'] !== $row['mb_id'])) continue;
        $rows[(int)$row['wr_id']] = $row;
    }
    $files = lk_files($bo_table, array_keys($rows));
    foreach ($rows as $id => $row) {
        $name = trim((string)$row['wr_1']) !== '' ? (string)$row['wr_1'] : (string)$row['wr_subject'];
        $rows[$id] = [
            'id' => $id,
            'name' => $name === '링크' ? '' : $name,
            'img' => $files[$id] ?? lk_url($row['wr_link1']),
            'uploaded' => isset($files[$id]),
            'href' => lk_url($row['wr_link2']),
            'tip' => trim((string)$row['wr_2']),
            'tip_bg' => lk_color($row['wr_3']),
            'tip_fg' => lk_color($row['wr_4']),
            'mine' => (string)$row['wr_5'] === '1',
            'category' => (string)$row['ca_name'],
            'order' => (int)$row['wr_10'],
            'secret' => strpos((string)$row['wr_option'], 'secret') !== false,
            'mb_id' => (string)$row['mb_id'],
        ];
    }
    return array_values($rows);
}

/** 바뀌지 않는 배너 그림 주소(새 그림을 올려도 같은 주소). 다른 사이트에 붙일 '내 배너'용. */
function lk_stable_img(string $bo_table, int $wr_id, string $skin_url): string
{
    return $skin_url.'/banner.php?bo_table='.rawurlencode($bo_table).'&amp;id='.$wr_id;
}

/** 배너 하나(그림 + 툴팁). */
function lk_item(array $b, array $links = []): string
{
    $tip = $b['tip'];
    $style = '';
    if ($b['tip_bg'] !== '') $style .= '--lk-tip-bg:'.$b['tip_bg'].';';
    if ($b['tip_fg'] !== '') $style .= '--lk-tip-fg:'.$b['tip_fg'].';';
    $face = $b['img'] !== ''
        ? '<img src="'.lk_e($b['img']).'" alt="'.lk_e($b['name']).'" loading="lazy">'
        : '<span class="lk-text">'.lk_e($b['name'] !== '' ? $b['name'] : '이름 없는 배너').'</span>';
    $tag = $b['href'] !== '' ? 'a href="'.lk_e($b['href']).'" target="_blank" rel="noopener"' : 'span';
    $h = '<li class="lk-item'.($tip !== '' ? ' has-tip' : '').($b['secret'] ? ' is-secret' : '').'"'.($style !== '' ? ' style="'.$style.'"' : '').'>';
    $h .= '<'.$tag.' class="lk-banner"'.($tip !== '' ? ' aria-describedby="lk-tip-'.$b['id'].'"' : '').'>'.$face.'</'.(strpos($tag, 'a ') === 0 ? 'a' : 'span').'>';
    if ($tip !== '') $h .= '<span class="lk-tip" role="tooltip" id="lk-tip-'.$b['id'].'">'.nl2br(lk_e($tip), false).'</span>';
    if ($links) {
        $h .= '<span class="lk-tools">';
        if (!empty($links['edit'])) $h .= '<a href="'.$links['edit'].'" title="수정" aria-label="'.lk_e($b['name']).' 수정"><i class="material-icons" aria-hidden="true">edit</i></a>';
        if (!empty($links['delete'])) $h .= '<a href="'.$links['delete'].'" title="삭제" aria-label="'.lk_e($b['name']).' 삭제" onclick="del(this.href); return false;"><i class="material-icons" aria-hidden="true">delete</i></a>';
        $h .= '</span>';
    }
    return $h.'</li>';
}

/** 배너 줄(설정한 한 줄 개수). */
function lk_grid(array $banners, array $settings, ?callable $links = null): string
{
    if (!$banners) return '';
    $cls = 'lk-grid'.($settings['pc'] ? ' is-cols' : '').($settings['mobile'] ? ' is-mcols' : '');
    $style = ($settings['pc'] ? '--lk-cols:'.$settings['pc'].';' : '').($settings['mobile'] ? '--lk-mcols:'.$settings['mobile'].';' : '');
    $h = '<ul class="'.$cls.'"'.($style !== '' ? ' style="'.$style.'"' : '').'>';
    foreach ($banners as $b) $h .= lk_item($b, $links ? $links($b) : []);
    return $h.'</ul>';
}

function lk_assets(string $skin_url): string
{
    static $done = false;
    if ($done) return '';
    $done = true;
    $css = __DIR__.'/style.css';
    $js = __DIR__.'/link.js';
    return '<link rel="stylesheet" href="'.$skin_url.'/style.css?v='.substr((string)@md5_file($css), 0, 10).'">'
        .'<script src="'.$skin_url.'/link.js?v='.substr((string)@md5_file($js), 0, 10).'" defer></script>';
}

/** 관리 화면 · 저장 요청 확인용 토큰. */
function lk_token(): string
{
    if (empty($_SESSION['lk_token'])) $_SESSION['lk_token'] = bin2hex(random_bytes(32));
    return $_SESSION['lk_token'];
}

function lk_check_token($value): bool
{
    return is_string($value) && !empty($_SESSION['lk_token']) && hash_equals($_SESSION['lk_token'], $value);
}

/**
 * 홈 화면 배너: 표시 설정에서 '홈 화면에 보이기'를 켠 링크 게시판들(또는 지정한 게시판 하나).
 * main.php가 부른다. 홈 화면 내용에 [배너] 또는 [배너:게시판아이디]를 적으면 그 자리에, 없으면 내용 아래에.
 */
function lk_home_html(string $only = ''): string
{
    global $g5;
    $where = "bo_skin = 'link'";
    if ($only !== '') $where .= " AND bo_table = '".sql_real_escape_string($only)."'";
    else $where .= " AND bo_3 = 'home'";
    $res = sql_query("SELECT * FROM {$g5['board_table']} WHERE {$where} ORDER BY bo_order, bo_table", false);
    $h = '';
    while ($res && ($bo = sql_fetch_array($res))) {
        $settings = lk_settings($bo);
        $banners = lk_banners($bo['bo_table'], $g5['write_prefix'].$bo['bo_table']);
        $banners = array_values(array_filter($banners, static fn($b) => !$b['secret'] && ($b['img'] !== '' || $b['name'] !== '')));
        usort($banners, static fn($a, $b) => (int)$b['mine'] <=> (int)$a['mine']); // 내 배너가 먼저(순서는 그대로)
        if (!$banners) continue;
        $h .= '<div class="lk lk-home" data-board="'.lk_e($bo['bo_table']).'">'.lk_grid($banners, $settings).'</div>';
    }
    return $h === '' ? '' : lk_assets(G5_SKIN_URL.'/board/link').$h;
}
