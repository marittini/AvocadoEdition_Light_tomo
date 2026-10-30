<?php
// Deliberately interrupts an INSTALLED DISPOSABLE site. Requires explicit opt-in and a test-named DB.
if (PHP_SAPI!=='cli'||getenv('TEST_ALLOW_DESIGN_INTERRUPT')!=='1'||!str_contains(getenv('MARIADB_DATABASE')?:'','test')) throw new RuntimeException('Use an explicitly authorized disposable test site only.');
$g5_path=['path'=>realpath(__DIR__.'/../AvocadoEdition_Light'),'url'=>'http://127.0.0.1:8094'];
require $g5_path['path'].'/config.php';require G5_LIB_PATH.'/common.lib.php';require G5_LIB_PATH.'/tomo_design.lib.php';
$prefix=getenv('TEST_TABLE_PREFIX')?:'avo_';if(!preg_match('/^[a-zA-Z0-9_]+$/',$prefix))throw new RuntimeException('Invalid test table prefix.');
$g5['connect_db']=new mysqli(getenv('TEST_DB_HOST')?:'tomo-test-db',getenv('MARIADB_USER'),getenv('MARIADB_PASSWORD'),getenv('MARIADB_DATABASE'));$g5['connect_db']->set_charset('utf8');$g5['connect_db']->query("SET SESSION sql_mode=''");
$g5['config_table']=$prefix.'config';$g5['css_table']=$prefix.'css_config';
$config=sql_fetch('SELECT * FROM `'.$g5['config_table'].'`');$r=sql_query('SELECT * FROM `'.$g5['css_table'].'` ORDER BY cs_id');$rows=[];while($row=sql_fetch_array($r))$rows[]=$row;
$store=new TomoDesignStore();
if(($argv[1]??'')==='state'){echo json_encode(['revision'=>tomo_design_revision($rows,$config),'css'=>hash_file('sha256',G5_DATA_PATH.'/css/_design.config.css'),'pending'=>$store->pending()]);exit;}
if(($argv[1]??'')!=='interrupt')throw new RuntimeException('Choose state or interrupt.');
require G5_PATH.'/extend/community.config.php';
$post=['tomo_design_revision'=>tomo_design_revision($rows,$config),'tomo_fonts_present'=>'1','tomo_fonts'=>json_decode($config['cf_tomo_fonts']??'[]',true)?:[],'cf_add_fonts'=>$config['cf_add_fonts'],'cf_add_script'=>$config['cf_add_script']."\n<!-- interrupted fixture -->"];
foreach($rows as$i=>$row){if($row['cs_name']==='')continue;foreach(TomoDesignStore::ROW_FIELDS as$field)$post[$field][$i]=$row[$field];if($row['cs_name']==='color_point')$post['cs_value'][$i]='#654321';}
$store=new class extends TomoDesignStore {protected function checkpoint(string$phase):void{if($phase==='published')exit(91);}};
$store->save($post,[]);throw new RuntimeException('Fixture did not interrupt.');
