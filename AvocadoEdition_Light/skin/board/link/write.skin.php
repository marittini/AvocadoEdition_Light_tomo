<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/link.lib.php';

$lk_name = trim((string)($write['wr_1'] ?? ''));
if ($lk_name === '' && ($write['wr_subject'] ?? '') !== '링크') $lk_name = (string)($write['wr_subject'] ?? '');
$lk_file = $w === 'u' ? (lk_files($bo_table, [(int)$wr_id])[(int)$wr_id] ?? '') : '';
$lk_tip_bg = lk_color($write['wr_3'] ?? '');
$lk_tip_fg = lk_color($write['wr_4'] ?? '');
$lk_cats = $is_category ? array_values(array_filter(array_map('trim', explode('|', (string)$board['bo_category_list'])), 'strlen')) : [];
$lk_width = (int)($board['bo_table_width'] ?? 0);
echo lk_assets($board_skin_url);
?>

<div class="lk lk-write"<?php if ($lk_width > 0) { ?> style="max-width:<?=$lk_width?><?=$lk_width > 100 ? 'px' : '%'?>;"<?php } ?>>
<section id="bo_w" class="theme-box">
<form name="fwrite" id="fwrite" action="<?=$action_url?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?=get_uniqid()?>">
	<input type="hidden" name="lk_token" value="<?=lk_e(lk_token())?>">
	<input type="hidden" name="w" value="<?=lk_e($w)?>">
	<input type="hidden" name="bo_table" value="<?=lk_e($bo_table)?>">
	<input type="hidden" name="wr_id" value="<?=(int)$wr_id?>">
	<input type="hidden" name="sca" value="<?=lk_e($sca)?>">
	<input type="hidden" name="page" value="<?=(int)$page?>">
	<input type="hidden" name="wr_subject" id="wr_subject" value="<?=lk_e($lk_name !== '' ? $lk_name : '링크')?>">
	<input type="hidden" name="wr_content" value="링크">
	<input type="hidden" name="wr_10" value="<?=(int)($write['wr_10'] ?? 0)?>">
	<?php if ($is_secret == 2 && !$is_admin) { ?><input type="hidden" name="secret" value="secret"><?php } ?>

	<h2 class="lk-title"><?=$w === 'u' ? '배너 수정' : '배너 추가'?></h2>

	<div class="lk-preview" aria-live="polite">
		<div class="lk-preview-box" id="lk-preview" style="<?=$lk_tip_bg ? '--lk-tip-bg:'.$lk_tip_bg.';' : ''?><?=$lk_tip_fg ? '--lk-tip-fg:'.$lk_tip_fg.';' : ''?>">
			<span class="lk-preview-img"><?php $face = $lk_file ?: lk_url($write['wr_link1'] ?? ''); if ($face) { ?><img src="<?=lk_e($face)?>" alt=""><?php } else { ?><span class="lk-text">배너 미리보기</span><?php } ?></span>
			<span class="lk-tip lk-tip-preview" <?=trim((string)($write['wr_2'] ?? '')) === '' ? 'hidden' : ''?>><?=nl2br(lk_e($write['wr_2'] ?? ''), false)?></span>
		</div>
	</div>

	<div class="lk-fields">
		<?php if ($lk_cats) { ?>
		<label class="lk-f"><span>분류</span>
			<select name="ca_name" required>
				<option value="">분류 선택</option>
				<?php foreach ($lk_cats as $c) { ?><option value="<?=lk_e($c)?>"<?=($write['ca_name'] ?? $sca) === $c ? ' selected' : ''?>><?=lk_e($c)?></option><?php } ?>
			</select>
		</label>
		<?php } ?>
		<label class="lk-f"><span>이름</span><input type="text" name="wr_1" id="lk-name" class="frm_input" value="<?=lk_e($lk_name)?>" maxlength="60" required placeholder="사이트 이름"></label>
		<label class="lk-f"><span>주소 <small>눌렀을 때 갈 곳</small></span><input type="text" inputmode="url" name="wr_link2" class="frm_input" value="<?=lk_e($write['wr_link2'] ?? '')?>" maxlength="1000" placeholder="https://"></label>

		<div class="lk-f lk-image">
			<span>배너 그림</span>
			<div class="lk-image-pick">
				<input type="text" inputmode="url" name="wr_link1" id="lk-img-url" class="frm_input" value="<?=lk_e($write['wr_link1'] ?? '')?>" maxlength="1000" placeholder="그림 주소 https://… (다른 사이트 배너)">
				<?php if ($is_file) { ?>
				<span class="lk-or">또는</span>
				<label class="ui-btn small"><input type="file" name="bf_file[]" id="lk-img-file" accept="image/png,image/jpeg,image/gif,image/webp" class="sound_only"><i class="material-icons" aria-hidden="true">upload</i> 그림 올리기</label>
				<?php if ($lk_file) { ?><label class="lk-check"><input type="checkbox" name="bf_file_del[0]" value="1"> 올린 그림 지우기</label><?php } ?>
				<?php } ?>
			</div>
			<small class="lk-help">올린 그림이 있으면 그림 주소보다 먼저 씁니다. 내 배너는 그림을 올려 두면 바꿔도 다른 사이트에 붙인 주소가 그대로입니다.</small>
		</div>

		<label class="lk-f"><span>툴팁 <small>마우스를 올리면 뜨는 말 · 선택</small></span><textarea name="wr_2" id="lk-tip" class="frm_input" rows="2" maxlength="200" placeholder="예) 1차 창작 · 그림 위주"><?=lk_e($write['wr_2'] ?? '')?></textarea></label>
		<div class="lk-f lk-colors">
			<span>툴팁 색 <small>비우면 기본(진한 회색 · 흰 글자)</small></span>
			<div class="lk-color-row">
				<label>바탕 <input type="color" data-lk-color="lk-tip-bg" value="<?=$lk_tip_bg ?: '#444444'?>"<?=$lk_tip_bg ? '' : ' class="is-empty"'?>></label>
				<input type="hidden" name="wr_3" id="lk-tip-bg" value="<?=lk_e($lk_tip_bg)?>">
				<label>글자 <input type="color" data-lk-color="lk-tip-fg" value="<?=$lk_tip_fg ?: '#ffffff'?>"<?=$lk_tip_fg ? '' : ' class="is-empty"'?>></label>
				<input type="hidden" name="wr_4" id="lk-tip-fg" value="<?=lk_e($lk_tip_fg)?>">
				<button type="button" class="ui-btn small etc" data-lk-color-clear>기본색</button>
			</div>
		</div>
		<?php if ($is_admin) { ?>
		<label class="lk-check lk-mine-check"><input type="checkbox" name="wr_5" value="1"<?=(string)($write['wr_5'] ?? '') === '1' ? ' checked' : ''?>> 내 배너 <small>맨 위에 두고, 다른 사이트에 붙일 배너 코드를 보여 줍니다</small></label>
		<?php } else { ?><input type="hidden" name="wr_5" value="<?=lk_e($write['wr_5'] ?? '')?>"><?php } ?>

		<?php if ($is_name || $is_password) { ?>
		<div class="lk-guest">
			<?php if ($is_name) { ?><label class="lk-f"><span>작성자</span><input type="text" name="wr_name" value="<?=$name?>" required class="frm_input required"></label><?php } ?>
			<?php if ($is_password) { ?><label class="lk-f"><span>비밀번호</span><input type="password" name="wr_password" <?=$password_required?> class="frm_input <?=$password_required?>"></label><?php } ?>
		</div>
		<?php } ?>
		<?php if ($is_guest) echo $captcha_html; ?>
		<?php if ($is_secret && ($is_admin || $is_secret == 1)) { ?><label class="lk-check"><input type="checkbox" name="secret" value="secret" <?=$secret_checked?>> 비밀글 <small>관리자 · 나만 보기</small></label><?php } ?>
	</div>

	<div class="lk-actions">
		<a href="<?=G5_BBS_URL?>/board.php?bo_table=<?=urlencode($bo_table)?>" class="ui-btn">취소</a>
		<button type="submit" id="btn_submit" class="ui-btn point"><?=$w === 'u' ? '저장' : '추가'?></button>
	</div>
</form>
</section>
</div>
<script>
function fwrite_submit(f) {
	var name = f.wr_1.value.trim();
	if (!name) { alert("이름을 입력해 주세요."); f.wr_1.focus(); return false; }
	f.wr_subject.value = name;
	<?=$captcha_js ?? ''?>
	document.getElementById("btn_submit").disabled = "disabled";
	return true;
}
</script>
