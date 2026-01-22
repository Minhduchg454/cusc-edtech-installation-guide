function initGlobalTOC() {
  const MOUNT_ID = "global-toc-mount";
  const JSON_FILE = "/docs.php";
  const mount = document.getElementById(MOUNT_ID);

  if (!mount) return;

  const currentFile = (
    location.pathname.split("/").pop() || "index.html"
  ).toLowerCase();

  function renderSidebar(data) {
    const aside = document.createElement("div");
    aside.innerHTML = `
      <h1>${data.title || "Danh sách tài liệu:"}</h1>
      <ul class="toc"></ul>
    `;
    const ul = aside.querySelector("ul.toc");

    (data.items || []).forEach((it) => {
      const li = document.createElement("li");
      const a = document.createElement("a");

      a.href = it.href;
      a.className = "lvl2";
      a.textContent = it.text;

      if (it.status === "remove") a.classList.add("remove");

      try {
        const itemFile = new URL(it.href, document.baseURI).pathname
          .split("/")
          .pop()
          .toLowerCase();
        if (itemFile === currentFile) a.classList.add("active");
      } catch (e) {}

      if (it.target) a.setAttribute("target", it.target);
      if (it.rel) a.setAttribute("rel", it.rel);

      li.appendChild(a);
      ul.appendChild(li);
    });

    mount.replaceChildren(aside);
  }

  function renderError() {
    mount.innerHTML = `
      <h1>Danh sách tài liệu:</h1>
      <p class="muted">Không tải được <code class="inline">${JSON_FILE}</code>.</p>
      <ul class="toc">
        <li><a class="lvl2" href="./">Trang chủ</a></li>
      </ul>
    `;
  }

  fetch(JSON_FILE, { cache: "no-store" })
    .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
    .then(renderSidebar)
    .catch(renderError);
}

// ============================
// Theme toggle
// ============================
const STORAGE_KEY = "theme";
const docEl = document.documentElement;
const themeBtn = document.getElementById("themeToggle");

function applyTheme(t) {
  if (t !== "light" && t !== "dark") t = "light";
  docEl.setAttribute("data-theme", t);
  localStorage.setItem(STORAGE_KEY, t);
  if (themeBtn)
    themeBtn.textContent = t === "dark" ? "🌞 Light mode" : "🌙 Dark mode";
}

applyTheme(localStorage.getItem(STORAGE_KEY) || "light");
themeBtn?.addEventListener("click", () => {
  applyTheme(
    (docEl.getAttribute("data-theme") || "light") === "dark" ? "light" : "dark",
  );
});

// ============================
// Page TOC (h2)
// ============================
const toc = document.getElementById("toc");
document.querySelectorAll(".content h2").forEach((h) => {
  const id = h.id || h.textContent.toLowerCase().replace(/\s+/g, "-");
  h.id = id;
  const li = document.createElement("li");
  const a = document.createElement("a");
  a.href = "#" + id;
  a.textContent = h.textContent;
  a.className = "lvl2";
  li.appendChild(a);
  toc?.appendChild(li);
});

// ============================
// Copy buttons
// ============================
document.querySelectorAll("pre .copy").forEach((btn) => {
  btn.addEventListener("click", () => {
    const code = btn.nextElementSibling.innerText;
    navigator.clipboard.writeText(code).then(() => {
      btn.textContent = "Đã copy";
      setTimeout(() => (btn.textContent = "Copy"), 1200);
    });
  });
});

// ============================
// Mobile sidebar toggle
// ============================
const menuBtn = document.getElementById("menuToggle");
const sidebar = document.querySelector(".sidebar");
const overlay = document.getElementById("overlay");

menuBtn?.addEventListener("click", () => {
  sidebar.classList.add("open");
  overlay.classList.add("show");
});

overlay?.addEventListener("click", () => {
  sidebar.classList.remove("open");
  overlay.classList.remove("show");
});

sidebar?.querySelectorAll("a").forEach((a) => {
  a.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  });
});

initGlobalTOC();

// ============================
// Auto page title from <h1 id="top">
// ============================
(function syncTitleFromH1() {
  const h1 = document.getElementById("top");
  if (!h1) return;

  const text = h1.textContent.trim();
  if (!text) return;

  document.title = text;
})();
