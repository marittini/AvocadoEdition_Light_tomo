/* 이모티콘 목록 팝업: [data-emoticon-open] 버튼을 누르면 열리고, 고르면 커서 자리에 코드를 넣는다. (lib/emoticon.lib.php) */
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
    if (br.bottom > window.innerHeight || br.top < 0) btn.scrollIntoView({ block: "center" });
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

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-emoticon-open]");
    if (btn) { e.preventDefault(); return pop && !pop.hidden && opener === btn ? close(false) : open(btn); }
    if (!pop || pop.hidden) return;
    const pick = e.target.closest("[data-emoticon-code]");
    if (pick) { insert(pick.dataset.emoticonCode); return close(false); }
    if (e.target.closest(".emoticon-pop-close")) return close(true);
    if (!pop.contains(e.target)) close(false);
  });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") close(true); });
  window.addEventListener("resize", () => { if (pop && !pop.hidden) place(); });
  window.addEventListener("scroll", () => { if (pop && !pop.hidden) place(); }, { passive: true });
})();
