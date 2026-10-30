<?php if (!defined('_GNUBOARD_')) exit; ?>
<section id="tomo-palette" class="tomo-settings tomo-palette">
<h2>색상 일괄설정</h2>
<p>팔레트를 고르거나 강조색으로 추천받은 뒤, 배경·본문·버튼·메뉴·게시판 색상에 한 번에 적용하세요.</p>
<div class="tomo-palette-presets" role="group" aria-label="추천 팔레트">
<?php foreach (['rose'=>'로즈','sage'=>'세이지','lavender'=>'라벤더','mono'=>'모노'] as $key=>$label) { ?><button type="button" data-palette-preset="<?=$key?>" aria-pressed="false"><?=$label?></button><?php } ?>
</div>
<div class="tomo-palette-colors">
<?php foreach (['background'=>['페이지 배경','#f8f5f6'],'surface'=>['상자 배경','#ffffff'],'text'=>['본문 글자','#49393c'],'accent'=>['강조색','#96565e'],'border'=>['구분선','#e6d4d8'],'muted'=>['보조 글자','#75646b']] as $key=>[$label,$default]) { ?>
<label for="palette-<?=$key?>"><span><?=$label?></span><span class="tomo-palette-control"><input type="color" id="palette-<?=$key?>" data-palette-color="<?=$key?>" value="<?=$default?>"><input type="text" data-palette-hex="<?=$key?>" value="<?=$default?>" maxlength="7" aria-label="<?=$label?> 색상코드"></span></label>
<?php } ?>
</div>
<div class="tomo-palette-actions"><button type="button" id="palette-recommend">강조색으로 추천</button><button type="button" id="palette-apply">디자인에 일괄 적용</button><button type="button" id="palette-undo" disabled>이번 적용 되돌리기</button></div>
<p id="palette-contrast" class="frm_info"></p><p id="palette-status" role="status">아직 적용하지 않았습니다. 적용 후 상단의 저장을 눌러야 사이트에 반영됩니다.</p>
<p class="frm_info">기존 배경 이미지·글꼴·크기·투명도는 유지합니다. 개별 색상은 다른 디자인 탭에서 다시 조정할 수 있습니다.</p>
</section>
<script defer src="<?=G5_ADMIN_URL?>/js/tomo-palette.js?v=1"></script>
