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
    * { transition: var(--transition); }
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }

    .sb-topnav{background:var(--dark-bg)!important; border:none; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#ffffff,#ffffff);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
    }

    .sb-sidenav{background:linear-gradient(180deg,#fbb97ce4 0%,#ff00006a 100%)!important; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .sb-sidenav-menu-heading{color:rgba(255,255,255,.7)!important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px!important; margin-top:15px}
    .sb-sidenav .nav-link{border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter:blur(10px)}
    .sb-sidenav .nav-link:hover{background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important}
    .sb-sidenav .nav-link.active{background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15)}
    .sb-sidenav .nav-link::before{content:''; position:absolute; left:0; top:0; height:100%; width:4px; background:linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0}
    .sb-sidenav .nav-link:hover::before,.sb-sidenav .nav-link.active::before{transform:scaleY(1)}
    .sb-sidenav .nav-link i{width:20px; text-align:center; margin-right:10px; font-size:1rem}
    .sb-sidenav-footer{background:rgba(255,255,255,.1)!important; color:#fff!important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px}

    .container-fluid { padding: 30px !important; }
    h1 {
      background: var(--primary-gradient);
      background-clip: text; -webkit-background-clip: text;
      color: transparent; -webkit-text-fill-color: transparent;
      font-weight: 700; font-size: 2.5rem; margin-bottom: 30px;
    }
    .breadcrumb { background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card { border: none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background: #fff; overflow: hidden; }
    .card-header { background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight: 600; }

    .table { border-radius: var(--border-radius); overflow: hidden; background: #fff; box-shadow: var(--card-shadow); }
    .table thead th { background: var(--primary-gradient); color: #000; border: none; font-weight: 600; padding: 15px; }
    .table tbody td, .table tbody th { padding: 12px; vertical-align: middle; border-color: rgba(0,0,0,.05); }
    .table-hover tbody tr:hover { background: rgba(227,23,111,.05); transform: scale(1.01); }

    .badge-shift { display:inline-block; min-width:72px; padding:.35rem .6rem; border-radius: 999px; background: rgba(102,126,234,.12); border:1px solid rgba(102,126,234,.25); }
    .badge-off   { display:inline-block; padding:.35rem .6rem; border-radius: 999px; background: rgba(0,0,0,.05); border:1px dashed rgba(0,0,0,.15); }

    .sb-topnav .form-control{border-radius:25px; border:2px solid transparent; background:rgba(255,255,255,.2); color:#fff}
    .sb-topnav .form-control:focus{background:rgba(255,255,255,.3); border-color:rgba(255,255,255,.5); box-shadow:0 0 20px rgba(255,255,255,.2); color:#fff}

    .btn-primary{background:var(--primary-gradient); border:none; border-radius:25px}
    .btn-primary:hover{transform:scale(1.05); box-shadow:0 10px 25px rgba(102,126,234,.976)}

    .range-item .input-group-text{min-width:2.5rem; justify-content:center}
    .range-item .form-control{min-width:6.5rem}

    .badge-shift {
      display: inline-block;
      background-color: #0d6efd;
      color: #fff;
      padding: 2px 6px;
      border-radius: 6px;
      font-size: 0.85rem;
      margin-bottom: 2px;
    }
    .badge-off {
      display: inline-block;
      background-color: #6c757d;
      color: #fff;
      padding: 2px 6px;
      border-radius: 6px;
      font-size: 0.85rem;
    }
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
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>班表填報 (下週)</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.html" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">班表</li>
          </ol>

          <!-- 週切換與下載圖片 -->
          <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="btn-group" role="group" aria-label="week switch">
              <button class="btn btn-outline-secondary" id="btnPrevWeek"><i class="fas fa-chevron-left me-1"></i>上週</button>
              <button class="btn btn-outline-secondary" id="btnNextWeek">下週<i class="fas fa-chevron-right ms-1"></i></button>
              <button class="btn btn-outline-secondary" id="btnNextNextWeek">再下週<i class="fas fa-chevron-right ms-1"></i></button>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
              <span class="text-muted">週期:</span>
              <strong id="weekRangeText">--</strong>
              <button class="btn btn-primary ms-3" id="btnDownloadPng"><i class="fas fa-image me-2"></i>下載班表圖片</button>
            </div>
          </div>

          <!-- 唯讀:本週班表 -->
          <div class="card mb-4" id="scheduleViewCard">
            <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>當前週班表(唯讀)</div>
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
                    <label class="form-label">填報週(自動為下週一)</label>
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

    /* ====== 🔥 修正版:不使用 toISOString(),避免時區問題 ====== */
    
    /**
     * 格式化日期為 YYYY-MM-DD
     * 🔥 關鍵修正:使用本地時間,不使用 UTC
     */
    function fmt(d) {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      const result = `${year}-${month}-${day}`;
      
      console.log('📅 fmt() - 輸入:', d.toLocaleDateString('zh-TW'), '→ 輸出:', result);
      return result;
    }

    /**
     * 取得指定日期所在週的週一
     */
    function getMonday(d = new Date()) {
      const date = new Date(d);
      date.setHours(0, 0, 0, 0);
      
      const dayOfWeek = date.getDay();
      
      console.log('📝 getMonday - 輸入日期:', date.toLocaleDateString('zh-TW'), '星期' + ['日','一','二','三','四','五','六'][dayOfWeek]);
      
      let daysToSubtract;
      if (dayOfWeek === 0) {
        daysToSubtract = 6;
      } else {
        daysToSubtract = dayOfWeek - 1;
      }
      
      date.setDate(date.getDate() - daysToSubtract);
      
      console.log('✅ getMonday - 計算結果:', date.toLocaleDateString('zh-TW'), fmt(date));
      
      return date;
    }

    function addDays(d, n){ 
      const x = new Date(d); 
      x.setDate(x.getDate() + n); 
      return x; 
    }

    function rangeMonToSun(monday){
      const arr = [];
      for(let i=0; i<7; i++){ 
        arr.push(addDays(monday,i)); 
      }
      return arr;
    }

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

      if(!data || !Array.isArray(data.rows) || data.rows.length===0){
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
      
      setTimeout(() => {
        slot.innerHTML = '';
      }, 3000);
    }

    async function submitAvailability(e){
      e.preventDefault();
      
      const weekStartStr = document.getElementById('weekStartInput').value;
      if(!weekStartStr){
        showFormMsg('請先選擇「填報週」', 'danger'); 
        return;
      }
      
      // 🔥 關鍵:從 input[type="date"] 讀取時,直接解析為本地日期
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
    // 🔥 預設顯示下週 (往後推7天)
    let currentMonday = addDays(getMonday(new Date()), 7);

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
      document.getElementById('weekStartInput').value = fmt(currentMonday);
    }

    /* ====== 初始化 ====== */
    window.addEventListener('DOMContentLoaded', async () => {
      const today = new Date();
      console.log('🚀 頁面初始化');
      console.log('  - 今天(本地):', today.toLocaleDateString('zh-TW'));
      console.log('  - 今天(Date物件):', today);
      console.log('  - 預設顯示下週一:', currentMonday.toLocaleDateString('zh-TW'), fmt(currentMonday));
      
      document.getElementById('btnPrevWeek').addEventListener('click', async () => { 
        currentMonday = addDays(currentMonday, -7); 
        await refreshAll(); 
      });
      
      document.getElementById('btnNextWeek').addEventListener('click', async () => { 
        currentMonday = addDays(getMonday(new Date()), 7); // 回到下週
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