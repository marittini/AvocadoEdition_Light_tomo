<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/gallery.lib.php';
gl_assets($board_skin_url);

// 보호글 비밀번호 칸(wr_protect)은 최고관리자가 처음 글쓰기 화면을 열 때 한 번 만든다.
if (!gl_has_protect_column($write_table) && $is_admin === 'super') {
	sql_query("ALTER TABLE `{$write_table}` ADD `wr_protect` varchar(255) NOT NULL DEFAULT '' AFTER `wr_url`", false);
}
$gl_protect_ready = (bool)sql_fetch("SHOW COLUMNS FROM `{$write_table}` LIKE 'wr_protect'", false);

// 본문은 에디션 공통 DHTML 에디터(HTML 작성 권한이 있는 회원).
$gl_core_editor = !empty($is_dhtml_editor);
$is_dhtml_editor = !empty($config['cf_editor']) && $is_member && (int)$member['mb_level'] >= (int)$board['bo_html_level'];
if ($is_dhtml_editor && !$gl_core_editor) {
	include_once G5_EDITOR_LIB;
	$gl_source = $write['wr_content'] ?? '';
	if ($w === 'u' && strpos($write['wr_option'] ?? '', 'html') === false) $gl_source = nl2br(htmlspecialchars($gl_source, ENT_QUOTES, 'UTF-8'));
	$editor_html = editor_html('wr_content', get_text($gl_source, 0), true);
	$editor_js = get_editor_js('wr_content', true).chk_editor_js('wr_content', true);
}
// 그림 글은 이미지만 올려도 되므로 '내용 없음' 확인은 하지 않는다(에디터 내용 저장만).
if ($is_dhtml_editor) {
	include_once G5_EDITOR_LIB;
	$editor_js = get_editor_js('wr_content', true);
}

$gl_kind = $w === 'u' ? gl_kind($write) : (($_GET['kind'] ?? '') === 'text' ? 'text' : 'art');
$gl_vis = $w === 'u' ? gl_visibility($write) : '';
$gl_vis_options = ['' => '전체공개'];
if ($is_member) $gl_vis_options['member'] = '멤버공개';
if ($is_member && $gl_protect_ready) $gl_vis_options['protect'] = '보호글';
if ($is_secret || $is_admin) $gl_vis_options['secret'] = '비밀글';
$gl_forced_secret = (int)$board['bo_use_secret'] === 2 && !$is_admin;
$gl_width = (int)($board['bo_table_width'] ?? 0);
?>

