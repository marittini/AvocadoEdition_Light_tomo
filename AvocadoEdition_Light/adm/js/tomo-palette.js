(function () {
  "use strict";
  const panel = document.getElementById("tomo-palette");
  if (!panel) return;
  const form = panel.closest("form"),
    status = document.getElementById("palette-status");
  const presets = {
    rose: {
      background: "#f8f5f6",
      surface: "#ffffff",
      text: "#49393c",
      accent: "#96565e",
      border: "#e6d4d8",
      muted: "#75646b",
    },
    sage: {
      background: "#f3f6f3",
      surface: "#ffffff",
      text: "#35443b",
      accent: "#446b52",
      border: "#d4dfd5",
      muted: "#627265",
    },
    lavender: {
      background: "#f6f4f8",
      surface: "#ffffff",
      text: "#423b4a",
      accent: "#705484",
      border: "#ded5e6",
      muted: "#74647e",
    },
    mono: {
      background: "#f5f5f4",
      surface: "#ffffff",
      text: "#363635",
      accent: "#595958",
      border: "#dcdcd9",
      muted: "#6c6c69",
    },
  };
  const channels = (color) =>
    color.match(/[a-f0-9]{2}/gi).map((value) => parseInt(value, 16));
  const mix = (a, b, weight) =>
    "#" +
    channels(a)
      .map((value, i) =>
        Math.round(value * (1 - weight) + channels(b)[i] * weight)
          .toString(16)
          .padStart(2, "0"),
      )
      .join("");
  const luminance = (color) =>
    channels(color)
      .map((value) => value / 255)
      .map((value) =>
        value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4,
      )
      .reduce((sum, value, i) => sum + value * [0.2126, 0.7152, 0.0722][i], 0);
  const contrast = (a, b) => {
    const x = luminance(a),
      y = luminance(b);
    return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
  };
  const palette = () =>
    Object.fromEntries(
      [...panel.querySelectorAll("[data-palette-color]")].map((input) => [
        input.dataset.paletteColor,
        input.value,
      ]),
    );
  const showContrast = () => {
    const p = palette();
    document.getElementById("palette-contrast").textContent =
      "불투명 색상 기준 · 본문 대비 " +
      contrast(p.text, p.surface).toFixed(1) +
      ":1 · 강조색 대비 " +
      contrast(p.accent, p.surface).toFixed(1) +
      ":1" +
      (contrast(p.text, p.surface) < 4.5 || contrast(p.accent, p.surface) < 4.5
        ? " · 글자 색상을 더 진하게 조정하면 읽기 편합니다."
        : "");
  };
  const fill = (p, preset = "") => {
    panel
      .querySelectorAll("[data-palette-preset]")
      .forEach((button) =>
        button.setAttribute(
          "aria-pressed",
          String(button.dataset.palettePreset === preset),
        ),
      );
    Object.entries(p).forEach(([key, value]) => {
      panel.querySelector("[data-palette-color=" + key + "]").value = value;
      panel.querySelector("[data-palette-hex=" + key + "]").value = value;
    });
    showContrast();
  };
  panel.querySelectorAll("[data-palette-color]").forEach((input) =>
    input.addEventListener("input", () => {
      panel.querySelector(
        "[data-palette-hex=" + input.dataset.paletteColor + "]",
      ).value = input.value;
      showContrast();
    }),
  );
  panel.querySelectorAll("[data-palette-hex]").forEach((input) =>
    input.addEventListener("change", () => {
      if (!/^#[0-9a-f]{6}$/i.test(input.value)) {
        status.textContent =
          "색상코드는 #과 여섯 자리 숫자/영문으로 입력해 주세요.";
        return;
      }
      panel.querySelector(
        "[data-palette-color=" + input.dataset.paletteHex + "]",
      ).value = input.value;
      showContrast();
    }),
  );
  panel.querySelectorAll("[data-palette-preset]").forEach((button) =>
    button.addEventListener("click", () => {
      fill(presets[button.dataset.palettePreset], button.dataset.palettePreset);
      status.textContent =
        button.textContent +
        " 팔레트를 불러왔습니다. 디자인에 일괄 적용을 누르세요.";
    }),
  );
  document.getElementById("palette-recommend").addEventListener("click", () => {
    let accent = palette().accent;
    const background = mix(accent, "#ffffff", 0.96);
    for (let i = 0; i < 15 && contrast(accent, "#ffffff") < 4.5; i++)
      accent = mix(accent, "#000000", 0.08);
    fill({
      background,
      surface: "#ffffff",
      text: mix(accent, "#202020", 0.75),
      accent,
      border: mix(accent, "#ffffff", 0.78),
      muted: mix(accent, "#454545", 0.55),
    });
    status.textContent =
      "강조색에 어울리는 밝은 팔레트를 추천했습니다. 디자인에 일괄 적용을 누르세요.";
  });
  let changes = [];
  const set = (input, value) => {
    if (!input || input.value === value) return;
    changes.push({ input, before: input.value, after: value });
    input.value = value;
    if (input.classList.contains("colorpicker") && window.jQuery?.fn.minicolors)
      window.jQuery(input).minicolors("value", value);
    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));
  };
  document.getElementById("palette-apply").addEventListener("click", () => {
    const p = palette();
    changes = [];
    const rows = new Map(
      [...form.querySelectorAll('input[name^="cs_name["]')].map((input) => [
        input.value,
        input.name.match(/\[(\d+)\]/)[1],
      ]),
    );
    const color = (row, slot, value) => {
      const index = rows.get(row);
      if (index === undefined) return;
      const input = form.elements.namedItem(
        (slot === 0 ? "cs_value" : "cs_etc_" + slot) + "[" + index + "]",
      );
      if (input?.classList.contains("colorpicker")) set(input, value);
    };
    for (const row of ["background", "m_background", "intro_background"])
      color(row, 1, p.background);
    color("mmb_contain_bak", 1, p.background);
    color("default_font", 0, p.text);
    color("color_default", 0, p.text);
    color("color_point", 0, p.accent);
    color("color_bak", 0, p.surface);
    for (const row of [
      "board_notice",
      "board_table",
      "list_header",
      "list_body",
      "form_header",
      "form_body",
      "mmb_list",
      "mmb_list_item",
      "mmb_log",
      "mmb_reply",
      "mmb_reply_item",
      "box_style",
    ]) {
      color(row, 0, p.surface);
      color(row, 2, p.text);
      color(row, 4, p.border);
    }
    color("menu_tooltip", 0, p.surface);
    color("menu_tooltip", 2, p.text);
    color("intro", 2, p.text);
    color("input_bak", 0, p.surface);
    color("input_bak", 3, p.text);
    color("input_bak", 6, p.border);
    const onAccent =
        contrast("#ffffff", p.accent) >= contrast("#202020", p.accent)
          ? "#ffffff"
          : "#202020",
      hover = mix(p.accent, "#000000", 0.12);
    for (const row of ["btn_default", "btn_etc"]) {
      color(row, 0, p.surface);
      color(row, 2, p.text);
      color(row, 4, p.border);
      color(row, 6, p.accent);
      color(row, 8, onAccent);
      color(row, 10, p.accent);
    }
    for (const [slot, value] of [
      [0, p.accent],
      [2, onAccent],
      [4, p.accent],
      [6, hover],
      [8, onAccent],
      [10, hover],
    ])
      color("btn_point", slot, value);
    for (const row of ["mmb_name"]) color(row, 0, p.text);
    for (const row of ["mmb_owner_name", "mmb_link", "mmb_log_ank", "mmb_hash"])
      color(row, 0, p.accent);
    color("mmb_datetime", 0, p.muted);
    document.getElementById("palette-undo").disabled = !changes.length;
    status.textContent =
      changes.length +
      "개 색상 항목에 적용했습니다. 상단의 저장을 누르면 사이트에 반영됩니다.";
  });
  document.getElementById("palette-undo").addEventListener("click", () => {
    const previous = changes;
    changes = [];
    let count = 0;
    previous.forEach(({ input, before, after }) => {
      if (input.value === after) {
        set(input, before);
        count++;
      }
    });
    changes = [];
    document.getElementById("palette-undo").disabled = true;
    status.textContent =
      count +
      "개 항목을 되돌렸습니다. 적용 이후 직접 수정한 색상은 유지했습니다.";
  });
  const current = { ...presets.rose };
  for (const [role, row, slot] of [
    ["background", "background", 1],
    ["surface", "color_bak", 0],
    ["text", "default_font", 0],
    ["accent", "color_point", 0],
    ["border", "board_table", 4],
    ["muted", "mmb_datetime", 0],
  ]) {
    const name = [...form.querySelectorAll('input[name^="cs_name["]')].find(
      (input) => input.value === row,
    );
    if (!name) continue;
    const index = name.name.match(/\[(\d+)\]/)[1];
    let value =
      form.elements.namedItem(
        (slot === 0 ? "cs_value" : "cs_etc_" + slot) + "[" + index + "]",
      )?.value || "";
    if (/^#[0-9a-f]{3}$/i.test(value))
      value = "#" + [...value.slice(1)].map((c) => c + c).join("");
    if (/^#[0-9a-f]{6}$/i.test(value)) current[role] = value;
  }
  const selectedPreset =
    Object.entries(presets).find(([, value]) =>
      Object.keys(current).every(
        (key) => current[key].toLowerCase() === value[key],
      ),
    )?.[0] || "";
  fill(current, selectedPreset);
})();
