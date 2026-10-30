# 웹폰트·품질·보호 기능 검증 기록

검증 환경: 격리된 PHP 8.4.27(GD WebP·mysqli·mbstring·EXIF), MariaDB 11.8, Chromium/Playwright. 운영 데이터와 별개의 새 설치를 사용했다. 비밀번호·DB 설정·업로드·테스트 fixture는 Git에 포함하지 않는다.

## 수행한 검증

- 새 설치와 기본 품질95/보호OFF, 실제 DB 임시 테이블의 반복 마이그레이션, 기존 설정 보존, 품질0 및 기존 설정 재실행 보존, 소유자 제한.
- 폰트 등록/재조회, 세 적용 영역의 선택, 직접 입력, 기존 CSS 보존, 사용 중 삭제 차단(UI/서버), 누락된 적용 필드에 대한 DB 값 참조, 실제 공개 페이지의 폰트 파일 로드.
- CSS `@import`를 `@font-face` 앞으로 배치하고 URL·문자열·주석 안의 구분 기호를 보존.
- WebP 기본95 및 변경값이 GD 결과와 바이트 단위로 일치. 썸네일의 새 품질 적용과 기존 캐시 유지. 투명도·기존 WebP/애니메이션 GIF 제외·실패 시 원본 유지·삭제 동작의 기존 단위 테스트 통과.
- SmartEditor와 CHEditor 각각 실제 HTTP 이미지 업로드 결과가 관리자에서 저장한 품질73의 인코딩 결과와 SHA-256 해시 일치.
- 보호 항목별 저장/독립 적용/OFF 복원, 공개 iframe 내부, 입력창·편집기·허용 영역 예외, 관리자 화면 제외.
- 잘못된 품질과 위조 토큰 거부, 기존 관리자 저장/실패 입력 보존, 격리 미리보기, 파일 용량 측정.
- 기존 게시판 그룹/게시판 생성, 첨부 업로드·표시·권한 게이트·수정·삭제 및 편집기 업로드 회귀.
- 실제 PHP 관리자·공개 화면 7개 경로를 320/390/768/1024/1440px에서 검사. 추가 설정 폼 2개 경로도 같은 5개 너비에서 별도 캡처. 문서 가로 넘침 없음.
- 전체 PHP 파일 구문 검사, 추가 JavaScript 구문 검사와 `git diff --check`.

CHEditor 경로에서 PHP 8이 허용하지 않는 기존 `defined(G5_PUNYCODE)` 호출을 발견해 `defined('G5_PUNYCODE')`로 수정했다. 업로드 테스트를 위해 필요한 작은 호환성 수정이다.

## 재현

일반 단위 검사:

```sh
php tests/tomo_settings_test.php
php tests/archive_test.php
```

실제 DB에 연결하되 연결 범위의 TEMPORARY 테이블만 사용하는 업그레이드 검사:

```sh
TEST_DB_HOST=... MARIADB_DATABASE=... MARIADB_USER=... MARIADB_PASSWORD=... php tests/tomo_upgrade_test.php
```

Playwright 검사는 **일회용 설치 사이트**에서만 실행한다. 환경설정/등록 폰트를 실제로 바꾼다.

```sh
TEST_URL=... TEST_USER=... TEST_PASSWORD=... SCREENSHOT_DIR=... node tests/tomo_settings_smoke.cjs
```

`tests/editor_quality_smoke.cjs`는 설치 사이트에 설정된 편집기에 맞춰 `TEST_EDITOR=smarteditor2` 또는 `cheditor5`를 지정하고, `TEST_IMAGE`에 PNG, `TEST_EXPECTED_WEBP`에 동일 PNG를 `archive_webp(..., 73)`로 인코딩한 비교 파일을 지정한다. 이 검사는 관리자 품질을73으로 변경한다. 테스트 뒤 설정을 원래 값으로 복구한다. 외부 폰트 서버는 사용하지 않고 로컬 테스트 폰트로 로딩을 확인한다.

## 한계

닷홈 실서버, 실제 휴대폰 기종, 외부 웹폰트 공급자의 CORS·가용성, 모든 사용자 스킨·커스텀 코드, Apache rewrite 직접 URL 정책은 이번에 검증하지 않았다. 기존 선택 필드 관련 PHP 경고는 서버 로그에 남을 수 있으며 화면에는 노출하지 않았다. 브라우저 보호는 콘텐츠 권한/암호화가 아니며 완전한 복제 방지를 보장하지 않는다.
