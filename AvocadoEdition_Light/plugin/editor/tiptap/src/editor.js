import { Editor, Extension, Node, Mark } from "@tiptap/core";
import { DOMParser as ProseMirrorDOMParser } from "@tiptap/pm/model";
import { closeHistory } from "@tiptap/pm/history";
import { TextSelection } from "@tiptap/pm/state";
import StarterKit from "@tiptap/starter-kit";
import Image from "@tiptap/extension-image";
import { TextStyleKit } from "@tiptap/extension-text-style";
import TextAlign from "@tiptap/extension-text-align";
import { TableKit } from "@tiptap/extension-table";
import Superscript from "@tiptap/extension-superscript";
import Subscript from "@tiptap/extension-subscript";
import Placeholder from "@tiptap/extension-placeholder";
import { TaskList, TaskItem } from "@tiptap/extension-list";
import {
  Details,
  DetailsSummary,
  DetailsContent,
} from "@tiptap/extension-details";
import Youtube from "@tiptap/extension-youtube";

// Native ruby HTML keeps annotations portable; annotation text is never HTML.
const Ruby = Node.create({
  name: "ruby",
  inline: true,
  group: "inline",
  content: "text*",
  addAttributes() {
    return {
      annotation: {
        default: "",
        rendered: false,
        parseHTML: (element) => element.querySelector("rt")?.textContent || "",
      },
    };
  },
  parseHTML() {
    return [
      {
        tag: "ruby",
        getContent: (element, schema) => {
          const copy = element.cloneNode(true);
          copy.querySelectorAll("rt,rp").forEach((node) => node.remove());
          return ProseMirrorDOMParser.fromSchema(schema).parseSlice(copy).content;
        },
      },
    ];
  },
  renderHTML({ node }) {
    return [
      "ruby",
      {},
      ["span", { class: "tomo-ruby-base" }, 0],
      ["rp", {}, "("],
      ["rt", {}, node.attrs.annotation],
      ["rp", {}, ")"],
    ];
  },
});
const Spoiler = Mark.create({
  name: "spoiler",
  inclusive: false,
  parseHTML() {
    return [
      { tag: "span.tomo-spoiler" },
      { tag: 'span[data-spoiler="true"]' },
      { tag: "span.spoiler-blur" },
    ];
  },
  renderHTML() {
    return ["span", { class: "tomo-spoiler" }, 0];
  },
});
// Image group: images side by side in 2-4 columns. Read pages show it as a CSS grid
// (css/editor-content.css) that turns into one column, in order, on narrow screens.
const GRID_COLS = [2, 3, 4];
const ImageGrid = Node.create({
  name: "imageGrid",
  group: "block",
  content: "image+",
  draggable: true,
  isolating: true,
  addAttributes() {
    return {
      cols: {
        default: 2,
        parseHTML: (el) => {
          const n = Number((/\btomo-grid-(\d)\b/.exec(el.className) || [])[1]);
          return GRID_COLS.includes(n) ? n : 2;
        },
        renderHTML: () => ({}),
      },
    };
  },
  parseHTML() {
    return [{ tag: "div.tomo-grid" }];
  },
  renderHTML({ node }) {
    return ["div", { class: "tomo-grid tomo-grid-" + node.attrs.cols }, 0];
  },
});
// Block styles stored as classes (the server HTML filter keeps class attributes).
const QUOTE_STYLES = [
  ["기본", ""],
  ["상자", "box"],
  ["배경", "fill"],
  ["말풍선", "bubble"],
  ["큰 따옴표", "mark"],
];
const HR_STYLES = [
  ["실선", ""],
  ["점선", "dotted"],
  ["이중선", "double"],
  ["짧은 선", "short"],
  ["굵은 선", "thick"],
];
const classStyle = (prefix, list) => ({
  default: "",
  parseHTML: (el) => {
    const m = new RegExp("\\b" + prefix + "-([a-z]+)\\b").exec(el.className || "");
    return m && list.some(([, v]) => v === m[1]) ? m[1] : "";
  },
});

// Keep attribute edits and drag resizing in sync in the editable node view.
const ProfileImage = Image.extend({
  addNodeView() {
    const create = this.parent?.();
    if (!create) return null;
    return (props) => {
      const view = create(props),
        image = view.dom.querySelector("img");
      const apply = (node) => {
        image.style.width = node.attrs.width ? node.attrs.width + "px" : "";
        image.style.height = node.attrs.height ? node.attrs.height + "px" : "";
        view.dom.style.justifyContent =
          { left: "flex-start", center: "center", right: "flex-end" }[
            node.attrs.imageAlign
          ] || "flex-start";
      };
      const update = view.update.bind(view);
      view.update = (node, ...rest) => {
        const accepted = update(node, ...rest);
        if (accepted) apply(node);
        return accepted;
      };
      apply(props.node);
      return view;
    };
  },
});

const Formatting = Extension.create({
  name: "tomoFormatting",
  addGlobalAttributes() {
    return [
      {
        types: ["textStyle"],
        attributes: {
          letterSpacing: {
            default: null,
            parseHTML: (element) => {
              const value = parseFloat(element.style.letterSpacing);
              return Number.isFinite(value) && value >= -2 && value <= 10
                ? value
                : null;
            },
            renderHTML: (attrs) =>
              Number.isFinite(attrs.letterSpacing)
                ? { style: "letter-spacing:" + attrs.letterSpacing + "px" }
                : {},
          },
        },
      },
      {
        types: ["tableCell", "tableHeader"],
        attributes: {
          backgroundColor: {
            default: null,
            parseHTML: (el) => el.style.backgroundColor || null,
            renderHTML: (attrs) =>
              attrs.backgroundColor
                ? { style: "background-color:" + attrs.backgroundColor }
                : {},
          },
        },
      },
      {
        types: ["paragraph", "heading"],
        attributes: {
          paragraphSpacing: {
            default: null,
            parseHTML: (el) =>
              el.style.marginBottom
                ? parseInt(el.style.marginBottom, 10)
                : null,
            renderHTML: (attrs) =>
              Number.isFinite(attrs.paragraphSpacing)
                ? { style: "margin-bottom:" + attrs.paragraphSpacing + "px" }
                : {},
          },
        },
      },
      {
        types: ["blockquote"],
        attributes: {
          quoteStyle: {
            ...classStyle("tomo-quote", QUOTE_STYLES),
            renderHTML: (attrs) =>
              attrs.quoteStyle ? { class: "tomo-quote-" + attrs.quoteStyle } : {},
          },
        },
      },
      {
        types: ["horizontalRule"],
        attributes: {
          hrStyle: {
            ...classStyle("tomo-hr", HR_STYLES),
            renderHTML: (attrs) =>
              attrs.hrStyle ? { class: "tomo-hr-" + attrs.hrStyle } : {},
          },
        },
      },
      {
        types: ["image"],
        attributes: {
          imageAlign: {
            default: "left",
            parseHTML: (el) =>
              el.style.marginLeft === "auto"
                ? el.style.marginRight === "auto"
                  ? "center"
                  : "right"
                : "left",
            renderHTML: (attrs) =>
              attrs.imageAlign === "center"
                ? { style: "margin-left:auto;margin-right:auto" }
                : attrs.imageAlign === "right"
                  ? { style: "margin-left:auto;margin-right:0" }
                  : {},
          },
        },
      },
    ];
  },
});

// Paragraph wrappers and invisible characters are not authored content.
// Non-text blocks (including images, tables and media) must survive this check.
function editorHasContent(editor) {
  let meaningful = false;
  editor.state.doc.descendants((node) => {
    if (meaningful) return false;
    if (node.isText) meaningful = /[^\s\p{Cf}]/u.test(node.text || "");
    else if (
      (node.isAtom && node.type.name !== "hardBreak") ||
      ["table", "details", "taskList"].includes(node.type.name)
    )
      meaningful = true;
    return !meaningful;
  });
  return meaningful;
}

