(() => {
  /* ---------- 배너 코드 복사(내 배너) ---------- */
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-lk-copy]");
    if (!btn) return;
    const input = document.getElementById(btn.dataset.lkCopy);
    if (!input) return;
    const label = btn.textContent;
    try {
      await navigator.clipboard.writeText(input.value);
    } catch (_) {
      input.select(); // 클립보드를 쓸 수 없으면 골라 두고 복사 명령
      try { document.execCommand("copy"); } catch (__) { /* 무시 */ }
    }
    btn.textContent = "복사했어요";
    setTimeout(() => { btn.textContent = label; }, 1500);
  });

  /* ---------- 쓰기: 미리보기(그림 · 툴팁 · 툴팁 색) ---------- */
  const preview = document.getElementById("lk-preview");
  if (preview) {
    const img = preview.querySelector(".lk-preview-img"), tip = preview.querySelector(".lk-tip-preview");
    const url = document.getElementById("lk-img-url"), file = document.getElementById("lk-img-file"), text = document.getElementById("lk-tip"), name = document.getElementById("lk-name");
    let objectUrl = "";
    const showImage = (src) => {
      img.replaceChildren();
      if (src) { const el = new Image(); el.src = src; el.alt = ""; img.append(el); }
      else { const s = document.createElement("span"); s.className = "lk-text"; s.textContent = (name && name.value.trim()) || "배너 미리보기"; img.append(s); }
    };
    url?.addEventListener("input", () => { if (!file?.files?.length) showImage(/^https?:\/\//i.test(url.value.trim()) ? url.value.trim() : ""); });
    file?.addEventListener("change", () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = file.files[0] ? URL.createObjectURL(file.files[0]) : "";
      showImage(objectUrl || (/^https?:\/\//i.test(url?.value.trim() || "") ? url.value.trim() : ""));
    });
    name?.addEventListener("input", () => { const t = img.querySelector(".lk-text"); if (t) t.textContent = name.value.trim() || "배너 미리보기"; });
    text?.addEventListener("input", () => { tip.textContent = text.value; tip.hidden = !text.value.trim(); });
    document.querySelectorAll("[data-lk-color]").forEach((pick) => {
      const hidden = document.getElementById(pick.dataset.lkColor), prop = pick.dataset.lkColor === "lk-tip-bg" ? "--lk-tip-bg" : "--lk-tip-fg";
      pick.addEventListener("input", () => { hidden.value = pick.value; pick.classList.remove("is-empty"); preview.style.setProperty(prop, pick.value); });
    });
    document.querySelector("[data-lk-color-clear]")?.addEventListener("click", () => {
      document.querySelectorAll("[data-lk-color]").forEach((pick) => { document.getElementById(pick.dataset.lkColor).value = ""; pick.classList.add("is-empty"); });
      preview.style.removeProperty("--lk-tip-bg");
      preview.style.removeProperty("--lk-tip-fg");
    });
  }

  /* ---------- 관리: 끌어서 / ↑ ↓ 로 순서 바꾸기 ---------- */
  const rows = document.getElementById("lk-rows");
  if (rows) {
    let dragging = null;
    rows.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-lk-move]");
      if (!btn) return;
      const row = btn.closest(".lk-row"), up = btn.dataset.lkMove === "-1";
      const other = up ? row.previousElementSibling : row.nextElementSibling;
      if (other) up ? other.before(row) : other.after(row);
      btn.focus();
    });
    rows.addEventListener("dragstart", (e) => {
      // 입력칸 안에서 글자를 끌 때는 줄을 옮기지 않는다
      if (e.target.closest("input, select, textarea")) { e.preventDefault(); return; }
      dragging = e.target.closest(".lk-row");
      if (!dragging) return;
      dragging.classList.add("is-dragging");
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", dragging.dataset.id);
    });
    rows.addEventListener("dragover", (e) => {
      const over = e.target.closest(".lk-row");
      if (!dragging || !over || over === dragging) return;
      e.preventDefault();
      rows.querySelectorAll(".is-over").forEach((r) => r.classList.remove("is-over"));
      over.classList.add("is-over");
    });
    rows.addEventListener("drop", (e) => {
      const over = e.target.closest(".lk-row");
      if (!dragging || !over || over === dragging) return;
      e.preventDefault();
      const after = [...rows.children].indexOf(dragging) < [...rows.children].indexOf(over);
      after ? over.after(dragging) : over.before(dragging);
    });
    rows.addEventListener("dragend", () => {
      dragging?.classList.remove("is-dragging");
      rows.querySelectorAll(".is-over").forEach((r) => r.classList.remove("is-over"));
      dragging = null;
    });
  }
})();
