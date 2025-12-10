<?php
// /lamian-ukn/index.php - A級老闆頁面
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
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>首頁 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
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

  /* ====== Top navbar ====== */
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

  /* ====== Sidebar 背景：淡藍漸層延伸 ====== */
  .sb-sidenav {
    background:
      radial-gradient(circle at 40% 0%, rgba(56, 189, 248, 0.38), transparent 65%),
      radial-gradient(circle at 80% 100%, rgba(147, 197, 253, 0.34), transparent 70%),
      linear-gradient(180deg, rgba(220, 235, 255, 0.92), rgba(185, 205, 255, 0.9));
    backdrop-filter: blur(22px);
    border-right: 1px solid rgba(255, 255, 255, 0.55);
  }

  /* ====== Sidebar 標題（CORE / PAGES / ADDONS） ====== */
  .sb-sidenav-menu-heading {
    color: #1e293b !important;
    opacity: 0.75;
    font-size: 0.78rem;
    letter-spacing: .18em;
    margin: 20px 0 8px 16px;
  }

  /* ====== Sidebar 按鈕（膠囊卡片，文字與框都更明顯） ====== */
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

  /* 左邊 icon 顏色加深 */
  .sb-sidenav .nav-link .sb-nav-link-icon {
    margin-right: 10px;
    color: #1e293b !important;
    opacity: 0.9 !important;
    font-size: 1.05rem;
  }

  /* 右邊箭頭顏色加深 */
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

  /* ====== Sidebar footer（Logged in as） ====== */
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

  /* ====== 系統通知 ====== */
  #alertBox {
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.96);
    padding: 18px 24px;
    border: 1.8px solid rgba(148, 163, 184, 0.55);
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.15);
  }

   /* ====== KPI cards（縮小高度版本） ====== */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px 24px;
    margin: 18px 0 22px;
  }

  .stats-card {
    position: relative;
    border-radius: 18px;
    padding: 12px 14px;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(226, 232, 240, 0.95);
    overflow: hidden;
    background: var(--card-bg);
  }

  .stats-card::after {
    content: "";
    position: absolute;
    right: -30px;
    bottom: -40px;
    width: 150px;
    height: 90px;
    border-radius: 999px;
    background: radial-gradient(circle at 20% 0, rgba(148, 163, 184, 0.18), transparent 65%);
    opacity: 0.8;
  }

  .stats-card .stats-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    font-size: 1.05rem;
  }

  .stats-number {
    font-size: 1.35rem;
    font-weight: 800;
    margin-bottom: 2px;
    line-height: 1.1;
  }

  .stats-label {
    font-size: 0.78rem;
    color: var(--text-subtle);
  }

  /* 每一張卡片加一點色彩背景 */
  .stats-card.primary {
    background:
      radial-gradient(circle at 0 0, rgba(96, 165, 250, 0.20), transparent 60%),
      var(--card-bg);
  }

  .stats-card.primary .stats-icon {
    background: rgba(96, 165, 250, 0.16);
    color: #2563eb;
  }

  .stats-card.secondary {
    background:
      radial-gradient(circle at 0 0, rgba(248, 113, 113, 0.22), transparent 60%),
      var(--card-bg);
  }

  .stats-card.secondary .stats-icon {
    background: rgba(248, 113, 113, 0.18);
    color: #db2777;
  }

  .stats-card.success {
    background:
      radial-gradient(circle at 0 0, rgba(52, 211, 153, 0.22), transparent 60%),
      var(--card-bg);
  }

  .stats-card.success .stats-icon {
    background: rgba(52, 211, 153, 0.20);
    color: #16a34a;
  }

  .stats-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
  }

  /* ====== 一般卡片 / 表格 ====== */
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

  footer {
    background: transparent;
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
      font-size: 1.6rem;
    }

    .stats-grid {
      margin: 20px 0;
      gap: 22px;
    }
  }
  /* ====== 修正側邊欄箭頭（SVG / ::after / background-image 全吃） ====== */
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
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.php">薪資管理</a>
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

                <a class="nav-link" href="activity_log.php"><div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>修改紀錄</a>
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
            <h1>營運儀表板</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active"><i class="fas fa-home me-2"></i>首頁</li>
          </ol>

          <div id="alertBox" class="alert d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-bell me-3"></i>
            <div>
              <strong>系統通知</strong><br>
              <span id="alertContent" class="loading-shimmer" style="display:inline-block;width:260px;height:1rem;border-radius:6px;"></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>

          <div class="stats-grid">
            <div class="stats-card primary">
              <div class="stats-icon"><i class="fas fa-dollar-sign"></i></div>
              <div class="stats-number" data-bind="revenue_today">--</div>
              <div class="stats-label">本日營收</div>
            </div>
            <div class="stats-card secondary">
              <div class="stats-icon"><i class="fas fa-yen-sign"></i></div>
              <div class="stats-number" data-bind="revenue_month">--</div>
              <div class="stats-label">本月營收</div>
            </div>
            <div class="stats-card success">
              <div class="stats-icon"><i class="fas fa-user-check"></i></div>
              <div class="stats-number" data-bind="present">--</div>
              <div class="stats-label">今天上班人數</div>
            </div>
          </div>

          <div class="row">
            <div class="col-xl-6 col-md-12 mb-4">
              <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div><i class="fas fa-chart-area me-1"></i>過去七日數據</div>
                  <a href="charts.php" class="text-decoration-none text-muted" title="查看詳細報表">
                    <i class="fas fa-external-link-alt fa-xs"></i>
                  </a>
                </div>
                <div class="card-body"><canvas id="myAreaChart" style="height:250px;"></canvas></div>
              </div>
            </div>

            <div class="col-xl-6 col-md-12 mb-4">
              <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-chart-pie me-1"></i>月報表
                    <a href="charts.php#chartTab-pie" class="ms-2" title="查看詳細報表">
                      <i class="fas fa-external-link-alt fa-xs text-muted"></i>
                    </a>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <select id="selectYear" class="form-select form-select-sm" style="width:100px;"></select>
                    <select id="selectMonth" class="form-select form-select-sm" style="width:90px;"></select>
                    <button id="btnApplyMonth" class="btn btn-sm btn-primary">套用</button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 text-center">
                      <h6>每月總收入</h6>
                      <canvas id="incomePieChart"></canvas>
                      <div id="noIncomeMsg" style="display:none;font-weight:bold;color:gray;padding-top:20px;">該月份無收入資料</div>
                    </div>
                    <div class="col-md-6 text-center">
                      <h6>成本支出圖</h6>
                      <canvas id="expensePieChart"></canvas>
                      <div id="noExpenseMsg" style="display:none;font-weight:bold;color:gray;padding-top:20px;">該月份無成本資料</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        
