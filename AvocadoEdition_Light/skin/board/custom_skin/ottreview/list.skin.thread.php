<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once __DIR__.'/ott.lib.php';
if (!empty($row['wr_is_comment'])) {
    $list[$i] = $row;
}
$list[$i]['is_notice'] = $list[$i]['is_notice'] ?? false;
if (empty($is_view) && !ott_can_read_post($list[$i])) {
    $ott_private_link = !empty($list[$i]['wr_protect'])
        ? $board_skin_url.'/protect.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$list[$i]['wr_id']
        : ($list[$i]['href'] ?? (G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table).'&amp;wr_id='.(int)$list[$i]['wr_id']));
    echo '<div class="thread_main">비공개 게시글입니다. <a href="'.$ott_private_link.'">확인</a></div>';
    return;
}

// 수정, 삭제 링크
$update_href = $delete_href = '';

// 로그인중이고 자신의 글이라면 또는 관리자라면 비밀번호를 묻지 않고 바로 수정, 삭제 가능
if (($member['mb_id'] && ($member['mb_id'] === $list[$i]['mb_id'])) || $is_admin) {
    $update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list[$i]['wr_id'].'&amp;page='.$page.$qstr;
    $token = ott_delete_token((int)$list[$i]['wr_id']);
    $delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$list[$i]['wr_id'].'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);

    if ($list[$i]['wr_is_comment']) {
        $update_href .= '&amp;is_comment=y';
        $comment_token = bin2hex(random_bytes(32));
        set_session('ss_delete_comment_'.(int)$list[$i]['wr_id'].'_token', $comment_token);
        $delete_href = $board_skin_url.'/delete_comment.php?bo_table='.rawurlencode($bo_table).'&amp;comment_id='.(int)$list[$i]['wr_id'].'&amp;token='.$comment_token.'&amp;page='.(int)$page;
    }

    if ($is_view) {
        $update_href .= '&amp;is_view=1';
        $delete_href .= '&amp;is_view=1';
    }
}
else if (!$list[$i]['mb_id']) { // 회원이 쓴 글이 아니라면
    $update_href = './password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list[$i]['wr_id'].'&amp;page='.$page.$qstr;
    $delete_href = './password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$list[$i]['wr_id'].'&amp;page='.$page.$qstr;

    if ($list[$i]['wr_is_comment']) {
        $update_href .= '&amp;is_comment=y';
    }

    if ($is_view) {
        $update_href .= '&amp;is_view=1';
        $delete_href .= '&amp;is_view=1';
    }
}

if (empty($list[$i]['file'])) {
    $list[$i]['file'] = get_file($board['bo_table'], $list[$i]['wr_id']);
}

// 별점 / 감상중 처리
$_thread_rating_raw = trim((string)$list[$i]['wr_6']);
$_thread_rating_text = '';
$_thread_rating_class = '';

if (!$list[$i]['wr_is_comment']) {
    if ($_thread_rating_raw === 'watching') {
        $_thread_rating_text = '감상 중···.';
        $_thread_rating_class = 'is_watching';
    } else {
        $_thread_rating = (int)$_thread_rating_raw;
        if ($_thread_rating >= 1 && $_thread_rating <= 5) {
            $_thread_rating_text = str_repeat('★', $_thread_rating) . str_repeat('☆', 5 - $_thread_rating);
        }
    }
}
?>

