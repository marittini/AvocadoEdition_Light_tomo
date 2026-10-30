<?php
if (!defined('_GNUBOARD_')) exit;

/** Preserve the uploaded original until WebP encoding has succeeded. Never resize implicitly. */
function archive_webp(string $source, string $destination, int $quality = IMG_WEBP_LOSSLESS): array
{
    $info = @getimagesize($source);
    if (!$info) throw new RuntimeException('이미지 파일을 읽을 수 없습니다.');
    $type = $info[2];
    // Animated GIF/WebP and existing WebP are deliberately preserved without re-encoding.
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return ['converted'=>false, 'path'=>$source];
    if (!function_exists('imagewebp') || !(gd_info()['WebP Support'] ?? false)) {
        throw new RuntimeException('서버가 WebP 변환을 지원하지 않습니다. 원본은 변경하지 않았습니다.');
    }
    // Decompression is governed by pixel size as well as input bytes.
    $estimate = $info[0] * $info[1] * 10 + 16777216;
    $limit = ini_parse_quantity(ini_get('memory_limit'));
    if ($info[0] * $info[1] > 24000000 || ($limit > 0 && memory_get_usage(true) + $estimate > $limit)) {
        throw new RuntimeException('이미지 해상도가 서버의 안전한 처리 범위를 넘습니다. 원본은 변경하지 않았습니다.');
    }
    if ($type === IMAGETYPE_JPEG && !function_exists('exif_read_data') && str_contains((string)file_get_contents($source, false, null, 0, 262144), "Exif\0\0")) throw new RuntimeException('사진 회전 정보 지원이 없어 원본을 보관합니다.');
    $image = $type === IMAGETYPE_JPEG ? @imagecreatefromjpeg($source) : @imagecreatefrompng($source);
    if (!$image) throw new RuntimeException('이미지 디코딩에 실패했습니다.');
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $orientation = (int)(@exif_read_data($source)['Orientation'] ?? 1);
        if (in_array($orientation, [2,4,5,7], true)) imageflip($image, IMG_FLIP_HORIZONTAL);
        $degrees = [3=>180,4=>180,5=>-90,6=>-90,7=>90,8=>90][$orientation] ?? 0;
        if ($degrees) $image = imagerotate($image, $degrees, 0);
    }
    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $temporary = $destination.'.'.bin2hex(random_bytes(8)).'.tmp';
    try {
        if (!imagewebp($image, $temporary, $quality) || !is_file($temporary) || filesize($temporary) === 0 || (@getimagesize($temporary)[2] ?? 0) !== IMAGETYPE_WEBP) {
            throw new RuntimeException('WebP 파일 생성에 실패했습니다.');
        }
        if (!rename($temporary, $destination)) throw new RuntimeException('변환 파일 저장에 실패했습니다.');
        chmod($destination, defined('G5_FILE_PERMISSION') ? G5_FILE_PERMISSION : 0644);
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
    return ['converted'=>true,'path'=>$destination,'bytes'=>filesize($destination)];
}

/** One disjoint classification per file. Logical file sizes, not hosting-provider quota. */
function archive_disk_category(string $relative): string
{
    if (preg_match('~^data/(cache|session|tmp|preview)/~', $relative)) return '임시·캐시·세션';
    if (preg_match('~^data/(backup|backups)/~', $relative)) return '로컬 백업';
    if (preg_match('~^data/(file|editor|site|member)/~', $relative)) {
        if (preg_match('~(?:^|/)thumb-|\.webp$~i', $relative)) return 'WebP·썸네일';
        if (preg_match('~\.(png|jpe?g|gif|bmp|avif)$~i', $relative)) return '원본 이미지';
        return '기타 첨부·장식';
    }
    return '프로그램·기타';
}

function archive_disk_start(string $root): array
{
    $root = realpath($root);
    if (!$root || !is_dir($root)) throw new RuntimeException('측정 루트를 확인할 수 없습니다.');
    return ['root'=>$root,'queue'=>[['path'=>'','offset'=>0]],'bytes'=>0,'files'=>0,'categories'=>[], 'errors'=>0,'skipped_links'=>0,'started'=>time(),'finished'=>null];
}

/** Bounded requests; resumable directory offsets; never follows symlinks or leaves the root. */
function archive_disk_step(array $state, int $budget = 600): array
{
    $deadline = microtime(true) + 0.6;
    $processed = 0;
    while ($state['queue'] && $processed < $budget && microtime(true) < $deadline) {
        $entry = array_shift($state['queue']);
        $path = $state['root'].($entry['path'] !== '' ? '/'.$entry['path'] : '');
        if (is_link($path)) { $state['skipped_links']++; continue; }
        $real = realpath($path);
        if (!$real || ($real !== $state['root'] && !str_starts_with($real, $state['root'].'/'))) { $state['errors']++; continue; }
        $dir = @opendir($path);
        if (!$dir) { $state['errors']++; continue; }
        $position = 0;
        $done = true;
        while (($name = readdir($dir)) !== false) {
            if ($position++ < $entry['offset']) continue;
            if ($name === '.' || $name === '..') continue;
            $relative = ltrim($entry['path'].'/'.$name, '/');
            $file = $state['root'].'/'.$relative;
            $processed++;
            if (is_link($file)) $state['skipped_links']++;
            elseif (is_dir($file)) $state['queue'][] = ['path'=>$relative,'offset'=>0];
            elseif (is_file($file)) {
                $bytes = @filesize($file);
                if ($bytes === false) $state['errors']++;
                else {
                    $category = archive_disk_category($relative);
                    $state['categories'][$category] = ($state['categories'][$category] ?? 0) + $bytes;
                    $state['bytes'] += $bytes; $state['files']++;
                }
            } else $state['errors']++;
            if ($processed >= $budget || microtime(true) >= $deadline) {
                array_unshift($state['queue'], ['path'=>$entry['path'],'offset'=>$position]);
                $done = false; break;
            }
        }
        closedir($dir);
    }
    if (!$state['queue']) $state['finished'] = time();
    return $state;
}


function archive_convert_saved_upload(string $source): array
{
    $info = @getimagesize($source);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return ['path'=>$source,'converted'=>false];
    $destination = $source.'.webp';
    $result = archive_webp($source, $destination);
    // Original remains beside its derivative. Record the relationship for backup/deletion.
    $metadata = ['original'=>basename($source),'webp'=>basename($destination),'created'=>time(),'policy'=>'lossless-preserve-original'];
    if (file_put_contents($destination.'.archive.json', json_encode($metadata), LOCK_EX) === false) {
        @unlink($destination);
        throw new RuntimeException('원본 연결 정보를 기록하지 못했습니다.');
    }
    return $result;
}

function archive_delete_companions(string $path): void
{
    $metadata = $path.'.archive.json';
    if (!is_file($metadata)) return;
    $info = json_decode((string)file_get_contents($metadata), true);
    $original = $info['original'] ?? '';
    if (is_string($original) && $original !== '' && basename($original) === $original && $original !== basename($path)) @unlink(dirname($path).'/'.$original);
    @unlink($metadata);
}
