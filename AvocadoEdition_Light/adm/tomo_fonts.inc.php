<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit;
$registered_fonts = tomo_fonts();
$font_rows = $registered_fonts ?: [['id'=>'','label'=>'','family'=>'','css'=>'']];
?>
<link rel="stylesheet" href="<?=G5_ADMIN_URL?>/css/tomo-settings.css?v=1">
<section id="tomo-fonts" class="tomo-settings">
<h2>웹폰트 등록</h2>
<p class="local_desc">표시 이름, font-family, 눈누·Google Fonts 등의 웹폰트 CSS를 등록한 뒤 아래 글꼴 목록에서 선택하세요. 외부 폰트는 저장 전 미리보기에서 차단되므로 저장 후 실제 페이지에서 확인하세요.</p>
<input type="hidden" name="tomo_fonts_present" value="1">
<div id="tomo-font-rows">
<?php foreach ($font_rows as $i=>$font) { ?>
<fieldset class="tomo-font-row">
<legend>웹폰트</legend>
<input type="hidden" name="tomo_fonts[<?=$i?>][id]" data-font-field="id" value="<?=tomo_html($font['id'])?>">
<label>표시 이름 <input type="text" name="tomo_fonts[<?=$i?>][label]" data-font-field="label" value="<?=tomo_html($font['label'])?>" maxlength="80" placeholder="예: 프리텐다드"></label>
<label>font-family <input type="text" name="tomo_fonts[<?=$i?>][family]" data-font-field="family" value="<?=tomo_html($font['family'])?>" maxlength="240" placeholder="예: Pretendard"></label>
<label>웹폰트 CSS <textarea name="tomo_fonts[<?=$i?>][css]" data-font-field="css" rows="4" placeholder="@import 또는 @font-face 코드"><?=tomo_html($font['css'])?></textarea></label>
<button type="button" data-font-remove>등록 삭제</button>
</fieldset>
<?php } ?>
</div>
<button type="button" id="tomo-font-add">웹폰트 추가</button>
<p id="tomo-font-status" role="status" aria-live="polite"></p>
</section>
<script defer src="<?=G5_ADMIN_URL?>/js/tomo-fonts.js?v=1"></script>