<div class="card mb-4">
            <div class="card-header">
              <i class="fas fa-calendar-alt me-2"></i>本週班表總覽
              <a href="班表管理.php" class="btn btn-sm btn-outline-primary float-end"><i class="fas fa-edit me-1"></i>編輯班表</a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-bordered text-center align-middle">
                  <thead id="weekScheduleHeader">
                    </thead>
                  <tbody id="currentScheduleTable">
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

    // 🔥 新增：從 班表管理.php 移植來的日期輔助函數
    function getMonday(d = new Date()) {
      const x = new Date(d);
      const dow = (x.getDay() + 6) % 7; // 星期一=0
      x.setHours(0, 0, 0, 0);
      x.setDate(x.getDate() - dow);
      return x;
    }
    function addDays(d, n) {
      const x = new Date(d);
      x.setDate(x.getDate() + n);
      return x;
    }
    function fmt(d) {
      return d.toISOString().slice(0, 10);
    }
    // 🔥 (結束) 新增輔助函數

    // 今日日期
    el('currentDate').textContent = new Date().toLocaleDateString('zh-TW', {year:'numeric',month:'long',day:'numeric',weekday:'long'});

    // 折起/展開側欄
    el('sidebarToggle')?.addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });

    // 取得登入者資訊（已從 PHP Session 取得）
    async function loadLoggedInUser(){
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
      
      console.log('✅ A級老闆已登入:', userName, 'ID:', userId);
      
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

    // 系統通知 placeholder
    function loadAlertsPlaceholder(){
      const span = el('alertContent');
      if(!span) return;
      setTimeout(()=>{
        span.classList.remove('loading-shimmer');
        span.textContent = '歡迎回來！今日尚無異常。';
      }, 700);
    }

    // 🔥 修改：統計卡 (呼叫新的 stats.php)
    async function loadMetrics(){
      try{
        // 1. 取得年月 (為了傳給 API 算月營收)
        const y = parseInt(el('selectYear').value, 10);
        const m = parseInt(el('selectMonth').value, 10);
        
        // 2. 🔥 一次呼叫新的 stats.php 取得所有資料
        const r_stats = await fetch(`${DATA_BASE}/stats.php?year=${y}&month=${m}&_=${new Date().getTime()}`, { credentials: 'include' });
        const j_stats = await r_stats.json();
        
        if (j_stats.success) {
          const data = j_stats.data;
          // 3. 填入 3 張卡片的資料
          document.querySelector('[data-bind="revenue_today"]').textContent = 'NT$ ' + (data.today_revenue || 0).toLocaleString();
          document.querySelector('[data-bind="revenue_month"]').textContent = 'NT$ ' + (data.month_revenue || 0).toLocaleString();
          document.querySelector('[data-bind="present"]').textContent   = data.attendance_count || 0;
        } else {
          throw new Error(j_stats.message || 'Stats API returned success=false');
        }
      } catch(e) {
        console.warn('統計卡資料載入失敗：', e);
        document.querySelector('[data-bind="revenue_today"]').textContent = '錯誤';
        document.querySelector('[data-bind="revenue_month"]').textContent = '錯誤';
        document.querySelector('[data-bind="present"]').textContent = '錯誤';
      }
    }

    // 七日（收入-支出）淨利折線圖
    async function loadLast7DaysChart(){
      const canvas = el('myAreaChart');
      if(!canvas) return;

      try{
        // 🔥 注意：這裡呼叫的是 get_daily_report.php (預設抓 7 天)
        const r = await fetch(`${DATA_BASE}/get_daily_report.php`);
        const j = await r.json();
        if(!j?.success) throw new Error(j?.message || 'get_daily_report failed');

        const rows = j.data || [];
        const labels = [];
        const values = [];
        const dayNames = ['日','一','二','三','四','五','六'];

        const today = new Date();
        const start = new Date(); start.setDate(today.getDate() - 6); 

        for(let d = new Date(start); d <= today; d.setDate(d.getDate()+1)){
          const y = d.getFullYear();
          const m = String(d.getMonth()+1).padStart(2,'0');
          const da= String(d.getDate()).padStart(2,'0');
          const ds= `${y}-${m}-${da}`;

          const w  = dayNames[d.getDay()];
          labels.push(`${parseInt(m)}/${parseInt(da)}(${w})`);

          const row = rows.find(r => (r.report_date||'').slice(0,10) === ds);
          const income  = row ? Number(row.total_income||0)  : 0;
          const expense = row ? Number(row.total_expense||0) : 0;
          values.push(income - expense);
        }

        if(window.__areaChart instanceof Chart) window.__areaChart.destroy();
        
        window.__areaChart = new Chart(canvas.getContext('2d'),{
          type:'line',
          data:{ labels, datasets:[{
            label:'(收入 - 支出) 淨利',
            data: values,
            borderColor:'rgba(78,115,223,1)',
            backgroundColor:'rgba(78,115,223,.08)',
            pointBackgroundColor:'rgba(78,115,223,1)',
            pointRadius:4,
            fill:true,
            tension:.35
          }]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:true}}, scales:{ y:{beginAtZero:true,title:{display:true,text:'金額'}}, x:{title:{display:true,text:'日期'}} } }
        });
      }catch(e){
        console.error('七日圖表載入失敗：', e);
      }
    }

    // 年月選單
    function buildYearMonthSelectors(){
      const ySel = el('selectYear');
      const mSel = el('selectMonth');
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

    // 月報：收入圓餅
    async function updateIncomeChart(){
      const year  = el('selectYear').value;
      const month = el('selectMonth').value;
      const canvas = el('incomePieChart');
      const msg    = el('noIncomeMsg');

      try{
        const r = await fetch(`${DATA_BASE}/get_monthly_income.php?year=${year}&month=${month}`);
        const j = await r.json();
        const d = j?.data || {cash_income:0,linepay_income:0,uber_income:0};
        
        const total = (d.cash_income||0)+(d.linepay_income||0)+(d.uber_income||0);

        if(window.__incomeChart instanceof Chart) window.__incomeChart.destroy();

        if(total <= 0){
          canvas.style.display='none'; msg.style.display='block';
          return;
        }
        canvas.style.display='block'; msg.style.display='none';

        window.__incomeChart = new Chart(canvas.getContext('2d'),{
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

    // 月報：成本圓餅
    async function updateExpenseChart(){
      const year  = el('selectYear').value;
      const month = el('selectMonth').value;
      const canvas = el('expensePieChart');
      const msg    = el('noExpenseMsg');

      try{
        const r = await fetch(`${DATA_BASE}/get_monthly_expense.php?year=${year}&month=${month}`);
        const j = await r.json();
        const arr = Array.isArray(j?.data) ? j.data : [];
        const total = arr.reduce((s,i)=>s + Number(i.amount||0), 0);

        if(window.__expenseChart instanceof Chart) window.__expenseChart.destroy();

        if(total <= 0){
          canvas.style.display='none'; msg.style.display='block';
          return;
        }
        canvas.style.display='block'; msg.style.display='none';

        window.__expenseChart = new Chart(canvas.getContext('2d'),{
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

    // ========= 🔥 班表邏輯重構 START 🔥 =========

    // 1. 新增：載入員工清單的函數
    let employeeList = []; // 儲存員工清單 [ {id: 1, name: "王小明"}, ... ]

    async function loadEmployeeList() {
      try {
        // 假設 api_get_employees.php 和 index.php 在同一層
        const r = await fetch('api_get_employees.php', { credentials: 'include' });
        if (!r.ok) throw new Error('無法抓取員工清單');
        const result = await r.json();
        
        if (result && result.success) {
          employeeList = result.data; 
          console.log('✅ 員工清單載入成功:', employeeList.length, '人');
        } else {
          console.error('載入員工清單失敗', result.message);
        }
      } catch (e) {
        console.warn('載入員工清單API失敗:', e);
      }
    }

    // 2. 重構：載入本週班表 (改成以「員工」為列)
    async function loadWeekSchedule() {
      const tbody = el('currentScheduleTable');
      const thead = el('weekScheduleHeader');
      if (!tbody || !thead) return;

      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">載入班表中...</td></tr>`;

      const today = new Date();
      const monday = getMonday(today);
      const todayDateString = fmt(today);
      
      // 1. 繪製表頭 (第一欄改成「員工姓名」)
      const weekday = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
      const headerCells = [];
      for (let i = 0; i < 7; i++) {
        const d = addDays(monday, i);
        headerCells.push(`<th>${weekday[i]}<br><small>${d.getMonth() + 1}/${d.getDate()}</small></th>`);
      }
      thead.innerHTML = `<tr><th style="width:120px">員工姓名</th>${headerCells.join('')}</tr>`;

      // 2. 抓取「時段為主」的原始班表資料
      try {
        const cacheBuster = `&_=${new Date().getTime()}`;
        const r = await fetch(`確認班表.php?date=${todayDateString}${cacheBuster}`, { credentials: 'include' });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        
        const timeSlotData = await r.json(); // 格式: [ {timeSlot: "上午", days: [...]}, ... ]

        if (!Array.isArray(timeSlotData) || timeSlotData.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">本週尚未排班</td></tr>`;
          return;
        }

        // 3. 🔥 資料重組 (Pivot)：
        
        // 建立一個以「全體員工」為基礎的 Map
        const scheduleMap = new Map();
        employeeList.forEach(emp => {
          // 每個員工都有7個空位 (週一到週日)
          scheduleMap.set(emp.name, Array(7).fill('')); 
        });

        // 追蹤所有在班表上找到的人 (包含可能不在員工列表的)
        const allNamesFound = new Set();

        // 處理 API 抓回來的資料
        timeSlotData.forEach(slotRow => { // 跑 "上午", "晚上"
          const period = slotRow.timeSlot; // e.g., "上午"
          
          slotRow.days.forEach((dayContent, dayIndex) => { // 跑 週一, 週二...
            // dayContent 可能是 "王小明 (10:30-18:30)<br>aaa (10:00-18:00)"
            if (!dayContent || dayContent === '-') return;

            const shifts = dayContent.split('<br>').filter(Boolean); // e.g., ["王小明 (10:30-18:30)", "aaa (10:00-18:00)"]
            
            shifts.forEach(shiftStr => {
              const match = shiftStr.match(/^(.*?)\s*\((.*?)\)$/); // 解析 "姓名 (時間)"
              if (!match) return; // 格式不符

              const name = match[1].trim();
              const time = match[2].trim();

              // 如果這個人在 scheduleMap 裡不存在 (e.g. 離職員工但還在班表上)
              if (!scheduleMap.has(name)) {
                if (!allNamesFound.has(name)) { // 避免重複警告
                   console.warn(`"${name}" 在班表中有資料，但不在 api_get_employees.php 清單中。`);
                }
                scheduleMap.set(name, Array(7).fill('')); //動態新增
              }
              allNamesFound.add(name); // 記錄所有有班的人

              // 取得該員工的班表陣列
              const employeeShifts = scheduleMap.get(name);
              const existingShift = employeeShifts[dayIndex];
              
              // 組合新時段字串 (e.g. "上午 10:00-18:00")
              const newShiftEntry = `${period} ${time}`; 
              
              if (existingShift) {
                // 如果格子裡已經有資料 (e.g. 上午)，就用 <br> 疊加上去 (e.g. 晚上)
                employeeShifts[dayIndex] = `${existingShift}<br>${newShiftEntry}`;
              } else {
                employeeShifts[dayIndex] = newShiftEntry;
              }
            });
          });
        });

        // 4. 繪製「員工為主」的表格
        const rowHtmls = [];
        
        // 取得所有要顯示的員工姓名並排序
        const sortedNames = Array.from(scheduleMap.keys()).sort(); 

        sortedNames.forEach(name => {
            const shifts = scheduleMap.get(name); // 取得 [週一, 週二, ...] 的陣列
            
            // 檢查該員工本週是否有班 (如果不想顯示空班的員工，可以取消註解這段)
            // const hasShifts = shifts.some(s => s);
            // if (!hasShifts) {
            //     return; 
            // }

            const cellsHtml = shifts.map(shiftContent => {
              // white-space:pre-line 讓 <br> 可以換行
              return `<td style="white-space:pre-line">${shiftContent || '-'}</td>`;
            }).join('');

            rowHtmls.push(`<tr><th class="bg-light">${name}</th>${cellsHtml}</tr>`);
        });

        if (rowHtmls.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">本週無人排班</td></tr>`;
        } else {
            tbody.innerHTML = rowHtmls.join('');
        }

      } catch (e) {
        console.error('載入首頁班表錯誤:', e);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">班表載入失敗: ${e.message}</td></tr>`;
      }
    }
    // ========= 🔥 班表邏輯重構 END 🔥 =========


    // 初始化
    window.addEventListener('DOMContentLoaded', async ()=>{
      buildYearMonthSelectors();
      loadAlertsPlaceholder();
      await loadLoggedInUser();
      await loadLast7DaysChart();
      await updateIncomeChart();
      await updateExpenseChart();
      
      // 🔥 修改：loadMetrics() 現在會載入全部卡片
      await loadMetrics(); 
      
      // ========= 🔥 載入順序調整 START 🔥 =========
      // 必須先載入員工清單，才能繪製「以員工為主」的班表
      await loadEmployeeList();
      await loadWeekSchedule(); 
      // ========= 🔥 載入順序調整 END 🔥 =========

      // 切換年月時更新圖與卡片
      el('btnApplyMonth')?.addEventListener('click', async ()=>{
        await updateIncomeChart();
        await updateExpenseChart();
        // 🔥 修改：切換月份時，也要更新「統計卡」
        await loadMetrics();
      });
    });
  </script>
</body>
</html>