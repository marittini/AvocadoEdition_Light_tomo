<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/bbscategory.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v='.substr(md5_file(__DIR__.'/style.css'), 0, 10).'">', 0);
add_stylesheet(bbscat_theme_vars(), 1);

// 보호글 비밀번호 칸(wr_protect)은 최고관리자가 처음 글쓰기 화면을 열 때 한 번 만든다.
if (!bbscat_has_protect_column($write_table) && $is_admin === 'super') {
	sql_query("ALTER TABLE `{$write_table}` ADD `wr_protect` varchar(255) NOT NULL DEFAULT '' AFTER `wr_url`", false);
}
$bbscat_protect_ready = bbscat_has_protect_column($write_table);
if (empty($_SESSION['bbscat_write_token'])) $_SESSION['bbscat_write_token'] = bin2hex(random_bytes(32));

// 본문은 에디션 공통 DHTML 에디터로 작성한다(HTML 작성 권한이 있는 회원).
$bbscat_core_editor = !empty($is_dhtml_editor);
$is_dhtml_editor = !empty($config['cf_editor']) && $is_member && (int)$member['mb_level'] >= (int)$board['bo_html_level'];
if ($is_dhtml_editor && !$bbscat_core_editor) {
	include_once G5_EDITOR_LIB;
	$bbscat_source = $write['wr_content'] ?? '';
	if ($w === 'u' && strpos($write['wr_option'] ?? '', 'html') === false) $bbscat_source = nl2br(htmlspecialchars($bbscat_source, ENT_QUOTES, 'UTF-8'));
	$editor_html = editor_html('wr_content', get_text($bbscat_source, 0), true);
	$editor_js = get_editor_js('wr_content', true).chk_editor_js('wr_content', true);
}

// 공개 범위
$bbscat_visibility = '';
if ($w === 'u') {
	if (!empty($write['wr_protect'])) $bbscat_visibility = 'protect';
	elseif (!empty($write['wr_secret'])) $bbscat_visibility = 'member';
	elseif (strpos($write['wr_option'] ?? '', 'secret') !== false) $bbscat_visibility = 'secret';
}
$bbscat_options = ['' => '전체공개'];
if ($is_secret && ($is_admin || $is_secret == 1)) $bbscat_options['secret'] = '비밀글';
if ($is_member && $bbscat_protect_ready) $bbscat_options['protect'] = '보호글';
if ($is_member) $bbscat_options['member'] = '멤버공개';
$bbscat_forced_secret = $is_secret == 2 && !$is_admin;

$option = '';
$option_hidden = '';
if ($is_notice) $option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'> <label for="notice">공지</label>';
if ($is_html) {
	if ($is_dhtml_editor) $option_hidden .= '<input type="hidden" value="html1" name="html">';
	else $option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'> <label for="html">html</label>';
}
if ($bbscat_forced_secret) $option_hidden .= '<input type="hidden" name="secret" value="secret">';
if ($is_mail) $option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'> <label for="mail">답변메일받기</label>';
$bbscat_width = (int)($board['bo_table_width'] ?? 0);
?>