<?php  if ($is_view && !$list[$i]['wr_is_comment']) { ?>
<!-- ******** OTT 스타일 히어로 영역 (게시글 상세) ******** -->
<?php $ott_hero_url = ott_safe_image_url($list[$i]['wr_10'] ?? ''); ?>
<div class="ott_hero<?=$ott_hero_url ? '' : ' no_thumb';?>">
    <?php  if ($ott_hero_url) { ?>
        <div class="ott_hero_bg" style="background-image:url('<?=$ott_hero_url;?>');"></div>
    <?php  } ?>
    <div class="ott_hero_scrim"></div>

    <div class="ott_hero_admin">
        <a href="javascript:void(0)" class="ott_icon_btn view_link" data-url="<?=$list[$i]['href'];?>" title="주소 복사"><i class="fa-solid fa-copy"></i></a>
        <input type="text" id="view_copy" style="position:absolute;top:-9999em;">
        <?php  if ($write_href) { ?>
            <?php  if ($update_href) { ?><a href="<?php  echo $update_href ?>" class="ott_icon_btn"><i class="fa-solid fa-pen-to-square"></i></a><?php  } ?>
            <?php  if ($delete_href) { ?><a href="<?php  echo $delete_href ?>" class="ott_icon_btn" onclick="del(this.href); return false;"><i class="fa-solid fa-trash"></i></a><?php  } ?>
        <?php  } ?>
    </div>

    <div class="ott_hero_body">
        <div class="ott_hero_badges">
            <?php  if ($list[$i]['is_notice']) { ?>
                <span class="ott_badge ott_badge_notice"><i class="fa-solid fa-map-pin"></i> 공지</span>
            <?php  } ?>
            <?php  if ($list[$i]['ca_name']) { ?>
                <span class="ott_badge"><?=htmlspecialchars($list[$i]['ca_name'], ENT_QUOTES, 'UTF-8')?></span>
            <?php  } ?>
            <?php  if ($_thread_rating_text) { ?>
                <span class="ott_badge ott_rating <?=$_thread_rating_class?>"><?=$_thread_rating_text?></span>
            <?php  } ?>
        </div>

        <h1 class="ott_hero_title"><?=htmlspecialchars($list[$i]['wr_subject'], ENT_QUOTES, 'UTF-8'); ?></h1>

    </div>
    <div class="ott_hero_scroll_cue"><i class="fa-solid fa-chevron-down"></i></div>
</div>
<!-- ******** OTT 스타일 히어로 영역 끝 ******** -->
<?php  } else { ?>
<?php  if (!$list[$i]['wr_is_comment']) { ?>
<div class="thread_admin">
    <?php  if (!$is_view) { ?>
        <a href="<?=$list[$i]['href'];?>"><i class="fa-solid fa-eye"></i></a>
    <?php  } ?>
    <a href="javascript:void(0)" class="view_link" data-url="<?=$list[$i]['href'];?>" title="주소 복사"><i class="fa-solid fa-copy"></i></a>
    <input type="text" id="view_copy" style="position:absolute;top:-9999em;">
    <?php  if ($write_href) { ?>
        <?php  if ($update_href) { ?><a href="<?php  echo $update_href ?>"><i class="fa-solid fa-pen-to-square"></i></a><?php  } ?>
        <?php  if ($delete_href) { ?><a href="<?php  echo $delete_href ?>" onclick="del(this.href); return false;"><i class="fa-solid fa-trash"></i></a><?php  } ?>
    <?php  } ?>
</div>
<?php  } ?>

<?php  if ($list[$i]['is_notice']) { ?>
    <span class="thread_notice"><i class="fa-solid fa-map-pin"></i> 공지</span>
<?php  } ?>


<?php  if (!$list[$i]['is_notice']) { ?>
<div class="thread_info">
    <?php  if ($list[$i]['wr_is_comment'] && $write_href && ($update_href || $delete_href)) { ?>
    <span class="thread_admin thread_admin_inline">
        <?php  if ($update_href) { ?><a href="<?php  echo $update_href ?>" title="수정"><i class="fa-solid fa-pen-to-square"></i><span>수정</span></a><?php  } ?>
        <?php  if ($delete_href) { ?><a href="<?php  echo $delete_href ?>" title="삭제" onclick="del(this.href); return false;"><i class="fa-solid fa-trash"></i><span>삭제</span></a><?php  } ?>
    </span>
    <?php  } ?>

</div>
<?php  } ?>

<?php  if($list[$i]['wr_subject']) { ?>
    <div class="thread_title">
        <strong class="thread_title_text"><?=htmlspecialchars($list[$i]['wr_subject'], ENT_QUOTES, 'UTF-8'); ?></strong>

        <?php  if ($list[$i]['ca_name'] && !$list[$i]['wr_is_comment']) { ?>
            <span class="thread_cate inline"><?=htmlspecialchars($list[$i]['ca_name'], ENT_QUOTES, 'UTF-8')?></span>
        <?php  } ?>

        <?php  if ($_thread_rating_text) { ?>
            <span class="list_rating inline <?=$_thread_rating_class?>"><?=$_thread_rating_text?></span>
        <?php  } ?>
    </div>
<?php  } elseif ($list[$i]['ca_name'] || $_thread_rating_text) { ?>
    <div class="thread_title">
        <?php  if ($list[$i]['ca_name'] && !$list[$i]['wr_is_comment']) { ?>
            <span class="thread_cate inline"><?=htmlspecialchars($list[$i]['ca_name'], ENT_QUOTES, 'UTF-8')?></span>
        <?php  } ?>
        <?php  if ($_thread_rating_text) { ?>
            <span class="list_rating inline <?=$_thread_rating_class?>"><?=$_thread_rating_text?></span>
        <?php  } ?>
    </div>
<?php  } ?>
<?php  } ?>

<div id="thread<?=$list[$i]['wr_id']; ?>" class="thread_wr">
<?php  if (!empty($list[$i]['wr_protect']) && !ott_can_read_post($list[$i])) { ?>
    <div class="thread_main">
        보호글입니다.
        <div class="pass-form">
            <a class="ui-btn" href="<?=$board_skin_url?>/protect.php?bo_table=<?=rawurlencode($bo_table)?>&amp;wr_id=<?=(int)$list[$i]['wr_id']?>">비밀번호 입력</a>
        </div>
    </div>
<?php  } elseif (!ott_can_read_post($list[$i])) { ?>
    <div class="thread_main">
        비공개 게시글입니다. <a href="<?=$list[$i]['href']?>">확인</a>
    </div>
<?php  } elseif (($member['mb_level'] < $board['bo_read_level'])) { ?>
    <div class="thread_main">
        글읽기 권한이 없습니다.
    </div>
<?php  } else { ?>
    <?php  include($board_skin_path.'/list.skin.con.php'); // 타래 본문 ?>
<?php  } ?>
</div><!-- #thread -->
