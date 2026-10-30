/* Disposable installation only. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
const fs = require("fs");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch({
      executablePath: "/usr/bin/chromium",
      args: ["--no-sandbox"],
    }),
    c = await b.newContext();
  await c.route("**/*", (r) =>
    r.request().url().startsWith(base) ? r.continue() : r.abort(),
  );
  const p = await c.newPage();
  p.setDefaultTimeout(15000);
  const errors = [];
  p.on("pageerror", (e) => {
    errors.push(e.message);
    console.log("JS", e.message);
  });
  p.on("dialog", (d) => d.accept().catch(() => {}));
  await p.goto(base + "/bbs/login.php");
  await p.locator("[name=mb_id]").fill(process.env.TEST_USER);
  await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
  await p.locator("[type=submit]").click();
  const save = async (name) => {
    const done = p.waitForResponse(
      (r) =>
        r.url().endsWith("/" + name + "_update.php") &&
        r.request().method() === "POST",
    );
    await p.locator("input[type=submit]").first().click();
    const r = await done;
    assert.equal(r.status(), 200);
    assert.equal((await r.json()).saved, true);
  };
  await p.goto(base + "/adm/config_form.php");
  assert.equal(await p.locator("input[type=submit]").count(), 1);
  assert.equal(await p.locator(".btn_confirm").count(), 1);
  // Register an actual local font; selecting its name must load the font in the admin.
  await p.goto(base + "/adm/design_form.php#tomo-fonts");
  await p.locator("#tomo-font-add").click();
  const row = p.locator(".tomo-font-row").last();
  const face = "RepairFace" + Date.now();
  await row.locator("[data-font-field=label]").fill("편집기 글꼴 검사");
  await row.locator("[data-font-field=family]").fill(face);
  await row
    .locator("[data-font-field=css]")
    .fill(
      "@font-face{font-family:" +
        face +
        ';src:url("/data/css/editor-test.ttf") format("truetype")}body{outline:99px solid red}',
    );
  await save("design_form");
  await p.goto(base + "/adm/viewer_form.php");
  await p.evaluate(() =>
    window.tomoEditors
      .get("main_content")
      .editor.commands.setContent(
        '<p>WWWWiiii 글꼴</p><p style="text-align:right">다른 문단</p>',
      ),
  );
  await p.locator(".tiptap p").first().click();
  await p.keyboard.press("Home");
  await p.keyboard.press("Shift+End");
  await p
    .getByRole("combobox", { name: "글꼴", exact: true })
    .selectOption(face);
  await p.evaluate((face) => document.fonts.load("16px " + face), face);
  assert(
    await p.evaluate(
      (face) =>
        [...document.fonts].some(
          (font) => font.family === face && font.status === "loaded",
        ),
      face,
    ),
  );
  assert((await p.locator(".tiptap p").first().innerHTML()).includes(face));
  assert.notEqual(
    await p.locator("body").evaluate((el) => getComputedStyle(el).outlineWidth),
    "99px",
  );
  await p
    .getByRole("combobox", { name: "글자 크기", exact: true })
    .selectOption("24px");
  assert((await p.locator(".tiptap p").first().innerHTML()).includes("24px"));
  for (const align of ["center", "left", "right", "center"]) {
    await p
      .getByRole("combobox", { name: "정렬", exact: true })
      .selectOption(align);
    assert.equal(
      await p
        .locator(".tiptap p")
        .first()
        .evaluate((el) => getComputedStyle(el).textAlign),
      align,
    );
  }
  await p.locator(".tiptap p").nth(1).click();
  await p.waitForFunction(
    () => document.querySelector('select[aria-label="정렬"]').value === "right",
  );
  assert.equal(
    await p.getByRole("combobox", { name: "정렬", exact: true }).inputValue(),
    "right",
  );
  assert.equal(
    await p.getByRole("combobox", { name: "글꼴", exact: true }).inputValue(),
    "",
  );
  await save("viewer_form");
  await p.reload();
  assert((await p.locator(".tiptap p").first().innerHTML()).includes(face));
  console.log(
    "PASS actual font loading, selection preserved across font/size/alignment, cursor state synchronized, theme CSS excluded",
  );
  await p.goto(base + "/adm/design_form.php#tomo-palette");
  const getField = async (name, slot) =>
    p.evaluate(
      ({ name, slot }) => {
        const input = [
          ...document.querySelectorAll('input[name^="cs_name["]'),
        ].find((el) => el.value === name);
        const index = input.name.match(/\[(\d+)\]/)[1];
        return document
          .querySelector("form")
          .elements.namedItem(
            (slot === 0 ? "cs_value" : "cs_etc_" + slot) + "[" + index + "]",
          ).value;
      },
      { name, slot },
    );
  const originalImage = await getField("background", 0),
    originalAccent = await getField("color_point", 0);
  await p.getByRole("button", { name: "세이지", exact: true }).click();
  await p.locator("#palette-apply").click();
  assert.equal(await getField("color_point", 0), "#446b52");
  assert.equal(await getField("background", 0), originalImage);
  await p.locator("#palette-undo").click();
  assert.equal(await getField("color_point", 0), originalAccent);
  await p.locator("#palette-apply").click();
  await save("design_form");
  await p.reload();
  assert.equal(await getField("color_point", 0), "#446b52");
  assert.equal(await p.locator("#palette-accent").inputValue(), "#446b52");
  const css = await (
    await c.request.get(base + "/data/css/_design.config.css")
  ).text();
  assert(css.includes("rgb(68,107,82)"));
  await p.locator("#palette-accent").fill("#ffee00");
  await p.locator("#palette-accent").dispatchEvent("input");
  await p.locator("#palette-recommend").click();
  assert.notEqual(await p.locator("#palette-accent").inputValue(), "#ffee00");
  console.log(
    "PASS palette presets/bulk application/undo/save and accent recommendations, background image preserved",
  );
  // GET only shows a form. Invalid tokens cannot delete a cache file.
  const root = "/workspace/implementation/AvocadoEdition_Light/data";
  const cache = root + "/cache/latest-tomo-tools-test.php";
  fs.writeFileSync(cache, "temporary cache");
  await p.goto(base + "/adm/cache_file_delete.php");
  assert(fs.existsSync(cache));
  await c.request.post(base + "/adm/cache_file_delete.php", {
    form: { token: "forged" },
  });
  assert(fs.existsSync(cache));
  await p.reload();
  await p.locator("input[type=submit]").click();
  assert(!fs.existsSync(cache));
  assert((await p.locator("[role=status]").innerText()).includes("삭제"));
  const session = root + "/session/sess_tomotoolsold";
  fs.writeFileSync(session, "fixture");
  const old = new Date(Date.now() - 30000000);
  fs.utimesSync(session, old, old);
  await p.goto(base + "/adm/session_file_delete.php");
  assert(fs.existsSync(session));
  await p.locator("input[type=submit]").click();
  assert(!fs.existsSync(session));
  await p.goto(base + "/adm/site_config_form.php");
  assert.equal(await p.locator("#fconfigform").count(), 1);
  assert.deepEqual(errors, []);
  console.log(
    "PASS cleanup GET/no deletion, CSRF rejection, real cache/session deletion, active admin session retained",
  );
  await b.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
