<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<script>
// 글자수 제한
var char_min = parseInt(<?php  echo $comment_min ?>); // 최소
var char_max = parseInt(<?php  echo $comment_max ?>); // 최대
</script>

<div class="board-comment-list board-comment-list<?=$list[$i]['wr_id'];?>">
	<?php 
	$cmt_amt = count($comment_list);
	for ($c=0; $c<$cmt_amt; $c++) {
		$comment_id = $comment_list[$c]['wr_id'];
		$cmt_depth = ""; // 댓글단계
		$cmt_depth = strlen($comment_list[$c]['wr_comment_reply']) * 10;
		$comment = $comment_list[$c]['content'];

		$comment = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $comment);
		$cmt_sv = $cmt_amt - $c + 1; // 댓글 헤더 z-index 재설정 ie8 이하 사이드뷰 겹침 문제 해결
		$comment = convertXLinks($comment); // X(트위터) 게시물 자동 임베드
		$comment = markup_content($comment); // 텍스트 서식
        $comment = textggu_change($comment); // 텍스트 꾸미기
		$comment = emote_ev($comment);
	?>
	<div class="item <?=($cmt_depth ? "reply" : "")?>" id="c_<?php  echo $comment_id ?>" <?php  if ($cmt_depth) { ?>style="border-left-width: <?php  echo $cmt_depth ?>px;"<?php  } ?>>
		<div class="co_head_wrap">
			<div class="co-name txt-point">
				· <?=htmlspecialchars($comment_list[$c]['wr_name'], ENT_QUOTES, 'UTF-8'); ?>
			</div>
			<div class="co-info">
				<span><?php  echo date('m.d H:i', strtotime($comment_list[$c]['wr_datetime'])) ?></span>
				<?php  if($comment_list[$c]['is_reply'] || $comment_list[$c]['is_edit'] || $comment_list[$c]['is_del']) {
					$query_string = clean_query_string($_SERVER['QUERY_STRING']);

					if($w == 'cu') {
						$sql = " select wr_id, wr_content, mb_id from $write_table where wr_id = '$c_id' and wr_is_comment = '1' ";
						$cmt = sql_fetch($sql);
						if (!($is_admin || ($member['mb_id'] == $cmt['mb_id'] && $cmt['mb_id'])))
							$cmt['wr_content'] = '';
						$c_wr_content = $cmt['wr_content'];
					}

					$c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
					$c_edit_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
				?>
				<?php  if ($comment_list[$c]['is_edit']) { ?><span><a href="javascript:void(0)" onclick="comment_box('<?php  echo $comment_id ?>', 'ru','<?=$list[$i]['wr_id'];?>'); return false;">수정</a></span><?php  } ?>
				<?php  if ($comment_list[$c]['is_del'])  { ?><span><a href="<?php  echo $comment_list[$c]['del_link'];  ?>" onclick="return comment_delete();">삭제</a></span><?php  } ?>
				<?php  } ?>
			</div>
		</div>
		<div class="co-content">
			<?php  if ($comment_list[$c]['wr_1'] == 'hide') { ?>
				<button type="button" class="ui-btn point" onclick="toggleComment('<?=$comment_id?>');">펼치기</button>
			<?php  } ?>
			<div class="co-inner co-inner<?=$comment_id?>"<?=($comment_list[$c]['wr_1'] == 'hide') ? ' style="display:none;"' : '';?>>
				<?php  if (strstr($comment_list[$c]['wr_option'], "secret")) { ?><span class="secret">[ 비밀글 ]</span><?php  } ?>
				<?php  echo $comment ?>
			</div>
			

			<span id="edit_<?php  echo $comment_id ?>"></span><!-- 수정 -->
			<span id="reply_<?php  echo $comment_id ?>"></span><!-- 답변 -->

			<input type="hidden" value="<?php  echo strstr($comment_list[$c]['wr_option'],"secret") ?>" id="secret_comment_<?php  echo $comment_id ?>_<?=$list[$i]['wr_id'];?>">
			<input type="hidden" value="<?php  echo $comment_list[$c]['wr_1']; ?>" id="wr_1_comment_<?php  echo $comment_id ?>_<?=$list[$i]['wr_id'];?>">
			<textarea id="save_comment_<?php  echo $comment_id ?>_<?=$list[$i]['wr_id'];?>" style="display:none"><?php  echo get_text($comment_list[$c]['content1'], 0) ?></textarea>
		</div>
	</div>
	<?php  } ?>

