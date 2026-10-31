<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/bbscategory.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v='.substr(md5_file(__DIR__.'/style.css'), 0, 10).'">', 0);
add_stylesheet(bbscat_theme_vars(), 1);

// 제목의 "말머리)"로 만든 카테고리. 게시판 설정의 분류는 사용하지 않는다.
$bbscat_genres = bbscat_categories($write_table);
$bbscat_base = G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);
$bbscat_width = (int)($board['bo_table_width'] ?? 0);
?>

<!-- 게시판 목록 시작 { -->
<div class="bbscat bbscat-list"<?php if ($bbscat_width > 0) { ?> style="max-width:<?=$bbscat_width?><?=$bbscat_width > 100 ? 'px' : '%'?>;margin:0 auto;"<?php } ?>>
	<hr class="padding">
	<?php if ($board['bo_content_head']) { ?>
		<div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div>
		<hr class="padding">
	<?php } ?>

	<?php if ($bbscat_genres) { ?>
	<!-- 말머리 카테고리 { -->
	<nav class="bbscat-category" aria-label="말머리">
		<ul>
			<li><a href="<?=$bbscat_base?>" class="ui-btn <?=$sca === '' ? 'point' : 'etc'?>"<?=$sca === '' ? ' aria-current="page"' : ''?>>전체</a></li>
			<?php foreach ($bbscat_genres as $bbscat_name => $bbscat_cnt) { $bbscat_on = (string)$bbscat_name === $sca; ?>
			<li><a href="<?=$bbscat_base?>&amp;sca=<?=urlencode((string)$bbscat_name)?>" class="ui-btn <?=$bbscat_on ? 'point' : 'etc'?>"<?=$bbscat_on ? ' aria-current="page"' : ''?>><?=bbscat_e($bbscat_name)?> <span class="cnt"><?=$bbscat_cnt?></span></a></li>
			<?php } ?>
		</ul>
	</nav>
	<!-- } 말머리 카테고리 -->
	<?php } ?>

	<form name="fboardlist" id="fboardlist" action="./board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
		<input type="hidden" name="bo_table" value="<?=bbscat_e($bo_table)?>">
		<input type="hidden" name="sfl" value="<?=bbscat_e($sfl)?>">
		<input type="hidden" name="stx" value="<?=bbscat_e($stx)?>">
		<input type="hidden" name="spt" value="<?=bbscat_e($spt)?>">
		<input type="hidden" name="sca" value="<?=bbscat_e($sca)?>">
		<input type="hidden" name="sst" value="<?=bbscat_e($sst)?>">
		<input type="hidden" name="sod" value="<?=bbscat_e($sod)?>">
		<input type="hidden" name="page" value="<?=(int)$page?>">
		<input type="hidden" name="sw" value="">

		<ul class="bbscat-rows">
			<?php for ($i = 0; $i < count($list); $i++) {
				$row = $list[$i];
				if (strpos($row['wr_option'], 'secret') !== false) $bbscat_mark = ['lock', '비밀글'];
				elseif (!empty($row['wr_protect'])) $bbscat_mark = ['key', '보호글'];
				elseif (!empty($row['wr_secret'])) $bbscat_mark = ['group', '멤버공개'];
				else $bbscat_mark = null;
			?>
			<li class="theme-box<?=$row['is_notice'] ? ' bo_notice' : ''?>">
				<?php if ($is_checkbox) { ?>
				<span class="td_chk">
					<label for="chk_wr_id_<?=$i?>" class="sound_only"><?=$row['subject']?></label>
					<input type="checkbox" name="chk_wr_id[]" value="<?=(int)$row['wr_id']?>" id="chk_wr_id_<?=$i?>">
				</span>
				<?php } ?>
				<a href="<?=$row['href']?>">
					<strong class="subject">
						<?php if ($bbscat_mark) { ?><span class="mark" title="<?=$bbscat_mark[1]?>"><i class="material-icons" aria-hidden="true"><?=$bbscat_mark[0]?></i><span class="sound_only"><?=$bbscat_mark[1]?></span></span><?php } ?>
						<?=bbscat_subject_html($row['subject'])?>
						<?php if ((int)$row['wr_comment']) { ?><span class="cnt_cmt"><?=(int)$row['wr_comment']?><span class="sound_only">개 댓글</span></span><?php } ?>
					</strong>
					<span class="info">
						<?php if (!$row['is_notice']) { ?><span class="date"><?=date('Y.m.d', strtotime($row['wr_datetime']))?></span><?php } else { ?><span class="date">공지</span><?php } ?>
					</span>
				</a>
			</li>
			<?php } ?>
			<?php if (count($list) == 0) { ?><li class="no-data">게시물이 없습니다.</li><?php } ?>
		</ul>

		<?php if ($list_href || $is_checkbox || $write_href || $admin_href) { ?>
		<div class="bo_fx txt-right">
			<?php if ($is_checkbox) { ?>
				<p class="chk_all">
					<label for="chkall" class="sound_only">현재 페이지 게시물 전체</label>
					<input type="checkbox" id="chkall" onclick="all_checked(this.checked);">
				</p>
				<button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value" class="ui-btn admin">선택삭제</button>
				<button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value" class="ui-btn admin">선택복사</button>
				<button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value" class="ui-btn admin">선택이동</button>
			<?php } ?>
			<?php if ($list_href) { ?><a href="<?=$list_href?>" class="ui-btn">목록</a><?php } ?>
			<?php if ($write_href) { ?><a href="<?=$write_href?>" class="ui-btn point">글쓰기</a><?php } ?>
			<?php if ($admin_href) { ?><a href="<?=$admin_href?>" class="ui-btn admin" target="_blank">관리자</a><?php } ?>
		</div>
		<?php } ?>
	</form>

	<?=$write_pages?>

	<!-- 게시판 검색 { -->
	<fieldset id="bo_sch" class="txt-center">
		<legend>게시물 검색</legend>
		<form name="fsearch" method="get">
			<input type="hidden" name="bo_table" value="<?=bbscat_e($bo_table)?>">
			<input type="hidden" name="sca" value="<?=bbscat_e($sca)?>">
			<input type="hidden" name="sop" value="and">
			<label for="sfl" class="sound_only">검색대상</label>
			<select name="sfl" id="sfl">
				<option value="wr_subject"<?=get_selected($sfl, 'wr_subject', true)?>>제목</option>
				<option value="wr_content"<?=get_selected($sfl, 'wr_content')?>>내용</option>
				<option value="wr_subject||wr_content"<?=get_selected($sfl, 'wr_subject||wr_content')?>>제목+내용</option>
			</select>
			<label for="stx" class="sound_only">검색어</label>
			<input type="text" name="stx" value="<?=bbscat_e(stripslashes($stx))?>" required id="stx" class="frm_input required" size="15" maxlength="20">
			<button type="submit" class="ui-btn point ico search default">검색</button>
		</form>
	</fieldset>
	<!-- } 게시판 검색 -->
</div>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
	var f = document.fboardlist;
	for (var i = 0; i < f.length; i++) if (f.elements[i].name == "chk_wr_id[]") f.elements[i].checked = sw;
}
function fboardlist_submit(f) {
	var chk_count = 0;
	for (var i = 0; i < f.length; i++) if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked) chk_count++;
	if (!chk_count) { alert(document.pressed + "할 게시물을 하나 이상 선택하세요."); return false; }
	if (document.pressed == "선택복사") { select_copy("copy"); return; }
	if (document.pressed == "선택이동") { select_copy("move"); return; }
	if (document.pressed == "선택삭제") {
		if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다.")) return false;
		f.removeAttribute("target");
		f.action = "./board_list_update.php";
	}
	return true;
}
function select_copy(sw) {
	var f = document.fboardlist;
	window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");
	f.sw.value = sw;
	f.target = "move";
	f.action = "./move.php";
	f.submit();
}
</script>
<?php } ?>
<!-- } 게시판 목록 끝 -->
