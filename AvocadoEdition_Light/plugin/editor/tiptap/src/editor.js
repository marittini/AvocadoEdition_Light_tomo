import { Editor, Extension } from "@tiptap/core";
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

const editors = new Map();
window.tomoEditors = editors;
window.tomoEditorSync = (id) => {
  const item = editors.get(id);
  if (item) item.field.value = item.editor.getHTML();
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
  if (item.editor.isEmpty) {
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
};
const svg = (key) =>
  '<svg viewBox="0 0 24 26" width="18" height="18" aria-hidden="true"><path d="' +
  icons[key] +
  '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
function init() {
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
    footer.textContent = "서식 있는 본문";
    const count = document.createElement("span");
    footer.append(count);
    field.before(toolbar, canvas);
    field.after(footer);
    field.hidden = true;
    const item = { field, wrapper, status, uploading: 0, editor: null };
    editors.set(field.id, item);
    const sync = () => {
      field.value = item.editor.getHTML();
      count.textContent =
        new Intl.NumberFormat("ko-KR").format(item.editor.getText().length) +
        "자";
      field.dispatchEvent(new Event("input", { bubbles: true }));
    };
    const upload = async (files) => {
      let position = item.editor.state.selection.from;
      for (const file of files) {
        item.uploading++;
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
          const mapped = Math.min(position, item.editor.state.doc.content.size);
          item.editor
            .chain()
            .focus()
            .insertContentAt(mapped, {
              type: "image",
              attrs: { src: result.url, alt: file.name },
            })
            .run();
          position = item.editor.state.selection.from;
          status.textContent = result.notice || "이미지를 첨부했습니다.";
        } catch (error) {
          status.textContent = error.message;
        } finally {
          item.uploading--;
        }
      }
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
        TextAlign.configure({ types: ["heading", "paragraph"] }),
        TableKit.configure({ table: { resizable: true } }),
        Formatting,
        Superscript,
        Subscript,
        TaskList,
        TaskItem.configure({ nested: true }),
        Placeholder.configure({
          placeholder: "내용을 입력하거나 이미지를 붙여넣으세요.",
        }),
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
    const buttons = [];
    const button = (name, icon, run, active) => {
      const el = document.createElement("button");
      el.type = "button";
      el.title = name;
      el.setAttribute("aria-label", name);
      el.innerHTML = svg(icon);
      el.addEventListener("mousedown", (event) => event.preventDefault());
      el.addEventListener("click", () => {
        run();
        refresh();
      });
      toolbar.append(el);
      buttons.push({ el, active });
      return el;
    };
    const select = (name, options, change) => {
      const el = document.createElement("select");
      el.setAttribute("aria-label", name);
      options.forEach(([label, value]) => el.add(new Option(label, value)));
      el.addEventListener("change", () => {
        change(el.value);
        refresh();
      });
      toolbar.append(el);
      return el;
    };
    const style = select(
      "문단 스타일",
      [
        ["본문", "paragraph"],
        ["큰 제목", "2"],
        ["중간 제목", "3"],
        ["작은 제목", "4"],
      ],
      (value) =>
        value === "paragraph"
          ? item.editor.chain().focus().setParagraph().run()
          : item.editor
              .chain()
              .focus()
              .toggleHeading({ level: Number(value) })
              .run(),
    );
    const fontOptions = [
      ["기본 글꼴", ""],
      ["고딕", "sans-serif"],
      ["명조", "serif"],
      ["고정폭", "monospace"],
      ...JSON.parse(wrapper.dataset.fonts || "[]").map((font) => [
        font.label,
        font.family,
      ]),
    ];
    select("글꼴", fontOptions, (value) =>
      value
        ? item.editor.chain().focus().setFontFamily(value).run()
        : item.editor.chain().focus().unsetFontFamily().run(),
    );
    select(
      "글자 크기",
      [
        ["기본 크기", ""],
        ...["12", "14", "16", "18", "24", "32"].map((size) => [
          size + "px",
          size + "px",
        ]),
      ],
      (value) =>
        value
          ? item.editor.chain().focus().setFontSize(value).run()
          : item.editor.chain().focus().unsetFontSize().run(),
    );
    button("실행 취소", "undo", () => item.editor.chain().focus().undo().run());
    button("다시 실행", "redo", () => item.editor.chain().focus().redo().run());
    for (const [name, icon, command, type] of [
      ["굵게", "bold", "toggleBold", "bold"],
      ["기울임", "italic", "toggleItalic", "italic"],
      ["밑줄", "underline", "toggleUnderline", "underline"],
      ["취소선", "strike", "toggleStrike", "strike"],
      ["글머리 목록", "list", "toggleBulletList", "bulletList"],
      ["번호 목록", "ordered", "toggleOrderedList", "orderedList"],
      ["인용", "quote", "toggleBlockquote", "blockquote"],
    ])
      button(
        name,
        icon,
        () => item.editor.chain().focus()[command]().run(),
        () => item.editor.isActive(type),
      );
    const color = document.createElement("input");
    color.type = "color";
    color.value = "#49393c";
    color.title = "글자 색상";
    color.setAttribute("aria-label", "글자 색상");
    color.addEventListener("input", () =>
      item.editor.chain().focus().setColor(color.value).run(),
    );
    toolbar.append(color);
    select(
      "정렬",
      [
        ["왼쪽 정렬", "left"],
        ["가운데 정렬", "center"],
        ["오른쪽 정렬", "right"],
      ],
      (value) => item.editor.chain().focus().setTextAlign(value).run(),
    );
    const linkPanel = document.createElement("div");
    linkPanel.className = "tomo-editor-link";
    linkPanel.hidden = true;
    const linkInput = document.createElement("input");
    linkInput.type = "url";
    linkInput.placeholder = "https://example.com";
    linkInput.setAttribute("aria-label", "링크 주소");
    linkPanel.append(linkInput);
    const apply = document.createElement("button");
    apply.type = "button";
    apply.textContent = "적용";
    const unlink = document.createElement("button");
    unlink.type = "button";
    unlink.textContent = "링크 해제";
    const cancel = document.createElement("button");
    cancel.type = "button";
    cancel.textContent = "닫기";
    linkPanel.append(apply, unlink, cancel);
    toolbar.after(linkPanel);
    let linkSelection = null;
    button(
      "링크",
      "link",
      () => {
        linkSelection = item.editor.state.selection;
        linkInput.value = item.editor.getAttributes("link").href || "";
        linkPanel.hidden = false;
        linkInput.focus();
      },
      () => item.editor.isActive("link"),
    );
    apply.addEventListener("click", () => {
      try {
        const url = new URL(linkInput.value, location.href);
        if (!["https:", "http:", "mailto:"].includes(url.protocol))
          throw Error();
        item.editor
          .chain()
          .focus()
          .setTextSelection({ from: linkSelection.from, to: linkSelection.to })
          .extendMarkRange("link")
          .setLink({ href: url.href })
          .run();
        linkPanel.hidden = true;
      } catch {
        status.textContent = "https:// 또는 http:// 주소를 입력해 주세요.";
      }
    });
    unlink.addEventListener("click", () => {
      item.editor
        .chain()
        .focus()
        .setTextSelection({ from: linkSelection.from, to: linkSelection.to })
        .unsetLink()
        .run();
      linkPanel.hidden = true;
    });
    cancel.addEventListener("click", () => {
      linkPanel.hidden = true;
      item.editor.commands.focus();
    });
    const file = document.createElement("input");
    file.type = "file";
    file.accept = "image/png,image/jpeg,image/gif,image/webp";
    file.multiple = true;
    file.hidden = true;
    wrapper.append(file);
    file.addEventListener("change", () => {
      upload([...file.files]);
      file.value = "";
    });
    button("이미지 첨부", "image", () => file.click());
    button("표 삽입", "table", () =>
      item.editor
        .chain()
        .focus()
        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
        .run(),
    );
    button("서식 지우기", "clear", () =>
      item.editor.chain().focus().unsetAllMarks().clearNodes().run(),
    );
    button(
      "화면 넓게 보기",
      "full",
      () => {
        wrapper.classList.toggle("is-expanded");
      },
      () => wrapper.classList.contains("is-expanded"),
    );
    // Keep presets, frequent actions and contextual tools on separate, consistent rows.
    const presets = document.createElement("div");
    presets.className = "tomo-editor-presets";
    const actions = document.createElement("div");
    actions.className = "tomo-editor-actions";
    const moveControl = (parent, label) => {
      const el = [...toolbar.querySelectorAll("[aria-label]")].find(
        (el) => el.getAttribute("aria-label") === label,
      );
      if (el) parent.append(el);
    };
    ["문단 스타일", "글꼴", "글자 크기"].forEach((label) =>
      moveControl(presets, label),
    );
    for (const [name, labels] of [
      ["실행 기록", ["실행 취소", "다시 실행"]],
      ["글자 서식", ["굵게", "기울임", "밑줄", "취소선", "글자 색상"]],
      ["문단 서식", ["글머리 목록", "번호 목록", "인용", "정렬"]],
      ["삽입", ["링크", "이미지 첨부", "표 삽입"]],
      ["화면 도구", ["서식 지우기", "화면 넓게 보기"]],
    ]) {
      const group = document.createElement("div");
      group.className = "tomo-editor-action-group";
      group.setAttribute("role", "group");
      group.setAttribute("aria-label", name);
      labels.forEach((label) => moveControl(group, label));
      actions.append(group);
    }
    toolbar.replaceChildren(presets, actions);

    // Additional tools remain discoverable without crowding the main formatting row.
    const tools = document.createElement("div");
    tools.className = "tomo-editor-tools";
    toolbar.after(tools);
    const panels = [];
    const panel = (label) => {
      const toggle = document.createElement("button");
      toggle.type = "button";
      toggle.textContent = label;
      toggle.setAttribute("aria-expanded", "false");
      tools.append(toggle);
      const body = document.createElement("div");
      body.className = "tomo-editor-panel";
      body.dataset.kind = {
        "추가 서식": "format",
        "표 편집": "table",
        "이미지 편집": "image",
        "찾기·바꾸기": "find",
        HTML: "source",
        "동영상·기호": "media",
      }[label];
      body.hidden = true;
      const id = field.id + "-panel-" + panels.length;
      body.id = id;
      toggle.setAttribute("aria-controls", id);
      tools.after(body);
      panels.push({ body, toggle });
      toggle.addEventListener("click", () => {
        const open = body.hidden;
        panels.forEach((p) => {
          p.body.hidden = true;
          p.toggle.setAttribute("aria-expanded", "false");
        });
        body.hidden = !open;
        toggle.setAttribute("aria-expanded", String(open));
      });
      return body;
    };
    const toolButton = (parent, label, action, disabled) => {
      const el = document.createElement("button");
      el.type = "button";
      el.textContent = label;
      el.addEventListener("mousedown", (event) => event.preventDefault());
      el.addEventListener("click", () => {
        action();
        refresh();
      });
      parent.append(el);
      buttons.push({ el, disabled });
      return el;
    };
    const labelInput = (parent, label, type = "text") => {
      const wrap = document.createElement("label");
      const caption = document.createElement("span");
      caption.textContent = label;
      wrap.append(caption);
      const input = document.createElement("input");
      input.type = type;
      input.setAttribute("aria-label", label);
      wrap.append(input);
      parent.append(wrap);
      return input;
    };
    const extra = panel("추가 서식");
    toolButton(extra, "위 첨자", () =>
      item.editor.chain().focus().toggleSuperscript().run(),
    );
    toolButton(extra, "아래 첨자", () =>
      item.editor.chain().focus().toggleSubscript().run(),
    );
    toolButton(extra, "체크리스트", () =>
      item.editor.chain().focus().toggleTaskList().run(),
    );
    toolButton(extra, "코드 블록", () =>
      item.editor.chain().focus().toggleCodeBlock().run(),
    );
    toolButton(extra, "구분선", () =>
      item.editor.chain().focus().setHorizontalRule().run(),
    );
    toolButton(extra, "접기·펼치기", () =>
      item.editor.chain().focus().setDetails().run(),
    );
    const highlight = labelInput(extra, "글자 배경색", "color");
    highlight.value = "#fff0ad";
    highlight.addEventListener("input", () =>
      item.editor.chain().focus().setBackgroundColor(highlight.value).run(),
    );
    toolButton(extra, "배경색 해제", () =>
      item.editor.chain().focus().unsetBackgroundColor().run(),
    );
    const line = labelInput(extra, "줄 간격", "number");
    line.min = "1";
    line.max = "3";
    line.step = "0.1";
    line.value = "1.8";
    line.addEventListener("change", () => {
      const value = Number(line.value);
      if (value >= 1 && value <= 3)
        item.editor.chain().focus().setLineHeight(String(value)).run();
    });
    const spacing = labelInput(extra, "문단 아래 여백(px)", "number");
    spacing.min = "0";
    spacing.max = "100";
    spacing.value = "12";
    spacing.addEventListener("change", () => {
      const value = Number(spacing.value);
      if (value >= 0 && value <= 100) {
        const type = item.editor.isActive("heading") ? "heading" : "paragraph";
        item.editor
          .chain()
          .focus()
          .updateAttributes(type, { paragraphSpacing: value })
          .run();
      }
    });
    const tablePanel = panel("표 편집");
    const tableHelp = document.createElement("p");
    tableHelp.textContent =
      "표 안의 셀을 클릭한 뒤 편집하세요. 여러 셀을 선택하면 병합할 수 있습니다.";
    tablePanel.append(tableHelp);
    for (const [label, command] of [
      ["위에 행 추가", "addRowBefore"],
      ["아래에 행 추가", "addRowAfter"],
      ["행 삭제", "deleteRow"],
      ["왼쪽 열 추가", "addColumnBefore"],
      ["오른쪽 열 추가", "addColumnAfter"],
      ["열 삭제", "deleteColumn"],
      ["셀 병합", "mergeCells"],
      ["셀 나누기", "splitCell"],
      ["제목 행 전환", "toggleHeaderRow"],
      ["제목 열 전환", "toggleHeaderColumn"],
      ["표 삭제", "deleteTable"],
    ])
      toolButton(
        tablePanel,
        label,
        () => item.editor.chain().focus()[command]().run(),
        () => !item.editor.can()[command](),
      );
    const cellColor = labelInput(tablePanel, "셀 배경색", "color");
    cellColor.value = "#fcf0f3";
    cellColor.addEventListener("input", () =>
      item.editor
        .chain()
        .focus()
        .setCellAttribute("backgroundColor", cellColor.value)
        .run(),
    );
    toolButton(tablePanel, "셀 배경색 해제", () =>
      item.editor
        .chain()
        .focus()
        .setCellAttribute("backgroundColor", null)
        .run(),
    );
    const imagePanel = panel("이미지 편집");
    const imageHelp = document.createElement("p");
    imageHelp.textContent =
      "본문에서 이미지를 클릭하세요. 모서리를 드래그하거나 크기를 입력할 수 있습니다.";
    imagePanel.append(imageHelp);
    const width = labelInput(imagePanel, "이미지 너비(px)", "number");
    width.min = "24";
    width.max = "10000";
    const height = labelInput(imagePanel, "이미지 높이(px)", "number");
    height.min = "24";
    height.max = "10000";
    const alt = labelInput(imagePanel, "이미지 설명");
    toolButton(imagePanel, "이미지 정보 적용", () => {
      if (!item.editor.isActive("image")) {
        status.textContent = "먼저 본문 이미지를 클릭해 주세요.";
        return;
      }
      const w = Number(width.value),
        h = Number(height.value);
      if (
        (width.value && (!Number.isFinite(w) || w < 24 || w > 10000)) ||
        (height.value && (!Number.isFinite(h) || h < 24 || h > 10000))
      ) {
        status.textContent = "이미지 크기는 24~10000px로 입력해 주세요.";
        return;
      }
      item.editor
        .chain()
        .focus()
        .updateAttributes("image", {
          width: width.value ? w : null,
          height: height.value ? h : null,
          alt: alt.value,
        })
        .run();
    });
    for (const [label, align] of [
      ["이미지 왼쪽", "left"],
      ["이미지 가운데", "center"],
      ["이미지 오른쪽", "right"],
    ])
      toolButton(
        imagePanel,
        label,
        () =>
          item.editor
            .chain()
            .focus()
            .updateAttributes("image", { imageAlign: align })
            .run(),
        () => !item.editor.isActive("image"),
      );
    toolButton(
      imagePanel,
      "원래 크기로",
      () =>
        item.editor
          .chain()
          .focus()
          .updateAttributes("image", { width: null, height: null })
          .run(),
      () => !item.editor.isActive("image"),
    );
    const findPanel = panel("찾기·바꾸기");
    const findInput = labelInput(findPanel, "찾을 내용");
    const replaceInput = labelInput(findPanel, "바꿀 내용");
    const findStatus = document.createElement("span");
    findStatus.setAttribute("role", "status");
    findPanel.append(findStatus);
    let matchIndex = -1;
    const matches = () => {
      const query = findInput.value,
        found = [];
      if (!query) return found;
      item.editor.state.doc.descendants((node, pos) => {
        if (!node.isTextblock) return;
        const text = node.textBetween(0, node.content.size, "\n", "\ufffc");
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
      item.editor
        .chain()
        .focus()
        .setTextSelection(found[matchIndex])
        .scrollIntoView()
        .run();
      findStatus.textContent = matchIndex + 1 + " / " + found.length + "개";
      return found[matchIndex];
    };
    findInput.addEventListener("input", () => {
      matchIndex = -1;
    });
    toolButton(findPanel, "다음 찾기", findNext);
    toolButton(findPanel, "선택 바꾸기", () => {
      const { from, to } = item.editor.state.selection;
      if (
        !findInput.value ||
        item.editor.state.doc.textBetween(from, to) !== findInput.value
      ) {
        findNext();
        return;
      }
      item.editor.view.dispatch(
        item.editor.state.tr.insertText(replaceInput.value, from, to),
      );
      findNext();
    });
    toolButton(findPanel, "모두 바꾸기", () => {
      const found = matches(),
        tr = item.editor.state.tr;
      found
        .reverse()
        .forEach((match) =>
          tr.insertText(replaceInput.value, match.from, match.to),
        );
      item.editor.view.dispatch(tr);
      findStatus.textContent = found.length + "개를 바꿨습니다.";
      matchIndex = -1;
    });
    const sourcePanel = panel("HTML");
    const sourceHelp = document.createElement("p");
    sourceHelp.textContent =
      "HTML을 적용하면 지원하는 문단·서식·표·이미지 구조로 정리됩니다. 스크립트나 임의 위젯은 유지되지 않을 수 있습니다.";
    sourcePanel.append(sourceHelp);
    const source = document.createElement("textarea");
    source.className = "tomo-editor-source";
    source.setAttribute("aria-label", "본문 HTML");
    sourcePanel.append(source);
    item.sourceDirty = false;
    panels
      .find((p) => p.body === sourcePanel)
      .toggle.addEventListener("click", () => {
        if (!sourcePanel.hidden && !item.sourceDirty)
          source.value = item.editor.getHTML();
      });
    source.addEventListener("input", () => {
      item.sourceDirty = true;
      status.textContent = "HTML 변경사항을 적용한 뒤 저장해 주세요.";
    });
    toolButton(sourcePanel, "HTML 적용", () => {
      item.editor.commands.setContent(source.value);
      item.sourceDirty = false;
      status.textContent = "HTML을 적용했습니다.";
      sync();
    });
    toolButton(sourcePanel, "HTML 변경 취소", () => {
      source.value = item.editor.getHTML();
      item.sourceDirty = false;
      status.textContent = "HTML 변경을 취소했습니다.";
    });
    const videoPanel = panel("동영상·기호");
    const videoUrl = labelInput(videoPanel, "유튜브 주소");
    videoUrl.placeholder = "https://www.youtube.com/watch?v=…";
    toolButton(videoPanel, "유튜브 삽입", () => {
      let url;
      try {
        url = new URL(videoUrl.value);
        if (
          !["https:", "http:"].includes(url.protocol) ||
          ![
            "www.youtube.com",
            "youtube.com",
            "youtu.be",
            "m.youtube.com",
          ].includes(url.hostname)
        )
          throw Error();
      } catch {
        status.textContent = "유튜브 동영상 주소를 입력해 주세요.";
        return;
      }
      if (!item.editor.chain().focus().setYoutubeVideo({ src: url.href }).run())
        status.textContent = "유튜브 동영상 주소를 확인해 주세요.";
    });
    const symbols = document.createElement("div");
    symbols.className = "tomo-editor-symbols";
    videoPanel.append(symbols);
    for (const symbol of [
      "♡",
      "♥",
      "☆",
      "★",
      "✓",
      "※",
      "→",
      "←",
      "↑",
      "↓",
      "…",
      "—",
      "☀",
      "☁",
      "☾",
      "✨",
      "🌿",
      "📌",
      "💬",
      "🎨",
    ])
      toolButton(symbols, symbol, () =>
        item.editor
          .chain()
          .focus()
          .insertContent({ type: "text", text: symbol })
          .run(),
      );
    const groupTools = (parent, title, labels) => {
      const group = document.createElement("fieldset");
      group.className = "tomo-editor-tool-group";
      const legend = document.createElement("legend");
      legend.textContent = title;
      group.append(legend);
      labels.forEach((label) => {
        const control = [...parent.children].find((el) =>
          el.matches("button")
            ? el.textContent === label
            : el.matches("label") &&
              el.querySelector("[aria-label]")?.getAttribute("aria-label") ===
                label,
        );
        if (control) group.append(control);
      });
      parent.append(group);
      return group;
    };
    groupTools(extra, "문자·블록", [
      "위 첨자",
      "아래 첨자",
      "체크리스트",
      "코드 블록",
      "구분선",
      "접기·펼치기",
    ]);
    groupTools(extra, "간격·색상", [
      "글자 배경색",
      "배경색 해제",
      "줄 간격",
      "문단 아래 여백(px)",
    ]);
    groupTools(tablePanel, "행·열", [
      "위에 행 추가",
      "아래에 행 추가",
      "행 삭제",
      "왼쪽 열 추가",
      "오른쪽 열 추가",
      "열 삭제",
    ]);
    groupTools(tablePanel, "셀·제목", [
      "셀 병합",
      "셀 나누기",
      "제목 행 전환",
      "제목 열 전환",
    ]);
    groupTools(tablePanel, "셀 색상", ["셀 배경색", "셀 배경색 해제"]);
    tablePanel.querySelector("button")?.classList.add("tomo-editor-danger");
    groupTools(imagePanel, "크기·설명", [
      "이미지 너비(px)",
      "이미지 높이(px)",
      "이미지 설명",
      "이미지 정보 적용",
    ]);
    groupTools(imagePanel, "배치", [
      "이미지 왼쪽",
      "이미지 가운데",
      "이미지 오른쪽",
      "원래 크기로",
    ]);
    groupTools(findPanel, "내용", ["찾을 내용", "바꿀 내용"]);
    groupTools(findPanel, "찾기·바꾸기", [
      "다음 찾기",
      "선택 바꾸기",
      "모두 바꾸기",
    ]);

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

    const refresh = () => {
      buttons.forEach(({ el, active, disabled }) => {
        if (active) el.setAttribute("aria-pressed", String(active()));
        if (disabled) el.disabled = disabled();
      });
      style.value = String(
        item.editor.getAttributes("heading").level || "paragraph",
      );
    };
    item.editor.on("transaction", refresh);
    refresh();
    count.textContent = item.editor.getText().length + "자";
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
