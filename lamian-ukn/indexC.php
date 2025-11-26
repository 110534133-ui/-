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
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);
      --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --card-shadow: 0 15px 35px rgba(0,0,0,.1);
      --hover-shadow: 0 25px 50px rgba(0,0,0,.15);
      --border-radius: 20px;
      --transition: all .3s cubic-bezier(.4,0,.2,1);
    }
    *{transition:var(--transition)}
    body{background:linear-gradient(135deg,#fff 0%,#fff 100%);font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;min-height:100vh}
    .sb-topnav{background:var(--dark-bg)!important;border:none;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    .navbar-brand{font-weight:700;font-size:1.5rem;background:linear-gradient(45deg,#fff,#fff);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}

    /* 搜尋框 */
    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container{ position:relative; display:flex; align-items:center; background:rgba(255,255,255,.15); border-radius:50px; padding:4px 4px 4px 20px; transition:all .3s cubic-bezier(.4,0,.2,1); backdrop-filter:blur(10px); border:2px solid transparent;}
    .search-container:hover{ background:rgba(255,255,255,.2); border-color:rgba(255,255,255,.3); transform:translateY(-1px); box-shadow:0 8px 20px rgba(0,0,0,.15);}
    .search-container:focus-within{ background:rgba(255,255,255,.25); border-color:rgba(255,255,255,.5); transform:translateY(-2px); box-shadow:0 10px 30px rgba(0,0,0,.2);}
    .search-input{ flex:1; border:none; outline:none; background:transparent; padding:10px 12px; font-size:14px; color:#fff; font-weight:500;}
    .search-input::placeholder{ color:rgba(255,255,255,.7); font-weight:400;}
    .search-btn{ background:linear-gradient(135deg, rgba(255,255,255,.9) 0%, rgba(255,255,255,.7) 100%); border:none; border-radius:40px; width:40px; height:40px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .3s ease; box-shadow:0 4px 12px rgba(0,0,0,.15); position:relative; overflow:hidden;}
    .search-btn::before{ content:''; position:absolute; top:50%; left:50%; width:0; height:0; border-radius:50%; background:rgba(251,185,124,.3); transform:translate(-50%,-50%); transition:width .6s, height .6s;}
    .search-btn:hover::before{ width:80px; height:80px;}
    .search-btn:hover{ transform:scale(1.08); box-shadow:0 6px 20px rgba(0,0,0,.25);}
    .search-btn:active{ transform:scale(.95);}
    .search-btn i{ color:#ff6b6b; font-size:16px; position:relative; z-index:1;}

    /* 側欄 */
    .sb-sidenav{background:linear-gradient(180deg,#fbb97ce4 0%,#ff00006a 100%)!important;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    .sb-sidenav-menu-heading{color:rgba(255,255,255,.7)!important;font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;padding:20px 15px 10px!important;margin-top:15px}
    .sb-sidenav .nav-link{border-radius:15px;margin:5px 15px;padding:12px 15px;position:relative;overflow:hidden;color:rgba(255,255,255,.9)!important;font-weight:500;backdrop-filter:blur(10px)}
    .sb-sidenav .nav-link:hover{background:rgba(255,255,255,.15)!important;transform:translateX(8px);box-shadow:0 8px 25px rgba(0,0,0,.2);color:#fff!important}
    .sb-sidenav .nav-link.active{background:rgba(255,255,255,.2)!important;color:#fff!important;font-weight:600;box-shadow:0 8px 25px rgba(0,0,0,.15)}
    .sb-sidenav .nav-link::before{content:'';position:absolute;left:0;top:0;height:100%;width:4px;background:linear-gradient(45deg,#fff,#fff);transform:scaleY(0);border-radius:0 10px 10px 0}
    .sb-sidenav .nav-link:hover::before,.sb-sidenav .nav-link.active::before{transform:scaleY(1)}
    .sb-sidenav .nav-link i{width:20px;text-align:center;margin-right:10px;font-size:1rem}
    .sb-sidenav-menu-nested .nav-link{padding-left:45px;font-size:.9rem;background:rgba(255,255,255,.05)!important;margin:2px 15px;border-radius:10px}
    .sb-sidenav-menu-nested .nav-link:hover{background:rgba(255,255,255,.1)!important;transform:translateX(5px);padding-left:50px}
    .sb-sidenav-footer{background:rgba(255,255,255,.1)!important;color:#fff!important;border-top:1px solid rgba(255,255,255,.2);padding:20px 15px;margin-top:20px}
    .sb-sidenav-footer .small{color:rgba(255,255,255,.7)!important;font-size:.8rem}

    .container-fluid{padding:30px!important}
    h1{background:var(--primary-gradient);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:2.5rem;margin-bottom:30px}
    .alert{border:none;border-radius:var(--border-radius);background:var(--warning-gradient);color:#fff;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    .card{border:none;border-radius:var(--border-radius);box-shadow:var(--card-shadow);backdrop-filter:blur(10px);background:rgba(255,255,255,.9);overflow:hidden;position:relative}
    .card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--primary-gradient)}
    .card:hover{transform:translateY(-10px);box-shadow:var(--hover-shadow)}
    .card-header{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7));border:none;padding:20px;font-weight:600;border-radius:var(--border-radius) var(--border-radius) 0 0!important}
    .card-body{padding:25px}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px;margin:30px 0}
    .stats-card{background:#fff;border-radius:var(--border-radius);padding:25px;box-shadow:var(--card-shadow);position:relative;overflow:hidden}
    .stats-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
    .stats-card.primary::before{background:var(--primary-gradient)}
    .stats-card.success::before{background:var(--success-gradient)}
    .stats-card.warning::before{background:var(--warning-gradient)}
    .stats-card.secondary::before{background:var(--secondary-gradient)}
    .stats-icon{width:60px;height:60px;border-radius:15px;display:flex;align-items:center;justify-content:center;margin-bottom:15px;font-size:24px;color:#fff}
    .stats-card.primary .stats-icon{background:var(--primary-gradient)}
    .stats-card.success .stats-icon{background:var(--success-gradient)}
    .stats-card.warning .stats-icon{background:var(--warning-gradient)}
    .stats-card.secondary .stats-icon{background:var(--secondary-gradient)}
    .stats-number{font-size:2rem;font-weight:700;color:#000;margin-bottom:5px;min-height:2.4rem}
    .stats-label{color:#7f8c8d;font-size:.9rem;font-weight:500}
    .table{border-radius:var(--border-radius);overflow:hidden;background:#fff;box-shadow:var(--card-shadow)}
    .table thead th{background:var(--primary-gradient);color:#000;border:none;font-weight:600;padding:15px;text-align:center}
    .table tbody td{padding:15px;vertical-align:middle;border-color:rgba(0,0,0,.05);text-align:center}
    .table tbody tr:hover{background:rgba(227,23,111,.05);transform:scale(1.01)}
    .breadcrumb{background:rgba(255,255,255,.8);border-radius:var(--border-radius);padding:15px 20px;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    footer{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7))!important;border-top:1px solid rgba(0,0,0,.1);backdrop-filter:blur(10px)}
    .loading-shimmer{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.6s infinite}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    .user-avatar{border:2px solid rgba(255,255,255,.5)}
    
    /* 班次樣式 */
    .badge-shift {
      display: inline-block;
      padding: 6px 12px;
      background: var(--success-gradient);
      color: white;
      border-radius: 8px;
      font-size: 13px;
      margin: 2px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .badge-off {
      display: inline-block;
      padding: 6px 12px;
      background: #6c757d;
      color: white;
      border-radius: 8px;
      font-size: 13px;
    }
    
    @media (max-width:768px){.container-fluid{padding:15px!important}.stats-grid{grid-template-columns:1fr;gap:15px}h1{font-size:2rem}}
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
      <div class="input-group">
        <input class="form-control" type="text" placeholder="Search for..." aria-label="Search" />
        <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
      </div>
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
        <div class="container-fluid px-4">
          <h1 class="mt-4">首頁儀表板</h1>
          <div class="mb-4">
            <span class="text-muted" id="currentDate">載入中...</span>
          </div>

          <!-- 系統通知 -->
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div>
              <strong>系統通知</strong><br>
              <span id="alertContent" class="loading-shimmer" style="display:inline-block;width:260px;height:1rem;border-radius:6px;"></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>

          <!-- 本週班表 -->
          <div class="card mb-4">
            <div class="card-header">
              <i class="fas fa-calendar-alt me-2"></i>本週班表總覽
              <span id="weekRangeText" class="float-end text-muted small"></span>
            </div>
            <div class="card-body">
              <div id="scheduleLoading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">載入中...</span>
                </div>
                <p class="mt-2 text-muted">正在載入班表資料...</p>
              </div>
              <div id="scheduleContent" class="table-responsive" style="display:none;">
                <table class="table table-hover">
                  <thead id="scheduleHeader"></thead>
                  <tbody id="scheduleBody"></tbody>
                </table>
              </div>
              <div id="scheduleError" class="alert alert-danger" style="display:none;"></div>
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

    // ✅ 修正：使用本地時間
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
    el('sidebarToggle')?.addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });

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
              <td class="text-start"><strong>${row.name || ''}</strong></td>
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

      // 切換年月時更新圖與卡片
      el('btnApplyMonth')?.addEventListener('click', async ()=>{
        await updateIncomeChart();
        await updateExpenseChart();
        await loadMetrics();
      });
    });
  </script>
</body>
</html>