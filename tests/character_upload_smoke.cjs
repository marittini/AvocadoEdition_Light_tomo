/* Disposable test installation only. Regression for count(null) on character uploads. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
(async () => {
  const base = process.env.TEST_URL;
  const b = await chromium.launch({
    executablePath: "/usr/bin/chromium",
    args: ["--no-sandbox"],
  });
  const c = await b.newContext();
  await c.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await c.newPage();
  p.setDefaultTimeout(10000);
  p.on("dialog", (d) => {
    console.log("DIALOG", d.message());
    d.accept();
  });
  p.on("pageerror", (e) => console.log("JS", e.message));
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").click();
  const id = "c" + Date.now().toString().slice(-8);
  await p.goto(base + "/adm/boardgroup_form.php");
  await p.locator("[name=gr_id]").fill(id);
  await p.locator("[name=gr_subject]").fill("캐릭터 검증");
  await p.locator("input[type=submit]").first().click();
  await p.goto(base + "/adm/board_form.php");
  await p.evaluate((id) => {
    for (const [key, value] of Object.entries({
      gr_id: id,
      bo_table: id,
      bo_subject: "캐릭터 검증",
      bo_skin: "character",
    }))
      document.querySelector("[name=" + key + "]").value = value;
  }, id);
  await p.locator("input[type=submit]").first().click();
  await p.waitForTimeout(300);
  await p.goto(base + "/bbs/write.php?bo_table=" + id);
  await p.locator("[name=wr_subject]").fill("캐릭터 첨부 검증");
  await p.locator("[name=wr_content]").fill("나이 성별");
  await p.locator("[name=wr_1_file]").setInputFiles(process.env.TEST_IMAGE);
  await p.locator("[name=wr_2_file]").setInputFiles(process.env.TEST_IMAGE);
  const response = p.waitForResponse(
    (r) =>
      r.url().includes("/write_update.php") && r.request().method() === "POST",
  );
  await p.locator("[type=submit]").first().click();
  const r = await response;
  console.log("CHARACTER HTTP", r.status(), "board", id);
  await p.waitForTimeout(300);
  console.log(
    "RESULT",
    p.url(),
    (await p.locator("body").innerText()).slice(0, 200),
  );
  assert.equal(r.status(), 302);
  assert(p.url().includes("wr_id=1"));
  const html = await (
    await c.request.get(base + "/bbs/write.php?bo_table=" + id + "&w=u&wr_id=1")
  ).text();
  assert(
    html.includes("/header_1_") && html.includes("/body_1_"),
    "head and body file URLs persist",
  );
  console.log(
    "PASS: character skin submits its separate head/body attachments without bf_file[] on PHP 8.4",
  );
  await b.close();
})();