<!-- 갤러리 쓰기 { -->
<div class="gl gl-write"<?php if ($gl_width > 0) { ?> style="max-width:<?=$gl_width?><?=$gl_width > 100 ? 'px' : '%'?>;"<?php } ?>>
<section id="bo_w">
	<form name="fwrite" id="fwrite" action="<?=$action_url?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="uid" value="<?=get_uniqid()?>">
		<input type="hidden" name="gl_token" value="<?=gl_e(gl_token())?>">
		<input type="hidden" name="w" value="<?=gl_e($w)?>">
		<input type="hidden" name="bo_table" value="<?=gl_e($bo_table)?>">
		<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
		<input type="hidden" name="sca" value="<?=gl_e($sca)?>">
		<input type="hidden" name="sfl" value="<?=gl_e($sfl)?>">
		<input type="hidden" name="stx" value="<?=gl_e($stx)?>">
		<input type="hidden" name="spt" value="<?=gl_e($spt)?>">
		<input type="hidden" name="sst" value="<?=gl_e($sst)?>">
		<input type="hidden" name="sod" value="<?=gl_e($sod)?>">
		<input type="hidden" name="page" value="<?=(int)$page?>">
		<?php if ($is_html && $is_dhtml_editor) { ?><input type="hidden" name="html" value="html1"><?php } ?>

		<div class="gl-paper theme-box">
			<div class="gl-w-row">
				<span class="gl-w-label">종류</span>
				<div class="gl-w-seg" role="radiogroup" aria-label="종류">
					<?php foreach (GL_KINDS as $k => $label) { ?><label><input type="radio" name="gl_kind" value="<?=$k?>"<?=$gl_kind === $k ? ' checked' : ''?>> <?=$label?></label><?php } ?>
				</div>
			</div>
			<?php if ($is_category) { ?>
			<div class="gl-w-row">
				<label class="gl-w-label" for="ca_name">분류</label>
				<select name="ca_name" id="ca_name" required class="frm_input">
					<option value="">선택하세요</option>
					<?=$category_option?>
				</select>
			</div>
			<?php } ?>
			<div class="gl-w-row">
				<label class="gl-w-label" for="set_secret">공개</label>
				<div class="gl-w-inline">
					<?php if ($gl_forced_secret) { ?><span class="gl-w-note">이 게시판의 글은 모두 비밀글입니다.</span><input type="hidden" name="set_secret" value="secret">
					<?php } else { ?>
					<select name="set_secret" id="set_secret" class="frm_input">
						<?php foreach ($gl_vis_options as $k => $label) { ?><option value="<?=$k?>"<?=$gl_vis === $k ? ' selected' : ''?>><?=$label?></option><?php } ?>
					</select>
					<?php } ?>
					<input type="password" name="wr_protect" id="wr_protect" class="frm_input" maxlength="20" autocomplete="new-password" aria-label="보호글 비밀번호"<?=$gl_vis === 'protect' ? '' : ' hidden'?>
						placeholder="<?=$w === 'u' && $gl_vis === 'protect' ? '비워 두면 기존 비밀번호 유지' : '보호글 비밀번호'?>">
					<?php if ($is_notice) { ?><label class="gl-w-check"><input type="checkbox" name="notice" value="1" <?=$notice_checked?>> 공지</label><?php } ?>
				</div>
			</div>
			<div class="gl-w-row">
				<label class="gl-w-label" for="wr_subject">제목</label>
				<input type="text" name="wr_subject" value="<?=$subject?>" id="wr_subject" required class="frm_input required gl-w-full" maxlength="255">
			</div>
			<div class="gl-w-row">
				<label class="gl-w-label" for="wr_1">출처</label>
				<input type="text" name="wr_1" value="<?=gl_e($write['wr_1'] ?? '')?>" id="wr_1" class="frm_input gl-w-full" maxlength="255" placeholder="그린 사람 · 쓴 사람 · 커미션 등 (선택)">
			</div>

			<div class="gl-w-editor wr_content">
				<?php if ($write_min || $write_max) { ?><p id="char_count_desc">최소 <strong><?=(int)$write_min?></strong>글자 이상, 최대 <strong><?=(int)$write_max?></strong>글자 이하</p><?php } ?>
				<label for="wr_content" class="sound_only">내용</label>
				<?=$editor_html?>
				<?php if ($write_min || $write_max) { ?><div id="char_count_wrap"><span id="char_count"></span>글자</div><?php } ?>
			</div>

			<?php if ($is_file) { ?>
			<div class="gl-w-row is-top">
				<span class="gl-w-label">이미지</span>
				<div class="gl-w-files">
					<p class="gl-w-note">첫 번째 이미지가 목록 썸네일이 됩니다. 글(소설)은 이미지 없이 올려도 됩니다.</p>
					<?php for ($i = 0; $i < $file_count; $i++) { ?>
					<div class="gl-w-file">
						<input type="file" name="bf_file[]" accept="image/*" title="파일첨부 <?=$i + 1?> : 용량 <?=$upload_max_filesize?> 이하만 업로드 가능" class="frm_file frm_input">
						<?php if ($w == 'u' && !empty($file[$i]['file'])) { ?><label class="gl-w-check"><input type="checkbox" name="bf_file_del[<?=$i?>]" value="1"> <?=$file[$i]['source']?>(<?=$file[$i]['size']?>) 삭제</label><?php } ?>
					</div>
					<?php } ?>
				</div>
			</div>
			<?php } ?>

			<?php if ($is_name || $is_password) { ?>
			<div class="gl-w-row">
				<span class="gl-w-label">작성자</span>
				<div class="gl-w-inline">
					<?php if ($is_name) { ?><input type="text" name="wr_name" value="<?=$name?>" id="wr_name" required class="frm_input required" placeholder="이름" aria-label="이름"><?php } ?>
					<?php if ($is_password) { ?><input type="password" name="wr_password" id="wr_password" <?=$password_required?> class="frm_input <?=$password_required?>" placeholder="비밀번호" aria-label="비밀번호"><?php } ?>
				</div>
			</div>
			<?php } ?>
			<?php if ($is_guest) { ?><div class="gl-w-row"><span class="gl-w-label"></span><div><?=$captcha_html?></div></div><?php } ?>
		</div>

		<div class="gl-actions">
			<a href="<?=gl_url($bo_table)?>" class="ui-btn">취소</a>
			<button type="submit" id="btn_submit" accesskey="s" class="ui-btn point"><?=$w === 'u' ? '수정' : '작성'?></button>
		</div>
	</form>

	<script>
	<?php if ($write_min || $write_max) { ?>
	var char_min = parseInt(<?=(int)$write_min?>), char_max = parseInt(<?=(int)$write_max?>);
	check_byte("wr_content", "char_count");
	$(function() { $("#wr_content").on("keyup", function() { check_byte("wr_content", "char_count"); }); });
	<?php } ?>
	(function () {
		var select = document.getElementById("set_secret"), pw = document.getElementById("wr_protect");
		if (!select || !pw) return;
		select.addEventListener("change", function () { pw.hidden = select.value !== "protect"; if (!pw.hidden) pw.focus(); });
	})();
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
		if (f.set_secret && f.set_secret.value === "protect" && !f.wr_protect.value && <?=$w === 'u' && $gl_vis === 'protect' ? 'false' : 'true'?>) {
			alert("보호글 비밀번호를 입력해 주세요."); f.wr_protect.focus(); return false;
		}
		<?=$captcha_js ?? ''?>
		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}
	</script>
</section>
</div>
<!-- } 갤러리 쓰기 -->
