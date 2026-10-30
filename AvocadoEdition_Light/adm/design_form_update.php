<?php
$sub_menu = "100300";
include_once('./_common.php');
check_demo();
if ($is_admin !== 'super') alert('최고관리자만 접근 가능합니다.');
check_admin_token();
include_once G5_LIB_PATH.'/tomo_settings.lib.php';
include_once G5_LIB_PATH.'/tomo_fonts.lib.php';
try {
    tomo_settings_upgrade();
    $tomo_fonts = isset($_POST['tomo_fonts_present']) ? tomo_post_fonts() : tomo_fonts();
    $selected = [];
    foreach (['default_font'=>3, 'menu_tooltip'=>9, 'intro'=>5] as $name=>$slot) {
        $stored = sql_fetch("SELECT cs_etc_{$slot} FROM {$g5['css_table']} WHERE cs_name = '{$name}'");
        $selected[$name] = (string)($stored['cs_etc_'.$slot] ?? '');
    }
    foreach (($_POST['cs_name'] ?? []) as $i=>$name) {
        $slot = ['default_font'=>3, 'menu_tooltip'=>9, 'intro'=>5][$name] ?? null;
        if ($slot !== null) $selected[$name] = stripslashes((string)($_POST['cs_etc_'.$slot][$i] ?? ''));
    }
    tomo_check_font_removal(tomo_fonts(), $tomo_fonts, $selected);
} catch (Throwable $e) { alert($e->getMessage()); }


$site_style_path = G5_DATA_PATH."/site";
$site_style_url = G5_DATA_URL."/site";

@mkdir($site_style_path, G5_DIR_PERMISSION);
@chmod($site_style_path, G5_DIR_PERMISSION);


for($i=0; $i < count($cs_name); $i++) {

	$de = sql_fetch("select * from {$g5['css_table']} where cs_name = '{$cs_name[$i]}'");

	if(is_array($cs_etc_1[$i]))		$cs_etc_1[$i] = "||".implode("||", $cs_etc_1[$i])."||";
	if(is_array($cs_etc_2[$i]))		$cs_etc_2[$i] = "||".implode("||", $cs_etc_2[$i])."||";
	if(is_array($cs_etc_3[$i]))		$cs_etc_3[$i] = "||".implode("||", $cs_etc_3[$i])."||";
	if(is_array($cs_etc_4[$i]))		$cs_etc_4[$i] = "||".implode("||", $cs_etc_4[$i])."||";
	if(is_array($cs_etc_5[$i]))		$cs_etc_5[$i] = "||".implode("||", $cs_etc_5[$i])."||";
	if(is_array($cs_etc_6[$i]))		$cs_etc_6[$i] = "||".implode("||", $cs_etc_6[$i])."||";
	if(is_array($cs_etc_7[$i]))		$cs_etc_7[$i] = "||".implode("||", $cs_etc_7[$i])."||";
	if(is_array($cs_etc_8[$i]))		$cs_etc_8[$i] = "||".implode("||", $cs_etc_8[$i])."||";
	if(is_array($cs_etc_9[$i]))		$cs_etc_9[$i] = "||".implode("||", $cs_etc_9[$i])."||";
	if(is_array($cs_etc_10[$i]))	$cs_etc_10[$i] = "||".implode("||", $cs_etc_10[$i])."||";

	if(is_array($cs_etc_11[$i]))	$cs_etc_11[$i] = "||".implode("||", $cs_etc_11[$i])."||";
	if(is_array($cs_etc_12[$i]))	$cs_etc_12[$i] = "||".implode("||", $cs_etc_12[$i])."||";
	if(is_array($cs_etc_13[$i]))	$cs_etc_13[$i] = "||".implode("||", $cs_etc_13[$i])."||";
	if(is_array($cs_etc_14[$i]))	$cs_etc_14[$i] = "||".implode("||", $cs_etc_14[$i])."||";
	if(is_array($cs_etc_15[$i]))	$cs_etc_15[$i] = "||".implode("||", $cs_etc_15[$i])."||";
	if(is_array($cs_etc_16[$i]))	$cs_etc_16[$i] = "||".implode("||", $cs_etc_16[$i])."||";
	if(is_array($cs_etc_17[$i]))	$cs_etc_17[$i] = "||".implode("||", $cs_etc_17[$i])."||";
	if(is_array($cs_etc_18[$i]))	$cs_etc_18[$i] = "||".implode("||", $cs_etc_18[$i])."||";
	if(is_array($cs_etc_19[$i]))	$cs_etc_19[$i] = "||".implode("||", $cs_etc_19[$i])."||";
	if(is_array($cs_etc_20[$i]))	$cs_etc_20[$i] = "||".implode("||", $cs_etc_20[$i])."||";

	// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
	if ($_FILES['cs_value_file']['name'][$i]) {
		// 확장자 따기
		$exp = explode(".", $_FILES['cs_value_file']['name'][$i]);
		$exp = $exp[count($exp)-1];

		$image_name = "design_".$cs_name[$i].".".$exp;
		upload_file($_FILES['cs_value_file']['tmp_name'][$i], $image_name, $site_style_path);
		$cs_value[$i] = $site_style_url."/".$image_name;
	}



	if($de['cs_id']) {
		// 수정
		$sql = " update {$g5['css_table']}
					set cs_value	= '{$cs_value[$i]}',
						cs_descript	= '{$cs_descript[$i]}',
						cs_etc_1	= '{$cs_etc_1[$i]}',
						cs_etc_2	= '{$cs_etc_2[$i]}',
						cs_etc_3	= '{$cs_etc_3[$i]}',
						cs_etc_4	= '{$cs_etc_4[$i]}',
						cs_etc_5	= '{$cs_etc_5[$i]}',
						cs_etc_6	= '{$cs_etc_6[$i]}',
						cs_etc_7	= '{$cs_etc_7[$i]}',
						cs_etc_8	= '{$cs_etc_8[$i]}',
						cs_etc_9	= '{$cs_etc_9[$i]}',
						cs_etc_10	= '{$cs_etc_10[$i]}',
						cs_etc_11	= '{$cs_etc_11[$i]}',
						cs_etc_12	= '{$cs_etc_12[$i]}',
						cs_etc_13	= '{$cs_etc_13[$i]}',
						cs_etc_14	= '{$cs_etc_14[$i]}',
						cs_etc_15	= '{$cs_etc_15[$i]}',
						cs_etc_16	= '{$cs_etc_16[$i]}',
						cs_etc_17	= '{$cs_etc_17[$i]}',
						cs_etc_18	= '{$cs_etc_18[$i]}',
						cs_etc_19	= '{$cs_etc_19[$i]}',
						cs_etc_20	= '{$cs_etc_20[$i]}'
					where cs_id = '{$de['cs_id']}'
		";
		sql_query($sql);

	} else {
		// 입력
		$sql = " insert into {$g5['css_table']}
					set cs_name		= '{$cs_name[$i]}',
						cs_value	= '{$cs_value[$i]}',
						cs_descript	= '{$cs_descript[$i]}',
						cs_etc_1	= '{$cs_etc_1[$i]}',
						cs_etc_2	= '{$cs_etc_2[$i]}',
						cs_etc_3	= '{$cs_etc_3[$i]}',
						cs_etc_4	= '{$cs_etc_4[$i]}',
						cs_etc_5	= '{$cs_etc_5[$i]}',
						cs_etc_6	= '{$cs_etc_6[$i]}',
						cs_etc_7	= '{$cs_etc_7[$i]}',
						cs_etc_8	= '{$cs_etc_8[$i]}',
						cs_etc_9	= '{$cs_etc_9[$i]}',
						cs_etc_10	= '{$cs_etc_10[$i]}',
						cs_etc_11	= '{$cs_etc_11[$i]}',
						cs_etc_12	= '{$cs_etc_12[$i]}',
						cs_etc_13	= '{$cs_etc_13[$i]}',
						cs_etc_14	= '{$cs_etc_14[$i]}',
						cs_etc_15	= '{$cs_etc_15[$i]}',
						cs_etc_16	= '{$cs_etc_16[$i]}',
						cs_etc_17	= '{$cs_etc_17[$i]}',
						cs_etc_18	= '{$cs_etc_18[$i]}',
						cs_etc_19	= '{$cs_etc_19[$i]}',
						cs_etc_20	= '{$cs_etc_20[$i]}'";
		sql_query($sql);
	}
}

