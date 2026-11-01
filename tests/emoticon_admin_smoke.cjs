// 관리자 > 게시판관리 > 이모티콘관리: 메뉴, 추가(이미지 검사 · 코드 검사 · 중복), 코드 수정, 삭제(파일도), 권한.
// Env: TEST_URL, TEST_USER(최고관리자), TEST_PASSWORD, TEST_MEMBER(일반 회원, 같은 비밀번호), TEST_IMAGE(png/jpg).
const { chromium } = require("playwright"),
  assert = require("node:assert/strict"),
  fs = require("node:fs"),
  os = require("node:os"),
  path = require("node:path");
(async () => {
  const base = process.env.TEST_URL,
    b = await chromium.launch(),
    errors = [];
  const fake = path.join(fs.mkdtempSync(path.join(os.tmpdir(), "emo-")), "not-image.png");
  fs.writeFileSync(fake, "<?php echo 1; ?>");
  try {
    async function open(id) {
      const c = await b.newContext({ viewport: { width: 1280, height: 900 } });
      await c.route("**/*", (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
      const p = await c.newPage();
      p.setDefaultTimeout(15000);
      p.on("pageerror", (e) => errors.push(e.message));
      p.dialogs = [];
      p.on("dialog", (d) => { p.dialogs.push(d.message()); d.accept(); });
      await p.goto(base + "/bbs/login.php");
      await p.locator("[name=mb_id]").fill(id);
      await p.locator("[name=mb_password]").fill(process.env.TEST_PASSWORD);
      await p.locator("[type=submit]").first().click();
      await p.waitForLoadState();
      return p;
    }
    const p = await open(process.env.TEST_USER);
    await p.goto(base + "/adm/board_list.php");
    const menu = p.locator("a[href*='emoticon_list.php']").first();
    assert.equal(await menu.count(), 1, "관리자 메뉴에 이모티콘관리");
    await p.goto(base + "/adm/emoticon_list.php");
    assert.match(await p.title(), /이모티콘 관리/);

    const code = "(t" + Date.now().toString().slice(-6) + ")";
    async function add(c, file) {
      await p.goto(base + "/adm/emoticon_list.php");
      await p.locator("#me_text").fill(c);
      await p.locator("#me_img").setInputFiles(file);
      await p.locator("#femoticonform input[type=submit]").click();
      await p.waitForLoadState();
    }
    // 이미지가 아닌 파일은 거부
    await add(code, fake);
    assert.ok(p.dialogs.pop().includes("이미지만 올릴 수 있습니다"), "이미지 아닌 파일 거부");
    // 코드 검사
    await add("나쁜 코드", process.env.TEST_IMAGE);
    assert.match(p.dialogs.pop() || "", /띄어쓰기/, "띄어쓰기 코드 거부");
    // 추가
    await add(code, process.env.TEST_IMAGE);
    const row = p.locator("tr", { has: p.locator(`input[value="${code}"]`) });
    assert.equal(await row.count(), 1, "추가됨");
    const src = await row.locator("img").getAttribute("src");
    assert.match(src, /\/data\/emoticon\/\d{6}_[0-9a-f]{16}\.(png|jpg)$/, "무작위 이름으로 저장: " + src);
    assert.equal((await p.request.get(src)).status(), 200, "이미지 열림");
    // 중복 거부
    await add(code, process.env.TEST_IMAGE);
    assert.ok(p.dialogs.pop().includes("이미 등록된 코드"), "중복 코드 거부");

    // 코드 수정
    const renamed = code.replace("(t", "(r");
    await p.goto(base + "/adm/emoticon_list.php");
    const r2 = p.locator("tr", { has: p.locator(`input[value="${code}"]`) });
    await r2.locator("input[type=checkbox]").check();
    await r2.locator("input[type=text]").fill(renamed);
    await p.locator("#femoticonlist input[value=선택수정]").click();
    await p.waitForLoadState();
    assert.equal(await p.locator(`input[value="${renamed}"]`).count(), 1, "코드 수정");

    // 일반 회원은 들어올 수 없음
    const member = await open(process.env.TEST_MEMBER);
    await member.goto(base + "/adm/emoticon_list.php");
    assert.equal(await member.locator("#femoticonlist").count(), 0, "일반 회원 접근 불가");

    // 삭제: 줄과 파일 모두
    await p.goto(base + "/adm/emoticon_list.php");
    await p.locator("tr", { has: p.locator(`input[value="${renamed}"]`) }).locator("input[type=checkbox]").check();
    await p.locator("#femoticonlist input[value=선택삭제]").click();
    await p.waitForLoadState();
    assert.equal(await p.locator(`input[value="${renamed}"]`).count(), 0, "삭제됨");
    assert.equal((await p.request.get(src)).status(), 404, "이미지 파일도 삭제");

    assert.deepEqual(errors, [], "자바스크립트 오류 없음");
    console.log("emoticon admin smoke ok");
  } finally {
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
