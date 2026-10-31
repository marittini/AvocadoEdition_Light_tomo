<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/menu.lib.php';
(static function () use ($g5, $menu_skin_url, $is_member, $is_admin) {
    // $config is needed for the BGM playlist (cf_bgm); the closure does not see globals otherwise.
    global $is_add_register, $config;
add_stylesheet('<link rel="stylesheet" href="'.$menu_skin_url.'/css/style.L.css?v=2">', 1);
add_javascript('<script defer src="'.$menu_skin_url.'/menu.js?v=1"></script>', 1);
?>

<ul class="gnbWrap ucho-menu">
    <li>
        <a href="<?=G5_URL?>/main.php" class="change-link">
            <span class="icons"><span><i class="material-icons">home</i></span></span>
            <span class="tooltips">홈</span>
        </a>
    </li>
    <li class="line"></li>

    <?php
    $menu_sql = " select me_id, me_code, me_name, me_link, me_target, me_icon from {$g5['menu_table']} where me_use = '1' order by me_order asc, me_id asc";
    $menu_query = sql_query($menu_sql);
    
    $rows = [];
    while ($row = sql_fetch_array($menu_query)) $rows[] = $row;
    $menus = ucho_menu_tree($rows);

    foreach ($menus as $me) {
        $has_sub = !empty($me['sub']);
        
        $main_link = $me['me_link'];
        $main_target = (($me['me_target'] ?? '') === 'blank') ? 'target="_blank" rel="noopener noreferrer"' : '';
        
        if ($me['me_name'] == '구분선') { 
            echo "<li class='line'></li>";
        } else {
    ?>
        <li class="<?php echo $has_sub ? 'has-sub' : ''; ?>">
            <?php if ($has_sub) { ?><button type="button" class="ucho-menu-toggle" aria-expanded="false" aria-label="<?=ucho_menu_escape($me['me_name'])?> 하위 메뉴"><?php } else { ?><a href="<?=ucho_menu_url($main_link)?>" <?=$main_target?> class="change-link"><?php } ?>
                <span class="icons"><span><i class="material-icons"><?=ucho_menu_escape($me['me_icon'] ?? '')?></i></span></span>
                
                <?php if(!$has_sub) { ?>
                    <span class="tooltips"><?=ucho_menu_escape($me['me_name'])?></span>
                <?php } ?>
            <?php if ($has_sub) { ?></button><?php } else { ?></a><?php } ?>
            
            <?php if($has_sub) { ?>
            <ul class="sub-menu">
                                
                <?php foreach($me['sub'] as $sub) { 
                    $sub_target = (($sub['me_target'] ?? '') === 'blank') ? 'target="_blank" rel="noopener noreferrer"' : '';
                ?>
                <li>
                    <a href="<?=ucho_menu_url($sub['me_link'])?>" <?php echo $sub_target ?> class="change-link">
                        <?=ucho_menu_escape($sub['me_name'])?>
                    </a>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </li>
    <?php }} ?>

    <li class="line"></li>
    
    <?php if(!$is_member) { ?>
        <li>
            <a href="<?=G5_BBS_URL?>/login.php">
                <span class="icons"><span><i class="material-icons">login</i></span></span>
                <span class="tooltips">로그인</span>
            </a>
        </li>
        <?php if (!empty($is_add_register)) { ?><li>
            <a href="<?=G5_BBS_URL?>/register.php">
                <span class="icons"><span><i class="material-icons">person_add</i></span></span>
                <span class="tooltips">가입</span>
            </a>
        </li>
        <?php } ?>
    <?php } else { // 회원: 로그아웃 & 관리자/정보수정 --> ?>
        <li>
            <a href="<?=G5_BBS_URL?>/logout.php">
                <span class="icons"><span><i class="material-icons">logout</i></span></span>
                <span class="tooltips">로그아웃</span>
            </a>
        </li>
        <?php if($is_admin) { ?>
        <li>
            <a href="<?=G5_URL?>/adm" target="_blank">
                <span class="icons"><span><i class="material-icons">settings</i></span></span>
                <span class="tooltips">관리자</span>
            </a>
        </li>
        <?php } else { ?>
            <li>
                <a href="<?=G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="change-link">
                    <span class="icons"><span><i class="material-icons">settings</i></span></span>
                    <span class="tooltips">정보수정</span>
                </a>
            </li>
        <?php } ?>
    <?php } ?>

    <?php if (defined('_INDEX_') && !empty($config['cf_bgm'])) { // Copied from skin/menu/basic: same markup, icons and labels. ?>
        <li class="bgm-btn">
            <div id="site_bgm_box">
                <iframe src="./bgm.php?action=play" name="bgm_frame" id="bgm_frame" border="0" frameborder="0" marginheight="0" marginwidth="0" topmargin="0" scrolling="no" allowTransparency="true"></iframe>
            </div>
            <a href="<?=G5_URL?>/bgm.php?action=play" target="bgm_frame" onclick="return fn_control_bgm('play')" class="control-bgm-play" style="display:none;">
                <span class="icons"><span><i class="material-icons">music_off</i></span></span>
                <span class="tooltips">BGM ON</span>
            </a>
            <a href="<?=G5_URL?>/bgm.php" target="bgm_frame" onclick="return fn_control_bgm('stop')" class="control-bgm-stop">
                <span class="icons"><span><i class="material-icons">music_note</i></span></span>
                <span class="tooltips">BGM OFF</span>
            </a>
        </li>
    <?php } ?>
</ul>
<?php })(); ?>
