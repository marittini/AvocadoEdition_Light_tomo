// 링크 · 배너 게시판(link): 배너 추가(그림 주소 / 그림 올리기), 툴팁 · 툴팁 색, 내 배너(코드 복사 · 바뀌지 않는 그림 주소),
// 배너 관리(순서 · 한꺼번에 고치기 · 표시 방식 · 토큰), 홈 화면 배너, 비공개 사이트에서도 내 배너 그림, 위험한 주소 거르기.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_IMAGE, TEST_SQL(예: "mysql -uroot g5").
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  { execSync } = require("node:child_process");
(async () => {
  const base = process.env.TEST_URL,
    prefix = process.env.TEST_PREFIX || "avo_",
    sql = (q) => execSync(process.env.TEST_SQL, { input: q }).toString(),
    b = await chromium.launch(),
    errors = [];
  const [open, mainBefore] = [sql(`SELECT cf_open FROM ${prefix}config;`).split("\n")[1].trim(), null];
  let id = null;
  try {
    const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
    await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const p = await c.newPage();
    p.setDefaultTimeout(15000);
    p.on("pageerror", (e) => errors.push(e.message));
    p.on("dialog", (d) => d.accept());
    await p.goto(base + "/bbs/login.php");
    await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
    await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
    await p.locator("[type=submit]").first().click();
    await p.waitForLoadState();
    id = "lk" + Date.now().toString().slice(-7);
    await p.goto(base + "/adm/board_form.php");
    await p.locator("[name=gr_id]").selectOption({ index: 1 });
    await p.locator("[name=bo_table]").fill(id);
    await p.locator("[name=bo_subject]").fill("링크");
    await p.locator("[role=tab][aria-controls=anc_004]").click();
    await p.locator("[name=bo_skin]").selectOption("link");
    await p.locator("[role=tab][aria-controls=anc_003]").click();
    await p.locator("[name=bo_upload_count]").fill("1");
    await p.locator("input[type=submit]").first().click();
    await p.waitForTimeout(600);
    const board = base + "/bbs/board.php?bo_table=" + id;
    const imgUrl = base + "/img/temp_main_image.png";

    async function add(o) {
      await p.goto(base + "/bbs/write.php?bo_table=" + id);
      await p.locator("#lk-name").fill(o.name);
      if (o.href) await p.locator("[name=wr_link2]").fill(o.href);
      if (o.img) await p.locator("#lk-img-url").fill(o.img);
      if (o.file) await p.locator("#lk-img-file").setInputFiles(o.file);
      if (o.tip) await p.locator("#lk-tip").fill(o.tip);
      if (o.bg) await p.locator("[data-lk-color=lk-tip-bg]").fill(o.bg);
      if (o.mine) await p.locator("[name=wr_5]").check();
      await p.locator("#btn_submit").click();
      await p.waitForLoadState();
    }
    // 1. 그림 주소 배너 + 툴팁 + 툴팁 색 (쓰는 동안 미리보기)
    await p.goto(base + "/bbs/write.php?bo_table=" + id);
    await p.locator("#lk-tip").fill("미리 보는 툴팁");
    assert.equal((await p.locator(".lk-tip-preview").textContent()).trim(), "미리 보는 툴팁", "툴팁 미리보기");
    await add({ name: "하나", href: "https://one.example/", img: imgUrl, tip: "첫 번째 배너", bg: "#2a4d8f" });
    await add({ name: "둘", href: "javascript:alert(1)", img: "javascript:alert(2)" });
    await add({ name: "내 사이트", href: "https://me.example/", file: process.env.TEST_IMAGE, mine: true });
    assert.equal(new URL(p.url()).searchParams.get("bo_table"), id, "저장 후 목록");

    // 2. 목록: 내 배너(코드) · 순서 · 툴팁 · 위험한 주소 거름
    assert.equal(await p.locator(".lk-mine .lk-item").count(), 1, "내 배너는 맨 위");
    const code = await p.locator(".lk-code input").inputValue();
    assert.match(code, /^<a href="https:\/\/me\.example\/" target="_blank"><img src="[^"]*\/skin\/board\/link\/banner\.php\?bo_table=lk\d+&id=\d+" alt="내 사이트"><\/a>$/, "배너 코드(바뀌지 않는 그림 주소): " + code);
    const others = p.locator(".lk-list > .lk-grid .lk-item");
    assert.equal(await others.count(), 2, "다른 배너 2개");
    assert.equal(await others.first().locator("img").getAttribute("src"), imgUrl);
    const tip = others.first().locator(".lk-tip");
    assert.equal(await tip.isVisible(), false, "툴팁은 처음엔 숨김");
    await others.first().locator(".lk-banner").hover();
    await p.waitForTimeout(200);
    assert.equal(await tip.isVisible(), true, "마우스를 올리면 툴팁");
    assert.equal(await tip.evaluate((e) => getComputedStyle(e).backgroundColor), "rgb(42, 77, 143)", "툴팁 바탕색");
    const second = others.nth(1);
    assert.equal(await second.locator("a.lk-banner").count(), 0, "javascript: 주소는 링크가 되지 않음");
    assert.equal(await second.locator("img").count(), 0, "javascript: 그림 주소는 쓰지 않음");

    // 3. 바뀌지 않는 그림 주소: 비공개 사이트 · 비회원도 그림을 받는다
    const stable = code.match(/src="([^"]+)"/)[1];
    sql(`UPDATE ${prefix}config SET cf_open=0;`);
    const res = await fetch(stable, { redirect: "manual" });
    assert.equal(res.status, 200, "내 배너 그림(비공개 사이트 · 비회원)");
    assert.match(res.headers.get("content-type") || "", /^image\//);
    const notMine = await fetch(stable.replace(/id=\d+/, "id=1"), { redirect: "manual" });
    assert.equal(notMine.status, 404, "내 배너가 아닌 글은 내보내지 않음");
    sql(`UPDATE ${prefix}config SET cf_open=${Number(open) || 0};`);

    // 4. 배너 관리: 순서 바꾸기(↑) · 이름 고치기 · 한 줄 2개 · 홈 화면
    await p.locator(".lk-actions a", { hasText: "배너 관리" }).click();
    await p.waitForLoadState();
    const rows = p.locator(".lk-row");
    assert.equal(await rows.count(), 3);
    await rows.nth(1).locator("[data-lk-move='-1']").click(); // 둘 → 맨 위
    await rows.first().locator("input[name^=name]").fill("둘(고침)");
    await p.locator("[name=lk_pc]").selectOption("2");
    await p.locator("[name=lk_home]").check();
    const forged = await p.request.post(base + "/skin/board/link/manage_update.php", { form: { bo_table: id, lk_token: "bad", "ids[]": "1" } });
    assert.match(await forged.text(), /저장 요청을 확인할 수 없습니다/, "토큰 없는 관리 저장 거부");
    await p.locator("#lk-manage-form [type=submit]").click();
    await p.waitForLoadState();
    assert.match(await p.locator(".lk-saved").innerText(), /저장/);
    await p.goto(board);
    const names = await p.locator(".lk-list > .lk-grid .lk-item").evaluateAll((els) => els.map((e) => (e.querySelector("img")?.alt || e.textContent).trim()));
    assert.deepEqual(names.map((n) => n.replace(/\s*edit\s*delete\s*$/, "")), ["둘(고침)", "하나"], "바꾼 순서 · 이름");
    assert.equal(await p.locator(".lk-list > .lk-grid").evaluate((e) => getComputedStyle(e).gridTemplateColumns.split(" ").length), 2, "PC 한 줄 2개");

    // 5. 홈 화면: 내용 아래 배너 + 툴팁
    await p.goto(base + "/main.php");
    const home = p.locator(`.lk-home[data-board="${id}"]`);
    assert.equal(await home.count(), 1, "홈 화면 배너");
    assert.equal(await home.locator(".lk-item").first().locator("img").getAttribute("alt"), "내 사이트", "홈 화면은 내 배너가 먼저");
    assert.equal(await home.locator(".lk-item.has-tip .lk-tip").count(), 1, "홈 화면에도 툴팁");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("link board smoke ok", id);
  } finally {
    sql(`UPDATE ${prefix}config SET cf_open=${Number(open) || 0};`);
    if (id) sql(`UPDATE ${prefix}board SET bo_3='' WHERE bo_table='${id}';`); // 테스트 게시판은 홈 화면에서 뺀다
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
