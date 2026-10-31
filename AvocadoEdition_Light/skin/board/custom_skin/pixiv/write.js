(() => {
  const picker = document.querySelector("#pixiv-files"),
    list = document.querySelector("#pixiv-file-list"),
    inputs = document.querySelector("#pixiv-file-inputs"),
    order = document.querySelector("#pixiv-order"),
    cover = document.querySelector("#pixiv-cover");
  const initial = JSON.parse(
      document.querySelector("#pixiv-existing").textContent,
    ),
    limit = Number(
      picker.closest("td").querySelector("p").textContent.match(/\d+/)?.[0] ||
        0,
    );
  let entries = initial.map((x) => ({ ...x, existing: true })),
    deleted = [],
    next = Math.max(-1, ...entries.map((x) => x.id)) + 1,
    dragged = null;
  let coverItem =
    entries.find((x) => String(x.id) === cover.value) || entries[0] || null;
  const category = document.querySelector("#pixiv-type"),
    contentRow = document.querySelector("#pixiv-content-row");
  function updateType() {
    contentRow.hidden = category.value !== "novel";
  }
  category.addEventListener("change", updateType);
  updateType();
  function render() {
    list.replaceChildren();
    entries.forEach((item, i) => {
      const li = document.createElement("li");
      li.draggable = true;
      li.dataset.id = item.id;
      const handle = document.createElement("span");
      handle.className = "px-drag-handle";
      handle.textContent = "⋮⋮";
      handle.title = "마우스로 끌어 순서 변경";
      handle.setAttribute("aria-hidden", "true");
      const im = document.createElement("img");
      im.draggable = false;
      im.src = item.url;
      im.alt = "";
      const name = document.createElement("span");
      name.textContent = item.name;
      const choice = document.createElement("label");
      choice.className = "px-cover-choice";
      const radio = document.createElement("input");
      radio.type = "radio";
      radio.name = "pixiv_cover_choice";
      radio.checked = item === coverItem;
      radio.setAttribute("aria-label", item.name + " 대표 이미지로 선택");
      radio.addEventListener("change", () => {
        coverItem = item;
        render();
      });
      choice.append(
        radio,
        document.createTextNode(
          item === coverItem ? "대표 이미지" : "대표로 지정",
        ),
      );
      li.append(handle, im, name, choice);
      for (const [label, op] of [
        ["앞으로", -1],
        ["뒤로", 1],
        ["삭제", 0],
      ]) {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "ui-btn small";
        b.textContent = label;
        b.disabled =
          op === -1 ? i === 0 : op === 1 ? i === entries.length - 1 : false;
        b.addEventListener("click", () => {
          if (!op) {
            const [gone] = entries.splice(i, 1);
            if (gone === coverItem) coverItem = entries[0] || null;
            if (gone.existing) deleted.push(gone.id);
            else URL.revokeObjectURL(gone.url);
          } else {
            const j = i + op;
            [entries[i], entries[j]] = [entries[j], entries[i]];
          }
          render();
        });
        li.append(b);
      }
      li.addEventListener("dragstart", (e) => {
        dragged = item.id;
        li.classList.add("is-dragging");
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = "move";
          e.dataTransfer.setData("text/plain", String(item.id));
        }
      });
      li.addEventListener("dragend", () => {
        dragged = null;
        list
          .querySelectorAll(".is-dragging,.is-drop-target")
          .forEach((x) => x.classList.remove("is-dragging", "is-drop-target"));
      });
      li.addEventListener("dragover", (e) => {
        if (dragged === null) return;
        e.preventDefault();
        if (dragged !== item.id) li.classList.add("is-drop-target");
      });
      li.addEventListener("dragleave", () =>
        li.classList.remove("is-drop-target"),
      );
      li.addEventListener("drop", (e) => {
        e.preventDefault();
        const from = entries.findIndex((x) => x.id === dragged),
          to = entries.findIndex((x) => x.id === item.id);
        if (from >= 0 && to >= 0) {
          entries.splice(to, 0, entries.splice(from, 1)[0]);
          render();
        }
      });
      list.append(li);
    });
    order.value = entries.map((x) => x.id).join(",");
    cover.value = coverItem ? String(coverItem.id) : "";
  }
  picker.addEventListener("change", () => {
    for (const f of picker.files) {
      if (entries.length >= limit) {
        alert("게시판의 첨부 가능 개수를 초과했습니다.");
        break;
      }
      if (
        !/^image\/(jpeg|png|gif|webp)$/.test(f.type) &&
        !(f.type === "" && /\.(jpe?g|png|gif|webp)$/i.test(f.name))
      ) {
        alert("JPG, PNG, GIF, WebP 이미지만 선택할 수 있습니다.");
        continue;
      }
      const added = {
        id: next++,
        existing: false,
        file: f,
        url: URL.createObjectURL(f),
        name: f.name,
      };
      entries.push(added);
      if (!coverItem) coverItem = added;
    }
    picker.value = "";
    render();
  });
  document
    .querySelector("#pixiv-tag-suggestions")
    .addEventListener("click", (e) => {
      let t = e.target.dataset.suggest;
      if (!t) return;
      let input = document.querySelector("#pixiv-tags"),
        tags = input.value
          .split(",")
          .map((x) => x.trim())
          .filter(Boolean);
      if (!tags.includes(t)) tags.push(t);
      input.value = tags.join(", ");
    });
  window.pixivPrepareFiles = () => {
    inputs.replaceChildren();
    let newId = Math.max(-1, ...initial.map((x) => x.id)) + 1;
    entries.filter((x) => !x.existing).forEach((x) => (x.id = newId++));
    next = newId;
    order.value = entries.map((x) => x.id).join(",");
    cover.value = coverItem ? String(coverItem.id) : "";
    if (
      document.querySelector("#pixiv-type").value !== "novel" &&
      !entries.length
    ) {
      alert("이미지를 1장 이상 첨부해 주세요.");
      return false;
    }
    if (
      next > 100 ||
      order.value.length + (cover.value ? cover.value.length + 3 : 0) > 250
    ) {
      alert("첨부 순서가 너무 깁니다. 게시판 첨부 수를 확인해 주세요.");
      return false;
    }
    for (let i = 0; i < next; i++) {
      const f = document.createElement("input");
      f.type = "file";
      f.name = "bf_file[]";
      f.hidden = true;
      const item = entries.find((x) => x.id === i && !x.existing);
      if (item) {
        try {
          const dt = new DataTransfer();
          dt.items.add(item.file);
          f.files = dt.files;
        } catch (_) {
          alert(
            "이 브라우저에서는 여러 이미지 첨부를 사용할 수 없습니다. 브라우저를 업데이트해 주세요.",
          );
          return false;
        }
      }
      inputs.append(f);
      if (deleted.includes(i)) {
        const d = document.createElement("input");
        d.type = "hidden";
        d.name = "bf_file_del[" + i + "]";
        d.value = "1";
        inputs.append(d);
      }
    }
    return true;
  };
  render();
})();
