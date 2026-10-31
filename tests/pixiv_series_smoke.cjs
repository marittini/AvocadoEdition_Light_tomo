// Pixiv skin series: create/attach, kind rules, episode navigation, editor, permissions and cleanup.
// Env: TEST_URL, TEST_USER (super admin), TEST_PASSWORD, TEST_IMAGE; member "tomo_viewer" shares TEST_PASSWORD.
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  fs = require("node:fs");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    image = { name: "p.png", mimeType: "image/png", buffer: fs.readFileSync(process.env.TEST_IMAGE) };
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
  const id = "ps" + Date.now().toString().slice(-7);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("시리즈 테스트");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(400);
  await p.goto(base + "/adm/board_form.php");
  await p.locator("[name=gr_id]").selectOption(id);
  await p.locator("[name=bo_table]").fill(id);
  await p.locator("[name=bo_subject]").fill("시리즈 테스트");
  await p.locator("[role=tab][aria-controls=anc_004]").click();
  await p.locator("[name=bo_skin]").selectOption("custom_skin/pixiv");
  await p.locator("[role=tab][aria-controls=anc_003]").click();
  await p.locator("[name=bo_upload_count]").fill("5");
  await p.locator("[name=bo_use_secret]").selectOption("1");
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(700);
  const board = base + "/bbs/board.php?bo_table=" + id;

  async function write(subject, type, series, extra = {}) {
    await p.goto(base + "/bbs/write.php?bo_table=" + id + (extra.query || ""));
    await p.locator("[name=wr_subject]").fill(subject);
    await p.locator("[name=pixiv_type]").selectOption(type);
    if (series && series.startsWith("new:")) {
      await p.locator("#pixiv-series").selectOption("new");
      await p.locator("#pixiv-series-title").fill(series.slice(4));
    } else if (series) await p.locator("#pixiv-series").selectOption({ label: series });
    if (type === "novel") await p.locator("[name=wr_content]").evaluate((t) => { t.value = "본문 ".repeat(50); });
    else await p.locator("#pixiv-files").setInputFiles([image]);
    if (extra.secret) await p.locator("#secret").check();
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    return new URL(p.url()).searchParams.get("wr_id");
  }
  const ep1 = await write("1화", "manga", "new:테스트 시리즈");
  const ep2 = await write("2화", "illustration", "테스트 시리즈 · 만화");
  const ep3 = await write("3화", "manga", "테스트 시리즈 · 만화");
  assert(ep1 && ep2 && ep3, "episodes saved");

  // Detail: breadcrumb, numbering and neighbours.
  await p.goto(board + "&wr_id=" + ep2);
  assert.match(await p.locator(".px-series-crumb").innerText(), /테스트 시리즈\s*#2/);
  assert.match(await p.locator(".px-series-step.is-prev").innerText(), /#1 1화/);
  assert.match(await p.locator(".px-series-step.is-next").innerText(), /#3 3화/);
  const seriesHref = await p.locator(".px-series-crumb").getAttribute("href");
  const ps = new URL(seriesHref, base).searchParams.get("px_series");

  // Viewer: cover → vertical pages → page viewer → end card offers the next episode.
  await p.locator("#px-open").click();
  assert.equal(await p.locator("#px-reader").isVisible(), true);
  await p.locator(".px-images .px-page").first().click();
  assert.equal(await p.locator("[data-comic-progress]").innerText(), "1 / 1");
  await p.locator("[data-comic=next]").click();
  assert.equal(await p.locator(".px-comic-end").isVisible(), true);
  assert.match(await p.locator(".px-comic-end .ui-btn.point").innerText(), /다음 화/);
  await p.keyboard.press("Escape");
  assert.equal(await p.locator("#px-comic").isVisible(), false);

  // A novel cannot join a comic series, even with a forged option.
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=pixiv_type]").selectOption("novel");
  assert.equal(await p.locator("#pixiv-series option", { hasText: "테스트 시리즈" }).isDisabled(), true);
  await p.locator("[name=wr_subject]").fill("소설");
  await p.locator("[name=wr_content]").evaluate((t) => { t.value = "본문"; });
  await p.locator("#pixiv-series").evaluate((s, v) => { s.options[1].disabled = false; s.value = v; }, ps);
  await p.locator("#btn_submit").click();
  await p.waitForTimeout(600);
  assert(p.dialogs.pop().includes("만화 시리즈에는 소설을 넣을 수 없습니다"));

  // Series page, list tab, then the editor: reorder (3,1,2), caption.
  await p.goto(seriesHref);
  assert.equal(await p.locator(".px-episode").count(), 3);
  await p.goto(board + "&px_type=series");
  assert.equal(await p.locator(".px-series-card").count(), 1);
  await p.goto(seriesHref + "&px_edit=1");
  await p.locator("#px-series-caption").fill("설명 테스트");
  await p.locator("#px-series-sort li").nth(2).locator("[data-move='-1']").click();
  await p.locator("#px-series-sort li").nth(1).locator("[data-move='-1']").click();
  await p.locator("button[name=mode][value=save]").click();
  await p.waitForLoadState();
  assert.deepEqual(await p.locator(".px-episode-name").allInnerTexts(), ["3화", "1화", "2화"]);
  assert.equal(await p.locator(".px-series-info .px-note").innerText(), "설명 테스트");

  // Secret episodes stay hidden from other members; non-owners cannot edit.
  const secret = await write("비밀화", "manga", null, { query: "&px_series=" + ps, secret: true });
  const v = await login("tomo_viewer");
  await v.goto(seriesHref);
  assert.equal((await v.content()).includes("비밀화"), false, "secret episode hidden");
  assert.equal(await v.locator("text=시리즈 편집").count(), 0);
  await v.goto(seriesHref + "&px_edit=1");
  assert.equal(await v.locator("#px-series-form").count(), 0);
  const forged = await v.context().request.post(base + "/skin/board/custom_skin/pixiv/series_update.php", {
    form: { bo_table: id, ps_id: ps, token: "forged", mode: "delete" },
  });
  assert((await forged.text()).includes("편집 인증이 만료"));
  await p.goto(seriesHref);
  assert.equal((await p.content()).includes("비밀화"), true, "owner sees secret episode");

  // Deleting a post removes it from the series.
  await p.goto(board + "&wr_id=" + secret);
  await p.locator(".btn_confirm a", { hasText: "삭제" }).click();
  await p.waitForLoadState();
  await p.goto(seriesHref);
  assert.equal(await p.locator(".px-episode").count(), 3);

  // Deleting the series keeps the works.
  await p.goto(seriesHref + "&px_edit=1");
  await p.locator("button[name=mode][value=delete]").click();
  await p.waitForLoadState();
  await p.goto(board + "&wr_id=" + ep1);
  assert.equal(await p.locator(".px-series-crumb").count(), 0);
  assert.equal(await p.locator("h1").innerText(), "1화");

  assert.deepEqual([...p.errors, ...v.errors], []);
  console.log("PASS series create/attach, kind rule, navigation, viewer end card, editor order, secret, permissions, cleanup");
  await b.close();
})().catch((e) => { console.error(e); process.exit(1); });
