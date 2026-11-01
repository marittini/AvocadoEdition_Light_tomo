<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/diary.lib.php';
diary_assets($board_skin_url);
if (empty($_SESSION['diary_write_token'])) $_SESSION['diary_write_token'] = bin2hex(random_bytes(32));

// 본문은 에디션 공통 DHTML 에디터로 쓴다(HTML 작성 권한이 있는 회원).
$diary_core_editor = !empty($is_dhtml_editor);
$is_dhtml_editor = !empty($config['cf_editor']) && $is_member && (int)$member['mb_level'] >= (int)$board['bo_html_level'];
if ($is_dhtml_editor && !$diary_core_editor) {
	include_once G5_EDITOR_LIB;
	$diary_source = $write['wr_content'] ?? '';
	if ($w === 'u' && strpos($write['wr_option'] ?? '', 'html') === false) $diary_source = nl2br(htmlspecialchars($diary_source, ENT_QUOTES, 'UTF-8'));
	$editor_html = editor_html('wr_content', get_text($diary_source, 0), true);
	$editor_js = get_editor_js('wr_content', true).chk_editor_js('wr_content', true);
}

$diary_options = [];
if ($is_notice) $diary_options[] = '<label><input type="checkbox" name="notice" value="1" '.$notice_checked.'> 공지</label>';
if ($is_secret == 2 && !$is_admin) $diary_hidden_secret = '<input type="hidden" name="secret" value="secret">';
elseif ($is_secret) $diary_options[] = '<label><input type="checkbox" name="secret" value="secret" '.$secret_checked.'> 비밀글</label>';
if ($is_html && !$is_dhtml_editor) $diary_options[] = '<label><input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'> html</label>';
if ($is_mail) $diary_options[] = '<label><input type="checkbox" name="mail" value="mail" '.$recv_email_checked.'> 답변메일받기</label>';
$diary_width = (int)($board['bo_table_width'] ?? 0);
?>

