/* 이모티콘: [data-emoticon-open] 버튼을 누르면 목록 창(bbs/emoticon.php)을 새 창으로 열고, 거기서 고른 코드를 커서 자리에 넣는다.
   브라우저가 새 창을 막으면 페이지 안 팝업으로 연다. (lib/emoticon.lib.php) */
(() => {
  if (window.__tomoEmoticonPicker) return;
  window.__tomoEmoticonPicker = true;
  const items = Array.isArray(window.tomoEmoticons) ? window.tomoEmoticons : [];
  let pop = null, opener = null, target = null;

  function build() {
    pop = document.createElement("div");
    pop.className = "emoticon-pop theme-box";
    pop.setAttribute("role", "dialog");
    pop.setAttribute("aria-label", "이모티콘");
    pop.hidden = true;
    const head = document.createElement("div");
    head.className = "emoticon-pop-head";
    head.innerHTML = '<span>이모티콘</span><button type="button" class="emoticon-pop-close" aria-label="닫기"><i class="material-icons" aria-hidden="true">close</i></button>';
    const list = document.createElement("ul");
    list.className = "emoticon-pop-list";
    for (const it of items) {
      const li = document.createElement("li");
      const b = document.createElement("button");
      b.type = "button";
      b.dataset.emoticonCode = it.code;
      b.title = it.code;
      const img = document.createElement("img");
      img.src = it.url; img.alt = ""; img.width = 48; img.height = 48;
      img.addEventListener("load", () => { if (pop && !pop.hidden) place(); }, { once: true });
      const code = document.createElement("span");
      code.textContent = it.code;
      b.append(img, code);
      li.append(b);
      list.append(li);
    }
    pop.append(head, list);
    document.body.append(pop);
  }

  function place() {
    pop.classList.toggle("is-sheet", mobile());
    if (mobile()) { pop.style.left = pop.style.top = pop.style.width = ""; return; } // 아래에서 올라오는 목록(CSS)
    const r = opener.getBoundingClientRect();
    const w = Math.min(320, window.innerWidth - 16);
    pop.style.width = w + "px";
    let left = Math.min(Math.max(8, r.left), window.innerWidth - w - 8);
    let top = r.bottom + 6;
    pop.style.left = left + "px";
    pop.style.top = top + "px";
    const h = pop.offsetHeight;
    if (top + h > window.innerHeight - 8) {
      // 아래 자리가 모자라면 버튼 위로, 그래도 모자라면 화면 안에 맞춘다
      top = r.top - h - 6 > 8 ? r.top - h - 6 : Math.max(8, window.innerHeight - h - 8);
      pop.style.top = top + "px";
    }
  }

  function open(btn) {
    if (!items.length) return;
    if (!pop) build();
    if (opener) opener.setAttribute("aria-expanded", "false");
    opener = btn;
    target = btn.dataset.emoticonOpen || "textarea";
    const br = btn.getBoundingClientRect();
    if (!mobile() && (br.bottom > window.innerHeight || br.top < 0)) btn.scrollIntoView({ block: "center" });
    pop.hidden = false;
    btn.setAttribute("aria-expanded", "true");
    place();
    pop.querySelector("[data-emoticon-code]")?.focus({ preventScroll: true });
  }
  function close(refocus) {
    if (!pop || pop.hidden) return;
    pop.hidden = true;
    if (opener) { opener.setAttribute("aria-expanded", "false"); if (refocus) opener.focus(); }
  }

  function insert(code) {
    if (target.startsWith("editor:")) {
      const ed = window.tomoEditors && window.tomoEditors.get(target.slice(7));
      if (ed && ed.editor) { ed.editor.chain().focus().insertContent(code).run(); return; }
      const area = document.getElementById(target.slice(7));
      if (area) return put(area, code);
      return;
    }
    const form = opener.closest("form");
    const area = form && form.querySelector("textarea[name=wr_content]");
    if (area) put(area, code);
  }
  function put(area, code) {
    const s = area.selectionStart ?? area.value.length, e = area.selectionEnd ?? s;
    area.setRangeText(code, s, e, "end");
    area.dispatchEvent(new Event("input", { bubbles: true }));
    area.focus();
  }

  // 휴대폰 · 태블릿(작은 화면이나 터치 화면)은 새 창이 새 탭으로 열려 오가기 불편하므로 화면 아래에서 올라오는 목록을 쓴다.
  const mobile = () => window.matchMedia("(max-width: 680px), (hover: none) and (pointer: coarse)").matches;

  // 목록 창(새 창) 열기. 휴대폰이거나 막히면 false.
  function openWindow(btn) {
    const url = btn.dataset.emoticonUrl;
    if (!url || mobile()) return false;
    const w = window.open(url, "tomo_emoticon", "width=400,height=560,scrollbars=yes,resizable=yes");
    if (!w) return false;
    if (opener) opener.setAttribute("aria-expanded", "false");
    opener = btn;
    target = btn.dataset.emoticonOpen || "textarea";
    close(false);
    try { w.focus(); } catch (_) { /* 무시 */ }
    return true;
  }
  // 목록 창에서 고른 코드(같은 사이트에서 보낸 등록된 코드만)
  window.addEventListener("message", (e) => {
    if (e.origin !== location.origin || !e.data || typeof e.data.tomoEmoticon !== "string" || !opener) return;
    const code = e.data.tomoEmoticon;
    if (items.some((it) => it.code === code)) insert(code);
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-emoticon-open]");
    if (btn) {
      e.preventDefault();
      if (openWindow(btn)) return;
      return pop && !pop.hidden && opener === btn ? close(false) : open(btn);
    }
    if (!pop || pop.hidden) return;
    const pick = e.target.closest("[data-emoticon-code]");
    if (pick) { insert(pick.dataset.emoticonCode); return close(false); }
    if (e.target.closest(".emoticon-pop-close")) return close(true);
    if (!pop.contains(e.target)) close(false);
  });
  // 댓글 폼에 버튼 자동으로 붙이기(bbs/view_comment.php가 window.tomoEmoticonAuto를 켠다)
  function autoButtons() {
    const url = window.tomoEmoticonAuto;
    if (!url) return;
    document.querySelectorAll('form[action*="write_comment_update"]').forEach((form) => {
      if (form.querySelector("[data-emoticon-open]")) return;
      const area = form.querySelector("textarea[name=wr_content]");
      if (!area) return;
      const bar = document.createElement("div");
      bar.className = "emoticon-auto-bar";
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "ui-btn small etc emoticon-open";
      btn.dataset.emoticonOpen = "textarea";
      btn.dataset.emoticonUrl = url;
      btn.setAttribute("aria-haspopup", "dialog");
      btn.setAttribute("aria-expanded", "false");
      btn.title = "이모티콘 목록 창 열기";
      btn.innerHTML = '<i class="material-icons" aria-hidden="true">mood</i> 이모티콘';
      bar.append(btn);
      // 글상자가 [글상자 | 등록 버튼] 같은 가로 줄 안에 있으면 그 줄 아래에 둔다(줄 안에 넣으면 버튼 높이가 늘어난다)
      let spot = area;
      while (spot.parentElement && spot.parentElement !== form) {
        const cs = getComputedStyle(spot.parentElement);
        const row = (cs.display.includes("flex") && !cs.flexDirection.startsWith("column")) || cs.display.includes("grid") || cs.position === "relative" && [...spot.parentElement.children].some((c) => getComputedStyle(c).position === "absolute");
        if (!row) break;
        spot = spot.parentElement;
      }
      spot.after(bar);
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", autoButtons); else autoButtons();

  document.addEventListener("keydown", (e) => { if (e.key === "Escape") close(true); });
  window.addEventListener("resize", () => { if (pop && !pop.hidden) place(); });
  window.addEventListener("scroll", () => { if (pop && !pop.hidden) place(); }, { passive: true });
})();
