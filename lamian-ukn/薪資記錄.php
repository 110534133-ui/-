<?php
// 🔥 整合：加入權限檢查
// 這裡是員工個人頁面，只需要確認 "已登入"
// auth_check.php 會自動檢查登入，如果未登入會導向 login.html
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 整合：取得用戶資訊 (用於頂部導覽列)
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

// 🔥 整合：定義 API 路徑 (給 JS 使用)
$API_BASE_URL  = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>薪資記錄 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%); /* 首頁同色 */
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

    /* 頂欄（跟首頁一致） */
    .sb-topnav {
      background: var(--dark-bg) !important;
      border: none;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      background: linear-gradient(45deg, #ffffff, #ffffff);
      /* 修復 Lint：標準屬性 + 前綴 + 透明文字 */
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
    }

    /* 側欄（跟首頁一致） */
    .sb-sidenav {
      background: linear-gradient(180deg, #fbb97ce4 0%, #ff00006a 100%) !important;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
    }
    .sb-sidenav-menu-heading {
      color: rgba(255,255,255,.7) !important;
      font-weight: 600;
      font-size: .85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 20px 15px 10px 15px !important;
      margin-top: 15px;
    }
    .sb-sidenav .nav-link {
      border-radius: 15px;
      margin: 5px 15px;
      padding: 12px 15px;
      position: relative;
      overflow: hidden;
      color: rgba(255,255,255,.9) !important;
      font-weight: 500;
      backdrop-filter: blur(10px);
    }
    .sb-sidenav .nav-link:hover {
      background: rgba(255,255,255,.15) !important;
      transform: translateX(8px);
      box-shadow: 0 8px 25px rgba(0,0,0,.2);
      color: #fff !important;
    }
    .sb-sidenav .nav-link.active {
      background: rgba(255,255,255,.2) !important;
      color: #fff !important;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(0,0,0,.15);
    }
    .sb-sidenav .nav-link::before {
      content: '';
      position: absolute; left: 0; top: 0; height: 100%; width: 4px;
      background: linear-gradient(45deg, #ffffff, #ffffff); /* 和首頁相同：白色亮條 */
      transform: scaleY(0);
      transition: var(--transition);
      border-radius: 0 10px 10px 0;
    }
    .sb-sidenav .nav-link:hover::before,
    .sb-sidenav .nav-link.active::before { transform: scaleY(1); }

    .sb-sidenav .nav-link i { width: 20px; text-align: center; margin-right: 10px; font-size: 1rem; }
    .sb-sidenav-footer {
      background: rgba(255,255,255,.1) !important;
      color: #fff !important;
      border-top: 1px solid rgba(255,255,255,.2);
      padding: 20px 15px;
      margin-top: 20px;
    }
    
    /* [!! 新增 !!] 新版導覽列 CSS */
    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container {
        position: relative; display: flex; align-items: center;
        background: rgba(255, 255, 255, 0.15); border-radius: 50px;
        padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px); border: 2px solid transparent;
    }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); }
    .search-input {
        flex: 1; border: none; outline: none; background: transparent;
        padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500;
    }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        border: none; border-radius: 40px; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn i { color: #ff6b6b; font-size: 16px; }
    .user-avatar{border:2px solid rgba(255,255,255,.5)}
    
    /* 內容區 */
    .container-fluid{ padding:30px !important; }
    h1{
      background: var(--primary-gradient);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight:700; font-size:2.5rem; margin-bottom:30px;
    }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.95), rgba(255,255,255,.75)); font-weight:600; }
    .form-control, .form-select{ border-radius:12px; }
    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }

    /* 表格 */
    .table{ border-radius:var(--border-radius); overflow:hidden; background:#fff; box-shadow:var(--card-shadow); }
    .table thead th{ background:var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color:rgba(0,0,0,.05); }
    .table tbody tr:hover{ background:rgba(227,23,111,.05); transform:scale(1.01); }

    /* 統計摘要（與日報表記錄一致） */
    .stat-card{ border:none; color:#fff; border-radius:var(--border-radius); background:#999; box-shadow:var(--card-shadow); position:relative; overflow:hidden; }
    .stat-card .card-body{ padding:1.1rem 1.25rem; }
    .stat-label{ font-size:.85rem; opacity:.9; }
    .stat-value{ font-size:1.6rem; font-weight:700; line-height:1.2; }
    .stat-icon{ font-size:2.2rem; opacity:.35; }
    .stat-glow{ position:absolute; right:-30px; top:-30px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,.15); filter:blur(12px); }
    .stat-primary{  background: var(--primary-gradient);  }
    .stat-success{  background: var(--success-gradient);  }
    .stat-warning{  background: var(--warning-gradient);  }
    .stat-secondary{ background: var(--secondary-gradient); }

    /* 遮蔽（米字號） */
    .masked{ letter-spacing:.06em; }
    .reveal-toggle{ border:none; border-radius:25px; background:var(--secondary-gradient); color:#fff; padding:.5rem .9rem; }
    .reveal-toggle:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(0,0,0,.12); }

    .badge-paytype{ font-size:.75rem; }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
        <i class="fas fa-bars"></i>
      </button>

      <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
          <div class="search-container-wrapper">
              <div class="search-container">
                  <input class="search-input" type="text" placeholder="搜尋員工、班表、薪資..." aria-label="Search" />
                  <button class="search-btn" id="btnNavbarSearch" type="button">
                      <i class="fas fa-search"></i>
                  </button>
              </div>
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
            <a class="nav-link" href="index.html">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <a class="nav-link active" href="薪資記錄.php">
              <div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄
            </a>
            <a class="nav-link" href="班表.html">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表
            </a>
            <a class="nav-link" href="請假申請.html">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請
            </a>
          </div>
        </div>
        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          <?php echo htmlspecialchars($userName ?? 'User'); ?>
        </div>
      </nav>
    </div>

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>薪資記錄</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.html" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">薪資記錄</li>
          </ol>

          <div id="loadingIndicator" class="d-none">
            <div class="d-flex justify-content-center align-items-center mb-4">
              <div class="spinner-border text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div>
              <span>載入中...</span>
            </div>
          </div>
          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>

          <div class="card stat-card stat-success mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <div class="stat-label">本月（<span id="currentMonthText"></span>）實領</div>
                <div class="stat-value">
                  <span id="currentMonthAmount" class="masked">＊＊＊＊＊</span>
                </div>
                <div class="mt-2 small">
                  <span class="me-3">底薪 / 時薪：<span id="cm_base" class="masked">＊＊＊</span></span>
                  <span class="me-3">工時：<span id="cm_hours" class="masked">＊＊＊</span></span>
                  <span class="me-3">獎金：<span id="cm_bonus" class="masked">＊＊＊</span></span>
                  <span>扣款：<span id="cm_ded" class="masked">＊＊＊</span></span>
                </div>
              </div>
              <div class="text-end">
                <button id="toggleRevealBtn" class="reveal-toggle">
                  <i class="fas fa-eye me-1"></i> 顯示金額
                </button>
                <div class="mt-2">
                  <button class="btn btn-light btn-sm" id="openDetailBtn">
                    <i class="fas fa-receipt me-1"></i> 查看明細
                  </button>
                </div>
              </div>
            </div>
            <span class="stat-glow"></span>
          </div>

          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><i class="fas fa-clock-rotate-left me-1"></i> 歷史薪資</div>
              <div class="text-muted small">僅顯示您個人的薪資資料</div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>月份</th>
                      <th>薪資類型</th>
                      <th>底薪/時薪</th>
                      <th>工時</th>
                      <th>獎金</th>
                      <th>扣款</th>
                      <th>實領</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="historyBody">
                    <tr id="noHistoryRow" class="d-none">
                      <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>尚無歷史資料
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>薪資詳情</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
          </div>
        </div>
      </div>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div>
              <a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  
  <script>
    const API_BASE = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
  </script>
  
  <script>
    // 頂欄日期 & 側欄收合
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-nav-toggled');
    });

    /* ===== API（連接真實資料庫） ===== */
    class APIClient {
      static async request(action, data = {}) {
        // [!! 修正 !!] 呼叫我們統一的 API 檔案
        const resp = await fetch('薪資管理_api.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action, ...data })
        });
        
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        
        const result = await resp.json();
        if (!result.success) throw new Error(result.message || '請求失敗');
        
        return result;
      }
      // 當月
      static getMySalary(month){ return this.request('fetch_my_detail', { month }); }
      // 歷史：按年份
      static getMyHistory(year){ return this.request('fetch_my_records', { year }); }
      // 明細
      static getDetail(month){ return this.request('fetch_my_detail', { month }); }
    }

    /* ===== Helpers ===== */
    const $ = (id)=>document.getElementById(id);
    function showLoading(show=true){ $('loadingIndicator').classList.toggle('d-none', !show); }
    function showError(msg){ const a=$('errorAlert'); $('errorMessage').textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 5000); }
    const currency = (n)=> new Intl.NumberFormat('zh-TW',{style:'currency',currency:'TWD',minimumFractionDigits:0}).format(n||0);

    // 米字號遮蔽（依位數）
    function maskStars(n){ const digits = Math.max(String(Math.round(Math.abs(n||0))).length, 3); return '＊'.repeat(digits); }

    // 金額/數字分開處理
    let revealed = false;
    function setMaskedMoney(el, value){
      el.dataset.raw = value ?? 0;
      el.textContent = revealed ? currency(Number(value||0)) : maskStars(Number(value||0));
      el.classList.toggle('masked', !revealed);
    }
    function setMaskedNumber(el, value, digits=2){
      const v = Number(value||0);
      el.dataset.raw = v;
      el.textContent = revealed ? v.toFixed(digits) : maskStars(v);
      el.classList.toggle('masked', !revealed);
    }

    function calcBasePay(row){
      if (!row) return 0;
      
      // 如果有時薪且時薪大於0，計算時薪*工時
      if (row.hourly_rate != null && row.hourly_rate !== undefined && row.hourly_rate > 0) {
        return Math.round((row.hourly_rate || 0) * (row.working_hours || 0));
      }
      
      // 否則返回月薪
      return row.base_salary || 0;
    }
    function payTypeBadge(row){
      if (!row) return '';
      
      const isHourly = (row.hourly_rate != null && row.hourly_rate !== undefined && row.hourly_rate > 0);
      return isHourly
        ? '<span class="badge bg-info badge-paytype"><i class="fas fa-clock me-1"></i>時薪</span>'
        : '<span class="badge bg-secondary badge-paytype"><i class="fas fa-briefcase me-1"></i>月薪</span>';
    }

    /* ===== 初始化 ===== */
    const now = new Date();
    const currentYM = now.toISOString().slice(0,7);
    const currentYear = now.getFullYear();
    $('currentMonthText').textContent = currentYM;

    // [!! 修正 !!] 在頂層宣告 '尚無資料' 的模板變數
    let noHistoryRowTemplate;

    document.addEventListener('DOMContentLoaded', async ()=>{
      // [!! 修正 !!] 頁面載入時，快取 '尚無資料' 的模板，並從 DOM 移除
      noHistoryRowTemplate = document.getElementById('noHistoryRow');
      if (noHistoryRowTemplate) {
          noHistoryRowTemplate.remove(); // 從 DOM 移除，保存到變數中
          noHistoryRowTemplate.classList.remove('d-none'); // 確保它在未來加入時是可見的
      }

      try{
        showLoading(true);
        await loadCurrent();
        await loadHistory();
      }catch(e){
        console.error('載入錯誤:', e); 
        showError('載入薪資資料失敗: ' + e.message);
      }finally{
        showLoading(false);
      }
    });

    // 顯示/隱藏按鈕
    $('toggleRevealBtn').addEventListener('click', ()=>{
      revealed = !revealed;
      $('toggleRevealBtn').innerHTML = revealed
        ? '<i class="fas fa-eye-slash me-1"></i> 隱藏金額'
        : '<i class="fas fa-eye me-1"></i> 顯示金額';
      if (currentRowCache) renderCurrent(currentRowCache);
      if (historyCache && historyCache.length > 0) renderHistory(historyCache);
    });

    // 查看當月明細
    $('openDetailBtn').addEventListener('click', ()=> openDetail(currentYM));

    /* ===== 當月 ===== */
    let currentRowCache = null;
    async function loadCurrent(){
      try {
        const r = await APIClient.getMySalary(currentYM);
        currentRowCache = r.record || {};
        renderCurrent(currentRowCache);
      } catch (e) {
        console.error('載入當月薪資失敗:', e);
        // 如果當月沒有資料，顯示空值
        currentRowCache = {
          base_salary: 0,
          hourly_rate: 0,
          working_hours: 0,
          bonus: 0,
          deductions: 0,
          total_salary: 0
        };
        renderCurrent(currentRowCache);
      }
    }
    function renderCurrent(row){
      if (!row) return;
      
      const calcBase = calcBasePay(row);
      const total = (row.total_salary != null && row.total_salary !== undefined) 
        ? row.total_salary 
        : (calcBase + (row.bonus || 0) - (row.deductions || 0));
      
      setMaskedMoney($('currentMonthAmount'), total);
      
      // 金額 - 根據薪資類型顯示底薪或時薪
      const baseValue = (row.hourly_rate != null && row.hourly_rate > 0) 
        ? row.hourly_rate 
        : row.base_salary;
      setMaskedMoney($('cm_base'), baseValue || 0);
      setMaskedMoney($('cm_bonus'), row.bonus || 0);
      setMaskedMoney($('cm_ded'), row.deductions || 0);
      
      // 工時
      setMaskedNumber($('cm_hours'), row.working_hours || 0, 2);
    }

    /* ===== 歷史 ===== */
    let historyCache = [];
    async function loadHistory(){
      try {
        const data = await APIClient.getMyHistory(currentYear);
        historyCache = data.records || [];
        renderHistory(historyCache);
      } catch (e) {
        console.error('載入歷史記錄失敗:', e);
        historyCache = [];
        renderHistory(historyCache);
      }
    }
    
    // [!! 修正 !!] 修改 renderHistory 函式
    function renderHistory(list){
      const tbody = $('historyBody');
      // const no = $('noHistoryRow'); // <-- 移除此行
      tbody.innerHTML = '';
      
      if(!list || list.length===0){
        if(noHistoryRowTemplate) { // <-- 使用快取的模板
            tbody.appendChild(noHistoryRowTemplate);
        }
        return; 
      }
      // no.classList.add('d-none'); // <-- 移除此行

      list.forEach(item=>{
        const calcBase = calcBasePay(item);
        const calcTotal = (item.total_salary != null && item.total_salary !== undefined) 
          ? item.total_salary 
          : (calcBase + (item.bonus || 0) - (item.deductions || 0));
        
        // 底薪/時薪顯示
        const isHourly = (item.hourly_rate != null && item.hourly_rate > 0);
        const baseValue = isHourly ? item.hourly_rate : (item.base_salary || 0);
        const baseDisplay = isHourly
          ? (revealed ? currency(baseValue) : maskStars(baseValue)) + '/時'
          : (revealed ? currency(baseValue) : maskStars(baseValue)) + '/月';
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${item.salary_month||'-'}</td>
          <td>${payTypeBadge(item)}</td>
          <td>${baseDisplay}</td>
          <td>${revealed? (Number(item.working_hours||0)).toFixed(2) : maskStars(item.working_hours||0)}</td>
          <td>${revealed? currency(item.bonus||0) : maskStars(item.bonus||0)}</td>
          <td>${revealed? currency(item.deductions||0) : maskStars(item.deductions||0)}</td>
          <td><strong>${revealed? currency(calcTotal) : maskStars(calcTotal)}</strong></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-info" onclick="openDetail('${item.salary_month||''}')" title="查看明細">
              <i class="fas fa-eye"></i>
            </button>
          </td>`;
        tbody.appendChild(tr);
      });
    }

    /* ===== 明細 Modal ===== */
    async function openDetail(month){
      if (!month) {
        showError('月份參數錯誤');
        return;
      }
      
      try{
        showLoading(true);
        const d = await APIClient.getDetail(month);
        const s = d.record || {};
        const calcBase = calcBasePay(s);
        const calcTot  = (s.total_salary!=null && s.total_salary!==undefined)? s.total_salary : (calcBase + (s.bonus||0) - (s.deductions||0));
        const fmtMoney = (n)=> revealed? currency(n) : maskStars(n);
        const fmtHours = (n)=> revealed? (Number(n||0)).toFixed(2) : maskStars(n);
        
        const isHourly = (s.hourly_rate != null && s.hourly_rate > 0);
        const baseValue = isHourly ? s.hourly_rate : (s.base_salary || 0);

        $('detailBody').innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-primary"><i class="fas fa-info-circle me-1"></i> 基本資訊</h6>
              <table class="table table-sm table-borderless">
                <tr><td class="fw-bold">月份：</td><td>${s.salary_month||month}</td></tr>
                <tr><td class="fw-bold">薪資類型：</td><td>${isHourly?'時薪':'月薪'}</td></tr>
                <tr><td class="fw-bold">工時：</td><td>${fmtHours(s.working_hours||0)}</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6 class="text-success"><i class="fas fa-calculator me-1"></i> 薪資計算</h6>
              <table class="table table-sm table-borderless">
                <tr><td class="fw-bold">${isHourly?'時薪：':'月薪：'}</td><td>${fmtMoney(baseValue)}</td></tr>
                <tr><td class="fw-bold">計算底薪：</td><td>${fmtMoney(calcBase)}</td></tr>
                <tr><td class="fw-bold">獎金：</td><td class="text-success">+${fmtMoney(s.bonus||0)}</td></tr>
                <tr><td class="fw-bold">扣款：</td><td class="text-danger">-${fmtMoney(s.deductions||0)}</td></tr>
                <tr class="table-success border-top">
                  <td class="fw-bold">實領薪資：</td>
                  <td class="fw-bold fs-5">${fmtMoney(calcTot)}</td>
                </tr>
              </table>
            </div>
          </div>
          ${s.created_at ? `
            <div class="text-center mt-3">
              <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                建立時間: ${new Date(s.created_at).toLocaleString('zh-TW')}
              </small>
            </div>
          ` : ''}
        `;
        new bootstrap.Modal(document.getElementById('detailModal')).show();
      }catch(e){
        console.error('讀取明細失敗:', e); 
        showError('讀取明細失敗: ' + e.message);
      }finally{
        showLoading(false);
      }
    }
  </script>

  <script src="js/scripts.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        loadLoggedInUser();
    });

    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('✅ 薪資記錄 已登入:', userName, 'ID:', userId);
        
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
</body>
</html>