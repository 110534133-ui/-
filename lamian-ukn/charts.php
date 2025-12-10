<?php
// /lamian-ukn/charts.php - 營運圖表 (A級)
// 🔥 啟用登入保護
session_start();

// 1. 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 2. 檢查用戶等級 - 只有 A 級可以訪問此頁
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';

if ($userLevel === 'B') {
    // B 級用戶跳轉到 indexB.php
    header('Location: indexB.php');
    exit;
} elseif ($userLevel === 'C') {
    // C 級用戶跳轉到 indexC.php
    header('Location: indexC.php');
    exit;
}
// 如果是 A 級，繼續執行

// 3. 取得用戶資訊
$userName = $_SESSION['name'] ?? '用戶';
$userId = $_SESSION['uid'] ?? '';

// 統一路徑：後端 API 與資料 API
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';

$pageTitle = '營運圖表 - 員工管理系統'; // 頁面標題
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

<style>
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

  /* ====== Top navbar：跟首頁一樣深藍 ====== */
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

  .user-avatar {
    border: 2px solid rgba(255, 255, 255, 0.7);
  }

  .container-fluid {
    padding: 26px 28px;
  }

  /* ====== 搜尋列：套藍色系玻璃感 ====== */
  .search-container-wrapper {
    position: relative;
    width: 100%;
    max-width: 400px;
  }
  .search-container {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
    padding: 4px 4px 4px 18px;
    backdrop-filter: blur(14px);
    border: 1.5px solid rgba(255, 255, 255, 0.75);
  }
  .search-container:hover {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(255, 255, 255, 1);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.2);
    transform: translateY(-1px);
  }
  .search-container:focus-within {
    background: rgba(255, 255, 255, 0.26);
    border-color: rgba(255, 255, 255, 1);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.26);
  }
  .search-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 9px 10px;
    font-size: 14px;
    color: #f9fafb;
    font-weight: 500;
  }
  .search-input::placeholder {
    color: rgba(241, 245, 249, 0.8);
  }
  .search-btn {
    background: linear-gradient(135deg, #ffffff 0%, #e5edff 100%);
    border: none;
    border-radius: 999px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
  }
  .search-btn:hover {
    transform: scale(1.05) translateY(-1px);
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.3);
  }
  .search-btn:active {
    transform: scale(0.96);
  }
  .search-btn i {
    color: #2563eb;
    font-size: 15px;
  }

  /* ====== Sidebar：沿用首頁的淡藍漸層 ====== */
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

  .sb-sidenav .sb-sidenav-collapse-arrow i {
    color: #1e293b !important;
    opacity: 0.85 !important;
  }

  .sb-sidenav .nav-link:hover {
    border-color: rgba(255, 255, 255, 1);
    box-shadow: 0 14px 30px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
  }

  .sb-sidenav .nav-link:hover .sb-nav-link-icon,
  .sb-sidenav .nav-link:hover .sb-sidenav-collapse-arrow i {
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

  .sb-sidenav-menu-nested .nav-link {
    padding-left: 42px;
    font-size: .9rem;
    background: rgba(255, 255, 255, .9) !important;
    margin: 3px 14px;
    border-radius: 12px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
  }
  .sb-sidenav-menu-nested .nav-link:hover {
    background: #ffffff !important;
    transform: translateX(4px);
    padding-left: 48px;
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

  /* 修正箭頭/ICON 顏色 */
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
    box-shadow: 0 8px 18px rgba(15,23,42,0.08);
  }

  .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    color: #9ca3af;
  }

  .breadcrumb a {
    color: #2563eb;
  }

  .breadcrumb a:hover {
    text-decoration: underline !important;
  }

  .text-muted i {
    color: #2563eb;
  }

  /* ====== 卡片（查詢 / 圖表） ====== */
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

  .card-footer {
    background: rgba(248, 250, 252, 0.96);
    border-top: 1px solid rgba(226, 232, 240, 0.95);
    font-size: 0.8rem;
    color: var(--text-subtle);
  }

  /* ====== Tabs：改成藍色膠囊 ====== */
  .nav-tabs {
    border-bottom: 1px solid rgba(148, 163, 184, 0.5);
  }

  .nav-tabs .nav-link {
    border: none;
    border-radius: 999px;
    margin-right: 8px;
    padding: 0.6rem 1.1rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #6b7280;
    background: transparent;
  }

  .nav-tabs .nav-link i {
    font-size: 0.9rem;
  }

  .nav-tabs .nav-link:hover {
    background: rgba(255, 255, 255, 0.9);
    color: #1d4ed8;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
    transform: translateY(-1px);
  }

  .nav-tabs .nav-link.active,
  .nav-tabs .nav-item.show .nav-link {
    color: #1d4ed8;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
  }

  .tab-content {
    padding-top: 1.2rem;
  }

  /* ====== 表單元素 ====== */
  .form-label {
    font-size: 0.85rem;
    color: #475569;
  }

  .form-control,
  .form-select {
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.7);
    font-size: 0.9rem;
  }
  .form-control:focus,
  .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.3);
  }

  .btn-primary {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    border: none;
    border-radius: 999px;
    font-size: 0.9rem;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.45);
  }
  .btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, #4338ca);
    box-shadow: 0 14px 30px rgba(37, 99, 235, 0.55);
    transform: translateY(-1px);
  }

  footer {
    background: transparent !important;
    border-top: 1px solid rgba(148, 163, 184, 0.35);
    margin-top: 24px;
    padding-top: 14px;
    font-size: 0.8rem;
    color: var(--text-subtle);
  }

  /* ====== RWD ====== */
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
      font-size: 1.7rem;
    }
    .nav-tabs .nav-link {
      padding: 0.5rem 0.8rem;
      font-size: 0.82rem;
    }
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
          <li><a class="dropdown-item" href="login.php"><i class="fas fa-right-from-bracket me-2"></i>登出</a></li>
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
            <a class="nav-link active" href="index.php">
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

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory" aria-expanded="false">
                  庫存管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link" href="商品管理.php">商品管理</a>
                  </nav>
                </div>

                <a class="nav-link" href="日報表.php"><div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
                <a class="nav-link" href="薪資管理.php"><div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄</a>
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
            <a class="nav-link" href="charts.php">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
            </a>
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
          
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>營運圖表</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">營運圖表</li>
          </ol>

          <ul class="nav nav-tabs mb-4" id="chartTypeTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="line-chart-tab" data-bs-toggle="tab" data-bs-target="#chartTab-line" type="button" role="tab" aria-controls="chartTab-line" aria-selected="true">
                <i class="fas fa-chart-line me-2"></i>每日淨利趨勢
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="pie-chart-tab" data-bs-toggle="tab" data-bs-target="#chartTab-pie" type="button" role="tab" aria-controls="chartTab-pie" aria-selected="false">
                <i class="fas fa-chart-pie me-2"></i>月報表分析
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="sales-chart-tab" data-bs-toggle="tab" data-bs-target="#chartTab-sales" type="button" role="tab" aria-controls="chartTab-sales" aria-selected="false">
                <i class="fas fa-chart-bar me-2"></i>銷售分析
              </button>
            </li>
          </ul>

          <div class="tab-content" id="chartTypeTabContent">

            <div class="tab-pane fade show active" id="chartTab-line" role="tabpanel" aria-labelledby="line-chart-tab">
              
              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-filter me-1"></i> 選擇日期區間</div>
                <div class="card-body">
                  <form class="row g-3 align-items-end" id="dateRangeForm">
                    <div class="col-md-5">
                      <label for="startDate" class="form-label">開始日期</label>
                      <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-5">
                      <label for="endDate" class="form-label">結束日期</label>
                      <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-2">
                      <button type="submit" class="btn btn-primary w-100" id="btnQueryLineChart">查詢</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-area me-1"></i>營運數據 (收入 - 支出) 淨利</div>
                <div class="card-body">
                    <canvas id="myAreaChart" style="height: 300px; width: 100%;"></canvas>
                </div>
                <div class="card-footer small text-muted" id="chartUpdateStatus">
                    請選擇日期並按查詢
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="chartTab-pie" role="tabpanel" aria-labelledby="pie-chart-tab">
              
              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-filter me-1"></i> 選擇月份</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2" style="max-width: 400px;">
                        <select id="selectYear_Pie" class="form-select" style="width:120px;"></select>
                        <select id="selectMonth_Pie" class="form-select" style="width:100px;"></select>
                        <button id="btnApplyMonth_Pie" class="btn btn-primary">套用</button>
                    </div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-pie me-1"></i>月報表</div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 text-center">
                      <h6>每月總收入</h6>
                      <canvas id="incomePieChart" style="max-height: 300px;"></canvas>
                      <div id="noIncomeMsg" style="display:none;font-weight:bold;color:gray;padding-top:20px;">該月份無收入資料</div>
                    </div>
                    <div class="col-md-6 text-center">
                      <h6>成本支出圖</h6>
                      <canvas id="expensePieChart" style="max-height: 300px;"></canvas>
                      <div id="noExpenseMsg" style="display:none;font-weight:bold;color:gray;padding-top:20px;">該月份無成本資料</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <div class="tab-pane fade" id="chartTab-sales" role="tabpanel" aria-labelledby="sales-chart-tab">

              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-filter me-1"></i> 選擇月份</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2" style="max-width: 400px;">
                        <select id="selectYear_Sales" class="form-select" style="width:120px;"></select>
                        <select id="selectMonth_Sales" class="form-select" style="width:100px;"></select>
                        <button id="btnApplyMonth_Sales" class="btn btn-primary">套用</button>
                    </div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-bar me-1"></i>銷售品項分析 (範例)</div>
                <div class="card-body">
                  <canvas id="salesBarChart" style="height: 300px; width: 100%;"></canvas>
                  <div id="noSalesMsg" class="text-center text-muted" style="display:none; padding-top: 20px;">該月份無銷售資料</div>
                </div>
                <div class="card-footer small text-muted">
                    注意：此為範例圖表。請開發者建立新 API (get_sales_by_item.php) 並替換 JS 中的範例資料。
                </div>
              </div>

            </div>

          </div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">© 2025 拉麵店經營系統 - ukn</div>
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

  <script>
    // ---- 常數（PHP 變數注入） ----
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

    const $  = s => document.querySelector(s);
    const el = id => document.getElementById(id);

    function fmt(d){ return d.toISOString().slice(0,10); }

    // 今日日期 (for Top Nav)
    const dateEl = el('currentDate');
    if(dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('zh-TW', {year:'numeric',month:'long',day:'numeric',weekday:'long'});
    }

    // 側欄開關
    el('sidebarToggle')?.addEventListener('click', e => { 
        e.preventDefault(); 
        document.body.classList.toggle('sb-sidenav-toggled'); 
    });

    // 取得登入者資訊
    async function loadLoggedInUser(){
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      console.log('✅ 圖表頁 已登入:', userName);
      el('loggedAs').textContent = userName;
      const navName = el('navUserName');
      if(navName) navName.textContent = userName;
      
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

    // ========= 🔥 折線圖邏輯 (頁籤1 - 已修改) =========
    let myAreaChartInstance = null;
    
    // 🔥 修改：此函數現在會根據參數抓取特定區間的資料並繪製
    async function renderChart() {
        const canvas = el('myAreaChart');
        if(!canvas) return;

        const startDateVal = el('startDate').value;
        const endDateVal = el('endDate').value;

        if (!startDateVal || !endDateVal) {
            el('chartUpdateStatus').textContent = '請選擇開始與結束日期。';
            return;
        }

        if (new Date(startDateVal) > new Date(endDateVal)) {
            alert('開始日期不能晚於結束日期');
            return;
        }
        
        el('chartUpdateStatus').textContent = '載入中...';
        
        try {
            // 🔥 修改：直接呼叫 API 並傳入日期參數
            const r = await fetch(`${DATA_BASE}/get_daily_report.php?start_date=${startDateVal}&end_date=${endDateVal}`);
            if (!r.ok) throw new Error('API 讀取失敗');
            
            const j = await r.json();
            if (!j.success) throw new Error(j.message || 'API 回傳錯誤');

            const reportData = j.data || [];
            
            // 🔥 修改：API 已回傳補 0 的資料，直接使用
            const labels = reportData.map(row => `${row.report_date.slice(5)} (${row.weekday.replace('星期','')})`);
            const values = reportData.map(row => (row.total_income || 0) - (row.total_expense || 0));

            if(myAreaChartInstance) {
                myAreaChartInstance.destroy();
            }
            
            myAreaChartInstance = new Chart(canvas.getContext('2d'),{
              type:'line',
              data:{ 
                  labels: labels, 
                  datasets:[{
                    label:'(收入 - 支出) 淨利',
                    data: values,
                    borderColor:'rgba(78,115,223,1)',
                    backgroundColor:'rgba(78,115,223,.08)',
                    pointBackgroundColor:'rgba(78,115,223,1)',
                    pointRadius:4,
                    fill:true,
                    tension:.35
                  }]
              },
              options:{ 
                  responsive:true, 
                  maintainAspectRatio:false, 
                  plugins:{ 
                      legend:{display:true},
                      tooltip: {
                          callbacks: {
                              label: function(context) {
                                  return `${context.dataset.label}: ${context.raw.toLocaleString()} 元`;
                              }
                          }
                      }
                  }, 
                  scales:{ 
                      y:{
                          beginAtZero:true,
                          title:{display:true,text:'金額'},
                          ticks: {
                               callback: function(value) {
                                   return value.toLocaleString();
                               }
                          }
                      }, 
                      x:{
                          title:{display:true,text:'日期'}
                      } 
                  } 
              }
            });
            
            el('chartUpdateStatus').textContent = `已更新圖表：${startDateVal} 至 ${endDateVal}`;
        
        } catch(e) {
            console.error('抓取日報表失敗：', e);
            el('chartUpdateStatus').textContent = `錯誤：無法獲取資料。 ${e.message}`;
        }
    }

    // ========= 🔥 圓餅圖邏輯 (頁籤2) =========
    let incomeChartInstance = null;
    let expenseChartInstance = null;

    // 🔥 修改：可重複使用的年月產生器
    function buildYearMonthSelectors(yearSelectId, monthSelectId){
      const ySel = el(yearSelectId);
      const mSel = el(monthSelectId);
      if (!ySel || !mSel) return;
      
      const now  = new Date();
      const cy   = now.getFullYear();
      const cm   = now.getMonth()+1;

      for(let y = cy; y >= cy-2; y--){
        const opt = document.createElement('option');
        opt.value = y; opt.textContent = `${y}年`;
        if(y===cy) opt.selected = true;
        ySel.appendChild(opt);
      }
      for(let m=1;m<=12;m++){
        const opt = document.createElement('option');
        opt.value = String(m).padStart(2,'0'); opt.textContent = `${m}月`;
        if(m===cm) opt.selected = true;
        mSel.appendChild(opt);
      }
    }

    async function updateIncomeChart(){
      const year  = el('selectYear_Pie').value;
      const month = el('selectMonth_Pie').value;
      const canvas = el('incomePieChart');
      const msg    = el('noIncomeMsg');
      if (!canvas || !msg) return;

      try{
        const r = await fetch(`${DATA_BASE}/get_monthly_income.php?year=${year}&month=${month}`);
        const j = await r.json();
        const d = j?.data || {cash_income:0,linepay_income:0,uber_income:0};
        const total = (d.cash_income||0)+(d.linepay_income||0)+(d.uber_income||0);

        if(incomeChartInstance) incomeChartInstance.destroy();

        if(total <= 0){
          canvas.style.display='none'; msg.style.display='block';
          return;
        }
        canvas.style.display='block'; msg.style.display='none';

        incomeChartInstance = new Chart(canvas.getContext('2d'),{
          type:'pie',
          data:{ labels:['現金收入','LinePay','Uber實收'],
                 datasets:[{ data:[d.cash_income,d.linepay_income,d.uber_income],
                             backgroundColor:['#36A2EB','#FFCE56','#FF6384'] }] },
          options:{ responsive:true, plugins:{ legend:{position:'bottom'} } }
        });
      }catch(e){
        console.error('收入圓餅載入錯誤：', e);
        canvas.style.display='none'; msg.style.display='block'; msg.textContent='收入資料載入失敗';
      }
    }

    async function updateExpenseChart(){
      const year  = el('selectYear_Pie').value;
      const month = el('selectMonth_Pie').value;
      const canvas = el('expensePieChart');
      const msg    = el('noExpenseMsg');
      if (!canvas || !msg) return;

      try{
        const r = await fetch(`${DATA_BASE}/get_monthly_expense.php?year=${year}&month=${month}`);
        const j = await r.json();
        const arr = Array.isArray(j?.data) ? j.data : [];
        const total = arr.reduce((s,i)=>s + Number(i.amount||0), 0);

        if(expenseChartInstance) expenseChartInstance.destroy();

        if(total <= 0){
          canvas.style.display='none'; msg.style.display='block';
          return;
        }
        canvas.style.display='block'; msg.style.display='none';

        expenseChartInstance = new Chart(canvas.getContext('2d'),{
          type:'pie',
          data:{ labels: arr.map(i=>i.category),
                 datasets:[{ data: arr.map(i=>i.amount),
                             backgroundColor:['#FF6384','#36A2EB','#FFCE56','#9966FF','#4BC0C0','#FF9F40'] }] },
          options:{ responsive:true, plugins:{ legend:{position:'bottom'} } }
        });
      }catch(e){
        console.error('成本圓餅載入錯誤：', e);
        canvas.style.display='none'; msg.style.display='block'; msg.textContent='成本資料載入失敗';
      }
    }

    // ========= 🔥 銷售圖表邏輯 (頁籤3 - 範例) =========
    let salesBarChartInstance = null;
    
    async function updateSalesChart(){
      const year  = el('selectYear_Sales').value;
      const month = el('selectMonth_Sales').value;
      const canvas = el('salesBarChart');
      const msg    = el('noSalesMsg');
      if (!canvas || !msg) return;
    
      // 🔥 【後端注意】:
      // 這裡是一個範例。你需要建立一個新的 API (例如 get_sales_by_item.php)
      // 讓它接收 year 和 month, 並回傳像這樣的資料:
      // { success: true, data: [
      //   { item_name: "豚骨拉麵", total_sales: 15000 },
      //   { item_name: "醬油拉麵", total_sales: 12000 },
      //   { item_name: "叉燒飯", total_sales: 8000 },
      //   ...
      // ]}
      
      // --- 範例資料 (Placeholder) ---
      console.log(`[範例] 正在查詢 ${year}-${month} 的銷售資料...`);
      const placeholderData = {
          success: true,
          data: [
              { item_name: "豚骨拉麵", total_sales: 15000 + (Math.random() * 5000) },
              { item_name: "醬油拉麵", total_sales: 12000 + (Math.random() * 3000) },
              { item_name: "叉燒飯", total_sales: 8000 + (Math.random() * 2000) },
              { item_name: "煎餃", total_sales: 6500 + (Math.random() * 1000) },
              { item_name: "啤酒", total_sales: 4000 + (Math.random() * 1000) },
          ]
      };
      // --- 範例資料結束 ---

      // (未來請取消註解這段)
      /*
      try {
        const r = await fetch(`${DATA_BASE}/get_sales_by_item.php?year=${year}&month=${month}`);
        if (!r.ok) throw new Error('API 讀取失敗');
        const j = await r.json();
        if (!j.success) throw new Error(j.message || '讀取銷售資料失敗');
        const arr = Array.isArray(j?.data) ? j.data : [];
      */
      
      // (暫時使用範例資料)
      const arr = placeholderData.data.sort((a, b) => b.total_sales - a.total_sales); // 排序
      const total = arr.reduce((s,i)=>s + Number(i.total_sales||0), 0);

      if(salesBarChartInstance) salesBarChartInstance.destroy();

      if(total <= 0){
        canvas.style.display='none'; msg.style.display='block';
        return;
      }
      canvas.style.display='block'; msg.style.display='none';

      salesBarChartInstance = new Chart(canvas.getContext('2d'),{
        type:'bar', // 改成 bar
        data:{ 
             labels: arr.map(i=>i.item_name),
             datasets:[{ 
                label: '銷售金額',
                data: arr.map(i=>i.total_sales),
                backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#9966FF','#4BC0C0','#FF9F40'] 
             }] 
        },
        options:{ 
            responsive:true, 
            maintainAspectRatio: false,
            plugins:{ 
                legend:{ display: false } // 條形圖通常不用圖例
            }, 
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: '銷售金額 (元)' }
                },
                x: {
                    title: { display: true, text: '品項' }
                }
            }
        }
      });

      /*
      }catch(e){
        console.error('銷售圖表載入錯誤：', e);
        canvas.style.display='none'; msg.style.display='block'; msg.textContent='銷售資料載入失敗';
      }
      */
    }


    // ========= 🔥 頁面初始化 =========
    window.addEventListener('DOMContentLoaded', async ()=>{
      // 載入共用項目
      await loadLoggedInUser();
      
      // --- 折線圖 (頁籤1) 初始化 ---
      const today = new Date();
      const sevenDaysAgo = new Date();
      sevenDaysAgo.setDate(today.getDate() - 6);
      el('startDate').value = fmt(sevenDaysAgo);
      el('endDate').value = fmt(today);
      
      el('dateRangeForm').addEventListener('submit', (e) => {
          e.preventDefault();
          renderChart(); // 重新繪製折線圖
      });
      
      // 🔥 修改：第一次載入時，執行一次查詢
      await renderChart(); 
      
      // --- 圓餅圖 (頁籤2) 初始化 ---
      buildYearMonthSelectors('selectYear_Pie', 'selectMonth_Pie'); // 建立年份/月份下拉選單
      await updateIncomeChart();
      await updateExpenseChart();
      
      el('btnApplyMonth_Pie')?.addEventListener('click', async ()=>{
        await updateIncomeChart();
        await updateExpenseChart();
      });

      // --- 銷售圖 (頁籤3) 初始化 ---
      buildYearMonthSelectors('selectYear_Sales', 'selectMonth_Sales'); // 建立年份/月份下拉選單
      await updateSalesChart(); // 繪製範例圖表

      el('btnApplyMonth_Sales')?.addEventListener('click', async ()=>{
        await updateSalesChart();
      });

      // --- 🔥 新增：讀取 URL hash 並切換到指定頁籤 ---
      function activateTabFromHash() {
          const hash = window.location.hash; // e.g., "#chartTab-pie"
          if (!hash) return;

          const tabButton = document.querySelector(`button[data-bs-toggle="tab"][data-bs-target="${hash}"]`);
          if (tabButton) {
              const tab = new bootstrap.Tab(tabButton);
              tab.show();
              // 滾動到頁籤頂部，避免頁面跳轉
              el('chartTypeTab').scrollIntoView({ behavior: 'smooth' });
          }
      }
      activateTabFromHash(); // 頁面載入時執行
      
      // 監聽 tab 變化，更新 URL hash (讓用戶可以複製連結)
      const allTabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
      allTabs.forEach(tabEl => {
          tabEl.addEventListener('shown.bs.tab', event => {
              const newHash = event.target.getAttribute('data-bs-target');
              if (history.pushState) {
                  // 僅更新 hash，不觸發頁面滾動
                  history.pushState(null, null, newHash);
              } else {
                  // 備用方案
                  window.location.hash = newHash;
              }
          });
      });
      
    });
  </script>
</body>
</html>
