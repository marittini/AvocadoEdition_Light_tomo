<?php
if (!defined('_GNUBOARD_')) exit;
include_once __DIR__.'/pixiv.lib.php';
// Drop series entries of the posts that no longer exist on this board.
pixiv_series_cleanup($bo_table, $write_table);
