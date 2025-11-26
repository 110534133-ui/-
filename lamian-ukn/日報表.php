<?php
// 🔥 新的 日報表.php (填寫頁面)
// 🔥 已套用您系統的版型 (包含權限檢查)

require_once __DIR__ . '/includes/auth_check.php';

// 2. 檢查權限：A 級(老闆) 或 B 級(管理員)
// 假設 check_user_level() 會檢查當前 session 用戶
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    // 如果 *既不是A* *也不是B*，導向回首頁 (index.php)
    header('Location: index.php'); 
    exit;
}

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '日報表 - 員工管理系統'; // 標題

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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
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

    /* KPI 卡片 */
    .kpi-card{ color:#fff; border:none; border-radius:16px; box-shadow: var(--card-shadow); }
    .kpi-income{ background: var(--success-gradient); }
    .kpi-expense{ background: var(--warning-gradient); color:#000; }
    .kpi-net{ background: var(--secondary-gradient); }
    .kpi-deposit{ background: var(--primary-gradient); }
    .kpi-card .kpi-value{ font-size:1.4rem; font-weight:700; }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; font-weight:600; }

    .table-hover tbody tr:hover{ background: rgba(227,23,111,.05); transform:scale(1.01); }
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
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.php">薪資管理</a>
              </nav>
            </div>

            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseOperation" data-bs-parent="#sidenavAccordion">
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

                <a class="nav-link active" href="日報表.php"><div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
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
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
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
            <h1>日報表</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">日報表</li>
          </ol>

             <div class="container mt-3">
          <div id="successAlert" class="alert alert-success d-none" role="alert">
            <i class="fas fa-check-circle me-2"></i><span id="successMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <div id="warningAlert" class="alert alert-warning d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="warningMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-times-circle me-2"></i><span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        </div>

          <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card kpi-income">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-white-50">今日收入合計</div>
                    <div class="kpi-value" id="kpi_income">—</div>
                  </div>
                  <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card kpi-expense">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small">今日支出合計</div>
                    <div class="kpi-value" id="kpi_expense">—</div>
                  </div>
                  <i class="fas fa-credit-card fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card kpi-net">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-white-50">今日淨收入（收−支）</div>
                    <div class="kpi-value" id="kpi_net">—</div>
                  </div>
                  <i class="fas fa-chart-line fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card kpi-deposit">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-white-50">存入銀行</div>
                    <div class="kpi-value" id="kpi_deposit">—</div>
                  </div>
                  <i class="fas fa-university fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
          </div>

        <form id="dailyReportForm">
  <div class="card mb-4">
    <div class="card-header"><i class="fas fa-info-circle me-2"></i>基本資訊</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">日期</label>
          <input type="date" class="form-control" id="report_date">
        </div>
        <div class="col-md-4">
          <label class="form-label">星期</label>
          <input type="text" class="form-control" id="weekday" placeholder="例如：星期一" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">填表人</label>
          <input type="text" class="form-control" id="filled_by" placeholder="填寫人員姓名">
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-wallet me-2"></i>收入</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">現金收入</label>
            <input type="number" class="form-control income" id="cash_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Line Pay</label>
            <input type="number" class="form-control income" id="linepay_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Uber 實收</label>
            <input type="number" class="form-control income" id="uber_income" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">其他收入</label>
            <input type="number" class="form-control income" id="other_income" placeholder="0">
          </div>
          <div class="alert alert-success mb-0 mt-auto">
            <strong>收入合計：</strong><span id="total_income">0</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-thumbtack me-2"></i>固定支出</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">人事成本</label>
            <input type="number" class="form-control expense" id="expense_salary" placeholder="0">
          </div>
         <div class="mb-2 d-flex align-items-center">
        <input type="checkbox" class="form-check-input me-2" id="enable_utilities">
        <label class="form-label mb-0 me-2" for="enable_utilities">水電瓦斯費</label>
        <select class="form-select form-select-sm" id="utility_month" style="width:auto;" disabled>
          <option value="term1">1-2月</option>
          <option value="term2">3-4月</option>
          <option value="term3">5-6月</option>
          <option value="term4">7-8月</option>
          <option value="term5">9-10月</option>
          <option value="term6">11-12月</option>
        </select>
      </div>
      <div class="mb-3">
        <input type="number" class="form-control expense" id="expense_utilities" placeholder="期金額（例如：整期總額）" disabled>
      </div>

      <div class="mb-2 d-flex align-items-center">
  <input type="checkbox" class="form-check-input me-2" id="enable_rent">
  <label class="form-label mb-0 me-2" for="enable_rent">租金</label>

  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rentSettingModal">
    <i class="bi bi-gear"></i>
  </button>
</div>

<div class="mb-3">
  <input type="number" class="form-control expense" id="expense_rent" placeholder="租金（金額）" disabled>
</div>

<input type="hidden" id="rent_setting" value='{"period":"month","months":3}'>


<div class="modal fade" id="rentSettingModal" tabindex="-1" aria-labelledby="rentSettingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
  <h5 class="modal-title d-flex align-items-center" id="rentSettingModalLabel">
    <i class="bi bi-gear me-2"></i> 租金設定
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
</div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="rent_period" class="form-label">租金週期</label>
          <select class="form-select form-select-sm" id="rent_period">
            <option value="month">月</option>
            <option value="season">季</option>
          </select>
        </div>
        <div class="mb-3 d-none" id="season_wrap">
          <label for="season_months" class="form-label">季期</label>
          <select class="form-select form-select-sm" id="season_months">
            <option value="3">3個月</option>
            <option value="4">4個月</option>
            <option value="6">6個月</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="rent_start" class="form-label">租期開始</label>
          <input type="date" class="form-control form-control-sm" id="rent_start">
        </div>
        <div class="mb-3">
          <label for="rent_end" class="form-label">租期結束</label>
          <input type="date" class="form-control form-control-sm" id="rent_end">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveRentSetting">儲存</button>
      </div>
    </div>
  </div>
</div>

      <div class="alert alert-danger mb-0 mt-auto">
  <strong>固定支出合計：</strong><span id="t_expense">0</span>
</div>
    </div>
  </div>
</div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-receipt me-2"></i>變動支出</div>
        <div class="card-body d-flex flex-column">
          <div class="mb-3">
            <label class="form-label">食材成本</label>
            <input type="number" class="form-control expense" id="expense_food" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">外送平台費</label>
            <input type="number" class="form-control expense" id="expense_delivery" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">雜項支出</label>
            <input type="number" class="form-control expense" id="expense_misc" placeholder="0">
          </div>
          <div class="mb-3">
            <label class="form-label">備註</label>
            <textarea class="form-control" id="expense_note" placeholder="如：採買食材、維修"></textarea>
          </div>
          <div class="alert alert-warning mb-0 mt-auto">
  <strong>變動支出合計：</strong><span id="total_variable">0</span>
</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card h-100">
        <div class="card-header"><i class="fas fa-coins me-2"></i>店內現金</div>
        <div class="card-body d-flex flex-column">
          <div class="row g-2">
            <div class="col-6"><div class="input-group"><span class="input-group-text">1000</span><input type="number" class="form-control cash" id="cash_1000" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">500</span><input type="number" class="form-control cash" id="cash_500" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">100</span><input type="number" class="form-control cash" id="cash_100" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">50</span><input type="number" class="form-control cash" id="cash_50" placeholder="張數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">10</span><input type="number" class="form-control cash" id="cash_10" placeholder="枚數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">5</span><input type="number" class="form-control cash" id="cash_5" placeholder="枚數"></div></div>
            <div class="col-6"><div class="input-group"><span class="input-group-text">1</span><input type="number" class="form-control cash" id="cash_1" placeholder="枚數"></div></div>
          </div>
          <div class="alert alert-info mt-3 mb-0 mt-auto">
            <strong>店內現金合計：</strong><span id="cash_total">0</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">存入銀行</label>
          <input type="number" class="form-control" id="deposit_to_bank" placeholder="輸入金額">
        </div>
        <div class="col-md-6 text-end align-self-end">
          <button type="submit" class="btn btn-primary" id="btnSubmit">
            <i class="fas fa-paper-plane me-1"></i>送出日報表
          </button>
        </div>
      </div>
    </div>
  </div>
</form>
</main>

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

  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="日報表.js"></script>





  <script>
      // ---- 常數（PHP 變數注入） ----
      const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
      const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

      const $  = s => document.querySelector(s);
      const el = id => document.getElementById(id);

      // 折起/展開側欄
   el('sidebarToggle')?.addEventListener('click', e => { 
    e.preventDefault(); 
    document.body.classList.toggle('sb-sidenav-toggled');
});

      // 取得登入者資訊（已從 PHP Session 取得）
      async function loadLoggedInUser(){
          const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
          const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
          
          console.log('✅ 日報表 已登入:', userName, 'ID:', userId);
          
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
          
          // 🔥 觸發 JS 檔案中的 updateKPI()
          if (typeof updateKPI === 'function') {
              updateKPI(); //
          } else {
              console.error("updateKPI() 函式不存在，請檢查 日報表.js");
          }
      });
  </script><script>
// 店內現金計算
function updateCashTotal() {
    // 每個面額對應其輸入欄位 id
    const cashMap = {
        1000: "cash_1000",
        500:  "cash_500",
        100:  "cash_100",
        50:   "cash_50",
        10:   "cash_10",
        5:    "cash_5",
        1:    "cash_1",
    };

    let total = 0;

    // 逐一計算 (張數 × 面額)
    for (const [value, id] of Object.entries(cashMap)) {
        const count = Number(document.getElementById(id)?.value) || 0;
        total += count * Number(value);
    }

    // 顯示到前端 <span id="cash_total">
    document.getElementById("cash_total").innerText = total;
}

// 🔥 為所有 class="cash" 的輸入框加入監聽事件
document.querySelectorAll(".cash").forEach(input => {
    input.addEventListener("input", updateCashTotal);
});
</script>
</body>
</html>