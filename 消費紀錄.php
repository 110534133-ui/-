
<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: ../shanhe/login.html");
    exit;
}

require_once __DIR__ . '/config.php'; // 保證無論怎麼 call 都正確

// 防止快取
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>消費紀錄 - 會員管理系統</title>

  <!-- 與其他頁一致 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);/* 儀表板，員工，查詢，排班顏色 */
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);/* 今日出勤 */
      --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);/* 系統通知顏色 */
      --dark-bg: linear-gradient(135deg,rgba(242, 189, 114, 0.21) 0%,rgba(249, 177, 77, 0.57) 100%);
      --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      --hover-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      --border-radius: 20px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    * { transition: var(--transition); }
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);/* 背景顏色 */
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    /* Enhanced Navigation */
    .sb-topnav {
      background: var(--dark-bg) !important;
      border: none;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      background: linear-gradient(45deg,rgb(0, 0, 0), #ffffff);/* 管理系統標題 */
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
    }
    /* Sidebar Enhancement */
    .sb-sidenav {
      background: linear-gradient(180deg, #fff9f0 100%,rgba(237, 165, 165, 0.42) 100%) !important;/* 側欄位 */
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
    }
    .sb-sidenav-menu-heading {
      color: rgba(0, 0, 0, 0.7) !important;/* 側欄小標文字 */
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 20px 15px 10px 15px !important;
      margin-top: 15px;
    }
    .sb-sidenav .nav-link {
      border-radius: 15px;
      margin: 5px 15px;
      padding: 12px 15px;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      color: rgba(0, 0, 0, 0.9) !important;/* 側欄文字顏色 */
      font-weight: 500;
      backdrop-filter: blur(10px);
    }
    .sb-sidenav .nav-link:hover {
      background: rgba(0, 0, 0, 0.15) !important;
      transform: translateX(8px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      color: white !important;
    }
    .sb-sidenav .nav-link.active {
      background: rgba(0, 0, 0, 0.2) !important;
      color: white !important;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .sb-sidenav .nav-link::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background: linear-gradient(45deg,rgb(0, 0, 0),rgb(0, 0, 0));/* 側欄按鈕圖標 */
      transform: scaleY(0);
      transition: var(--transition);
      border-radius: 0 10px 10px 0;
    }
    .sb-sidenav .nav-link:hover::before,
    .sb-sidenav .nav-link.active::before { transform: scaleY(1); }
    .sb-sidenav .nav-link i { width: 20px; text-align: center; margin-right: 10px; font-size: 1rem; }
    .sb-sidenav-collapse-arrow { transition: var(--transition); }
    .sb-sidenav .nav-link[aria-expanded="true"] .sb-sidenav-collapse-arrow { transform: rotate(180deg); }
    /* Nested Navigation */
    .sb-sidenav-menu-nested .nav-link {
      padding-left: 45px;
      font-size: 0.9rem;
      background: rgba(76, 27, 27, 0.05) !important;/* 側欄按鈕顏色 */
      margin: 2px 15px;
      border-radius: 10px;
    }
    .sb-sidenav-menu-nested .nav-link:hover {
      background: rgba(255, 255, 255, 0.1) !important;/* 側欄滑鼠放到按鈕顏色 */
      transform: translateX(5px);
      padding-left: 50px;
    }
    .sb-sidenav-footer {
      background: rgba(255, 255, 255, 0.1) !important;/* 側欄底下字顏色 */
      color: white !important;/* 側欄底下字顏色 */
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      padding: 20px 15px;
      margin-top: 20px;
    }
    .sb-sidenav-footer .small {
      color: rgba(0, 0, 0, 0.7) !important;/* 側欄底下字顏色 */
      font-size: 0.8rem;
    }
    /* Main Content Enhancement */
    .container-fluid { padding: 30px !important; }
    h1 {
    background: linear-gradient(135deg, #ce1212, #ff6666); /* 設置紅色漸層 */
    -webkit-background-clip: text; /* 剪裁背景到文字 */
    -webkit-text-fill-color: transparent; /* 文字填充為透明 */
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 30px;
}
    /* Enhanced Alert */
    .alert {
      border: none; border-radius: var(--border-radius);
      background: var(--warning-gradient); color: white;/* 兩個圖，排班底色 */
      box-shadow: var(--card-shadow); backdrop-filter: blur(10px);
    }
    /* Card Enhancements */
    .card {
      border: none; border-radius: var(--border-radius);
      box-shadow: var(--card-shadow); backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.9);/* 兩個圖，排班上框顏色 */
      transition: var(--transition);
      overflow: hidden; position: relative;
    }
    .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); }
    .card:hover { transform: translateY(-10px); box-shadow: var(--hover-shadow); }
    .card-header {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));/* 四個格子底色 */
      border: none; padding: 20px; font-weight: 600;
      border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    }
    .card-body { padding: 25px; }
    /* Stats Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin: 30px 0; }
    .stats-card {
      background: white;/* 四個格子底色 */
      border-radius: var(--border-radius);
      padding: 25px; box-shadow: var(--card-shadow);
      position: relative; overflow: hidden;
    }
    .stats-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .stats-card.primary::before { background: var(--primary-gradient); }
    .stats-card.success::before { background: var(--success-gradient); }
    .stats-card.warning::before { background: var(--warning-gradient); }
    .stats-card.secondary::before { background: var(--secondary-gradient); }
    .stats-icon {
      width: 60px; height: 60px; border-radius: 15px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 15px; font-size: 24px; color: white;/* 四個圖案顏色 */
    }
    .stats-card.primary .stats-icon { background: var(--primary-gradient); }
    .stats-card.success .stats-icon { background: var(--success-gradient); }
    .stats-card.warning .stats-icon { background: var(--warning-gradient); }
    .stats-card.secondary .stats-icon { background: var(--secondary-gradient); }
    .stats-number { font-size: 2rem; font-weight: 700; color: #000000; margin-bottom: 5px; min-height: 2.4rem; }
    .stats-label { color: #7f8c8d; font-size: 0.9rem; font-weight: 500; }
    /* Table Enhancement */
    .table { border-radius: var(--border-radius); overflow: hidden; background: white;/* 排班底色*/ box-shadow: var(--card-shadow); }
    .table thead th { background: var(--primary-gradient); color: rgb(0, 0, 0);/* 排班文字*/ border: none; font-weight: 600; padding: 15px; }
    .table tbody td { padding: 15px; vertical-align: middle; border-color: rgba(0, 0, 0, 0.05);/* 排班表線 */ }
    .table tbody tr:hover { background: rgba(227, 23, 111, 0.05); transform: scale(1.01); }
    /* Breadcrumb Enhancement */
    .breadcrumb { background: rgba(255, 255, 255, 0.8);/* 首頁 */ border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    /* Footer Enhancement */
    footer { background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7)) !important;/* 隱私政策 */ border-top: 1px solid rgba(0, 0, 0, 0.1);/* 分開底線 */ backdrop-filter: blur(10px); }
    /* Search Enhancement */
    .form-control { border-radius: 25px; border: 2px solid transparent; background: rgba(255, 255, 255, 0.2); color: white; }
    .form-control:focus { background: rgba(255, 255, 255, 0.3); border-color: rgba(255, 255, 255, 0.5); box-shadow: 0 0 20px rgba(255, 255, 255, 0.2); color: white; }
    .btn-primary { background: var(--primary-gradient); border: none; border-radius: 25px; }
    .btn-primary:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.976); }
    /* Quick Actions */
    .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
    .quick-action { background: white; border-radius: var(--border-radius); padding: 20px; text-align: center; box-shadow: var(--card-shadow); transition: var(--transition); text-decoration: none; color: inherit; }
    .quick-action:hover { transform: translateY(-5px); box-shadow: var(--hover-shadow); text-decoration: none; color: inherit; }
    .quick-action i { font-size: 2rem; margin-bottom: 10px; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    /* Responsive */
    @media (max-width: 768px) {
      .container-fluid { padding: 15px !important; }
      .stats-grid { grid-template-columns: 1fr; gap: 15px; }
      h1 { font-size: 2rem; }
    }
    /* Loading Animation */
    .loading-shimmer { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.6s infinite; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
  </style> img{ max-width:160px; border-radius:8px; }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">會員系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
      <div class="input-group">
        </div>
    </form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user fa-fw"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          
          <li><form method="post" action="logout.php" style="display:inline;">
    <button type="submit" class="dropdown-item" style="border:none; background:none; padding:0; cursor:pointer;">
      登出
    </button>
</form></li>
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
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
<a class="nav-link" href="會員基本資料.php">
  <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>會員基本資料
</a>

<a class="nav-link" href="點數記錄.php">
  <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>我的點數
</a>


<a class="nav-link" href="消費紀錄.php">
  <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>消費紀錄
</a>

<a class="nav-link" href="點數兌換.php">
  <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt"></i></div>點數兌換
</a>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as: <br>會員</div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>會員消費紀錄</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">消費紀錄</li>
          </ol>

<!-- 消費統計摘要 -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card stat-card stat-success">
      <div class="card-body text-center">
        <div class="stat-label">累計消費</div>
        <div class="stat-value" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
          $<span id="totalSpent">0</span>
        </div>
        <div class="small text-muted">歷史總計</div>
      </div>
      <span class="stat-glow"></span>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="stat-label">本月消費</div>
        <div class="stat-value" style="color: #4facfe;">
          $<span id="monthSpent">0</span>
        </div>
        <div class="small text-muted">
          共 <span id="monthCount">0</span> 筆
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="stat-label">可用點數</div>
        <div class="stat-value" style="color: #ff6b00;">
          <span id="availablePoints">0</span>
        </div>
        <div class="small text-muted">可折抵消費</div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="stat-label">平均消費</div>
        <div class="stat-value" style="color: #54bcc1;">
          $<span id="avgSpent">0</span>
        </div>
        <div class="small text-muted">每次消費</div>
      </div>
    </div>
  </div>
</div>


<!-- 消費記錄表格 -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fas fa-receipt me-2"></i>消費記錄</div>
    <div>
      <button class="btn btn-light btn-sm" id="btnExport">
        <i class="fas fa-download me-1"></i>匯出報表
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 140px;">訂單日期</th>
            <th style="width: 150px;">訂單編號</th>
            <th style="width: 140px;">電話</th>
            <th style="width: 100px;" class="text-end">總金額</th>
            <th style="width: 100px;" class="text-end">獲得點數</th>
            <th>商品明細</th>
            <th style="width: 160px;">建立時間</th>
          </tr>
        </thead>
        <tbody id="consumptionTable">
          <tr id="noDataRow">
            <td colspan="7" class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2"></i><br>尚無消費記錄
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

    <!-- 分頁 -->
    <nav aria-label="Consumption pagination" id="paginationNav" class="mt-3" style="display: none;">
      <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
  </div>
</div>

<!-- 消費明細 Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>消費明細</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody">
        <!-- 動態內容 -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
        <button class="btn btn-primary" id="btnPrintReceipt">
          <i class="fas fa-print me-1"></i>列印收據
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.stat-card {
  border: none;
  border-radius: var(--border-radius);
  box-shadow: var(--card-shadow);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.stat-label {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 8px;
  font-weight: 500;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 5px;
}

.stat-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(251,185,124,0.15) 0%, transparent 70%);
  pointer-events: none;
}

.badge-type {
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 500;
}

.badge-dine-in {
  background: rgba(75, 172, 254, 0.12);
  color: #2196F3;
  border: 1px solid rgba(33, 150, 243, 0.25);
}

.badge-takeout {
  background: rgba(255, 193, 7, 0.12);
  color: #ff9800;
  border: 1px solid rgba(255, 152, 0, 0.25);
}

.badge-delivery {
  background: rgba(76, 175, 80, 0.12);
  color: #4caf50;
  border: 1px solid rgba(76, 175, 80, 0.25);
}

.receipt-detail {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  border: 2px dashed #dee2e6;
}

.receipt-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.receipt-row:last-child {
  border-bottom: none;
}

.receipt-total {
  font-size: 1.3rem;
  font-weight: 700;
  background: var(--primary-gradient);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
}
</style>

<script>

  // 載入統計資料
  function loadStats() {
    document.getElementById('totalSpent').textContent = stats.total.toLocaleString();
    document.getElementById('monthSpent').textContent = stats.month.toLocaleString();
    document.getElementById('monthCount').textContent = stats.monthCount;
    document.getElementById('availablePoints').textContent = stats.points;
    document.getElementById('avgSpent').textContent = stats.avg;
  }

  // 載入消費記錄表格
  function loadRecords() {
    const tbody = document.getElementById('consumptionTable');
    const noDataRow = document.getElementById('noDataRow');

    if (filteredRecords.length === 0) {
      noDataRow.style.display = '';
      tbody.innerHTML = '';
      tbody.appendChild(noDataRow);
      document.getElementById('paginationNav').style.display = 'none';
      return;
    }

    noDataRow.style.display = 'none';

</script>

        </div>
        <!-- 懸浮小麵圖標 -->
<style>
#chatbot-icon {
  position: fixed;
  bottom: 50px;
  right: 20px;
  width: 100px;  /* 調整圖標大小 */
  height: 100px;
  cursor: pointer;
  z-index: 1000;
}

#chatbot-frame {
  display: none; /* 預設隱藏 */
  position: fixed;
  bottom: 90px;  /* 圖標上方 */
  right: 20px;
  width: 350px;  /* 調整聊天框大小 */
  height: 500px;
  border: 1px solid #ccc;
  border-radius: 10px;
  z-index: 999;
}
</style>

