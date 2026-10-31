<?php 
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once __DIR__.'/ott.lib.php';
include_once ($board_skin_path.'/setting/user.config.php');
include_once ($board_skin_path.'/color.config.php');
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=20260723_ott_x_embed_v1">', 1);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/txtggu/textggu.css">', 1);
if (!ott_can_read_post($view)) {
    if (!empty($view['wr_protect'])) goto_url($board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$view['wr_id']);
    alert('멤버 공개 글입니다. 로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php');
}

$list[$i] = $view;
$is_view = true;
?>
<div class="board-skin-basic ott-view-page">
<div class="thread_wrap">
    <div class="thread-box theme-box<?php  if ($list[$i]['is_notice']) echo " bo_notice"; ?>">

        <?php  include ($board_skin_path.'/list.skin.thread.php'); ?>

        <!-- 타래 코멘트 리스트 -->
        <?php 
            $thread_co_cnt = sql_fetch("SELECT count(wr_id) AS cnt FROM {$write_table} WHERE wr_parent = '{$list[$i]['wr_id']}' AND wr_is_comment = '1' AND wr_reply =''");
            $thread_co_sql = "SELECT * FROM {$write_table} WHERE wr_parent = '{$list[$i]['wr_id']}' AND wr_is_comment = '1' AND wr_reply ='' ORDER BY wr_comment ASC ";
            $thread_result = sql_query($thread_co_sql);
        ?>
        <?php  if ($thread_co_cnt['cnt'] > 0) { ?>
        <div class="thread_co_wrap">
            <?php  for ($k=0; $row=sql_fetch_array($thread_result); $k++) { ?>
                <div class="thread_co">
                <?php  include ($board_skin_path.'/list.skin.thread.php'); ?>
                </div>
            <?php  } ?>
        </div>
        <?php  } ?>

        <?php  if ($write_href) { ?>
        <div class="thread_bottom">
            <button type="button" class="txt-point" onclick="writeThreadComment(<?=$list[$i]['wr_parent'];?>,'<?=$list[$i]['ca_name'];?>')"><i class="fa-solid fa-comment"></i></button>
        </div>
        <div id="thread_comment<?=$list[$i]['wr_parent'];?>" class="thread_comment"></div>
        <?php  } ?>
    </div>
</div>
<!-- } 게시글 읽기 끝 -->
<!-- 링크 버튼 시작 { -->
<?php 
// 수정, 삭제 링크
$update_href = $delete_href = '';
// 로그인중이고 자신의 글이라면 또는 관리자라면 비밀번호를 묻지 않고 바로 수정, 삭제 가능
if (($member['mb_id'] && ($member['mb_id'] === $view['mb_id'])) || $is_admin) {
    $update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$view['wr_id'].'&amp;page='.$page.$qstr;
    $token = ott_delete_token((int)$view['wr_id']);
    $delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$view['wr_id'].'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
}
else if (!$view['mb_id']) { // 회원이 쓴 글이 아니라면
    $update_href = './password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$view['wr_id'].'&amp;page='.$page.$qstr;
    $delete_href = './password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$view['wr_id'].'&amp;page='.$page.$qstr;
}
?>
<div id="bo_v_bot">
    <?php 
    ob_start();
        ?>
    <?php  if ($prev_href || $next_href) { ?>
    <div class="bo_v_nb">
        <?php  if ($prev_href) { ?><a href="<?php  echo $prev_href ?>" class="ui-btn">이전타래</a><?php  } ?>
        <?php  if ($next_href) { ?><a href="<?php  echo $next_href ?>" class="ui-btn">다음타래</a><?php  } ?>
    </div>
    <?php  } ?>

    <div class="bo_v_com">
        <?php  if ($update_href) { ?><a href="<?php  echo $update_href ?>" class="ui-btn">수정</a><?php  } ?>
        <?php  if ($delete_href) { ?><a href="<?php  echo $delete_href ?>" class="ui-btn admin" onclick="del(this.href); return false;">삭제</a><?php  } ?>
        <a href="<?php  echo $list_href ?>" class="ui-btn">목록</a>
    </div>
    <?php 
    $link_buttons = ob_get_contents();
    ob_end_flush();
        ?>
</div>
<!-- } 링크 버튼 끝 -->
</div>

<?php  if (!empty($_REQUEST['bottom'])) { ?>
<script>
$(function(e){
    $('html, body').scrollTop( $(document).height() );
});
</script>
<?php  } ?>
<?php  include_once($board_skin_path.'/thread.script.php'); // 타래 공통 스크립트 ?>
