<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/bbscategory.lib.php';
$c_id = isset($c_id) ? (int)$c_id : 0;
$c_wr_content = '';
if ($w == 'cu' && $c_id) {
	$cmt = sql_fetch("SELECT wr_id, wr_content, mb_id FROM {$write_table} WHERE wr_id = '{$c_id}' AND wr_is_comment = '1'");
	if (!empty($cmt) && ($is_admin || (!empty($cmt['mb_id']) && $member['mb_id'] == $cmt['mb_id']))) $c_wr_content = $cmt['wr_content'];
}
?>

<script>
var char_min = parseInt(<?=(int)$comment_min?>); // 최소
var char_max = parseInt(<?=(int)$comment_max?>); // 최대
</script>

<?php if (count($list)) { ?>
<div class="board-comment-list theme-box" id="bo_vc">
	<hr class="co-line">
	<?php for ($i = 0; $i < count($list); $i++) {
		$comment_id = (int)$list[$i]['wr_id'];
		$cmt_depth = strlen($list[$i]['wr_comment_reply']) * 10;
		$bbscat_name = bbscat_e($list[$i]['wr_name']);
		if (!empty($list[$i]['mb_id'])) $bbscat_name = '<a href="'.G5_BBS_URL.'/memo_form.php?me_recv_mb_id='.urlencode($list[$i]['mb_id']).'" class="send_memo">'.$bbscat_name.'</a>';
	?>
	<div class="item<?=$cmt_depth ? ' reply' : ''?>" id="c_<?=$comment_id?>"<?php if ($cmt_depth) { ?> style="border-left-width:<?=$cmt_depth?>px;"<?php } ?>>
		<div class="co-name txt-point"><?=$bbscat_name?></div>
		<div class="co-content">
			<div class="co-inner">
				<?php if (strpos($list[$i]['wr_option'], 'secret') !== false) { ?><span class="secret"><i class="material-icons" aria-hidden="true">lock</i> 비밀댓글</span><?php } ?>
				<?=bbscat_replace_contents($list[$i]['content'])?>
			</div>
			<div class="co-info">
				<span><?=date('Y.m.d H:i', strtotime($list[$i]['wr_datetime']))?></span>
				<?php if ($list[$i]['is_reply'] || $list[$i]['is_edit'] || $list[$i]['is_del']) {
					$query_string = clean_query_string($_SERVER['QUERY_STRING']);
					$c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
					$c_edit_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
				?>
					<?php if ($list[$i]['is_reply']) { ?><span><a href="<?=$c_reply_href?>" onclick="comment_box('<?=$comment_id?>', 'c'); return false;" >답글</a></span><?php } ?>
					<?php if ($list[$i]['is_edit']) { ?><span><a href="<?=$c_edit_href?>" onclick="comment_box('<?=$comment_id?>', 'cu'); return false;">수정</a></span><?php } ?>
					<?php if ($list[$i]['is_del']) { ?><span><a href="<?=$list[$i]['del_link']?>" onclick="return comment_delete();">삭제</a></span><?php } ?>
				<?php } ?>
			</div>
			<span id="edit_<?=$comment_id?>"></span><!-- 수정 -->
			<span id="reply_<?=$comment_id?>"></span><!-- 답변 -->
			<input type="hidden" value="<?=strpos($list[$i]['wr_option'], 'secret') !== false ? 'secret' : ''?>" id="secret_comment_<?=$comment_id?>">
			<textarea id="save_comment_<?=$comment_id?>" style="display:none"><?=get_text($list[$i]['content1'], 0)?></textarea>
		</div>
	</div>
	<hr class="co-line">
	<?php } ?>
</div>
<?php } ?>

