// 다이어리(diary): 공통 에디터 작성, 목록에 본문 미노출, 연도(여러 해)·월 탐색, 페이지, 위조 요청.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD. 글 날짜를 여러 해로 옮기기 위해 TEST_SQL(예: "mysql -uroot g5")이 필요하다.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }),
    b = await chromium.launch(),
    c = await b.newContext();
  await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  const errors = [];
  p.on("pageerror", (e) => errors.push(e.message));
  p.on("dialog", (d) => d.accept());
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").first().click();
  await p.waitForLoadState();

  const id = "dy" + Date.now().toString().slice(-7);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("다이어리 테스트");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(400);
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("다이어리 테스트");
  await p.locator("[role=tab][aria-controls=anc_004]").click();
  await p.locator("[name=bo_skin]").selectOption("diary");
  await p.locator("[role=tab][aria-controls=anc_003]").click();
  await p.locator("[name=bo_use_secret]").selectOption("1");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(700);
  sql(`UPDATE ${prefix}board SET bo_page_rows=2, bo_mobile_page_rows=2 WHERE bo_table='${id}';`);
  const board = base + "/bbs/board.php?bo_table=" + id;

  const dates = ["2025-12-31 23:00:00", "2026-03-10 10:00:00", "2026-03-20 10:00:00", "2026-10-05 10:00:00", "2027-01-01 09:00:00", "2028-02-29 09:00:00"];
  for (const [i, when] of dates.entries()) {
    await p.goto(base + "/bbs/write.php?bo_table=" + id);
    await p.locator("#wr_subject").fill("기록 " + (i + 1));
    if (i === 3) await p.locator("input[name=secret]").check();
    const editor = p.locator(".ProseMirror").first();
    assert.equal(await editor.count(), 1, "공통 DHTML 에디터");
    await editor.click();
    await p.keyboard.type("본문비밀문장 " + i);
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    const wr = new URL(p.url()).searchParams.get("wr_id");
    sql(`UPDATE ${prefix}write_${id} SET wr_datetime='${when}' WHERE wr_id=${Number(wr)};`);
  }

  // 기본: 가장 최근 연도(2028), 본문은 목록에 나오지 않는다
  await p.goto(board);
  assert.equal(await p.locator(".diary-year-title").innerText(), "2028");
  assert.equal((await p.content()).includes("본문비밀문장"), false, "목록에 본문 없음");
  // 이전 해로 이동: 2028 → 2027 → 2026 → 2025
  for (const year of ["2027", "2026", "2025"]) {
    await Promise.all([p.waitForURL(new RegExp("yr=" + year)), p.locator(".diary-year-step").first().click()]);
    assert.equal(await p.locator(".diary-year-title").innerText(), year);
  }
  // 2026: 글이 있는 달만 링크, 3월 필터 2편, 한 페이지 2편이라 1월~12월 전체는 2쪽
  await p.goto(board + "&yr=2026");
  assert.deepEqual(await p.locator(".diary-months a").allInnerTexts(), ["Mar", "Oct"]);
  assert.equal(await p.locator(".diary-entries li").count(), 2);
  assert.equal(await p.locator(".pg_wrap").count(), 1);
  await p.goto(board + "&yr=2026&mo=3");
  assert.deepEqual(await p.locator(".diary-entries .title").allInnerTexts(), ["기록 3", "기록 2"]);
  await p.goto(board + "&yr=2026&mo=10");
  assert.equal(await p.locator(".diary-entries .material-icons").innerText(), "lock");
  // 없는 연도·월은 가장 최근 연도로
  await p.goto(board + "&yr=1999&mo=13");
  assert.equal(await p.locator(".diary-year-title").innerText(), "2028");

  // 읽기: 본문과 서명, 목록으로 돌아가면 같은 연도·월
  await p.goto(board + "&yr=2026&mo=3");
  await Promise.all([p.waitForURL(/wr_id=/), p.locator(".diary-entries a").first().click()]);
  assert.match(await p.locator("#bo_v_con").innerText(), /본문비밀문장 2/);
  assert.equal(await p.locator(".diary-sign").count(), 0, "서명 없음");
  // 제목 수정: 날짜(연·월)는 그대로, 목록에 새 제목
  await Promise.all([p.waitForURL(/w=u/), p.locator(".diary-view-actions a", { hasText: "수정" }).click()]);
  assert.equal(await p.locator("#wr_subject").inputValue(), "기록 3");
  await p.locator("#wr_subject").fill("고친 제목");
  await p.locator("#btn_submit").click();
  await p.waitForLoadState();
  await p.goto(board + "&yr=2026&mo=3");
  assert.deepEqual(await p.locator(".diary-entries .title").allInnerTexts(), ["고친 제목", "기록 2"]);
  assert.deepEqual(await p.locator(".diary-entries time").allInnerTexts(), ["03.20", "03.10"], "날짜만, 요일 없음");
  await Promise.all([p.waitForURL(/wr_id=/), p.locator(".diary-entries a").first().click()]);
  await Promise.all([p.waitForURL(/mo=3/), p.locator(".diary-view-actions a", { hasText: "목록" }).click()]);

  // 토큰 없는 글쓰기는 막는다
  const forged = await c.request.post(base + "/bbs/write_update.php", { form: { w: "", bo_table: id, wr_subject: "x", wr_content: "y", html: "html1" } });
  assert((await forged.text()).includes("글쓰기 요청을 확인할 수 없습니다"));

  assert.deepEqual(errors, []);
  console.log("PASS diary editor, no excerpt, years 2025-2028, months, paging, secret, view, title edit, forged write");
  await b.close();
})().catch((e) => { console.error(e); process.exit(1); });
