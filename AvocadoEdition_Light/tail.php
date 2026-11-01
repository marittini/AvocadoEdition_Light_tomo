<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
		</div>
	</div>
</section>

<div id="topCont">
	<a href="#body" title="위로">
		<span class="icons"><span><i class="material-icons">arrow_upward</i></span></span>
	</a>
</div>

<script src="<?php echo G5_JS_URL ?>/swiper.js"></script>
<script src="<?php echo G5_JS_URL ?>/_custom.js"></script>

<?php
// 클릭 효과(원 + 효과음). 끄거나 바꾸려면 tail.click_effect.php 맨 위 설정을 고치세요.
include_once(G5_PATH."/tail.click_effect.php");
include_once(G5_PATH."/tail.sub.php");
?>