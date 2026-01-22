(function(){
  const MOUNT_ID = 'global-toc-mount';
  const JSON_FILE = 'docs.php'; // nằm cùng thư mục
  const mount = document.getElementById(MOUNT_ID);

  // Nếu không có mount thì bỏ qua
  if (!mount) return;

  // Tên file hiện tại (dùng để đánh dấu active)
  const currentFile = (location.pathname.split('/').pop() || 'index.html').toLowerCase();

  // Tạo khối aside + UL
  function renderSidebar(data){
    const aside = document.createElement('div');
    aside.innerHTML = `
      <h1>${data.title || 'Danh sách tài liệu:'}</h1>
      <ul class="toc"></ul>
    `;
    const ul = aside.querySelector('ul.toc');

    (data.items || []).forEach(it => {
      const li = document.createElement('li');
      const a  = document.createElement('a');
      a.href = it.href;
      a.className = 'lvl2';
      a.textContent = it.text;

      // trạng thái (ví dụ: gạch xoá như bạn đang dùng)
      if (it.status === 'remove') a.classList.add('remove');

      // đánh dấu active theo file hiện tại
      try {
        const itemFile = new URL(it.href, document.baseURI).pathname.split('/').pop().toLowerCase();
        console.log(itemFile);
        console.log(currentFile);
        if (itemFile === currentFile) {
            a.classList.add('active');
            console.log("----------------------------------------------------------------------")
        };
      } catch(e){ /* bỏ qua nếu URL lỗi */ }

      // tuỳ chọn attributes (target, rel…)
      if (it.target) a.setAttribute('target', it.target);
      if (it.rel)    a.setAttribute('rel', it.rel);

      li.appendChild(a);
      ul.appendChild(li);
    });

    mount.replaceChildren(aside);
  }

  // Fallback nếu lỗi
  function renderError(){
    mount.innerHTML = `
      <h1>Danh sách tài liệu:</h1>
      <p class="muted">Không tải được <code class="inline">${JSON_FILE}</code>.</p>
      <ul class="toc"><li><a class="lvl2" href="./">Trang chủ</a></li></ul>
    `;
  }

  // Fetch JSON (cùng origin)
  fetch(JSON_FILE, { cache: 'no-store' })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(renderSidebar)
    .catch(renderError);
})();