<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
/*
 * 게시판 스킨 위치 변경: skin/board/custom_skin/pixiv → skin/board/pixiv (다른 스킨도 같은 방식)
 * 예전 위치(custom_skin/…)로 설정된 게시판은 새 위치에 스킨이 있으면 자동으로 새 이름으로 바꾼다.
 * 지금 보고 있는 게시판도 바로 새 경로로 그린다.
 */
$skin_move_names = ['bbscategory', 'diary', 'gallery', 'guestbook', 'otaku', 'ottreview', 'pixiv'];
$skin_move_old = array_map(static fn($n) => 'custom_skin/'.$n, $skin_move_names);
$skin_move_in = "'".implode("','", $skin_move_old)."'";
$skin_move_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['board_table']} WHERE bo_skin IN ({$skin_move_in}) OR bo_mobile_skin IN ({$skin_move_in})", false);
if (!empty($skin_move_row['cnt'])) {
    foreach ($skin_move_names as $skin_move_name) {
        if (!is_file(G5_SKIN_PATH.'/board/'.$skin_move_name.'/list.skin.php')) continue; // 새 위치에 없으면 그대로 둔다
        foreach (['bo_skin', 'bo_mobile_skin'] as $skin_move_col) {
            sql_query("UPDATE {$g5['board_table']} SET {$skin_move_col} = '{$skin_move_name}' WHERE {$skin_move_col} = 'custom_skin/{$skin_move_name}'", false);
        }
    }
}
// 지금 요청의 게시판(이미 예전 경로로 정해졌을 수 있음)
if (!empty($board['bo_skin']) && in_array($board['bo_skin'], $skin_move_old, true)) {
    $skin_move_new = substr($board['bo_skin'], strlen('custom_skin/'));
    if (is_file(G5_SKIN_PATH.'/board/'.$skin_move_new.'/list.skin.php')) {
        $board['bo_skin'] = $skin_move_new;
        $board_skin_path = get_skin_path('board', $board['bo_skin']);
        $board_skin_url = get_skin_url('board', $board['bo_skin']);
    }
}
unset($skin_move_names, $skin_move_old, $skin_move_in, $skin_move_row, $skin_move_name, $skin_move_col, $skin_move_new);
