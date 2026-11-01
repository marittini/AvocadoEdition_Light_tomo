<?php 
include_once __DIR__.'/_common.php';
include_once G5_PATH.'/head.sub.php';

// 이모티콘 목록 불러오기
$sql = "select * from {$g5['emoticon_table']}";
$result = sql_query($sql);

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.emoticon.css">', 0);
?>

<div id="emoticon_page">
	<div id="emoticon_head"></div>
	<div id="page_title">
		이모티콘
		<i id="emoticon_line"></i>
	</div>

	<div id="emoticon_content">
		<ul>
		<?php  for($i=0; $row = sql_fetch_array($result); $i++) { ?>
			<li>
				<em>
					<img src="<?=G5_URL?><?=$row['me_img']?>" alt="" />
				</em>
				<span><?=$row['me_text']?></span>
			</li>
		<?php  }
			if($i == 0) { 
		?>
			<li class="no-data">
				등록된 이모티콘이 없습니다.
			</li>
		<?php 
			}
		?>
		</ul>
	</div>
	<div id="emoticon_footer"></div>
</div>




<?php  include_once G5_PATH.'/tail.sub.php'; ?>