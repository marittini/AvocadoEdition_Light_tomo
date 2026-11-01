<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// 댓글: 그누보드 기본 view_comment.php가 준비한 $list를 그대로 쓴다. 답글(대댓글) 없이 한 줄로 쌓는다.
$gl_c_action = https_url(G5_BBS_DIR).'/write_comment_update.php';
$gl_c_form = static function (string $w, int $comment_id = 0, string $content = '') use ($bo_table, $wr_id, $page, $is_member, $gl_c_action, $captcha_html): string {
	$id = $w === 'cu' ? 'gl-cedit-'.$comment_id : 'gl-cform';
	$h = '<form class="gl-c-form" method="post" action="'.$gl_c_action.'" autocomplete="off" data-gl-cform>'
		.'<input type="hidden" name="w" value="'.$w.'"><input type="hidden" name="bo_table" value="'.gl_e($bo_table).'">'
		.'<input type="hidden" name="wr_id" value="'.(int)$wr_id.'"><input type="hidden" name="comment_id" value="'.$comment_id.'">'
		.'<input type="hidden" name="page" value="'.(int)$page.'"><input type="hidden" name="gl_token" value="'.gl_e(gl_token()).'">';
	if ($is_member) $h .= '<input type="hidden" name="wr_name" value="">';
	$h .= '<label class="sound_only" for="'.$id.'">댓글</label><textarea id="'.$id.'" name="wr_content" class="frm_input" rows="1" required maxlength="10000" placeholder="댓글" data-gl-grow>'.gl_e($content).'</textarea>'
		.'<div class="gl-c-bar"><div class="gl-c-opts">';
	if ($w === 'c' && !$is_member) {
		$h .= '<input type="text" name="wr_name" class="frm_input" maxlength="20" required placeholder="이름" aria-label="이름">'
			.'<input type="password" name="wr_password" class="frm_input" maxlength="20" required placeholder="비밀번호" aria-label="비밀번호">';
	}
	$h .= '</div><div class="gl-c-btns">';
	if ($w === 'cu') $h .= '<button type="button" class="ui-btn small" data-gl-cancel>취소</button>';
	$h .= '<button type="submit" class="ui-btn small point">'.($w === 'cu' ? '저장' : '남기기').'</button></div></div>';
	if ($w === 'c' && !$is_member && $captcha_html) $h .= '<div class="gl-c-captcha">'.$captcha_html.'</div>';
	return $h.'</form>';
};
?>
<section class="gl-comments" aria-label="댓글">
	<?php if ($list) { ?>
	<h2 class="gl-c-title">댓글 <b><?=count($list)?></b></h2>
	<ol class="gl-c-list">
	<?php foreach ($list as $c) { $cid = (int)$c['wr_id']; ?>
		<li class="gl-comment" id="c_<?=$cid?>">
			<div class="gl-c-head">
				<b><?=gl_e(get_text($c['wr_name']))?></b>
				<time datetime="<?=gl_e(date('c', strtotime($c['wr_datetime'])))?>"><?=date('y.m.d H:i', strtotime($c['wr_datetime']))?></time>
				<?php if ($c['is_edit'] || $c['is_del']) { ?>
				<span class="gl-c-tools">
					<?php if ($c['is_edit']) { ?><button type="button" class="ui-btn small etc" data-gl-edit>수정</button><?php } ?>
					<?php if ($c['is_del']) { ?><a href="<?=$c['del_link']?>" class="ui-btn small" data-gl-confirm="이 댓글을 삭제할까요?">삭제</a><?php } ?>
				</span>
				<?php } ?>
			</div>
			<div class="gl-c-body" data-gl-cview><?=$c['content']?></div>
			<?php if ($c['is_edit']) { ?><div class="gl-c-editor" data-gl-ceditor hidden><?=$gl_c_form('cu', $cid, (string)$c['content1'])?></div><?php } ?>
		</li>
	<?php } ?>
	</ol>
	<?php } ?>
	<?php if ($is_comment_write) { ?>
	<?=$gl_c_form('c')?>
	<?php } ?>
</section>