const editors = new Map();
window.tomoEditors = editors;
window.tomoEditorSync = (id) => {
  const item = editors.get(id);
  if (item)
    item.field.value = editorHasContent(item.editor)
      ? item.editor.getHTML()
      : "";
};
window.tomoEditorValidate = (id) => {
  const item = editors.get(id);
  if (!item) return false;
  if (item.sourceDirty) {
    item.status.textContent = "HTML 변경사항을 적용한 뒤 저장해 주세요.";
    return false;
  }
  if (item.uploading) {
    item.status.textContent = "이미지 업로드가 끝난 뒤 저장해 주세요.";
    return false;
  }
  window.tomoEditorSync(id);
  if (!editorHasContent(item.editor)) {
    item.status.textContent = "내용을 입력해 주세요.";
    item.editor.commands.focus();
    return false;
  }
  return true;
};
window.get_editor_wr_content = () => {
  window.tomoEditorSync("wr_content");
  return document.getElementById("wr_content")?.value || "";
};
window.put_editor_wr_content = (content) =>
  editors.get("wr_content")?.editor.commands.setContent(content);

const icons = {
  format: "M4 5h16 M12 5v15 M8 20h8 M18 14v8 M15 17l3-3 3 3",
  source: "M8 6l-6 7 6 7 M16 6l6 7-6 7 M14 3l-4 20",
  search: "M15 15l7 7 M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14",
  replace: "M4 5h9 M4 10h6 M14 16h7 M18 12l4 4-4 4 M5 20V15h5",
  apply: "M4 13l5 5L21 6",
  close: "M5 5l14 16 M19 5L5 21",
  superscript: "M4 11l8 11 M12 11L4 22 M16 3h5l-5 6h5",
  subscript: "M4 4l8 11 M12 4L4 15 M16 16h5l-5 6h5",
  checklist: "M3 6l2 2 4-5 M12 6h9 M3 15l2 2 4-5 M12 15h9 M12 22h9",
  codeblock: "M2 4h20v18H2z M8 10l-3 3 3 3 M16 10l3 3-3 3",
  hr: "M3 13h18",
  collapse: "M4 4h16v18H4z M8 9l4 4 4-4 M8 18h8",
  ruby: "M4 11l5-5 5 5 M6 10v12 M12 10v12 M6 16h6 M17 3h5 M19 3v5",
  spoiler:
    "M2 13s4-7 10-7 10 7 10 7-4 7-10 7S2 13 2 13 M3 3l18 20 M10 11a3 3 0 0 0 4 4",
  gallery: "M7 3h15v17H7z M2 7v17h15 M9 16l4-5 3 3 2-2 3 4 M17 7h.1",
  refresh: "M20 8A9 9 0 1 0 21 17 M20 2v6h-6",
  insert: "M12 3v18 M3 12h18",
  remove: "M3 12h18",
  trash: "M3 6h18 M9 6V3h6v3 M5 6l1 17h12l1-17 M10 10v9 M14 10v9",
  merge: "M3 4h18v18H3z M3 13h5 M21 13h-5 M6 10l3 3-3 3 M18 10l-3 3 3 3",
  split: "M3 4h18v18H3z M12 4v18 M9 10l-3 3 3 3 M15 10l3 3-3 3",
  rowAbove: "M3 9h18v13H3z M3 15h18 M9 5h6 M12 2v6",
  rowBelow: "M3 3h18v13H3z M3 9h18 M9 21h6 M12 18v6",
  rowDelete: "M3 4h18v18H3z M3 10h18 M3 16h18 M8 13h8",
  colLeft: "M9 3h13v19H9z M15 3v19 M2 12h6 M5 9v6",
  colRight: "M2 3h13v19H2z M8 3v19 M17 12h6 M20 9v6",
  colDelete: "M3 4h18v18H3z M9 4v18 M15 4v18 M11 12h2",
  headerRow: "M3 4h18v18H3z M3 10h18 M5 7h14 M3 16h18 M9 10v12 M15 10v12",
  headerCol: "M3 4h18v18H3z M9 4v18 M6 6v14 M15 4v18 M9 10h12 M9 16h12",
  alignLeft: "M3 5h18 M3 10h12 M3 15h18 M3 20h12",
  alignCenter: "M3 5h18 M6 10h12 M3 15h18 M6 20h12",
  alignRight: "M3 5h18 M9 10h12 M3 15h18 M9 20h12",
  video: "M3 4h18v18H3z M10 9l6 4-6 4z",
  undo: "M3 10h10a7 7 0 0 1 0 14 M3 10l6-6 M3 10l6 6",
  redo: "M21 10H11a7 7 0 0 0 0 14 M21 10l-6-6 M21 10l-6 6",
  bold: "M6 4h7a4 4 0 0 1 0 8H6z M6 12h8a4 4 0 0 1 0 8H6z",
  italic: "M10 4h10 M4 20h10 M15 4 9 20",
  underline: "M6 3v7a6 6 0 0 0 12 0V3 M4 21h16",
  strike: "M5 4h14 M12 4v16 M4 12h16",
  list: "M9 6h12 M9 12h12 M9 18h12 M3 6h.01 M3 12h.01 M3 18h.01",
  ordered: "M10 6h11 M10 12h11 M10 18h11 M3 4h1v5 M3 14h2l-2 5h3",
  quote: "M4 6h6v7H5l-1 5 M14 6h6v7h-5l-1 5",
  link: "M10 14l4-4 M8 16l-2 2a4 4 0 0 1-6-6l4-4a4 4 0 0 1 6 0 M14 8l2-2a4 4 0 0 1 6 6l-4 4a4 4 0 0 1-6 0",
  image: "M3 3h18v18H3z M3 17l6-6 4 4 3-3 5 5 M15 7h.01",
  table: "M3 4h18v16H3z M3 9h18 M3 14h18 M9 4v16 M15 4v16",
  full: "M8 3H3v5 M16 3h5v5 M21 16v5h-5 M8 21H3v-5",
  clear: "M4 4l16 16 M10 4h10 M15 4l-4 12 M4 20h8",
  alignJustify: "M3 5h18 M3 10h18 M3 15h18 M3 20h18",
  palette: "M12 3a10 10 0 1 0 0 20c1.5 0 2-1 2-2s-1-1.5-1-2.5 1-2 2.5-2H18a4 4 0 0 0 4-4C22 7 17.5 3 12 3z M7.5 12h.01 M9.5 7.5h.01 M14.5 7.5h.01",
  grid: "M3 4h8v8H3z M13 4h8v8h-8z M3 14h8v8H3z M13 14h8v8h-8z",
  symbol: "M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6-4.5-4.2 6.1-.7z",
  more: "M4 13a1.6 1.6 0 1 0 3.2 0 1.6 1.6 0 1 0-3.2 0 M10.4 13a1.6 1.6 0 1 0 3.2 0 1.6 1.6 0 1 0-3.2 0 M16.8 13a1.6 1.6 0 1 0 3.2 0 1.6 1.6 0 1 0-3.2 0",
};
const svg = (key) =>
  '<svg viewBox="0 0 24 26" width="18" height="18" aria-hidden="true"><path d="' +
  icons[key] +
  '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
