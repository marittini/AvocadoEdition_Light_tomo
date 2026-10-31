<?php
include_once './_common.php';
include_once __DIR__.'/ott.lib.php';
if (empty($board['bo_table']) || empty($write['wr_id']) || !empty($write['wr_is_comment']) || empty($write['wr_protect'])) alert('보호글이 없습니다.');
if ((int)($member['mb_level'] ?? 1) < (int)$board['bo_read_level']) alert('글을 읽을 권한이 없습니다.');
if (!empty($group['gr_use_access']) && !$is_admin) {
    $grant = sql_fetch("SELECT gm_id FROM {$g5['group_member_table']} WHERE gr_id='".sql_real_escape_string($group['gr_id'])."' AND mb_id='".sql_real_escape_string($member['mb_id'] ?? '')."'", false);
    if (empty($grant['gm_id'])) alert('글을 읽을 권한이 없습니다.');
}
if (!empty($config['cf_cert_use']) && !$is_admin && !empty($board['bo_use_cert'])) {
    $cert = $board['bo_use_cert'];
    if (!$is_member || ($cert === 'cert' && empty($member['mb_certify'])) || ($cert === 'adult' && empty($member['mb_adult'])) || ($cert === 'hp-cert' && ($member['mb_certify'] ?? '') !== 'hp') || ($cert === 'hp-adult' && (empty($member['mb_adult']) || ($member['mb_certify'] ?? '') !== 'hp'))) alert('본인확인이 필요한 게시판입니다.');
}
$target = G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$write['wr_id'];
if (ott_can_read_post($write)) goto_url($target);
if (empty($_SESSION['ott_protect_token'])) $_SESSION['ott_protect_token'] = bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['ott_protect_token'] ?? '';
    $password = $_POST['wr_password'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['ott_protect_token'], $token) || !is_string($password)) alert('요청을 확인할 수 없습니다.');
    if (ott_password_matches(stripslashes($password), $write['wr_protect'])) {
        set_session('ss_secret_'.$bo_table.'_'.$write['wr_num'], true);
        goto_url($target);
    }
    $error = '비밀번호가 일치하지 않습니다.';
}
$g5['title'] = '보호글 확인';
include_once G5_PATH.'/head.php';
?>
<div id="password_box" class="theme-box">
    <h1>보호글 확인</h1><p>비밀번호를 입력해 주세요.</p>
    <?php if ($error !== '') { ?><p role="alert"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></p><?php } ?>
    <form method="post" action="<?=htmlspecialchars($board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&wr_id='.(int)$write['wr_id'], ENT_QUOTES, 'UTF-8')?>">
        <input type="hidden" name="ott_protect_token" value="<?=htmlspecialchars($_SESSION['ott_protect_token'], ENT_QUOTES, 'UTF-8')?>">
        <label for="ott-protect-password" class="sound_only">비밀번호</label>
        <input type="password" id="ott-protect-password" name="wr_password" class="frm_input" required maxlength="20" autocomplete="current-password">
        <button type="submit" class="ui-btn point">확인</button>
        <a class="ui-btn" href="<?=G5_BBS_URL?>/board.php?bo_table=<?=rawurlencode($bo_table)?>">목록</a>
    </form>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>
