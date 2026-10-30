---
name: charcoal-gnuboard
description: Design, implement, or review GNUBOARD board skins using pixiv Charcoal design-system principles while preserving the host site's existing visual identity. Use for board lists, posts, forms, comments, responsive states, and accessibility in PHP-based GNUBOARD projects.
---

# Charcoal 원칙을 활용한 그누보드 게시판 스킨

## 우선순위

사용자의 명시적 요구와 프로젝트 지침을 우선한다. 현재 사이트와 스킨의 시각적 정체성, 테마 설정과 기존 기능을 보존하면서 Charcoal의 일관된 컴포넌트 구성과 상태 표현을 참고한다. pixiv의 외형을 그대로 적용하거나 새로운 색상 체계를 강제하지 않는다.

## 작업 순서

1. 해당 프로젝트의 지침과 게시판 스킨 파일, 공통 CSS, 테마 변수, 실제 화면을 살핀다. 목록·상세·글쓰기·댓글과 PC·모바일의 색상, 글꼴, 간격, 테두리, 버튼 및 상호작용을 기록한다. 관리자 화면과 사용자 화면의 톤이 다르면 작업 대상 화면을 기준으로 한다.
2. [Charcoal 공식 문서](https://charcoal-web.pixiv.design/)와 [Storybook](https://pixiv.github.io/charcoal/)에서 필요한 컴포넌트와 상태를 확인한다. 공통적으로 적용할 원칙과 현재 사이트 고유의 표현을 구분한다. 공식 자료를 확인할 수 없으면 확인하지 못했다고 밝히고 프로젝트의 기존 규칙을 기준으로 진행한다.
3. 화면의 정보 우선순위를 정하고 제목, 본문, 부가 정보, 주요 동작이 명확히 구분되도록 한다. 간격과 크기는 기존 스타일에서 반복되는 값을 찾아 일관되게 사용한다. 텍스트와 아이콘, 테두리 및 상태 표시가 배경과 구분되도록 확인한다.
4. 버튼, 입력창, 선택 요소, 탭, 알림, 목록 등은 역할과 상태를 일관되게 표현한다. 기본·호버·키보드 포커스·비활성·오류·빈 상태와 로딩 상태는 실제로 해당되는 곳에 적용한다. 색상만으로 의미를 전달하지 않는다. 클릭 대상과 연결 대상은 각각 button과 a를 사용한다.
5. 그누보드의 스킨 경계와 기존 PHP 변수·폼 필드·권한 검사·토큰·첨부·검색·페이징·에디터 연동을 확인해 유지한다. 프로젝트의 테마 색상 설정을 읽고, CSS는 스킨 범위로 제한하며 기존 공통 스타일과 충돌하지 않도록 한다. 기존 지원 환경을 확인하고 별도 요청이 없으면 PHP·HTML·CSS와 현재 사용 중인 JavaScript로 구현한다. React, npm, CDN 또는 Charcoal 패키지는 필수 조건이 아니다.
6. 모바일에서 텍스트와 버튼이 잘리지 않고 사용 가능한지 확인한다. 내용이 긴 제목, 긴 닉네임, 이미지가 없는 글, 첨부파일, 빈 목록, 오류 및 권한별 화면을 살펴본다. 키보드로 이동·조작하고 포커스가 보이는지, 레이블과 오류 안내가 연결되는지 확인한다.
7. 가능하면 실제 실행 화면을 PC와 모바일로 캡처해 기존 화면과 대조한다. 구현된 동작과 테스트 결과, 확인하지 못한 환경을 보고한다. 목업은 실제 캡처로 표현하지 않는다.

## Charcoal 참조와 라이선스

- 원본: [pixiv/charcoal](https://github.com/pixiv/charcoal), 공식 [문서](https://charcoal-web.pixiv.design/), [Storybook](https://pixiv.github.io/charcoal/).
- Charcoal 저장소의 라이선스는 [Apache License 2.0](https://github.com/pixiv/charcoal/blob/main/LICENSE)이다. 이 스킬은 원본 코드나 이미지 파일을 포함하지 않는다.
- 원본 코드·아이콘 등 자산을 실제로 복사하거나 배포할 때는 해당 파일과 패키지의 라이선스 및 NOTICE를 확인하고, 필요한 라이선스 사본·고지·변경 표시를 함께 제공한다. 참조 링크만으로 해당 의무를 대신하지 않는다.


## 설치

Claude Code에서는 프로젝트의 `.claude/skills/charcoal-gnuboard/` 또는 개인용 `~/.claude/skills/charcoal-gnuboard/`에 이 폴더를 둔다. Claude 앱에서는 사용자 지정 스킬 업로드 기능이 제공되는 경우 이 폴더가 들어 있는 ZIP을 업로드한다. 기능 및 사용 가능 여부는 계정과 제품 설정에 따른다.
