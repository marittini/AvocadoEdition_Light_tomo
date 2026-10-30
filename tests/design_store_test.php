<?php
// Real, isolated DB tables + temporary files. Never modifies the installed config/CSS tables.
$g5_path=['path'=>realpath(__DIR__.'/../AvocadoEdition_Light'),'url'=>'http://design-test.invalid'];
require $g5_path['path'].'/config.php';
require G5_LIB_PATH.'/common.lib.php';
require G5_LIB_PATH.'/tomo_design.lib.php';
if (!getenv('MARIADB_DATABASE') || !getenv('MARIADB_USER')) throw new RuntimeException('Set disposable MARIADB_* credentials.');
$g5['connect_db']=new mysqli(getenv('TEST_DB_HOST')?:'tomo-test-db',getenv('MARIADB_USER'),getenv('MARIADB_PASSWORD'),getenv('MARIADB_DATABASE'));
$g5['connect_db']->set_charset('utf8');$g5['connect_db']->query("SET SESSION sql_mode=''");
function td_rows(string $table): array {$r=sql_query('SELECT * FROM `'.$table.'` ORDER BY '.(str_ends_with($table,'css')?'cs_id':'cf_title'));$a=[];while($x=sql_fetch_array($r))$a[]=$x;return$a;}
function td_post(array $tables): array {
 $rows=td_rows($tables['css']);$cfg=td_rows($tables['config'])[0];
 $p=['tomo_design_revision'=>tomo_design_revision($rows,$cfg),'tomo_fonts_present'=>'1','tomo_fonts'=>json_decode($cfg['cf_tomo_fonts']??'[]',true)?:[], 'cf_add_fonts'=>$cfg['cf_add_fonts'],'cf_add_script'=>$cfg['cf_add_script']];
 foreach($rows as$i=>$row){if($row['cs_name']==='')continue;foreach(TomoDesignStore::ROW_FIELDS as$field)$p[$field][$i]=$row[$field];}
 $i=array_search('color_point',array_column($rows,'cs_name'));$p['cs_value'][$i]='#123456';return$p;
}
function td_check($ok,string$msg):void{if(!$ok)throw new RuntimeException($msg);echo'PASS '.$msg."\n";}
function td_state(array$t,string$root):array{$cfg=td_rows($t['config'])[0];return[tomo_design_revision(td_rows($t['css']),$cfg),file_get_contents($root.'/css/_design.config.css')];}
class TdStore extends TomoDesignStore {
 public string $fail='';public bool $crash=false;public bool $hold=false;public string $signal='';
 protected function checkpoint(string$phase):void{
  if($this->hold&&$phase==='journaled'){file_put_contents($this->signal,'ready');usleep(500000);}
  if($phase===$this->fail){if($this->crash)exit(91);throw new RuntimeException('Injected '.$phase);}
 }
 protected function moveUpload(string$a,string$b):void{if(!rename($a,$b))throw new RuntimeException('fixture move failed');}
}
if (($argv[1]??'')==='worker') {
 $ctx=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
 foreach($ctx['tables']as$table)if(!preg_match('/^td_test_[a-f0-9]+_(cfg|css)$/',$table))throw new RuntimeException('Use isolated fixture tables.');
 $config=td_rows($ctx['tables']['config'])[0];require G5_PATH.'/extend/community.config.php';
 $s=new TdStore($ctx['root'],$ctx['tables']);$s->fail=$ctx['fail']??'';$s->crash=true;$s->hold=$ctx['hold']??false;$s->signal=$ctx['root'].'/entered';
 try{$s->save($ctx['post'],[]);exit(0);}catch(TomoDesignConflict$e){exit(73);}
}
$tag=bin2hex(random_bytes(4));$tables=['config'=>'td_test_'.$tag.'_cfg','css'=>'td_test_'.$tag.'_css'];
$root=sys_get_temp_dir().'/tomo-design-'.$tag;mkdir($root,0700);mkdir($root.'/css');mkdir($root.'/site');
$config=sql_fetch('SELECT * FROM avo_config');require G5_PATH.'/extend/community.config.php';
try {
 foreach(['config'=>'avo_config','css'=>'avo_css_config']as$key=>$source){sql_query('CREATE TABLE `'.$tables[$key].'` LIKE `'.$source.'`');sql_query('INSERT INTO `'.$tables[$key].'` SELECT * FROM `'.$source.'`');}
 sql_query("ALTER TABLE `{$tables['css']}` ADD custom_note VARCHAR(30) NOT NULL DEFAULT '', ADD custom_len INT GENERATED ALWAYS AS (CHAR_LENGTH(cs_name)) VIRTUAL");
 sql_query("UPDATE `{$tables['css']}` SET custom_note='preserve extension' WHERE cs_name='color_point'");
 sql_query("UPDATE `{$tables['config']}` SET cf_add_script='<!-- <?php intentionally invalid template ?> -->'");
 file_put_contents($root.'/css/_design.config.css','/* original css */');file_put_contents($root.'/site/original.png','original asset');
 $original=td_state($tables,$root);
 foreach(['staged','journaled','rows_written','config_written','published','committed']as$phase){
  $store=new TdStore($root,$tables);$store->fail=$phase;
  $post=td_post($tables);$i=array_search('background',$post['cs_name']);
  file_put_contents($root.'/upload.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN1sAAAAASUVORK5CYII='));
  $files=['cs_value_file'=>['name'=>[$i=>'picture.png'],'error'=>[$i=>UPLOAD_ERR_OK],'tmp_name'=>[$i=>$root.'/upload.png']]];
  $caught=false;try{$store->save($post,$files);}catch(RuntimeException$e){$caught=true;}
  td_check($caught&&td_state($tables,$root)===$original&&!$store->pending(),'failure at '.$phase.' restores DB and exact CSS');
  td_check(sql_fetch("SELECT custom_note FROM `{$tables['css']}` WHERE cs_name='color_point'")['custom_note']==='preserve extension','extension data preserved at '.$phase);
  td_check(count(glob($root.'/site/design_*'))===0&&file_get_contents($root.'/site/original.png')==='original asset','failure at '.$phase.' cleans only new assets');
 }
 // A real SQL failure, after earlier MyISAM rows may have changed.
 $trigger='td_test_'.$tag.'_fail';sql_query("CREATE TRIGGER `$trigger` BEFORE UPDATE ON `{$tables['css']}` FOR EACH ROW BEGIN IF NEW.cs_name='color_point' AND NEW.cs_value='#123456' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='forced design failure'; END IF; END");
 try{$failed=false;try{(new TdStore($root,$tables))->save(td_post($tables),[]);}catch(Throwable$e){$failed=true;}td_check($failed&&td_state($tables,$root)===$original,'real MyISAM SQL failure rolls back preceding updates');}finally{sql_query('DROP TRIGGER `'.$trigger.'`');}
 // Permission failure is tested as a non-root user, otherwise root bypasses chmod.
 if(function_exists('posix_geteuid')&&posix_geteuid()!==0){chmod($root.'/css',0555);try{$failed=false;try{(new TdStore($root,$tables))->save(td_post($tables),[]);}catch(Throwable$e){$failed=true;}td_check($failed&&td_state($tables,$root)===$original,'unwritable CSS directory changes no DB values');}finally{chmod($root.'/css',0755);}}
 $post=td_post($tables);$done=(new TdStore($root,$tables))->save($post,[]);$published=td_state($tables,$root);td_check($published!==$original&&$done['fields']['tomo_design_revision']===$published[0],'normal save publishes CSS and new revision');
 $failed=false;try{(new TdStore($root,$tables))->save($post,[]);}catch(TomoDesignConflict$e){$failed=true;}td_check($failed&&td_state($tables,$root)===$published,'stale form cannot overwrite a newer save');
 $context=$root.'/worker.json';
 $spawn=static function(array$ctx)use($context){$file=$context.'.'.bin2hex(random_bytes(3));file_put_contents($file,json_encode($ctx));$p=proc_open([PHP_BINARY,__FILE__,'worker',$file],[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);if(!is_resource($p))throw new RuntimeException('worker start failed');return$p;};
 foreach(['journaled','rows_written','config_written','published','committed']as$phase){
  $before=td_state($tables,$root);$post=td_post($tables);$i=array_search('color_point',$post['cs_name']);$post['cs_value'][$i]='#'.substr(hash('sha256',$phase),0,6);
  $worker=$spawn(['tables'=>$tables,'root'=>$root,'post'=>$post,'fail'=>$phase]);td_check(proc_close($worker)===91,'process stopped at '.$phase);
  $store=new TdStore($root,$tables);td_check($store->pending(),'interrupted '.$phase.' leaves durable recovery record');$guard=proc_open([PHP_BINARY,$root.'/css/.tomo-design/recovery.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$guardPipes);$guardOut=stream_get_contents($guardPipes[1]);$guardError=stream_get_contents($guardPipes[2]);fclose($guardPipes[1]);fclose($guardPipes[2]);td_check(proc_close($guard)===0&&$guardOut===''&&$guardError==='','journal PHP guard hides backup data at '.$phase);$committed=td_state($tables,$root);$store->recover();$store->recover();
  td_check(td_state($tables,$root)===($phase==='committed'?$committed:$before)&&!$store->pending(),'restart recovery at '.$phase.' is idempotent');
 }
 // A committed journal is not permission to overwrite a later out-of-band design change.
 $post=td_post($tables);$worker=$spawn(['tables'=>$tables,'root'=>$root,'post'=>$post,'fail'=>'committed']);td_check(proc_close($worker)===91,'completed writer interrupted before journal cleanup');
 $cfg=td_rows($tables['config'])[0];sql_query("UPDATE `{$tables['config']}` SET cf_add_script=CONCAT(cf_add_script,'manual change')");$changed=td_state($tables,$root);$store=new TdStore($root,$tables);$blocked=false;try{$store->recover();}catch(RuntimeException$e){$blocked=true;}
 td_check($blocked&&$store->pending()&&td_state($tables,$root)===$changed,'committed recovery refuses to overwrite newer external changes');
 $q=mysqli_real_escape_string($g5['connect_db'],$cfg['cf_add_script']);sql_query("UPDATE `{$tables['config']}` SET cf_add_script='$q'");$store->recover();td_check(!$store->pending(),'confirmed completed save can finalize after repair');
 // Two independent PHP/DB sessions submit the same revision. Only one may publish.
 $post=td_post($tables);$a=$spawn(['tables'=>$tables,'root'=>$root,'post'=>$post,'hold'=>true]);$deadline=microtime(true)+4;while(!is_file($root.'/entered')&&microtime(true)<$deadline)usleep(10000);
 td_check(is_file($root.'/entered'),'first writer holds the save lock');$b=$spawn(['tables'=>$tables,'root'=>$root,'post'=>$post]);$codes=[proc_close($a),proc_close($b)];sort($codes);td_check($codes===[0,73],'parallel writers serialize and reject the stale writer');
 // A malformed journal must fail closed before deleting or replacing any DB rows.
 $before=td_state($tables,$root);file_put_contents($root.'/css/.tomo-design/recovery.php',"<?php exit; /*\n".base64_encode(json_encode(['format'=>1,'tables'=>$tables,'phase'=>'prepared','before'=>['rows'=>'broken','config'=>[]],'created'=>[],'css_before'=>'']))."\n*/\n");
 $store=new TdStore($root,$tables);$blocked=false;try{$store->recover();}catch(RuntimeException$e){$blocked=true;}td_check($blocked&&$store->pending()&&td_state($tables,$root)===$before,'malformed recovery record changes no DB or CSS');unlink($root.'/css/.tomo-design/recovery.php');
 // Run the normal/rollback path with InnoDB as well, without requiring an engine migration.
 foreach($tables as$table)sql_query('ALTER TABLE `'.$table.'` ENGINE=InnoDB');$before=td_state($tables,$root);$s=new TdStore($root,$tables);$s->fail='published';try{$s->save(td_post($tables),[]);}catch(RuntimeException$e){}td_check(td_state($tables,$root)===$before,'existing InnoDB tables also recover without engine conversion');
} finally {
 foreach($tables as$table)sql_query('DROP TABLE IF EXISTS `'.$table.'`',false);
 if(is_dir($root)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$file){if($file->isDir()) {chmod($file->getPathname(),0700);rmdir($file->getPathname());}else unlink($file->getPathname());}rmdir($root);}
}