<div class="bbscat bbscat-write"<?php if ($bbscat_width > 0) { ?> style="max-width:<?=$bbscat_width?><?=$bbscat_width > 100 ? 'px' : '%'?>;margin:0 auto;"<?php } ?>>
<hr class="padding">
<section id="bo_w">
	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?=$action_url?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="uid" value="<?=get_uniqid()?>">
		<input type="hidden" name="bbscat_token" value="<?=bbscat_e($_SESSION['bbscat_write_token'])?>">
		<input type="hidden" name="w" value="<?=bbscat_e($w)?>">
		<input type="hidden" name="bo_table" value="<?=bbscat_e($bo_table)?>">
		<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
		<input type="hidden" name="sca" value="<?=bbscat_e($sca)?>">
		<input type="hidden" name="sfl" value="<?=bbscat_e($sfl)?>">
		<input type="hidden" name="stx" value="<?=bbscat_e($stx)?>">
		<input type="hidden" name="spt" value="<?=bbscat_e($spt)?>">
		<input type="hidden" name="sst" value="<?=bbscat_e($sst)?>">
		<input type="hidden" name="sod" value="<?=bbscat_e($sod)?>">
		<input type="hidden" name="page" value="<?=(int)$page?>">
		<?=$option_hidden?>

		<div class="board-write theme-box">
			<dl>
				<dt><label for="set_secret">OPTION</label></dt>
				<dd>
					<?php if (!$bbscat_forced_secret) { ?>
					<select name="set_secret" id="set_secret">
						<?php foreach ($bbscat_options as $bbscat_v => $bbscat_label) { ?><option value="<?=$bbscat_v?>"<?=$bbscat_visibility === $bbscat_v ? ' selected' : ''?>><?=$bbscat_label?></option><?php } ?>
					</select>
					<?php } else { ?><span>비밀글</span><?php } ?>
					<?=$option?>
				</dd>
			</dl>
			<dl id="set_protect"<?=$bbscat_visibility === 'protect' ? '' : ' hidden'?>>
				<dt><label for="wr_protect">PW</label></dt>
				<dd><input type="password" name="wr_protect" id="wr_protect" value="" maxlength="20" class="frm_input" autocomplete="new-password" placeholder="<?=$w === 'u' && $bbscat_visibility === 'protect' ? '비워 두면 기존 비밀번호 유지' : '보호글 비밀번호'?>"></dd>
			</dl>
			<dl>
				<dt><label for="wr_subject">SUBJECT</label></dt>
				<dd>
					<input type="text" name="wr_subject" value="<?=$subject?>" id="wr_subject" required class="frm_input required full" size="50" maxlength="255" placeholder="말머리) 제목" aria-describedby="bbscat-subject-help">
					<p class="write-help" id="bbscat-subject-help">제목을 <b>말머리) 제목</b>처럼 쓰면 말머리가 카테고리가 됩니다.</p>
				</dd>
			</dl>
			<?php if (!empty($board['bo_1'])) { ?><div class="write-notice"><?=$board['bo_1']?></div><?php } ?>
			<dl>
				<dt><label for="wr_content">CONTENT</label></dt>
				<dd>
					<div class="wr_content">
						<?php if ($write_min || $write_max) { ?><p id="char_count_desc">이 게시판은 최소 <strong><?=(int)$write_min?></strong>글자 이상, 최대 <strong><?=(int)$write_max?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p><?php } ?>
						<?=$editor_html?>
						<?php if ($write_min || $write_max) { ?><div id="char_count_wrap"><span id="char_count"></span>글자</div><?php } ?>
					</div>
				</dd>
			</dl>
			<?php if ($is_file && !$is_dhtml_editor) { ?>
			<dl>
				<dt>FILES</dt>
				<dd>
					<?php for ($i = 0; $is_file && $i < $file_count; $i++) { ?>
						<input type="file" name="bf_file[]" title="파일첨부 <?=$i + 1?> : 용량 <?=$upload_max_filesize?> 이하만 업로드 가능" class="frm_file frm_input full">
						<?php if ($is_file_content) { ?><input type="text" name="bf_content[]" value="<?=$w == 'u' ? bbscat_e($file[$i]['bf_content'] ?? '') : ''?>" title="파일 설명을 입력해주세요." class="frm_file frm_input" size="50"><?php } ?>
						<?php if ($w == 'u' && !empty($file[$i]['file'])) { ?><input type="checkbox" id="bf_file_del<?=$i?>" name="bf_file_del[<?=$i?>]" value="1"> <label for="bf_file_del<?=$i?>"><?=$file[$i]['source']?>(<?=$file[$i]['size']?>) 파일 삭제</label><?php } ?>
					<?php } ?>
				</dd>
			</dl>
			<?php } ?>
			<?php if ($is_name || $is_password) { ?>
			<dl>
				<dt></dt>
				<dd class="txt-right">
					<?php if ($is_name) { ?><label for="wr_name">NAME<strong class="sound_only">필수</strong></label> <input type="text" name="wr_name" value="<?=$name?>" id="wr_name" required class="frm_input required"><?php } ?>
					<?php if ($is_password) { ?>&nbsp;&nbsp;<label for="wr_password">PASSWORD<strong class="sound_only">필수</strong></label> <input type="password" name="wr_password" id="wr_password" <?=$password_required?> class="frm_input <?=$password_required?>"><?php } ?>
				</dd>
			</dl>
			<?php } ?>
			<?php if ($is_guest) { ?><dl><dt>자동등록방지</dt><dd><?=$captcha_html?></dd></dl><?php } ?>
		</div>

		<hr class="padding">
		<div class="btn_confirm txt-center">
			<input type="submit" value="작성완료" id="btn_submit" accesskey="s" class="btn_submit ui-btn point">
			<a href="./board.php?bo_table=<?=rawurlencode($bo_table)?>" class="btn_cancel ui-btn">취소</a>
		</div>
	</form>

	<script>
	<?php if ($write_min || $write_max) { ?>
	var char_min = parseInt(<?=(int)$write_min?>);
	var char_max = parseInt(<?=(int)$write_max?>);
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
		var visibility = f.set_secret ? f.set_secret.value : "";
		if (visibility === "protect" && !f.wr_protect.value && !<?=$w === 'u' && $bbscat_visibility === 'protect' ? 'true' : 'false'?>) {
			alert("보호글 비밀번호를 입력해 주세요."); f.wr_protect.focus(); return false;
		}
		<?=$captcha_js ?? ''?>
		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}

	(function () {
		var select = document.getElementById("set_secret"), box = document.getElementById("set_protect"), pw = document.getElementById("wr_protect");
		if (!select) return;
		select.addEventListener("change", function () {
			box.hidden = select.value !== "protect";
			if (box.hidden) pw.value = ""; else pw.focus();
		});
	})();
	</script>
</section>
</div>
<!-- } 게시물 작성/수정 끝 -->
