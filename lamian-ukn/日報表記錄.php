<?php
// 🔥 日報表記錄.php (頁面) - 只有 A 級（老闆）可以訪問

require_once __DIR__ . '/includes/auth_check.php';

// 只有 A 級（老闆）可以訪問
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}

// 取得用戶資訊
$user      = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '日報表歷史記錄 - 員工管理系統';

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

  <!-- 保留你原本用的 CSS 引用 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 整體風格：跟 員工資料表.php 一樣 ====== */
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

    /* ====== Top navbar：藍色漸層（和 index / 員工資料表 一樣） ====== */
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

    /* ====== Sidebar：與 員工資料表 相同 ====== */
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

    /* 表格樣式（與 員工資料表 一樣） */
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
    }
    .table tbody tr:hover {
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

    /* ====== KPI 四張統計卡 ====== */
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

    /* ===== 操作欄：編輯 / 刪除按鈕美化 ===== */
    #reportsTable .btn-warning,
    #reportsTable .btn-danger {
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

    #reportsTable .btn-warning {
      background: linear-gradient(135deg, #fff7d6, #ffe0a8);
      color: #854d0e;
    }

    #reportsTable .btn-danger {
      background: linear-gradient(135deg, #ffe4e6, #fecaca);
      color: #7f1d1d;
    }

    #reportsTable .btn-warning:hover,
    #reportsTable .btn-danger:hover {
      transform: translateY(-1px) scale(1.03);
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.22);
      filter: brightness(1.02);
    }

    #reportsTable .btn-warning i,
    #reportsTable .btn-danger i {
      font-size: 1rem;
    }

    /* ===== 表格底下拖曳滑桿 ===== */
    .scroll-slider {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 4px 8px 0;
      margin-top: 4px;
    }

    .scroll-slider input[type="range"] {
      flex: 1;
      -webkit-appearance: none;
      appearance: none;
      height: 4px;
      border-radius: 999px;
      background: linear-gradient(90deg, #9ca3af, #6b7280);
      outline: none;
    }

    .scroll-slider input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #f9fafb;
      border: 2px solid #4b5563;
      cursor: pointer;
      box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    .scroll-slider input[type="range"]::-moz-range-thumb {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #f9fafb;
      border: 2px solid #4b5563;
      cursor: pointer;
    }

    .scroll-btn {
      width: 26px;
      height: 26px;
      border-radius: 999px;
      border: none;
      background: rgba(156, 163, 175, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #374151;
      font-size: 12px;
      cursor: pointer;
    }
    .scroll-btn:hover {
      background: rgba(107, 114, 128, 0.9);
      color: #f9fafb;
    }
    .scroll-btn.disabled {
      opacity: .35;
      cursor: default;
    }

    /* ===== 日報表記錄：按鈕 chip 基礎樣式 ===== */
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
      font-size: 0.9rem;
      white-space: nowrap;
    }
    .btn-chip .ic {
      font-size: 15px;
      line-height: 1;
    }
    .btn-chip .tx {
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

    .btn-success-lite {
      background: linear-gradient(135deg, #34d399 0%, #22c55e 100%);
      color: #fff;
      border-color: rgba(34, 197, 94, .25);
    }

    @media (max-width: 576px) {
      .btn-chip { --h: 38px; --px: 12px; }
      .btn-chip .tx { display: none; }
    }

    /* ===== 功能方塊：四個功能用這個方框 ===== */
    .feature-grid {
      margin-bottom: 1.5rem;
    }
    .feature-card {
      border-radius: 20px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
      padding: 14px 16px;
      background: linear-gradient(135deg, #ffffff, #f3f4ff);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      text-align: left;
      cursor: pointer;
      text-decoration: none !important;
      color: #0f172a;
    }
    .feature-card:hover {
      box-shadow: var(--shadow-hover);
      transform: translateY(-2px);
    }
    .feature-main {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .feature-icon {
      width: 40px;
      height: 40px;
      border-radius: 16px;
      background: rgba(255,255,255,0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
      font-size: 1.1rem;
    }
    .feature-text-title {
      font-weight: 700;
      font-size: 0.98rem;
    }
    .feature-text-sub {
      font-size: 0.78rem;
      color: #6b7280;
    }
    .feature-tag {
      font-size: 0.72rem;
      padding: 4px 8px;
      border-radius: 999px;
      background: rgba(37, 99, 235, 0.08);
      color: #1d4ed8;
      font-weight: 600;
      white-space: nowrap;
    }

    .feature-1 { background: linear-gradient(135deg, #dbeafe, #eef2ff); }
    .feature-2 { background: linear-gradient(135deg, #dcfce7, #f0fdf4); }
    .feature-3 { background: linear-gradient(135deg, #fee2e2, #fef2f2); }
    .feature-4 { background: linear-gradient(135deg, #fef9c3, #fffbeb); }

    .feature-1 .feature-icon { color: #1d4ed8; }
    .feature-2 .feature-icon { color: #16a34a; }
    .feature-3 .feature-icon { color: #b91c1c; }
    .feature-4 .feature-icon { color: #ca8a04; }

    @media (max-width: 768px) {
      .feature-card {
        padding: 12px 14px;
      }
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- 上方 Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
      <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-1"
               src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>"
               width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
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
    <!-- Sidebar -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
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

                <a class="nav-link" href="日報表.php">
                  <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表
                </a>
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
            <a class="nav-link" href="charts.html">
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

    <!-- 主要內容 -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>日報表歷史記錄</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item"><a href="日報表.php" class="text-decoration-none">日報表</a></li>
            <li class="breadcrumb-item active">歷史記錄</li>
          </ol>

          <!-- 🔥 四個功能方塊 -->
          <div class="row feature-grid">
            <!-- 功能 1：填寫日報表 -->
            <div class="col-xl-3 col-md-6 mb-3">
              <a href="日報表.php" class="feature-card feature-1">
                <div class="feature-main">
                  <div class="feature-icon">
                    <i class="fas fa-pen-to-square"></i>
                  </div>
                  <div>
                    <div class="feature-text-title">填寫日報表</div>
                    <div class="feature-text-sub">新增今天的營收與支出紀錄</div>
                  </div>
                </div>
                <div class="feature-tag">填寫</div>
              </a>
            </div>

            <!-- 功能 2：查詢（沿用 filter_btn） -->
            <div class="col-xl-3 col-md-6 mb-3">
              <button type="button" class="feature-card feature-2" id="filter_btn">
                <div class="feature-main">
                  <div class="feature-icon">
                    <i class="fas fa-search"></i>
                  </div>
                  <div>
                    <div class="feature-text-title">查詢記錄</div>
                    <div class="feature-text-sub">依日期區間與填表人篩選</div>
                  </div>
                </div>
                <div class="feature-tag">查詢</div>
              </button>
            </div>

            <!-- 功能 3：清除條件（沿用 clear_btn） -->
            <div class="col-xl-3 col-md-6 mb-3">
              <button type="button" class="feature-card feature-3" id="clear_btn">
                <div class="feature-main">
                  <div class="feature-icon">
                    <i class="fas fa-eraser"></i>
                  </div>
                  <div>
                    <div class="feature-text-title">清除條件</div>
                    <div class="feature-text-sub">重置日期與填表人篩選</div>
                  </div>
                </div>
                <div class="feature-tag">重設</div>
              </button>
            </div>

            <!-- 功能 4：匯出 Excel（沿用 exportBtn+onclick） -->
            <div class="col-xl-3 col-md-6 mb-3">
              <button type="button" class="feature-card feature-4" id="exportBtn" onclick="exportToExcel()">
                <div class="feature-main">
                  <div class="feature-icon">
                    <i class="fas fa-file-excel"></i>
                  </div>
                  <div>
                    <div class="feature-text-title">匯出 Excel</div>
                    <div class="feature-text-sub">下載目前篩選結果</div>
                  </div>
                </div>
                <div class="feature-tag">匯出</div>
              </button>
            </div>
          </div>

          <!-- 篩選條件卡片（只保留欄位，不再放按鈕，避免重複 ID） -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">開始日期</label>
                  <input type="date" class="form-control" id="start_date">
                </div>
                <div class="col-md-3">
                  <label class="form-label">結束日期</label>
                  <input type="date" class="form-control" id="end_date">
                </div>
                <div class="col-md-3">
                  <label class="form-label">填表人</label>
                  <select class="form-control" id="filled_by_filter">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end justify-content-end">
                  <span class="text-muted small">※ 請使用上方四個方塊操作</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 四張統計卡：KPI -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-primary">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總記錄數</div>
                      <div class="h5" id="total_records">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-clipboard-list"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-success">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總收入</div>
                      <div class="h5" id="total_income_sum">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-dollar-sign"></i>
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
                      <div class="small text-muted">總支出</div>
                      <div class="h5" id="total_expense_sum">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-credit-card"></i>
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
                      <div class="small text-muted">淨收入</div>
                      <div class="h5" id="net_income">-</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-chart-line"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 列表區 -->
          <div class="card mb-4" id="reportList">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><i class="fas fa-table me-1"></i>日報表記錄列表</div>
            </div>
            <div class="card-body">
              <div class="table-responsive" id="reportsTableWrapper">
                <table class="table table-bordered" id="reportsTable" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>日期</th>
                      <th>填表人</th>
                      <th>現金收入</th>
                      <th>LinePay</th>
                      <th>Uber</th>
                      <th>其他收入</th>
                      <th>收入合計</th>
                      <th>食材成本</th>
                      <th>人事成本</th>
                      <th>租金</th>
                      <th>每日租金平攤</th>
                      <th>水電瓦斯費</th>
                      <th>外送平台費</th>
                      <th>雜項支出</th>
                      <th>支出合計</th>
                      <th class="sticky-right">操作</th>
                    </tr>
                  </thead>
                  <tbody id="reportTableBody">
                    <tr id="noDataRow" class="d-none">
                      <td colspan="16" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>
      </main>

      <!-- 編輯 Modal：保留你原本的內容 -->
      <div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title" id="editReportModalLabel"><i class="fas fa-edit me-2"></i>修改日報表</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
              <form id="editReportForm">
                <input type="hidden" id="editId" name="id">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3 sticky-row">
                      <label class="form-label">報表日期</label>
                      <input type="date" id="editDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">填表人</label>
                      <input type="text" id="editFilledBy" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">現金收入</label>
                      <input type="number" id="editCashIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">LinePay收入</label>
                      <input type="number" id="editLinepayIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Uber收入</label>
                      <input type="number" id="editUberIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">其他收入</label>
                      <input type="number" id="editOtherIncome" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="total_income" class="form-label">收入合計</label>
                      <input type="number" id="total_income" name="total_income" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">食材成本</label>
                      <input type="number" id="editExpenseFood" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">人事成本</label>
                      <input type="number" id="editExpenseSalary" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">租金</label>
                      <input type="number" id="editExpenseRent" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="editRentDaily" class="form-label">每日租金平攤</label>
                      <input type="number" id="editRentDaily" name="rent_daily" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">水電瓦斯費</label>
                      <input type="number" id="editExpenseUtilities" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">外送平台費</label>
                      <input type="number" id="editExpenseDelivery" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">雜項支出</label>
                      <input type="number" id="editExpenseMisc" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="total_expense" class="form-label">支出合計</label>
                      <input type="number" id="total_expense" name="total_expense" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
              <button type="submit" form="editReportForm" class="btn btn-primary">儲存修改</button>
            </div>
          </div>
        </div>
      </div>

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

  <script src="日報表紀錄.js"></script>

  <script>
    // ---- 常數（PHP 變數注入） ----
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

    const $  = s => document.querySelector(s);
    const el = id => document.getElementById(id);

    // 折起/展開側欄
    el('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    // 取得登入者資訊（已從 PHP Session 取得）
    async function loadLoggedInUser() {
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId   = <?php echo json_encode($userId,   JSON_UNESCAPED_UNICODE); ?>;

      console.log('✅ 日報表記錄 已登入:', userName, 'ID:', userId);

      // 設定用戶名 (Sidenav footer)
      const loggedAsEl = el('loggedAs');
      if (loggedAsEl) loggedAsEl.textContent = userName;

      // 設定用戶名 (Navbar)
      const navName = el('navUserName');
      if (navName) navName.textContent = userName;

      // 從 me.php 載入真實頭像
      try {
        const r = await fetch(API_BASE + '/me.php', { credentials: 'include' });
        if (r.ok) {
          const data = await r.json();
          if (data.avatar_url) {
            const avatarUrl = data.avatar_url + (data.avatar_url.includes('?') ? '&' : '?') + 'v=' + Date.now();
            const avatar = document.querySelector('.navbar .user-avatar');
            if (avatar) {
              avatar.src = avatarUrl;
              console.log('✅ 頭像已更新:', avatarUrl);
            }
          }
        }
      } catch (e) {
        console.warn('載入頭像失敗:', e);
      }
    }

    // 表格下方拖曳滑桿控制（目前沒有實際 slider DOM，保留函式不影響）
    function initTableScrollSlider() {
      const wrapper = document.getElementById('reportsTableWrapper');
      const range   = document.getElementById('tableScrollRange');
      const leftBtn  = document.querySelector('.scroll-btn[data-dir="left"]');
      const rightBtn = document.querySelector('.scroll-btn[data-dir="right"]');

      if (!wrapper || !range) return;

      const syncFromScroll = () => {
        const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
        if (maxScroll <= 0) {
          range.value = 0;
          range.disabled = true;
          leftBtn && leftBtn.classList.add('disabled');
          rightBtn && rightBtn.classList.add('disabled');
          return;
        }
        range.disabled = false;
        leftBtn && leftBtn.classList.remove('disabled');
        rightBtn && rightBtn.classList.remove('disabled');

        const ratio = wrapper.scrollLeft / maxScroll;
        range.value = Math.round(ratio * 100);
      };

      const syncFromRange = () => {
        const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
        const value = parseInt(range.value, 10) || 0;
        wrapper.scrollLeft = (value / 100) * maxScroll;
      };

      range.addEventListener('input', syncFromRange);
      wrapper.addEventListener('scroll', syncFromScroll);

      leftBtn && leftBtn.addEventListener('click', () => {
        const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
        wrapper.scrollLeft = Math.max(0, wrapper.scrollLeft - maxScroll * 0.15);
      });
      rightBtn && rightBtn.addEventListener('click', () => {
        const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
        wrapper.scrollLeft = Math.min(maxScroll, wrapper.scrollLeft + maxScroll * 0.15);
      });

      setTimeout(syncFromScroll, 0);
    }

    // 初始化
    window.addEventListener('DOMContentLoaded', async () => {
      await loadLoggedInUser();

      // 觸發 JS 檔案中的 loadReports()
      if (typeof loadReports === 'function') {
        loadReports();
      } else {
        console.error("loadReports() 函式不存在，請檢查 日報表紀錄.js");
      }

      initTableScrollSlider();
    });
  </script>
</body>
</html>
