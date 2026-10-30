<?php if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit; ?>
<section id="tomo-member-settings" class="tomo-settings">
<h2>회원가입 승인</h2>
<input type="hidden" name="tomo_members_present" value="1">
<div class="tbl_frm01 tbl_wrap"><table><colgroup><col style="width:170px"><col></colgroup><tbody><tr><th scope="row">가입 방식</th><td>
<label><input type="checkbox" name="cf_tomo_require_approval" value="1" <?=!empty($config['cf_tomo_require_approval'])?'checked':''?>> 새 가입자는 관리자 승인 후 이용</label>
<p class="frm_info">기본환경 탭의 ‘계정생성 가능’도 켜져 있어야 가입 신청을 받을 수 있습니다.</p>
<p class="frm_info">기존 회원과 최고관리자는 그대로 이용할 수 있습니다. 켠 뒤 새로 가입한 회원만 승인 대기로 접수되며, 이메일 인증과 게시판 권한은 별도로 적용됩니다.</p>
<p class="frm_info">설정을 꺼도 이미 대기 중이거나 거절된 계정은 자동 승인되지 않습니다.</p>
<a href="./member_approval.php" class="btn_frmline">가입 승인 관리</a>
</td></tr></tbody></table></div>
</section>
