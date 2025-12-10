<?php
// 🔥 打卡管理.php (管理端頁面) - A/B 級可以使用

require_once __DIR__ . '/includes/auth_check.php';

// 檢查權限：A 級(老闆) 或 B 級(管理員)
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    header('Location: index.php');
    exit;
}

// 取得用戶資訊
$user      = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '打卡管理 - 員工管理系統';

// 統一路徑
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';
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
    /* ====== 整體風格：與 日報表記錄.php 一致 ====== */
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

    /* ====== Navbar：藍色漸層 ====== */
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
      border: 2px solid rgba(255,255,255,.5);
    }

    .container-fluid {
      padding: 26px 28px;
    }

    /* ====== Sidebar：與 日報表記錄 相同 ====== */
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

    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
      border: none;
      font-weight: 600;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
    }

    .table tbody td {
      text-align: center;
      vertical-align: middle;
      white-space: nowrap;
      border-color: rgba(226, 232, 240, 0.9);
    }

    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    /* ====== KPI 四張統計卡：沿用日報表樣式 ====== */
    .kpi-card {
      border-radius: 26px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
      overflow: hidden;
      position: relative;
    }
    .kpi-card .card-body {
      position: relative;
      z-index: 1;
    }
    .kpi-card::after {
      content: '';
      position: absolute;
      right: -80px;
      bottom: -80px;
      width: 260px;
      height: 180px;
      border-radius: 55% 0 0 0;
      background: radial-gradient(circle at 0 0, #e5e7eb, transparent 60%);
      opacity: 0.9;
    }
    .kpi-card .icon-pill {
      width: 46px;
      height: 46px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      box-shadow: 0 10px 25px rgba(15,23,42,0.16);
      background: rgba(255,255,255,0.9);
    }

    .kpi-primary {
      background: linear-gradient(135deg, #acc6f6ff, #818cf859) !important;
    }
    .kpi-success {
      background: linear-gradient(135deg, #b1f9caff, #22c55e4d) !important;
    }
    .kpi-warning {
      background: linear-gradient(135deg, #faebaeff, #facc154d) !important;
    }
    .kpi-info {
      background: linear-gradient(135deg, #bce4ffff, #38bdf84d) !important;
    }

    /* ===== 狀態徽章 ===== */
    .badge-status {
      border-radius: 999px;
      padding: .35rem .6rem;
      border: 1px solid transparent;
      font-size: 0.78rem;
    }
    .badge-normal {
      background: rgba(22, 163, 74, .12);
      border-color: rgba(22, 163, 74, .35);
      color: #166534;
    }
    .badge-ot {
      background: rgba(37, 99, 235, .12);
      border-color: rgba(37, 99, 235, .35);
      color: #1d4ed8;
    }
    .badge-missing {
      background: rgba(220, 38, 38, .12);
      border-color: rgba(220, 38, 38, .35);
      color: #b91c1c;
    }

    /* ===== Chip 風格按鈕（查詢 / 清除） ===== */
    .btn-chip {
      --h: 40px;
      --px: 14px;
      height: var(--h);
      padding: 0 var(--px);
      border-radius: 999px;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
      transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
    }
    .btn-chip .ic {
      font-size: 15px;
      line-height: 1;
    }
    .btn-chip:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
    }
    .btn-chip:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
    }

    .btn-primary-lite {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      color: #fff;
      border-color: rgba(59, 130, 246, .25);
    }
    .btn-primary-lite:hover {
      filter: brightness(1.03);
    }

    .btn-ghost {
      background: #ffffff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .35);
    }
    .btn-ghost:hover {
      background: #eff6ff;
    }

    /* 匯出 Excel 按鈕：放在表格右上角用 */
    .btn-excel {
      border-radius: 999px;
      border: 1px solid rgba(34, 197, 94, .35);
      background: linear-gradient(135deg, #bbf7d0 0%, #22c55e 100%);
      color: #065f46;
      font-weight: 600;
      padding: 6px 16px;
      font-size: 0.9rem;
      box-shadow: 0 8px 18px rgba(22, 163, 74, 0.35);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-excel i {
      font-size: 0.95rem;
    }
    .btn-excel:hover {
      filter: brightness(1.03);
      transform: translateY(-1px);
      box-shadow: 0 10px 24px rgba(22, 163, 74, 0.45);
    }

    @media (max-width: 576px) {
      .btn-chip .tx { display: none; }
      .btn-chip { --h: 38px; --px: 12px; }
    }

    .filter-row .form-label {
      font-weight: 600;
      color: #334155;
    }
    .filter-row .form-control {
      height: 44px;
      border-radius: 12px;
    }

    /* Modal 按鈕：同色系 */
    .btn-primary {
      background: linear-gradient(135deg, #4f8bff, #7b6dff) !important;
      border: none;
      border-radius: 999px;
      padding-inline: 20px;
      box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
    }
    .btn-primary:hover {
      filter: brightness(1.05);
      box-shadow: 0 12px 30px rgba(59, 130, 246, 0.45);
      transform: translateY(-1px);
    }

    /* ===== 操作欄：編輯 / 刪除按鈕美化（跟日報表記錄一樣） ===== */
    #attTable .btn-warning,
    #attTable .btn-danger {
      border: none;
      width: 40px;
      height: 40px;
      padding: 0;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
      transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
    }

    /* 編輯：柔和奶油黃橘 */
    #attTable .btn-warning {
      background: linear-gradient(135deg, #fff7d6, #ffe0a8);
      color: #854d0e;
    }

    /* 刪除：柔和霧粉紅 */
    #attTable .btn-danger {
      background: linear-gradient(135deg, #ffe4e6, #fecaca);
      color: #7f1d1d;
    }

    #attTable .btn-warning:hover,
    #attTable .btn-danger:hover {
      transform: translateY(-1px) scale(1.03);
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.22);
      filter: brightness(1.02);
    }

    #attTable .btn-warning i,
    #attTable .btn-danger i {
      font-size: 1rem;
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
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
      <i class="fas fa-bars"></i>
    </button>

    <!-- 這裡保留空表單（如果之後要加搜尋可以再塞） -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-1"
               src="https://i.pravatar.cc/40?u=<?= urlencode($userName); ?>"
               width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
          <span id="navUserName"><?= htmlspecialchars($userName); ?></span>
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
    <!-- 側欄 -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseLayouts" aria-expanded="false">
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

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseOperation" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                   data-bs-target="#operationCollapseInventory" aria-expanded="false">
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

                <a class="nav-link" href="日報表.php">
                  <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表
                </a>
              </nav>
            </div>
            
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseWebsite" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>會員管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="member-list.php">會員清單</a>
                <a class="nav-link" href="member-detail.php">詳細資料頁</a>
                <a class="nav-link" href="point-manage.php">點數管理</a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                   data-bs-target="#websiteCollapseMember" aria-expanded="false">
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
            <a class="nav-link" href="charts.html">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
            </a>
            <a class="nav-link" href="tables.html">
              <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables
            </a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          <span id="loggedAs"><?= htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>打卡管理</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">
              <a href="index.php" class="text-decoration-none">首頁</a>
            </li>
            <li class="breadcrumb-item active">打卡管理</li>
          </ol>

          <!-- 篩選列 -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-4 align-items-end">
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">開始日期</label>
                  <input type="date" class="form-control" id="start_date">
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">結束日期</label>
                  <input type="date" class="form-control" id="end_date">
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">員工</label>
                  <select class="form-control" id="employee_filter">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">狀態</label>
                  <select class="form-control" id="status_filter">
                    <option value="">全部</option>
                    <option value="正常">正常</option>
                    <option value="缺卡">缺卡</option>
                    <option value="加班">加班</option>
                  </select>
                </div>

                <div class="col-12 d-flex justify-content-end flex-wrap gap-3 pt-2">
                  <button class="btn btn-chip btn-primary-lite" id="btnSearch" type="button" title="查詢">
                    <i class="ic fas fa-search"></i><span class="tx">查詢</span>
                  </button>
                  <button class="btn btn-chip btn-ghost" id="btnClear" type="button" title="清除">
                    <i class="ic fas fa-eraser"></i><span class="tx">清除</span>
                  </button>
                  <!-- 匯出按鈕從這裡移走，改放到表格卡片右上角 -->
                </div>
              </div>
            </div>
          </div>

          <!-- 摘要：KPI 卡片 -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-success">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總工時（小時）</div>
                      <div class="h5" id="sum_hours">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-clock"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-primary">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">出勤筆數</div>
                      <div class="h5" id="sum_records">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-list-check"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-warning">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">缺卡筆數</div>
                      <div class="h5" id="sum_missing">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-triangle-exclamation"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-info">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">加班（小時）</div>
                      <div class="h5" id="sum_ot">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-bolt"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 表格 -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <i class="fas fa-table me-1"></i>打卡記錄列表
              </div>
              <!-- 匯出 Excel 按鈕：位置跟範例一樣在右上角 -->
              <button class="btn btn-excel" id="btnExport" type="button" title="匯出 Excel">
                <i class="fas fa-file-excel"></i>
                <span>匯出 Excel</span>
              </button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="attTable">
                  <thead>
                    <tr>
                      <th>日期</th>
                      <th>員工姓名</th>
                      <th>員工編號</th>
                      <th>上班時間</th>
                      <th>下班時間</th>
                      <th>地點</th>
                      <th>工作時數</th>
                      <th>狀態</th>
                      <th style="width:140px">操作</th>
                    </tr>
                  </thead>
                  <tbody id="attTableBody">
                    <tr>
                      <td colspan="9" class="text-center text-muted py-4">載入中…</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 訊息 -->
          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

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
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- Modal：新增/編輯打卡 -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="editForm">
          <div class="modal-header">
            <h5 class="modal-title">編輯打卡</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="f_id">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">日期</label>
                <input type="date" class="form-control" id="f_date" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">員工編號（員工基本資料.id）</label>
                <input type="text" class="form-control" id="f_emp_id" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">上班時間</label>
                <input type="time" class="form-control" id="f_clock_in" step="60">
              </div>
              <div class="col-md-6">
                <label class="form-label">下班時間</label>
                <input type="time" class="form-control" id="f_clock_out" step="60">
              </div>
              <div class="col-md-6">
                <label class="form-label">狀態</label>
                <select id="f_status" class="form-select">
                  <option value="">自動判斷</option>
                  <option value="正常">正常</option>
                  <option value="加班">加班</option>
                  <option value="缺卡">缺卡</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">備註</label>
                <input type="text" class="form-control" id="f_note" placeholder="可留白">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">取消</button>
            <button class="btn btn-primary" type="submit">
              <i class="fas fa-save me-1"></i>儲存
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 今日日期 / 側欄
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{
        year:'numeric',month:'long',day:'numeric',weekday:'long'
      });

    document.getElementById('sidebarToggle').addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    // === API 路徑 ===
    const API_BASE       = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_LIST       = API_BASE + '/clock_list.php';
    const API_DELETE     = API_BASE + '/clock_delete.php';
    const API_ADMIN_SAVE = API_BASE + '/clock_admin_save.php';

    // 小工具
    function parseHHMM(t){ if(!t) return null; const [h,m] = t.split(':').map(Number); if(Number.isNaN(h)||Number.isNaN(m)) return null; return h*60+m; }
    function minutesBetween(ci,co){ const a=parseHHMM(ci), b=parseHHMM(co); if(a==null||b==null) return null; let d=b-a; if(d<0) d+=1440; return d; }
    function hr2(mins){ return mins==null? '-' : (Math.round((mins/60)*100)/100).toFixed(2); }
    function inferStatus(ci,co,mins){ if(!ci||!co) return '缺卡'; if(mins!=null && mins>480) return '加班'; return '正常'; }
    function badge(status){
      if(status==='缺卡') return '<span class="badge-status badge-missing">缺卡</span>';
      if(status==='加班') return '<span class="badge-status badge-ot">加班</span>';
      return '<span class="badge-status badge-normal">正常</span>';
    }
    function showOk(m){
      const a=document.getElementById('msgOk');
      a.textContent=m; a.classList.remove('d-none');
      setTimeout(()=>a.classList.add('d-none'),2500);
    }
    function showErr(m){
      const a=document.getElementById('msgErr');
      a.textContent=m; a.classList.remove('d-none');
      setTimeout(()=>a.classList.add('d-none'),4000);
    }

    // 狀態
    let DATA = [];
    let timer = null;

    function setDefaultDates(){
      const end = new Date();
      const start = new Date(); start.setDate(end.getDate()-13);
      document.getElementById('end_date').value = end.toISOString().slice(0,10);
      document.getElementById('start_date').value = start.toISOString().slice(0,10);
    }

    function fillEmployeeFilter(rows){
      const sel = document.getElementById('employee_filter');
      const prev = sel.value;
      const ids = new Map();
      rows.forEach(r=>{
        const code = r.employee_id ?? r.emp_no ?? '';
        const name = r.emp_name ?? '';
        if(!code && !name) return;
        const label = code ? `${name}（${code}）` : name;
        ids.set(code || name, label);
      });
      sel.innerHTML = '<option value="">全部</option>' +
        Array.from(ids.entries()).map(([v,l])=>
          `<option value="${String(v).replace(/"/g,'&quot;')}">${l}</option>`
        ).join('');
      if (ids.has(prev)) sel.value = prev;
    }

    async function loadList(){
      const p = new URLSearchParams();
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;
      const emp = document.getElementById('employee_filter').value;
      const st  = document.getElementById('status_filter').value;

      if(s) p.set('start_date', s);
      if(e) p.set('end_date', e);
      if(emp) p.set('q', emp);

      try{
        const r = await fetch(API_LIST + (p.toString()?('?'+p.toString()):''), {
          headers:{'Accept':'application/json'}
        });
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const list = Array.isArray(rows)? rows : (rows.data||[]);
        DATA = list.filter(x=>{
          if(!st) return true;
          const mins = minutesBetween(x.clock_in, x.clock_out);
          const status = inferStatus(x.clock_in, x.clock_out, mins);
          return status === st;
        });
        fillEmployeeFilter(list);
        render();
      }catch(err){
        console.error(err);
        document.getElementById('attTableBody').innerHTML =
          `<tr><td colspan="9" class="text-center text-danger py-4">載入失敗：${String(err.message)}</td></tr>`;
      }
    }

    function render(){
      const tbody = document.getElementById('attTableBody');
      if(!DATA.length){
        tbody.innerHTML =
          `<tr><td colspan="9" class="text-center text-muted py-4">目前沒有資料</td></tr>`;
        setSummary(0,0,0,0);
        return;
      }
      let total=0, miss=0, otMin=0;
      tbody.innerHTML = DATA.map(row=>{
        const mins = minutesBetween(row.clock_in, row.clock_out);
        const st = inferStatus(row.clock_in, row.clock_out, mins);
        total += (mins||0);
        if(st==='缺卡') miss++;
        if(st==='加班' && mins) otMin += (mins-480);
        const hrs = hr2(mins);
        const empCode = row.employee_id ?? row.emp_no ?? '';
        const ops = `
          <button class="btn btn-warning me-1"
                  onclick='openEdit(${JSON.stringify(row).replace(/'/g,"&#39;")})'>
            <i class="fas fa-pen"></i>
          </button>
          <button class="btn btn-danger" onclick="delRow(${row.id})">
            <i class="fas fa-trash"></i>
          </button>`;
        return `
          <tr>
            <td>${row.date??''}</td>
            <td>${row.emp_name??''}</td>
            <td>${empCode}</td>
            <td>${row.clock_in??''}</td>
            <td>${row.clock_out??''}</td>
            <td>—</td>
            <td>${hrs}</td>
            <td>${badge(st)}</td>
            <td>${ops}</td>
          </tr>`;
      }).join('');
      setSummary(
        (Math.round((total/60)*100)/100).toFixed(2),
        DATA.length,
        miss,
        (Math.round((otMin/60)*100)/100).toFixed(2)
      );
    }

    function setSummary(h, cnt, miss, ot){
      document.getElementById('sum_hours').textContent   = h || '0.00';
      document.getElementById('sum_records').textContent = cnt || 0;
      document.getElementById('sum_missing').textContent = miss || 0;
      document.getElementById('sum_ot').textContent      = ot || '0.00';
    }

    async function delRow(id){
      if(!confirm('確定要刪除此筆資料？')) return;
      try{
        const r = await fetch(API_DELETE + '?id=' + encodeURIComponent(id));
        const resp = await r.json();
        if(!r.ok || resp.error){
          throw new Error(resp.error || ('HTTP '+r.status));
        }
        showOk('已刪除');
        await loadList();
      }catch(err){
        console.error(err);
        showErr('刪除失敗：'+err.message);
      }
    }

    // 匯出 Excel（實際格式是 CSV，Excel 可直接開啟）
    function exportExcel(){
  if(!DATA.length){
    alert('目前沒有可匯出的資料');
    return;
  }

  const headers = ['日期','員工姓名','員工編號','上班時間','下班時間','地點','工作時數','狀態'];
  const rows = DATA.map(r=>{
    const mins = minutesBetween(r.clock_in, r.clock_out);
    const st = inferStatus(r.clock_in, r.clock_out, mins);
    const empCode = r.employee_id ?? r.emp_no ?? r.user_id ?? '';
    return [
      r.date || '',
      r.emp_name || '',
      empCode,
      r.clock_in || '',
      r.clock_out || '',
      '—',
      hr2(mins),
      st
    ];
  });

  const csvBody = [headers, ...rows].map(cols =>
    cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')
  ).join('\r\n');

  // 🔥 重點：在最前面加上 UTF-8 BOM，讓 Excel 正確用 UTF-8 開啟
  const BOM = '\uFEFF';
  const blob = new Blob([BOM + csvBody], { type: 'text/csv;charset=utf-8;' });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = '打卡管理_' + (new Date().toISOString().slice(0,10)) + '.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}


    // === 編輯 Modal ===
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    document.getElementById('editForm').addEventListener('submit', saveForm);

    function openEdit(row){
      document.getElementById('f_id').value        = row.id || '';
      document.getElementById('f_date').value      = row.date || '';
      document.getElementById('f_emp_id').value    = (row.employee_id ?? row.user_id ?? '');
      document.getElementById('f_clock_in').value  = row.clock_in || '';
      document.getElementById('f_clock_out').value = row.clock_out || '';
      document.getElementById('f_status').value    = row.status || '';
      document.getElementById('f_note').value      = row.note || '';
      editModal.show();
    }

    async function saveForm(e){
      e.preventDefault();
      const payload = {
        id:        (document.getElementById('f_id').value||'') || undefined,
        date:      document.getElementById('f_date').value,
        user_id:   document.getElementById('f_emp_id').value.trim(),
        clock_in:  document.getElementById('f_clock_in').value || null,
        clock_out: document.getElementById('f_clock_out').value || null,
        status:    document.getElementById('f_status').value || '',
        note:      document.getElementById('f_note').value.trim()
      };
      if(!payload.date)    return showErr('請填 日期');
      if(!payload.user_id) return showErr('請填 員工編號');

      try{
        const r = await fetch(API_ADMIN_SAVE, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials:'include'
        });
        const resp = await r.json();
        if(!r.ok || resp.error){
          throw new Error(resp.detail || resp.error || ('HTTP '+r.status));
        }
        editModal.hide();
        showOk('已儲存');
        await loadList();
      }catch(err){
        console.error(err);
        showErr('儲存失敗：'+err.message);
      }
    }

    // 綁定事件 & 初始化
    window.addEventListener('DOMContentLoaded', async ()=>{
      setDefaultDates();
      await loadList();

      // 🔥 呼叫載入登入者資訊（包含頭像）
      if (typeof loadLoggedInUser === 'function') {
        loadLoggedInUser();
      }

      document.getElementById('btnSearch').addEventListener('click', loadList);
      document.getElementById('btnClear').addEventListener('click', async ()=>{
        setDefaultDates();
        document.getElementById('employee_filter').value = '';
        document.getElementById('status_filter').value = '';
        await loadList();
      });
      document.getElementById('btnExport').addEventListener('click', exportExcel);

      // 自動刷新（8 秒）
      timer = setInterval(loadList, 8000);
    });
  </script>

  <script>
    // 取得登入者資訊（已從 PHP Session 取得）
    async function loadLoggedInUser(){
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId   = <?php echo json_encode($userId,   JSON_UNESCAPED_UNICODE); ?>;
      
      console.log('✅ 打卡管理 已登入:', userName, 'ID:', userId);
      
      // 設定用戶名 (Sidenav footer)
      const loggedAsEl = document.getElementById('loggedAs');
      if (loggedAsEl) loggedAsEl.textContent = userName;

      // 設定用戶名 (Navbar)
      const navName = document.getElementById('navUserName');
      if (navName) navName.textContent = userName;
      
      // 🔥 從 me.php 載入真實頭像
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

  <script src="js/scripts.js"></script>
</body>
</html>
