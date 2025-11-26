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
      --border-radius: 20px;
    }
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    .sb-topnav {
      background: var(--dark-bg) !important;
      border: none;
      box-shadow: var(--card-shadow);
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      background: linear-gradient(45deg, #ffffff, #ffffff);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }
    .sb-sidenav {
      background: linear-gradient(180deg, #fbb97ce4 0%, #ff00006a 100%) !important;
      box-shadow: var(--card-shadow);
    }
    .sb-sidenav .nav-link {
      border-radius: 15px;
      margin: 5px 15px;
      padding: 12px 15px;
      color: rgba(255,255,255,.9) !important;
      font-weight: 500;
    }
    .sb-sidenav .nav-link:hover {
      background: rgba(255,255,255,.15) !important;
      transform: translateX(8px);
      color: #fff !important;
    }
    .container-fluid { padding: 30px !important; }
    h1 {
      background: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      font-weight: 700;
      font-size: 2.2rem;
    }
    .card {
      border: none;
      border-radius: var(--border-radius);
      box-shadow: var(--card-shadow);
      background: #fff;
    }
    .card-header {
      background: linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7));
      font-weight: 600;
    }
    .table thead th {
      background: var(--primary-gradient);
      color: #000;
      border: none;
      font-weight: 600;
      padding: 12px;
    }
    .badge-status { border-radius: 999px; padding: .35rem .6rem; }
    .badge-normal { background: rgba(25,135,84,.15); color: #0f5132; }
    .badge-ot { background: rgba(13,110,253,.15); color: #084298; }
    .badge-missing { background: rgba(220,53,69,.15); color: #842029; }
    .user-info-card {
      background: var(--primary-gradient);
      color: white;
      padding: 20px;
      border-radius: 16px;
      margin-bottom: 24px;
      box-shadow: 0 8px 24px rgba(251,185,124,.3);
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
      <i class="fas fa-bars"></i>
    </button>

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
            <!-- ✅ 直接平鋪的按鈕：不使用 collapse -->
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
          <h1 class="mb-4">我的打卡記錄</h1>

          <!-- 用戶資訊卡片 -->
          <div class="user-info-card">
            <div class="d-flex align-items-center">
              <i class="fas fa-user-circle fa-3x me-3"></i>
              <div>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($userName); ?></div>
                <div class="small opacity-90">員工編號：<?php echo htmlspecialchars($userId); ?></div>
              </div>
            </div>
          </div>

          <!-- 統計卡片 -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="card text-white" style="background: var(--success-gradient);">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small">總工時（小時）</div>
                      <div class="h5" id="sum_hours">0.00</div>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-white" style="background: var(--primary-gradient);">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small">出勤筆數</div>
                      <div class="h5" id="sum_records">0</div>
                    </div>
                    <i class="fas fa-list-check fa-2x opacity-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-white" style="background: var(--warning-gradient);">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small">缺卡筆數</div>
                      <div class="h5" id="sum_missing">0</div>
                    </div>
                    <i class="fas fa-triangle-exclamation fa-2x opacity-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-white" style="background: var(--secondary-gradient);">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small">加班（小時）</div>
                      <div class="h5" id="sum_ot">0.00</div>
                    </div>
                    <i class="fas fa-bolt fa-2x opacity-50"></i>
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
                <div class="col-12 text-end">
                  <button class="btn btn-primary" id="btnSearch">
                    <i class="fas fa-search me-1"></i>查詢
                  </button>
                  <button class="btn btn-secondary" id="btnClear">
                    <i class="fas fa-eraser me-1"></i>清除
                  </button>
                  <button class="btn btn-success" id="btnExport">
                    <i class="fas fa-file-export me-1"></i>匯出CSV
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
            <div class="text-muted">Copyright &copy; Xxing0625</div>
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
      
      // ⭐ 關鍵：自動帶入當前用戶 ID 進行篩選
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

    function exportCSV() {
      if(!FILTERED.length) {
        alert('目前沒有可匯出的資料');
        return;
      }
      
      const headers = ['日期','上班時間','下班時間','工時','狀態','備註'];
      const rows = FILTERED.map(r => {
        const mins = minutesBetween(r.clock_in, r.clock_out);
        const st = inferStatus(r.clock_in, r.clock_out, mins);
        return [
          r.date||'', r.clock_in||'', r.clock_out||'',
          hr2(mins), st, r.note||''
        ];
      });
      
      const csv = [headers, ...rows].map(cols =>
        cols.map(v => `"${String(v??'').replace(/"/g,'""')}"`).join(',')
      ).join('\r\n');

      const blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = '我的打卡記錄_'+(new Date().toISOString().slice(0,10))+'.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
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
      document.getElementById('btnExport').addEventListener('click', exportCSV);
    });
  </script>
    <script src="js/user-avatar-loader.js"></script>
</body>
</html>