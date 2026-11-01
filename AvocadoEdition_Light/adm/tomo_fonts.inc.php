<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit;
$registered_fonts = tomo_fonts();
$font_rows = $registered_fonts ?: [['id'=>'','label'=>'','family'=>'','css'=>'']];
?>
<link rel="stylesheet" href="<?=G5_ADMIN_URL?>/css/tomo-settings.css?v=<?=substr((string)@md5_file(G5_ADMIN_PATH.'/css/tomo-settings.css'), 0, 10)?>">
<section id="tomo-fonts" class="tomo-settings">
<h2>웹폰트 등록</h2>
<p class="local_desc">표시 이름, font-family, 눈누·Google Fonts 등의 웹폰트 CSS를 등록한 뒤 아래 글꼴 목록에서 선택하세요. 외부 폰트는 저장 전 미리보기에서 차단되므로 저장 후 실제 페이지에서 확인하세요.<br>등록한 폰트는 한 줄씩 접혀 있습니다. 눌러서 펼쳐 수정하고, ↑ ↓ 버튼으로 순서를 바꾸면 저장 후 글꼴 선택 목록도 그 순서로 나옵니다.</p>
<input type="hidden" name="tomo_fonts_present" value="1">
<?php
// 저장된 웹폰트 CSS를 관리자 화면에도 불러와 목록의 미리보기 글자를 실제 폰트로 보여 준다(관리자가 등록한 값).
$font_preview_css = tomo_font_css('', $registered_fonts);
if (trim($font_preview_css) !== '') echo '<style id="tomo-font-preview-css">'.str_ireplace('</style', '<\/style', $font_preview_css).'</style>';
?>
<div class="tomo-font-tools"<?=count($registered_fonts) ? '' : ' hidden'?>>
<span class="tomo-font-count" id="tomo-font-count"></span>
<label class="tomo-font-filter"><span class="sound_only">등록한 웹폰트 찾기</span><input type="search" id="tomo-font-filter" placeholder="이름 · font-family로 찾기" autocomplete="off"></label>
<button type="button" id="tomo-font-expand">모두 펼치기</button>
</div>
<div id="tomo-font-rows">
<?php foreach ($font_rows as $i=>$font) { $empty = trim((string)$font['label']) === '' && trim((string)$font['family']) === ''; ?>
<details class="tomo-font-item"<?=$empty ? ' open' : ''?>>
<summary>
<span class="tomo-font-sum-label"><?=tomo_html($font['label'] !== '' ? $font['label'] : '새 웹폰트')?></span>
<span class="tomo-font-sum-family"><?=tomo_html($font['family'])?></span>
<span class="tomo-font-sum-sample" aria-hidden="true">가나다 Aa 123</span>
<span class="tomo-font-used" hidden>사용 중</span>
<span class="tomo-font-move"><button type="button" data-font-move="-1" aria-label="위로"><span class="material-icons" aria-hidden="true">arrow_upward</span></button><button type="button" data-font-move="1" aria-label="아래로"><span class="material-icons" aria-hidden="true">arrow_downward</span></button></span>
</summary>
<fieldset class="tomo-font-row">
<legend>웹폰트</legend>
<input type="hidden" name="tomo_fonts[<?=$i?>][id]" data-font-field="id" value="<?=tomo_html($font['id'])?>">
<label>표시 이름 <input type="text" name="tomo_fonts[<?=$i?>][label]" data-font-field="label" value="<?=tomo_html($font['label'])?>" maxlength="80" placeholder="예: 프리텐다드"></label>
<label>font-family <input type="text" name="tomo_fonts[<?=$i?>][family]" data-font-field="family" value="<?=tomo_html($font['family'])?>" maxlength="240" placeholder="예: Pretendard"></label>
<label>웹폰트 CSS <textarea name="tomo_fonts[<?=$i?>][css]" data-font-field="css" rows="4" placeholder="@import 또는 @font-face 코드"><?=tomo_html($font['css'])?></textarea></label>
<button type="button" data-font-remove>등록 삭제</button>
</fieldset>
</details>
<?php } ?>
</div>
<button type="button" id="tomo-font-add">웹폰트 추가</button>
<p id="tomo-font-status" role="status" aria-live="polite"></p>
</section>
<script defer src="<?=G5_ADMIN_URL?>/js/tomo-fonts.js?v=<?=substr((string)@md5_file(G5_ADMIN_PATH.'/js/tomo-fonts.js'), 0, 10)?>"></script>
