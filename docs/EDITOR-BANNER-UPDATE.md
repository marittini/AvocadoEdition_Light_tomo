# 편집기 새 배치 · 이미지 묶음 · 링크(배너) 게시판 · 가입 승인 점검 외

## 1. 편집기(DHTML, Tiptap) 새 배치

The editor was cluttered: three rows plus eight icon-only panel toggles ("추가 서식", "루비·스포일러", "표 편집",
"이미지 편집", …), so many tools were hidden behind unlabeled icons. It now follows the BearEditor demo layout
(babyfirebear.naru.pub), redone with the existing Tiptap engine and the admin palette. No code was copied.

- **Mode tabs** at the top: `편집` / `HTML`. Switching back to 편집 applies edited HTML. 넓게 보기 sits on the right.
- **One grouped toolbar** (sticky while scrolling; groups separated by a thin line):
  1. 문단 스타일 · 글꼴 · 글자 크기
  2. 굵게 · 기울임 · 밑줄 · 취소선 · 색상 (palette for text colour and highlight, a custom colour, and 없애기)
  3. 왼쪽 · 가운데 · 오른쪽 · 양쪽 정렬
  4. 줄 간격 · 자간 (pick from a list)
  5. 글머리 목록 · 번호 목록 · 체크리스트 · 표 삽입
  6. 인용 (기본 / 상자 / 배경 / 말풍선 / 큰 따옴표, with previews) · 구분선 (실선 / 점선 / 이중선 / 짧은 선 / 굵은 선) ·
     접기·펼치기 · 루비 · 스포일러 블러
  7. 링크 · 이미지 (image window: 이미지 첨부, upload to the list only, pick images to insert, group, remove) ·
     이미지 묶음 · 동영상 · 기호
  8. 실행 취소 · 다시 실행 · 서식 지우기 · 더보기 (위/아래 첨자, 코드 블록, 문단 아래 여백, 찾기·바꾸기) · 찾기
- **Contextual bar**: it appears by itself under the toolbar.
  - Click an image: size, alt text, alignment, 원래 크기로.
  - Click inside a table: rows and columns, merge and split, header row and column, cell colour, 표 삭제.
  - Click an image group: 2열 / 3열 / 4열, 묶음에 그림 추가, 묶음 풀기.
- **Footer**: `N단어 · N자`.
- **Phones**: the groups flow together, so there are fewer rows. Buttons are 44px tall.

Files: `plugin/editor/tiptap/src/editor.js` (built into `editor.bundle.js` with `npm run build`), `editor.css`, and
`editor.lib.php` (asset URLs now versioned by file hash).

## 2. 이미지 묶음 (image grid)

Images side by side, like Notion columns, without using tables.

- Make a group in either of two ways:
  - **이미지 묶음 → 그림 올려서 묶기** (choose 2, 3 or 4 per row).
  - Select images in the text and use **선택한 그림 묶기**, or **선택 이미지 묶어 넣기** in the image window.
- Each image keeps its own height, so rows size themselves automatically and nothing is cropped.
- **Read pages:** the group is a CSS grid (`<div class="tomo-grid tomo-grid-3">`). On screens up to 640px it becomes
  one image per row, in the original order. The rules are in `css/editor-content.css`, so they apply in every skin.
- **Saving:** the server HTML filter already keeps `class` attributes, so groups and the quote and divider styles are
  saved as they are.

## 3. 링크 · 배너 게시판 (`skin/board/link`)

This replaces the link board from `link.zip`. It reads the same fields (name `wr_1`, banner image URL `wr_link1`,
link `wr_link2`, order `wr_10`), so an existing board only needs its skin switched to `link`.

- **Add a banner:** name, link, banner image (an image address or an uploaded file), and a tooltip with its own
  background and text colour. A live preview shows the banner and tooltip while you type.
- **Tooltip:** a speech bubble above the banner on hover or keyboard focus. It works on the home screen too.
- **내 배너** (admin only):
  - Shown at the top with a **배너 코드 복사** button (`<a href=…><img src=…></a>`).
  - An uploaded image is served from a fixed address (`banner.php`). Uploading a new image never breaks the code
    already pasted on other sites, and the image stays visible even when the site is closed.
- **배너 관리** (admin only):
  - Drag or use ↑ ↓ to reorder.
  - Edit name, link, image URL, tooltip, category and 내 배너 in one table.
  - Display settings: banners per row on PC and on phones (or natural size), and **홈 화면에도 보이기**.
- **Home screen:**
  - With 홈 화면에도 보이기 on, banners appear under the home content.
  - To place them yourself, write `[배너]` in the home content (관리자 > 메인 화면). For one board, write `[배너:게시판아이디]`.
- **Safety:** `javascript:` and other unsafe addresses are dropped, colours are validated, and saves need a token.

## 4. Other changes

- **ottreview**: the thumbnail list shows 2 columns on phones (3:4 thumbnails).
- **pixiv**: the type tabs are on two rows, `전체 · 시리즈` / `일러스트 · 만화 · 소설`.
- **otaku**
  - List categories are centred round buttons, like the other boards.
  - The post header reads category / pair name / `A • B`.
  - On phones the header, 원본/AU, tabs and person tabs are centred.
  - Bubble tail: a flush tail left a 1px gap at 125% and 150% display scaling. The tail now overlaps the bubble by
    1.5px. A bubble with no character colour gets an opaque colour mixed from the paper colour, so the overlap
    never shows.
- **크게 보기 (enlarged image window)**: with right-click protection on, the image itself also blocks right-click,
  dragging and long-press saving. Protection no longer waits for the deferred script. Phones also block long-press
  saving on protected pages.
- **Member approval (가입 승인)**, checked end to end:
  - A pending member who verifies their email now hears that they must wait for approval (it used to say they
    could log in).
  - The admin menu shows the pending count, e.g. `가입 승인 (3)`.
  - On a closed site (사이트 오픈 off), the signup pages (when signup is enabled), password recovery, email
    verification and the captcha image now open. Before, they all redirected to login, so nobody could sign up.
  - The login page links to 회원가입 when signup is enabled, and to 아이디 · 비밀번호 찾기.

## Tests

- **New:** `editor_blocks_smoke`, `link_board_smoke`, `closed_site_account_smoke`, `view_image_protection_smoke`.
- **Updated for the new editor layout:** `tiptap_features_smoke`, `editor_annotations_smoke`, `admin_tools_smoke`.
- **Extended:** `member_approval_smoke` (pending count).
