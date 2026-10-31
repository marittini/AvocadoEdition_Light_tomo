<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 방명록은 목록 화면에서 쓴다. 이 화면은 비회원 글 수정(비밀번호 확인 뒤)과 글쓰기 주소로 직접 들어온 경우에만 쓰인다.
include_once __DIR__.'/guestbook.lib.php';
gb_assets($board_skin_url);
$gb_width = (int)($board['bo_table_width'] ?? 0);
?>
<div class="gb gb-write"<?php if ($gb_width > 0) { ?> style="max-width:<?=$gb_width?><?=$gb_width > 100 ? 'px' : '%'?>;"<?php } ?>>
	<section class="gb-paper gb-compose theme-box" aria-label="<?=$w === 'u' ? '방명록 수정' : '방명록 쓰기'?>">
		<?=gb_form(['kind' => 'post', 'w' => $w === 'u' ? 'u' : '', 'wr_id' => (int)$wr_id, 'content' => $w === 'u' ? (string)$write['wr_content'] : '',
			'secret' => $w === 'u' && strpos((string)$write['wr_option'], 'secret') !== false, 'notice' => $w === 'u' && in_array((int)$wr_id, array_map('intval', explode(',', (string)$board['bo_notice'])), true),
			'id' => 'gb-write'])?>
		<?php if ($is_guest) { ?><div class="gb-captcha" id="gb-captcha"><?=$captcha_html?></div><?php } ?>
	</section>
	<div class="gb-bottom"><span></span><a href="<?=gb_url($bo_table, ['page' => (int)$page])?>" class="ui-btn small">목록</a></div>
	<?php if ($gb_emo = gb_emoticons()) { ?>
	<div class="gb-emo-panel" id="gb-emo" hidden><ul><?php foreach ($gb_emo as $code => $url) { ?><li><button type="button" data-gb-emo="<?=gb_e($code)?>" title="<?=gb_e($code)?>"><img src="<?=gb_e($url)?>" alt="<?=gb_e($code)?>" loading="lazy"></button></li><?php } ?></ul></div>
	<?php } ?>
</div>
<?=gb_js($board_skin_url)?>
