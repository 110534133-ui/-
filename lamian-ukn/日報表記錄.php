<?php
// 🔥 新的 日報表記錄.php (頁面)
// 🔥 已套用您系統的版型 (包含權限檢查)

require_once __DIR__ . '/includes/auth_check.php';

// 只有 A 級（老闆）可以訪問
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '日報表歷史記錄 - 員工管理系統'; // 標題

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
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
    }
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
      background: linear-gradient(45deg, #ffffff, #ffffff);
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
    .container-fluid { padding: 30px !important; }
    h1 {
      background: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
      font-weight: 700;
      font-size: 2.5rem;
      margin-bottom: 30px;
    }
    .breadcrumb {
      background: rgba(255,255,255,.8);
      border-radius: var(--border-radius);
      padding: 15px 20px;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
    }
    .table {
      border-radius: var(--border-radius);
      overflow: hidden;
      background: #fff;
      box-shadow: var(--card-shadow);
    }
    .table thead th {
      background: var(--primary-gradient);
      color: #000;
      border: none;
      font-weight: 600;
      padding: 15px;
    }
    .table tbody td {
      padding: 15px;
      vertical-align: middle;
      border-color: rgba(0,0,0,.05);
    }
    .table tbody tr:hover {
      background: rgba(227, 23, 111, 0.05);
      transform: scale(1.01);
    }
    
    /* 🔥 修正：使用 員工資料表.php 的頂欄搜尋框樣式 */
    .search-container-wrapper {
        position: relative;
        width: 100%;
        max-width: 400px;
    }
    .search-container {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50px;
        padding: 4px 4px 4px 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
    }
    .search-container:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
    }
    .search-container:focus-within {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.5);
    }
    .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        padding: 10px 12px;
        font-size: 14px;
        color: #fff;
        font-weight: 500;
    }
    .search-input::placeholder {
        color: rgba(255, 255, 255, 0.7);
        font-weight: 400;
    }
    .search-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        border: none;
        border-radius: 40px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .search-btn:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }
    .search-btn i {
        color: #ff6b6b;
        font-size: 16px;
    }
    .user-avatar{border:2px solid rgba(255,255,255,.5)}

    .btn-primary {
      background: var(--primary-gradient);
      border: none;
      border-radius: 25px;
    }
    .btn-primary:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 25px rgba(209, 209, 209, 0.976);
    }
  </style>

<style>
/* 表格容器，允許橫向滾動 */
.table-responsive {
  overflow-x: auto;
  max-height: 500px;
  position: relative;
}

/* 表頭樣式 */
#reportsTable thead th {
  white-space: nowrap;       /* 不換行 */
  text-align: center;        /* 文字置中 */
  vertical-align: middle;    /* 垂直置中 */
  position: sticky;          /* 固定在上方 */
  top: 0;
  z-index: 10;               /* 確保表頭在最上層 */

}

/* 表格資料儲存格 */
#reportsTable td {
  white-space: nowrap;        /* 不換行 */
  overflow: hidden;
  text-overflow: ellipsis;    /* 過長文字顯示省略號 */
  text-align: center;
  vertical-align: middle;
  padding: 8px;
  border-bottom: 1px solid #dee2e6;
}

/* sticky 右側欄位 */
.sticky-right {
  position: sticky;
  right: 0;
  z-index: 5; /* 低於表頭，但高於一般儲存格 */
  box-shadow: -2px 0 5px rgba(0,0,0,0.1);
}

/* tbody hover 保留原本漸層 hover */
#reportsTable tbody tr:hover {
  background: rgba(227, 23, 111, 0.05);
  transform: scale(1.01);
  transition: transform 0.2s ease, background 0.2s ease;
}


/* sticky 操作欄整欄浮動，按鈕一起跟隨 */
#reportsTable td.sticky-right {
  position: sticky;
  right: 0;
  background: #fff; /* 背景避免透出其他行 */
  z-index: 20;      /* 高於一般儲存格，但低於表頭 */
  display: flex;
  justify-content: center; /* 按鈕置中 */
  gap: 5px;               /* 按鈕間距 */
  padding: 8px;
}
</style>
</head>

