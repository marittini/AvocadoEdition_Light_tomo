(() => {
  const picker = document.querySelector("#pixiv-files"),
    list = document.querySelector("#pixiv-file-list"),
    inputs = document.querySelector("#pixiv-file-inputs"),
    order = document.querySelector("#pixiv-order");
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
      const im = document.createElement("img");
      im.src = item.url;
      im.alt = "";
      const name = document.createElement("span");
      name.textContent = item.name;
      li.append(im, name);
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
      li.addEventListener("dragstart", () => (dragged = item.id));
      li.addEventListener("dragover", (e) => e.preventDefault());
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
      entries.push({
        id: next++,
        existing: false,
        file: f,
        url: URL.createObjectURL(f),
        name: f.name,
      });
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
    if (
      document.querySelector("#pixiv-type").value !== "novel" &&
      !entries.length
    ) {
      alert("이미지를 1장 이상 첨부해 주세요.");
      return false;
    }
    if (next > 100 || order.value.length > 250) {
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
