<?php
// 🔥 整合：加入權限檢查
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 修正：A 級(老闆) 或 B 級(管理員) 可以訪問
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    // 如果 '不是A' 而且 '也不是B'，就顯示無權限
    show_no_permission_page(); // 會 exit
}

// 🔥 整合：取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '庫存調整 - 員工管理系統'; // 標題

// 統一路徑 (JS 會用到)
$API_BASE_URL  = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root{
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);
      --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --card-shadow: 0 15px 35px rgba(0,0,0,.1);
      --border-radius: 20px;
      --transition: all .3s cubic-bezier(.4,0,.2,1);
    }
    *{ transition: var(--transition); }
    body{ background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }
    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#ffffff,#ffffff);
      -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
    }
    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }
    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container {
        position: relative; display: flex; align-items: center;
        background: rgba(255, 255, 255, 0.15); border-radius: 50px;
        padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px); border: 2px solid transparent;
    }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); }
    .search-input {
        flex: 1; border: none; outline: none; background: transparent;
        padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500;
    }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        border: none; border-radius: 40px; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn i { color: #ff6b6b; font-size: 16px; }
    .user-avatar{border:2px solid rgba(255,255,255,.5)}
    .container-fluid{ padding:30px !important; }
    h1{
      background: var(--primary-gradient);
      -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight:700; font-size:2.5rem; margin-bottom:30px;
    }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; }
    .btn-primary{
      background: var(--primary-gradient) !important;
      border: none !important;
      border-radius: 25px;
      color: #fff;
    }
    .btn-primary:hover,.btn-primary:focus,.btn-primary:active{
      background: var(--primary-gradient) !important;
      filter: brightness(1.05);
      box-shadow: 0 10px 25px rgba(209,209,209,.976);
      color: #fff;
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

      <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
          <div class="search-container-wrapper">
              <div class="search-container">
                  <input class="search-input" type="text" placeholder="搜尋員工、班表、薪資..." aria-label="Search" />
                  <button class="search-btn" id="btnNavbarSearch" type="button">
                      <i class="fas fa-search"></i>
                  </button>
              </div>
          </div>
      </form>

      <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <img class="user-avatar rounded-circle me-1" src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>" width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
                  <span id="navUserName"><?php echo htmlspecialchars($userName); ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="帳號設置.php">帳號設置</a></li>
                  <li><hr class="dropdown-divider" /></li>
                  <li><a class="dropdown-item" href="logout.php"><i class="fas fa-right-from-bracket me-2"></i>登出</a></li>
              </ul>
          </li>
      </ul>
  </nav>

  <div id="layoutSidenav">
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.php">薪資管理</a>
              </nav>
            </div>

            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory" aria-expanded="true">
                  庫存管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse show" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link active" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link" href="商品管理.php">商品管理</a>
                  </nav>
                </div>
                <a class="nav-link" href="日報表.php"> <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
                </nav>
            </div>

          
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseWebsite" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>網站管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="layout-static.php">官網資料修改</a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#websiteCollapseMember" aria-expanded="false">
                  會員管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="websiteCollapseMember" data-bs-parent="#sidenavAccordionWebsite">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="member-list.php">會員清單</a>
                    <a class="nav-link" href="member-detail.php">詳細資料頁</a>
                    <a class="nav-link" href="point-manage.php">點數管理</a>
                  </nav>
                </div>
              </nav>
            </div>

            <div class="sb-sidenav-menu-heading">Addons</div>
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <span id="loggedAs"><?php echo htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>庫存調整</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">庫存調整</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

          <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-plus-circle me-2"></i>新增庫存異動</div>
            <div class="card-body">
              <form id="adjustForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">品項</label>
                  <select id="itemSelect" class="form-select" required>
                    <option value="">請選擇品項</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">類別</label>
                  <input id="itemCategory" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">單位</label>
                  <input id="itemUnit" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">數量</label>
                  <input id="qty" class="form-control" type="number" step="1" placeholder="例如 10" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label d-block">方向</label>
                  <div class="d-flex gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_in" value="in" checked>
                      <label class="form-check-label" for="io_in">入庫</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_out" value="out">
                      <label class="form-check-label" for="io_out">出庫</label>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <label class="form-label">進貨/異動時間（可留白=現在）</label>
                  <input id="when" class="form-control" type="datetime-local">
                </div>
                <div class="col-md-3">
                  <label class="form-label">進貨人 / 經手人</label>
                  <input id="who" class="form-control" type="text" placeholder="輸入姓名" required>
                </div>
                <div class="col-md-3">
                  <button id="btnSubmit" class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-1"></i><span class="txt">送出</span></button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-secondary w-100" type="button" id="btnClear"><i class="fas fa-eraser me-1"></i>清除</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-header fw-semibold"><i class="fas fa-clock-rotate-left me-2"></i>最近異動</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                  <thead class="table-light">
                    <tr>
                      <th>編號</th>
                      <th>品項名稱</th>
                      <th>類別</th>
                      <th>数量</th>
                      <th>單位</th>
                      <th>時間</th>
                      <th>經手人</th>
                    </tr>
                  </thead>
                  <tbody id="recentBody">
                    <tr id="recentEmpty" class="d-none">
                      <td colspan="7" class="text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">© 2024 令和博多餐廳管理系統 - Xxing0625</div>
            <div>
              <a href="#" class="text-decoration-none">隱私政策</a>
              <span class="mx-2">•</span>
              <a href="#" class="text-decoration-none">使用條款</a>
              <span class="mx-2">•</span>
              <a href="#" class="text-decoration-none">技術支援</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    const API_BASE    = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_PRODUCTS= API_BASE + '/product_list.php';
    const API_ADJUST  = API_BASE + '/inventory_adjust.php';
    const API_RECENT  = API_BASE + '/inventory_latest.php?limit=20';
    const recentUrl   = () => API_RECENT + '&t=' + Date.now();
    const currentUserName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;

    const qs   = id => document.getElementById(id);
    const escapeHtml = str => String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    const setBusy = (b)=>{ const btn=qs('btnSubmit'); if(!btn) return; btn.disabled=b; const t=btn.querySelector('.txt'); if(t) t.textContent=b?'處理中…':'送出'; };
    function showOk(msg){ const a=qs('msgOk'); if(!a) return; a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 2500); }
    function showErr(msg){ const a=qs('msgErr'); if(!a) return; a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 5000); }
    function hideMsg(){ qs('msgOk')?.classList.add('d-none'); qs('msgErr')?.classList.add('d-none'); }
    function localInputToIso(val){ if(!val) return ''; return val.replace('T',' ') + (val.length === 16 ? ':00' : ''); }

    let products = [];

    window.addEventListener('DOMContentLoaded', async ()=>{
      await loadLoggedInUser();
      if (qs('who')) {
          qs('who').value = currentUserName;
      }
      await loadProducts();
      await loadRecent();
      bind();
    });

    function bind(){
      qs('itemSelect')?.addEventListener('change', onItemChange);
      qs('adjustForm')?.addEventListener('submit', submitAdjust);
      qs('btnClear')?.addEventListener('click', resetForm);
    }

    function onItemChange(){
      const id = Number(qs('itemSelect')?.value||0);
      const p = products.find(x=> Number(x.id) === id);
      if(qs('itemCategory')) qs('itemCategory').value = p ? (p.category||'') : '';
      if(qs('itemUnit'))     qs('itemUnit').value      = p ? (p.unit||'')     : '';
    }

    async function loadProducts(){
      const sel = qs('itemSelect');
      try{
        const r = await fetch(API_PRODUCTS + '?t=' + Date.now(), {credentials:'include'});
        const text = await r.text();
        let data;
        try { data = JSON.parse(text); }
        catch(e){ console.error('product_list 非 JSON：', text); throw new Error('品項清單格式錯誤'); }
        if(!r.ok || data.error){ throw new Error(data?.error || ('HTTP '+r.status)); }
        const list = Array.isArray(data) ? data : (data.data||[]);
        if(!list.length){
          if(sel){ sel.innerHTML = '<option value="">（尚無品項）</option>'; sel.disabled = true; }
          showErr('品項清單為空，請先在「商品管理」頁面建立品項');
          return;
        }
        products = list;
        if(sel){
          sel.disabled = false;
          sel.innerHTML = '<option value="">請選擇品項</option>' +
            products.map(p => `<option value="${p.id}">${escapeHtml(p.name || ('品項#'+p.id))}${p.unit?'（'+escapeHtml(p.unit)+'）':''}</option>`).join('');
        }
      }catch(e){
        if(sel){ sel.innerHTML = '<option value="">無法載入品項</option>'; sel.disabled = true; }
        showErr('載入品項失敗：' + e.message);
      }
    }

    async function loadRecent(silent=false){
      try{
        const r = await fetch(recentUrl(), {credentials:'include'});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const tb = qs('recentBody');
        const empty = qs('recentEmpty');
        if(!tb) return;
        tb.innerHTML='';
        if(!rows.length){
          if(empty){ empty.classList.remove('d-none'); tb.appendChild(empty); }
          return;
        }
        if(empty) empty.classList.add('d-none');
        tb.innerHTML = rows.map(x=>`
          <tr>
            <td>${escapeHtml(x.id)}</td>
            <td class="text-start">${escapeHtml(x.name||'')}</td>
            <td>${escapeHtml(x.category||'')}</td>
            <td class="${Number(x.quantity)<0?'text-danger fw-bold':''}">${escapeHtml(x.quantity)}</td>
            <td>${escapeHtml(x.unit||'')}</td>
            <td>${escapeHtml(x.last_update_iso||x.last_update||'')}</td>
            <td>${escapeHtml(x.updated_by||'')}</td>
          </tr>
        `).join('');
      }catch(e){
        console.error(e);
        if(!silent) showErr('載入最近異動失敗：' + e.message);
      }
    }

    async function submitAdjust(e){
      e.preventDefault();
      hideMsg();
      setBusy(true);

      const item_id   = Number(qs('itemSelect')?.value||0);
      const qty_raw   = Number(qs('qty')?.value||0);
      const io        = document.querySelector('input[name="io"]:checked')?.value || 'in';
      const updated_by= (qs('who')?.value||'').trim();
      const whenInput = qs('when')?.value || '';

      if(!item_id){ setBusy(false); return showErr('請選擇品項'); }
      if(!qty_raw || !Number.isFinite(qty_raw)){ setBusy(false); return showErr('請輸入正確數量'); }
      if(!updated_by){ setBusy(false); return showErr('請輸入經手人'); }

      const quantity = io === 'out' ? -Math.abs(qty_raw) : Math.abs(qty_raw);
      const body = { item_id, quantity, updated_by };
      if(whenInput){ body.when = whenInput; }

      try{
        const r = await fetch(API_ADJUST, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(body),
          credentials:'include'
        });

        const rawText = await r.text();
        let resp;
        try { resp = JSON.parse(rawText); }
        catch { throw new Error('伺服器回應非 JSON：\n' + rawText); }
        if(!r.ok || resp.error){ throw new Error(resp.error || ('HTTP '+r.status)); }

        const p = products.find(x => Number(x.id) === item_id) || {};
        const whenText = whenInput ? localInputToIso(whenInput)
                                   : new Date().toLocaleString('zh-TW',{ hour12:false });
        const rowHtml = `
          <tr>
            <td>${escapeHtml(resp.id ?? '')}</td>
            <td class="text-start">${escapeHtml(p.name||'')}</td>
            <td>${escapeHtml(p.category||'')}</td>
            <td class="${quantity<0?'text-danger fw-bold':''}">${escapeHtml(quantity)}</td>
            <td>${escapeHtml(p.unit||'')}</td>
            <td>${escapeHtml(whenText || '')}</td>
            <td>${escapeHtml(updated_by)}</td>
          </tr>`;

        const tb = qs('recentBody');
        const empty = qs('recentEmpty');
        if(empty) empty.classList.add('d-none');
        if(tb) tb.insertAdjacentHTML('afterbegin', rowHtml);

        showOk('已新增庫存異動（編號 ' + (resp.id ?? '') + '）');
        resetForm();
        await loadRecent(true);
      }catch(e){
        console.error(e);
        showErr('新增失敗：' + e.message);
      }finally{
        setBusy(false);
      }
    }

    function resetForm(){
      if(qs('itemSelect'))   qs('itemSelect').value='';
      if(qs('itemCategory')) qs('itemCategory').value='';
      if(qs('itemUnit'))     qs('itemUnit').value='';
      if(qs('qty'))          qs('qty').value='';
      if(qs('when'))         qs('when').value='';
      if(qs('who'))          qs('who').value = currentUserName;
      const ioIn = qs('io_in'); if(ioIn) ioIn.checked = true;
    }
    
    const el = id => document.getElementById(id);
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        console.log('✅ 庫存調整 已登入:', userName, 'ID:', userId);
        try {
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
            const data = await r.json();
            if(data.avatar_url) {
                const avatarUrl = data.avatar_url + (data.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
                const avatar = document.querySelector('.navbar .user-avatar');
                if(avatar) {
                    avatar.src = avatarUrl;
                    console.log('✅ 頭像已更新:', avatarUrl);
                }
            }
            }
        } catch(e) {
            console.warn('載入頭像失敗:', e);
        }
    }
  </script>
</body>
</html>