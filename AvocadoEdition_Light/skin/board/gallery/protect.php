<?php
include_once __DIR__.'/_common.php';
include_once __DIR__.'/gallery.lib.php';
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
if (gl_can_read($write)) goto_url($target);
if (empty($_SESSION['gl_protect_token'])) $_SESSION['gl_protect_token'] = bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['gl_protect_token'] ?? '';
    $password = $_POST['wr_password'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['gl_protect_token'], $token) || !is_string($password)) alert('요청을 확인할 수 없습니다.');
    if (gl_password_matches(stripslashes($password), $write['wr_protect'])) {
        set_session('ss_secret_'.$bo_table.'_'.$write['wr_num'], true);
        goto_url($target);
    }
    $error = '비밀번호가 일치하지 않습니다.';
}
$g5['title'] = '보호글 확인';
include_once G5_PATH.'/head.php';
// 사이트의 비밀글 비밀번호 확인 화면(회원 스킨)과 같은 상자·문구 구성을 사용한다.
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>
<div id="password_box" class="theme-box">
	<h1>비밀번호 확인</h1>
	<hr class="line" />
	<div class="descript">
		<p>
			<strong class="txt-point">보호글입니다.</strong>
			작성자가 정한 비밀번호를 입력하면 열람할 수 있습니다.
		</p>
		<?php if ($error !== '') { ?><p class="txt-point" role="alert"><?=gl_e($error)?></p><?php } ?>
		<div class="pass-form">
			<form method="post" action="<?=gl_e($board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&wr_id='.(int)$write['wr_id'])?>">
				<input type="hidden" name="gl_protect_token" value="<?=gl_e($_SESSION['gl_protect_token'])?>">
				<fieldset class="box-pw">
					<label for="gl-protect-password" class="sound_only">비밀번호</label>
					<input type="password" id="gl-protect-password" name="wr_password" required class="frm_input required" size="15" maxlength="20" autocomplete="current-password">
				</fieldset>
				<fieldset class="box-btn">
					<button type="submit" class="point ui-btn">확인</button>
				</fieldset>
			</form>
		</div>
	</div>
	<hr class="line" />
	<div class="btn_confirm">
		<a href="<?=G5_BBS_URL?>/board.php?bo_table=<?=rawurlencode($bo_table)?>" class="ui-btn">목록으로 돌아가기</a>
	</div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>
