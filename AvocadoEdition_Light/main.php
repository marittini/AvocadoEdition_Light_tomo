<?php
include_once('./_common.php');
define('_MAIN_', true);

include_once(G5_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/main.css">', 0);
$main_content = get_site_content('site_main');

if(!$main_content) {
	$logo = get_logo();
	$main_content = "";
	if($logo)		$main_content .= "<img src='{$logo}' alt='' />";
}

// 링크 게시판 배너: 홈 화면 내용에 [배너] / [배너:게시판아이디]를 적으면 그 자리에,
// 없으면 '홈 화면에도 보이기'를 켠 링크 게시판의 배너를 내용 아래에 붙인다.
if (is_file(G5_SKIN_PATH.'/board/link/link.lib.php')) {
	include_once G5_SKIN_PATH.'/board/link/link.lib.php';
	if (preg_match('/\[배너(?::[A-Za-z0-9_]+)?\]/u', $main_content)) {
		$main_content = preg_replace_callback('/\[배너(?::([A-Za-z0-9_]+))?\]/u', static fn($m) => lk_home_html($m[1] ?? ''), $main_content);
	} else {
		$main_content .= lk_home_html();
	}
}
?>

<div id="main_body">
	<?php  echo $main_content; ?>
</div>

<script>
$(function() {
	window.onload = function() {
		$('#body').css('opacity', 1);
	};
});
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>