<!-- 다이어리 쓰기 { -->
<div class="diary diary-write"<?php if ($diary_width > 0) { ?> style="max-width:<?=$diary_width?><?=$diary_width > 100 ? 'px' : '%'?>;"<?php } ?>>
<section id="bo_w">
	<form name="fwrite" id="fwrite" action="<?=$action_url?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="uid" value="<?=get_uniqid()?>">
		<input type="hidden" name="diary_token" value="<?=diary_e($_SESSION['diary_write_token'])?>">
		<input type="hidden" name="w" value="<?=diary_e($w)?>">
		<input type="hidden" name="bo_table" value="<?=diary_e($bo_table)?>">
		<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
		<input type="hidden" name="sca" value="<?=diary_e($sca)?>">
		<input type="hidden" name="sfl" value="<?=diary_e($sfl)?>">
		<input type="hidden" name="stx" value="<?=diary_e($stx)?>">
		<input type="hidden" name="spt" value="<?=diary_e($spt)?>">
		<input type="hidden" name="sst" value="<?=diary_e($sst)?>">
		<input type="hidden" name="sod" value="<?=diary_e($sod)?>">
		<input type="hidden" name="page" value="<?=(int)$page?>">
		<?php if ($is_html && $is_dhtml_editor) { ?><input type="hidden" name="html" value="html1"><?php } ?>
		<?=$diary_hidden_secret ?? ''?>

		<div class="diary-paper theme-box">
			<p class="diary-date"><?=$w === 'u' && !empty($write['wr_datetime']) ? date('Y. n. j.', strtotime($write['wr_datetime'])).' '.diary_weekday($write['wr_datetime']).'요일' : date('Y. n. j.', G5_SERVER_TIME).' '.diary_weekday(G5_TIME_YMDHIS).'요일'?></p>
			<label for="wr_subject" class="sound_only">제목<strong>필수</strong></label>
			<input type="text" name="wr_subject" value="<?=$subject?>" id="wr_subject" required class="diary-title-input frm_input required" maxlength="255" placeholder="제목">

			<?php if (!empty($board['bo_1'])) { ?><div class="diary-guide"><?=$board['bo_1']?></div><?php } ?>

			<div class="diary-editor wr_content">
				<?php if ($write_min || $write_max) { ?><p id="char_count_desc">최소 <strong><?=(int)$write_min?></strong>글자 이상, 최대 <strong><?=(int)$write_max?></strong>글자 이하</p><?php } ?>
				<label for="wr_content" class="sound_only">내용<strong>필수</strong></label>
				<?=$editor_html?>
				<?php if ($diary_emoticon = emoticon_button($is_dhtml_editor ? 'editor:wr_content' : 'textarea')) { ?><div class="diary-emoticon"><?=$diary_emoticon?></div><?php } ?>
				<?php if ($write_min || $write_max) { ?><div id="char_count_wrap"><span id="char_count"></span>글자</div><?php } ?>
			</div>

			<?php if ($is_file && !$is_dhtml_editor) { ?>
			<div class="diary-field">
				<span class="diary-label">파일</span>
				<div>
				<?php for ($i = 0; $i < $file_count; $i++) { ?>
					<input type="file" name="bf_file[]" title="파일첨부 <?=$i + 1?> : 용량 <?=$upload_max_filesize?> 이하만 업로드 가능" class="frm_file frm_input">
					<?php if ($w == 'u' && !empty($file[$i]['file'])) { ?><label><input type="checkbox" name="bf_file_del[<?=$i?>]" value="1"> <?=$file[$i]['source']?>(<?=$file[$i]['size']?>) 삭제</label><?php } ?>
				<?php } ?>
				</div>
			</div>
			<?php } ?>

			<?php if ($is_name || $is_password) { ?>
			<div class="diary-field">
				<?php if ($is_name) { ?><label for="wr_name" class="diary-label">이름</label><input type="text" name="wr_name" value="<?=$name?>" id="wr_name" required class="frm_input required"><?php } ?>
				<?php if ($is_password) { ?><label for="wr_password" class="diary-label">비밀번호</label><input type="password" name="wr_password" id="wr_password" <?=$password_required?> class="frm_input <?=$password_required?>"><?php } ?>
			</div>
			<?php } ?>
			<?php if ($is_guest) { ?><div class="diary-field"><?=$captcha_html?></div><?php } ?>

			<?php if ($diary_options) { ?><div class="diary-options"><?=implode('', $diary_options)?></div><?php } ?>
		</div>

		<div class="diary-actions">
			<a href="<?=diary_url($bo_table)?>" class="ui-btn">취소</a>
			<button type="submit" id="btn_submit" accesskey="s" class="ui-btn point"><?=$w === 'u' ? '수정' : '작성'?></button>
		</div>
	</form>

	<script>
	<?php if ($write_min || $write_max) { ?>
	var char_min = parseInt(<?=(int)$write_min?>), char_max = parseInt(<?=(int)$write_max?>);
	check_byte("wr_content", "char_count");
	$(function() { $("#wr_content").on("keyup", function() { check_byte("wr_content", "char_count"); }); });
	<?php } ?>
	function html_auto_br(obj) {
		if (obj.checked) obj.value = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.") ? "html2" : "html1";
		else obj.value = "";
	}
	function fwrite_submit(f) {
		<?=$editor_js?>
		var subject = "", content = "";
		$.ajax({
			url: g5_bbs_url + "/ajax.filter.php", type: "POST",
			data: { "subject": f.wr_subject.value, "content": f.wr_content.value },
			dataType: "json", async: false, cache: false,
			success: function(data) { subject = data.subject; content = data.content; }
		});
		if (subject) { alert("제목에 금지단어('" + subject + "')가 포함되어있습니다"); f.wr_subject.focus(); return false; }
		if (content) {
			alert("내용에 금지단어('" + content + "')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined") ed_wr_content.returnFalse(); else f.wr_content.focus();
			return false;
		}
		if (document.getElementById("char_count") && (char_min > 0 || char_max > 0)) {
			var cnt = parseInt(check_byte("wr_content", "char_count"));
			if (char_min > 0 && char_min > cnt) { alert("내용은 " + char_min + "글자 이상 쓰셔야 합니다."); return false; }
			if (char_max > 0 && char_max < cnt) { alert("내용은 " + char_max + "글자 이하로 쓰셔야 합니다."); return false; }
		}
		<?=$captcha_js ?? ''?>
		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}
	</script>
</section>
</div>
<!-- } 다이어리 쓰기 -->
