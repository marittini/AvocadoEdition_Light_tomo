<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once G5_LIB_PATH.'/thumbnail.lib.php';
include_once __DIR__.'/diary.lib.php';
diary_assets($board_skin_url);

$diary_dt = strtotime($view['wr_datetime']);
$diary_back = diary_url($bo_table, ['yr' => (int)($_GET['yr'] ?? date('Y', $diary_dt)), 'mo' => (int)($_GET['mo'] ?? 0)]);
$diary_width = (int)($board['bo_table_width'] ?? 0);

$diary_images = '';
$diary_files = [];
for ($i = 0; $i < count($view['file']); $i++) {
	if (!empty($view['file'][$i]['view'])) $diary_images .= $view['file'][$i]['view'];
	elseif (!empty($view['file'][$i]['source'])) $diary_files[] = $view['file'][$i];
}
?>
<script src="<?=G5_JS_URL?>/viewimageresize.js"></script>

<!-- 다이어리 읽기 { -->
<div class="diary diary-view"<?php if ($diary_width > 0) { ?> style="max-width:<?=$diary_width?><?=$diary_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<article id="bo_v" class="diary-paper theme-box">
		<header class="diary-head">
			<p class="diary-date"><time datetime="<?=diary_e(date('c', $diary_dt))?>"><?=date('Y. n. j.', $diary_dt)?> <?=diary_weekday($view['wr_datetime'])?>요일</time><?php if (strpos($view['wr_option'], 'secret') !== false) { ?> <i class="material-icons" aria-hidden="true">lock</i><span class="sound_only">비밀글</span><?php } ?></p>
			<h1 class="diary-title"><?=get_text($view['wr_subject']) !== '' ? get_text($view['wr_subject']) : '제목 없음'?></h1>
		</header>

		<div class="diary-body">
			<?php if ($diary_images !== '') { ?><div id="bo_v_img" class="diary-images"><?=$diary_images?></div><?php } ?>
			<div id="bo_v_con"><?=emoticon_html(get_view_thumbnail($view['content']))?></div>
		</div>

		<?php if ($diary_files) { ?>
		<ul class="diary-files">
			<?php foreach ($diary_files as $f) { ?><li><a href="<?=$f['href']?>" class="view_file_download"><i class="material-icons" aria-hidden="true">attach_file</i> <?=$f['source']?> <span>(<?=$f['size']?>)</span></a></li><?php } ?>
		</ul>
		<?php } ?>

	</article>

	<div class="diary-actions diary-view-actions">
		<div class="diary-nb">
			<?php if ($prev_href) { ?><a href="<?=$prev_href?>" class="ui-btn">이전글</a><?php } ?>
			<?php if ($next_href) { ?><a href="<?=$next_href?>" class="ui-btn">다음글</a><?php } ?>
		</div>
		<div class="diary-com">
			<?php if ($update_href) { ?><a href="<?=$update_href?>" class="ui-btn">수정</a><?php } ?>
			<?php if ($delete_href) { ?><a href="<?=$delete_href?>" class="ui-btn etc" onclick="del(this.href); return false;">삭제</a><?php } ?>
			<a href="<?=$diary_back?>" class="ui-btn">목록</a>
		</div>
	</div>
</div>

<script>
<?php if ($board['bo_download_point'] < 0) { ?>
$(function() {
	$("a.view_file_download").click(function() {
		if (!g5_is_member) { alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오."); return false; }
		if (confirm("파일을 다운로드 하시면 포인트가 차감(<?=number_format($board['bo_download_point'])?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n그래도 다운로드 하시겠습니까?")) { $(this).attr("href", $(this).attr("href") + "&js=on"); return true; }
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
<!-- } 다이어리 읽기 -->
