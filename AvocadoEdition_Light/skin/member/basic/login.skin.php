<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if(strstr((string)($url ?? ''), 'adm')) {
	include_once($member_skin_path.'/login.admin.skin.php');
} else {

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/login.css?v='.substr(md5_file(G5_PATH.'/css/login.css'), 0, 10).'">', 0);


/*********** Logo Data ************/
$logo = get_logo('pc');
$m_logo = get_logo('mo');

$logo_data = "";
if($logo)		$logo_data .= "<img src='".$logo."' />";
/*********************************/
?>



<div class="loginWrap">
	<div class="login-inner">

		<?php
		// 등록된 로고 파일이 있을 경우에만 출력 한다.
		if($logo_data) { ?>
			<div class="login-logo">
				<?=$logo_data?>
			</div>
			<hr class="padding" />
		<?php  } ?>
		<div class="login-form-box theme-box">
			<div class="inner">
				<form name="flogin" action="<?php echo $login_action_url ?>" onsubmit="return flogin_submit(this);" method="post">
					<input type="hidden" name="url" value='<?php echo $login_url ?>'>
					<div class="login-fields">
						<fieldset>
							<input type="text" name="mb_id" id="login_id" required class="frm_input required" size="20" maxLength="20" placeholder="아이디" aria-label="아이디" autocomplete="username">
						</fieldset>
						<fieldset>
							<input type="password" name="mb_password" id="login_pw" required class="frm_input required" size="20" maxLength="20" placeholder="비밀번호" aria-label="비밀번호" autocomplete="current-password">
						</fieldset>
						<button type="submit" class="ui-btn point">LOGIN</button>
					</div>
					<label class="login-auto" for="login_auto_login">
						<input type="checkbox" name="auto_login" id="login_auto_login" value="1">
						<span>자동로그인</span>
					</label>
				</form>
			</div>
		</div>
	</div>
</div>


<script>
// 관리자 로그인 화면과 같은 자동로그인(그누보드 기본 기능: 한 달 동안 유지, 최고관리자는 제외)
document.getElementById("login_auto_login").addEventListener("change", function () {
	if (this.checked) {
		this.checked = confirm("자동로그인을 사용하시면 다음부터 회원아이디와 비밀번호를 입력하실 필요가 없습니다.\n\n공공장소에서는 개인정보가 유출될 수 있으니 사용을 자제하여 주십시오.\n\n자동로그인을 사용하시겠습니까?");
	}
});
function flogin_submit(f) {
	return true;
}
</script>
<!-- } 로그인 끝 -->
<?php  } ?>