$cf_add_fonts = '';
if (isset($_POST['cf_add_fonts'])) {
	$cf_add_fonts = substr(trim($_POST['cf_add_fonts']),0,65536);
	$cf_add_fonts = preg_replace("#[\\\]+$#", "", $cf_add_fonts);
}

$tomo_fonts_json = sql_escape_string(json_encode($tomo_fonts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
$cf_css_version = date('YmdHis').bin2hex(random_bytes(3));


$sql = " update {$g5['config_table']}
			set cf_add_fonts	= '{$cf_add_fonts}',
                cf_tomo_fonts = '{$tomo_fonts_json}',
				cf_css_version	= '{$cf_css_version}',
				cf_add_script		= '{$_POST['cf_add_script']}'";
sql_query($sql);


$config['cf_tomo_fonts'] = json_encode($tomo_fonts, JSON_UNESCAPED_UNICODE);
$tomo_legacy_font_css = stripslashes($cf_add_fonts);

// CSS 설정 파일 생성
$css_data_path = G5_DATA_PATH."/css";
$css_data_url = G5_DATA_URL."/css";

@mkdir($css_data_path, G5_DIR_PERMISSION);
@chmod($css_data_path, G5_DIR_PERMISSION);

$file_path = $css_data_path.'/_design.config.css';
ob_start();
try {
    include './design_form_css.php';
    $generated_css = ob_get_clean();
    $temporary = $file_path.'.'.bin2hex(random_bytes(8)).'.tmp';
    if (file_put_contents($temporary, $generated_css, LOCK_EX) === false || !rename($temporary, $file_path)) {
        @unlink($temporary);
        throw new RuntimeException('디자인 파일 저장 실패');
    }
    chmod($file_path, G5_FILE_PERMISSION);
} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    error_log('Design save: '.$e->getMessage());
    alert('디자인 파일을 저장하지 못했습니다. 입력을 유지하고 서버 쓰기 권한을 확인해 주세요.');
}

goto_url('./design_form.php', false);
?>