<!-- 懸浮圖標 -->
<img src="xm.png" id="chatbot-icon" alt="Chatbot">

<!-- 聊天框 iframe -->
<iframe id="chatbot-frame" 
        src="https://cdn.botpress.cloud/webchat/v3.3/shareable.html?configUrl=https://files.bpcontent.cloud/2025/10/29/03/20251029030317-XBWHYHXK.json"
        allow="fullscreen">
</iframe>

<script>
// 點擊圖標切換聊天框顯示/隱藏
const icon = document.getElementById('chatbot-icon');
const frame = document.getElementById('chatbot-frame');

icon.addEventListener('click', () => {
  if (frame.style.display === 'none') {
    frame.style.display = 'block';
  } else {
    frame.style.display = 'none';
  }
});
</script>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; </div>
            <div>
              <span class="mx-2">•</span>
              <a href="隱私政策.html" class="text-decoration-none">隱私政策</a>
              <span class="mx-2">•</span>
              <a href="使用條款.html" class="text-decoration-none">使用條款</a>
              <span class="mx-2">•</span>
            
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 今天日期 / 側欄收合
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  fetch("get_orders.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success || !data.data || data.data.length === 0) {
        document.getElementById("totalSpent").textContent = 0;
        document.getElementById("monthSpent").textContent = 0;
        document.getElementById("monthCount").textContent = 0;
        document.getElementById("avgSpent").textContent = 0;
        return;
      }

      const orders = data.data;
      let totalSpent = 0;
      let monthSpent = 0;
      let monthCount = 0;

      const now = new Date();
      const thisYear = now.getFullYear();
      const thisMonth = now.getMonth(); // 0 ~ 11

      orders.forEach(order => {
        const amount = Number(order.總金額) || 0;
        totalSpent += amount;

        const orderDate = new Date(order.訂單日期);
        if (orderDate.getFullYear() === thisYear && orderDate.getMonth() === thisMonth) {
          monthSpent += amount;
          monthCount++;
        }
      });

      // 顯示累計消費
      document.getElementById("totalSpent").textContent = totalSpent.toLocaleString();
      // 顯示本月消費
      document.getElementById("monthSpent").textContent = monthSpent.toLocaleString();
      // 顯示本月筆數
      document.getElementById("monthCount").textContent = monthCount;
      // 顯示平均消費（歷史平均）
      const avgSpent = orders.length > 0 ? totalSpent / orders.length : 0;
      document.getElementById("avgSpent").textContent = avgSpent.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    })
    .catch(err => {
      console.error("無法取得訂單資料:", err);
      document.getElementById("totalSpent").textContent = "-";
      document.getElementById("monthSpent").textContent = "-";
      document.getElementById("monthCount").textContent = "-";
      document.getElementById("avgSpent").textContent = "-";
    });
});
</script>

   <script>
