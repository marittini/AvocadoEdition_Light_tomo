# 기능·스킨 개발에 필요한 코드 고르기

모든 경로는 `AvocadoEdition_Light/` 기준이다. 아래 파일만 다른 프로그램에 복사하면 독립 동작한다는 뜻은 아니다. 공통 초기화와 DB/세션/권한 계약이 있는 통합 지점이다.

## 작업별 출발점

| 만들거나 바꿀 것 | 먼저 볼 곳 | 함께 확인할 의존성 | 검증 |
|---|---|---|---|
| 일반 게시판 스킨 | `skin/board/basic/` | `bbs/board.php`, `bbs/list.php`, `bbs/view.php`, `bbs/write.php`, `$list`·`$view`·`$board`·URL 변수 | 목록·글·댓글·비밀글·권한·첨부·페이지 이동 |
| 캐릭터/페어 스킨 | `skin/board/character/` | `_setting.php`, 목록 정렬, `list.character.skin.php`, `list.couple.skin.php`, `proc/`의 이미지 처리와 개별 접근 검사 | 캐릭터/페어 등록·목록·프로필·전신 이미지 변경 |
| 로드비 스킨 | `skin/board/mmb/` | `extend/mmb.lib.php`, `extend/community.config.php`, `action/`, 댓글·즐겨찾기·이모티콘 요청 | 로그 작성·타입별 표시·댓글·즐겨찾기·권한 |
| 메뉴 스킨 | `skin/menu/basic/` 또는 현재 사용 스킨 | `menu.php`, `css.config.php`, 방향별 `menu.*.inc.php`, 관리자 설정 | 네 방향·PC/모바일·하위 메뉴·프레임 링크 |
| 새 관리자 기능 | `adm/_common.php`, `admin.lib.php`, `admin.menu*.php` | `$sub_menu`, `auth_check`, 최고관리자 여부, `check_admin_token`, JSON 오류/성공 응답 | 비로그인·일반회원·위조 토큰 거부, 저장/실패 복구 |
| 폰트·디자인 항목 | `adm/design_form.php`, `lib/tomo_fonts.lib.php` | `adm/design_form_update.php`, `adm/design_form_css.php`, `adm/archive.ajax.php`, 생성 CSS 캐시 버전 | DB 재조회·실제 공개 CSS·미리보기 제한 |
| 업로드/미디어 | `lib/archive.lib.php` | `bbs/write_update.php`, `bbs/write_update_file.php`, SmartEditor UploadHandler, CHEditor upload, `lib/thumbnail.lib.php` | 실제 두 편집기/첨부, 변환·삭제·원본 유지·썸네일 |
| 비공개 파일 | `bbs/archive_file.php` | `install/data-protection.htaccess`, 게시글/내용 참조 및 권한 | Apache 직접 URL·비회원 거부·허용 사용자 표시 |
| 새 설정값 | `lib/tomo_settings.lib.php` | 설정 폼/저장, `install/gnuboard5.sql`의 신규 설치 스키마 | 기본값·추가형 업그레이드·재실행·이전 데이터 유지 |
| 공개 화면 보호 | `head.sub.php`, `js/tomo-protection.js` | 각 HTML 문서의 공통 헤더, 보호 CSS, 환경설정 bit mask | iframe 내부·입력창/편집기 예외·개별 OFF |

## 게시판 스킨 작업 순서

1. 목적과 가장 가까운 스킨을 새 이름으로 복제한다. 캐릭터/로드비를 일반 게시판의 단순 시각 테마로 취급하지 않는다.
2. 관리자에서 테스트 게시판에만 새 스킨을 지정한다. PHP 파일 선두의 `_GNUBOARD_` 접근 차단을 유지한다.
3. 기존 스킨이 받는 변수와 링크·폼 이름을 목록화한다. HTML을 바꿔도 `bo_table`, `wr_id`, 토큰, 업로드 필드와 서버 권한 검사를 유지한다.
4. 외부 입력은 HTML/속성/SQL/URL 문맥에 맞게 처리한다. 클라이언트에서 숨기는 것으로 권한 검사를 대체하지 않는다.
5. 스킨 CSS는 스킨 루트 클래스로 범위를 제한하고 `add_stylesheet()`로 등록한다. 전역 `.ui-btn`, `img`, `table` 재정의는 다른 스킨/디자인 설정을 손상시킬 수 있다.
6. 테스트 게시판에서 글 작성·수정·삭제·댓글·첨부 및 PC/모바일을 확인한 후 적용한다.

## 이번 추가 모듈의 계약

- `tomo_webp_quality()`는 현재 `$config`에서 0~100을 읽으며 누락/잘못된 저장값은 95로 처리한다. 저장 검증은 `tomo_validate_quality()`가 담당한다.
- `archive_webp($source, $destination, ?int $quality = null)`의 생략값은 현재 관리자 품질이다. 명시적 `IMG_WEBP_LOSSLESS` 호출은 내부 호환성을 위해 허용하지만 관리자 UI는 0~100만 제공한다.
- `tomo_validate_fonts()`는 raw 폰트 배열을 검증한다. 각 행은 `id`, `label`, `family`, `css`다. `tomo_post_fonts()`만 그누보드의 SQL-escaped POST를 raw 값으로 바꾼다.
- 기존 디자인 DB에는 선택한 font-family 문자열을 저장한다. 폰트 ID를 `cs_etc_*` 필드에 넣지 않는다. 현재 적용 영역은 기본(3), 메뉴 툴팁(9), 인트로(5)다.
- `tomo_font_css()`는 기존 CSS와 등록 CSS를 합치고 `@import`를 규칙 앞에 둔다. 서버에서 외부 CSS를 다운로드하지 않는다. 폰트 CSS는 기존 커스텀 CSS와 마찬가지로 소유자가 신뢰하는 코드다.
- 보호 설정은 `cf_tomo_protection`의 bit 1=우클릭, 2=이미지 드래그, 4=선택/복사다. 모두 OFF면 공개 스크립트/CSS를 출력하지 않는다. 스킨에 복사 허용 구간이 필요하면 `data-tomo-allow-copy`를 사용한다.

## 호환성과 배포 경계

기존 스킨/라이브러리 API를 바꾸지 않고 작은 연결부를 추가하는 방식이 기본이다. 새 코드에는 `tomo_` 또는 별도 기능 접두사를 쓴다. PHP 8.4 대응과 비공개 파일 보호 때문에 코어·편집기 파일에도 개선판 수정이 있으므로 upstream 업데이트는 diff 비교 후 병합한다.

`plugin/`의 composer 파일이 있다고 프로젝트 전체 Composer 구조로 가정하지 않는다. `extend/` 자동 로딩으로 등록하는 함수는 전 요청에서 전역 이름을 차지한다. 파일명 자연순 정렬에 숨은 의존성을 만들지 말고 필요한 기능 라이브러리를 명시적으로 `require_once`한다.

데이터, DB 비밀번호, 업로드, 테스트 로그인 상태는 Git에 넣지 않는다. 운영 배포는 파일·DB를 함께 백업한 뒤 진행하고, `.htaccess` 보호는 PHP 개발 서버 테스트만으로 완료 판정하지 않는다.
