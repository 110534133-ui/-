<?php
// 🔥 新頁面：商品管理.php (原 商品主檔管理.php)
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 權限：僅 A 級（老闆）可以訪問
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

// 🔥 修改：更新標題
$pageTitle = '商品管理 - 員工管理系統'; // 標題

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
    /* ====== 整體風格：跟 日報表記錄 / 薪資管理 / 庫存查詢 / 庫存調整 統一 ====== */
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
      padding: 26px 28px !important;
    }

    /* ====== Sidebar：藍紫玻璃感 ====== */
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

    /* 修正側欄箭頭 / icon 顏色 */
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
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
      backdrop-filter: blur(10px);
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
      overflow: hidden;
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
        padding: 20px 16px !important;
      }
    }

    @media (max-width: 768px) {
      .container-fluid {
        padding: 16px 12px !important;
      }
      h1 {
        font-size: 1.6rem;
      }
    }

    /* ====== 按鈕造型：主按鈕 / 外框按鈕（分類操作 / 商品操作 / modal） ====== */
    .btn-primary {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%) !important;
      color: #fff;
      border-color: rgba(59, 130, 246, .25) !important;
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }
    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
      filter: brightness(1.03);
      box-shadow: 0 8px 18px rgba(59, 130, 246, 0.5);
      transform: translateY(-1px);
      color: #fff;
    }

    .btn-outline-secondary,
    .btn-secondary.btn-outline-secondary {
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

    .btn-outline-primary {
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      border-color: rgba(59, 130, 246, .7);
      color: #1d4ed8;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .btn-outline-primary:hover {
      background-color: #eff6ff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .9);
      box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
      transform: translateY(-1px);
    }

    .btn-outline-danger {
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 2px 8px rgba(248, 113, 113, .25);
    }

    /* modal 裡的取消按鈕維持 bootstrap 預設，只是圓角統一 */
    .btn-secondary {
      border-radius: 999px;
    }

    /* ====== Navbar 搜尋列：沿用設計但配藍色 ====== */
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

    .form-select, .form-control {
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
                    <a class="nav-link" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link active" href="商品管理.php">商品管理</a>
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
        <div class="container-fluid px-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>商品管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">商品管理</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>
          
          <div class="row g-4">
            
            <div class="col-lg-5">
              <div class="card h-100">
                <div class="card-header fw-semibold"><i class="fas fa-tags me-2"></i>商品分類管理</div>
                <div class="card-body">
                  <h5 class="mb-3">新增/編輯分類</h5>
                  <form id="categoryForm">
                    <div class="input-group">
                      <input type="hidden" id="catId" value="">
                      <input type="text" id="catName" class="form-control" placeholder="輸入分類名稱 (例如: 飲料)" required>
                      <button class="btn btn-primary" type="submit" id="btnSaveCat">儲存</button>
                      <button class="btn btn-outline-secondary" type="button" id="btnClearCat">清除</button>
                    </div>
                  </form>
                </div>
                <div class="card-body border-top">
                  <h5 class="mb-3">現有分類</h5>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>分類名稱</th>
                          <th style="width: 100px;">操作</th>
                        </tr>
                      </thead>
                      <tbody id="catListBody">
                        <tr><td colspan="2" class="text-muted">載入中...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-7">
              <div class="card h-100">
                <div class="card-header fw-semibold">
                  <i class="fas fa-boxes me-2"></i>商品主檔管理
                </div>
                <div class_="card-body p-3">
                  <div class="d-flex justify-content-end p-3">
                    <button class="btn btn-primary" id="btnShowProductModal">
                      <i class="fas fa-plus me-1"></i> 新增商品
                    </button>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                      <thead class="table-light">
                        <tr>
                          <th>ID</th>
                          <th>品項名稱</th>
                          <th>類別</th>
                          <th>單位</th>
                          <th>操作</th>
                        </tr>
                      </thead>
                      <tbody id="prodListBody">
                        <tr><td colspan="5" class="text-muted">載入中...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div> </div> </main>

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

  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalLabel">新增/編輯商品</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="productForm">
          <div class="modal-body">
            <input type="hidden" id="prodId" value="">
            <div class="mb-3">
              <label for="prodName" class="form-label">品項名稱 <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="prodName" required>
            </div>
            <div class="mb-3">
              <label for="prodUnit" class="form-label">單位 <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="prodUnit" placeholder="例如: 包, 瓶, 公斤, 個" required>
            </div>
            <div class="mb-3">
              <label for="prodCatId" class="form-label">商品分類 <span class="text-danger">*</span></label>
              <select class="form-select" id="prodCatId" required>
                <option value="">請先建立分類</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            <button type="submit" class="btn btn-primary" id="btnSaveProd">儲存</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteModalLabel">確認刪除</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="deleteModalText">您確定要刪除嗎？此操作無法復原。</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="btnConfirmDelete">確認刪除</button>
        </div>
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    // 🔥 修改：API Endpoints 已合併
    const API_BASE       = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_PRODS_LIST = API_BASE + '/product_list.php';      // (GET) 讀取商品 (沿用)
    const API_CAT_API    = API_BASE + '/category_master_api.php'; // (GET ?action=list) / (POST action=save/delete)
    const API_PROD_API   = API_BASE + '/product_master_api.php';  // (POST action=save/delete)

    // Global State
    let allCategories = [];
    let allProducts = [];
    let productModal, deleteModal; // BS Modal 實體
    
    // 工具
    const qs = sel => document.querySelector(sel);
    const qsa = sel => document.querySelectorAll(sel);
    const escapeHtml = str => String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    const setBusy = (btn, busy) => { btn.disabled = busy; btn.innerHTML = busy ? '<span class="spinner-border spinner-border-sm"></span>' : btn.dataset.text || '儲存'; };
    const showOk = (msg) => { qs('#msgOk').textContent = msg; qs('#msgOk').classList.remove('d-none'); setTimeout(()=>qs('#msgOk').classList.add('d-none'), 2500); };
    const showErr = (msg) => { qs('#msgErr').textContent = msg; qs('#msgErr').classList.remove('d-none'); setTimeout(()=>qs('#msgErr').classList.add('d-none'), 5000); };

    // ===== 初始化 =====
    window.addEventListener('DOMContentLoaded', async () => {
      // 側欄/日期
      qs('#currentDate').textContent = new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
      qs('#sidebarToggle').addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });
      
      // 儲存按鈕原始文字
      qsa('button[type="submit"]').forEach(btn => btn.dataset.text = btn.textContent);

      // 初始化 Modals
      productModal = new bootstrap.Modal(qs('#productModal'));
      deleteModal = new bootstrap.Modal(qs('#deleteModal'));
      
      await loadLoggedInUser();
      
      await loadCategories(); 
      await loadProducts();
      
      bindEvents();
    });

    // ===== 事件綁定 =====
    function bindEvents() {
      // 分類表單
      qs('#categoryForm').addEventListener('submit', saveCategory);
      qs('#btnClearCat').addEventListener('click', resetCategoryForm);
      qs('#catListBody').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        if (btn.dataset.action === 'edit-cat') {
          qs('#catId').value = id;
          qs('#catName').value = name;
          qs('#catName').focus();
        } else if (btn.dataset.action === 'del-cat') {
          showDeleteModal('category', id, name);
        }
      });
      
      // 商品表單
      qs('#btnShowProductModal').addEventListener('click', () => showProductModal(null));
      qs('#productForm').addEventListener('submit', saveProduct);
      qs('#prodListBody').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        if (btn.dataset.action === 'edit-prod') {
          const prod = allProducts.find(p => p.id == id);
          showProductModal(prod);
        } else if (btn.dataset.action === 'del-prod') {
          showDeleteModal('product', id, name);
        }
      });
      
      // 刪除 Modal
      qs('#btnConfirmDelete').addEventListener('click', executeDelete);
    }
    
    // ===== 資料載入 (R) =====
    
    // 載入分類
    async function loadCategories() {
      const tbody = qs('#catListBody');
      try {
        // 🔥 修改：呼叫合併的 API (action=list)
        const res = await fetch(API_CAT_API + '?action=list', {credentials:'include'});
        if (!res.ok) throw new Error('API 錯誤: ' + res.status);
        const data = await res.json();
        
        allCategories = Array.isArray(data) ? data : (data.data || []);
        renderCategoryTable();
        populateCategoryDropdown();
        
      } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="2" class="text-danger">分類載入失敗 (API: ${API_CAT_API})</td></tr>`;
        showErr('無法載入商品分類: ' + e.message);
      }
    }
    
    // 載入商品 (使用您已有的 product_list.php)
    async function loadProducts() {
      const tbody = qs('#prodListBody');
      try {
        const res = await fetch(API_PRODS_LIST + '?t=' + Date.now(), {credentials:'include'}); // 加 cache buster
        if (!res.ok) throw new Error('API 錯誤: ' + res.status);
        const data = await res.json();

        allProducts = Array.isArray(data) ? data : (data.data || []);
        renderProductTable();

      } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger">商品載入失敗 (API: ${API_PRODS_LIST})</td></tr>`;
        showErr('無法載入商品清單: ' + e.message);
      }
    }

    // ===== 畫面渲染 =====
    
    // 渲染分類表格
    function renderCategoryTable() {
      const tbody = qs('#catListBody');
      if (allCategories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-muted">尚無分類</td></tr>`;
        return;
      }
      tbody.innerHTML = allCategories.map(cat => `
        <tr>
          <td class="align-middle">${escapeHtml(cat.name)} (ID: ${cat.id})</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-action="edit-cat" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-action="del-cat" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }
    
    // 渲染商品表格
    function renderProductTable() {
      const tbody = qs('#prodListBody');
      if (allProducts.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-muted">尚無商品</td></tr>`;
        return;
      }
      tbody.innerHTML = allProducts.map(prod => `
        <tr>
          <td>${escapeHtml(prod.id)}</td>
          <td>${escapeHtml(prod.name)}</td>
          <td>${escapeHtml(prod.category || 'N/A')}</td>
          <td>${escapeHtml(prod.unit)}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-action="edit-prod" data-id="${prod.id}">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-action="del-prod" data-id="${prod.id}" data-name="${escapeHtml(prod.name)}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }
    
    // 填充商品 Modal 中的分類下拉選單
    function populateCategoryDropdown() {
      const sel = qs('#prodCatId');
      if (allCategories.length === 0) {
        sel.innerHTML = `<option value="">請先建立分類</option>`;
        sel.disabled = true;
      } else {
        sel.disabled = false;
        sel.innerHTML = '<option value="">-- 請選擇分類 --</option>' +
          allCategories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
      }
    }
    
    // ===== 資料儲存 (C/U) =====

    // 重置分類表單
    function resetCategoryForm() {
      qs('#catId').value = '';
      qs('#catName').value = '';
      qs('#btnSaveCat').dataset.text = '儲存';
      qs('#btnSaveCat').innerHTML = '儲存';
    }

    // 儲存分類
    async function saveCategory(e) {
      e.preventDefault();
      const btn = qs('#btnSaveCat');
      const id = qs('#catId').value;
      const name = qs('#catName').value.trim();
      if (!name) return showErr('請輸入分類名稱');
      
      setBusy(btn, true);
      try {
        // 🔥 修改：呼叫合併的 API (action=save)
        const res = await fetch(API_CAT_API, {
          method: 'POST', credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action: 'save', id: id || null, name: name })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || '儲存失敗');
        
        showOk(id ? '分類已更新' : '分類已新增');
        resetCategoryForm();
        await loadCategories(); // 重新載入分類

      } catch(e) {
        showErr('分類儲存失敗: ' + e.message);
      } finally {
        setBusy(btn, false);
      }
    }

    // 顯示商品 Modal (新增 or 編輯)
    function showProductModal(prod) {
      qs('#productForm').reset();
      if (prod) {
        // 編輯
        qs('#productModalLabel').textContent = '編輯商品';
        qs('#prodId').value = prod.id;
        qs('#prodName').value = prod.name;
        qs('#prodUnit').value = prod.unit;
        qs('#prodCatId').value = prod.category_id || '';
      } else {
        // 新增
        qs('#productModalLabel').textContent = '新增商品';
        qs('#prodId').value = '';
      }
      productModal.show();
    }
    
    // 儲存商品
    async function saveProduct(e) {
      e.preventDefault();
      const btn = qs('#btnSaveProd');
      const body = {
        action: 'save', // 🔥 修改：加入 action
        id: qs('#prodId').value || null,
        name: qs('#prodName').value.trim(),
        unit: qs('#prodUnit').value.trim(),
        category_id: qs('#prodCatId').value
      };
      
      if (!body.name || !body.unit || !body.category_id) {
        return showErr('所有欄位皆為必填');
      }
      
      setBusy(btn, true);
      try {
        // 🔥 修改：呼叫合併的 API (action=save)
        const res = await fetch(API_PROD_API, {
          method: 'POST', credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || '儲存失敗');
        
        showOk(body.id ? '商品已更新' : '商品已新增');
        productModal.hide();
        await loadProducts(); // 重新載入商品

      } catch(e) {
        showErr('商品儲存失敗: ' + e.message);
      } finally {
        setBusy(btn, false);
      }
    }
    
    // ===== 資料刪除 (D) =====
    
    // 顯示刪除確認
    function showDeleteModal(type, id, name) {
      qs('#deleteModalText').innerHTML = `您確定要刪除 ${type==='category'?'分類':'商品'}：<br><strong>${escapeHtml(name)} (ID: ${id})</strong>？<br>此操作無法復原。`;
      qs('#btnConfirmDelete').dataset.type = type;
      qs('#btnConfirmDelete').dataset.id = id;
      deleteModal.show();
    }
    
    // 執行刪除
async function executeDelete() {
  const btn  = qs('#btnConfirmDelete');
  const type = btn.dataset.type;   // 'product' or 'category'
  const id   = btn.dataset.id;

  if (!id) {
    showErr('找不到要刪除的 ID（前端）');
    return;
  }

  const url = (type === 'category') ? API_CAT_API : API_PROD_API;
  const body = { action: 'delete', id: id };

  setBusy(btn, true);
  try {
    const res  = await fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(body)
    });
    let data = {};
    try { data = await res.json(); } catch (_) {}

    if (!res.ok || data.error) {
      throw new Error(data.error || data.detail || '刪除失敗');
    }

    showOk('已刪除');
    deleteModal.hide();

    if (type === 'category') {
      await loadCategories();
      await loadProducts();
    } else {
      await loadProducts();
    }
  } catch (e) {
    console.error('刪除失敗', e);
    showErr('刪除失敗: ' + e.message);
  } finally {
    setBusy(btn, false);
  }
}

    // ===== 載入登入者資訊 (同其他頁) =====
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        // 🔥 修改：更新 console
        console.log('✅ 商品管理 已登入:', userName, 'ID:', userId);
        try {
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
            const data = await r.json();
            if(data.avatar_url) {
                const avatarUrl = data.avatar_url + (data.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
                const avatar = document.querySelector('.navbar .user-avatar');
                if(avatar) avatar.src = avatarUrl;
            }
            }
        } catch(e) {
            console.warn('載入頭像失敗:', e);
        }
    }
  </script>

</body>
</html>