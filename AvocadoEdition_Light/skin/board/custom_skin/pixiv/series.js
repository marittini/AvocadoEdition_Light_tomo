(() => {
  // Series editor: reorder episodes by dragging or with the ↑/↓ buttons.
  const form = document.querySelector("#px-series-form"),
    list = document.querySelector("#px-series-sort"),
    order = document.querySelector("#px-series-order");
  if (!form || !list || !order) return;
  let dragged = null;
  function sync() {
    const items = [...list.children];
    items.forEach((li, i) => {
      li.querySelector(".px-episode-no").textContent = "#" + (i + 1);
      li.querySelector("[data-move='-1']").disabled = i === 0;
      li.querySelector("[data-move='1']").disabled = i === items.length - 1;
    });
    order.value = items.map((li) => li.dataset.id).join(",");
  }
  list.addEventListener("click", (e) => {
    const button = e.target.closest("button");
    if (!button) return;
    const li = button.closest("li");
    if (button.hasAttribute("data-remove")) {
      li.remove();
    } else {
      const step = Number(button.dataset.move);
      const target = step < 0 ? li.previousElementSibling : li.nextElementSibling;
      if (target) step < 0 ? target.before(li) : target.after(li);
      button.focus();
    }
    sync();
  });
  list.addEventListener("dragstart", (e) => {
    dragged = e.target.closest("li");
    dragged?.classList.add("is-dragging");
    e.dataTransfer && (e.dataTransfer.effectAllowed = "move");
  });
  list.addEventListener("dragover", (e) => {
    const li = e.target.closest("li");
    if (!dragged || !li || li === dragged) return;
    e.preventDefault();
    const box = li.getBoundingClientRect();
    e.clientY < box.top + box.height / 2 ? li.before(dragged) : li.after(dragged);
  });
  list.addEventListener("dragend", () => {
    dragged?.classList.remove("is-dragging");
    dragged = null;
    sync();
  });
  form.addEventListener("submit", (e) => {
    const message = e.submitter?.dataset.confirm;
    if (message && !confirm(message)) e.preventDefault();
    sync();
  });
  sync();
})();
