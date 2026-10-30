<?php
if (!defined('_GNUBOARD_')) exit;
require_once __DIR__.'/tomo_fonts.lib.php';

class TomoDesignConflict extends RuntimeException {}

/** Revision of design-owned values only; unrelated site settings do not invalidate this form. */
function tomo_design_revision(array $rows, array $config): string
{
    $normalized = [];
    foreach ($rows as $row) {
        $item = [];
        foreach (TomoDesignStore::ROW_FIELDS as $field) $item[$field] = (string)($row[$field] ?? '');
        $normalized[] = $item;
    }
    usort($normalized, static fn($a,$b)=>strcmp($a['cs_name'],$b['cs_name']));
    $settings = [];
    foreach (TomoDesignStore::CONFIG_FIELDS as $field) $settings[$field] = $config[$field] ?? null;
    return hash('sha256', json_encode([$normalized,$settings], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

/** MyISAM-compatible coordinated save. No transaction/engine migration is assumed. */
class TomoDesignStore
{
    public const CONFIG_FIELDS = ['cf_add_fonts','cf_tomo_fonts','cf_add_script','cf_css_version'];
    public const ROW_FIELDS = ['cs_name','cs_value','cs_descript','cs_etc_1','cs_etc_2','cs_etc_3','cs_etc_4','cs_etc_5','cs_etc_6','cs_etc_7','cs_etc_8','cs_etc_9','cs_etc_10','cs_etc_11','cs_etc_12','cs_etc_13','cs_etc_14','cs_etc_15','cs_etc_16','cs_etc_17','cs_etc_18','cs_etc_19','cs_etc_20'];
    private const PREFIX = "<?php exit; /*\n";
    private const SUFFIX = "\n*/\n";
    private string $root;
    private string $state;
    private array $tables;
    private $renderer;

    public function __construct(?string $root = null, ?array $tables = null, ?callable $renderer = null)
    {
        global $g5;
        $this->root = rtrim($root ?? G5_DATA_PATH, '/');
        $this->state = $this->root.'/css/.tomo-design';
        $this->tables = $tables ?? ['config'=>$g5['config_table'], 'css'=>$g5['css_table']];
        foreach ($this->tables as $table) if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('디자인 테이블 설정을 확인해 주세요.');
        $this->renderer = $renderer;
    }

    public function pending(): bool { return is_file($this->state.'/recovery.php'); }
    protected function checkpoint(string $phase): void {} // Fault injection is available only to a test subclass, never HTTP input.

    protected function query(string $sql)
    {
        $result = sql_query($sql, false);
        if ($result === false) throw new RuntimeException('디자인 DB 작업을 완료하지 못했습니다.');
        return $result;
    }
    private function quote($value): string
    {
        global $g5;
        // Snapshots must be escaped, not run through configurable request-content filters again.
        return $value === null ? 'NULL' : "'".mysqli_real_escape_string($g5['connect_db'],(string)$value)."'";
    }
    private function rows(string $sql): array
    {
        $result = $this->query($sql); $rows = [];
        while ($row = sql_fetch_array($result)) $rows[] = $row;
        return $rows;
    }
    private function snapshot(): array
    {
        $rows = $this->rows("SELECT * FROM `{$this->tables['css']}` ORDER BY cs_id");
        if (count(array_unique(array_column($rows,'cs_name'))) !== count($rows)) throw new RuntimeException('중복된 디자인 설정 이름을 정리한 뒤 저장해 주세요.');
        $configs = $this->rows('SELECT '.implode(',',self::CONFIG_FIELDS)." FROM `{$this->tables['config']}`");
        if (count($configs)!==1) throw new RuntimeException('사이트 기본 설정 행을 확인해 주세요.');
        return ['rows'=>$rows,'config'=>$configs[0]];
    }

    private function write(string $path, string $bytes, int $mode): void
    {
        $file = @fopen($path, 'xb');
        if (!$file) throw new RuntimeException('디자인 임시 파일을 만들 수 없습니다. 저장 폴더의 쓰기 권한과 여유 공간을 확인해 주세요.');
        try {
            $offset=0; $size=strlen($bytes);
            while ($offset<$size) { $n=fwrite($file,substr($bytes,$offset)); if (!$n) throw new RuntimeException('디자인 파일 쓰기에 실패했습니다.'); $offset+=$n; }
            if (!fflush($file) || (function_exists('fsync') && !fsync($file))) throw new RuntimeException('디자인 파일 동기화에 실패했습니다.');
            if (!chmod($path,$mode)) throw new RuntimeException('디자인 파일 권한 설정에 실패했습니다.');
        } catch (Throwable $e) { fclose($file); @unlink($path); throw $e; }
        fclose($file);
    }
    private function atomicWrite(string $path, string $bytes, int $mode): void
    {
        $temporary=$path.'.'.bin2hex(random_bytes(8)).'.tmp';
        $this->write($temporary,$bytes,$mode);
        if (!@rename($temporary,$path)) { @unlink($temporary); throw new RuntimeException('디자인 파일 교체에 실패했습니다.'); }
    }
    private function journal(array $record): void
    {
        $this->atomicWrite($this->state.'/recovery.php',self::PREFIX.base64_encode(json_encode($record,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)).self::SUFFIX,0600);
    }
    private function cleanup(array $created): void
    {
        foreach ($created as $relative) {
            if (!preg_match('~^(site/design_[a-zA-Z0-9_]+_[a-f0-9]{16}\.[a-z0-9]+|css/\.tomo-design-stage-[a-f0-9]{16}\.css)$~',$relative)) throw new RuntimeException('디자인 복구 파일 목록이 올바르지 않습니다.');
            if (is_file($this->root.'/'.$relative) && !@unlink($this->root.'/'.$relative)) error_log('Design save: unused staging file could not be removed.');
        }
    }
    private function clearJournal(): void
    {
        if ($this->pending() && !@unlink($this->state.'/recovery.php')) error_log('Design save: completed recovery journal could not be removed.');
    }

    private function locked(callable $operation)
    {
        if (!is_dir($this->root.'/css') && !@mkdir($this->root.'/css',0755,true) && !is_dir($this->root.'/css')) throw new RuntimeException('디자인 CSS 폴더를 만들 수 없습니다.');
        if (!is_dir($this->state) && !@mkdir($this->state,0700,true) && !is_dir($this->state)) throw new RuntimeException('디자인 저장 잠금 폴더를 만들 수 없습니다.');
        if (!is_file($this->state.'/.htaccess')) $this->atomicWrite($this->state.'/.htaccess',"Require all denied\n",0600);
        $lock=@fopen($this->state.'/save.lock','c+b');
        if (!$lock) throw new RuntimeException('디자인 저장 잠금 파일을 열 수 없습니다.');
        $deadline=microtime(true)+5;
        while (!flock($lock,LOCK_EX|LOCK_NB)) {
            if (microtime(true)>$deadline) { fclose($lock); throw new TomoDesignConflict('다른 디자인 저장이 진행 중입니다. 잠시 후 다시 시도해 주세요.'); }
            usleep(25000);
        }
        $tablesLocked=false; $timeout=null;
        try {
            $setting=$this->rows('SELECT @@SESSION.lock_wait_timeout AS wait_timeout');
            $timeout=(int)$setting[0]['wait_timeout'];
            $this->query('SET SESSION lock_wait_timeout=5');
            $this->query("LOCK TABLES `{$this->tables['css']}` WRITE, `{$this->tables['config']}` WRITE");
            $tablesLocked=true;
            return $operation();
        } finally {
            if ($tablesLocked) sql_query('UNLOCK TABLES',false);
            if ($timeout!==null) sql_query('SET SESSION lock_wait_timeout='.$timeout,false);
            flock($lock,LOCK_UN); fclose($lock);
        }
    }
    private function updateConfig(array $config): void
    {
        $assign=[];
        foreach (self::CONFIG_FIELDS as $field) $assign[]='`'.$field.'`='.$this->quote($config[$field] ?? null);
        $this->query("UPDATE `{$this->tables['config']}` SET ".implode(',',$assign));
    }
    private function insertRow(array $row, bool $withId): void
    {
        $fields=$withId ? array_keys($row) : self::ROW_FIELDS;
        if ($withId) {
            // Preserve extension columns as well; generated columns recompute from their source fields.
            $generated=[];
            foreach($this->rows("SHOW COLUMNS FROM `{$this->tables['css']}`") as $column) {
                if(preg_match('/(?:VIRTUAL|STORED|PERSISTENT) GENERATED/i',$column['Extra']))$generated[]=$column['Field'];
            }
            $fields=array_values(array_diff($fields,$generated));
        }
        $values=[];foreach($fields as $field)$values[]=$this->quote($row[$field] ?? null);
        $quoted=array_map(static fn($field)=>'`'.str_replace('`','``',$field).'`',$fields);
        $this->query("INSERT INTO `{$this->tables['css']}` (".implode(',',$quoted).') VALUES ('.implode(',',$values).')');
    }
    private function restore(array $record): void
    {
        $this->query("DELETE FROM `{$this->tables['css']}`");
        foreach ($record['before']['rows'] as $row) $this->insertRow($row,true);
        $this->updateConfig($record['before']['config']);
        if (!hash_equals(tomo_design_revision($record['before']['rows'],$record['before']['config']),$this->currentRevision())) throw new RuntimeException('복원된 디자인 DB 값의 검증에 실패했습니다.');
        $css=$this->root.'/css/_design.config.css';
        $old=$record['css_before']===null ? null : base64_decode($record['css_before'],true);
        if ($old===false) throw new RuntimeException('디자인 복구 CSS가 올바르지 않습니다.');
        if ($old===null) {
            if (file_exists($css) && (!is_file($css) || !@unlink($css))) throw new RuntimeException('이전 디자인 CSS 상태를 복구하지 못했습니다.');
        } elseif (!is_file($css) || hash_file('sha256',$css)!==hash('sha256',$old)) {
            $this->atomicWrite($css,$old,defined('G5_FILE_PERMISSION')?G5_FILE_PERMISSION:0644);
        }
        $this->cleanup($record['created']);
        $this->clearJournal();
    }
    private function currentRevision(): string
    {
        $snapshot=$this->snapshot();
        return tomo_design_revision($snapshot['rows'],$snapshot['config']);
    }
    private function recoverLocked(): void
    {
        if (!$this->pending()) return;
        $bytes=@file_get_contents($this->state.'/recovery.php');
        if ($bytes===false || !str_starts_with($bytes,self::PREFIX) || !str_ends_with($bytes,self::SUFFIX)) throw new RuntimeException('디자인 복구 기록을 읽을 수 없습니다.');
        $payload=base64_decode(substr($bytes,strlen(self::PREFIX),-strlen(self::SUFFIX)),true);
        if($payload===false)throw new RuntimeException('디자인 복구 기록 형식이 올바르지 않습니다.');
        $record=json_decode($payload,true,512,JSON_THROW_ON_ERROR);
        if (!is_array($record) || ($record['format']??0)!==1 || ($record['tables']??[])!==$this->tables || !in_array($record['phase']??'', ['prepared','committed'],true)
            || !is_array($record['before']['rows']??null) || !is_array($record['before']['config']??null) || !is_array($record['created']??null)
            || !array_key_exists('css_before',$record) || ($record['css_before']!==null && (!is_string($record['css_before']) || base64_decode($record['css_before'],true)===false))) throw new RuntimeException('디자인 복구 기록을 확인해 주세요.');
        foreach($record['before']['rows']as$row){
            if(!is_array($row)||!isset($row['cs_id'])||!ctype_digit((string)$row['cs_id']))throw new RuntimeException('디자인 복구 행을 확인해 주세요.');
            foreach(self::ROW_FIELDS as$field)if(!array_key_exists($field,$row)||(!is_scalar($row[$field])&&$row[$field]!==null))throw new RuntimeException('디자인 복구 값이 올바르지 않습니다.');
        }
        foreach(self::CONFIG_FIELDS as$field)if(!array_key_exists($field,$record['before']['config'])||(!is_scalar($record['before']['config'][$field])&&$record['before']['config'][$field]!==null))throw new RuntimeException('기본 설정 복구 값이 올바르지 않습니다.');
        foreach($record['created']as$relative)if(!is_string($relative)||!preg_match('~^(site/design_[a-zA-Z0-9_]+_[a-f0-9]{16}\.[a-z0-9]+|css/\.tomo-design-stage-[a-f0-9]{16}\.css)$~',$relative))throw new RuntimeException('복구 파일 목록이 올바르지 않습니다.');
        $css=$this->root.'/css/_design.config.css';
        if (($record['phase']??'')==='committed') {
            if (!is_file($css) || hash_file('sha256',$css)!==($record['css_after_hash']??'') || $this->currentRevision()!==($record['revision_after']??'')) {
                throw new RuntimeException('완료된 디자인 저장 이후 값 또는 파일이 변경되었습니다. 복구 기록을 보존하고 수동 점검이 필요합니다.');
            }
            $this->clearJournal(); return;
        }
        $this->restore($record);
    }
    public function recover(): void
    {
        if ($this->pending()) $this->locked(function(){ $this->recoverLocked(); });
    }

    private function scalar($value, int $limit, bool $list=false): string
    {
        if ($list && is_array($value)) {
            foreach ($value as $part) if (!is_scalar($part)) throw new InvalidArgumentException('디자인 선택값을 확인해 주세요.');
            $value='||'.implode('||',$value).'||';
        }
        if (!is_scalar($value) && $value!==null) throw new InvalidArgumentException('디자인 입력 형식을 확인해 주세요.');
        $value=(string)$value;
        if (!mb_check_encoding($value,'UTF-8') || mb_strlen($value,'UTF-8')>$limit) throw new InvalidArgumentException('디자인 입력값의 길이 또는 문자 인코딩을 확인해 주세요.');
        return $value;
    }
    protected function moveUpload(string $source, string $destination): void
    {
        if (!is_uploaded_file($source) || !move_uploaded_file($source,$destination)) throw new RuntimeException('디자인 이미지를 저장하지 못했습니다.');
        if (!chmod($destination,defined('G5_FILE_PERMISSION')?G5_FILE_PERMISSION:0644)) throw new RuntimeException('디자인 이미지 권한 설정에 실패했습니다.');
    }
    private function render(array $rows, array $config, array $fonts): string
    {
        if ($this->renderer) return ($this->renderer)($rows,$config,$fonts);
        $archive_preview_css=[];
        foreach ($rows as $row) {
            $archive_preview_css[$row['cs_name']]=[$row['cs_value']];
            for($n=1;$n<=20;$n++)$archive_preview_css[$row['cs_name']][$n]=$row['cs_etc_'.$n];
        }
        $tomo_legacy_font_css=$config['cf_add_fonts']; $tomo_render_fonts=$fonts;
        if (!defined('ARCHIVE_CSS_RENDER')) define('ARCHIVE_CSS_RENDER',true);
        $level=ob_get_level();ob_start();
        try { include dirname(__DIR__).'/adm/design_form_css.php'; return ob_get_clean(); }
        catch(Throwable $e){while(ob_get_level()>$level)ob_end_clean();throw $e;}
    }

    /** POST is raw UTF-8, after undoing common.php's request escaping exactly once. */
    public function save(array $post, array $files): array
    {
        return $this->locked(function() use($post,$files){
            $this->recoverLocked();
            $before=$this->snapshot();
            $revision=tomo_design_revision($before['rows'],$before['config']);
            if (!is_string($post['tomo_design_revision']??null) || !hash_equals($revision,$post['tomo_design_revision'])) throw new TomoDesignConflict('다른 화면에서 디자인이 변경되었습니다. 현재 입력을 보관하고 새로고침한 뒤 다시 적용해 주세요.');
            if (!is_array($post['cs_name']??null) || !$post['cs_name']) throw new InvalidArgumentException('디자인 항목이 누락되었습니다. 새로고침한 뒤 다시 저장해 주세요.');
            foreach(array_slice(self::ROW_FIELDS,1)as$field)if(isset($post[$field])&&!is_array($post[$field]))throw new InvalidArgumentException('디자인 항목 형식을 확인해 주세요.');
            $fontRows=$post['tomo_fonts']??[];
            if (!is_array($fontRows)) throw new InvalidArgumentException('폰트 목록을 확인해 주세요.');
            $fonts=isset($post['tomo_fonts_present'])?tomo_validate_fonts($fontRows):(json_decode($before['config']['cf_tomo_fonts']??'[]',true)?:[]);
            $candidate=[];foreach($before['rows']as$row)$candidate[$row['cs_name']]=$row;
            $fields=[];$clearFiles=[];$created=[];$journaled=false;
            try {
                foreach($post['cs_name'] as $index=>$name) {
                    if (!is_string($name)||!preg_match('/^[a-zA-Z0-9_]{1,255}$/',$name)) throw new InvalidArgumentException('디자인 항목 이름을 확인해 주세요.');
                    $row=$candidate[$name]??[];$row['cs_name']=$name;
                    foreach(array_slice(self::ROW_FIELDS,1)as$field)$row[$field]=$this->scalar($post[$field][$index]??'', $field==='cs_value'?65535:255, str_starts_with($field,'cs_etc_'));
                    if (strlen($row['cs_value'])>65535) throw new InvalidArgumentException('디자인 값은 65535바이트 이하여야 합니다.');
                    $error=$files['cs_value_file']['error'][$index]??UPLOAD_ERR_NO_FILE;
                    if ($error!==UPLOAD_ERR_NO_FILE) {
                        if ($error!==UPLOAD_ERR_OK) throw new RuntimeException('이미지 업로드가 완료되지 않았습니다. 크기 제한을 확인해 주세요.');
                        $extension=strtolower(pathinfo($files['cs_value_file']['name'][$index]??'',PATHINFO_EXTENSION));
                        if (!in_array($extension,['jpg','jpeg','png','gif','webp','bmp','avif','ico','svg'],true)) throw new InvalidArgumentException('디자인 이미지는 이미지 파일 형식으로 등록해 주세요.');
                        if (!is_dir($this->root.'/site') && !@mkdir($this->root.'/site',0755,true) && !is_dir($this->root.'/site')) throw new RuntimeException('디자인 이미지 폴더를 만들 수 없습니다.');
                        $relative='site/design_'.$name.'_'.bin2hex(random_bytes(8)).'.'.$extension;
                        $created[]=$relative;
                        $this->moveUpload($files['cs_value_file']['tmp_name'][$index]??'', $this->root.'/'.$relative);
                        $row['cs_value']=G5_DATA_URL.'/'.$relative;
                        $fields['cs_value['.$index.']']=$row['cs_value'];$clearFiles[]='cs_value_file['.$index.']';
                    }
                    $candidate[$name]=$row;
                }
                $selected=[];foreach(['default_font'=>3,'menu_tooltip'=>9,'intro'=>5]as$name=>$slot)$selected[]=$candidate[$name]['cs_etc_'.$slot]??'';
                tomo_check_font_removal(json_decode($before['config']['cf_tomo_fonts']??'[]',true)?:[],$fonts,$selected);
                $ids=array_column($fonts,'id','family');foreach($fontRows as$index=>$font){$family=trim((string)($font['family']??''));if(isset($ids[$family]))$fields['tomo_fonts['.$index.'][id]']=$ids[$family];}
                $config=$before['config'];
                foreach(['cf_add_fonts','cf_add_script']as$field){$config[$field]=$this->scalar($post[$field]??$config[$field],65535);if(strlen($config[$field])>65535)throw new InvalidArgumentException('추가 코드는 65535바이트 이하여야 합니다.');}
                $config['cf_tomo_fonts']=json_encode($fonts,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $config['cf_css_version']=date('YmdHis').bin2hex(random_bytes(6));
                $css=$this->render(array_values($candidate),$config,$fonts);
                $target=$this->root.'/css/_design.config.css';
                if (file_exists($target) && !is_file($target)) throw new RuntimeException('디자인 CSS 경로가 파일인지 확인해 주세요.');
                $old=is_file($target)?file_get_contents($target):null;
                if ($old===false) throw new RuntimeException('기존 디자인 CSS를 읽지 못했습니다.');
                $temporary='css/.tomo-design-stage-'.bin2hex(random_bytes(8)).'.css';$created[]=$temporary;
                $this->write($this->root.'/'.$temporary,$css,defined('G5_FILE_PERMISSION')?G5_FILE_PERMISSION:0644);
                $this->checkpoint('staged');
                $record=['format'=>1,'tables'=>$this->tables,'phase'=>'prepared','before'=>$before,'css_before'=>$old===null?null:base64_encode($old),'created'=>$created,'css_after_hash'=>hash('sha256',$css),'revision_after'=>tomo_design_revision(array_values($candidate),$config)];
                $this->journal($record);$journaled=true;
                $this->checkpoint('journaled');
                foreach($candidate as$row){
                    if (isset($row['cs_id'])) {
                        $assign=[];foreach(self::ROW_FIELDS as$field)$assign[]='`'.$field.'`='.$this->quote($row[$field]);
                        $this->query("UPDATE `{$this->tables['css']}` SET ".implode(',',$assign).' WHERE cs_id='.(int)$row['cs_id']);
                    } else $this->insertRow($row,false);
                }
                $this->checkpoint('rows_written');
                $this->updateConfig($config);$this->checkpoint('config_written');
                if (!hash_equals($record['revision_after'],$this->currentRevision())) throw new RuntimeException('저장된 디자인 값의 검증에 실패했습니다.');
                if (!@rename($this->root.'/'.$temporary,$target)) throw new RuntimeException('디자인 CSS 교체에 실패했습니다.');
                $this->checkpoint('published');
                $record['phase']='committed';$this->journal($record);
                $this->checkpoint('committed');
                $this->clearJournal();
                $fields['tomo_design_revision']=$record['revision_after'];
                return ['fields'=>$fields,'clear_files'=>$clearFiles];
            } catch(Throwable $e) {
                if ($journaled) {
                    try { $this->restore($record); }
                    catch(Throwable $restore){error_log('Design recovery pending: '.$restore->getMessage());throw new RuntimeException('디자인 저장 복구가 완료되지 않았습니다. 저장 폴더와 DB 권한을 복구한 뒤 다시 접속해 주세요.',0,$e);}
                } else $this->cleanup($created);
                throw $e;
            }
        });
    }
}
