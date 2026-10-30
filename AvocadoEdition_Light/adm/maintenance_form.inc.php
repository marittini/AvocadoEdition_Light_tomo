<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit;
include_once G5_LIB_PATH.'/tomo_maintenance.lib.php';
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();
    $result = tomo_clean_generated_files($maintenance_kind === 'session' ? G5_SESSION_PATH : G5_DATA_PATH.'/cache', $maintenance_kind, session_id());
}
include_once './admin.head.php';
?>
<section class="archive-maintenance">
<h2><?=htmlspecialchars($g5['title'], ENT_QUOTES, 'UTF-8')?></h2>
<p><?=$maintenance_kind === 'session' ? '최근 6시간 동안 갱신되지 않은 세션 파일만 정리합니다. 현재 로그인 세션은 유지합니다. 평소에는 PHP가 자동으로 정리하므로 자주 실행할 필요가 없습니다.' : '최신글 목록의 임시 캐시만 비웁니다. 게시글·첨부파일·디자인 설정은 삭제하지 않으며 다음 조회 때 다시 생성됩니다.'?></p>
<?php if ($result) { ?>
<p role="status"><?=htmlspecialchars($result['error'] ?: '정리 완료 · 삭제 '.$result['deleted'].'개 / 실패 '.$result['failed'].'개 / 유지 '.$result['skipped'].'개', ENT_QUOTES, 'UTF-8')?></p>
<?php } ?>
<form method="post" data-archive-no-settings>
<input type="hidden" name="token" value="<?=get_admin_token()?>">
<div class="btn_confirm"><input type="submit" value="<?=$maintenance_kind === 'session'?'오래된 세션 정리':'최신글 캐시 비우기'?>" class="btn_submit"><a href="./disk_usage.php">디스크 사용량으로</a></div>
</form>
</section>
<?php include_once './admin.tail.php'; ?>
