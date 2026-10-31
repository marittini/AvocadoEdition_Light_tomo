<?php
if (!defined('_GNUBOARD_')) exit;
// Keep the work detail separate even when the board enables a list below posts.
if (!empty($wr_id)) return;
include_once __DIR__.'/pixiv.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.pixiv_asset_url($board_skin_url, 'style.css').'">',0);

// A single series page (and its editor) has its own template.
if (isset($_GET['px_series']) && is_string($_GET['px_series']) && ctype_digit($_GET['px_series'])) {
    include __DIR__.'/series.skin.php';
    return;
}

$types=['illustration'=>'일러스트','manga'=>'만화','novel'=>'소설'];
$type=$_GET['px_type'] ?? ''; if(!is_string($type)||(!isset($types[$type]) && $type!=='series')) $type='';
$px_tables = pixiv_series_tables();
$raw=$_GET['tag'] ?? []; if (!is_array($raw)) $raw=[$raw];
$tags=[];foreach(array_slice($raw,0,20) as $t) if(is_string($t)&&preg_match('/^[\p{L}\p{N}_-]{1,30}$/u',$t) && !in_array($t,$tags,true)) $tags[]=$t;
if ($type==='series') $tags=[];
$where='w.wr_is_comment=0';
if($type!=='' && $type!=='series') $where.=" AND w.wr_1='".sql_real_escape_string('pixiv:'.$type)."'";
foreach($tags as $tag) $where.=" AND INSTR(w.wr_2,'|".sql_real_escape_string($tag)."|')>0";
// Do not reveal a secret post's tags through matching results.
if ($tags) $where .= " AND w.wr_1 IN ('pixiv:illustration','pixiv:manga','pixiv:novel') AND ".pixiv_visible_sql('w');

$rowsPerPage=max(1,min(60,(int)$board['bo_page_rows'] ?: 20));
$cur=max(1,(int)($_GET['page']??1));
$base=G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);
$build=static function($newType,$newTags,$newPage=1) use($base){return $base.'&amp;'.http_build_query(['px_type'=>$newType,'tag'=>$newTags,'page'=>$newPage],'','&amp;');};

