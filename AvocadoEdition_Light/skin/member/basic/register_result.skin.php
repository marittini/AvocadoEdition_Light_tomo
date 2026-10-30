<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<div id="member_page">

	<h1 class="member-title">
		<strong><?php echo !empty($approval_message) ? '가입 신청 접수' : '계정 생성 완료'; ?></strong>
		<span>《 Complete Community Account 》</span>
	</h1>

	<div class="member-contents register-pannel">

		<?php if (!empty($approval_message)) { ?>
		<section>
			<h2>가입 신청 접수</h2>
			<div class="theme-box">
				<p><?php echo get_text($approval_message); ?></p>
				<p>신청한 아이디: <strong><?php echo get_text($mb['mb_id']); ?></strong></p>
			</div>
		</section>
		<?php } else { ?>
		<section>
			<h2>정보관리 안내</h2>
			<div class="theme-box">
				<p><strong class="txt-point">"<?php echo get_text($mb['mb_name']); ?>"</strong>님의 <strong><?=$config['cf_title']?></strong> 가입이 완료되었습니다.</p>
				<p>회원님의 비밀번호는 아무도 알 수 없는 암호화 코드로 저장되므로 안심하셔도 좋습니다.</p>
				<p>아이디, 비밀번호 분실시에는 관리자에게 문의해 주세요.</p>
			</div>
		</section>


		<?php } ?>
		<div class="ui-button-box txt-center">
			<button class="ui-btn point" onclick="location.href='<?=G5_URL?>';">메인화면으로</button>
		</div>
	</div>
</div>
