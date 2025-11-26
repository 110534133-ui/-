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

$pageTitle = '庫存查詢 - 員工管理系統'; // 標題

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
    .navbar-brand{ font-weight:700; font-size:1.5rem; background: linear-gradient(45deg,#ffffff,#ffffff);
      -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; }

    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .container-fluid{ padding:30px !important; }
    h1{ background: var(--primary-gradient); -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent; font-weight:700; font-size:2.5rem; margin-bottom:30px; }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }

    .table{ border-radius: var(--border-radius); overflow:hidden; background:#fff; box-shadow: var(--card-shadow); }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color: rgba(0,0,0,.05); }
    .table-hover tbody tr:hover{ background: rgba(227,23,111,.05); transform: scale(1.01); }

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

    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }
    .form-select, .form-control{ border-radius:12px; }
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
                    <a class="nav-link active" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link" href="庫存調整.php">庫存調整</a>
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
            <h1>庫存查詢</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">庫存查詢</li>
          </ol>

          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">關鍵字</label>
                  <input type="text" class="form-control" id="keywordInput" placeholder="輸入品名 / 編號 / 單位 / 類別 / 經手人 / 時間...">
                </div>
                <div class="col-md-3">
                  <label class="form-label">類別</label>
                  <select id="categorySelect" class="form-select">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-primary me-2" id="btnSearch"><i class="fas fa-search me-1"></i>查詢</button>
                  <button class="btn btn-outline-secondary" id="btnClear">清除</button>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><i class="fas fa-boxes-stacked me-2"></i>庫存列表</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>編號</th>
                      <th>品項名稱</th>
                      <th>類別</th>
                      <th>庫存數量</th>
                      <th>單位</th>
                      <th>進貨時間</th>
                      <th>進貨人</th>
                    </tr>
                  </thead>
                  <tbody id="inventoryTable">
                    <tr id="noDataRow">
                      <td colspan="7" class="text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>載入中...
                      </td>
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

  <script>
    const API_BASE = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_INVENTORY = API_BASE + '/product_stock_list.php';
  </script>
  <script>
    const norm = s => String(s ?? '').normalize('NFKC').trim().toLowerCase();
    const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-nav-toggled');
    });

    // ===== 狀態 =====
    let rawInventory = [];
    let filtered = [];
    let noRowTemplate; // 🔥 修正：建立一個全域變數來保存 "無資料" 的模板

    // ===== 初始化 =====
    window.addEventListener('DOMContentLoaded', async () => {
      // 🔥 修正：頁面載入時，立刻抓取 "無資料" 模板，並將它從 DOM 移除
      noRowTemplate = document.getElementById('noDataRow');
      if (noRowTemplate) {
          noRowTemplate.remove();
          noRowTemplate.classList.remove('d-none'); // 確保它在加入時是可見的
      }
      
      await loadLoggedInUser();
      bindEvents();
      await loadInventory();
    });

    function bindEvents(){
      const run = () => applyFilter();
      document.getElementById('btnSearch').addEventListener('click', run);
      document.getElementById('btnClear').addEventListener('click', () => {
        document.getElementById('keywordInput').value = '';
        document.getElementById('categorySelect').value = '';
        run();
      });
      document.getElementById('keywordInput').addEventListener('keydown', e => { if(e.key==='Enter') run(); });
      document.getElementById('categorySelect').addEventListener('change', run);
    }

    async function loadInventory(){
      const url = API_INVENTORY + '?limit=2000&t=' + Date.now();
      const tbody = document.getElementById('inventoryTable');
      
      // 🔥 修正：在載入開始前，先把 "載入中..." 放回去
      if (noRowTemplate) {
          noRowTemplate.querySelector('td').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>載入中...';
          tbody.innerHTML = '';
          tbody.appendChild(noRowTemplate);
      }
      
      try{
        const res  = await fetch(url, { credentials:'include' });
        const text = await res.text();

        let data;
        try { data = JSON.parse(text); }
        catch(e){
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-start p-3">
            <div class="fw-bold mb-2">API 回傳不是 JSON，可能是 PHP 錯誤訊息：</div>
            <pre class="small mb-0" style="white-space:pre-wrap">${escapeHtml(text)}</pre>
          </td></tr>`;
          return;
        }

        const rows = Array.isArray(data) ? data : (data.data || data.rows || []);
        if(!Array.isArray(rows)){
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-3">API 格式不符，預期為陣列/有 data 欄位。</td></tr>`;
          return;
        }

        rawInventory = rows.map(r => {
          const catLabel = (r.category ?? r.category_name ?? r.category_text ?? r.category_label ?? '');
          const catId    = (r.category_id ?? r.cat_id ?? r.cid ?? null);
          const lastAt   = (r.last_update_iso ?? r.last_update_at ?? r.last_update ?? '');
          return {
            id:            r.id ?? r.item_id ?? '',
            name:          r.name ?? r.item_name ?? '',
            category:      String(catLabel || '').trim(),
            category_key:  norm(catLabel || (catId ?? '')),
            category_id:   (catId === null || catId === undefined) ? '' : String(catId),
            unit:          r.unit ?? '',
            quantity:      Number(r.quantity ?? 0),
            purchase_time: String(lastAt),
            purchaser:     r.updated_by ?? r.purchaser ?? ''
          };
        });

        const sel = document.getElementById('categorySelect');
        const map = new Map();
        rawInventory.forEach(x=>{
          const key   = x.category_key;
          const label = x.category || (x.category_id ? ('分類 #' + x.category_id) : '');
          if(key) map.set(key, label);
        });
        sel.innerHTML = '';
        sel.appendChild(new Option('全部',''));
        [...map.entries()]
          .sort((a,b)=>a[1].localeCompare(b[1],'zh-Hant'))
          .forEach(([key,label])=> sel.appendChild(new Option(label, key)));

        filtered = [...rawInventory];
        
        // 🔥 修正：更新 "無資料" 模板的文字
        if (noRowTemplate) {
            noRowTemplate.querySelector('td').innerHTML = '<i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料';
        }
        
        renderTable();
      }catch(err){
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-3">載入失敗：${escapeHtml(err.message)}</td></tr>`;
      }
    }

    function applyFilter(){
      const kw     = norm(document.getElementById('keywordInput').value);
      const selKey = document.getElementById('categorySelect').value;

      filtered = rawInventory.filter(item => {
        const inCat =
          !selKey ||
          item.category_key === selKey ||
          norm(item.category) === selKey ||
          (item.category_id && norm(item.category_id) === selKey);

        const hay = [
          item.id, item.name, item.category, item.category_id,
          item.unit, item.purchaser, item.purchase_time, item.quantity
        ].map(x => String(x ?? '')).join(' ').toLowerCase();

        const inKw = !kw || hay.includes(kw);
        return inCat && inKw;
      });

      renderTable();
    }

    // 🔥 修正：重寫 renderTable 函數
    function renderTable(){
      const tbody = document.getElementById('inventoryTable');
      tbody.innerHTML = ''; // 清空表格

      if(filtered.length === 0){
        // 如果沒有資料，就把 "無資料" 模板加回去
        if (noRowTemplate) {
            tbody.appendChild(noRowTemplate);
        }
        return;
      }

      // 如果有資料，建立新的資料列
      tbody.innerHTML = filtered.map(item => `
        <tr>
          <td>${escapeHtml(item.id)}</td>
          <td class="text-start">${escapeHtml(item.name)}</td>
          <td>${escapeHtml(item.category || (item.category_id ? ('分類 #' + item.category_id) : ''))}</td>
          <td>${escapeHtml(item.quantity)}</td>
          <td>${escapeHtml(item.unit)}</td>
          <td>${escapeHtml(item.purchase_time)}</td>
          <td>${escapeHtml(item.purchaser)}</td>
        </tr>
      `).join('');
    }
    
    const el = id => document.getElementById(id);
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('✅ 庫存查詢 已登入:', userName, 'ID:', userId);
        
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>