<?php if ($is_comment_write && !$is_guest) { if ($w == '') $w = 'c'; ?>
<!-- 댓글 쓰기 시작 { -->
<div id="bo_vc_w" class="board-comment-write">
	<form name="fviewcomment" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
		<input type="hidden" name="w" value="<?=bbscat_e($w)?>" id="w">
		<input type="hidden" name="bo_table" value="<?=bbscat_e($bo_table)?>">
		<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
		<input type="hidden" name="comment_id" value="<?=$c_id ?: ''?>" id="comment_id">
		<input type="hidden" name="sca" value="<?=bbscat_e($sca)?>">
		<input type="hidden" name="sfl" value="<?=bbscat_e($sfl)?>">
		<input type="hidden" name="stx" value="<?=bbscat_e($stx)?>">
		<input type="hidden" name="spt" value="<?=bbscat_e($spt)?>">
		<input type="hidden" name="page" value="<?=(int)$page?>">
		<input type="hidden" name="is_good" value="">

		<div class="board-comment-form">
			<?php if ($comment_min || $comment_max) { ?><strong id="char_cnt"><span id="char_count"></span>글자</strong><?php } ?>
			<label for="wr_content" class="sound_only">댓글 내용</label>
			<textarea id="wr_content" name="wr_content" maxlength="10000" required class="required" title="내용"<?php if ($comment_min || $comment_max) { ?> onkeyup="check_byte('wr_content', 'char_count');"<?php } ?>><?=bbscat_e($c_wr_content)?></textarea>
			<?php if ($comment_min || $comment_max) { ?><script>check_byte('wr_content', 'char_count');</script><?php } ?>
			<div class="btn_confirm">
				<button type="submit" id="btn_submit" class="ui-btn">댓글등록</button>
			</div>
			<p class="bbscat-cform-opts">
				<span><input type="checkbox" name="wr_secret" value="secret" id="wr_secret"> <label for="wr_secret">비밀글</label></span>
				<?php include_once G5_LIB_PATH.'/emoticon.lib.php'; echo emoticon_button('textarea'); // 이모티콘(등록된 것이 있을 때만) ?>
			</p>
		</div>
	</form>
</div>

<script>
var save_before = '';
var save_html = document.getElementById('bo_vc_w').innerHTML;

function fviewcomment_submit(f) {
	var pattern = /(^\s*)|(\s*$)/g;
	f.is_good.value = 0;
	var content = "";
	$.ajax({
		url: g5_bbs_url + "/ajax.filter.php", type: "POST",
		data: { "subject": "", "content": f.wr_content.value },
		dataType: "json", async: false, cache: false,
		success: function(data) { content = data.content; }
	});
	if (content) { alert("내용에 금지단어('" + content + "')가 포함되어있습니다"); f.wr_content.focus(); return false; }
	document.getElementById('wr_content').value = document.getElementById('wr_content').value.replace(pattern, "");
	if (char_min > 0 || char_max > 0) {
		check_byte('wr_content', 'char_count');
		var cnt = parseInt(document.getElementById('char_count').innerHTML);
		if (char_min > 0 && char_min > cnt) { alert("댓글은 " + char_min + "글자 이상 쓰셔야 합니다."); return false; }
		if (char_max > 0 && char_max < cnt) { alert("댓글은 " + char_max + "글자 이하로 쓰셔야 합니다."); return false; }
	} else if (!document.getElementById('wr_content').value) {
		alert("댓글을 입력하여 주십시오."); return false;
	}
	set_comment_token(f);
	document.getElementById("btn_submit").disabled = "disabled";
	return true;
}

function comment_box(comment_id, work) {
	var el_id = comment_id ? (work == 'c' ? 'reply_' : 'edit_') + comment_id : 'bo_vc_w';
	if (save_before != el_id) {
		if (save_before) {
			document.getElementById(save_before).style.display = 'none';
			document.getElementById(save_before).innerHTML = '';
		}
		document.getElementById(el_id).style.display = '';
		document.getElementById(el_id).innerHTML = save_html;
		if (work == 'cu') {
			document.getElementById('wr_content').value = document.getElementById('save_comment_' + comment_id).value;
			if (typeof char_count != 'undefined') check_byte('wr_content', 'char_count');
			document.getElementById('wr_secret').checked = !!document.getElementById('secret_comment_' + comment_id).value;
		}
		document.getElementById('comment_id').value = comment_id;
		document.getElementById('w').value = work;
		save_before = el_id;
	}
}

function comment_delete() { return confirm("이 댓글을 삭제하시겠습니까?"); }

comment_box('', 'c');
</script>
<?php } ?>
