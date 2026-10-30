<?php
$sub_menu = '200110';
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.');
include_once G5_LIB_PATH.'/tomo_settings.lib.php';
try { tomo_settings_upgrade(); tomo_members_upgrade(); }
catch (Throwable $e) { alert($e->getMessage()); }
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();
    $action = $_POST['approval_action'] ?? '';
    $status = is_string($action) ? (['approve'=>'approved','reject'=>'rejected','pending'=>'pending'][$action] ?? '') : '';
    $ids = $_POST['member_ids'] ?? [];
    if (!$status || !is_array($ids) || count($ids)>100) alert('처리할 회원과 승인 상태를 확인해 주세요.');
    $count = 0;
    foreach (array_unique(array_filter($ids, 'is_string')) as $id) {
        if (!is_string($id) || strlen($id)>20 || strcasecmp($id, $config['cf_admin']) === 0) continue;
        $safe = sql_real_escape_string($id);
        $row = get_member($id);
        if (!$row['mb_id'] || strcasecmp($row['mb_id'], $config['cf_admin']) === 0 || $row['mb_leave_date']) continue;
        sql_query("UPDATE `{$g5['member_table']}` SET mb_tomo_approval='$status' WHERE mb_id='$safe'");
        $count++;
    }
    $notice = $count.'명의 가입 상태를 변경했습니다.';
}
$status = in_array($_GET['status'] ?? '', ['pending','approved','rejected'], true) ? $_GET['status'] : 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));$limit = 30;$offset = ($page-1)*$limit;
$count = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$g5['member_table']}` WHERE mb_tomo_approval='$status' AND mb_leave_date=''");
$rows = sql_query("SELECT mb_id,mb_name,mb_nick,mb_datetime,mb_tomo_approval FROM `{$g5['member_table']}` WHERE mb_tomo_approval='$status' AND mb_leave_date='' ORDER BY mb_datetime DESC LIMIT $offset,$limit");
$g5['title'] = '가입 승인 관리';include_once './admin.head.php';
?>
<p>기존 회원의 권한 등급과는 별개입니다. 최고관리자의 가입 상태는 변경하지 않습니다.</p>
<?php if ($notice) { ?><p role="status"><?=get_text($notice)?></p><?php } ?>
<nav class="archive-tabs" aria-label="가입 상태">
<?php foreach (['pending'=>'승인 대기','approved'=>'승인 완료','rejected'=>'가입 거절'] as $key=>$label) { ?><a class="tomo-approval-filter <?=$key===$status?'is-active':''?>" href="?status=<?=$key?>"><?=$label?></a><?php } ?>
</nav>
<form method="post" data-archive-no-settings>
<input type="hidden" name="token" value="<?=get_admin_token()?>">
<div class="tbl_head01 tbl_wrap"><table><thead><tr><th>선택</th><th>아이디</th><th>이름</th><th>닉네임</th><th>가입일</th></tr></thead><tbody>
<?php $found = false;while ($row = sql_fetch_array($rows)) { $found = true; ?>
<tr><td><?php if ($row['mb_id']!==$config['cf_admin']) { ?><input type="checkbox" name="member_ids[]" value="<?=get_text($row['mb_id'])?>" aria-label="<?=get_text($row['mb_id'])?> 선택"><?php } ?></td><td><?=get_text($row['mb_id'])?></td><td><?=get_text($row['mb_name'])?></td><td><?=get_text($row['mb_nick'])?></td><td><?=get_text($row['mb_datetime'])?></td></tr>
<?php } if (!$found) { ?><tr><td colspan="5">해당 상태의 회원이 없습니다.</td></tr><?php } ?>
</tbody></table></div>
<div class="btn_confirm"><button type="submit" name="approval_action" value="approve">선택 회원 승인</button><button type="submit" name="approval_action" value="reject">선택 회원 거절</button><button type="submit" name="approval_action" value="pending">승인 대기로 변경</button></div>
</form>
<?=get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, (int)ceil($count['cnt']/$limit), './member_approval.php?status='.$status.'&amp;page=')?>
<?php include_once './admin.tail.php'; ?>