<body class="sb-nav-fixed">
  
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

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
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link active" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.php">薪資管理</a>
              </nav>
            </div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
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

                <a class="nav-link" href="日報表.php"><div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
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
            <a class="nav-link" href="charts.html">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
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
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>日報表歷史記錄</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item"><a href="日報表.html" class="text-decoration-none">日報表</a></li>
            <li class="breadcrumb-item active">歷史記錄</li>
          </ol>
        
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">開始日期</label>
                  <input type="date" class="form-control" id="start_date">
                </div>
                <div class="col-md-3">
                  <label class="form-label">結束日期</label>
                  <input type="date" class="form-control" id="end_date">
                </div>
                <div class="col-md-3">
                  <label class="form-label">填表人</label>
                  <select class="form-control" id="filled_by_filter">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-primary me-2" id="filter_btn">
                    <i class="fas fa-search"></i> 查詢
                  </button>
                  <button class="btn btn-secondary" id="clear_btn">
                    <i class="fas fa-refresh"></i> 清除
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
              <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small text-white-50">總記錄數</div>
                      <div class="h5" id="total_records">-</div>
                    </div>
                    <i class="fas fa-clipboard-list fa-2x text-white-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-success text-white mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small text-white-50">總收入</div>
                      <div class="h5" id="total_income_sum">-</div>
                    </div>
                    <i class="fas fa-dollar-sign fa-2x text-white-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small text-white-50">總支出</div>
                      <div class="h5" id="total_expense_sum">-</div>
                    </div>
                    <i class="fas fa-credit-card fa-2x text-white-50"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-info text-white mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="small text-white-50">淨收入</div>
                      <div class="h5" id="net_income">-</div>
                    </div>
                    <i class="fas fa-chart-line fa-2x text-white-50"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
            
          <div class="card mb-4" id="reportList">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><i class="fas fa-table me-1"></i>日報表記錄列表</div>
              <button class="btn btn-success btn-sm" onclick="exportToExcel()" id="exportBtn">
                <i class="fas fa-download"></i> 匯出Excel
              </button>
            </div>
            <div class="card-body table-responsive">
              <table class="table table-bordered" id="reportsTable" width="100%" cellspacing="0">
                <thead>
                  <tr>
                    <th>日期</th>
                    <th>填表人</th>
                    <th>現金收入</th>
                    <th>LinePay</th>
                    <th>Uber</th>
                    <th>其他收入</th>
                    <th>收入合計</th>
                    <th>食材成本</th>
                    <th>人事成本</th>
                    <th>租金</th>
                    <th>每日租金平攤</th>
                    <th>水電瓦斯費</th>
                    <th>外送平台費</th>
                    <th>雜項支出</th>
                    <th>支出合計</th>
                    <th class="sticky-right">操作</th>
                  </tr>
                </thead>
                <tbody id="reportTableBody">
                  <tr id="noDataRow" class="d-none">
                    <td colspan="16" class="text-center text-muted py-4">
                      <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            </div>
        </div>
      </main>

      <div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title" id="editReportModalLabel"><i class="fas fa-edit me-2"></i>修改日報表</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
              <form id="editReportForm">
                <input type="hidden" id="editId" name="id">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3 sticky-row">
                        <label class="form-label">報表日期</label>
                        <input type="date" id="editDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">填表人</label>
                      <input type="text" id="editFilledBy" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">現金收入</label>
                      <input type="number" id="editCashIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">LinePay收入</label>
                      <input type="number" id="editLinepayIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Uber收入</label>
                      <input type="number" id="editUberIncome" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">其他收入</label>
                      <input type="number" id="editOtherIncome" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="total_income" class="form-label">收入合計</label>
                      <input type="number" id="total_income" name="total_income" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">食材成本</label>
                      <input type="number" id="editExpenseFood" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">人事成本</label>
                      <input type="number" id="editExpenseSalary" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">租金</label>
                      <input type="number" id="editExpenseRent" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="editRentDaily" class="form-label">每日租金平攤</label>
                      <input type="number" id="editRentDaily" name="rent_daily" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">水電瓦斯費</label>
                      <input type="number" id="editExpenseUtilities" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">外送平台費</label>
                      <input type="number" id="editExpenseDelivery" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">雜項支出</label>
                      <input type="number" id="editExpenseMisc" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label for="total_expense" class="form-label">支出合計</label>
                      <input type="number" id="total_expense" name="total_expense" class="form-control" readonly style="background-color:#f5f5f5;">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
              <button type="submit" form="editReportForm" class="btn btn-primary">儲存修改</button>
            </div>
          </div>
        </div>
      </div>
  
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

  <script src="日報表紀錄.js"></script>

  <script>
      // ---- 常數（PHP 變數注入） ----
      const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
      const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

      const $  = s => document.querySelector(s);
      const el = id => document.getElementById(id);

      // 今日日期 (此頁面也有 'currentDate')
      // (日報表紀錄.js 會處理)

      // 折起/展開側欄
      el('sidebarToggle')?.addEventListener('click', e => { 
          e.preventDefault(); 
          document.body.classList.toggle('sb-sidenav-toggled'); 
      });

      // 取得登入者資訊（已從 PHP Session 取得）
      async function loadLoggedInUser(){
          const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
          const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
          
          console.log('✅ 日報表記錄 已登入:', userName, 'ID:', userId);
          
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
          
          // 🔥 觸發 JS 檔案中的 loadReports()
          if (typeof loadReports === 'function') {
              loadReports();
          } else {
              console.error("loadReports() 函式不存在，請檢查 日報表紀錄.js");
          }
      });
  </script>
</body>
</html>