</div>

<?php  if($c == 0) { ?>
<script>
	$('.board-comment-list<?=$list[$i]['wr_id']?>').remove();
</script>
<?php  } ?>

<?php  if ($is_comment_write) { //@210403
	if($w == '')
		$w = 'r';
?>
<!-- 댓글 쓰기 시작 { -->
<div id="bo_vc_w_<?=$list[$i]['wr_id'];?>" class="board-comment-write bo_vc_w_<?=$list[$i]['wr_id'];?>">
	<form name="fviewcomment" action="<?=$board_skin_url; ?>/write_reply_update.php" onsubmit="return fviewreply_submit(this);" method="post" autocomplete="off">
		<input type="hidden" name="w" value="<?php  echo $w ?>" id="w_<?=$list[$i]['wr_id'];?>">
		<input type="hidden" name="bo_table" value="<?php  echo $bo_table ?>">
		<input type="hidden" name="wr_id" value="<?php  echo $list[$i]['wr_id'] ?>">
		<input type="hidden" name="comment_id" value="<?php  echo $c_id ?>" id="comment_id_<?=$list[$i]['wr_id'];?>">
		<input type="hidden" name="sca" value="<?php  echo $sca ?>">
		<input type="hidden" name="sfl" value="<?php  echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php  echo $stx ?>">
		<input type="hidden" name="spt" value="<?php  echo $spt ?>">
		<input type="hidden" name="page" value="<?php  echo $page ?>">
		<?php  if ($is_view) { ?>
		<input type="hidden" name="is_view" value="true">
		<?php  } ?>
		
		<div class="board-comment-form">
	
			<textarea id="wr_content_<?=$list[$i]['wr_id'];?>" name="wr_content" maxlength="10000" required class="required" title="내용" placeholder="댓글을 입력하세요. X(트위터) 게시물 링크는 자동으로 인용됩니다."
			<?php  if ($comment_min || $comment_max) { ?>onkeyup="check_byte('wr_content', 'char_count');"<?php  } ?>><?php  echo $c_wr_content;  ?></textarea>
			<?php  if ($comment_min || $comment_max) { ?><script> check_byte('wr_content', 'char_count'); </script><?php  } ?>
			<script>
			$(document).on( "keyup change", "textarea#wr_content_<?=$list[$i]['wr_id'];?>[maxlength]", function(){
				var str = $(this).val()
				var mx = parseInt($(this).attr("maxlength"))
				if (str.length > mx) {
					$(this).val(str.substr(0, mx));
					return false;
				}
			});
			</script>
			
			<p>
			<?php if ($is_guest) { //@210403 ?>

            <label for="wr_name_<?=$list[$i]['wr_id'];?>" class="sound_only">이름<strong> 필수</strong></label>			
            <input type="text" name="wr_name" value="<?php echo get_cookie("ck_sns_name"); ?>" id="wr_name_<?=$list[$i]['wr_id'];?>" required class="frm_input required" size="25" placeholder="이름">

            <label for="wr_password_<?=$list[$i]['wr_id'];?>" class="sound_only">비밀번호<strong> 필수</strong></label>
            <input type="password" name="wr_password" id="wr_password_<?=$list[$i]['wr_id'];?>" required class="frm_input required" size="25"  placeholder="비밀번호">&nbsp;&nbsp;

            <?php } ?>

			<input type="checkbox" name="secret" value="secret" id="co_wr_secret_<?=$list[$i]['wr_id'];?>"> <label for="co_wr_secret_<?=$list[$i]['wr_id'];?>">비밀글</label> <?php //@230616 비밀글 처리?>
			&nbsp;&nbsp;
			<input type="checkbox" name="wr_1" value="hide" id="co_wr_1_<?=$list[$i]['wr_id'];?>"> <label for="co_wr_1_<?=$list[$i]['wr_id'];?>">접기</label>
			</p>

			<div class="btn_confirm">
				<?php include_once G5_LIB_PATH.'/emoticon.lib.php'; echo emoticon_button('textarea'); // 이모티콘 고르기 ?>
				<button type="submit" id="btn_submit_<?=$list[$i]['wr_id'];?>" class="ui-btn point">댓글등록</button>
			</div>
		</div>
		
	</form>
</div>

<script>
var save_html<?=$list[$i]['wr_id'];?> = document.getElementById('bo_vc_w_<?=$list[$i]['wr_id'];?>').innerHTML;

</script>
<?php  } ?>
