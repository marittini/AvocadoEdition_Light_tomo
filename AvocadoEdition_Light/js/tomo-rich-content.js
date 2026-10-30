/* Presentation only: access to private content must still be checked on the server. */
(() => {
  "use strict";
  const selector = ".tomo-spoiler";
  function setup(root) {
    root.querySelectorAll(selector).forEach((node) => {
      if (node.closest(".tomo-editor")) return;
      node.tabIndex = 0;
      node.setAttribute("role", "button");
      node.setAttribute("aria-label", "스포일러 표시 전환");
      node.setAttribute(
        "aria-pressed",
        String(node.classList.contains("tomo-spoiler-visible")),
      );
    });
  }
  function toggle(event) {
    const node = event.target.closest?.(selector);
    if (
      !node ||
      node.closest(".tomo-editor") ||
      (event.type === "keydown" && !["Enter", " "].includes(event.key))
    )
      return;
    event.preventDefault();
    node.setAttribute(
      "aria-pressed",
      String(node.classList.toggle("tomo-spoiler-visible")),
    );
  }
  document.addEventListener("click", toggle);
  document.addEventListener("keydown", toggle);
  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", () => setup(document));
  else setup(document);
})();
