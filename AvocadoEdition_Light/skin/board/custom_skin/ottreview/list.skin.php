<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once __DIR__.'/ott.lib.php';
$is_view = false;
include_once ($board_skin_path.'/setting/user.config.php');
include_once ($board_skin_path.'/color.config.php');
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 1);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/txtggu/textggu.css">', 1);

?>
<div <?php if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?php }?>>

<?php  if($board['bo_content_head']) { ?>
	<div class="board-notice">
		<?=stripslashes($board['bo_content_head']);?>
	</div>
<?php  } ?>

<div class="board-skin-basic">

<?php  if ($write_href) { ?>
    <div class="thread_btn">
        <a href="<?=$write_href?>" class="ui-btn point" title="새글 작성" aria-label="새글 작성">
            <i class="fa-solid fa-feather"></i>
        </a>
    </div>
<?php  } ?>

<?php  if ($_list_style == 'list') { ?>
    <form name="fboardlist" id="fboardlist" action="./board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">
	<?php //@201023 게시판 글 다중체크용 폼 필드 추가?>
<?php  } ?>
    
	<div class="thread_wrap type_<?=$_list_style; ?>">
		<?php  for ($i=0; $i<count($list); $i++) { $list[$i]['file'] = get_file($bo_table, $list[$i]['wr_id']); ?>

    <?php  if ($_list_style == 'thread') { // 타래형 ?>
		<div class="thread-box theme-box<?php  if ($list[$i]['is_notice']) echo " bo_notice"; ?>">
            <?php  include ($board_skin_path.'/list.skin.thread.php'); ?>
            <?php if (ott_can_read_post($list[$i])) { ?>
            <!-- 타래 코멘트 리스트 -->
            <?php 
                $thread_co_cnt = sql_fetch("SELECT count(wr_id) AS cnt FROM {$write_table} WHERE wr_parent = '{$list[$i]['wr_id']}' AND wr_is_comment = '1' AND wr_reply =''");
                $thread_co_sql = "SELECT * FROM (SELECT * FROM {$write_table} WHERE wr_parent = '{$list[$i]['wr_id']}' AND wr_is_comment = '1' AND wr_reply ='' ORDER BY wr_comment DESC limit 2) AS sub ORDER BY wr_comment ASC";
                $thread_result = sql_query($thread_co_sql);
            ?>
            <?php  if ($thread_co_cnt['cnt'] > 0) { ?>
            <div class="thread_co_wrap">
                <?php  if ($thread_co_cnt['cnt'] > 2) { ?>
                    <a href="<?=$list[$i]['href']?>">전체 보기</a>
                <?php  } ?>
                <?php  for ($k=0; $row=sql_fetch_array($thread_result); $k++) { ?>
                    <div class="thread_co">
                    <?php  include ($board_skin_path.'/list.skin.thread.php'); ?>
                    </div>
                <?php  } ?>
            </div>
            <?php  } ?>
            <?php  if ($write_href) { ?>
            <div class="thread_bottom">
                <button type="button" class="txt-point" onclick="writeThreadComment(<?=$list[$i]['wr_parent'];?>,'<?=$list[$i]['ca_name']?>')"><i class="fa-solid fa-comment"></i></button>
            </div>
            <div id="thread_comment<?=$list[$i]['wr_parent'];?>" class="thread_comment"></div>
            <?php  } ?>
			<?php } ?>
		</div>

    <?php  } elseif ($_list_style == 'list') { // 목록형

        $_ott_visible = ott_can_read_post($list[$i]);
        $_file_src = '';
        foreach ($list[$i]['file'] as $_slot => $_entry) {
            if (is_int($_slot) && !empty($_entry['file'])) { $_file_src = ott_file_url($_entry, $bo_table); break; }
        }
        if (preg_match('/^ottcover:(\d+)$/', $list[$i]['wr_9'] ?? '', $_cover_match) && !empty($list[$i]['file'][(int)$_cover_match[1]]['file'])) $_file_src = ott_file_url($list[$i]['file'][(int)$_cover_match[1]], $bo_table);
        $_img_src = $list[$i]['wr_2'] ? $list[$i]['wr_2'] : $_file_src;
        $_img_src = $list[$i]['wr_10'] ? $list[$i]['wr_10'] : $_img_src;
        if (!empty($_cover_match) && !empty($list[$i]['file'][(int)$_cover_match[1]]['file'])) $_img_src = $_file_src;
        if (!$_ott_visible) $_img_src = '';
        $_img_src = ott_safe_image_url(html_entity_decode($_img_src, ENT_QUOTES, 'UTF-8'));

$_rating_raw = $list[$i]['wr_6'];
$_rating_text = '';
$_rating_class = '';

if ($_rating_raw === 'watching') {
    $_rating_text = '감상 중···.';
    $_rating_class = 'is_watching';
} else {
    $_rating = (int)$_rating_raw;
    if ($_rating >= 1 && $_rating <= 5) {
        $_rating_text = str_repeat('★', $_rating) . str_repeat('☆', 5 - $_rating);
    }
}
    ?>
        <div class="list-box">
            <?php if ($is_checkbox) { ?>
            <span class="td_chk">
                <label for="chk_wr_id_<?php echo $i ?>" class="sound_only"><?php echo $list[$i]['subject'] ?></label>
                <input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>">
            </span>
            <?php } //@201023?>

            <a href="<?=$list[$i]['href']?>">
                <div class="list_thumb<?=(!$_img_src) ? ' no_img' : ''; ?>">
                    <?php  if ($_img_src) { ?>
                        <img src="<?=$_img_src; ?>" alt="">
                    <?php  } else { ?>
                        <i class="fa-solid fa-list"></i>
                    <?php  } ?>
                </div>

 <?php  if ($list[$i]['wr_subject'] || $_rating_text) { ?>
    <div class="list_title">
        <?php  if ($list[$i]['wr_subject']) { ?>
            <span><?=htmlspecialchars($_ott_visible ? $list[$i]['wr_subject'] : '비공개 게시글', ENT_QUOTES, 'UTF-8'); ?></span>
        <?php  } ?>
        <?php  if ($_rating_text) { ?>
<div class="list_rating <?=$_rating_class?>"><?=$_rating_text?></div>
        <?php  } ?>
    </div>
<?php  } ?>
            </a>
        </div>
    <?php  } ?>		
		<?php  } ?>

		<?php  if (count($list) == 0) { echo '<div class="no-data ">게시물이 없습니다.</div>'; } ?>
	</div>
	
	<?php  if ($list_href || $is_checkbox || $write_href) { ?>
	<div class="bo_fx txt-right">
		<?php  if ($list_href || $write_href) { ?>
            <?php if ($is_checkbox && $_list_style == 'list') { ?> 
            <p class="chk_all">
                <label for="chkall" class="sound_only">현재 페이지 게시물 전체</label>
                <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
            </p> 
		    <button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value" class="ui-btn admin">선택삭제</button>
            <?php } ?>
		<?php  if ($list_href) { ?><a href="<?php  echo $list_href ?>" class="ui-btn">목록</a><?php  } ?>		
		<?php  } ?>
		<?php  if($admin_href){?><a href="<?=$admin_href?>" class="ui-btn admin" target="_blank">관리자</a><?php }?>
	</div>
	<?php  } ?>

    <?php  if ($_list_style == 'list') { ?>
    </form>
    <script>
    function all_checked(sw) {
        var f = document.fboardlist;

        for (var i=0; i<f.length; i++) {
            if (f.elements[i].name == "chk_wr_id[]")
                f.elements[i].checked = sw;
        }
    }

    function fboardlist_submit(f) {
        var chk_count = 0;

        for (var i=0; i<f.length; i++) {
            if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
                chk_count++;
        }

        if (!chk_count) {
            alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
            return false;
        }

        if(document.pressed == "선택삭제") {
            if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
                return false;

            f.removeAttribute("target");
            f.action = "./board_list_update.php";
        }
        return true;
    }
    </script>
    <?php  } ?>

	<!-- 페이지 -->
	<?php  echo $write_pages;  ?>

	<!-- 게시판 검색 시작 { -->
	<fieldset id="bo_sch" class="txt-center">
		<legend>게시물 검색</legend>

		<form name="fsearch" method="get">
		<input type="hidden" name="bo_table" value="<?php  echo $bo_table ?>">
		<input type="hidden" name="sca" value="<?php  echo $sca ?>">
		<input type="hidden" name="sop" value="and">
		<select name="sfl" id="sfl">
			<option value="wr_subject"<?php  echo get_selected($sfl, 'wr_subject', true); ?>>제목</option>
			<option value="wr_content"<?php  echo get_selected($sfl, 'wr_content'); ?>>내용</option>
			<option value="wr_subject||wr_content"<?php  echo get_selected($sfl, 'wr_subject||wr_content'); ?>>제목+내용</option>
		</select>
		<input type="text" name="stx" value="<?php  echo stripslashes($stx) ?>" required id="stx" class="frm_input required" size="15" maxlength="20">
		<button type="submit" class="ui-btn point ico search default"><i class="fas fa-search"></i></button>
		</form>
	</fieldset>
	<!-- } 게시판 검색 끝 -->
</div>
</div>

<?php  include_once($board_skin_path.'/thread.script.php'); // 타래 공통 스크립트 ?>
<!-- } 게시판 목록 끝 -->