function iconify(button, label, icon) {
  button.classList.add("tomo-editor-icon-button");
  button.title = label;
  button.setAttribute("aria-label", label);
  button.innerHTML = svg(icon);
  const text = document.createElement("span");
  text.className = "tomo-editor-sr-only";
  text.textContent = label;
  button.append(text);
}
function loadEditorFontFaces() {
  const data = document.getElementById("tomo-editor-font-css");
  if (!data || document.getElementById("tomo-editor-loaded-fonts")) return;
  const style = document.createElement("style");
  style.id = "tomo-editor-loaded-fonts";
  style.media = "not all";
  style.textContent = JSON.parse(data.textContent || '""');
  document.head.append(style);
  // Load font declarations without applying the site's theme selectors to the admin.
  const collect = (rules) =>
    [...rules]
      .map((rule) => {
        if ([CSSRule.FONT_FACE_RULE, CSSRule.IMPORT_RULE].includes(rule.type))
          return rule.cssText;
        if (rule.cssRules && rule.conditionText) {
          const inner = collect(rule.cssRules);
          return inner
            ? "@media " + rule.conditionText + "{" + inner + "}"
            : "";
        }
        return "";
      })
      .join("\n");
  const fonts = collect(style.sheet.cssRules);
  style.textContent = fonts;
  style.media = "all";
}
function init() {
  loadEditorFontFaces();

  document.querySelectorAll(".tomo-rich-editor").forEach((field) => {
    if (editors.has(field.id)) return;
    const wrapper = field.closest(".tomo-editor"),
      status = wrapper.querySelector("[role=status]");
    const toolbar = document.createElement("div");
    toolbar.className = "tomo-editor-toolbar";
    toolbar.setAttribute("role", "toolbar");
    toolbar.setAttribute("aria-label", "본문 서식");
    const canvas = document.createElement("div");
    canvas.className = "tomo-editor-canvas";
    const footer = document.createElement("div");
    footer.className = "tomo-editor-footer";
    const hint = document.createElement("span");
    hint.className = "tomo-editor-hint";
    hint.textContent = "도구에 초점을 두면 이름이 표시됩니다.";
    hint.setAttribute("aria-live", "polite");
    footer.append(hint);
    const describe = (event) => {
      const control = event.target.closest(
        "button[aria-label],select[aria-label],input[aria-label]",
      );
      if (control) hint.textContent = control.getAttribute("aria-label");
    };
    wrapper.addEventListener("focusin", describe);
    wrapper.addEventListener("pointerover", describe);
    const count = document.createElement("span");
    footer.append(count);
    field.before(toolbar, canvas);
    field.after(footer);
    field.hidden = true;
    const item = {
      field,
      wrapper,
      status,
      uploading: 0,
      editor: null,
      gallery: [],
    };
    editors.set(field.id, item);
    const rememberEditorSelection = () => {
      const view = item.editor?.view,
        selection = document.getSelection();
      if (
        !view ||
        !(item.editor.state.selection instanceof TextSelection) ||
        !selection?.anchorNode ||
        !selection.focusNode ||
        !view.dom.contains(selection.anchorNode) ||
        !view.dom.contains(selection.focusNode)
      )
        return;
      try {
        const anchor = view.posAtDOM(
            selection.anchorNode,
            selection.anchorOffset,
          ),
          head = view.posAtDOM(selection.focusNode, selection.focusOffset),
          doc = item.editor.state.doc;
        const next = TextSelection.between(
          doc.resolve(anchor),
          doc.resolve(head),
        );
        if (!next.eq(item.editor.state.selection))
          view.dispatch(item.editor.state.tr.setSelection(next));
      } catch {
        /* A just-removed DOM node must not replace the current selection. */
      }
    };
    const focused = () => {
      item.editor.view.focus();
      return item.editor.chain();
    };

    // Footer: words and characters (spaces included), like the counters in other editors.
    const countText = () => {
      const text = item.editor.getText(),
        words = (text.trim().match(/\S+/g) || []).length,
        nf = new Intl.NumberFormat("ko-KR");
      return nf.format(words) + "단어 · " + nf.format(text.length) + "자";
    };
    const sync = () => {
      window.tomoEditorSync(field.id);
      count.textContent = countText();
      field.dispatchEvent(new Event("input", { bubbles: true }));
    };
    const upload = async (files, { insert = true } = {}) => {
      let position = item.editor.state.selection.from;
      const done = [];
      // Count the whole batch so saving cannot race between two uploads.
      item.uploading += files.length;
      for (const file of files) {
        status.textContent = "이미지를 업로드하고 있습니다.";
        try {
          if (file.size > 20 * 1024 * 1024)
            throw new Error("이미지는 20MB 이하로 첨부해 주세요.");
          const data = new FormData();
          data.set("image", file);
          data.set("editor_token", wrapper.dataset.token);
          const response = await fetch(wrapper.dataset.upload, {
            method: "POST",
            body: data,
            credentials: "same-origin",
          });
          let result;
          try {
            result = await response.json();
          } catch {
            throw new Error(
              "이미지를 저장하지 못했습니다. 서버 업로드 한도를 확인해 주세요.",
            );
          }
          if (!response.ok || !result.url)
            throw new Error(result.error || "이미지 업로드에 실패했습니다.");
          const image = { src: result.url, alt: file.name, selected: true };
          item.gallery.push(image);
          done.push({ src: result.url, alt: file.name });
          const mapped = Math.min(position, item.editor.state.doc.content.size);
          if (insert)
            focused()
              .insertContentAt(mapped, {
                type: "image",
                attrs: { src: result.url, alt: file.name },
              })
              .run();
          position = item.editor.state.selection.from;
          status.textContent =
            result.notice ||
            (insert
              ? "이미지를 첨부했습니다."
              : "업로드 완료 · 선택한 이미지를 본문에 삽입해 주세요.");
          item.renderGallery?.();
        } catch (error) {
          status.textContent = error.message;
        } finally {
          item.uploading--;
        }
      }
      return done;
    };
    item.upload = upload;
    item.editor = new Editor({
      element: canvas,
      content: field.value,
      extensions: [
        StarterKit.configure({
          link: { openOnClick: false, autolink: false },
          heading: { levels: [2, 3, 4] },
        }),
        ProfileImage.configure({
          allowBase64: false,
          resize: {
            enabled: true,
            directions: ["bottom-left", "bottom-right"],
            minWidth: 24,
            minHeight: 24,
            alwaysPreserveAspectRatio: true,
          },
        }),
        TextStyleKit,
        TextAlign.configure({
          types: ["heading", "paragraph"],
          alignments: ["left", "center", "right", "justify"],
        }),
        TableKit.configure({ table: { resizable: true } }),
        Formatting,
        Ruby,
        Spoiler,
        Superscript,
        Subscript,
        TaskList,
        TaskItem.configure({ nested: true }),
        Placeholder.configure({
          placeholder: "내용을 입력하거나 이미지를 붙여넣으세요.",
        }),
        ImageGrid,
        Details.configure({ persist: true }),
        DetailsSummary,
        DetailsContent,
        Youtube.configure({
          nocookie: true,
          HTMLAttributes: { title: "YouTube 동영상" },
        }),
      ],
      editorProps: {
        attributes: {
          "aria-label": "본문 편집",
          role: "textbox",
          "aria-multiline": "true",
        },
        handlePaste: (view, event) => {
          const images = [...(event.clipboardData?.files || [])].filter(
            (file) => file.type.startsWith("image/"),
          );
          if (images.length) {
            event.preventDefault();
            upload(images);
            return true;
          }
          return false;
        },
        handleDrop: (view, event, moved) => {
          const images = [...(event.dataTransfer?.files || [])].filter((file) =>
            file.type.startsWith("image/"),
          );
          if (!moved && images.length) {
            event.preventDefault();
            const pos = view.posAtCoords({
              left: event.clientX,
              top: event.clientY,
            });
            if (pos) item.editor.commands.setTextSelection(pos.pos);
            upload(images);
            return true;
          }
          return false;
        },
      },
      onUpdate: sync,
      onSelectionUpdate: () => refresh(),
    });
    /* ================================================================
       Toolbar: one grouped row (wraps on narrow screens). Rare tools live in
       popovers; image / table / image-group tools appear on their own when the
       cursor is on one (contextual bar), so the toolbar never needs a panel toggle.
       ================================================================ */
    const buttons = [];
    const selects = new Map();
    const el = (tag, cls, text) => {
      const node = document.createElement(tag);
      if (cls) node.className = cls;
      if (text != null) node.textContent = text;
      return node;
    };
    const keepSelection = (node) =>
      node.addEventListener("mousedown", (event) => {
        rememberEditorSelection();
        event.preventDefault();
      });
    // Icon button (the label is the tooltip and the accessible name).
    const button = (parent, name, icon, run, active, disabled, keep = true) => {
      const b = el("button");
      b.type = "button";
      iconify(b, name, icon);
      if (keep) keepSelection(b); // 서식 버튼은 본문 선택을 그대로 둔다(팝업 여는 버튼은 포커스를 받는다)
      else b.addEventListener("mousedown", rememberEditorSelection);
      b.addEventListener("click", () => {
        run();
        refresh();
      });
      parent.append(b);
      buttons.push({ el: b, active, disabled });
      return b;
    };
    // Text button for popovers and the contextual bar.
    const textButton = (parent, label, run, disabled, cls) => {
      const b = el("button", cls || "", label);
      b.type = "button";
      keepSelection(b);
      b.addEventListener("click", () => {
        run();
        refresh();
      });
      parent.append(b);
      if (disabled) buttons.push({ el: b, disabled });
      return b;
    };
    const select = (parent, name, options, change) => {
      const s = el("select");
      s.setAttribute("aria-label", name);
      s.title = name;
      options.forEach(([label, value]) => s.add(new Option(label, value)));
      let bookmark = null;
      const remember = () => {
        rememberEditorSelection();
        bookmark = item.editor.state.selection.getBookmark();
      };
      s.addEventListener("pointerdown", remember);
      s.addEventListener("focus", remember);
      s.addEventListener("change", () => {
        const value = s.value;
        if (!bookmark) remember();
        if (bookmark) {
          try {
            item.editor.view.dispatch(
              item.editor.state.tr.setSelection(
                bookmark.resolve(item.editor.state.doc),
              ),
            );
          } catch {
            /* The selection may have been removed by another edit. */
          }
        }
        change(value);
        bookmark = null;
        refresh();
      });
      parent.append(s);
      selects.set(name, s);
      return s;
    };
    const labelInput = (parent, label, type = "text") => {
      const wrap = el("label", "tomo-editor-field");
      wrap.append(el("span", "", label));
      const input = el("input");
      input.type = type;
      input.setAttribute("aria-label", label);
      wrap.append(input);
      parent.append(wrap);
      return input;
    };
    const group = (label) => {
      const g = el("div", "tomo-tb-group");
      g.setAttribute("role", "group");
      g.setAttribute("aria-label", label);
      toolbar.append(g);
      return g;
    };

    // Mode tabs (편집 / HTML) and the wide-screen toggle sit above the toolbar.
    const top = el("div", "tomo-editor-top");
    const modes = el("div", "tomo-editor-modes");
    modes.setAttribute("role", "tablist");
    modes.setAttribute("aria-label", "편집 방식");
    const modeTab = (label) => {
      const b = el("button", "", label);
      b.type = "button";
      b.setAttribute("role", "tab");
      b.setAttribute("aria-label", label);
      modes.append(b);
      return b;
    };
    const visualTab = modeTab("편집"),
      sourceTab = modeTab("HTML");
    top.append(modes);
    toolbar.before(top);

    // Popovers open under the toolbar, one at a time.
    const pops = el("div", "tomo-editor-pops");
    toolbar.after(pops);
    const popovers = [];
    const popover = (parent, name, icon, title, active) => {
      const toggle = button(parent, name, icon, () => {}, active, null, false);
      toggle.setAttribute("aria-expanded", "false");
      const body = el("div", "tomo-editor-pop");
      body.hidden = true;
      body.id = field.id + "-pop-" + popovers.length;
      toggle.setAttribute("aria-controls", body.id);
      body.append(el("p", "tomo-editor-pop-title", title || name));
      pops.append(body);
      const entry = { toggle, body, onOpen: [] };
      popovers.push(entry);
      toggle.addEventListener("click", () => openPop(entry, body.hidden));
      return entry;
    };
    const openPop = (entry, open) => {
      popovers.forEach((p) => {
        p.body.hidden = true;
        p.toggle.setAttribute("aria-expanded", "false");
      });
      if (!entry || !open) return;
      entry.body.hidden = false;
      entry.toggle.setAttribute("aria-expanded", "true");
      entry.onOpen.forEach((fn) => fn());
      entry.body.querySelector("input:not([type=file]):not([type=color]), textarea")?.focus({ preventScroll: true });
    };
    const closePops = () => openPop(null, false);
    wrapper.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && popovers.some((p) => !p.body.hidden)) {
        event.preventDefault();
        closePops();
        item.editor.commands.focus();
      }
    });

    /* ---------- 1. 문단 · 글꼴 · 크기 ---------- */
    const gText = group("글자 모양");
    select(
      gText,
      "문단 스타일",
      [
        ["본문", "paragraph"],
        ["큰 제목", "2"],
        ["중간 제목", "3"],
        ["작은 제목", "4"],
      ],
      (value) =>
        value === "paragraph"
          ? focused().setParagraph().run()
          : focused()
              .toggleHeading({ level: Number(value) })
              .run(),
    );
    select(
      gText,
      "글꼴",
      [
        ["기본 글꼴", ""],
        ["고딕", "sans-serif"],
        ["명조", "serif"],
        ["고정폭", "monospace"],
        ...JSON.parse(wrapper.dataset.fonts || "[]").map((font) => [
          font.label,
          font.family,
        ]),
      ],
      (value) =>
        value
          ? focused().setFontFamily(value).run()
          : focused().unsetFontFamily().run(),
    );
    select(
      gText,
      "글자 크기",
      [
        ["기본 크기", ""],
        ...["12", "13", "14", "15", "16", "18", "20", "24", "28", "32", "40"].map(
          (size) => [size + "px", size + "px"],
        ),
      ],
      (value) =>
        value
          ? focused().setFontSize(value).run()
          : focused().unsetFontSize().run(),
    );

    /* ---------- 2. 굵게 · 기울임 · 밑줄 · 취소선 · 색 ---------- */
    const gMark = group("글자 서식");
    for (const [name, icon, command, type] of [
      ["굵게", "bold", "toggleBold", "bold"],
      ["기울임", "italic", "toggleItalic", "italic"],
      ["밑줄", "underline", "toggleUnderline", "underline"],
      ["취소선", "strike", "toggleStrike", "strike"],
    ])
      button(
        gMark,
        name,
        icon,
        () => focused()[command]().run(),
        () => item.editor.isActive(type),
      );
    const colorPop = popover(gMark, "색상", "palette", "글자 색 · 형광펜");
    const SWATCHES = ["#49393c", "#7a7a7a", "#c0392b", "#d35400", "#b7950b", "#1e8449", "#1f618d", "#6c3483", "#c2185b", "#ffffff"];
    const HIGHLIGHTS = ["#fff0ad", "#ffd8b8", "#ffd1dc", "#d7f5d0", "#d3ecff", "#e6dcff", "#eeeeee"];
    const swatchRow = (title, colors, apply, clear, custom) => {
      const row = el("div", "tomo-editor-swatches");
      row.append(el("span", "tomo-editor-swatch-title", title));
      colors.forEach((c) => {
        const b = el("button", "tomo-editor-swatch");
        b.type = "button";
        b.style.background = c;
        b.title = title + " " + c;
        b.setAttribute("aria-label", title + " " + c);
        keepSelection(b);
        b.addEventListener("click", () => {
          apply(c);
          refresh();
        });
        row.append(b);
      });
      const pick = el("input");
      pick.type = "color";
      pick.value = custom;
      pick.setAttribute("aria-label", title + " 직접 고르기");
      pick.title = "직접 고르기";
      pick.addEventListener("input", () => apply(pick.value));
      row.append(pick);
      textButton(row, "없애기", clear, null, "tomo-editor-plain");
      colorPop.body.append(row);
      return pick;
    };
    swatchRow(
      "글자 색상",
      SWATCHES,
      (c) => focused().setColor(c).run(),
      () => focused().unsetColor().run(),
      "#49393c",
    );
    swatchRow(
      "글자 배경색",
      HIGHLIGHTS,
      (c) => focused().setBackgroundColor(c).run(),
      () => focused().unsetBackgroundColor().run(),
      "#fff0ad",
    );

    /* ---------- 3. 정렬 ---------- */
    const gAlign = group("정렬");
    const alignOf = () =>
      item.editor.getAttributes(item.editor.isActive("heading") ? "heading" : "paragraph").textAlign || "left";
    for (const [name, icon, value] of [
      ["왼쪽 정렬", "alignLeft", "left"],
      ["가운데 정렬", "alignCenter", "center"],
      ["오른쪽 정렬", "alignRight", "right"],
      ["양쪽 정렬", "alignJustify", "justify"],
    ])
      button(
        gAlign,
        name,
        icon,
        () => focused().setTextAlign(value).run(),
        () => alignOf() === value,
      );

    /* ---------- 4. 줄 간격 · 자간 ---------- */
    const gSpace = group("간격");
    select(
      gSpace,
      "줄 간격",
      [["줄 간격", ""], ...["1.0", "1.2", "1.4", "1.6", "1.8", "2.0", "2.4", "3.0"].map((v) => [v, v])],
      (value) =>
        value
          ? focused().setLineHeight(value).run()
          : focused().unsetLineHeight().run(),
    );
    select(
      gSpace,
      "자간",
      [["자간", ""], ...["-1", "-0.5", "0.5", "1", "1.5", "2", "3", "5"].map((v) => [v + "px", v])],
      (value) =>
        value === ""
          ? focused().setMark("textStyle", { letterSpacing: null }).removeEmptyTextStyle().run()
          : focused().setMark("textStyle", { letterSpacing: Number(value) }).run(),
    );

    /* ---------- 5. 목록 · 표 ---------- */
    const gList = group("목록 · 표");
    button(gList, "글머리 목록", "list", () => focused().toggleBulletList().run(), () => item.editor.isActive("bulletList"));
    button(gList, "번호 목록", "ordered", () => focused().toggleOrderedList().run(), () => item.editor.isActive("orderedList"));
    button(gList, "체크리스트", "checklist", () => focused().toggleTaskList().run(), () => item.editor.isActive("taskList"));
    button(gList, "표 삽입", "table", () =>
      focused().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    );

    /* ---------- 6. 인용 · 구분선 · 접기 · 루비 · 스포일러 ---------- */
    const gBlock = group("꾸미기");
    const quotePop = popover(gBlock, "인용", "quote", "인용구 모양", () => item.editor.isActive("blockquote"));
    const quoteRow = el("div", "tomo-editor-choices");
    QUOTE_STYLES.forEach(([label, value]) => {
      const b = textButton(quoteRow, label, () => {
        const chain = focused();
        if (!item.editor.isActive("blockquote")) chain.setBlockquote();
        chain.updateAttributes("blockquote", { quoteStyle: value }).run();
        closePops();
      }, null, "tomo-editor-choice tomo-quote-sample" + (value ? " tomo-quote-" + value : ""));
      b.setAttribute("aria-label", "인용구 " + label);
    });
    textButton(quoteRow, "인용 해제", () => {
      focused().unsetBlockquote().run();
      closePops();
    }, () => !item.editor.isActive("blockquote"), "tomo-editor-plain");
    quotePop.body.append(quoteRow);
    const hrPop = popover(gBlock, "구분선", "hr", "구분선 모양");
    const hrRow = el("div", "tomo-editor-choices");
    HR_STYLES.forEach(([label, value]) => {
      const b = textButton(hrRow, "", () => {
        focused().insertContent({ type: "horizontalRule", attrs: { hrStyle: value } }).run();
        closePops();
      }, null, "tomo-editor-choice tomo-editor-hr-choice");
      const sample = el("hr", value ? "tomo-hr-" + value : "");
      b.append(sample, el("span", "", label));
      b.setAttribute("aria-label", "구분선 " + label);
    });
    hrPop.body.append(hrRow);
    button(gBlock, "접기·펼치기", "collapse", () => focused().setDetails().run());
    const rubyPop = popover(gBlock, "루비", "ruby", "루비(읽는 법 · 설명)", () => item.editor.isActive("ruby"));
    rubyPop.body.append(el("p", "tomo-editor-help", "한 문단 안에서 글자를 선택한 뒤 읽는 법이나 설명을 적으세요."));
    const annotation = labelInput(rubyPop.body, "루비 발음·설명");
    annotation.maxLength = 200;
    const showAnnotation = () => {
      if (document.activeElement !== annotation)
        annotation.value = item.editor.isActive("ruby") ? item.editor.getAttributes("ruby").annotation || "" : "";
    };
    rubyPop.onOpen.push(showAnnotation);
    item.editor.on("selectionUpdate", () => {
      if (!rubyPop.body.hidden) showAnnotation();
    });
    const rubyActions = el("div", "tomo-editor-pop-actions");
    rubyPop.body.append(rubyActions);
    textButton(rubyActions, "루비 적용", () => {
      const editor = item.editor,
        text = annotation.value.trim();
      if (!text || text.length > 200) {
        status.textContent = "루비 설명을 1~200자로 입력해 주세요.";
        return;
      }
      if (editor.isActive("ruby")) {
        focused().updateAttributes("ruby", { annotation: text }).run();
        return;
      }
      const { from, to, $from, $to } = editor.state.selection;
      if (from === to || !$from.sameParent($to) || !$from.parent.isTextblock) {
        status.textContent = "한 문단 안에서 루비를 붙일 글자를 선택해 주세요.";
        return;
      }
      let hasMedia = false;
      editor.state.doc.nodesBetween(from, to, (node) => {
        if ((node.isAtom && !node.isText) || node.type.name === "ruby") hasMedia = true;
      });
      if (hasMedia) {
        status.textContent = "이미지가 아닌 글자를 선택해 주세요.";
        return;
      }
      const base = editor.state.doc.slice(from, to).content.toJSON();
      focused().insertContent({ type: "ruby", attrs: { annotation: text }, content: base }).run();
    }, null, "tomo-editor-primary");
    textButton(rubyActions, "루비 해제", () => {
      const { doc, selection } = item.editor.state,
        changes = [];
      doc.descendants((node, pos) => {
        if (node.type.name === "ruby" && pos < selection.to && pos + node.nodeSize > selection.from) {
          changes.push({ node, pos });
          return false;
        }
      });
      if (!changes.length) {
        status.textContent = "해제할 루비 안에 커서를 두거나 루비를 선택해 주세요.";
        return;
      }
      const tr = item.editor.state.tr;
      changes.reverse().forEach(({ node, pos }) => tr.replaceWith(pos, pos + node.nodeSize, node.content));
      item.editor.view.dispatch(tr);
      item.editor.commands.focus();
    });
    button(gBlock, "스포일러 블러", "spoiler", () => focused().toggleMark("spoiler").run(), () => item.editor.isActive("spoiler"));

    /* ---------- 7. 링크 · 이미지 · 이미지 묶음 · 동영상 · 기호 ---------- */
    const gInsert = group("넣기");
    const linkPop = popover(gInsert, "링크", "link", "링크", () => item.editor.isActive("link"));
    const linkInput = labelInput(linkPop.body, "링크 주소", "url");
    linkInput.placeholder = "https://example.com";
    let linkSelection = null;
    linkPop.onOpen.push(() => {
      linkSelection = item.editor.state.selection;
      linkInput.value = item.editor.getAttributes("link").href || "";
    });
    const linkActions = el("div", "tomo-editor-pop-actions");
    linkPop.body.append(linkActions);
    const applyLink = () => {
      try {
        const url = new URL(linkInput.value, location.href);
        if (!["https:", "http:", "mailto:"].includes(url.protocol)) throw Error();
        focused()
          .setTextSelection({ from: linkSelection.from, to: linkSelection.to })
          .extendMarkRange("link")
          .setLink({ href: url.href })
          .run();
        closePops();
      } catch {
        status.textContent = "https:// 또는 http:// 주소를 입력해 주세요.";
      }
    };
    textButton(linkActions, "적용", applyLink, null, "tomo-editor-primary");
    linkInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        applyLink();
      }
    });
    textButton(linkActions, "링크 해제", () => {
      focused().setTextSelection({ from: linkSelection.from, to: linkSelection.to }).unsetLink().run();
      closePops();
    });

    // Hidden file input used by "이미지 첨부" (insert at the cursor).
    const file = el("input");
    file.type = "file";
    file.setAttribute("aria-label", "본문 이미지 첨부");
    file.accept = "image/png,image/jpeg,image/gif,image/webp";
    file.multiple = true;
    file.hidden = true;
    wrapper.append(file);
    let fileTarget = "insert";
    file.addEventListener("change", async () => {
      const files = [...file.files];
      file.value = "";
      if (!files.length) return;
      if (fileTarget === "grid") {
        const uploaded = await upload(files, { insert: false });
        insertGrid(uploaded, Number(gridCols.value));
      } else if (fileTarget === "grid-add") {
        const uploaded = await upload(files, { insert: false });
        addToGrid(uploaded);
      } else upload(files);
      fileTarget = "insert";
      renderGallery();
    });
    const chooseFiles = (target) => {
      fileTarget = target;
      file.click();
    };

    const imagePop = popover(gInsert, "이미지", "image", "이미지");
    const imageTop = el("div", "tomo-editor-pop-actions");
    imagePop.body.append(imageTop);
    textButton(imageTop, "이미지 첨부", () => chooseFiles("insert"), null, "tomo-editor-primary");
    const drop = el("p", "tomo-editor-drop", "본문에 그림을 끌어 놓거나 붙여 넣어도 됩니다. 아래에서 고른 그림을 넣거나 묶음으로 넣을 수 있습니다.");
    imagePop.body.append(drop);
    const galleryUpload = el("input");
    galleryUpload.type = "file";
    galleryUpload.accept = file.accept;
    galleryUpload.multiple = true;
    galleryUpload.setAttribute("aria-label", "관리창 이미지 업로드");
    const galleryUploadLabel = el("label", "tomo-editor-field tomo-editor-upload");
    galleryUploadLabel.append(el("span", "", "목록에만 올리기"), galleryUpload);
    imageTop.append(galleryUploadLabel);
    const galleryList = el("div", "tomo-editor-gallery");
    imagePop.body.append(galleryList);
    const galleryActions = el("div", "tomo-editor-pop-actions");
    imagePop.body.append(galleryActions);
    const currentImages = () => {
      const result = new Map();
      item.editor.state.doc.descendants((node, pos) => {
        if (node.type.name !== "image") return;
        const entry = result.get(node.attrs.src) || { src: node.attrs.src, alt: node.attrs.alt || "이미지", positions: [] };
        entry.positions.push({ pos, size: node.nodeSize });
        result.set(node.attrs.src, entry);
      });
      return result;
    };
    const chosenImages = new Set();
    const renderGallery = () => {
      const current = currentImages();
      item.gallery.forEach((image) => {
        if (!current.has(image.src)) current.set(image.src, { ...image, positions: [] });
        if (image.selected) {
          chosenImages.add(image.src);
          image.selected = false;
        }
      });
      for (const url of chosenImages) if (!current.has(url)) chosenImages.delete(url);
      galleryList.replaceChildren();
      if (!current.size) galleryList.textContent = "이 글에 이미지가 없습니다. '이미지 첨부'로 올려 주세요.";
      current.forEach((image) => {
        const card = el("label", "tomo-editor-gallery-card");
        const check = el("input");
        check.type = "checkbox";
        check.checked = chosenImages.has(image.src);
        check.setAttribute("aria-label", image.alt + " 선택");
        check.addEventListener("change", () => (check.checked ? chosenImages.add(image.src) : chosenImages.delete(image.src)));
        const thumb = el("img");
        thumb.src = image.src;
        thumb.alt = "";
        thumb.loading = "lazy";
        card.append(check, thumb, el("span", "", image.alt), el("small", "", image.positions.length ? "본문 " + image.positions.length + "개" : "본문 밖 · 서버에 업로드됨"));
        galleryList.append(card);
      });
      return current;
    };
    item.renderGallery = renderGallery;
    imagePop.onOpen.push(renderGallery);
    galleryUpload.addEventListener("change", async () => {
      galleryUpload.disabled = true;
      try {
        await upload([...galleryUpload.files], { insert: false });
      } finally {
        galleryUpload.disabled = false;
        galleryUpload.value = "";
        renderGallery();
      }
    });
    const chosenContent = () => {
      const current = currentImages();
      item.gallery.forEach((image) => {
        if (!current.has(image.src)) current.set(image.src, image);
      });
      return [...chosenImages].map((src) => current.get(src)).filter(Boolean).map((image) => ({ src: image.src, alt: image.alt }));
    };
    textButton(galleryActions, "선택 이미지 삽입", () => {
      if (item.uploading) {
        status.textContent = "업로드가 끝난 뒤 삽입해 주세요.";
        return;
      }
      const content = chosenContent().map((attrs) => ({ type: "image", attrs }));
      if (!content.length) {
        status.textContent = "삽입할 이미지를 선택해 주세요.";
        return;
      }
      focused().insertContent(content).run();
      chosenImages.clear();
      renderGallery();
    }, null, "tomo-editor-primary");
    textButton(galleryActions, "선택 이미지 묶어 넣기", () => {
      const images = chosenContent();
      if (images.length < 2) {
        status.textContent = "묶을 이미지를 두 장 이상 선택해 주세요.";
        return;
      }
      insertGrid(images, Math.min(4, images.length));
      chosenImages.clear();
      renderGallery();
    });
    textButton(galleryActions, "선택 이미지 본문에서 제거", () => {
      const current = currentImages(),
        changes = [...chosenImages].flatMap((src) => current.get(src)?.positions || []);
      const tr = item.editor.state.tr;
      changes.sort((a, b) => b.pos - a.pos).forEach(({ pos, size }) => tr.delete(pos, pos + size));
      if (changes.length) item.editor.view.dispatch(closeHistory(tr));
      chosenImages.clear();
      renderGallery();
      status.textContent = "선택 이미지를 본문에서 제거했습니다. 서버 파일은 보관하며 실행 취소로 되돌릴 수 있습니다.";
    });
    textButton(galleryActions, "선택 업로드 목록에서 제외", () => {
      item.gallery = item.gallery.filter((image) => !chosenImages.has(image.src));
      chosenImages.clear();
      renderGallery();
      status.textContent = "이번 업로드 목록에서 제외했습니다. 본문과 서버 파일은 유지됩니다.";
    });

    /* Image group (imageGrid): helpers shared by the toolbar popover and the contextual bar. */
    const gridAt = () => {
      const { selection } = item.editor.state;
      if (selection.node?.type.name === "imageGrid") return { node: selection.node, pos: selection.from };
      const $from = selection.$from;
      for (let d = $from.depth; d > 0; d--)
        if ($from.node(d).type.name === "imageGrid") return { node: $from.node(d), pos: $from.before(d) };
      return null;
    };
    const imageNodes = (images) =>
      images.map((attrs) => item.editor.schema.nodes.image.create({ src: attrs.src, alt: attrs.alt || "" }));
    function insertGrid(images, cols) {
      if (!images.length) return;
      const grid = item.editor.schema.nodes.imageGrid.create({ cols: GRID_COLS.includes(cols) ? cols : 2 }, imageNodes(images));
      const { tr } = item.editor.state;
      tr.replaceSelectionWith(grid);
      item.editor.view.dispatch(tr.scrollIntoView());
      item.editor.commands.focus();
      status.textContent = "이미지 묶음을 넣었습니다. 읽는 화면에서 휴대폰은 한 줄에 하나씩 보입니다.";
    }
    function addToGrid(images) {
      const at = gridAt();
      if (!at || !images.length) return;
      const tr = item.editor.state.tr.insert(at.pos + at.node.nodeSize - 1, imageNodes(images));
      item.editor.view.dispatch(tr);
    }
    // Wrap the selected images (top-level blocks) into one group at the first one's place.
    const groupSelectedImages = (cols) => {
      const { doc, selection } = item.editor.state;
      const found = [];
      doc.forEach((node, offset) => {
        if (node.type.name === "image" && offset < selection.to && offset + node.nodeSize > selection.from) found.push({ node, pos: offset });
      });
      if (found.length < 2) {
        status.textContent = "본문에서 이미지 두 장 이상을 드래그로 함께 선택한 뒤 묶어 주세요.";
        return;
      }
      const grid = item.editor.schema.nodes.imageGrid.create({ cols }, found.map((f) => f.node));
      const tr = item.editor.state.tr;
      [...found].reverse().forEach((f) => tr.delete(tr.mapping.map(f.pos), tr.mapping.map(f.pos + f.node.nodeSize)));
      tr.insert(tr.mapping.map(found[0].pos), grid);
      item.editor.view.dispatch(tr);
      status.textContent = found.length + "장을 묶었습니다.";
    };
    const gridPop = popover(gInsert, "이미지 묶음", "grid", "이미지 묶음(나란히)");
    gridPop.body.append(el("p", "tomo-editor-help", "그림을 2~4장씩 나란히 놓습니다. 세로 길이는 그림에 맞춰 저절로 정해지고, 휴대폰에서는 한 줄에 하나씩 차례대로 보입니다."));
    const gridColsWrap = el("label", "tomo-editor-field");
    gridColsWrap.append(el("span", "", "한 줄에"));
    const gridCols = el("select");
    gridCols.setAttribute("aria-label", "묶음 열 수");
    GRID_COLS.forEach((n) => gridCols.add(new Option(n + "장", String(n))));
    gridColsWrap.append(gridCols);
    gridPop.body.append(gridColsWrap);
    const gridActions = el("div", "tomo-editor-pop-actions");
    gridPop.body.append(gridActions);
    textButton(gridActions, "그림 올려서 묶기", () => {
      closePops();
      chooseFiles("grid");
    }, null, "tomo-editor-primary");
    textButton(gridActions, "선택한 그림 묶기", () => {
      groupSelectedImages(Number(gridCols.value));
      closePops();
    });

    const videoPop = popover(gInsert, "동영상", "video", "유튜브 동영상");
    const videoUrl = labelInput(videoPop.body, "유튜브 주소");
    videoUrl.placeholder = "https://www.youtube.com/watch?v=…";
    const videoActions = el("div", "tomo-editor-pop-actions");
    videoPop.body.append(videoActions);
    textButton(videoActions, "유튜브 삽입", () => {
      let url;
      try {
        url = new URL(videoUrl.value);
        if (!["https:", "http:"].includes(url.protocol) || !["www.youtube.com", "youtube.com", "youtu.be", "m.youtube.com"].includes(url.hostname)) throw Error();
      } catch {
        status.textContent = "유튜브 동영상 주소를 입력해 주세요.";
        return;
      }
      if (!focused().setYoutubeVideo({ src: url.href }).run()) status.textContent = "유튜브 동영상 주소를 확인해 주세요.";
      else closePops();
    }, null, "tomo-editor-primary");
    const symbolPop = popover(gInsert, "기호", "symbol", "기호 넣기");
    const symbols = el("div", "tomo-editor-symbols");
    symbolPop.body.append(symbols);
    for (const symbol of ["♡", "♥", "☆", "★", "✓", "※", "→", "←", "↑", "↓", "…", "—", "「", "」", "『", "』", "☀", "☁", "☾", "✨", "🌿", "📌", "💬", "🎨"]) {
      const b = textButton(symbols, symbol, () => focused().insertContent({ type: "text", text: symbol }).run());
      b.setAttribute("aria-label", "기호 " + symbol);
    }

    /* ---------- 8. 되돌리기 · 서식 지우기 · 더보기 · 넓게 ---------- */
    const gHistory = group("기록");
    button(gHistory, "실행 취소", "undo", () => focused().undo().run(), null, () => !item.editor.can().undo());
    button(gHistory, "다시 실행", "redo", () => focused().redo().run(), null, () => !item.editor.can().redo());
    button(gHistory, "서식 지우기", "clear", () => focused().unsetAllMarks().clearNodes().run());
    const morePop = popover(gHistory, "더보기", "more", "더보기");
    const moreRow = el("div", "tomo-editor-choices");
    morePop.body.append(moreRow);
    button(moreRow, "위 첨자", "superscript", () => focused().toggleSuperscript().run(), () => item.editor.isActive("superscript"));
    button(moreRow, "아래 첨자", "subscript", () => focused().toggleSubscript().run(), () => item.editor.isActive("subscript"));
    button(moreRow, "코드 블록", "codeblock", () => focused().toggleCodeBlock().run(), () => item.editor.isActive("codeBlock"));
    const spacing = labelInput(morePop.body, "문단 아래 여백(px)", "number");
    spacing.min = "0";
    spacing.max = "100";
    spacing.placeholder = "12";
    spacing.addEventListener("change", () => {
      const value = Number(spacing.value);
      if (spacing.value !== "" && value >= 0 && value <= 100) {
        const type = item.editor.isActive("heading") ? "heading" : "paragraph";
        focused().updateAttributes(type, { paragraphSpacing: value }).run();
      }
    });
    // Find and replace (in 더보기).
    const findBox = el("div", "tomo-editor-find");
    findBox.append(el("p", "tomo-editor-pop-subtitle", "찾기·바꾸기"));
    morePop.body.append(findBox);
    const findInput = labelInput(findBox, "찾을 내용");
    const replaceInput = labelInput(findBox, "바꿀 내용");
    const findStatus = el("span", "tomo-editor-help");
    findStatus.setAttribute("role", "status");
    let matchIndex = -1;
    const matches = () => {
      const query = findInput.value,
        found = [];
      if (!query) return found;
      item.editor.state.doc.descendants((node, pos) => {
        if (!node.isTextblock) return;
        const text = node.textBetween(0, node.content.size, "\n", "￼");
        let at = 0;
        while ((at = text.indexOf(query, at)) !== -1) {
          found.push({ from: pos + 1 + at, to: pos + 1 + at + query.length });
          at += query.length;
        }
        return false;
      });
      return found;
    };
    const findNext = () => {
      const found = matches();
      if (!found.length) {
        findStatus.textContent = "일치하는 내용이 없습니다.";
        return null;
      }
      matchIndex = (matchIndex + 1) % found.length;
      focused().setTextSelection(found[matchIndex]).scrollIntoView().run();
      findStatus.textContent = matchIndex + 1 + " / " + found.length + "개";
      return found[matchIndex];
    };
    findInput.addEventListener("input", () => {
      matchIndex = -1;
    });
    const findActions = el("div", "tomo-editor-pop-actions");
    findBox.append(findActions, findStatus);
    textButton(findActions, "다음 찾기", findNext);
    textButton(findActions, "선택 바꾸기", () => {
      const { from, to } = item.editor.state.selection;
      if (!findInput.value || item.editor.state.doc.textBetween(from, to) !== findInput.value) {
        findNext();
        return;
      }
      item.editor.view.dispatch(item.editor.state.tr.insertText(replaceInput.value, from, to));
      findNext();
    });
    textButton(findActions, "모두 바꾸기", () => {
      const found = matches(),
        tr = item.editor.state.tr;
      found.reverse().forEach((match) => tr.insertText(replaceInput.value, match.from, match.to));
      item.editor.view.dispatch(tr);
      findStatus.textContent = found.length + "개를 바꿨습니다.";
      matchIndex = -1;
    });
    // 찾기·바꾸기 keeps its own name so it can be opened directly (and by tests/shortcuts).
    const findOpen = button(gHistory, "찾기·바꾸기", "search", () => {
      openPop(morePop, true);
      findInput.focus();
    });
    findOpen.classList.add("tomo-editor-find-open");
    const wide = button(top, "화면 넓게 보기", "full", () => wrapper.classList.toggle("is-expanded"), () => wrapper.classList.contains("is-expanded"));
    wide.classList.add("tomo-editor-wide");

    /* ---------- HTML mode ---------- */
    const sourceBox = el("div", "tomo-editor-sourcebox");
    sourceBox.hidden = true;
    sourceBox.append(el("p", "tomo-editor-help", "HTML을 적용하면 지원하는 문단·서식·표·이미지 구조로 정리됩니다. 스크립트나 임의 위젯은 남지 않을 수 있습니다."));
    const source = el("textarea", "tomo-editor-source");
    source.setAttribute("aria-label", "본문 HTML");
    sourceBox.append(source);
    const sourceActions = el("div", "tomo-editor-pop-actions");
    sourceBox.append(sourceActions);
    canvas.after(sourceBox);
    item.sourceDirty = false;
    const setMode = (html) => {
      sourceBox.hidden = !html;
      canvas.hidden = html;
      toolbar.classList.toggle("is-disabled", html);
      toolbar.querySelectorAll("button, select, input").forEach((c) => {
        c.disabled = html;
      });
      visualTab.setAttribute("aria-selected", String(!html));
      sourceTab.setAttribute("aria-selected", String(html));
      closePops();
      if (html) {
        if (!item.sourceDirty) source.value = item.editor.getHTML();
        source.focus();
      } else {
        refresh();
      }
    };
    source.addEventListener("input", () => {
      item.sourceDirty = true;
      status.textContent = "HTML 변경사항을 적용한 뒤 저장해 주세요.";
    });
    const applySource = () => {
      item.editor.commands.setContent(source.value);
      item.sourceDirty = false;
      status.textContent = "HTML을 적용했습니다.";
      sync();
    };
    textButton(sourceActions, "HTML 적용", () => {
      applySource();
      setMode(false);
    }, null, "tomo-editor-primary");
    textButton(sourceActions, "HTML 변경 취소", () => {
      source.value = item.editor.getHTML();
      item.sourceDirty = false;
      status.textContent = "HTML 변경을 취소했습니다.";
    });
    visualTab.addEventListener("click", () => {
      if (item.sourceDirty) applySource(); // 편집으로 돌아오면 고친 HTML을 그대로 적용
      setMode(false);
    });
    sourceTab.addEventListener("click", () => setMode(true));

    /* ---------- Contextual bar: image · table · image group ---------- */
    const context = el("div", "tomo-editor-context");
    context.setAttribute("role", "toolbar");
    pops.after(context);
    const ctx = (name, title) => {
      const bar = el("div", "tomo-editor-ctx");
      bar.dataset.kind = name;
      bar.setAttribute("aria-label", title);
      bar.append(el("span", "tomo-editor-ctx-title", title));
      bar.hidden = true;
      context.append(bar);
      return bar;
    };
    const imageBar = ctx("image", "이미지");
    const width = labelInput(imageBar, "이미지 너비(px)", "number");
    width.min = "24";
    width.max = "10000";
    const height = labelInput(imageBar, "이미지 높이(px)", "number");
    height.min = "24";
    height.max = "10000";
    const alt = labelInput(imageBar, "이미지 설명");
    textButton(imageBar, "이미지 정보 적용", () => {
      if (!item.editor.isActive("image")) {
        status.textContent = "먼저 본문 이미지를 클릭해 주세요.";
        return;
      }
      const w = Number(width.value),
        h = Number(height.value);
      if ((width.value && (!Number.isFinite(w) || w < 24 || w > 10000)) || (height.value && (!Number.isFinite(h) || h < 24 || h > 10000))) {
        status.textContent = "이미지 크기는 24~10000px로 입력해 주세요.";
        return;
      }
      focused().updateAttributes("image", { width: width.value ? w : null, height: height.value ? h : null, alt: alt.value }).run();
    }, null, "tomo-editor-primary");
    for (const [label, icon, align] of [
      ["이미지 왼쪽", "alignLeft", "left"],
      ["이미지 가운데", "alignCenter", "center"],
      ["이미지 오른쪽", "alignRight", "right"],
    ])
      button(imageBar, label, icon, () => focused().updateAttributes("image", { imageAlign: align }).run(), () => item.editor.getAttributes("image").imageAlign === align);
    button(imageBar, "원래 크기로", "refresh", () => focused().updateAttributes("image", { width: null, height: null }).run());

    const gridBar = ctx("grid", "이미지 묶음");
    GRID_COLS.forEach((n) =>
      textButton(gridBar, n + "열", () => {
        const at = gridAt();
        if (at) item.editor.view.dispatch(item.editor.state.tr.setNodeMarkup(at.pos, undefined, { ...at.node.attrs, cols: n }));
      }, null, "tomo-editor-grid-cols").dataset.cols = String(n),
    );
    textButton(gridBar, "묶음에 그림 추가", () => chooseFiles("grid-add"));
    textButton(gridBar, "묶음 풀기", () => {
      const at = gridAt();
      if (!at) return;
      const images = [];
      at.node.forEach((child) => images.push(child));
      item.editor.view.dispatch(item.editor.state.tr.replaceWith(at.pos, at.pos + at.node.nodeSize, images));
    });

    const tableBar = ctx("table", "표");
    for (const [label, icon, command] of [
      ["위에 행 추가", "rowAbove", "addRowBefore"],
      ["아래에 행 추가", "rowBelow", "addRowAfter"],
      ["행 삭제", "rowDelete", "deleteRow"],
      ["왼쪽 열 추가", "colLeft", "addColumnBefore"],
      ["오른쪽 열 추가", "colRight", "addColumnAfter"],
      ["열 삭제", "colDelete", "deleteColumn"],
      ["셀 병합", "merge", "mergeCells"],
      ["셀 나누기", "split", "splitCell"],
      ["제목 행 전환", "headerRow", "toggleHeaderRow"],
      ["제목 열 전환", "headerCol", "toggleHeaderColumn"],
    ])
      button(tableBar, label, icon, () => focused()[command]().run(), null, () => !item.editor.can()[command]());
    const cellColor = el("input");
    cellColor.type = "color";
    cellColor.value = "#fcf0f3";
    cellColor.setAttribute("aria-label", "셀 배경색");
    cellColor.title = "셀 배경색";
    cellColor.addEventListener("input", () => focused().setCellAttribute("backgroundColor", cellColor.value).run());
    tableBar.append(cellColor);
    button(tableBar, "셀 배경색 해제", "clear", () => focused().setCellAttribute("backgroundColor", null).run());
    button(tableBar, "표 삭제", "trash", () => focused().deleteTable().run()).classList.add("tomo-editor-danger");

    const updateContext = () => {
      const grid = gridAt(),
        inTable = item.editor.isActive("table"),
        isImage = !grid && item.editor.isActive("image");
      imageBar.hidden = !isImage;
      gridBar.hidden = !grid;
      tableBar.hidden = !inTable || !!grid;
      if (grid) gridBar.querySelectorAll("[data-cols]").forEach((b) => b.setAttribute("aria-pressed", String(Number(b.dataset.cols) === grid.node.attrs.cols)));
      context.hidden = imageBar.hidden && gridBar.hidden && tableBar.hidden;
    };
    // Close popovers when clicking into the text; keep them while working inside.
    canvas.addEventListener("mousedown", () => closePops());
    item.editor.on("selectionUpdate", updateContext);
    item.editor.on("transaction", updateContext);
    updateContext();
    // Start in 편집 mode (refresh() runs once it is defined below).
    sourceBox.hidden = true;
    visualTab.setAttribute("aria-selected", "true");
    sourceTab.setAttribute("aria-selected", "false");

    let composing = false;
    canvas.addEventListener("compositionstart", () => {
      composing = true;
    });
    canvas.addEventListener("compositionend", () => {
      composing = false;
    });
    item.isComposing = () => composing;
    item.editor.on("selectionUpdate", () => {
      if (item.editor.isActive("image")) {
        const attrs = item.editor.getAttributes("image");
        width.value = attrs.width || "";
        height.value = attrs.height || "";
        alt.value = attrs.alt || "";
      }
    });

    const setSelectValue = (name, value) => {
      const control = selects.get(name);
      if (!control || document.activeElement === control) return;
      const normalized = String(value || "");
      const clean = (v) => v.replace(/["'\s]/g, "").toLowerCase();
      const option = [...control.options]
        .filter((option) => !option.hasAttribute("data-current"))
        .find((option) => clean(option.value) === clean(normalized));
      control.querySelector("option[data-current]")?.remove();
      if (option) control.value = option.value;
      else if (normalized) {
        const current = new Option(normalized, normalized);
        current.dataset.current = "1";
        control.add(current);
        control.value = normalized;
      } else control.value = "";
    };
    const refresh = () => {
      buttons.forEach(({ el, active, disabled }) => {
        if (active) el.setAttribute("aria-pressed", String(active()));
        if (disabled) el.disabled = disabled();
      });
      setSelectValue(
        "문단 스타일",
        String(item.editor.getAttributes("heading").level || "paragraph"),
      );
      const textStyle = item.editor.getAttributes("textStyle");
      setSelectValue("글꼴", textStyle.fontFamily || "");
      setSelectValue("글자 크기", textStyle.fontSize || "");
      setSelectValue("줄 간격", textStyle.lineHeight ? Number(textStyle.lineHeight).toFixed(1) : "");
      setSelectValue("자간", Number.isFinite(textStyle.letterSpacing) ? String(textStyle.letterSpacing) : "");
    };
    window.tomoEditorSync(field.id);
    item.editor.on("transaction", refresh);
    refresh();
    count.textContent = countText();
    field.form?.addEventListener(
      "submit",
      (event) => {
        if (item.sourceDirty || item.uploading || item.isComposing()) {
          event.preventDefault();
          status.textContent = item.sourceDirty
            ? "HTML 변경사항을 적용한 뒤 저장해 주세요."
            : "입력 또는 이미지 업로드가 끝난 뒤 저장해 주세요.";
        }
        window.tomoEditorSync(field.id);
      },
      true,
    );
  });
}
if (document.readyState === "loading")
  document.addEventListener("DOMContentLoaded", init);
else init();
