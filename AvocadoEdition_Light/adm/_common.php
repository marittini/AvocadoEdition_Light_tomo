<?php
define('G5_IS_ADMIN', true);
include_once ('../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
if ($is_admin === 'super') {
    try { tomo_twitter_upgrade(); } catch (Throwable $e) { $tomo_twitter_upgrade_error = $e->getMessage(); }
}
?>