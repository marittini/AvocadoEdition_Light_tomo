<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
 

$comment_list = array();
$comment_id = 0;
$c_id = (int)($_REQUEST['c_id'] ?? 0);
$c_wr_content = '';

$is_comment_write = false;
if ($member['mb_level'] >= $board['bo_comment_level'])
    $is_comment_write = true;

// 코멘트 출력
$comment_sql = " select * from $write_table where wr_parent = '{$list[$i]['wr_id']}' and wr_reply != '' order by wr_reply ";
$comment_result = sql_query($comment_sql);

for ($c=0; $row=sql_fetch_array($comment_result); $c++)
{
    $comment_list[$c] = $row;
    
    $tmp_name = get_text(cut_str($row['wr_name'], $config['cf_cut_name'])); // 설정된 자리수 만큼만 이름 출력
    if ($board['bo_use_sideview'])
        $comment_list[$c]['name'] = get_sideview($row['mb_id'], $tmp_name, $row['wr_email'], $row['wr_homepage']);
    else
        $comment_list[$c]['name'] = '<span class="'.($row['mb_id']?'member':'guest').'">'.$tmp_name.'</span>';


    $comment_list[$c]['content'] = $comment_list[$c]['content1']= '비밀글 입니다.';
    if (!strstr($row['wr_option'], 'secret') ||
        $is_admin ||
        ($write['mb_id']===$member['mb_id'] && $member['mb_id']) ||
        ($row['mb_id']===$member['mb_id'] && $member['mb_id'])) {
        $comment_list[$c]['content1'] = $row['wr_content'];
        $comment_list[$c]['content'] = conv_content($row['wr_content'], 0, 'wr_content');
        $comment_list[$c]['content'] = search_font($stx, $comment_list[$c]['content']);
    } else {
        $ss_name = 'ss_secret_comment_'.$bo_table.'_'.$comment_list[$c]['wr_id'];

        if(!get_session($ss_name))
            $comment_list[$c]['content'] = '<a href="./password.php?w=sc&amp;bo_table='.$bo_table.'&amp;wr_id='.$comment_list[$c]['wr_id'].$qstr.'" class="s_cmt">댓글내용 확인</a>';
        else {
            $comment_list[$c]['content'] = conv_content($row['wr_content'], 0, 'wr_content');
            $comment_list[$c]['content'] = search_font($stx, $comment_list[$c]['content']);
        }
    }

    $comment_list[$c]['datetime'] = substr($row['wr_datetime'],2,14);

    // 관리자가 아니라면 중간 IP 주소를 감춘후 보여줍니다.
    $comment_list[$c]['ip'] = $row['wr_ip'];
    if (!$is_admin)
        $comment_list[$c]['ip'] = preg_replace("/([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", G5_IP_DISPLAY, $row['wr_ip']);

    $comment_list[$c]['is_reply'] = false;
    $comment_list[$c]['is_edit'] = false;
    $comment_list[$c]['is_del']  = false;
    if ($is_comment_write || $is_admin)
    {
        $token = '';

        if ($member['mb_id'])
        {
            if ($row['mb_id'] === $member['mb_id'] || $is_admin)
            {
                set_session('ss_delete_comment_'.$row['wr_id'].'_token', $token = bin2hex(random_bytes(32)));
                $comment_list[$c]['del_link']  = $board_skin_url.'/delete_comment.php?bo_table='.$bo_table.'&amp;comment_id='.$row['wr_id'].'&amp;token='.$token.'&amp;page='.$page.$qstr;
                if ($is_view) {
                    $comment_list[$c]['del_link']  .= '&amp;is_view=true';
                }
                $comment_list[$c]['is_edit']   = true;
                $comment_list[$c]['is_del']    = true;
            }
        }
        else
        {
            if (!$row['mb_id']) {
                $comment_list[$c]['del_link'] = './password.php?w=x&amp;bo_table='.$bo_table.'&amp;comment_id='.$row['wr_id'].'&amp;page='.$page.$qstr;
                $comment_list[$c]['is_del']   = true;
            }
        }

        if (strlen($row['wr_comment_reply']) < 5)
            $comment_list[$c]['is_reply'] = true;
    }

    // 05.05.22
    // 답변있는 코멘트는 수정, 삭제 불가
    if ($c > 0 && !$is_admin)
    {
        if ($row['wr_comment_reply'])
        {
            $tmp_comment_reply = substr($row['wr_comment_reply'], 0, strlen($row['wr_comment_reply']) - 1);
            if ($tmp_comment_reply == $comment_list[$c-1]['wr_comment_reply'])
            {
                $comment_list[$c-1]['is_edit'] = false;
                $comment_list[$c-1]['is_del'] = false;
            }
        }
    }
        
}

//  코멘트수 제한 설정값
if ($is_admin)
{
    $comment_min = $comment_max = 0;
}
else
{
    $comment_min = (int)$board['bo_comment_min'];
    $comment_max = (int)$board['bo_comment_max'];
}


include($board_skin_path.'/view_comment.skin.php');

if (!$member['mb_id']) // 비회원일 경우에만
    echo '<script src="'.G5_JS_URL.'/md5.js"></script>'."\n";

?>
