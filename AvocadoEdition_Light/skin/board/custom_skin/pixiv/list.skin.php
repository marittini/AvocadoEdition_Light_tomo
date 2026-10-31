<?php
if (!defined('_GNUBOARD_')) exit;
// Keep the work detail separate even when the board enables a list below posts.
if (!empty($wr_id)) return;
include_once __DIR__.'/pixiv.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=5">',0);
$types=['illustration'=>'일러스트','manga'=>'만화','novel'=>'소설'];
$type=$_GET['px_type'] ?? ''; if(!is_string($type)||!isset($types[$type])) $type='';
$raw=$_GET['tag'] ?? []; if (!is_array($raw)) $raw=[$raw];
$tags=[];foreach(array_slice($raw,0,20) as $t) if(is_string($t)&&preg_match('/^[\p{L}\p{N}_-]{1,30}$/u',$t) && !in_array($t,$tags,true)) $tags[]=$t;
$where='wr_is_comment=0';
if($type!=='') $where.=" AND wr_1='".sql_real_escape_string('pixiv:'.$type)."'";
foreach($tags as $tag) $where.=" AND INSTR(wr_2,'|".sql_real_escape_string($tag)."|')>0";
// Do not reveal a secret post's tags through matching results.
if ($tags) $where .= " AND wr_1 IN ('pixiv:illustration','pixiv:manga','pixiv:novel')";
if ($tags && !$is_admin) {
    $where .= " AND (wr_option NOT LIKE '%secret%'";
    if (!empty($member['mb_id'])) $where .= " OR mb_id='".sql_real_escape_string($member['mb_id'])."'";
    $where .= ")";
}

$total=sql_fetch("SELECT COUNT(*) AS n FROM `{$write_table}` WHERE $where");
$rowsPerPage=max(1,min(60,(int)$board['bo_page_rows'] ?: 20));
$cur=max(1,(int)($_GET['page']??1)); $pages=max(1,(int)ceil($total['n']/$rowsPerPage));$cur=min($cur,$pages);$offset=($cur-1)*$rowsPerPage;
$result=sql_query("SELECT * FROM `{$write_table}` WHERE $where ORDER BY wr_num,wr_reply LIMIT $offset,$rowsPerPage");
$base=G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);
$build=static function($newType,$newTags,$newPage=1) use($base){return $base.'&amp;'.http_build_query(['px_type'=>$newType,'tag'=>$newTags,'page'=>$newPage],'','&amp;');};
// Exclude secret posts when suggesting tags: never expose their metadata.
$tagRows=sql_query("SELECT wr_2 FROM `{$write_table}` WHERE wr_is_comment=0 AND wr_1 IN ('pixiv:illustration','pixiv:manga','pixiv:novel') AND wr_option NOT LIKE '%secret%' AND wr_2<>'' ORDER BY wr_num");
$available=[];while($tr=sql_fetch_array($tagRows))foreach(pixiv_tags($tr['wr_2']) as $t)$available[$t]=true;
?>
<div class="px-board">
<?php if (!empty($board['bo_content_head'])) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>
<nav class="px-categories" aria-label="작품 분류"><a class="ui-btn<?=!$type?' point':''?>" href="<?=$build('',$tags)?>" <?=!$type?'aria-current="page"':''?>>전체</a><?php foreach($types as $id=>$label){?><a class="ui-btn<?=$type===$id?' point':''?>" href="<?=$build($id,$tags)?>" <?=$type===$id?'aria-current="page"':''?>><?=$label?></a><?php } ?></nav>
<?php if($available || $tags){?><nav class="px-tags" aria-label="장르 태그"><?php foreach(array_unique(array_merge($tags,array_keys($available))) as $tag){$active=in_array($tag,$tags,true);$next=$active?array_values(array_diff($tags,[$tag])):array_merge($tags,[$tag]);?><a class="ui-btn<?=$active?' point':''?>" href="<?=$build($type,$next)?>" <?=$active?'aria-current="true"':''?>>#<?=pixiv_escape($tag)?><?=$active?' ×':''?></a><?php } ?></nav><?php } ?>
<div class="px-grid">
<?php while($row=sql_fetch_array($result)){ $secret=str_contains($row['wr_option'],'secret'); $files=$secret?[]:pixiv_files($bo_table,(int)$row['wr_id'],$row['wr_3']); $cover=pixiv_cover_file($files,$row['wr_3']); $href=$base.'&amp;wr_id='.(int)$row['wr_id']; ?>
<a class="px-item" href="<?=$href?>"><span class="px-thumb"><?php if($files){?><img src="<?=pixiv_cover_url($bo_table,$cover)?>" alt="" loading="lazy"><?php if(count($files)>1){?><span class="px-count"><?=count($files)?>장</span><?php }} else { ?><span class="px-placeholder"><?php if(!$secret && $row['wr_1']==='pixiv:novel'){?><span class="px-novel-preview"><?=pixiv_escape(cut_str(trim(strip_tags($row['wr_content'])),120,'…'))?></span><?php }else{?><?=$secret?'비밀글':'이미지 없음'?><?php } ?></span><?php } ?></span></a>
<?php } ?>
</div>
<?php if(!(int)$total['n']){?><p class="px-empty">작품이 없습니다.</p><?php } ?>
<nav class="px-pages" aria-label="목록 페이지"><?php for($p=max(1,$cur-2);$p<=min($pages,$cur+2);$p++){?><a href="<?=$build($type,$tags,$p)?>" <?=$p===$cur?'aria-current="page"':''?>><?=$p?></a><?php } ?></nav>
<?php if($write_href){?><div class="btn_confirm"><a href="<?=$write_href?>" class="ui-btn point">글쓰기</a></div><?php } ?>
</div>
