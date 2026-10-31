(() => {
  const form = document.querySelector("#fwrite");
  if (!form) return;

  /* ---------- 탭: 프로필 · 관계 · 연표 ---------- */
  const tabs = [...form.querySelectorAll("[data-tab]")];
  function showTab(key, focus) {
    tabs.forEach((t) => {
      const on = t.dataset.tab === key;
      t.setAttribute("aria-selected", String(on));
      t.tabIndex = on ? 0 : -1;
      if (on && focus) t.focus();
    });
    form.querySelectorAll("[data-panel]").forEach((p) => { p.hidden = p.dataset.panel !== key; });
    form.querySelectorAll("[data-panel]:not([hidden]) textarea[data-grow]").forEach(grow);
  }
  window.otakuShowTab = showTab;
  tabs.forEach((t, i) => {
    t.tabIndex = i ? -1 : 0;
    t.addEventListener("click", () => showTab(t.dataset.tab));
    t.addEventListener("keydown", (e) => {
      if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(e.key)) return;
      let n = tabs.indexOf(t);
      n = e.key === "Home" ? 0 : e.key === "End" ? tabs.length - 1 : (n + (e.key === "ArrowRight" ? 1 : -1) + tabs.length) % tabs.length;
      e.preventDefault();
      showTab(tabs[n].dataset.tab, true);
    });
  });
  // 숨은 탭의 필수 칸이 비어 있으면 그 탭을 연다.
  form.addEventListener("invalid", (e) => {
    const panel = e.target.closest("[data-panel]");
    if (panel && panel.hidden) showTab(panel.dataset.panel);
  }, true);

  /* ---------- 글상자 높이 맞춤 ---------- */
  function grow(t) {
    if (!t.offsetParent) return;
    t.style.height = "auto";
    t.style.height = t.scrollHeight + 2 + "px";
  }
  form.querySelectorAll("textarea[data-grow]").forEach(grow);
  form.addEventListener("input", (e) => { if (e.target.matches("textarea[data-grow]")) grow(e.target); });

  /* ---------- 반복 행: 추가 · 위아래 · 지우기 ---------- */
  form.addEventListener("click", (e) => {
    const button = e.target.closest("button");
    if (!button) return;
    const list = button.closest("[data-list]");
    if (button.hasAttribute("data-add") && list) {
      const row = list.querySelector("template").content.firstElementChild.cloneNode(true);
      list.querySelector(".otaku-rows").append(row);
      syncNames();
      const fields = row.querySelectorAll("input[type=text], textarea");
      if (button.dataset.add && fields[0]) {
        fields[0].value = button.dataset.add;
        (fields[1] || fields[0]).focus();
      } else (row.querySelector("select, input, textarea") || row).focus();
      row.querySelectorAll("textarea[data-grow]").forEach(grow);
      return;
    }
    const row = button.closest("[data-row]");
    if (!row) return;
    if (button.hasAttribute("data-remove")) {
      const next = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      (next ? next.querySelector("input, textarea, select") : list && list.querySelector("[data-add]"))?.focus();
    } else if (button.dataset.move) {
      const up = Number(button.dataset.move) < 0;
      const target = up ? row.previousElementSibling : row.nextElementSibling;
      if (target) up ? target.before(row) : target.after(row);
      button.focus();
    }
  });

  /* ---------- 이름: 관계 제목 · 연표 '누구의 일' 따라 바뀜 ---------- */
  function syncNames() {
    form.querySelectorAll("[data-name]").forEach((input) => {
      const i = input.dataset.name;
      const name = input.value.trim() || "인물 " + (Number(i) + 1);
      form.querySelectorAll('[data-name-echo="' + i + '"]').forEach((el) => { el.textContent = name; });
      form.querySelectorAll('select[name="tl_who[]"] option[value="' + i + '"]').forEach((o) => { o.textContent = name; });
    });
  }
  form.addEventListener("input", (e) => { if (e.target.matches("[data-name]")) syncNames(); });

  /* ---------- 사진 미리보기 ---------- */
  form.addEventListener("change", (e) => {
    const input = e.target.closest("[data-photo]");
    if (!input) return;
    const file = input.files && input.files[0];
    const box = form.querySelector('[data-preview="' + input.dataset.photo + '"]');
    if (!box || !file || !file.type.startsWith("image/")) return;
    const img = document.createElement("img");
    img.src = URL.createObjectURL(file);
    img.alt = "";
    box.replaceChildren(img);
    const del = form.querySelector('input[name="bf_file_del[' + input.dataset.photo + ']"]');
    if (del) del.checked = false;
  });
})();
