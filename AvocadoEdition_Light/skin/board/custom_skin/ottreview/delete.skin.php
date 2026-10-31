<?php
if (!defined('_GNUBOARD_')) exit;
// The engine handles the parent. Its default cleanup omits attachments on child posts.
$ott_files = sql_query("SELECT f.wr_id, f.bf_file FROM {$g5['board_file_table']} f JOIN {$write_table} w ON w.wr_id=f.wr_id WHERE f.bo_table='".sql_real_escape_string($bo_table)."' AND w.wr_parent=".(int)$write['wr_id']." AND w.wr_is_comment=1", false);
while ($ott_files && $ott_file = sql_fetch_array($ott_files)) {
    $name = $ott_file['bf_file'];
    if ($name !== '' && basename($name) === $name) {
        $path = G5_DATA_PATH.'/file/'.$bo_table.'/'.$name;
        archive_delete_companions($path);
        @unlink($path);
        delete_board_thumbnail($bo_table, $name);
    }
    sql_query("DELETE FROM {$g5['board_file_table']} WHERE bo_table='".sql_real_escape_string($bo_table)."' AND wr_id=".(int)$ott_file['wr_id']." AND bf_file='".sql_real_escape_string($name)."'");
}
