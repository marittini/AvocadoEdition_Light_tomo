<?php if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit; ?>
<link rel="stylesheet" href="<?=G5_ADMIN_URL?>/css/tomo-settings.css?v=1">
<section id="tomo-media-settings" class="tomo-settings">
<h2>이미지 및 콘텐츠 보호</h2>
<input type="hidden" name="tomo_settings_present" value="1">
<div class="tbl_frm01 tbl_wrap"><table><colgroup><col style="width:170px"><col></colgroup><tbody>
<tr><th scope="row"><label for="cf_tomo_webp_quality">WebP 품질</label></th><td>
<input type="number" name="cf_tomo_webp_quality" id="cf_tomo_webp_quality" value="<?=tomo_webp_quality()?>" min="0" max="100" step="1" required> / 100
<span class="frm_info">기본 95. 숫자가 높을수록 품질과 용량이 커집니다. 새 첨부·편집기 이미지와 새 WebP 썸네일부터 적용하며 기존 파일을 다시 변환하지 않습니다.</span>
</td></tr>
<tr><th scope="row">콘텐츠 보호</th><td class="tomo-protection-options">
<?php foreach ([1=>['context','우클릭 제한'],2=>['drag','이미지 드래그 제한'],4=>['copy','본문 선택·복사 제한']] as $bit=>$option) { ?>
<label><input type="checkbox" name="tomo_protect_<?=$option[0]?>" value="1" <?=tomo_protection_mask() & $bit ? 'checked' : ''?>> <?=$option[1]?></label>
<?php } ?>
<span class="frm_info">공개 화면에서만 적용하며 입력창·편집기·관리자 화면은 제외합니다. 브라우저 동작을 제한하는 보조 수단이며 스크린샷 등 모든 복제를 막을 수는 없습니다.</span>
</td></tr>
</tbody></table></div>
</section>