document.addEventListener("DOMContentLoaded", function() {
  const pointsEl = document.getElementById("availablePoints");

  // 顯示「讀取中...」
  if (pointsEl) pointsEl.textContent = "讀取中...";

  fetch("get_points.php", {
    method: "GET",
    credentials: "same-origin" // ✅ 帶上 session cookie
  })
  .then(response => {
    if (!response.ok) throw new Error("HTTP 錯誤狀態: " + response.status);
    return response.json();
  })
  .then(data => {
    console.log("🔹 get_points.php 回傳：", data); // <-- 請在 Console 看看這裡印出什麼

    if (data.success) {
      pointsEl.textContent = data.totalPoints ?? 0;
    } else {
      pointsEl.textContent = "-";
      console.warn("回傳訊息:", data.message);
    }
  })
  .catch(err => {
    console.error("⚠️ 無法取得點數資料:", err);
    if (pointsEl) pointsEl.textContent = "-";
  });
});
</script>
<script>
// 頁面載入後自動抓取訂單紀錄
document.addEventListener("DOMContentLoaded", function () {
  fetchOrders();
});

function fetchOrders() {
  fetch("get_orders.php")
    .then(response => response.json())
    .then(data => {
      const tableBody = document.getElementById("consumptionTable");
      const noDataRow = document.getElementById("noDataRow");
      tableBody.innerHTML = ""; // 清空原內容

      if (data.success && data.data.length > 0) {
        data.data.forEach(order => {
          const row = document.createElement("tr");

          row.innerHTML = `
            <td>${order.訂單日期 || "-"}</td>
            <td>${order.訂單編號 || "-"}</td>
            <td>${order.電話 || "-"}</td>
            <td class="text-end">${order.總金額 ? Number(order.總金額).toLocaleString() : 0}</td>
            <td class="text-end">${order.獲得點數 || 0}</td>
            <td>${order.商品明細 || "-"}</td>
            <td>${order.建立時間 || "-"}</td>
          `;

          tableBody.appendChild(row);
        });
      } else {
        // 沒資料就顯示預設那一列
        tableBody.innerHTML = `
          <tr id="noDataRow">
            <td colspan="7" class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2"></i><br>尚無消費記錄
            </td>
          </tr>
        `;
      }
    })
    .catch(error => {
      console.error("取得訂單資料時發生錯誤:", error);
      const tableBody = document.getElementById("consumptionTable");
      tableBody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center text-danger py-4">
            無法載入資料，請稍後再試
          </td>
        </tr>
      `;
    });
}
</script>


  <script src="js/scripts.js"></script>
</body>
</html>
