# 관리자 > 메뉴설정: 아이콘 선택창

Reference: https://www.postype.com/@bbing-dev/post/21948013

Every icon field (`me_icon[]`) on the menu settings page gets a **선택** button. It opens a picker with all Material Icons:

- search by name (`home`, `arrow`) or common Korean words (`홈`, `별`, `하트`, `메뉴`, …); Enter picks the first result
- glyph + name grid, the current icon highlighted, **아이콘 비우기** to clear, a link to the Google Fonts icon site
- picking fills the field and updates the row's preview icon; Esc / backdrop / × closes and returns focus to the field
- rows added later by the 메뉴 추가 window (`menu_form.php`) get the button too (MutationObserver); their preview cell
  now has an icon placeholder
- full screen on narrow windows; admin colours, admin font (appended inside `#container`)

Files:

| File | Role |
| --- | --- |
| `adm/js/icon-names.js` | 2,234 Material Icons names from google/material-design-icons `MaterialIcons-Regular.codepoints` (Apache 2.0). All of them were checked to render with the site's Material Icons font. |
| `adm/js/icon-picker.js` | Picker (vanilla JS, no jQuery). Also exposes `window.openIconPicker(input)`; any `input[data-icon-picker]` gets the button. |
| `adm/css/icon-picker.css` | Styles |
| `adm/menu_list.php` | Loads the three files; notes the button in the help text |
| `adm/menu_form.php` | Preview placeholder in new rows |

Test: `tests/icon_picker_smoke.cjs` (adds a temporary menu through the 메뉴 추가 window, picks/searches/clears/saves, checks
the mobile layout, then deletes the temporary menu).
