<?php
// 🔥 新的 日報表.php (填寫頁面)
// 🔥 已套用您系統的版型 (包含權限檢查)

require_once __DIR__ . '/includes/auth_check.php';

// 2. 檢查權限：A 級(老闆) 或 B 級(管理員)
// 假設 check_user_level() 會檢查當前 session 用戶
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    // 如果 *既不是A* *也不是B*，導向回首頁 (index.php)
    header('Location: index.php'); 
    exit;
}

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '日報表 - 員工管理系統'; // 標題

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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    --blue-main: #1e3a8a;
    --blue-accent: #2563eb;
    --blue-soft: #bfdbfe;
    --green-main: #16a34a;
    --amber-main: #f59e0b;
    --rose-main: #e11d48;
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

  /* ===== Top navbar（跟首頁一樣藍色漸層） ===== */
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
    border: 2px solid rgba(191, 219, 254, 0.8);
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.1);
  }

  .container-fluid {
    padding: 26px 28px;
  }

  /* ===== Navbar 專用搜尋框（保留你原本結構，改成藍系玻璃風） ===== */
  .search-container-wrapper {
    position: relative;
    width: 100%;
    max-width: 380px;
  }
  .search-container {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(248, 250, 252, 0.16);
    border-radius: 999px;
    padding: 4px 4px 4px 18px;
    border: 1.5px solid rgba(191, 219, 254, 0.7);
    backdrop-filter: blur(16px);
  }
  .search-container:hover {
    background: rgba(248, 250, 252, 0.25);
    border-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 0 1px rgba(255,255,255,0.55);
  }
  .search-container:focus-within {
    background: rgba(248, 250, 252, 0.32);
    border-color: rgba(129, 140, 248, 0.9);
    box-shadow: 0 0 0 1px rgba(129, 140, 248, 0.75);
  }
  .search-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 9px 10px;
    font-size: 0.9rem;
    color: #e5e7eb;
    font-weight: 500;
  }
  .search-input::placeholder {
    color: rgba(226, 232, 240, 0.8);
  }
  .search-btn {
    background: linear-gradient(135deg, #eff6ff, #ffffff);
    border: none;
    border-radius: 999px;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.35);
  }
  .search-btn:hover {
    transform: translateY(-1px) scale(1.04);
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.45);
  }
  .search-btn i {
    color: #2563eb;
    font-size: 0.9rem;
  }

  /* ===== Sidebar：沿用首頁那套藍白風 ===== */
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

  /* ===== 標題 & 麵包屑 ===== */
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


  /* ===== Alert 區塊（上方成功 / 警告 / 錯誤訊息） ===== */
  #successAlert,
  #warningAlert,
  #errorAlert {
    border-radius: 18px;
    padding: 10px 14px;
    border-width: 1px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    gap: 6px;
  }
  #successAlert {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    border-color: rgba(34, 197, 94, 0.55);
    color: #065f46;
  }
  #warningAlert {
    background: linear-gradient(135deg, #fef9c3, #fee2a1);
    border-color: rgba(234, 179, 8, 0.6);
    color: #78350f;
  }
  #errorAlert {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-color: rgba(248, 113, 113, 0.65);
    color: #7f1d1d;
  }
  #successAlert .btn-close,
  #warningAlert .btn-close,
  #errorAlert .btn-close {
    margin-left: auto;
  }

  /* 下方合計用的 alert */
  .alert-success {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    border-color: rgba(34, 197, 94, 0.6);
    color: #065f46;
  }
  .alert-danger {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-color: rgba(248, 113, 113, 0.65);
    color: #7f1d1d;
  }
  .alert-warning {
    background: linear-gradient(135deg, #fef9c3, #fee2a1);
    border-color: rgba(234, 179, 8, 0.65);
    color: #78350f;
  }
  .alert-info {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-color: rgba(59, 130, 246, 0.6);
    color: #1e3a8a;
  }

  /* 店內現金 input-group 小修 */
  .input-group-text {
    border-radius: 10px 0 0 10px;
    border-color: rgba(209, 213, 219, 0.9);
    background: #f9fafb;
    font-size: 0.82rem;
  }
  .input-group .form-control {
    border-radius: 0 10px 10px 0;
  }

  /* ===== 主要按鈕（送出日報表） ===== */
  .btn-primary {
    background: linear-gradient(135deg, #4f46e5, #2563eb);
    border: none;
    border-radius: 999px;
    padding-inline: 18px;
    min-width: 160px;
    box-shadow: 0 14px 32px rgba(37, 99, 235, 0.55);
    font-weight: 600;
    letter-spacing: .03em;
  }
  .btn-primary:hover {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 18px 40px rgba(37, 99, 235, 0.75);
    filter: brightness(1.03);
  }
  .btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.6);
  }

  .btn-secondary.btn-sm,
  .btn-outline-secondary.btn-sm {
    border-radius: 999px;
    font-size: 0.78rem;
  }

  /* 租金設定 Modal */
  #rentSettingModal .modal-content {
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.35);
    border: 1px solid rgba(226, 232, 240, 0.9);
  }
  #rentSettingModal .modal-header {
    border-bottom-color: rgba(226, 232, 240, 0.8);
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
  }
  #rentSettingModal .modal-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e3a8a;
  }

  /* footer 跟首頁一致 */
  footer {
    background: transparent !important;
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

                <a class="nav-link active" href="日報表.php"><div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
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
            <h1>日報表</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">日報表</li>
          </ol>

             <div class="container mt-3">
          <div id="successAlert" class="alert alert-success d-none" role="alert">
            <i class="fas fa-check-circle me-2"></i><span id="successMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <div id="warningAlert" class="alert alert-warning d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="warningMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-times-circle me-2"></i><span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        </div>

      <div class="row g-3 mb-3">
  <!-- 今日收入合計：綠色 -->
  <div class="col-xl-3 col-md-6">
    <div class="card kpi-card kpi-success">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small kpi-label">今日收入合計</div>
          <div class="kpi-value" id="kpi_income">—</div>
        </div>
        <div class="icon-pill">
          <i class="fas fa-dollar-sign"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 今日支出合計：藍色 -->
  <div class="col-xl-3 col-md-6">
    <div class="card kpi-card kpi-primary">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small kpi-label">今日支出合計</div>
          <div class="kpi-value" id="kpi_expense">—</div>
        </div>
        <div class="icon-pill">
          <i class="fas fa-credit-card"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 今日淨收入：黃色 -->
  <div class="col-xl-3 col-md-6">
    <div class="card kpi-card kpi-warning">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small kpi-label">今日淨收入（收−支）</div>
          <div class="kpi-value" id="kpi_net">—</div>
        </div>
        <div class="icon-pill">
          <i class="fas fa-chart-line"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 存入銀行：藍綠色 -->
  <div class="col-xl-3 col-md-6">
    <div class="card kpi-card kpi-info">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small kpi-label">存入銀行</div>
          <div class="kpi-value" id="kpi_deposit">—</div>
        </div>
        <div class="icon-pill">
          <i class="fas fa-university"></i>
        </div>
      </div>
    </div>
  </div>
</div>



        <form id="dailyReportForm">
  <div class="card mb-4">
    <div class="card-header"><i class="fas fa-info-circle me-2"></i>基本資訊</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">日期</label>
          <input type="date" class="form-control" id="report_date">
        </div>
        <div class="col-md-4">
          <label class="form-label">星期</label>
          <input type="text" class="form-control" id="weekday" placeholder="例如：星期一" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">填表人</label>
          <input type="text" class="form-control" id="filled_by" placeholder="填寫人員姓名">
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-wallet me-2"></i>收入</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">現金收入</label>
            <input type="number" class="form-control income" id="cash_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Line Pay</label>
            <input type="number" class="form-control income" id="linepay_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Uber 實收</label>
            <input type="number" class="form-control income" id="uber_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">其他收入</label>
            <input type="number" class="form-control income" id="other_income" placeholder="0">
          </div>
          <div class="alert alert-success mb-0 mt-auto">
            <strong>收入合計：</strong><span id="total_income">0</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-thumbtack me-2"></i>固定支出</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">人事成本</label>
            <input type="number" class="form-control expense" id="expense_salary" placeholder="0">
          </div>
         <div class="mb-2 d-flex align-items-center">
        <input type="checkbox" class="form-check-input me-2" id="enable_utilities">
        <label class="form-label mb-0 me-2" for="enable_utilities">水電瓦斯費</label>
        <select class="form-select form-select-sm" id="utility_month" style="width:auto;" disabled>
          <option value="term1">1-2月</option>
          <option value="term2">3-4月</option>
          <option value="term3">5-6月</option>
          <option value="term4">7-8月</option>
          <option value="term5">9-10月</option>
          <option value="term6">11-12月</option>
        </select>
      </div>
      <div class="mb-3">
        <input type="number" class="form-control expense" id="expense_utilities" placeholder="期金額（例如：整期總額）" disabled>
      </div>

      <div class="mb-2 d-flex align-items-center">
  <input type="checkbox" class="form-check-input me-2" id="enable_rent">
  <label class="form-label mb-0 me-2" for="enable_rent">租金</label>

  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rentSettingModal">
    <i class="bi bi-gear"></i>
  </button>
</div>

<div class="mb-3">
  <input type="number" class="form-control expense" id="expense_rent" placeholder="租金（金額）" disabled>
</div>

<input type="hidden" id="rent_setting" value='{"period":"month","months":3}'>


<div class="modal fade" id="rentSettingModal" tabindex="-1" aria-labelledby="rentSettingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
  <h5 class="modal-title d-flex align-items-center" id="rentSettingModalLabel">
    <i class="bi bi-gear me-2"></i> 租金設定
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
</div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="rent_period" class="form-label">租金週期</label>
          <select class="form-select form-select-sm" id="rent_period">
            <option value="month">月</option>
            <option value="season">季</option>
          </select>
        </div>
        <div class="mb-3 d-none" id="season_wrap">
          <label for="season_months" class="form-label">季期</label>
          <select class="form-select form-select-sm" id="season_months">
            <option value="3">3個月</option>
            <option value="4">4個月</option>
            <option value="6">6個月</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="rent_start" class="form-label">租期開始</label>
          <input type="date" class="form-control form-control-sm" id="rent_start">
        </div>
        <div class="mb-3">
          <label for="rent_end" class="form-label">租期結束</label>
          <input type="date" class="form-control form-control-sm" id="rent_end">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveRentSetting">儲存</button>
      </div>
    </div>
  </div>
</div>

      <div class="alert alert-danger mb-0 mt-auto">
  <strong>固定支出合計：</strong><span id="t_expense">0</span>
</div>
    </div>
  </div>
</div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-receipt me-2"></i>變動支出</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">食材成本</label>
            <input type="number" class="form-control expense" id="expense_food" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">外送平台費</label>
            <input type="number" class="form-control expense" id="expense_delivery" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">雜項支出</label>
            <input type="number" class="form-control expense" id="expense_misc" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">備註</label>
            <textarea class="form-control" id="expense_note" placeholder="如：採買食材、維修"></textarea>
          </div>
          <div class="alert alert-warning mb-0 mt-auto">
  <strong>變動支出合計：</strong><span id="total_variable">0</span>
</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-coins me-2"></i>店內現金</div>
        <div class="card-body d-flex flex-column">
          <div class="row g-2">
            <div class="col-6"><div class="input-group"><span class="input-group-text">1000</span><input type="number" class="form-control cash" id="cash_1000" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">500</span><input type="number" class="form-control cash" id="cash_500" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">100</span><input type="number" class="form-control cash" id="cash_100" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">50</span><input type="number" class="form-control cash" id="cash_50" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">10</span><input type="number" class="form-control cash" id="cash_10" placeholder="枚數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">5</span><input type="number" class="form-control cash" id="cash_5" placeholder="枚數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">1</span><input type="number" class="form-control cash" id="cash_1" placeholder="枚數"></div></div>
          </div>
          <div class="alert alert-info mt-3 mb-0 mt-auto">
            <strong>店內現金合計：</strong><span id="cash_total">0</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">存入銀行</label>
          <input type="number" class="form-control" id="deposit_to_bank" placeholder="輸入金額">
        </div>
        <div class="col-md-6 text-end align-self-end">
          <button type="submit" class="btn btn-primary" id="btnSubmit">
            <i class="fas fa-paper-plane me-1"></i>送出日報表
          </button>
        </div>
      </div>
    </div>
  </div>
</form>
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
  <script src="日報表.js"></script>





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
      async function loadLoggedInUser(){
          const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
          const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
          
          console.log('✅ 日報表 已登入:', userName, 'ID:', userId);
          
          // 設定用戶名 (Sidenav footer)
          const loggedAsEl = el('loggedAs');
          if (loggedAsEl) loggedAsEl.textContent = userName;

          // 設定用戶名 (Navbar)
          const navName = el('navUserName');
          if(navName) navName.textContent = userName;
          
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




      // 初始化
      window.addEventListener('DOMContentLoaded', async ()=>{
          await loadLoggedInUser();
          
          // 🔥 觸發 JS 檔案中的 updateKPI()
          if (typeof updateKPI === 'function') {
              updateKPI(); //
          } else {
              console.error("updateKPI() 函式不存在，請檢查 日報表.js");
          }
      });
  </script><script>
// 店內現金計算
function updateCashTotal() {
    // 每個面額對應其輸入欄位 id
    const cashMap = {
        1000: "cash_1000",
        500:  "cash_500",
        100:  "cash_100",
        50:   "cash_50",
        10:   "cash_10",
        5:    "cash_5",
        1:    "cash_1",
    };

    let total = 0;

    // 逐一計算 (張數 × 面額)
    for (const [value, id] of Object.entries(cashMap)) {
        const count = Number(document.getElementById(id)?.value) || 0;
        total += count * Number(value);
    }

    // 顯示到前端 <span id="cash_total">
    document.getElementById("cash_total").innerText = total;
}

// 🔥 為所有 class="cash" 的輸入框加入監聽事件
document.querySelectorAll(".cash").forEach(input => {
    input.addEventListener("input", updateCashTotal);
});
</script>
</body>
</html>