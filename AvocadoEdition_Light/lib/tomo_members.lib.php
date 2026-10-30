<?php
if (!defined('_GNUBOARD_')) exit;

function tomo_member_approval_message(array $member): string
{
    global $config;
    if (($member['mb_id'] ?? '') === ($config['cf_admin'] ?? '') && !empty($member['mb_id'])) return '';
    return match ($member['mb_tomo_approval'] ?? 'approved') {
        'pending'=>'관리자의 가입 승인을 기다리고 있습니다. 승인 후 로그인해 주세요.',
        'rejected'=>'가입 신청이 승인되지 않았습니다. 사이트 관리자에게 문의해 주세요.',
        default=>''
    };
}

/** Existing members remain approved; access levels and email verification are separate. */
function tomo_members_upgrade(): void
{
    global $g5, $is_admin;
    if ($is_admin !== 'super') throw new RuntimeException('최고관리자만 가입 승인 설정을 갱신할 수 있습니다.');
    $column = sql_fetch("SHOW COLUMNS FROM `{$g5['member_table']}` LIKE 'mb_tomo_approval'");
    if (!$column && !sql_query("ALTER TABLE `{$g5['member_table']}` ADD `mb_tomo_approval` ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved'", false)) {
        if (!sql_query("SELECT mb_tomo_approval FROM `{$g5['member_table']}` LIMIT 1", false)) throw new RuntimeException('가입 승인 필드를 만들 수 없습니다. 기존 회원 정보는 유지됩니다.');
    }
}

/** Normalize X/Twitter handles without treating URLs as usernames. */
function tomo_twitter_handle($value): ?string
{
    if (!is_string($value)) return null;
    $value = trim($value);
    if (str_starts_with($value, '@')) $value = substr($value, 1);
    return preg_match('/^[A-Za-z0-9_]{1,15}$/D', $value) ? $value : null;
}

/** Called by a signed-in super administrator, never by public requests. */
function tomo_twitter_upgrade(): void
{
    global $g5, $is_admin;
    if ($is_admin !== 'super') throw new RuntimeException('최고관리자만 회원 필드를 갱신할 수 있습니다.');
    $column = sql_fetch("SHOW COLUMNS FROM `{$g5['member_table']}` LIKE 'mb_tomo_twitter'");
    if (!$column && !sql_query("ALTER TABLE `{$g5['member_table']}` ADD `mb_tomo_twitter` VARCHAR(15) NOT NULL DEFAULT ''", false)) {
        if (!sql_query("SELECT mb_tomo_twitter FROM `{$g5['member_table']}` LIMIT 1", false)) throw new RuntimeException('트위터 아이디 필드를 만들 수 없습니다. 호스팅 업체에 DB 테이블 변경 권한을 확인해 주세요.');
    }
}
