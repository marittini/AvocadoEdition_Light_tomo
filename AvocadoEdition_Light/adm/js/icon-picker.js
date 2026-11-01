/*
 * 아이콘 선택창 (관리자 > 메뉴설정)
 * 참고: https://www.postype.com/@bbing-dev/post/21948013
 * 아이콘 입력칸(input[data-icon-picker] 또는 name="me_icon[]") 옆에 '선택' 버튼을 붙이고,
 * 누르면 Material Icons 목록에서 검색 · 미리보기로 골라 입력칸에 이름을 넣는다. 나중에 추가되는 줄에도 붙는다.
 */
(() => {
  const names = Array.isArray(window.tomoIconNames) ? window.tomoIconNames : [];
  // 자주 찾는 한글 낱말 → 아이콘 이름 일부
  const ko = {
    "홈": "home", "집": "home house", "메뉴": "menu", "검색": "search", "설정": "settings", "사람": "person people",
    "회원": "person account", "프로필": "person account_circle badge", "별": "star", "하트": "favorite heart", "좋아요": "thumb favorite",
    "메일": "mail email", "편지": "mail drafts", "전화": "call phone", "채팅": "chat forum", "댓글": "comment chat",
    "알림": "notifications", "종": "notifications", "달력": "calendar event", "일정": "event calendar", "시계": "schedule clock watch",
    "사진": "photo image", "이미지": "image photo", "카메라": "camera photo_camera", "음악": "music audio", "영상": "movie video",
    "책": "book menu_book", "글": "article description edit", "쓰기": "edit create", "수정": "edit", "삭제": "delete",
    "추가": "add", "닫기": "close", "확인": "check done", "체크": "check", "화살표": "arrow", "위": "up upward", "아래": "down downward",
    "왼쪽": "left back", "오른쪽": "right forward", "링크": "link", "공유": "share", "다운로드": "download", "업로드": "upload",
    "잠금": "lock", "열쇠": "key vpn_key", "게임": "game sports_esports", "선물": "card_giftcard redeem gift", "장바구니": "shopping cart",
    "지도": "map place location", "위치": "place location pin", "날씨": "cloud sunny", "해": "sunny wb_sunny", "달": "nightlight bedtime dark_mode",
    "꽃": "local_florist", "고양이": "pets", "동물": "pets", "음식": "restaurant fastfood", "커피": "coffee local_cafe",
    "로그인": "login", "로그아웃": "logout", "도움말": "help", "정보": "info", "경고": "warning", "공지": "campaign announcement",
    "폴더": "folder", "파일": "description insert_drive_file", "목록": "list format_list", "그리드": "grid apps", "대시보드": "dashboard",
    "캐릭터": "face person", "얼굴": "face mood", "웃음": "mood sentiment_satisfied", "새": "new fiber_new", "인기": "whatshot local_fire",
  };

  let dialog, search, grid, meta, current = null, buttons = [];

  function build() {
    dialog = document.createElement("div");
    dialog.className = "icon-picker";
    dialog.hidden = true;
    dialog.innerHTML =
      '<div class="icon-picker-backdrop" data-icon-close></div>' +
      '<div class="icon-picker-panel" role="dialog" aria-modal="true" aria-labelledby="icon-picker-title">' +
      '<div class="icon-picker-head"><strong class="icon-picker-title" id="icon-picker-title">아이콘 선택</strong>' +
      '<button type="button" class="icon-picker-x" data-icon-close aria-label="닫기"><span class="material-icons" aria-hidden="true">close</span></button></div>' +
      '<div class="icon-picker-search"><span class="material-icons" aria-hidden="true">search</span>' +
      '<input type="search" placeholder="이름으로 찾기 (예: home, star, 홈, 별)" aria-label="아이콘 검색" autocomplete="off"></div>' +
      '<p class="icon-picker-meta" aria-live="polite"></p>' +
      '<div class="icon-picker-grid" role="listbox" aria-label="아이콘"></div>' +
      '<div class="icon-picker-foot"><button type="button" class="icon-picker-clear" data-icon-clear>아이콘 비우기</button>' +
      '<a href="https://fonts.google.com/icons?icon.set=Material+Icons" target="_blank" rel="noopener">구글 아이콘 사이트</a></div>' +
      "</div>";
    (document.getElementById("container") || document.body).append(dialog); // 관리자 본문 글꼴을 그대로 따른다
    search = dialog.querySelector("input");
    grid = dialog.querySelector(".icon-picker-grid");
    meta = dialog.querySelector(".icon-picker-meta");
    const frag = document.createDocumentFragment();
    for (const name of names) {
      const b = document.createElement("button");
      b.type = "button";
      b.className = "icon-picker-item";
      b.dataset.icon = name;
      b.setAttribute("role", "option");
      b.title = name;
      b.innerHTML = '<span class="material-icons" aria-hidden="true">' + name + "</span><span class=\"icon-picker-name\">" + name + "</span>";
      frag.append(b);
      buttons.push(b);
    }
    grid.append(frag);
    search.addEventListener("input", filter);
    search.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); const first = buttons.find((b) => !b.hidden); if (first) pick(first.dataset.icon); }
      if (e.key === "ArrowDown") { e.preventDefault(); buttons.find((b) => !b.hidden)?.focus(); }
    });
    dialog.addEventListener("click", (e) => {
      if (e.target.closest("[data-icon-close]")) return close();
      if (e.target.closest("[data-icon-clear]")) return pick("");
      const item = e.target.closest(".icon-picker-item");
      if (item) pick(item.dataset.icon);
    });
    dialog.addEventListener("keydown", (e) => {
      if (e.key === "Escape") { e.preventDefault(); close(); }
      if (e.key === "Tab") { // 창 안에서만 돌기
        const f = [...dialog.querySelectorAll("input, button:not([hidden]), a")].filter((el) => el.offsetParent);
        if (!f.length) return;
        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
      }
    });
  }

  function filter() {
    const q = search.value.trim().toLowerCase();
    const terms = q ? q.split(/\s+/).map((t) => (ko[t] ? ko[t].split(" ") : [t])) : [];
    let n = 0;
    for (const b of buttons) {
      const name = b.dataset.icon;
      const show = terms.every((alts) => alts.some((t) => name.includes(t.replace(/\s+/g, "_"))));
      b.hidden = !show;
      if (show) n++;
    }
    meta.textContent = q ? (n ? "찾은 아이콘 " + n.toLocaleString() + "개" : "찾는 아이콘이 없습니다. 영어 이름으로도 찾아 보세요.") : "전체 " + names.length.toLocaleString() + "개";
  }

  function open(input) {
    if (!names.length) { window.open("https://fonts.google.com/icons?icon.set=Material+Icons", "_blank", "noopener"); return; }
    if (!dialog) build();
    current = input;
    const value = input.value.trim();
    buttons.forEach((b) => { const on = b.dataset.icon === value; b.classList.toggle("is-current", on); b.setAttribute("aria-selected", String(on)); });
    search.value = "";
    filter();
    dialog.hidden = false;
    document.documentElement.classList.add("icon-picker-open");
    const cur = buttons.find((b) => b.dataset.icon === value);
    if (cur) cur.scrollIntoView({ block: "center" }); else grid.scrollTop = 0;
    search.focus();
  }
  function close() {
    if (!dialog || dialog.hidden) return;
    dialog.hidden = true;
    document.documentElement.classList.remove("icon-picker-open");
    if (current) current.focus();
  }
  function pick(name) {
    if (current) {
      current.value = name;
      current.dispatchEvent(new Event("input", { bubbles: true }));
      current.dispatchEvent(new Event("change", { bubbles: true }));
    }
    close();
  }

  // 같은 줄의 미리보기(<i class="material-icons">)를 입력에 맞춰 바꾼다
  function preview(input) {
    const row = input.closest("tr");
    let icon = row && [...row.querySelectorAll("i.material-icons")].find((i) => !i.closest(".icon-picker-field"));
    if (!icon) { // 미리보기 자리가 없으면 입력칸 앞의 빈 칸에 만든다
      const prev = input.closest("td")?.previousElementSibling;
      if (!prev || prev.querySelector("input, select, button, i")) return;
      icon = document.createElement("i");
      icon.className = "material-icons";
      prev.append(icon);
    }
    icon.textContent = input.value.trim();
  }

  function enhance(root) {
    root.querySelectorAll('input[data-icon-picker], input[name="me_icon[]"]').forEach((input) => {
      if (input.closest(".icon-picker-field")) return;
      const wrap = document.createElement("span");
      wrap.className = "icon-picker-field";
      input.before(wrap);
      wrap.append(input);
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "icon-picker-open";
      btn.title = "아이콘 선택";
      btn.innerHTML = '<span class="material-icons" aria-hidden="true">apps</span><span>선택</span>';
      btn.addEventListener("click", () => open(input));
      wrap.append(btn);
      input.addEventListener("input", () => preview(input));
    });
  }

  enhance(document);
  // 메뉴 추가 창(menu_form.php)이 넣는 새 줄에도 붙인다
  new MutationObserver((list) => { for (const m of list) for (const n of m.addedNodes) if (n.nodeType === 1) enhance(n.parentNode || n); })
    .observe(document.body, { childList: true, subtree: true });
  window.openIconPicker = (input) => input && open(input); // 원본 글과 같은 이름으로도 부를 수 있게
})();
