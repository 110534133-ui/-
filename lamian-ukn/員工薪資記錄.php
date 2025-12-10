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
  <title>薪資記錄 - 員工管理系統</title>

  <!-- 依賴 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 全站基礎風格：跟日報表紀錄 / 請假申請一致 ====== */
    :root {
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
      --text-main: #0f172a;
      --text-subtle: #64748b;

      --card-bg: rgba(255, 255, 255, 0.96);
      --card-radius: 22px;

      --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.12);
      --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

      --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);

      --primary-gradient: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      --secondary-gradient: linear-gradient(135deg, #38bdf8 0%, #6366f1 100%);
      --success-gradient: linear-gradient(135deg, #4398ffff 0%, #c4ebfcff 100%);
      --warning-gradient: linear-gradient(135deg, #facc15 0%, #fb923c 100%);
      --dark-bg: linear-gradient(120deg, #1e3a8a, #3658ff);
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
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "Microsoft JhengHei", sans-serif;
      color: var(--text-main);
    }

    /* ====== 頂欄 ====== */
    .sb-topnav {
      background: var(--dark-bg) !important;
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

    /* ====== 側欄 ====== */
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

    /* 修正側欄 icon / ::after 顏色 */
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
      box-shadow: var(--shadow-soft);
    }

    .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
      color: #9ca3af;
    }

    /* ====== 卡片 / 表單 ====== */
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

    .form-control,
    .form-select {
      border-radius: 12px;
    }

    /* ====== 表格 ====== */
    .table {
      border-radius: var(--card-radius);
      overflow: hidden;
      background: #fff;
      box-shadow: var(--shadow-soft);
    }

    .table thead th {
      background: var(--primary-gradient);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: 12px 15px;
      text-align: center;
    }

    .table tbody td {
      padding: 12px 15px;
      vertical-align: middle;
      border-color: rgba(226, 232, 240, 0.9);
      text-align: center;
      white-space: nowrap;
    }

    .table tbody td.text-start {
      text-align: left;
      white-space: normal;
    }

    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
      transform: translateY(-1px);
    }

    /* ====== 按鈕 ====== */
    .btn-primary {
      background: var(--primary-gradient);
      border: none;
      border-radius: 999px;
      padding-inline: 20px;
      font-weight: 600;
    }
    .btn-primary:hover {
      filter: brightness(1.03);
      box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
      transform: translateY(-1px);
    }

    /* ====== 統計摘要卡片 ====== */
    .stat-card {
      border: none;
      color: #fff;
      border-radius: var(--card-radius);
      background: var(--success-gradient);
      box-shadow: var(--shadow-soft);
      position: relative;
      overflow: hidden;
    }
    .stat-card .card-body {
      padding: 1.1rem 1.25rem;
    }
    .stat-label {
      font-size: .85rem;
      opacity: .9;
    }
    .stat-value {
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .stat-icon {
      font-size: 2.2rem;
      opacity: .35;
    }
    .stat-glow {
      position: absolute;
      right: -30px;
      top: -30px;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,.18);
      filter: blur(12px);
    }
    .stat-success { background: var(--success-gradient); }

    /* ====== 遮蔽顯示 ====== */
    .masked {
      letter-spacing: .06em;
    }
    .reveal-toggle {
      border: none;
      border-radius: 25px;
      background: var(--secondary-gradient);
      color: #fff;
      padding: .5rem .9rem;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .reveal-toggle:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 25px rgba(0,0,0,.12);
    }

    .badge-paytype {
      font-size: .75rem;
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
            <!-- 直接平鋪的按鈕：不使用 collapse -->
            <a class="nav-link" href="新增班表.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表
            </a>
            <a class="nav-link" href="新增請假申請.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請
            </a>
            <a class="nav-link active" href="員工薪資記錄.php">
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

    <!-- Content -->
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

          <!-- 載入 / 訊息 -->
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

          <!-- 當月摘要 + 顯示按鈕 -->
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

          <!-- 歷史薪資 -->
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

      <!-- 詳細資料 Modal -->
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

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 頂欄日期 & 側欄收合
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    /* ===== API(連接真實資料庫) ===== */
    class APIClient {
      static async request(action, data = {}) {
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
      // 歷史:按年份
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

    document.addEventListener('DOMContentLoaded', async ()=>{
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
    function renderHistory(list){
      const tbody = $('historyBody');
      const noRow = $('noHistoryRow');
      
      // 先移除所有資料行（但保留 noHistoryRow）
      const rows = tbody.querySelectorAll('tr:not(#noHistoryRow)');
      rows.forEach(row => row.remove());
      
      if(!list || list.length === 0) {
        if(noRow) noRow.classList.remove('d-none');
        return;
      }
      
      if(noRow) noRow.classList.add('d-none');

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
  <script src="js/user-avatar-loader.js"></script>
</body>
</html>
