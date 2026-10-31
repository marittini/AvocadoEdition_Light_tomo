// 페어 프로필(custom_skin/otaku): 페어 작성(사진·썸네일·미니), 반복 행, 관계 연결, AU(답글) 전환·사진 대체,
// AU 주소 이동, 목록 카드, 위조 요청, 삭제 정리.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_IMAGE(jpg/png 아무 이미지), TEST_SQL(예: "mysql -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    image = process.env.TEST_IMAGE,
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    c = await b.newContext();
  await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  const errors = [],
    dialogs = [];
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => { dialogs.push(d.message()); d.accept(); });
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").first().click();
  await p.waitForLoadState();

  const id = "ot" + Date.now().toString().slice(-7);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("페어 테스트");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(400);
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("페어 테스트");
  await p.locator("[role=tab][aria-controls=anc_004]").click();
  await p.locator("[name=bo_skin]").selectOption("custom_skin/otaku");
  await p.locator("[role=tab][aria-controls=anc_003]").click();
  await p.locator("[name=bo_upload_count]").fill("5");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(700);
  const board = base + "/bbs/board.php?bo_table=" + id;

  async function addRow(list, values, preset) {
    await list.locator(preset ? `[data-add="${preset}"]` : "[data-add].point").click();
    const fields = list.locator(".otaku-row").last().locator("input[type=text], textarea, select");
    for (const [i, v] of values.entries()) {
      if (v === null) continue;
      const f = fields.nth(i);
      if ((await f.evaluate((e) => e.tagName)) === "SELECT") await f.selectOption({ label: v });
      else await f.fill(v);
    }
  }
  async function save() {
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    return new URL(p.url());
  }

  // 1. 관계에 이어질 다른 페어
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  assert.equal(await p.locator("[data-tab]").count(), 3, "쓰기 화면 탭 3개");
  await p.locator("[name='p[0][name]']").fill("한여름");
  await p.locator("[name='p[1][name]']").fill("민도윤");
  await p.locator("input[name='bf_file[0]']").setInputFiles(image);
  const friend = (await save()).searchParams.get("wr_id");
  assert.ok(friend, "첫 페어 저장");

  // 2. 본 페어: 페어 이름, 사진·썸네일·미니, 정보·소개, 관계, 연표
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=otaku_title]").fill("GEAS");
  for (const slot of [0, 1, 2, 3, 4]) await p.locator(`input[name='bf_file[${slot}]']`).setInputFiles(image);
  assert.ok(await p.locator("[data-preview='2'] img").count(), "썸네일 미리보기");
  await p.locator("[name='p[0][name]']").fill("윤서하");
  await p.locator("[name='p[1][name]']").fill("강이안");
  await p.locator("[name='p[0][quote]']").fill("말로 하면 사라질 것 같아서.");
  const first = p.locator(".otaku-person-edit").first();
  await addRow(first.locator("[data-list]").nth(0), [null, "3월 14일"], "생일");
  await addRow(first.locator("[data-list]").nth(0), [null, "163 cm"], "키");
  // 순서 바꾸기: 키를 위로
  await first.locator("[data-list]").nth(0).locator(".otaku-row").nth(1).locator("[data-move='-1']").click();
  await addRow(first.locator("[data-list]").nth(1), [null, "조용하지만 잘 관찰한다."], "성격");
  await addRow(first.locator("[data-list]").nth(1), [null, "지울 줄"], "외모");
  await first.locator("[data-list]").nth(1).locator(".otaku-row").last().locator("[data-remove]").click();
  await p.locator("[data-tab=relation]").click();
  assert.equal(await p.locator("[data-panel=profile]").isHidden(), true, "탭 전환");
  assert.equal(await p.locator("[data-name-echo='0']").textContent(), "윤서하", "관계 제목에 이름");
  await p.locator("[name=otaku_bond]").fill("같은 반, 같은 도서실 창가.");
  await addRow(p.locator(".otaku-rel-edit").nth(0).locator("[data-list]"), ["한여름 · 민도윤", null, "친구", "편지를 주고받는다."]);
  await addRow(p.locator(".otaku-rel-edit").nth(1).locator("[data-list]"), ["직접 입력", "서지후", "라이벌", ""]);
  await p.locator("[data-tab=timeline]").click();
  await addRow(p.locator("[data-panel=timeline] [data-list]"), ["2021.03.02", "전학", "도서실부터 찾았다.", "윤서하"]);
  await addRow(p.locator("[data-panel=timeline] [data-list]"), ["2021.06.03", "빌린 책", "", "두 사람"]);
  const main = (await save()).searchParams.get("wr_id");
  assert.ok(main, "본 페어 저장");

  // 3. 읽기: 프로필 · 관계 · 연표
  await p.goto(board + "&wr_id=" + main);
  const v0 = p.locator("[data-version='0']");
  assert.equal(await p.locator("[data-version-title='0'] .otaku-title").textContent(), "GEAS");
  assert.match(await v0.locator(".otaku-facts").first().innerText(), /키\s+163 cm\s+생일\s+3월 14일/, "행 순서 바꾸기 반영");
  assert.equal(await v0.locator(".otaku-person").first().locator(".otaku-text").count(), 1, "지운 행은 저장 안 됨");
  assert.equal(await v0.locator(".otaku-photo img").count(), 2, "인물 사진 2장");
  await v0.locator("[data-tab=relation]").click();
  assert.equal(await v0.locator("[data-panel=relation]").isVisible(), true);
  assert.equal(await v0.locator(`.otaku-rels a[href*='wr_id=${friend}']`).count(), 1, "이어진 글 링크");
  assert.equal(await v0.locator(".otaku-rels img").count(), 1, "이어진 글 사진");
  await v0.locator("[data-tab=timeline]").click();
  assert.match(await v0.locator(".otaku-tl").innerText(), /빌린 책\s*윤서하 · 강이안/, "두 사람의 일");

  // 4. 위조 요청은 막힌다
  const forged = await p.request.post(base + "/bbs/write_update.php", {
    multipart: { w: "", bo_table: id, wr_subject: "x", otaku_token: "bad", "p[0][name]": "a", "p[1][name]": "b" },
  });
  assert.match(await forged.text(), /글쓰기 요청을 확인할 수 없습니다/, "토큰 없는 요청 거부");

  // 5. AU: 원본 내용이 복사된 채 시작, 바꾼 부분만 저장
  await p.goto(base + `/bbs/write.php?w=r&bo_table=${id}&wr_id=${main}`);
  assert.equal(await p.locator("[name='p[0][name]']").inputValue(), "윤서하", "AU는 원본을 복사");
  assert.equal(await p.locator("input[name='bf_file[2]']").count(), 0, "AU에는 썸네일 칸 없음");
  await p.locator("input[name=wr_subject]").fill("현대 AU");
  await p.locator("[name='p[0][line]']").fill("27 · 편집자");
  const au = (await save()).searchParams.get("au");
  assert.ok(au, "AU 저장 후 원본에서 AU가 열림");
  assert.equal(await p.locator(`[data-version='${au}']`).isVisible(), true);
  assert.match(await p.locator(`[data-version='${au}'] .otaku-line`).first().textContent(), /편집자/);
  assert.equal(await p.locator(`[data-version='${au}'] .otaku-photo img`).count(), 2, "AU 사진은 원본 사진으로 대체");

  // AU ↔ 원본: 보던 탭 유지, 주소 갱신
  await p.locator(`[data-version='${au}'] [data-tab=timeline]`).click();
  await p.locator("[data-version-link='0']").click();
  assert.equal(await v0.locator("[data-panel=timeline]").isVisible(), true, "같은 탭 유지");
  assert.equal(new URL(p.url()).searchParams.get("au"), null);
  assert.equal(await p.locator("[data-version-tools='0']").isVisible(), true, "원본 수정 버튼");

  // AU 글 주소로 들어오면 원본에서 AU를 연다
  await p.goto(board + "&wr_id=" + au);
  assert.equal(new URL(p.url()).searchParams.get("wr_id"), main);
  assert.equal(new URL(p.url()).searchParams.get("au"), au);

  // 6. 목록: 원본만 카드로, 썸네일·미니·AU 수
  await p.goto(board);
  assert.equal(await p.locator(".otaku-card").count(), 2, "AU는 카드로 나오지 않음");
  const card = p.locator(`.otaku-card[href*='wr_id=${main}']`);
  assert.equal(await card.locator(".otaku-card-minis img").count(), 2, "미니 캐릭터 2명");
  assert.match(await card.innerText(), /GEAS[\s\S]*윤서하 · 강이안[\s\S]*AU 1/);
  assert.equal(await p.locator(`.otaku-card[href*='wr_id=${friend}'] .otaku-card-pics img`).count(), 1, "썸네일 없으면 인물 사진");
  await p.setViewportSize({ width: 390, height: 800 });
  const cols = await p.evaluate(() => getComputedStyle(document.querySelector(".otaku-cards")).gridTemplateColumns.split(" ").length);
  assert.equal(cols, 1, "모바일 1열");
  await p.setViewportSize({ width: 1280, height: 800 });
  assert.equal(await p.evaluate(() => getComputedStyle(document.querySelector(".otaku-cards")).gridTemplateColumns.split(" ").length), 3, "PC 3열");

  // 7. AU 삭제 → 스킨 테이블 정리
  await p.goto(board + `&wr_id=${main}&au=${au}`);
  await p.locator(`[data-version-tools='${au}'] a.etc`).click();
  await p.waitForLoadState();
  const left = sql(`SELECT COUNT(*) FROM ${prefix}otaku_data WHERE bo_table='${id}';`);
  assert.match(left, /\b2\b/, "AU 데이터 삭제");

  assert.deepEqual(errors, [], "자바스크립트 오류 없음");
  console.log("otaku smoke ok", id);
  await b.close();
})().catch((e) => { console.error(e); process.exit(1); });
