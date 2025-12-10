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
    /* ====== 整體風格：跟 日報表記錄 / 薪資管理 一樣 ====== */
    :root {
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
      --text-main: #0f172a;
      --text-subtle: #64748b;

      --card-bg: rgba(255, 255, 255, 0.96);
      --card-radius: 22px;

      --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.12);
      --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

      --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);
    }

    * {
      transition: var(--transition-main);
    }

    body {
      min-height: 100vh;
      background:
        radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.24), transparent 55%),
        radial-gradient(circle at 100% 0%, rgba(222, 114, 244, 0.24), transparent 55%),
        var(--bg-gradient);
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text-main);
    }


    /* ====== Top navbar：藍色漸層 ====== */
    .sb-topnav {
      background: linear-gradient(120deg, #1e3a8a, #3658ff) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 14px 35px rgba(15, 23, 42, 0.42);
      backdrop-filter: blur(18px);
    }

    .navbar-brand {
      font-weight: 800;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: #f9fafb !important;
    }

    .navbar-nav .nav-link {
      color: #e5e7eb !important;
    }

    .navbar-nav .nav-link:hover {
      color: #ffffff !important;
    }

    .container-fluid {
      padding: 26px 28px;
    }
    /* ====== Sidebar：跟日報表記錄一樣 ====== */
   .sb-sidenav {
      background:
        radial-gradient(circle at 40% 0%, rgba(56, 189, 248, 0.38), transparent 65%),
        radial-gradient(circle at 80% 100%, rgba(147, 197, 253, 0.34), transparent 70%),
        linear-gradient(180deg, rgba(220, 235, 255, 0.92), rgba(185, 205, 255, 0.9));
      backdrop-filter: blur(22px);
      border-right: 1px solid rgba(255, 255, 255, 0.55);
    }

    .sb-sidenav-menu-heading {
      color: #1e293b !important;
      opacity: 0.75;
      font-size: 0.78rem;
      letter-spacing: .18em;
      margin: 20px 0 8px 16px;
    }

    .sb-sidenav .nav-link {
      color: #0f172a !important;
      font-weight: 600;
      border-radius: 18px;
      padding: 12px 18px;
      margin: 8px 12px;
      border: 2px solid rgba(255, 255, 255, 0.9);
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.80),
        rgba(241, 248, 255, 0.95)
      );
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .sb-sidenav .nav-link .sb-nav-link-icon {
      margin-right: 10px;
      color: #1e293b !important;
      opacity: 0.9 !important;
      font-size: 1.05rem;
    }

    .sb-sidenav .sb-sidenav-collapse-arrow i,
    .sb-sidenav .nav-link i.fa-chevron-right {
      color: #1e293b !important;
      opacity: 0.85 !important;
    }

    .sb-sidenav .nav-link:hover {
      border-color: rgba(255, 255, 255, 1);
      box-shadow: 0 14px 30px rgba(59, 130, 246, 0.4);
      transform: translateY(-1px);
    }

    .sb-sidenav .nav-link:hover .sb-nav-link-icon,
    .sb-sidenav .nav-link:hover .sb-sidenav-collapse-arrow i,
    .sb-sidenav .nav-link:hover i.fa-chevron-right {
      color: #0f172a !important;
      opacity: 1 !important;
    }

    .sb-sidenav .nav-link.active {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      border-color: rgba(255, 255, 255, 0.98);
      color: #ffffff !important;
      box-shadow: 0 18px 36px rgba(59, 130, 246, 0.6);
    }

    .sb-sidenav .nav-link.active .sb-nav-link-icon,
    .sb-sidenav .nav-link.active .sb-sidenav-collapse-arrow i {
      color: #e0f2fe !important;
    }

    .sb-sidenav-footer {
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.9),
        rgba(226, 232, 255, 0.95)
      ) !important;
      backdrop-filter: blur(16px);
      border-top: 1px solid rgba(148, 163, 184, 0.5);
      padding: 16px 20px;
      color: #111827 !important;
      box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.10);
      font-size: 0.95rem;
    }

    .sb-sidenav-footer .small {
      color: #6b7280 !important;
    }


    /* 修正側欄箭頭顏色 */
    .sb-sidenav .nav-link svg,
    .sb-sidenav .nav-link svg path,
    .sb-sidenav .nav-link i,
    .sb-sidenav .nav-link::after {
      stroke: #1e293b !important;
      color: #1e293b !important;
      fill: #1e293b !important;
      opacity: 0.9 !important;
    }
    .sb-sidenav .nav-link:hover svg,
    .sb-sidenav .nav-link:hover svg path,
    .sb-sidenav .nav-link:hover i,
    .sb-sidenav .nav-link:hover::after {
      stroke: #0f172a !important;
      color: #0f172a !important;
      fill: #0f172a !important;
      opacity: 1 !important;
    }

    /* ====== 標題 & 麵包屑 ====== */
    h1 {
      font-size: 2rem;
      font-weight: 800;
      letter-spacing: .04em;
      background: linear-gradient(120deg, #0f172a, #2563eb);
      -webkit-background-clip: text;
      color: transparent;
      margin-bottom: 8px;
    }

    .breadcrumb {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 0.8rem;
      border: 1px solid rgba(148, 163, 184, 0.4);
    }

    .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
      color: #9ca3af;
    }

    /* ====== 卡片 / 表格 ====== */
    .card {
      background: var(--card-bg);
      border-radius: var(--card-radius);
      border: 1px solid rgba(226, 232, 240, 0.95);
      box-shadow: var(--shadow-soft);
    }

    .card-header {
      background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(239, 246, 255, 0.96));
      border-bottom: 1px solid rgba(226, 232, 240, 0.95);
      font-weight: 600;
      font-size: 0.95rem;
      padding-top: 14px;
      padding-bottom: 10px;
    }

    .card-body {
      padding: 18px 20px 20px;
    }

    .table {
      border-radius: var(--card-radius);
      overflow: hidden;
      background: #fff;
    }

    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
      border: none;
      font-weight: 600;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      padding: 12px 10px;
    }

    .table tbody td {
      text-align: center;
      vertical-align: middle;
      white-space: nowrap;
      padding: 12px 10px;
      border-color: rgba(148, 163, 184, .25);
    }

    .table-hover tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    footer {
      background: transparent;
      border-top: 1px solid rgba(148, 163, 184, 0.35);
      margin-top: 24px;
      padding-top: 14px;
      font-size: 0.8rem;
      color: var(--text-subtle);
    }

    @media (max-width: 992px) {
      .container-fluid {
        padding: 20px 16px;
      }
    }

    @media (max-width: 768px) {
      .container-fluid {
        padding: 16px 12px;
      }
      h1 {
        font-size: 1.6rem;
      }
    }

    /* ====== 查詢 / 清除按鈕：做成 pill 感覺 ====== */
    .btn-primary {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      color: #fff;
      border-color: rgba(59, 130, 246, .25);
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }
    .btn-primary:hover {
      filter: brightness(1.03);
      box-shadow: 0 8px 18px rgba(59, 130, 246, 0.5);
      transform: translateY(-1px);
    }

    .btn-outline-secondary {
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      border-color: rgba(148, 163, 184, 0.7);
      color: #1d4ed8;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .btn-outline-secondary:hover {
      background-color: #eff6ff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .6);
      box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
      transform: translateY(-1px);
    }

    /* ====== Navbar 搜尋列（沿用你原本設計，顏色調整後仍能用） ====== */
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
    .search-btn i { color: #2563eb; font-size: 16px; }

    .user-avatar {
      border: 2px solid rgba(255, 255, 255, .5);
    }

    .form-select, .form-control{
      border-radius: 12px;
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

      <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>

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
    <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="員工資料表.php">員工資料表</a>
    <?php endif; ?>

    <a class="nav-link" href="班表管理.php">班表管理</a>
     <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
    <?php endif; ?>   
    <a class="nav-link" href="假別管理.php">假別管理</a>
    <a class="nav-link" href="打卡管理.php">打卡管理</a>

    <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="薪資管理.php">薪資管理</a>
    <?php endif; ?>

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
                  <input type="text" class="form-control" id="keywordInput" placeholder="輸入品名">
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