if ($type==='series') {
    // Series index: series that have at least one readable work. Owners and admins also see empty ones.
    $sqlBo = sql_real_escape_string($bo_table);
    $own = $is_admin ? '1' : (!empty($member['mb_id']) ? "ps.mb_id='".sql_real_escape_string($member['mb_id'])."'" : '0');
    $seriesSql = $px_tables ? "FROM `{$px_tables['series']}` ps
        LEFT JOIN `{$px_tables['works']}` sw ON sw.ps_id=ps.ps_id AND sw.bo_table=ps.bo_table
        LEFT JOIN `{$write_table}` w ON w.wr_id=sw.wr_id AND w.wr_is_comment=0 AND ".pixiv_visible_sql('w')."
        WHERE ps.bo_table='{$sqlBo}' GROUP BY ps.ps_id HAVING COUNT(w.wr_id)>0 OR {$own}" : '';
    $total = $px_tables ? sql_fetch("SELECT COUNT(*) AS n FROM (SELECT ps.ps_id {$seriesSql}) x") : ['n'=>0];
} else {
    $total=sql_fetch("SELECT COUNT(*) AS n FROM `{$write_table}` w WHERE $where");
}
$pages=max(1,(int)ceil($total['n']/$rowsPerPage));$cur=min($cur,$pages);$offset=($cur-1)*$rowsPerPage;
if ($type==='series') {
    $result = $px_tables ? sql_query("SELECT ps.*, COUNT(w.wr_id) AS px_count {$seriesSql} ORDER BY ps.ps_update DESC LIMIT $offset,$rowsPerPage") : false;
} elseif ($px_tables) {
    $result=sql_query("SELECT w.*, ps.ps_id AS px_series_id, ps.ps_title AS px_series_title FROM `{$write_table}` w
        LEFT JOIN `{$px_tables['works']}` sw ON sw.bo_table='".sql_real_escape_string($bo_table)."' AND sw.wr_id=w.wr_id
        LEFT JOIN `{$px_tables['series']}` ps ON ps.ps_id=sw.ps_id
        WHERE $where ORDER BY w.wr_num,w.wr_reply LIMIT $offset,$rowsPerPage");
} else {
    $result=sql_query("SELECT w.* FROM `{$write_table}` w WHERE $where ORDER BY w.wr_num,w.wr_reply LIMIT $offset,$rowsPerPage");
}
// Exclude secret posts when suggesting tags: never expose their metadata.
$available=[];
if ($type!=='series') {
    $tagRows=sql_query("SELECT wr_2 FROM `{$write_table}` WHERE wr_is_comment=0 AND wr_1 IN ('pixiv:illustration','pixiv:manga','pixiv:novel') AND wr_option NOT LIKE '%secret%' AND wr_2<>'' ORDER BY wr_num");
    while($tr=sql_fetch_array($tagRows))foreach(pixiv_tags($tr['wr_2']) as $t)$available[$t]=true;
}
$tabs = $types + ($px_tables ? ['series'=>'시리즈'] : []);
?>
<div class="px-board">
<?php if (!empty($board['bo_content_head'])) { ?><div class="board-notice-box"><?=stripslashes($board['bo_content_head'])?></div><?php } ?>
<nav class="px-categories" aria-label="작품 분류"><a class="ui-btn<?=!$type?' point':''?>" href="<?=$build('',$tags)?>" <?=!$type?'aria-current="page"':''?>>전체</a><?php foreach($tabs as $id=>$label){?><a class="ui-btn<?=$type===$id?' point':''?>" href="<?=$build($id,$id==='series'?[]:$tags)?>" <?=$type===$id?'aria-current="page"':''?>><?=$label?></a><?php } ?></nav>
<?php if($available || $tags){?><nav class="px-tags" aria-label="장르 태그"><?php foreach(array_unique(array_merge($tags,array_keys($available))) as $tag){$active=in_array($tag,$tags,true);$next=$active?array_values(array_diff($tags,[$tag])):array_merge($tags,[$tag]);?><a class="ui-btn<?=$active?' point':''?>" href="<?=$build($type,$next)?>" <?=$active?'aria-current="true"':''?>>#<?=pixiv_escape($tag)?><?=$active?' ×':''?></a><?php } ?></nav><?php } ?>
<div class="px-grid">
<?php if ($type==='series') { while($result && $row=sql_fetch_array($result)){
    $works = pixiv_series_works($bo_table, $write_table, (int)$row['ps_id']);
    $coverUrl = pixiv_series_cover($bo_table, $works); ?>
<a class="px-item px-series-card" href="<?=pixiv_series_url($bo_table,(int)$row['ps_id'])?>"><span class="px-thumb"><?php if($coverUrl){?><img src="<?=$coverUrl?>" alt="" loading="lazy"><?php } else { ?><span class="px-placeholder"><span class="px-novel-preview"><?=pixiv_escape($row['ps_title'])?></span></span><?php } ?><span class="px-count px-count-series"><?=(int)$row['px_count']?>화</span></span><span class="px-item-title"><?=pixiv_escape($row['ps_title'])?></span><span class="px-item-meta"><span class="px-kind"><?=pixiv_series_kind_label($row['ps_kind'])?></span><time datetime="<?=pixiv_escape(str_replace(' ','T',$row['ps_update']))?>"><?=pixiv_escape(substr($row['ps_update'],0,10))?> 업데이트</time></span></a>
<?php } } else { while($row=sql_fetch_array($result)){ $secret=str_contains($row['wr_option'],'secret'); $files=$secret?[]:pixiv_files($bo_table,(int)$row['wr_id'],$row['wr_3']); $cover=pixiv_cover_file($files,$row['wr_3']); $href=$base.'&amp;wr_id='.(int)$row['wr_id']; ?>
<div class="px-item"><a class="px-thumb" href="<?=$href?>" tabindex="-1" aria-hidden="true"><?php if($files){?><img src="<?=pixiv_cover_url($bo_table,$cover)?>" alt="" loading="lazy"><?php if(count($files)>1){?><span class="px-count"><?=count($files)?>장</span><?php }} else { ?><span class="px-placeholder"><?php if(!$secret && $row['wr_1']==='pixiv:novel'){?><span class="px-novel-preview"><?=pixiv_escape(cut_str(trim(strip_tags($row['wr_content'])),120,'…'))?></span><?php }else{?><?=$secret?'비밀글':'이미지 없음'?><?php } ?></span><?php } ?><?php if($row['wr_1']==='pixiv:novel'){?><span class="px-kind-badge">소설</span><?php } ?></a>
<a class="px-item-title" href="<?=$href?>"><?php if($secret){?><span class="px-lock" aria-label="비밀글">🔒</span><?php } ?><?=pixiv_escape($row['wr_subject'])?></a>
<span class="px-item-meta"><?php if(!empty($row['px_series_id'])){?><a class="px-series-chip" href="<?=pixiv_series_url($bo_table,(int)$row['px_series_id'])?>"><?=pixiv_escape($row['px_series_title'])?></a><?php } ?><time class="px-upload-date" datetime="<?=pixiv_escape(str_replace(' ','T',$row['wr_datetime']))?>"><?=pixiv_escape(substr($row['wr_datetime'],0,10))?></time></span></div>
<?php } } ?>
</div>
<?php if(!(int)$total['n']){?><p class="px-empty"><?=$type==='series'?'시리즈가 없습니다. 글쓰기에서 작품을 새 시리즈에 넣으면 여기에 표시됩니다.':'작품이 없습니다.'?></p><?php } ?>
<?php if($pages>1){?><nav class="px-pages" aria-label="목록 페이지"><?php if($cur>1){?><a href="<?=$build($type,$tags,$cur-1)?>" aria-label="이전 페이지">‹</a><?php } for($p=max(1,$cur-2);$p<=min($pages,$cur+2);$p++){?><a href="<?=$build($type,$tags,$p)?>" <?=$p===$cur?'aria-current="page"':''?>><?=$p?></a><?php } if($cur<$pages){?><a href="<?=$build($type,$tags,$cur+1)?>" aria-label="다음 페이지">›</a><?php } ?></nav><?php } ?>
<?php if($write_href){?><div class="btn_confirm"><a href="<?=$write_href?>" class="ui-btn point">글쓰기</a></div><?php } ?>
</div>
