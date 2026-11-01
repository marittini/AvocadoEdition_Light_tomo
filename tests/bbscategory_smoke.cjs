// 말머리 BBS 게시판(bbscategory): 말머리 카테고리, 공통 에디터, 공개 범위, 보호글, 위조 요청.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD; 회원 "tomo_viewer"는 같은 TEST_PASSWORD 사용.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch();
  async function login(id) {
    const c = await b.newContext();
    await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const p = await c.newPage();
    p.setDefaultTimeout(15000);
    p.dialogs = [];
    p.on("dialog", (d) => { p.dialogs.push(d.message()); d.accept(); });
    p.errors = [];
    p.on("pageerror", (e) => p.errors.push(e.message));
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(id);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    return p;
  }
  const p = await login(process.env.TEST_USER);
  const id = "bc" + Date.now().toString().slice(-7);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("말머리 테스트");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(400);
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("말머리 테스트");
  await p.locator("[role=tab][aria-controls=anc_004]").click();
  await p.locator("[name=bo_skin]").selectOption("bbscategory");
  await p.locator("[role=tab][aria-controls=anc_003]").click();
  await p.locator("[name=bo_use_secret]").selectOption("1");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(700);
  const board = base + "/bbs/board.php?bo_table=" + id;

  async function write(subject, visibility, body, pw) {
    await p.goto(base + "/bbs/write.php?bo_table=" + id);
    assert.equal(await p.locator(".ProseMirror").count() > 0 || await p.locator("[name=wr_content]").count() > 0, true);
    await p.locator("#wr_subject").fill(subject);
    if (visibility) await p.locator("#set_secret").selectOption(visibility);
    if (pw) await p.locator("#wr_protect").fill(pw);
    const editor = p.locator(".ProseMirror").first();
    if (await editor.count()) { await editor.click(); await p.keyboard.type(body); }
    else await p.locator("[name=wr_content]").fill(body);
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    return new URL(p.url()).searchParams.get("wr_id");
  }
  const a = await write("일상) 산책", "", "요1[숨긴 글]-열기- https://example.com,참고");
  const m = await write("설정) 멤버 설정", "member", "멤버 본문");
  const pr = await write("로그) 보호 로그", "protect", "보호 본문", "4321");
  await write("일상) 두 번째", "", "본문");
  await write("말머리 없음", "", "본문");

  // 말머리 메뉴: 글 수와 필터
  await p.goto(board);
  const menu = await p.locator(".bbscat-category a").allInnerTexts();
  assert.deepEqual(menu.map((t) => t.replace(/\s+/g, " ").trim()), ["전체", "로그 1", "설정 1", "일상 2"]);
  assert.equal(await p.locator(".bbscat-rows .genre").count(), 4);
  await Promise.all([p.waitForURL(/sca=/), p.locator(".bbscat-category a", { hasText: "일상" }).click()]);
  assert.equal(await p.locator(".bbscat-rows > li:not(.no-data)").count(), 2);

  // 본문 치환자
  await p.goto(board + "&wr_id=" + a);
  assert.equal(await p.locator("#bo_v_con details.bbscat-fold summary").innerText(), "열기");
  assert.equal(await p.locator("#bo_v_con a.other-site-link").innerText(), "참고");

  // 다른 회원: 멤버공개는 보이고, 보호글은 비밀번호 필요
  const v = await login("tomo_viewer");
  await v.goto(board + "&wr_id=" + m);
  assert.match(await v.locator("#bo_v_con").innerText(), /멤버 본문/);
  await v.goto(board + "&wr_id=" + pr);
  assert.match(v.url(), /protect\.php/);
  await v.locator("#bbscat-protect-password").fill("0000");
  await v.locator("button[type=submit]").click();
  await v.waitForLoadState();
  assert.match(await v.locator("[role=alert]").innerText(), /일치하지 않습니다/);
  await v.locator("#bbscat-protect-password").fill("4321");
  await v.locator("button[type=submit]").click();
  await v.waitForLoadState();
  assert.match(await v.locator("#bo_v_con").innerText(), /보호 본문/);

  // 위조 요청: 토큰 없는 글쓰기, 게시판 분류를 켠 상태
  const forged = await v.context().request.post(base + "/bbs/write_update.php", { form: { w: "", bo_table: id, wr_subject: "x) y", wr_content: "z" } });
  assert((await forged.text()).includes("글쓰기 요청을 확인할 수 없습니다"));

  assert.deepEqual([...p.errors, ...v.errors], []);
  console.log("PASS bbscategory genres/filter, editor, replacements, member/protect visibility, forged write");
  await b.close();
})().catch((e) => { console.error(e); process.exit(1); });
