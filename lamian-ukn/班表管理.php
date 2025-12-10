<?php
// /lamian-ukn/班表管理.php
// ✅ 正確使用 auth_check.php 的版本

// 🔥 重要:先啟動 session
session_start();

// 1. 載入權限檢查 (確保路徑正確)
$auth_file = __DIR__ . '/includes/auth_check.php';

if (file_exists($auth_file)) {
    require_once $auth_file;
    
    // 2. 檢查權限:A 級(老闆)或 B 級(管理員)
    check_user_level(['A', 'B'], true);
    
    // 3. 取得用戶資訊
    $user = get_user_info();
    $userName  = $user['name'];
    $userId    = $user['uid'];
    $userLevel = $user['level'];
    
    error_log("✅ 班表管理 - 使用 auth_check.php - 用戶: {$userName} ({$userId}), Level: {$userLevel}");
    
} else {
    // 🔥 如果找不到 auth_check.php,使用備用方案
    error_log("⚠️ auth_check.php 不存在於: {$auth_file}");
    
    // 檢查登入
    if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
    
    // 檢查權限
    $userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? $_SESSION['role'] ?? 'C';
    
    if (!in_array($userLevel, ['A', 'B'])) {
        // 不是 A 或 B,導向對應首頁
        if ($userLevel === 'C') {
            header('Location: indexC.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
    
    // 取得用戶資訊
    $userName = $_SESSION['name'] ?? '未知用戶';
    $userId   = $_SESSION['uid'] ?? '';
    
    error_log("⚠️ 班表管理 - 使用備用方案 - 用戶: {$userName} ({$userId}), Level: {$userLevel}");
}

// 4. 統一路徑
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';

$pageTitle = '班表管理 - 員工管理系統';
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
  <!-- ✅ 下載班表圖片要用 -->
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>

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

  /* =========================
   員工可排時段 (日檢視) 專用
   ========================= */

  .gantt-card {
    overflow: visible;
  }

  .gantt-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 6px;
    -webkit-overflow-scrolling: touch;
  }

  .gantt-header {
    background: #e5edff;
    border-bottom: 1px solid #93a3c7;
  }

  .gantt-header .name {
    padding: 12px 14px;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    border-right: 1px solid rgba(148,163,184,.6);
  }

  .gantt-row .name {
    padding: 18px 16px;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    background: #f8fbff;
    border-right: 1px solid rgba(148,163,184,.35);
    white-space: nowrap;
  }

  .gantt-row {
    border-top: 1px solid rgba(148,163,184,.3);
  }

  .gantt-header .scale div {
    border-left: 1px solid rgba(148,163,184,.7);
    font-size: 0.9rem;
    color: #111827;
  }

  .gantt-row:nth-child(odd) .track {
    background: linear-gradient(180deg, #ffffff, #f3f6ff);
  }
  .gantt-row:nth-child(even) .track {
    background: linear-gradient(180deg, #f9fafb, #edf2ff);
  }

  .gantt-grid div {
    border-left: 1px dashed rgba(148,163,184,.6);
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

  /* ====== 搜尋框 ====== */
  .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
  .search-container {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50px;
    padding: 4px 4px 4px 20px;
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
  }
  .search-container:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
  }
  .search-container:focus-within {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.3);
  }
  .search-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 10px 12px;
    font-size: 14px;
    color: #f9fafb;
    font-weight: 500;
  }
  .search-input::placeholder {
    color: rgba(241, 245, 249, 0.9);
    font-weight: 400;
  }
  .search-btn {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(226, 232, 255, 0.95));
    border: none;
    border-radius: 40px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    position: relative;
    overflow: hidden;
  }
  .search-btn i {
    color: #2563eb;
    font-size: 16px;
    position: relative;
    z-index: 1;
  }

  /* ====== Sidebar 背景 ====== */
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

  .user-avatar {
    border: 2px solid rgba(255, 255, 255, .5);
  }

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

  /* ====== 一般卡片 / 表格 ====== */
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

  footer {
    background: transparent;
    border-top: 1px solid rgba(148, 163, 184, 0.35);
    margin-top: 24px;
    padding-top: 14px;
    font-size: 0.8rem;
    color: var(--text-subtle);
  }

  .form-control, .form-select {
    border-radius: 12px;
    border-color: rgba(148, 163, 184, 0.6);
  }

  .form-control:focus, .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.25);
  }

  /* ====== 按鈕 ====== */
  .btn-primary {
    background: linear-gradient(135deg, #4f8bff, #7b6dff);
    border: none;
    border-radius: 999px;
    padding: 0.45rem 1.3rem;
    font-weight: 600;
    box-shadow: 0 10px 22px rgba(59, 130, 246, 0.45);
  }
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 30px rgba(59, 130, 246, 0.55);
    background: linear-gradient(135deg, #436ff0, #6a5bff);
  }
  .btn-outline-secondary {
    border-radius: 999px;
    padding: 0.4rem 1.2rem;
    border-color: rgba(148, 163, 184, 0.8);
    color: #4b5563;
    background: rgba(255,255,255,0.9);
  }
  .btn-outline-secondary:hover {
    background: #e5e7eb;
    color: #111827;
  }

  /* ====== Gantt 區域 ====== */
  .gantt-toolbar {
    gap: .5rem;
    flex-wrap: wrap;
  }
  .gantt-toolbar .btn-day {
    min-width: 96px;
    border-radius: 999px;
  }
  .gantt-legend {
    font-size: .9rem;
    opacity: .75;
  }

  .gantt {
    display:inline-block;
    min-width:1600px;
    background:#fff;
    border:1px solid rgba(148,163,184,.4);
    border-radius:18px;
    box-shadow: var(--shadow-soft);
    overflow:hidden;
  }

  .gantt-header,
  .gantt-row {
    display:grid;
    grid-template-columns: 140px 1fr;
  }
  .gantt-header {
    background:#f1f5f9;
    border-bottom:1px solid rgba(148,163,184,.4);
  }
  .gantt-header .times {
    position:relative;
    padding:10px 8px;
    border-left:1px solid rgba(148,163,184,.4);
  }
  .gantt-header .scale {
    display:grid;
    grid-template-columns: repeat(15, 1fr);
    font-size:.85rem;
    text-align:center;
  }
  .gantt-header .scale div {
    padding:2px 0;
  }
  .gantt-row + .gantt-row {
    border-top:1px solid rgba(148,163,184,.35);
  }
  .gantt-row .track {
    position:relative;
    padding:12px 8px;
    border-left:1px solid rgba(148,163,184,.35);
    background:linear-gradient(180deg,#ffffff,#f8fafc);
  }
  .gantt-grid {
    position:absolute;
    inset:12px 8px;
    display:grid;
    grid-template-columns: repeat(15, 1fr);
  }
  .gantt-grid div {
    border-left:1px dashed rgba(148,163,184,.3);
  }
  .gantt-bar {
    position:absolute;
    height:28px;
    border-radius:9px;
    background: linear-gradient(135deg, #4f8bff, #7b6dff);
    display:flex;
    align-items:center;
    padding:0 10px;
    box-shadow: 0 6px 16px rgba(37, 99, 235, .35);
    font-size:.9rem;
    color:#f9fafb;
    white-space:nowrap;
    cursor:pointer;
    user-select:none;
  }
  .gantt-bar:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 22px rgba(37, 99, 235, .5);
    z-index: 5;
  }

  .pulse-highlight {
    animation: pulseBg 1.4s ease-out 1;
  }
  @keyframes pulseBg {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, .7); }
    100% { box-shadow: 0 0 0 18px rgba(59, 130, 246, 0); }
  }

  .chip-highlight {
    animation: highlight-chip 1.5s ease;
  }

  @keyframes highlight-chip {
    0% {
      background-color: #bfdbfe !important;
      transform: scale(1.15);
      box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
    }
    50% {
      background-color: #bfdbfe !important;
      transform: scale(1.08);
    }
    100% {
      background-color: #2563eb !important;
      transform: scale(1);
      box-shadow: none;
    }
  }

  .cell-flash {
    animation: flash-cell 1.5s ease;
  }
  @keyframes flash-cell {
    0% {
      background-color: #dbeafe;
      box-shadow: inset 0 0 15px rgba(59, 130, 246, 0.5);
    }
    100% {
      background-color: transparent;
      box-shadow: none;
    }
  }

  .assign-chip {
    font-size: 0.9rem;
    padding: 6px 6px 6px 10px;
    border-radius: 999px;
    background-color: #2563eb;
    color: #f9fafb;
    display:inline-flex;
    align-items:center;
    gap:4px;
  }
  .assign-chip .chip-btn {
    padding: 0;
    margin: 0;
    width: 18px;
    height: 18px;
    font-size: 11px;
    line-height: 18px;
    border-radius: 50%;
    opacity: 0.8;
    border: none;
  }
  .assign-chip .chip-btn:hover {
    opacity: 1;
  }

  .table {
    border-radius: 18px;
    overflow: hidden;
    background:#ffffff;
  }
  .table thead th {
    background: linear-gradient(135deg, #e5edff, #dbeafe);
    border-bottom: 1px solid rgba(148,163,184,.5);
    color: #1e293b;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    padding: 12px 10px;
  }
  .table tbody td, .table tbody th {
    padding: 12px 10px;
    vertical-align: middle;
    border-color: rgba(148,163,184,.25);
    text-align: center;
  }
  .table tbody tr:hover {
    background: rgba(219, 234, 254, 0.6);
  }

  /* ===== 本週班表預覽（當前週班表）===== */
  .weekly-preview-table {
    border-radius: 18px;
    overflow: hidden;
    background: #ffffff;
  }
  .weekly-preview-table thead th {
    background: linear-gradient(135deg, #4f8bff, #7b6dff);
    border-bottom: none;
    color: #ffffff;
    font-weight: 600;
    padding: 12px 10px;
    font-size: 0.9rem;
    text-align: center;
  }
  .weekly-preview-table thead .preview-name-header {
    text-align: left;
    padding-left: 18px;
  }
  .weekly-preview-table .preview-name-cell {
    background: #f8fbff;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: left;
    padding: 10px 14px;   /* 🔹縮小高度 */
    min-width: 110px;     /* 🔹縮窄寬度 */
    max-width: 140px;     /* 🔹避免字太長撐開 */
    border-right: 1px solid rgba(148,163,184,.35);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* 🔹字太長用 ... */
  }

  .weekly-preview-table tbody td {
    text-align: center;
    vertical-align: middle;
    padding: 14px 10px;
    border-color: rgba(148,163,184,.25);
    color: #4b5563;
  }
  .weekly-preview-table tbody tr:nth-child(odd) td {
    background: #ffffff;
  }
  .weekly-preview-table tbody tr:nth-child(even) td {
    background: #f9fafb;
  }
  .weekly-preview-table tbody tr:hover td,
  .weekly-preview-table tbody tr:hover .preview-name-cell {
    background: rgba(219,234,254,.7);
  }

  /* 🔹 班次 badge：沿用新增班表的樣式 */
  .badge-shift {
    display: inline-block;
    min-width: 70px;
    padding: 4px 10px;
    border-radius: 18px;
    background: rgba(59,130,246,0.12);
    border: 1px solid rgba(59,130,246,0.35);
    color: #1d4ed8;
    font-size: 0.8rem;
    margin-bottom: 2px;
    white-space: nowrap;
  }

  .badge-off {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    padding: 4px 12px;
    border-radius: 16px;
    background: rgba(148,163,184,0.22);
    border: 1px dashed rgba(148,163,184,0.9);
    color: #374151;
    font-size: 0.8rem;
    white-space: nowrap;
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
          <!-- 預設先用 pravatar，稍後 JS 會用 API 頭像覆蓋 -->
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

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <!-- 標題與日期 -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>班表管理</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <!-- 麵包屑 -->
          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="<?php echo ($userLevel === 'A') ? 'index.php' : 'indexB.php'; ?>" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">班表管理</li>
          </ol>

          <!-- 系統狀態 (測試用) -->
          <div class="alert alert-info mb-4">
            <strong>系統狀態:</strong> 
            用戶: <?php echo htmlspecialchars($userName); ?> | 
            ID: <?php echo htmlspecialchars($userId); ?> | 
            等級: <?php echo htmlspecialchars($userLevel); ?>級
          </div>

          <!-- 本週班表預覽（跟新增班表的當前週班表一樣） -->
          <div class="card mb-4" id="scheduleViewCard">
            <div class="card-header">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center me-auto">
                  <i class="fas fa-calendar-alt me-2"></i>
                  <span>當前週班表(唯讀)</span>
                </div>

                <!-- 週切換按鈕：上週 / 本週 / 下週 -->
                <div class="btn-group me-2" role="group" aria-label="week switch">
                  <button class="btn btn-outline-secondary" id="btnPrevWeek">
                    <i class="fas fa-chevron-left me-1"></i>上週
                  </button>
                  <button class="btn btn-outline-secondary" id="btnNextWeek">
                    本週
                  </button>
                  <button class="btn btn-outline-secondary" id="btnNextNextWeek">
                    下週<i class="fas fa-chevron-right ms-1"></i>
                  </button>
                </div>

                <!-- 右邊：週期 + 下載班表圖片 -->
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted">週期:</span>
                  <strong id="weekRangeText">--</strong>
                  <button class="btn btn-primary ms-2" id="btnDownloadPng">
                    <i class="fas fa-image me-2"></i>下載班表圖片
                  </button>
                </div>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table weekly-preview-table text-center align-middle">
                  <thead>
                    <tr id="previewHeaderRow">
                      <th class="preview-name-header">員工</th>
                      <!-- JS 動態加 7 天 -->
                    </tr>
                  </thead>
                  <tbody id="previewBody"></tbody>
                </table>
              </div>
              <div class="small text-muted" id="previewHint">※ 本區塊僅供瀏覽,不可編輯。</div>
            </div>
          </div>

          <!-- 員工可排時段總覽 (日檢視) -->
          <div class="card mb-4 gantt-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>
                <i class="fas fa-users-clock me-2"></i>員工可排時段 
                <span class="badge bg-primary ms-2" style="font-size: 0.75rem;">點擊藍色條 → 快速添加</span>
              </span>
              <div class="gantt-toolbar d-flex" id="ganttDayButtons">
                <!-- 動態生成 7 個按鈕 -->
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-3 py-2">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>使用提示:</strong>直接點擊藍色時間條,該員工會立即出現在下方編輯班表中!
              </div>

              <div class="gantt-scroll">
                <div id="ganttChart" class="gantt">
                  <!-- 動態生成 Gantt 圖 -->
                </div>
              </div>
            </div>
          </div>

          <!-- 編輯班表 (週檢視) -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span><i class="fas fa-calendar-check me-2"></i>編輯班表</span>
              <div>
                <button class="btn btn-outline-secondary btn-sm me-2" id="btnClearDraft">
                  <i class="fas fa-eraser me-1"></i>清空草稿
                </button>
                <button class="btn btn-primary btn-sm" id="btnSaveDraft">
                  <i class="fas fa-save me-1"></i>儲存班表
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr id="editorHeaderRow">
                      <th style="min-width:100px">時段</th>
                      <!-- 動態生成 7 個日期欄 -->
                    </tr>
                  </thead>
                  <tbody id="editorBody">
                    <!-- 動態生成 -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <!-- Footer -->
      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Xxing 0625</div>
            <div>
              <a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- 新增/修改人員 Modal -->
  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="assignModalTitle">新增人員</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="assignForm">
          <div class="modal-body">
            <input type="hidden" id="assignDs" />
            <input type="hidden" id="assignPeriod" />
            <input type="hidden" id="assignOriginalName" />
            
            <div class="mb-3">
              <label class="form-label">姓名</label>
              <select class="form-select" id="assignNameSelect" required>
                <option value="">請選擇員工</option>
              </select>
            </div>
            
            <div class="row">
              <div class="col-6">
                <label class="form-label">開始時間</label>
                <input type="time" class="form-control" id="assignStart" required />
              </div>
              <div class="col-6">
                <label class="form-label">結束時間</label>
                <input type="time" class="form-control" id="assignEnd" required />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
            <button type="submit" class="btn btn-primary">確定</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>
  
  <script>
    // 🔥 注入 PHP 變數
    const PHP_USER_NAME   = <?php echo json_encode($userName,   JSON_UNESCAPED_UNICODE); ?>;
    const PHP_USER_ID     = <?php echo json_encode($userId,     JSON_UNESCAPED_UNICODE); ?>;
    const PHP_USER_LEVEL  = <?php echo json_encode($userLevel,  JSON_UNESCAPED_UNICODE); ?>;
    const API_BASE        = <?php echo json_encode($API_BASE_URL,  JSON_UNESCAPED_SLASHES); ?>;
    const DATA_BASE       = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

    console.log('✅ 班表管理頁面載入:', PHP_USER_NAME, 'ID:', PHP_USER_ID, 'Level:', PHP_USER_LEVEL);

    // ===== 載入登入者資訊 & 頭像 =====
    async function loadLoggedInUser() {
      const userName = PHP_USER_NAME;
      const userId   = PHP_USER_ID;

      console.log('✅ 班表管理 - 已登入:', userName, 'ID:', userId);

      // 更新右下角 Logged in as
      const loggedAs = document.getElementById('loggedAs');
      if (loggedAs) loggedAs.textContent = userName;

      // 更新右上角名字
      const navName = document.getElementById('navUserName');
      if (navName) navName.textContent = userName;

      // 從 /api/me.php 抓真正的頭像
      try {
        const r = await fetch(API_BASE + '/me.php', { credentials: 'include' });
        if (r.ok) {
          const data = await r.json();
          if (data.avatar_url) {
            const avatarUrl = data.avatar_url + (data.avatar_url.includes('?') ? '&' : '?') + 'v=' + Date.now();
            const avatarImg = document.querySelector('.navbar .user-avatar');
            if (avatarImg) {
              avatarImg.src = avatarUrl;
              console.log('✅ 班表管理頭像已更新:', avatarUrl);
            }
          }
        }
      } catch (e) {
        console.warn('班表管理載入頭像失敗:', e);
      }
    }

    // ===== 基本設定 =====
    const PERIODS = ['上午', '晚上'];
    let availabilityDetail = {};
    let scheduleAssignedMap = {};
    let draftAssignedMap = {};
    let allEmployees = [];

    // ===== 日期工具函數 =====
    function getMonday(d = new Date()) {
      const date = new Date(d);
      const day = (date.getDay() + 6) % 7;
      date.setDate(date.getDate() - day);
      date.setHours(0, 0, 0, 0);
      return date;
    }

    function fmt(d) {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function addDays(d, n) {
      const x = new Date(d);
      x.setDate(x.getDate() + n);
      return x;
    }

    function daysOfWeek(monday) {
      return Array.from({ length: 7 }, (_, i) => addDays(monday, i));
    }

    // ✅ 週期顯示：與新增班表一致，YYYY/MM/DD - YYYY/MM/DD
    function renderWeekHeader(monday) {
      const sun = addDays(monday, 6);
      const s = `${monday.getFullYear()}/${String(monday.getMonth()+1).padStart(2,'0')}/${String(monday.getDate()).padStart(2,'0')}`;
      const e = `${sun.getFullYear()}/${String(sun.getMonth()+1).padStart(2,'0')}/${String(sun.getDate()).padStart(2,'0')}`;
      
      const el1 = document.getElementById('weekRangeText');
      if (el1) el1.textContent = `${s} - ${e}`;

      const el2 = document.getElementById('weekRangeTextTop');
      if (el2) el2.textContent = `${s} - ${e}`;
    }

    // ✅ 下載當前週班表圖片
    async function downloadSchedulePng(){
      const el = document.getElementById('scheduleViewCard');
      if (!el) return;

      if (typeof html2canvas === 'undefined') {
        alert('html2canvas 未載入,無法下載圖片');
        return;
      }

      try {
        const canvas = await html2canvas(el, {
          scale: 2,
          backgroundColor: '#ffffff'
        });
        const url = canvas.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = url;
        a.download = `班表_${document.getElementById('weekRangeText').textContent}.png`;
        a.click();
      } catch (err) {
        console.error('下載圖片失敗:', err);
        alert('下載圖片失敗: ' + err.message);
      }
    }

    // ===== API 請求 =====
    async function fetchJSON(url, options = {}) {
      try {
        const res = await fetch(url, {
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          ...options
        });
        
        if (!res.ok) {
          const text = await res.text();
          console.error('API 錯誤:', res.status, text);
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        
        return await res.json();
      } catch (err) {
        console.error('[API ERROR]', url, err);
        alert('API 錯誤: ' + err.message);
        return null;
      }
    }

    // ===== 載入全體員工清單 =====
    async function loadEmployeeList() {
      try {
        const res = await fetch('班表管理API.php?action=employees', {
          credentials: 'include'
        });
        
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.employees) {
            allEmployees = data.employees;
            console.log('✅ 載入員工清單:', allEmployees.length, '人');
            
            const select = document.getElementById('assignNameSelect');
            select.innerHTML = '<option value="">請選擇員工</option>' +
              allEmployees.map(emp => 
                `<option value="${emp.name}">${emp.name} (${emp.position || ''})</option>`
              ).join('');
          }
        }
      } catch (e) {
        console.error('載入員工清單失敗:', e);
      }
    }

    // ===== 載入員工可排時段 =====
    async function loadAvailability(monday) {
      const data = await fetchJSON(`班表.php?start=${fmt(monday)}`);
      if (!data || !data.rows) {
        console.warn('無可排時段資料');
        availabilityDetail = {};
        return;
      }
      
      availabilityDetail = {};
      data.rows.forEach(emp => {
        const name = emp.name;
        (emp.shifts || []).forEach((dayShifts, i) => {
          const date = fmt(addDays(monday, i));
          dayShifts.forEach(shift => {
            const time = shift.split('~')[0] || '00:00';
            const hour = parseInt(time.split(':')[0]);
            const period = (hour >= 6 && hour < 14) ? '上午' : '晚上';
            
            const key = `${date}::${period}`;
            if (!availabilityDetail[key]) {
              availabilityDetail[key] = [];
            }
            
            availabilityDetail[key].push({
              name: name,
              time: shift
            });
          });
        });
      });
      
      console.log('✅ 載入可排時段:', Object.keys(availabilityDetail).length, '個時段');
    }

    // ===== 載入已確認班表 =====
    async function loadSchedulePreview(monday) {
      const url = `確認班表.php?date=${fmt(monday)}&t=${Date.now()}`;
      const data = await fetchJSON(url);
      
      if (!Array.isArray(data)) {
        console.warn('無已確認班表資料');
        scheduleAssignedMap = {};
        return;
      }
      
      scheduleAssignedMap = {};
      daysOfWeek(monday).forEach((d, i) => {
        const ds = fmt(d);
        scheduleAssignedMap[ds] = { '上午': [], '晚上': [] };
        
        data.forEach(row => {
          if (row.timeSlot === '上午' || row.timeSlot === '晚上') {
            const dayContent = row.days[i];
            if (dayContent && dayContent !== '-') {
              const matches = dayContent.match(/(.+?)\s+\((.+?)\)/g);
              if (matches) {
                matches.forEach(match => {
                  const m = match.match(/(.+?)\s+\((.+?)\)/);
                  if (m) {
                    scheduleAssignedMap[ds][row.timeSlot].push({
                      name: m[1].trim(),
                      time: m[2].trim(),
                      note: ''
                    });
                  }
                });
              }
            }
          }
        });
      });
      
      console.log('✅ 載入已確認班表');
    }

    // ===== 最上方「本週班表預覽」（左邊是員工姓名） =====
    function renderPreviewHeader(monday) {
      const headRow = document.getElementById('previewHeaderRow');
      if (!headRow) return;

      headRow.querySelectorAll('th:nth-child(n+2)').forEach(th => th.remove());

      const labels = ['一', '二', '三', '四', '五', '六', '日'];
      daysOfWeek(monday).forEach((d, i) => {
        const th = document.createElement('th');
        th.innerHTML = `${d.getMonth() + 1}/${d.getDate()}<br>星期${labels[i]}`;
        headRow.appendChild(th);
      });
    }

    function renderPreviewBody(monday) {
      const tbody = document.getElementById('previewBody');
      const hint  = document.getElementById('previewHint');
      if (!tbody) return;

      tbody.innerHTML = '';

      let names = [];

      // 🔹優先用「所有員工名單」來當左側列表
      if (Array.isArray(allEmployees) && allEmployees.length > 0) {
        names = allEmployees
          .map(emp => emp.name)
          .filter(Boolean);
      } else {
        // 🔹如果沒載到員工名單，就從已儲存班表抓名字
        const nameSet = new Set();
        Object.keys(scheduleAssignedMap).forEach(ds => {
          PERIODS.forEach(period => {
            (scheduleAssignedMap[ds]?.[period] || []).forEach(x => {
              if (x.name) nameSet.add(x.name);
            });
          });
        });
        names = Array.from(nameSet);
      }

      // 排序一下名字，讓列表穩定
      names.sort();

      if (names.length === 0) {
        if (hint) {
          hint.textContent = '尚未有已儲存班表，請在下方「編輯班表」設定後按下「儲存班表」。';
        }
        return;
      }

      names.forEach(name => {
        const tr = document.createElement('tr');

        // 左邊：員工姓名
        const th = document.createElement('th');
        th.className = 'preview-name-cell';
        th.textContent = name;
        tr.appendChild(th);

        // 右邊：本週 7 天
        daysOfWeek(monday).forEach(d => {
          const ds = fmt(d);
          const td = document.createElement('td');
          td.style.whiteSpace = 'nowrap';
          td.style.verticalAlign = 'top';

          const lines = [];

          // 查這個員工在這一天上午/晚上有沒有被排到班
          PERIODS.forEach(period => {
            const list = (scheduleAssignedMap[ds]?.[period]) || [];
            list
              .filter(x => x.name === name)
              .forEach(x => {
                const label = x.time ? `${period} ${x.time}` : period;
                lines.push(label);
              });
          });

          // 🔹改成「新增班表」的 badge 樣式
          if (lines.length > 0) {
            td.innerHTML = lines
              .map(label => `<span class="badge-shift">${label}</span>`)
              .join('<br>');
          } else {
            td.innerHTML = '<span class="badge-off">休</span>';
          }

          tr.appendChild(td);
        });

        tbody.appendChild(tr);
      });

      if (hint) {
        hint.textContent = '左側為員工姓名，右側為本週已儲存的班表，若需調整請在下方「編輯班表」修改後再儲存。';
      }
    }

    function renderPreview(monday) {
      renderPreviewHeader(monday);
      renderPreviewBody(monday);
    }

    // ===== Gantt 圖 (日檢視) =====
    let currentGanttDate = null;

    function renderDayButtons(monday) {
      const container = document.getElementById('ganttDayButtons');
      container.innerHTML = '';
      
      const labels = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
      daysOfWeek(monday).forEach((d, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-primary btn-day';
        btn.textContent = `${labels[i]} ${d.getMonth() + 1}/${d.getDate()}`;
        btn.dataset.date = fmt(d);
        
        btn.addEventListener('click', () => {
          container.querySelectorAll('.btn-day').forEach(b => 
            b.classList.remove('active', 'btn-primary')
          );
          btn.classList.add('active', 'btn-primary');
          btn.classList.remove('btn-outline-primary');
          
          currentGanttDate = fmt(d);
          renderGanttChart(currentGanttDate);
        });
        
        container.appendChild(btn);
        
        if (i === 0) {
          currentGanttDate = fmt(d);
          btn.click();
        }
      });
    }

    function renderGanttChart(dateStr) {
      console.log('🎨 渲染甘特圖:', dateStr);
      
      const container = document.getElementById('ganttChart');
      
      const morningKey = `${dateStr}::上午`;
      const eveningKey = `${dateStr}::晚上`;
      
      console.log('🔍 查找資料:', { morningKey, eveningKey });
      console.log('📦 可用資料:', Object.keys(availabilityDetail));
      
      const morningList = availabilityDetail[morningKey] || [];
      const eveningList = availabilityDetail[eveningKey] || [];
      
      console.log('📊 上午人員:', morningList.length, '人');
      console.log('📊 晚上人員:', eveningList.length, '人');
      
      const allNames = new Set();
      [...morningList, ...eveningList].forEach(x => allNames.add(x.name));
      
      if (allNames.size === 0) {
        container.innerHTML = '<div class="text-center text-muted p-4">此日沒有可排人員</div>';
        return;
      }
      
      let html = `
        <div class="gantt-header">
          <div class="name">員工</div>
          <div class="times">
            <div class="scale">
              ${Array.from({ length: 15 }, (_, i) => {
                const hour = 6 + i;
                return `<div>${hour}:00</div>`;
              }).join('')}
            </div>
          </div>
        </div>
      `;
      
      Array.from(allNames).sort().forEach(name => {
        const morning = morningList.find(x => x.name === name);
        const evening = eveningList.find(x => x.name === name);
        
        html += `
          <div class="gantt-row">
            <div class="name">${name}</div>
            <div class="track">
              <div class="gantt-grid">
                ${Array.from({ length: 15 }, () => '<div></div>').join('')}
              </div>
        `;
        
        if (morning) {
          const time = morning.time.split('~');
          if (time.length === 2) {
            const start = parseFloat(time[0].replace(':', '.'));
            const end = parseFloat(time[1].replace(':', '.'));
            const left = ((start - 6) / 15) * 100;
            const width = ((end - start) / 15) * 100;
            
            html += `
              <div class="gantt-bar" style="left:${left}%; width:${width}%"
                   data-date="${dateStr}" 
                   data-period="上午" 
                   data-name="${name}"
                   data-time="${time[0]}-${time[1]}"
                   title="點擊添加 ${name} 到編輯區">
                上午 ${time[0]}-${time[1]}
              </div>
            `;
          }
        }
        
        if (evening) {
          const time = evening.time.split('~');
          if (time.length === 2) {
            const start = parseFloat(time[0].replace(':', '.'));
            const end = parseFloat(time[1].replace(':', '.'));
            const left = ((start - 6) / 15) * 100;
            const width = ((end - start) / 15) * 100;
            
            html += `
              <div class="gantt-bar" style="left:${left}%; width:${width}%"
                   data-date="${dateStr}" 
                   data-period="晚上" 
                   data-name="${name}"
                   data-time="${time[0]}-${time[1]}"
                   title="點擊添加 ${name} 到編輯區">
                晚上 ${time[0]}-${time[1]}
              </div>
            `;
          }
        }
        
        html += `
            </div>
          </div>
        `;
      });
      
      container.innerHTML = html;
      
      container.querySelectorAll('.gantt-bar').forEach(bar => {
        bar.addEventListener('click', () => {
          const date = bar.dataset.date;
          const period = bar.dataset.period;
          const name = bar.dataset.name;
          const time = bar.dataset.time || '';
          
          console.log('🖱️ 點擊甘特圖:', { date, period, name, time });
          
          if (inDraft(date, period, name)) {
            console.log('⚠️ 已存在於編輯區');
            
            bar.style.opacity = '0.6';
            const originalBg = bar.style.background;
            bar.style.background = 'linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%)';
            
            setTimeout(() => {
              bar.style.opacity = '1';
              bar.style.background = originalBg;
            }, 800);
            
            const td = document.querySelector(
              `#editorBody td[data-ds="${date}"][data-period="${period}"]`
            );
            
            if (td) {
              td.scrollIntoView({ behavior: 'smooth', block: 'center' });
              td.classList.add('pulse-highlight');
              setTimeout(() => td.classList.remove('pulse-highlight'), 1500);
            }
            
            return;
          }
          
          console.log('✅ 添加到編輯區:', { date, period, name, time });
          addToDraft(date, period, name, time, true);
          
          const originalBg = bar.style.background;
          bar.style.background = 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)';
          bar.style.transform = 'scale(1.08)';
          
          const originalHtml = bar.innerHTML;
          bar.innerHTML = `<i class="fas fa-check-circle me-1"></i>` + originalHtml;
          
          setTimeout(() => {
            bar.style.background = originalBg;
            bar.style.transform = 'scale(1)';
            bar.innerHTML = originalHtml;
          }, 1500);
          
          const td = document.querySelector(
            `#editorBody td[data-ds="${date}"][data-period="${period}"]`
          );
          
          if (td) {
            td.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
      });
    }

    // ===== 編輯班表 =====
    function ensureDraftKey(ds) {
      if (!draftAssignedMap[ds]) {
        console.log('🔧 初始化日期:', ds);
        draftAssignedMap[ds] = { '上午': [], '晚上': [] };
      }
    }

    function inDraft(ds, period, name) {
      return (draftAssignedMap[ds]?.[period] || []).some(x => x.name === name);
    }

    function addToDraft(ds, period, name, time, showHighlight = false) {
      console.log('📝 addToDraft 被調用:', { ds, period, name, time, showHighlight });
      
      ensureDraftKey(ds);
      
      console.log('📦 當前 draftAssignedMap[ds]:', draftAssignedMap[ds]);
      
      if (!inDraft(ds, period, name)) {
        draftAssignedMap[ds][period].push({ name, time, note: '' });
        console.log('✅ 成功添加到草稿:', { ds, period, name, time });
        console.log('📊 更新後的列表:', draftAssignedMap[ds][period]);
        
        const td = document.querySelector(
          `#editorBody td[data-ds="${ds}"][data-period="${period}"]`
        );
        
        if (td) {
          renderEditorCell(ds, period, showHighlight);
        } else {
          console.warn('⚠️ 表格尚未渲染該日期,稍後會自動顯示:', ds);
        }
      } else {
        console.log('⚠️ 該員工已存在於草稿中');
      }
    }

    function removeFromDraft(ds, period, name) {
      if (draftAssignedMap[ds]?.[period]) {
        draftAssignedMap[ds][period] = draftAssignedMap[ds][period].filter(
          x => x.name !== name
        );
        renderEditorCell(ds, period);
      }
    }

    function upsertDraft(ds, period, name, time, originalName = null) {
      ensureDraftKey(ds);
      
      if (originalName && originalName !== name) {
        removeFromDraft(ds, period, originalName);
      }
      
      const list = draftAssignedMap[ds][period];
      const existing = list.find(x => x.name === name);
      
      if (existing) {
        existing.time = time;
      } else {
        list.push({ name, time, note: '' });
      }
      
      renderEditorCell(ds, period);
    }

    function renderEditorHeader(monday) {
      const headRow = document.getElementById('editorHeaderRow');
      headRow.querySelectorAll('th:nth-child(n+2)').forEach(th => th.remove());
      
      const labels = ['一', '二', '三', '四', '五', '六', '日'];
      daysOfWeek(monday).forEach((d, i) => {
        const th = document.createElement('th');
        th.innerHTML = `${d.getMonth() + 1}/${d.getDate()}<br>星期${labels[i]}`;
        headRow.appendChild(th);
      });
    }

    function renderEditorGrid(monday) {
      const tbody = document.getElementById('editorBody');
      tbody.innerHTML = '';
      
      PERIODS.forEach(period => {
        const tr = document.createElement('tr');
        const th = document.createElement('th');
        th.className = 'bg-light';
        th.textContent = period;
        tr.appendChild(th);

        daysOfWeek(monday).forEach(d => {
          const ds = fmt(d);
          ensureDraftKey(ds);
          
          const td = document.createElement('td');
          td.dataset.ds = ds;
          td.dataset.period = period;
          td.innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-assign-btn">
              <i class="fas fa-plus me-1"></i>新增
            </button>
          `;
          
          tr.appendChild(td);

          td.querySelector('.add-assign-btn').addEventListener('click', () => 
            openAssignModal({ ds, period })
          );
          
          renderEditorCell(ds, period);
        });

        tbody.appendChild(tr);
      });
    }

    function renderEditorCell(ds, period, highlightNew = false) {
      console.log('🎨 renderEditorCell:', { ds, period, highlightNew });
      
      const td = document.querySelector(
        `#editorBody td[data-ds="${ds}"][data-period="${period}"]`
      );
      
      if (!td) {
        console.error('❌ 找不到對應的 td 元素:', { ds, period });
        console.log('📋 當前週一:', fmt(currentMonday));
        console.log('📅 嘗試渲染的日期:', ds);
        
        const monday = currentMonday;
        const weekDates = daysOfWeek(monday).map(d => fmt(d));
        console.log('📆 當前週的日期範圍:', weekDates);
        
        if (!weekDates.includes(ds)) {
          console.warn('⚠️ 該日期不在當前週範圍內,無法渲染');
        }
        
        return;
      }
      
      console.log('✅ 找到 td 元素');
      
      const wrap = td.querySelector('div');
      wrap.innerHTML = '';
      
      const list = draftAssignedMap[ds]?.[period] || [];
      console.log('📝 要渲染的員工列表:', list);
      
      list.forEach(({ name, time }, index) => {
        const chip = document.createElement('span');
        chip.className = 'badge text-bg-primary assign-chip d-inline-flex align-items-center';
        chip.innerHTML = `
          <i class="fas fa-user me-1"></i>${name}
          <small class="opacity-75 ms-1">${time || ''}</small>
          <button type="button" class="btn btn-light btn-sm chip-btn ms-2" title="修改">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn btn-light btn-sm chip-btn" title="移除">×</button>
        `;
        
        const [btnEdit, btnDel] = chip.querySelectorAll('button');
        btnEdit.addEventListener('click', () => 
          openAssignModal({ ds, period, name, time })
        );
        btnDel.addEventListener('click', () => 
          removeFromDraft(ds, period, name)
        );
        
        wrap.appendChild(chip);
        
        if (highlightNew && index === list.length - 1) {
          console.log('✨ 添加高亮動畫');
          chip.classList.add('chip-highlight');
          setTimeout(() => {
            chip.classList.remove('chip-highlight');
          }, 1500);
        }
      });
      
      if (highlightNew) {
        console.log('✨ 單元格閃爍動畫');
        td.classList.add('cell-flash');
        setTimeout(() => {
          td.classList.remove('cell-flash');
        }, 1500);
      }
      
      console.log('✅ renderEditorCell 完成');
    }

    // ===== Modal =====
    const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    const assignForm = document.getElementById('assignForm');
    const assignNameSelect = document.getElementById('assignNameSelect');

    function openAssignModal({ ds, period, name = '', time = '' }) {
      document.getElementById('assignDs').value = ds;
      document.getElementById('assignPeriod').value = period;
      document.getElementById('assignOriginalName').value = name || '';
      document.getElementById('assignModalTitle').textContent = 
        name ? '修改人員' : '新增人員';

      assignNameSelect.value = name || '';

      let start = '', end = '';
      if (time && time.includes('-')) {
        [start, end] = time.split('-');
      }
      document.getElementById('assignStart').value = start || '';
      document.getElementById('assignEnd').value = end || '';

      assignModal.show();
    }

    assignForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const ds = document.getElementById('assignDs').value;
      const period = document.getElementById('assignPeriod').value;
      const originalName = document.getElementById('assignOriginalName').value || null;
      const name = assignNameSelect.value;
      const start = document.getElementById('assignStart').value;
      const end = document.getElementById('assignEnd').value;

      if (!name || !start || !end) {
        if (!name) alert('請選擇姓名');
        return;
      }
      
      const time = `${start}-${end}`;
      upsertDraft(ds, period, name, time, originalName);
      assignModal.hide();
    });

    // ===== 儲存班表 =====
    async function saveDraft(monday) {
      const payload = { 
        week_start: fmt(monday), 
        assignments: {} 
      };
      
      daysOfWeek(monday).forEach(d => {
        const ds = fmt(d);
        payload.assignments[ds] = {};
        
        PERIODS.forEach(period => {
          payload.assignments[ds][period] = (draftAssignedMap[ds]?.[period] || [])
            .map(x => ({
              name: x.name,
              time: x.time,
              note: x.note || ''
            }));
        });
      });

      try {
        const result = await fetchJSON('確認班表.php', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        if (result && result.success) {
          await loadSchedulePreview(currentMonday);
          renderPreview(currentMonday);   // 儲存後更新上方預覽
          alert('班表已確認並儲存!');
        } else {
          alert('儲存失敗: ' + (result?.error || '未知錯誤'));
        }
      } catch (err) {
        console.error('儲存班表錯誤', err);
        alert('儲存班表失敗,請稍後再試');
      }
    }

    // ===== 刷新流程 =====
    const defaultDateForLoad = new Date();
    defaultDateForLoad.setDate(defaultDateForLoad.getDate() + 7);
    
    let currentMonday = getMonday(defaultDateForLoad);

    async function refreshAll() {
      console.log('🔄 開始刷新所有數據...');
      
      renderWeekHeader(currentMonday);
      renderEditorHeader(currentMonday);

      // 先載入已儲存班表 → 給預覽 & 編輯區用
      await loadSchedulePreview(currentMonday);

      // 更新最上面的「本週班表預覽」
      renderPreview(currentMonday);

      // 編輯區一開始以「已儲存班表」為草稿
      draftAssignedMap = JSON.parse(JSON.stringify(scheduleAssignedMap));
      renderEditorGrid(currentMonday);
      
      // 可排時段 + 日檢視甘特圖
      await loadAvailability(currentMonday);
      renderDayButtons(currentMonday);
      
      console.log('✅ 所有數據刷新完成');
    }

    // ===== 事件綁定 =====
    document.getElementById('btnSaveDraft').addEventListener('click', () => 
      saveDraft(currentMonday)
    );

    document.getElementById('btnClearDraft').addEventListener('click', () => {
      if (!confirm('確定要清空本週的草稿嗎?')) return;
      draftAssignedMap = {};
      renderEditorGrid(currentMonday);
    });

    document.getElementById('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      });
    }

    window.addEventListener('DOMContentLoaded', async () => {
      // 先更新登入者資訊 & 頭像
      await loadLoggedInUser();

      // 再載員工清單 + 班表資料
      await loadEmployeeList();
      await refreshAll();

      // ✅ 上週 / 本週 / 下週 的事件
      document.getElementById('btnPrevWeek')?.addEventListener('click', async () => {
        currentMonday = addDays(currentMonday, -7);
        await refreshAll();
      });

      document.getElementById('btnNextWeek')?.addEventListener('click', async () => {
        currentMonday = getMonday(new Date());
        await refreshAll();
      });

      document.getElementById('btnNextNextWeek')?.addEventListener('click', async () => {
        currentMonday = addDays(currentMonday, 7);
        await refreshAll();
      });

      // ✅ 下載圖片按鈕
      document.getElementById('btnDownloadPng')?.addEventListener('click', downloadSchedulePng);
    });
  </script>
</body>
</html>
