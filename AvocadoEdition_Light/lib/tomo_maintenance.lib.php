<?php
if (!defined('_GNUBOARD_')) exit;

/** Remove only the documented generated files; never recurse or follow links. */
function tomo_clean_generated_files(string $directory, string $kind, string $currentSession = ''): array
{
    if (!in_array($kind, ['session','cache'], true)) throw new InvalidArgumentException('정리 항목을 확인해 주세요.');
    $result = ['deleted'=>0,'failed'=>0,'skipped'=>0,'error'=>''];
    $handle = @opendir($directory);
    if (!$handle) { $result['error']='정리 폴더를 열 수 없습니다. 폴더 쓰기 권한을 확인해 주세요.';return $result; }
    try {
        while (($name = readdir($handle)) !== false) {
            if ($kind === 'session' ? !preg_match('/^sess_[a-zA-Z0-9,-]+$/D', $name) : !preg_match('/^latest-.+\.php$/D', $name)) continue;
            $path = $directory.'/'.$name;
            if (is_link($path) || !is_file($path)) { $result['skipped']++;continue; }
            if ($kind === 'session') {
                $modified = @filemtime($path);
                if ($name === 'sess_'.$currentSession || $modified === false || $modified > time()-21600) { $result['skipped']++;continue; }
            }
            if (@unlink($path)) $result['deleted']++;
            else $result['failed']++;
        }
    } finally { closedir($handle); }
    return $result;
}
