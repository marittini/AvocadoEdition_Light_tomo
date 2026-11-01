// 관리자 > 게시판관리 > 이모티콘관리: 메뉴, 추가(이미지 검사 · 코드 검사 · 중복), 코드 수정, 삭제(파일도), 권한,
// 표시 크기(사이트 어디서나 같은 높이), 기본 스킨 댓글의 이모티콘 버튼 · 표시.
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
  let sizeBefore = null, admin = null;
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
    const p = (admin = await open(process.env.TEST_USER));
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

    // 표시 크기: 저장하면 사이트 어디서나 img.emoticon 높이가 같다
    await p.goto(base + "/adm/emoticon_list.php");
    sizeBefore = await p.locator("#emoticon_size").inputValue();
    await p.locator("#emoticon_size").fill("30");
    await p.locator("#femoticonsize .emoticon-size-save").click();
    await p.waitForLoadState();
    assert.equal(await p.locator("#emoticon_size").inputValue(), "30", "표시 크기 저장");

    // 기본(basic) 스킨 게시판: 댓글 칸 아래에 이모티콘 버튼이 저절로 붙고, 댓글에서 그림으로 보인다
    const bo = "eb" + Date.now().toString().slice(-7);
    await p.goto(base + "/adm/board_form.php");
    await p.locator("[name=gr_id]").selectOption({ index: 1 });
    await p.locator("[name=bo_table]").fill(bo);
    await p.locator("[name=bo_subject]").fill("이모티콘 기본 스킨");
    await p.locator("[role=tab][aria-controls=anc_004]").click();
    await p.locator("[name=bo_skin]").selectOption("basic");
    await p.locator("input[type=submit]").first().click();
    await p.waitForTimeout(600);
    await p.goto(base + "/bbs/write.php?bo_table=" + bo);
    await p.locator("#wr_subject").fill("댓글 이모티콘");
    const ed = p.locator(".ProseMirror").first();
    if (await ed.count()) { await ed.click(); await p.keyboard.insertText("본문"); } else await p.locator("#wr_content").fill("본문");
    await p.locator("#btn_submit").click();
    await p.waitForLoadState();
    const cform = p.locator("form[action*='write_comment_update']").first();
    assert.equal(await cform.locator(".emoticon-auto-bar [data-emoticon-open]").count(), 1, "댓글 칸에 이모티콘 버튼");
    await cform.locator("textarea[name=wr_content]").fill("좋아요 ");
    await p.evaluate(() => { window.open = () => null; }); // 새 창 대신 페이지 안 목록
    await cform.locator("[data-emoticon-open]").click();
    await p.locator(`.emoticon-pop [data-emoticon-code="${renamed}"]`).click();
    assert.equal(await cform.locator("textarea[name=wr_content]").inputValue(), "좋아요 " + renamed, "댓글 칸에 코드");
    await cform.locator("[type=submit]").first().click();
    await p.waitForLoadState("load");
    const emo = p.locator("#bo_vc img.emoticon").first();
    assert.equal(await emo.count(), 1, "댓글에 이모티콘 그림");
    assert.equal(Math.round((await emo.boundingBox()).height), 30, "표시 크기 30px");

    // 다이어리 본문(#bo_v_con 그림 규칙이 height:auto !important를 거는 곳)에서도 표시 크기를 따른다
    const dy = "ed" + Date.now().toString().slice(-7);
    await p.goto(base + "/adm/board_form.php");
    await p.locator("[name=gr_id]").selectOption({ index: 1 });
    await p.locator("[name=bo_table]").fill(dy);
    await p.locator("[name=bo_subject]").fill("이모티콘 다이어리");
    await p.locator("[role=tab][aria-controls=anc_004]").click();
    await p.locator("[name=bo_skin]").selectOption("diary");
    await p.locator("input[type=submit]").first().click();
    await p.waitForTimeout(600);
    await p.goto(base + "/bbs/write.php?bo_table=" + dy);
    await p.locator("#wr_subject").fill("크기 확인");
    await p.locator(".ProseMirror").first().click();
    await p.keyboard.insertText("오늘 " + renamed);
    await p.locator("#btn_submit").click();
    await p.waitForLoadState("load");
    const body = p.locator("#bo_v_con img.emoticon").first();
    assert.equal(Math.round((await body.boundingBox()).height), 30, "다이어리 본문 이모티콘도 30px");

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
    if (admin && sizeBefore !== null) { // 표시 크기 되돌리기
      await admin.goto(base + "/adm/emoticon_list.php");
      await admin.locator("#emoticon_size").fill(sizeBefore || "0");
      await admin.locator("#femoticonsize .emoticon-size-save").click();
      await admin.waitForLoadState();
    }
    await b.close();
  }
})().catch((e) => { console.error(e); process.exit(1); });
