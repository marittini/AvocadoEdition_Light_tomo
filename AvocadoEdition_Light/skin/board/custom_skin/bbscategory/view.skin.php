<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once G5_LIB_PATH.'/thumbnail.lib.php';
include_once __DIR__.'/bbscategory.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v='.substr(md5_file(__DIR__.'/style.css'), 0, 10).'">', 0);
add_stylesheet(bbscat_theme_vars(), 1);
// 텍스트 꾸미기(제목1[...] 등) 스타일은 에디션에 포함된 OTT 스킨의 텍스트 꾸미기 CSS를 함께 사용한다.
if (is_file(G5_SKIN_PATH.'/board/custom_skin/ottreview/txtggu/textggu.css')) {
	add_stylesheet('<link rel="stylesheet" href="'.G5_SKIN_URL.'/board/custom_skin/ottreview/txtggu/textggu.css">', 1);
}

// 멤버공개·보호글·비밀글 확인 (본인·관리자는 통과)
if (!bbscat_can_read($view)) {
	if (!empty($view['wr_protect'])) goto_url($board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$view['wr_id']);
	alert('멤버공개 글입니다. 로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php?url='.urlencode(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&wr_id='.(int)$view['wr_id']));
}
$bbscat_width = (int)($board['bo_table_width'] ?? 0);
?>

<script src="<?=G5_JS_URL?>/viewimageresize.js"></script>

<div class="bbscat bbscat-view"<?php if ($bbscat_width > 0) { ?> style="max-width:<?=$bbscat_width?><?=$bbscat_width > 100 ? 'px' : '%'?>;margin:0 auto;"<?php } ?>>
<article id="bo_v" class="board-viewer theme-box">
	<hr class="padding">
	<div class="subject">
		<strong><?=bbscat_subject_html(get_text($view['wr_subject']))?></strong>
	</div>
	<div class="info">
		<?php if (!$view['is_notice']) { ?>
			<?php if (!empty($view['wr_protect'])) { ?><span><i class="material-icons" aria-hidden="true">key</i> 보호글</span><?php } elseif (strpos($view['wr_option'], 'secret') !== false) { ?><span><i class="material-icons" aria-hidden="true">lock</i> 비밀글</span><?php } elseif (!empty($view['wr_secret'])) { ?><span><i class="material-icons" aria-hidden="true">group</i> 멤버공개</span><?php } ?>
			<span><time datetime="<?=date('c', strtotime($view['wr_datetime']))?>"><?=date('Y.m.d H:i', strtotime($view['wr_datetime']))?></time></span>
		<?php } ?>
	</div>

	<?php
	$bbscat_files = [];
	for ($i = 0; $i < count($view['file']); $i++) {
		if (!empty($view['file'][$i]['source']) && empty($view['file'][$i]['view'])) $bbscat_files[] = $view['file'][$i];
	}
	if ($bbscat_files) { ?>
	<div class="files">
		<ul>
			<?php foreach ($bbscat_files as $bbscat_f) { ?>
			<li>
				<a href="<?=$bbscat_f['href']?>" class="view_file_download"><i class="material-icons" aria-hidden="true">attach_file</i> <strong><?=$bbscat_f['source']?></strong> <?=$bbscat_f['content']?> (<?=$bbscat_f['size']?>)</a>
				<span class="bo_v_file_cnt"><?=$bbscat_f['download']?>회 다운로드</span>
			</li>
			<?php } ?>
		</ul>
	</div>
	<?php } ?>

	<hr class="line2">
	<div class="contents">
		<?php
		$bbscat_images = '';
		for ($i = 0; $i < count($view['file']); $i++) if (!empty($view['file'][$i]['view'])) $bbscat_images .= $view['file'][$i]['view'];
		if ($bbscat_images !== '') echo '<div id="bo_v_img">'.$bbscat_images.'</div>';
		?>
		<!-- 본문 내용 시작 { -->
		<div id="bo_v_con"><?=bbscat_replace_contents(get_view_thumbnail($view['content']))?></div>
		<!-- } 본문 내용 끝 -->
	</div>

	<hr class="line2">

	<?php include_once G5_BBS_PATH.'/view_comment.php'; ?>

	<!-- 링크 버튼 { -->
	<div id="bo_v_bot">
		<?php if ($prev_href || $next_href) { ?>
		<div class="bo_v_nb">
			<?php if ($prev_href) { ?><a href="<?=$prev_href?>" class="ui-btn">이전글</a><?php } ?>
			<?php if ($next_href) { ?><a href="<?=$next_href?>" class="ui-btn">다음글</a><?php } ?>
		</div>
		<?php } ?>
		<div class="bo_v_com">
			<?php if ($update_href) { ?><a href="<?=$update_href?>" class="ui-btn" title="수정" aria-label="수정">M</a><?php } ?>
			<?php if ($delete_href) { ?><a href="<?=$delete_href?>" class="ui-btn etc" title="삭제" aria-label="삭제" onclick="del(this.href); return false;">X</a><?php } ?>
			<a href="<?=$list_href?>" class="ui-btn">목록</a>
		</div>
	</div>
	<!-- } 링크 버튼 -->
</article>
</div>

<script>
<?php if ($board['bo_download_point'] < 0) { ?>
$(function() {
	$("a.view_file_download").click(function() {
		if (!g5_is_member) { alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오."); return false; }
		var msg = "파일을 다운로드 하시면 포인트가 차감(<?=number_format($board['bo_download_point'])?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n그래도 다운로드 하시겠습니까?";
		if (confirm(msg)) { $(this).attr("href", $(this).attr("href") + "&js=on"); return true; }
		return false;
	});
});
<?php } ?>
$(function() {
	$("a.view_image").click(function() {
		window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
		return false;
	});
	$("#bo_v_con").viewimageresize();
});
</script>
<!-- } 게시글 읽기 끝 -->
