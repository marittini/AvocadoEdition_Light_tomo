/* Disposable installation with Tiptap selected. */
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
const fs = require("node:fs");
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
  await p.goto(base + "/adm/viewer_form.php");
  // Source editing must be explicitly applied before a save can send stale content.
  await p.getByRole("button", { name: "HTML", exact: true }).click();
  await p
    .getByRole("textbox", { name: "본문 HTML", exact: true })
    .fill("<h2>캐릭터 기록</h2><p>사과와 사과, 프로필을 작성합니다.</p>");
  let requests = 0;
  p.on("request", (r) => {
    if (r.url().includes("/viewer_form_update.php")) requests++;
  });
  await p.locator("[type=submit]").first().click();
  await p.waitForTimeout(200);
  assert.equal(requests, 0);
  await p.getByRole("button", { name: "HTML 적용", exact: true }).click();
  assert.equal(await p.locator(".tiptap h2").innerText(), "캐릭터 기록");
  await p.getByRole("button", { name: "찾기·바꾸기", exact: true }).click();
  await p.getByRole("textbox", { name: "찾을 내용", exact: true }).fill("사과");
  await p.getByRole("textbox", { name: "바꿀 내용", exact: true }).fill("배");
  await p.getByRole("button", { name: "모두 바꾸기", exact: true }).click();
  assert((await p.locator(".tiptap").innerText()).includes("배와 배"));
  await p.locator(".tiptap").click();
  await p.locator(".tiptap").press("Control+End");
  await p.locator(".tiptap").press("Enter");
  await p.getByRole("button", { name: "표 삽입", exact: true }).click();
  assert.equal(await p.locator(".tiptap table tr").count(), 3);
  await p.locator(".tiptap th").first().click();
  await p.getByRole("button", { name: "표 편집", exact: true }).click();
  await p.getByRole("button", { name: "아래에 행 추가", exact: true }).click();
  assert.equal(await p.locator(".tiptap table tr").count(), 4);
  await p.getByRole("button", { name: "오른쪽 열 추가", exact: true }).click();
  assert.equal(
    await p.locator(".tiptap tr").first().locator("th,td").count(),
    4,
  );
  // A real cell selection exercises the merge/split controls.
  await p.evaluate(() => {
    const e = window.tomoEditors.get("main_content").editor,
      positions = [];
    e.state.doc.descendants((node, pos) => {
      if (["tableCell", "tableHeader"].includes(node.type.name))
        positions.push(pos);
    });
    e.commands.setCellSelection({
      anchorCell: positions[0],
      headCell: positions[1],
    });
  });
  await p.getByRole("button", { name: "셀 병합", exact: true }).click();
  assert.equal(
    await p.locator(".tiptap tr").first().locator('[colspan="2"]').count(),
    1,
  );
  await p.getByRole("button", { name: "셀 나누기", exact: true }).click();
  assert.equal(
    await p.locator(".tiptap tr").first().locator("th,td").count(),
    4,
  );
  await p.getByRole("button", { name: "이미지 첨부", exact: true }).click(); // Cancel the chooser; use the same input to provide two files.
  await p.locator(".tomo-editor input[type=file]").setInputFiles([
    {
      name: "첫번째.png",
      mimeType: "image/png",
      buffer: fs.readFileSync(process.env.TEST_IMAGE),
    },
    {
      name: "두번째.png",
      mimeType: "image/png",
      buffer: fs.readFileSync(process.env.TEST_IMAGE),
    },
  ]);
  await p.waitForFunction(
    () => document.querySelectorAll(".tiptap img").length === 2,
  );
  await p.locator(".tiptap img").first().click();
  await p.getByRole("button", { name: "이미지 편집", exact: true }).click();
  await p
    .getByRole("spinbutton", { name: "이미지 너비(px)", exact: true })
    .fill("160");
  await p
    .getByRole("spinbutton", { name: "이미지 높이(px)", exact: true })
    .fill("160");
  await p
    .getByRole("textbox", { name: "이미지 설명", exact: true })
    .fill("캐릭터 두상");
  await p
    .getByRole("button", { name: "이미지 정보 적용", exact: true })
    .click();
  assert.equal(
    await p.locator(".tiptap img").first().getAttribute("alt"),
    "캐릭터 두상",
  );
  assert.equal(
    Math.round((await p.locator(".tiptap img").first().boundingBox()).width),
    160,
  );
  await p.getByRole("button", { name: "이미지 가운데", exact: true }).click();
  await p.locator(".tiptap").press("Control+End");
  await p.locator(".tiptap").press("ArrowRight");
  await p.locator(".tiptap").press("Enter");
  await p.getByRole("button", { name: "추가 서식", exact: true }).click();
  await p.getByRole("button", { name: "접기·펼치기", exact: true }).click();
  assert.equal(await p.locator(".tiptap [data-type=details]").count(), 1);
  await p.locator(".tiptap summary").fill("더 알아보기");
  await p.locator(".tiptap").press("Control+End");
  await p.locator(".tiptap").press("Enter");
  await p.getByRole("button", { name: "체크리스트", exact: true }).click();
  assert((await p.locator(".tiptap input[type=checkbox]").count()) > 0);
  await p.getByRole("button", { name: "동영상·기호", exact: true }).click();
  await p
    .getByRole("textbox", { name: "유튜브 주소", exact: true })
    .fill("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
  await p.getByRole("button", { name: "유튜브 삽입", exact: true }).click();
  assert.equal(await p.locator(".tiptap iframe").count(), 1);
  assert(
    (await p.locator(".tiptap iframe").getAttribute("src")).startsWith(
      "https://www.youtube-nocookie.com/embed/",
    ),
  );
  const done = p.waitForResponse(
    (r) =>
      r.url().endsWith("/viewer_form_update.php") &&
      r.request().method() === "POST",
  );
  await p.locator("[type=submit]").first().click();
  assert.equal((await (await done).json()).saved, true);
  await p.reload();
  assert.equal(await p.locator(".tiptap img").count(), 2);
  assert.equal(await p.locator(".tiptap iframe").count(), 1);
  assert.equal(await p.locator(".tiptap [data-type=details]").count(), 1);
  assert((await p.locator(".tiptap").innerText()).includes("배와 배"));
  for (const width of [390, 1440]) {
    await p.setViewportSize({ width, height: 1000 });
    assert(
      await p.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    );
    await p.getByRole("button", { name: "표 편집", exact: true }).click();
    if (process.env.SCREENSHOT_DIR)
      await p.screenshot({
        path: process.env.SCREENSHOT_DIR + "/editor-expanded-" + width + ".png",
        fullPage: true,
      });
    await p.getByRole("button", { name: "표 편집", exact: true }).click();
  }
  assert.deepEqual(errors, []);
  console.log(
    "PASS: HTML apply/save guard, find and replace, table rows/columns/merge/split, multiple image upload/size/alt/alignment, details/checklist, YouTube, save/reopen, mobile",
  );
  await b.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
