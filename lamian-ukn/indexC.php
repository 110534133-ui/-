<?php
// /lamian-ukn/indexC.php - C級員工頁面
// 🔥 啟用登入保護
session_start();

// 1. 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 2. 檢查用戶等級 - 只有 C 級可以訪問此頁
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';

if ($userLevel === 'A') {
    // A 級用戶跳轉到 index.php
    header('Location: index.php');
    exit;
} elseif ($userLevel === 'B') {
    // B 級用戶跳轉到 indexB.php
    header('Location: indexB.php');
    exit;
}
// 如果是 C 級，繼續執行

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
  <title>首頁 - 員工管理系統（C 級）</title>

  <!-- ✅ 必要的 Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- 你的既有資源 -->
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

  .user-avatar {
    border: 2px solid rgba(255, 255, 255, 0.55);
  }

  .container-fluid {
    padding: 26px 28px !important;
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

  /* Sidebar 標題 */
  .sb-sidenav-menu-heading {
    color: #1e293b !important;
    opacity: 0.75;
    font-size: 0.78rem;
    letter-spacing: .18em;
    margin: 20px 0 8px 16px;
  }

  /* Sidebar 按鈕：膠囊卡片 */
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

  /* ====== 標題 & 日期 / 麵包屑 ====== */
  h1 {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: .04em;
    background: linear-gradient(120deg, #0f172a, #2563eb);
    -webkit-background-clip: text;
    color: transparent;
    margin-bottom: 8px;
  }

  .current-date-wrap {
    color: var(--text-subtle);
    font-size: 0.92rem;
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
  display: flex;
  align-items: center;
  gap: 14px;
}


  #alertBox strong {
    font-size: 0.98rem;
  }

  .loading-shimmer {
    background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
    background-size:200% 100%;
    animation:shimmer 1.6s infinite;
  }
  @keyframes shimmer{
    0%{background-position:200% 0}
    100%{background-position:-200% 0}
  }

  /* ====== 卡片 / 班表區塊 ====== */
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

  .card-header small {
    font-weight: 400;
    color: var(--text-subtle);
  }

  .card-body {
    padding: 18px 20px 20px;
  }

  /* 班表表格 */
  .table {
    margin-bottom: 0;
    border-radius: 18px;
    overflow: hidden;
    background-color: #ffffff;
  }

  .table thead th {
    background: linear-gradient(135deg, #e0edff, #f1f5f9);
    color: #0f172a;
    border-bottom-width: 0;
    font-size: 0.86rem;
    text-align: center;
    vertical-align: middle;
  }

  .table tbody td,
  .table tbody th {
    vertical-align: middle;
    font-size: 0.9rem;
    border-color: rgba(148, 163, 184, 0.25);
    text-align: center;
  }

  .table tbody th {
    background-color: #f8fafc;
    font-weight: 600;
    text-align: left;
    padding-left: 14px;
  }

  .table tbody tr:hover {
    background-color: rgba(191, 219, 254, 0.25);
  }

  /* 班次 badge */
  .badge-shift {
    display: inline-block;
    padding: 4px 10px;
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border-radius: 999px;
    font-size: 12px;
    margin: 2px 0;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.32);
  }

.badge-off {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 48px;      /* 👉 讓外框至少有一定長度 */
  padding: 4px 12px;    /* 👉 保留一點左右內距 */
  background: #94a3b8;
  color: white;
  border-radius: 16px;  /* 👉 改成圓角矩形，不是正圓 */
  font-size: 12px;
}


  /* Footer */
  footer {
    background: transparent !important;
    border-top: 1px solid rgba(148, 163, 184, 0.35);
    margin-top: 24px;
    padding-top: 14px;
    font-size: 0.8rem;
    color: var(--text-subtle);
  }

  /* RWD */
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

  /* 修正側邊欄箭頭（SVG / ::after / background-image 全吃） */
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
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link active" href="indexC.php">
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
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mt-2 mb-0">我的班表</h1>
            <div class="current-date-wrap">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate">載入中...</span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">
              <i class="fas fa-home me-2"></i>首頁
            </li>
          </ol>

          <!-- 系統通知 -->
          <div id="alertBox" class="alert d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-bell me-3"></i>
            <div>
              <strong>系統通知</strong><br>
              <span id="alertContent" class="loading-shimmer" style="display:inline-block;width:260px;height:1rem;border-radius:6px;"></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>


          <!-- 本週班表 -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <i class="fas fa-calendar-alt me-2"></i>本週班表總覽
              </div>
              <span id="weekRangeText" class="text-muted small"></span>
            </div>
            <div class="card-body">
              <div id="scheduleLoading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">載入中...</span>
                </div>
                <p class="mt-2 text-muted">正在載入班表資料...</p>
              </div>
              <div id="scheduleContent" class="table-responsive" style="display:none;">
                <table class="table table-hover align-middle">
                  <thead id="scheduleHeader"></thead>
                  <tbody id="scheduleBody"></tbody>
                </table>
              </div>
              <div id="scheduleError" class="alert alert-danger mt-3" style="display:none;"></div>
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

  <!-- Libs -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

  <script>
    // ---- 常數（PHP 變數注入） ----
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;   // /lamian-ukn/api
    const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;  // /lamian-ukn/首頁

    const $  = s => document.querySelector(s);
    const el = id => document.getElementById(id);

    // ===== 日期工具函數 =====
    function getMonday(d = new Date()) {
      const date = new Date(d);
      const day = (date.getDay() + 6) % 7;
      date.setDate(date.getDate() - day);
      date.setHours(0, 0, 0, 0);
      return date;
    }

    // ✅ 使用本地時間格式化
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

    function rangeMonToSun(monday) {
      return Array.from({ length: 7 }, (_, i) => addDays(monday, i));
    }

    // 今日日期
    el('currentDate').textContent = new Date().toLocaleDateString('zh-TW', {year:'numeric',month:'long',day:'numeric',weekday:'long'});

    // 折起/展開側欄
    el('sidebarToggle')?.addEventListener('click', e => { 
      e.preventDefault(); 
      document.body.classList.toggle('sb-sidenav-toggled'); 
    });

    // 取得登入者資訊（已從 PHP Session 取得）
    async function loadLoggedInUser(){
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;

      console.log('✅ C級員工已登入:', userName, 'ID:', userId);

      // 設定用戶名
      el('loggedAs').textContent = userName;
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

    // 系統通知 placeholder
    function loadAlertsPlaceholder(){
      const span = el('alertContent');
      if(!span) return;
      setTimeout(()=>{
        span.classList.remove('loading-shimmer');
        span.textContent = '歡迎回來！今日尚無異常。';
      }, 700);
    }

    // ===== 🔥 載入真實班表資料 =====
    async function loadWeekSchedule() {
      console.log('🔄 開始載入本週班表...');

      const loading = el('scheduleLoading');
      const content = el('scheduleContent');
      const error = el('scheduleError');
      const header = el('scheduleHeader');
      const tbody = el('scheduleBody');

      try {
        // 顯示載入中
        loading.style.display = 'block';
        content.style.display = 'none';
        error.style.display = 'none';

        // 取得本週一
        const monday = getMonday(new Date());
        const startStr = fmt(monday);

        // 顯示週範圍
        const sunday = addDays(monday, 6);
        el('weekRangeText').textContent = `${fmt(monday)} ~ ${fmt(sunday)}`;

        console.log('📅 查詢週範圍:', startStr);

        // 呼叫 API
        const response = await fetch(`員工確認班表.php?start=${startStr}`, {
          credentials: 'same-origin'
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('✅ API 回應:', data);

        if (!data.success) {
          throw new Error(data.message || '載入失敗');
        }

        // 生成表頭
        const days = rangeMonToSun(monday);
        const weekday = ['一', '二', '三', '四', '五', '六', '日'];

        header.innerHTML = `
          <tr>
            <th style="min-width:120px">員工</th>
            ${days.map((d, i) => `
              <th>
                ${d.getMonth() + 1}/${d.getDate()}<br>
                <small>星期${weekday[i]}</small>
              </th>
            `).join('')}
          </tr>
        `;

        // 生成表格內容
        if (!data.rows || data.rows.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                本週尚未安排班表
              </td>
            </tr>
          `;
        } else {
          tbody.innerHTML = data.rows.map(row => `
            <tr>
              <th class="text-start"><strong>${row.name || ''}</strong></th>
              ${(row.shifts || Array(7).fill([])).map(dayShifts => {
                if (!dayShifts || dayShifts.length === 0) {
                  return `<td><span class="badge-off">休</span></td>`;
                }
                return `<td>${dayShifts.map(s => `<span class="badge-shift">${s}</span>`).join('<br>')}</td>`;
              }).join('')}
            </tr>
          `).join('');
        }

        // 顯示內容
        loading.style.display = 'none';
        content.style.display = 'block';

        console.log('✅ 班表載入完成');

      } catch (err) {
        console.error('❌ 載入班表失敗:', err);

        loading.style.display = 'none';
        error.style.display = 'block';
        error.innerHTML = `
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>載入失敗：</strong>${err.message}
          <button class="btn btn-sm btn-outline-danger ms-3" onclick="loadWeekSchedule()">
            <i class="fas fa-redo me-1"></i>重試
          </button>
        `;
      }
    }

    // 若你在別處有圖表/指標函式，這裡只是呼叫；沒有就不會報錯
    async function buildYearMonthSelectors() {}
    async function loadLast7DaysChart() {}
    async function updateIncomeChart() {}
    async function updateExpenseChart() {}
    async function loadMetrics() {}

    // 初始化
    window.addEventListener('DOMContentLoaded', async ()=>{

      buildYearMonthSelectors();
      loadAlertsPlaceholder();
      await loadLoggedInUser();
      await loadLast7DaysChart();
      await updateIncomeChart();
      await updateExpenseChart();
      await loadMetrics();

      // ✅ 載入真實班表
      await loadWeekSchedule();

      // 切換年月時更新圖與卡片（如果之後 C 級也加圖表就可以直接用）
      el('btnApplyMonth')?.addEventListener('click', async ()=>{
        await updateIncomeChart();
        await updateExpenseChart();
        await loadMetrics();
      });
    });
  </script>
</body>
</html>
