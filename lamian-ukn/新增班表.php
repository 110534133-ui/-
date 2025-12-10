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
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>班表 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 整體風格：改成跟「日報表記錄.php」同一套藍紫系 ====== */
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
      border: 2px solid rgba(255,255,255,0.6);
    }

    .container-fluid {
      padding: 26px 28px !important;
    }

    /* ====== Sidebar ====== */
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
      box-shadow: 0 10px 30px rgba(15,23,42,0.12);
      backdrop-filter: blur(12px);
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
      padding-top: 10px;
      padding-bottom: 10px;
    }

    .card-body {
      padding: 18px 20px 20px;
    }

    .table {
      border-radius: var(--card-radius);
      overflow: hidden;
      background: #fff;
      box-shadow: var(--shadow-soft);
    }

    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: 12px 10px;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
    }

    .table tbody td,
    .table tbody th {
      padding: 10px;
      vertical-align: middle;
      border-color: rgba(226, 232, 240, 0.7);
    }

    .table-hover tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
      transform: translateY(-1px);
    }

    /* ====== 上方週切換＋下載按鈕：chip 風格 ====== */
    .btn {
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      font-size: 0.9rem;
    }

    .btn-outline-secondary {
      background: #ffffff;
      color: #1d4ed8;
      border-color: rgba(148, 163, 184, 0.7);
      box-shadow: 0 2px 8px rgba(15,23,42,0.06);
    }
    .btn-outline-secondary:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: rgba(59,130,246,0.55);
      box-shadow: 0 6px 16px rgba(15,23,42,0.15);
      transform: translateY(-1px);
    }

    .btn-outline-primary {
      background: #ffffff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .45);
      box-shadow: 0 2px 8px rgba(15,23,42,0.06);
    }
    .btn-outline-primary:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: rgba(59,130,246,0.8);
      box-shadow: 0 6px 16px rgba(15,23,42,0.15);
      transform: translateY(-1px);
    }

    .btn-primary {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      border: none;
      color: #fff;
      box-shadow: 0 8px 20px rgba(59,130,246,0.4);
      padding-inline: 18px;
    }
    .btn-primary:hover {
      filter: brightness(1.05);
      box-shadow: 0 12px 26px rgba(59,130,246,0.55);
      transform: translateY(-1px);
    }

    .btn-sm {
      padding: 4px 12px;
      font-size: 0.78rem;
    }

    /* ====== 時段輸入的 input-group ====== */
    .range-item .input-group-text {
      min-width: 2.5rem;
      justify-content: center;
      background: #eef2ff;
      border-color: rgba(148, 163, 184, 0.7);
      font-size: 0.78rem;
    }
    .range-item .form-control {
      min-width: 6.5rem;
      font-size: 0.78rem;
    }
    .range-item .btn-outline-danger {
      border-radius: 999px;
    }

    /* ====== 班次 badge ====== */
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
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="indexC.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

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
          <a class="nav-link" href="indexC.php">
            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
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
          <a class="nav-link" href="員工打卡記錄.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1>班表填報</h1>
          <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
        </div>

        <ol class="breadcrumb mb-4">
          <li class="breadcrumb-item"><a href="index.html" class="text-decoration-none">首頁</a></li>
          <li class="breadcrumb-item active">班表</li>
        </ol>

        <!-- 唯讀:當週班表（header 裡面塞週切換那一排） -->
        <div class="card mb-4" id="scheduleViewCard">
          <div class="card-header">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="d-flex align-items-center me-auto">
                <i class="fas fa-calendar-alt me-2"></i>
                <span>當前週班表(唯讀)</span>
              </div>

              <!-- ⬇ 這一排移進來 -->
              <div class="btn-group me-2" role="group" aria-label="week switch">
                <button class="btn btn-outline-secondary" id="btnPrevWeek"><i class="fas fa-chevron-left me-1"></i>上週</button>
                <button class="btn btn-outline-secondary" id="btnNextWeek">本週</button>
                <button class="btn btn-outline-secondary" id="btnNextNextWeek">下週<i class="fas fa-chevron-right ms-1"></i></button>
              </div>

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
              <table class="table table-bordered table-hover text-center align-middle" id="readonlyTable">
                <thead id="viewHeader"></thead>
                <tbody id="viewBody"></tbody>
              </table>
            </div>
            <div class="small text-muted">※ 本區塊僅供瀏覽,不可編輯。</div>
          </div>
        </div>

        <!-- 員工填報:本週可排時段 -->
        <div class="card">
          <div class="card-header"><i class="fas fa-clipboard-list me-2"></i>可排班時段填報</div>
          <div class="card-body">
            <form id="availabilityForm">
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">填報週</label>
                  <input type="date" class="form-control" id="weekStartInput" required />
                  <div class="form-text">系統以這天為「週一」,往後產生 7 天。</div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered align-middle">
                  <thead class="table-light" id="availHeader"></thead>
                  <tbody id="availBody"></tbody>
                </table>
              </div>

              <div class="text-end">
                <button type="button" class="btn btn-outline-secondary" id="btnClear">清除全部</button>
                <button type="submit" class="btn btn-primary ms-2">送出可排時段</button>
              </div>
            </form>
            <div id="formMsg" class="mt-3"></div>
          </div>
        </div>

      </div>
    </main>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script>
  /* ====== 基本設定 ====== */
  const BASE_URL = '';
  const DEFAULT_HEADERS = { 'Content-Type': 'application/json' };

  async function fetchJSON(path, options = {}) {
    try {
      const res = await fetch(BASE_URL + path, {
        headers: DEFAULT_HEADERS,
        credentials: 'include',
        ...options
      });

      if (!res.ok) {
        throw new Error(res.status + ' ' + res.statusText);
      }

      const data = await res.json();
      return data;
    } catch (err) {
      console.error('[API ERROR]', path, err);
      alert('API 錯誤: ' + err.message);
      return null;
    }
  }

  document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-TW', {
    year:'numeric',
    month:'long',
    day:'numeric',
    weekday:'long'
  });

  document.getElementById('sidebarToggle').addEventListener('click', e => {
    e.preventDefault();
    document.body.classList.toggle('sb-sidenav-toggled');
  });

  /* ====== 日期處理 ====== */
  function fmt(d) {
    const year  = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day   = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function getMonday(d = new Date()) {
    const date = new Date(d);
    date.setHours(0, 0, 0, 0);

    const dayOfWeek = date.getDay(); // 0(日) ~ 6(六)
    let daysToSubtract;
    if (dayOfWeek === 0) {
      daysToSubtract = 6;
    } else {
      daysToSubtract = dayOfWeek - 1;
    }

    date.setDate(date.getDate() - daysToSubtract);
    return date;
  }

  function addDays(d, n) {
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
  }

  function rangeMonToSun(monday){
    const arr = [];
    for (let i = 0; i < 7; i++) {
      arr.push(addDays(monday, i));
    }
    return arr;
  }

  // 目前選擇的週一
  let currentMonday = getMonday(new Date());

  /* ====== 唯讀班表 ====== */
  async function loadReadonlySchedule(monday){
    console.log('🔥 載入班表 - 週一日期:', fmt(monday));

    const data = await fetchJSON(`員工確認班表.php?start=${fmt(monday)}`);
    const days = rangeMonToSun(monday);
    const head = document.getElementById('viewHeader');
    const body = document.getElementById('viewBody');

    const weekday = ['一','二','三','四','五','六','日'];
    head.innerHTML = `
      <tr>
        <th style="min-width:120px">員工</th>
        ${days.map((d,i)=>`<th>${d.getMonth()+1}/${d.getDate()}<br>星期${weekday[i]}</th>`).join('')}
      </tr>`;

    if (!data || !Array.isArray(data.rows) || data.rows.length===0){
      body.innerHTML = `<tr><td colspan="8" class="text-center text-muted">目前沒有班表資料。</td></tr>`;
      return;
    }

    body.innerHTML = data.rows.map(r => `
      <tr>
        <th class="bg-light text-start">${r.name ?? ''}</th>
        ${(r.shifts ?? Array(7).fill([])).map(dayShifts => {
          if (!dayShifts || dayShifts.length === 0) {
            return `<td><span class="badge-off">休</span></td>`;
          }
          return `<td>` + dayShifts.map(s => `<span class="badge-shift">${s}</span>`).join('<br>') + `</td>`;
        }).join('')}
      </tr>
    `).join('');
  }

  async function downloadSchedulePng(){
    const el = document.getElementById('scheduleViewCard');

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

  /* ====== 可排時段填報 ====== */
  function renderAvailabilityTable(monday){
    console.log('📝 渲染填報表 - 週一日期:', fmt(monday));

    const days = rangeMonToSun(monday);
    const weekdayFull = ['星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
    const head = document.getElementById('availHeader');
    const body = document.getElementById('availBody');

    head.innerHTML = `
      <tr>
        ${days.map((d,i)=>`
          <th class="text-center">
            ${weekdayFull[i]}<br>
            ${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}
          </th>
        `).join('')}
      </tr>`;

    const row = document.createElement('tr');
    days.forEach((d, i) => {
      const dateStr = fmt(d);
      const td = document.createElement('td');
      td.style.minWidth = '220px';
      td.innerHTML = `
        <div class="ranges" data-date="${dateStr}"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-action="add-range" data-date="${dateStr}">
          <i class="fas fa-plus me-1"></i>新增時段
        </button>
        <div class="form-text mt-1">可新增多段時間</div>
      `;
      row.appendChild(td);
    });

    body.innerHTML = '';
    body.appendChild(row);

    document.querySelectorAll('.ranges').forEach(r => addRangeRow(r.dataset.date));
  }

  function addRangeRow(dateStr){
    const container = document.querySelector(`.ranges[data-date="${dateStr}"]`);
    if (!container) return;

    const idx = container.querySelectorAll('.range-item').length;
    const idStart = `s_${dateStr}_${idx}`;
    const idEnd   = `e_${dateStr}_${idx}`;

    const div = document.createElement('div');
    div.className = 'range-item input-group input-group-sm mb-2';
    div.innerHTML = `
      <span class="input-group-text">起</span>
      <input type="time" class="form-control start" id="${idStart}" aria-label="start">
      <span class="input-group-text">迄</span>
      <input type="time" class="form-control end" id="${idEnd}" aria-label="end">
      <button class="btn btn-outline-danger" type="button" title="移除" data-action="remove-range">&times;</button>
    `;
    container.appendChild(div);
  }

  function clearAllRanges(){
    document.querySelectorAll('.ranges').forEach(r => r.innerHTML = '');
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if(!btn) return;

    const action = btn.dataset.action;
    if(action === 'add-range'){
      addRangeRow(btn.dataset.date);
    } else if(action === 'remove-range'){
      const item = btn.closest('.range-item');
      if(item) item.remove();
    }
  });

  function showFormMsg(text, type='secondary'){
    const slot = document.getElementById('formMsg');
    slot.innerHTML = `<div class="alert alert-${type} mb-0" role="alert">${text}</div>`;
    setTimeout(() => { slot.innerHTML = ''; }, 3000);
  }

  async function submitAvailability(e){
    e.preventDefault();

    const weekStartStr = document.getElementById('weekStartInput').value;
    if(!weekStartStr){
      showFormMsg('請先選擇「填報週」', 'danger');
      return;
    }

    const [year, month, day] = weekStartStr.split('-').map(Number);
    const inputDate = new Date(year, month - 1, day);

    const weekStart = getMonday(inputDate);
    console.log('📤 送出填報');
    console.log('  - 原始輸入:', weekStartStr);
    console.log('  - 解析日期:', inputDate.toLocaleDateString('zh-TW'));
    console.log('  - 計算週一:', weekStart.toLocaleDateString('zh-TW'), fmt(weekStart));

    const availability = {};
    let invalid = false;

    document.querySelectorAll('.ranges').forEach(r => {
      const date = r.dataset.date;
      const items = Array.from(r.querySelectorAll('.range-item'));
      const ranges = [];

      items.forEach(it => {
        const s = it.querySelector('.start')?.value || '';
        const e = it.querySelector('.end')?.value || '';

        if(!s && !e) return;

        if(!s || !e || s >= e){
          invalid = true;
          return;
        }

        ranges.push({ start: s, end: e, note: '' });
      });

      availability[date] = ranges;
    });

    if(invalid){
      showFormMsg('有不合法的時間段(起需早於迄,且欄位不可空白)。請修正後再送出。', 'danger');
      return;
    }

    const payload = {
      week_start: fmt(weekStart),
      availability
    };

    console.log('📤 送出資料:', JSON.stringify(payload, null, 2));

    const result = await fetchJSON('班表.php', {
      method:'POST',
      body: JSON.stringify(payload)
    });

    if(result && result.success){
      showFormMsg('已送出,感謝填報!', 'success');
      await loadReadonlySchedule(currentMonday);
    } else {
      const errorMsg = result?.error || '未知錯誤';
      showFormMsg('送出失敗: ' + errorMsg, 'danger');
    }
  }

  function clearForm(){
    clearAllRanges();
    document.querySelectorAll('.ranges').forEach(r => addRangeRow(r.dataset.date));
    showFormMsg('已清除全部時間段。', 'secondary');
  }

  /* ====== 週切換控制 ====== */
  function updateWeekRangeText(monday){
    const sun = addDays(monday, 6);
    const s = `${monday.getFullYear()}/${String(monday.getMonth()+1).padStart(2,'0')}/${String(monday.getDate()).padStart(2,'0')}`;
    const e = `${sun.getFullYear()}/${String(sun.getMonth()+1).padStart(2,'0')}/${String(sun.getDate()).padStart(2,'0')}`;
    document.getElementById('weekRangeText').textContent = `${s} - ${e}`;
    console.log('📅 週期顯示:', s, '-', e);
  }

  async function refreshAll(){
    console.log('🔄 刷新全部 - currentMonday:', currentMonday.toLocaleDateString('zh-TW'), fmt(currentMonday));
    updateWeekRangeText(currentMonday);
    await loadReadonlySchedule(currentMonday);
    renderAvailabilityTable(currentMonday);
    document.getElementById('weekStartInput').value = fmt(addDays(currentMonday, 7));
  }

  /* ====== 初始化 ====== */
  window.addEventListener('DOMContentLoaded', async () => {
    const today = new Date();
    console.log('🚀 頁面初始化');
    console.log('  - 今天(本地):', today.toLocaleDateString('zh-TW'));
    console.log('  - 本週一:', currentMonday.toLocaleDateString('zh-TW'), fmt(currentMonday));

    document.getElementById('btnPrevWeek').addEventListener('click', async () => {
      currentMonday = addDays(currentMonday, -7);
      await refreshAll();
    });

    document.getElementById('btnNextWeek').addEventListener('click', async () => {
      currentMonday = getMonday(new Date());
      await refreshAll();
    });

    document.getElementById('btnNextNextWeek').addEventListener('click', async () => {
      currentMonday = addDays(currentMonday, 7);
      await refreshAll();
    });

    document.getElementById('btnDownloadPng').addEventListener('click', downloadSchedulePng);
    document.getElementById('availabilityForm').addEventListener('submit', submitAvailability);
    document.getElementById('btnClear').addEventListener('click', clearForm);

    document.getElementById('weekStartInput').addEventListener('change', async (e) => {
      const inputValue = e.target.value;
      const [year, month, day] = inputValue.split('-').map(Number);
      const d = new Date(year, month - 1, day);

      console.log('📅 日期選擇器變更');
      console.log('  - input值:', inputValue);
      console.log('  - 解析結果:', d.toLocaleDateString('zh-TW'));

      currentMonday = getMonday(d);
      await refreshAll();
    });

    await refreshAll();
  });
</script>
<script src="js/user-avatar-loader.js"></script>
</body>
</html>
