<?php
// 啟用登入保護
session_start();

// 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 取得用戶資訊
$userName = $_SESSION['name'] ?? '用戶';
$userId = $_SESSION['uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>我的打卡記錄 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <!-- ✅ 加上 xlsx，用來匯出 Excel -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

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

    .user-avatar {
      border: 2px solid rgba(255, 255, 255, 0.55);
    }

    .container-fluid {
      padding: 26px 28px !important;
    }

    /* ====== Sidebar：同一套風格 ====== */
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
    }

    .sb-sidenav .nav-link .sb-nav-link-icon {
      margin-right: 10px;
      color: #1e293b !important;
      opacity: 0.9 !important;
      font-size: 1.05rem;
    }

    .sb-sidenav .nav-link:hover {
      border-color: rgba(255, 255, 255, 1);
      box-shadow: 0 14px 30px rgba(59, 130, 246, 0.4);
      transform: translateY(-1px);
    }

    .sb-sidenav .nav-link.active {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      border-color: rgba(255, 255, 255, 0.98);
      color: #ffffff !important;
      box-shadow: 0 18px 36px rgba(59, 130, 246, 0.6);
    }

    .sb-sidenav .nav-link.active .sb-nav-link-icon {
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

    /* 修正側欄箭頭顏色（SVG / icon） */
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
      margin-bottom: 0;
      background-color: #ffffff;
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
      border-color: rgba(148, 163, 184, 0.25);
    }

    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    /* ===== User info 卡片 ===== */
    .user-info-card {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: white;
      padding: 18px 20px;
      border-radius: 20px;
      margin-bottom: 24px;
      box-shadow: 0 16px 40px rgba(59, 130, 246, 0.45);
    }

    .user-info-card .small {
      opacity: .9;
    }

    /* ===== KPI 四張統計卡 ===== */
    .kpi-card {
      border-radius: 26px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
      overflow: hidden;
      position: relative;
      color: #0f172a;
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

    .kpi-hours {
      background: linear-gradient(135deg, #b1f9caff, #22c55e4d) !important;
    }
    .kpi-count {
      background: linear-gradient(135deg, #acc6f6ff, #818cf859) !important;
    }
    .kpi-missing {
      background: linear-gradient(135deg, #fee2e2, #fecaca) !important;
    }
    .kpi-ot {
      background: linear-gradient(135deg, #bce4ffff, #38bdf84d) !important;
    }

    /* ===== 狀態 badge ===== */
    .badge-status {
      border-radius: 999px;
      padding: .35rem .9rem;
      font-size: 0.78rem;
      font-weight: 600;
    }
    .badge-normal {
      background: rgba(22, 163, 74, 0.12);
      color: #166534;
    }
    .badge-ot {
      background: rgba(37, 99, 235, 0.12);
      color: #1d4ed8;
    }
    .badge-missing {
      background: rgba(220, 38, 38, 0.12);
      color: #b91c1c;
    }

    /* 篩選區按鈕 chip 風格 */
    .btn-chip {
      --h: 40px;
      --px: 16px;
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
    .btn-secondary-lite {
      background: #ffffff;
      color: #1f2937;
      border-color: rgba(148, 163, 184, .6);
    }
    .btn-success-lite {
      background: linear-gradient(135deg, #34d399 0%, #22c55e 100%);
      color: #fff;
      border-color: rgba(34, 197, 94, .25);
    }

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
      .btn-chip { --h: 38px; --px: 12px; }
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="indexC.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
      <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
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
    <!-- Side Nav -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="indexC.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
              首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link" href="新增班表.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表
            </a>
            <a class="nav-link" href="新增請假申請.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請
            </a>
            <a class="nav-link" href="員工薪資記錄.php">
              <div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄
            </a>
            <a class="nav-link active" href="員工打卡記錄.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>打卡記錄
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
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">我的打卡記錄</h1>
            <div class="text-muted small">
              <i class="fas fa-id-card me-2"></i>員工編號：<?php echo htmlspecialchars($userId); ?>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="indexC.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">打卡記錄</li>
          </ol>

          <!-- 用戶資訊卡片 -->
          <div class="user-info-card">
            <div class="d-flex align-items-center">
              <i class="fas fa-user-circle fa-3x me-3"></i>
              <div>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($userName); ?></div>
                <div class="small">員工編號：<?php echo htmlspecialchars($userId); ?></div>
              </div>
            </div>
          </div>

          <!-- 統計卡片 -->
          <div class="row mb-4">
            <div class="col-md-3 mb-3">
              <div class="card kpi-card kpi-hours">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總工時（小時）</div>
                      <div class="h5 mb-0" id="sum_hours">0.00</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-clock"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card kpi-card kpi-count">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">出勤筆數</div>
                      <div class="h5 mb-0" id="sum_records">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-list-check"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card kpi-card kpi-missing">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">缺卡筆數</div>
                      <div class="h5 mb-0" id="sum_missing">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-triangle-exclamation"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card kpi-card kpi-ot">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">加班（小時）</div>
                      <div class="h5 mb-0" id="sum_ot">0.00</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-bolt"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 篩選 -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">開始日期</label>
                  <input type="date" class="form-control" id="start_date">
                </div>
                <div class="col-md-4">
                  <label class="form-label">結束日期</label>
                  <input type="date" class="form-control" id="end_date">
                </div>
                <div class="col-md-4">
                  <label class="form-label">狀態</label>
                  <select class="form-control" id="status_filter">
                    <option value="">全部</option>
                    <option value="正常">正常</option>
                    <option value="缺卡">缺卡</option>
                    <option value="加班">加班</option>
                  </select>
                </div>
                <div class="col-12 text-end mt-2">
                  <button class="btn-chip btn-primary-lite me-2" id="btnSearch">
                    <span class="ic"><i class="fas fa-search"></i></span>
                    <span class="tx">查詢</span>
                  </button>
                  <button class="btn-chip btn-secondary-lite me-2" id="btnClear">
                    <span class="ic"><i class="fas fa-eraser"></i></span>
                    <span class="tx">清除</span>
                  </button>
                  <!-- ✅ 改成匯出 Excel，但保留同一個 id -->
                  <button class="btn-chip btn-success-lite" id="btnExport">
                    <span class="ic"><i class="fas fa-file-excel"></i></span>
                    <span class="tx">匯出 Excel</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 表格 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>打卡記錄明細</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead>
                    <tr>
                      <th>日期</th>
                      <th>上班時間</th>
                      <th>下班時間</th>
                      <th>工時（小時）</th>
                      <th>狀態</th>
                      <th>備註</th>
                    </tr>
                  </thead>
                  <tbody id="attTableBody">
                    <tr><td colspan="6" class="text-center py-4">載入中…</td></tr>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ===== 設定：從 PHP 傳入當前用戶 ID =====
    const CURRENT_USER_ID = <?php echo json_encode($userId); ?>;
    const API_BASE = '/lamian-ukn/api';
    const API_LIST = API_BASE + '/clock_list.php';

    console.log('📋 當前用戶 ID:', CURRENT_USER_ID);
    console.log('📡 API 路徑:', API_LIST);

    // ===== 側欄切換 =====
    document.getElementById('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    // ===== 工具函數 =====
    function parseHHMM(t) {
      if(!t) return null;
      const [h,m] = t.split(':').map(Number);
      return Number.isNaN(h) || Number.isNaN(m) ? null : h*60+m;
    }

    function minutesBetween(ci, co) {
      const a = parseHHMM(ci), b = parseHHMM(co);
      if(a==null || b==null) return null;
      let d = b-a;
      if(d<0) d+=1440;
      return d;
    }

    function hr2(mins) {
      return mins==null ? '-' : (Math.round((mins/60)*100)/100).toFixed(2);
    }

    function inferStatus(ci, co, mins) {
      if(!ci || !co) return '缺卡';
      return mins!=null && mins>480 ? '加班' : '正常';
    }

    function badge(status) {
      if(status==='缺卡') return '<span class="badge-status badge-missing">缺卡</span>';
      if(status==='加班') return '<span class="badge-status badge-ot">加班</span>';
      return '<span class="badge-status badge-normal">正常</span>';
    }

    // ===== 資料 =====
    let RAW = [];
    let FILTERED = [];

    function setDefaultDates() {
      const end = new Date();
      const start = new Date();
      start.setDate(end.getDate()-29); // 最近30天
      document.getElementById('end_date').value = end.toISOString().slice(0,10);
      document.getElementById('start_date').value = start.toISOString().slice(0,10);
    }

    async function loadAttendance() {
      const params = new URLSearchParams();
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;

      if(s) params.set('start_date', s);
      if(e) params.set('end_date', e);

      // ⭐ 自動帶入當前用戶 ID 進行篩選
      params.set('q', CURRENT_USER_ID);

      const url = `${API_LIST}?${params.toString()}`;
      console.log('📡 發送請求:', url);

      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
          credentials: 'include'
        });

        console.log('📥 回應狀態:', res.status);

        if(!res.ok) {
          const text = await res.text();
          console.error('❌ API 錯誤:', text);
          throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();
        console.log('✅ 取得資料:', data);

        RAW = Array.isArray(data) ? data : (data?.data || []);
        console.log(`📊 共 ${RAW.length} 筆記錄`);

        applyFilter();

      } catch(err) {
        console.error('❌ 載入失敗:', err);
        document.getElementById('attTableBody').innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-danger py-4">
              <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
              <div>載入失敗：${err.message}</div>
              <div class="small mt-2 text-muted">請確認 API 路徑是否正確</div>
            </td>
          </tr>`;
        setSummary(0,0,0,0);
      }
    }

    function applyFilter() {
      const st = document.getElementById('status_filter').value;

      FILTERED = RAW.filter(x => {
        if(!st) return true;
        const mins = minutesBetween(x.clock_in, x.clock_out);
        const status = inferStatus(x.clock_in, x.clock_out, mins);
        return status === st;
      });

      render();
    }

    function render() {
      const tbody = document.getElementById('attTableBody');

      if(!FILTERED.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
              <div>目前沒有打卡記錄</div>
            </td>
          </tr>`;
        setSummary(0,0,0,0);
        return;
      }

      let total=0, miss=0, otMin=0;

      tbody.innerHTML = FILTERED.map(row => {
        const mins = minutesBetween(row.clock_in, row.clock_out);
        const st = inferStatus(row.clock_in, row.clock_out, mins);
        total += (mins||0);
        if(st==='缺卡') miss++;
        if(st==='加班' && mins) otMin += (mins-480);

        return `
          <tr>
            <td><strong>${row.date ?? ''}</strong></td>
            <td>${row.clock_in ?? '-'}</td>
            <td>${row.clock_out ?? '-'}</td>
            <td><strong>${hr2(mins)}</strong></td>
            <td>${badge(st)}</td>
            <td class="text-muted small">${row.note ?? ''}</td>
          </tr>`;
      }).join('');

      setSummary(
        (Math.round((total/60)*100)/100).toFixed(2),
        FILTERED.length,
        miss,
        (Math.round((otMin/60)*100)/100).toFixed(2)
      );
    }

    function setSummary(h, cnt, miss, ot) {
      document.getElementById('sum_hours').textContent = h;
      document.getElementById('sum_records').textContent = cnt;
      document.getElementById('sum_missing').textContent = miss;
      document.getElementById('sum_ot').textContent = ot;
    }

    // ✅ 匯出 Excel（.xlsx）
    function exportExcel() {
      if(!FILTERED.length) {
        alert('目前沒有可匯出的資料');
        return;
      }

      const headers = ['日期','上班時間','下班時間','工時（小時）','狀態','備註'];

      const rows = FILTERED.map(r => {
        const mins = minutesBetween(r.clock_in, r.clock_out);
        const st = inferStatus(r.clock_in, r.clock_out, mins);
        return [
          r.date || '',
          r.clock_in || '',
          r.clock_out || '',
          hr2(mins),
          st,
          r.note || ''
        ];
      });

      const aoa = [headers, ...rows];
      const ws = XLSX.utils.aoa_to_sheet(aoa);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, '打卡記錄');

      const today = new Date().toISOString().slice(0,10);
      XLSX.writeFile(wb, `我的打卡記錄_${today}.xlsx`);
    }

    // ===== 初始化 =====
    window.addEventListener('DOMContentLoaded', () => {
      setDefaultDates();
      loadAttendance();

      document.getElementById('btnSearch').addEventListener('click', loadAttendance);
      document.getElementById('btnClear').addEventListener('click', () => {
        setDefaultDates();
        document.getElementById('status_filter').value = '';
        loadAttendance();
      });
      document.getElementById('status_filter').addEventListener('change', applyFilter);
      document.getElementById('btnExport').addEventListener('click', exportExcel);
    });
  </script>
  <script src="js/user-avatar-loader.js"></script>
</body>
</html>
