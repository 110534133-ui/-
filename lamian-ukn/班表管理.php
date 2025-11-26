<?php
// /lamian-ukn/班表管理.php
// ✅ 正確使用 auth_check.php 的版本

// 🔥 重要:先啟動 session
session_start();

// 1. 載入權限檢查 (確保路徑正確)
$auth_file = __DIR__ . '/includes/auth_check.php';

if (file_exists($auth_file)) {
    require_once $auth_file;
    
    // 2. 檢查權限:A 級(老闆)或 B 級(管理員)
    check_user_level(['A', 'B'], true);
    
    // 3. 取得用戶資訊
    $user = get_user_info();
    $userName  = $user['name'];
    $userId    = $user['uid'];
    $userLevel = $user['level'];
    
    error_log("✅ 班表管理 - 使用 auth_check.php - 用戶: {$userName} ({$userId}), Level: {$userLevel}");
    
} else {
    // 🔥 如果找不到 auth_check.php,使用備用方案
    error_log("⚠️ auth_check.php 不存在於: {$auth_file}");
    
    // 檢查登入
    if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
    
    // 檢查權限
    $userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? $_SESSION['role'] ?? 'C';
    
    if (!in_array($userLevel, ['A', 'B'])) {
        // 不是 A 或 B,導向對應首頁
        if ($userLevel === 'C') {
            header('Location: indexC.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
    
    // 取得用戶資訊
    $userName = $_SESSION['name'] ?? '未知用戶';
    $userId   = $_SESSION['uid'] ?? '';
    
    error_log("⚠️ 班表管理 - 使用備用方案 - 用戶: {$userName} ({$userId}), Level: {$userLevel}");
}

// 4. 統一路徑
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';

$pageTitle = '班表管理 - 員工管理系統';
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
    .search-container { position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15); border-radius: 50px; padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(10px); border: 2px solid transparent; }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
    .search-input { flex: 1; border: none; outline: none; background: transparent; padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500; }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn { background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%); border: none; border-radius: 40px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); position: relative; overflow: hidden; }
    .search-btn::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%; background: rgba(251, 185, 124, 0.3); transform: translate(-50%, -50%); transition: width 0.6s, height 0.6s; }
    .search-btn:hover::before { width: 80px; height: 80px; }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn:active { transform: scale(0.95); }
    .search-btn i { color: #ff6b6b; font-size: 16px; position: relative; z-index: 1; }

    /* Sidenav */
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
    .user-avatar{border:2px solid rgba(255,255,255,.5)}

    /* 內容區 */
    .container-fluid{padding:30px!important}
    h1{background:var(--primary-gradient);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:2.5rem;margin-bottom:30px}
    .card{border:none;border-radius:var(--border-radius);box-shadow:var(--card-shadow);backdrop-filter:blur(10px);background:rgba(255,255,255,.9);overflow:hidden;position:relative}
    .card:hover{transform:translateY(-10px);box-shadow:var(--hover-shadow)}
    .card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--primary-gradient)}
    .card-header{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7));border:none;padding:20px;font-weight:600;border-radius:var(--border-radius) var(--border-radius) 0 0!important}
    .card-body{padding:25px}
    
    .table{border-radius:var(--border-radius);overflow:hidden;background:#fff}
    .table thead th{background:var(--primary-gradient);color:#000;border:none;font-weight:600;padding:15px;text-align:center;vertical-align:middle;white-space:nowrap}
    .table tbody td{padding:15px;vertical-align:middle;border-color:rgba(0,0,0,.05);text-align:center;white-space:nowrap}
    .table tbody tr:hover{background:rgba(227,23,111,.05)}
    
    .breadcrumb{background:rgba(255,255,255,.8);border-radius:var(--border-radius);padding:15px 20px;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    footer{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7))!important;border-top:1px solid rgba(0,0,0,.1);backdrop-filter:blur(10px)}
    
    /* 按鈕 */
    .btn-primary { background: var(--primary-gradient); border: none; border-radius: 25px; padding: 0.5rem 1.25rem; color: #fff; }
    .btn-primary:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(209, 209, 209, 0.976); background: var(--primary-gradient); color: #fff; }
    .btn-outline-secondary { border-radius: 25px; padding: 0.5rem 1.25rem; }
    .form-control { border-radius: 12px; }

    /* Gantt */
    .gantt-toolbar { gap: .5rem; flex-wrap: wrap; }
    .gantt-toolbar .btn-day { min-width: 96px; }
    .gantt-legend { font-size: .9rem; opacity: .75; }
    .gantt { background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:12px; box-shadow: var(--card-shadow); overflow:hidden; }
    .gantt-header, .gantt-row { display:grid; grid-template-columns: 140px 1fr; }
    .gantt-header { background:#f8f9fa; border-bottom:1px solid rgba(0,0,0,.06); }
    .gantt-header .times { position:relative; padding:10px 8px; border-left:1px solid rgba(0,0,0,.06); }
    .gantt-header .scale { display:grid; grid-template-columns: repeat(15, 1fr); font-size:.85rem; text-align:center; }
    .gantt-header .scale div { border-left:1px dashed rgba(0,0,0,.07); padding:2px 0; }
    .gantt-row + .gantt-row { border-top:1px solid rgba(0,0,0,.06); }
    .gantt-row .name { padding:10px 12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; background:#fff; }
    .gantt-row .track { position:relative; padding:12px 8px; border-left:1px solid rgba(0,0,0,.06); background:linear-gradient(180deg,#fff,#fff); }
    .gantt-grid { position:absolute; inset:12px 8px; display:grid; grid-template-columns: repeat(15, 1fr); }
    .gantt-grid div { border-left:1px dashed rgba(0,0,0,.06); }
    .gantt-bar { 
      position:absolute; 
      height:28px; 
      border-radius:8px; 
      background: var(--success-gradient); 
      display:flex; 
      align-items:center; 
      padding:0 10px; 
      box-shadow: 0 6px 16px rgba(0,0,0,.12); 
      font-size:.9rem; 
      color:#fff; 
      white-space:nowrap; 
      cursor:pointer;
      transition: all 0.3s ease;
      user-select: none;
    }
    
    .gantt-bar:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      z-index: 10;
    }
    
    .gantt-bar:active {
      transform: scale(0.98);
    }

    .pulse-highlight { animation: pulseBg 1.4s ease-out 1; }
    @keyframes pulseBg {
      0% { box-shadow: 0 0 0 0 rgba(79,172,254,.6); }
      100% { box-shadow: 0 0 0 18px rgba(79,172,254,0); }
    }
    
    /* 新添加員工的高亮動畫 */
    .chip-highlight {
      animation: highlight-chip 1.5s ease;
    }
    
    @keyframes highlight-chip {
      0% { 
        background-color: #ffc107 !important;
        transform: scale(1.15);
        box-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
      }
      50% {
        background-color: #ffc107 !important;
        transform: scale(1.1);
      }
      100% { 
        background-color: #0d6efd !important;
        transform: scale(1);
        box-shadow: none;
      }
    }
    
    /* 表格單元格閃爍效果 */
    .cell-flash {
      animation: flash-cell 1.5s ease;
    }
    
    @keyframes flash-cell {
      0% { 
        background-color: #fff3cd;
        box-shadow: inset 0 0 15px rgba(255, 193, 7, 0.5);
      }
      100% { 
        background-color: transparent;
        box-shadow: none;
      }
    }
    
    .assign-chip { font-size: 0.9rem; padding: 6px 6px 6px 10px; }
    .assign-chip .chip-btn {
        padding: 0;
        margin: 0;
        width: 18px;
        height: 18px;
        font-size: 11px;
        line-height: 18px;
        border-radius: 50%;
        opacity: 0.7;
    }
    .assign-chip .chip-btn:hover { opacity: 1; }
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
            <a class="nav-link active" href="index.php">
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
            <a class="nav-link" href="charts.php">
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

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <!-- 標題與日期 -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>班表管理</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <!-- 麵包屑 -->
          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="<?php echo ($userLevel === 'A') ? 'index.php' : 'indexB.php'; ?>" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">班表管理</li>
          </ol>

          <!-- 系統狀態 (測試用) -->
          <div class="alert alert-info mb-4">
            <strong>系統狀態:</strong> 
            用戶: <?php echo htmlspecialchars($userName); ?> | 
            ID: <?php echo htmlspecialchars($userId); ?> | 
            等級: <?php echo htmlspecialchars($userLevel); ?>級
          </div>

          <!-- 日期選擇與週切換 -->
          <div class="card mb-4">
            <div class="card-header">
              <i class="fas fa-calendar me-2"></i>選擇週期
            </div>
            <div class="card-body">
              <div class="row g-3 align-items-center">
                <div class="col-md-3">
                  <label class="form-label">選擇日期</label>
                  <input type="date" class="form-control" id="dateSelect" />
                </div>
                <div class="col-md-auto">
                  <label class="form-label d-block">&nbsp;</label>
                  <button class="btn btn-primary" id="btnQuery"><i class="fas fa-search me-1"></i>查詢</button>
                </div>
                <div class="col-md-auto ms-auto">
                  <label class="form-label d-block">&nbsp;</label>
                  <strong id="weekRangeText" class="text-primary"></strong>
                </div>
              </div>
            </div>
          </div>

          <!-- 員工可排時段總覽 (日檢視) -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>
                <i class="fas fa-users-clock me-2"></i>員工可排時段 (日檢視)
                <span class="badge bg-primary ms-2" style="font-size: 0.75rem;">點擊藍色條 → 快速添加</span>
              </span>
              <div class="gantt-toolbar d-flex" id="ganttDayButtons">
                <!-- 動態生成 7 個按鈕 -->
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-3 py-2">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>使用提示:</strong>直接點擊藍色時間條,該員工會立即出現在下方編輯班表中!
              </div>
              <div id="ganttChart" class="gantt">
                <!-- 動態生成 Gantt 圖 -->
              </div>
            </div>
          </div>

          <!-- 編輯班表 (週檢視) -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span><i class="fas fa-calendar-check me-2"></i>編輯班表</span>
              <div>
                <button class="btn btn-outline-secondary btn-sm me-2" id="btnClearDraft">
                  <i class="fas fa-eraser me-1"></i>清空草稿
                </button>
                <button class="btn btn-primary btn-sm" id="btnSaveDraft">
                  <i class="fas fa-save me-1"></i>儲存班表
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr id="editorHeaderRow">
                      <th style="min-width:100px">時段</th>
                      <!-- 動態生成 7 個日期欄 -->
                    </tr>
                  </thead>
                  <tbody id="editorBody">
                    <!-- 動態生成 -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <!-- Footer -->
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

  <!-- 新增/修改人員 Modal -->
  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="assignModalTitle">新增人員</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="assignForm">
          <div class="modal-body">
            <input type="hidden" id="assignDs" />
            <input type="hidden" id="assignPeriod" />
            <input type="hidden" id="assignOriginalName" />
            
            <div class="mb-3">
              <label class="form-label">姓名</label>
              <select class="form-select" id="assignNameSelect" required>
                <option value="">請選擇員工</option>
              </select>
            </div>
            
            <div class="row">
              <div class="col-6">
                <label class="form-label">開始時間</label>
                <input type="time" class="form-control" id="assignStart" required />
              </div>
              <div class="col-6">
                <label class="form-label">結束時間</label>
                <input type="time" class="form-control" id="assignEnd" required />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
            <button type="submit" class="btn btn-primary">確定</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>
  
  <script>
    // 🔥 注入 PHP 變數
    const PHP_USER_NAME = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
    const PHP_USER_ID = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
    const PHP_USER_LEVEL = <?php echo json_encode($userLevel, JSON_UNESCAPED_UNICODE); ?>;
    
    console.log('✅ 班表管理頁面載入:', PHP_USER_NAME, 'ID:', PHP_USER_ID, 'Level:', PHP_USER_LEVEL);

    // ===== 基本設定 =====
    const PERIODS = ['上午', '晚上'];
    let availabilityDetail = {};
    let scheduleAssignedMap = {};
    let draftAssignedMap = {};
    let allEmployees = [];

    // ===== 日期工具函數 =====
    function getMonday(d = new Date()) {
      const date = new Date(d);
      const day = (date.getDay() + 6) % 7;
      date.setDate(date.getDate() - day);
      date.setHours(0, 0, 0, 0);
      return date;
    }

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

    function daysOfWeek(monday) {
      return Array.from({ length: 7 }, (_, i) => addDays(monday, i));
    }

    function selectedDate() {
      return new Date(document.getElementById('dateSelect').value);
    }

    function renderWeekHeader(monday) {
      const sun = addDays(monday, 6);
      document.getElementById('weekRangeText').textContent = 
        `${fmt(monday)} ~ ${fmt(sun)}`;
    }

    function initDateSelectors() {
      const nextWeek = new Date();
      nextWeek.setDate(nextWeek.getDate() + 7);
      document.getElementById('dateSelect').value = fmt(nextWeek);
    }

    // ===== API 請求 =====
    async function fetchJSON(url, options = {}) {
      try {
        const res = await fetch(url, {
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          ...options
        });
        
        if (!res.ok) {
          const text = await res.text();
          console.error('API 錯誤:', res.status, text);
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        
        return await res.json();
      } catch (err) {
        console.error('[API ERROR]', url, err);
        alert('API 錯誤: ' + err.message);
        return null;
      }
    }

    // ===== 載入全體員工清單 =====
    async function loadEmployeeList() {
      try {
        const res = await fetch('班表管理API.php?action=employees', {
          credentials: 'include'
        });
        
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.employees) {
            allEmployees = data.employees;
            console.log('✅ 載入員工清單:', allEmployees.length, '人');
            
            const select = document.getElementById('assignNameSelect');
            select.innerHTML = '<option value="">請選擇員工</option>' +
              allEmployees.map(emp => 
                `<option value="${emp.name}">${emp.name} (${emp.position || ''})</option>`
              ).join('');
          }
        }
      } catch (e) {
        console.error('載入員工清單失敗:', e);
      }
    }

    // ===== 載入員工可排時段 =====
    async function loadAvailability(monday) {
      const data = await fetchJSON(`班表.php?start=${fmt(monday)}`);
      if (!data || !data.rows) {
        console.warn('無可排時段資料');
        availabilityDetail = {};
        return;
      }
      
      availabilityDetail = {};
      data.rows.forEach(emp => {
        const name = emp.name;
        (emp.shifts || []).forEach((dayShifts, i) => {
          const date = fmt(addDays(monday, i));
          dayShifts.forEach(shift => {
            const time = shift.split('~')[0] || '00:00';
            const hour = parseInt(time.split(':')[0]);
            const period = (hour >= 6 && hour < 14) ? '上午' : '晚上';
            
            const key = `${date}::${period}`;
            if (!availabilityDetail[key]) {
              availabilityDetail[key] = [];
            }
            
            availabilityDetail[key].push({
              name: name,
              time: shift
            });
          });
        });
      });
      
      console.log('✅ 載入可排時段:', Object.keys(availabilityDetail).length, '個時段');
    }

    // ===== 載入已確認班表 =====
    async function loadSchedulePreview(monday) {
      const url = `確認班表.php?date=${fmt(monday)}&t=${Date.now()}`;
      const data = await fetchJSON(url);
      
      if (!Array.isArray(data)) {
        console.warn('無已確認班表資料');
        scheduleAssignedMap = {};
        return;
      }
      
      scheduleAssignedMap = {};
      daysOfWeek(monday).forEach((d, i) => {
        const ds = fmt(d);
        scheduleAssignedMap[ds] = { '上午': [], '晚上': [] };
        
        data.forEach(row => {
          if (row.timeSlot === '上午' || row.timeSlot === '晚上') {
            const dayContent = row.days[i];
            if (dayContent && dayContent !== '-') {
              const matches = dayContent.match(/(.+?)\s+\((.+?)\)/g);
              if (matches) {
                matches.forEach(match => {
                  const m = match.match(/(.+?)\s+\((.+?)\)/);
                  if (m) {
                    scheduleAssignedMap[ds][row.timeSlot].push({
                      name: m[1].trim(),
                      time: m[2].trim(),
                      note: ''
                    });
                  }
                });
              }
            }
          }
        });
      });
      
      console.log('✅ 載入已確認班表');
    }

    // ===== Gantt 圖 (日檢視) =====
    let currentGanttDate = null;

    function renderDayButtons(monday) {
      const container = document.getElementById('ganttDayButtons');
      container.innerHTML = '';
      
      const labels = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
      daysOfWeek(monday).forEach((d, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-primary btn-day';
        btn.textContent = `${labels[i]} ${d.getMonth() + 1}/${d.getDate()}`;
        btn.dataset.date = fmt(d);
        
        btn.addEventListener('click', () => {
          container.querySelectorAll('.btn-day').forEach(b => 
            b.classList.remove('active', 'btn-primary')
          );
          btn.classList.add('active', 'btn-primary');
          btn.classList.remove('btn-outline-primary');
          
          currentGanttDate = fmt(d);
          renderGanttChart(currentGanttDate);
        });
        
        container.appendChild(btn);
        
        if (i === 0) {
          currentGanttDate = fmt(d);
          btn.click();
        }
      });
    }

    function renderGanttChart(dateStr) {
      console.log('🎨 渲染甘特圖:', dateStr);
      
      const container = document.getElementById('ganttChart');
      
      const morningKey = `${dateStr}::上午`;
      const eveningKey = `${dateStr}::晚上`;
      
      console.log('🔍 查找資料:', { morningKey, eveningKey });
      console.log('📦 可用資料:', Object.keys(availabilityDetail));
      
      const morningList = availabilityDetail[morningKey] || [];
      const eveningList = availabilityDetail[eveningKey] || [];
      
      console.log('📊 上午人員:', morningList.length, '人');
      console.log('📊 晚上人員:', eveningList.length, '人');
      
      const allNames = new Set();
      [...morningList, ...eveningList].forEach(x => allNames.add(x.name));
      
      if (allNames.size === 0) {
        container.innerHTML = '<div class="text-center text-muted p-4">此日沒有可排人員</div>';
        return;
      }
      
      let html = `
        <div class="gantt-header">
          <div class="name">員工</div>
          <div class="times">
            <div class="scale">
              ${Array.from({ length: 15 }, (_, i) => {
                const hour = 6 + i;
                return `<div>${hour}:00</div>`;
              }).join('')}
            </div>
          </div>
        </div>
      `;
      
      Array.from(allNames).sort().forEach(name => {
        const morning = morningList.find(x => x.name === name);
        const evening = eveningList.find(x => x.name === name);
        
        html += `
          <div class="gantt-row">
            <div class="name">${name}</div>
            <div class="track">
              <div class="gantt-grid">
                ${Array.from({ length: 15 }, () => '<div></div>').join('')}
              </div>
        `;
        
        if (morning) {
          const time = morning.time.split('~');
          if (time.length === 2) {
            const start = parseFloat(time[0].replace(':', '.'));
            const end = parseFloat(time[1].replace(':', '.'));
            const left = ((start - 6) / 15) * 100;
            const width = ((end - start) / 15) * 100;
            
            html += `
              <div class="gantt-bar" style="left:${left}%; width:${width}%"
                   data-date="${dateStr}" 
                   data-period="上午" 
                   data-name="${name}"
                   data-time="${time[0]}-${time[1]}"
                   title="點擊添加 ${name} 到編輯區">
                上午 ${time[0]}-${time[1]}
              </div>
            `;
          }
        }
        
        if (evening) {
          const time = evening.time.split('~');
          if (time.length === 2) {
            const start = parseFloat(time[0].replace(':', '.'));
            const end = parseFloat(time[1].replace(':', '.'));
            const left = ((start - 6) / 15) * 100;
            const width = ((end - start) / 15) * 100;
            
            html += `
              <div class="gantt-bar" style="left:${left}%; width:${width}%"
                   data-date="${dateStr}" 
                   data-period="晚上" 
                   data-name="${name}"
                   data-time="${time[0]}-${time[1]}"
                   title="點擊添加 ${name} 到編輯區">
                晚上 ${time[0]}-${time[1]}
              </div>
            `;
          }
        }
        
        html += `
            </div>
          </div>
        `;
      });
      
      container.innerHTML = html;
      
      container.querySelectorAll('.gantt-bar').forEach(bar => {
        bar.addEventListener('click', () => {
          const date = bar.dataset.date;
          const period = bar.dataset.period;
          const name = bar.dataset.name;
          const time = bar.dataset.time || '';
          
          console.log('🖱️ 點擊甘特圖:', { date, period, name, time });
          
          // 檢查是否已經在編輯區
          if (inDraft(date, period, name)) {
            console.log('⚠️ 已存在於編輯區');
            
            // 已存在,給予提示
            bar.style.opacity = '0.6';
            const originalBg = bar.style.background;
            bar.style.background = 'linear-gradient(135deg, #ffc107 0%, #ff9800 100%)';
            
            setTimeout(() => {
              bar.style.opacity = '1';
              bar.style.background = originalBg;
            }, 800);
            
            // 滾動到編輯區並高亮
            const td = document.querySelector(
              `#editorBody td[data-ds="${date}"][data-period="${period}"]`
            );
            
            if (td) {
              td.scrollIntoView({ behavior: 'smooth', block: 'center' });
              td.classList.add('pulse-highlight');
              setTimeout(() => td.classList.remove('pulse-highlight'), 1500);
            }
            
            return;
          }
          
          // 添加到編輯區
          console.log('✅ 添加到編輯區:', { date, period, name, time });
          addToDraft(date, period, name, time, true);  // true = 顯示高亮動畫
          
          // 視覺反饋 - 藍色條變綠色並縮放
          const originalBg = bar.style.background;
          bar.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
          bar.style.transform = 'scale(1.08)';
          
          // 添加勾選圖示
          const originalHtml = bar.innerHTML;
          bar.innerHTML = `<i class="fas fa-check-circle me-1"></i>` + originalHtml;
          
          setTimeout(() => {
            bar.style.background = originalBg;
            bar.style.transform = 'scale(1)';
            bar.innerHTML = originalHtml;
          }, 1500);
          
          // 滾動到編輯區
          const td = document.querySelector(
            `#editorBody td[data-ds="${date}"][data-period="${period}"]`
          );
          
          if (td) {
            td.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
        
        // 添加 hover 效果提示
        bar.addEventListener('mouseenter', function() {
          this.style.transform = 'scale(1.05)';
          this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.25)';
        });
        
        bar.addEventListener('mouseleave', function() {
          if (!this.style.transform.includes('1.08')) {
            this.style.transform = 'scale(1)';
          }
          this.style.boxShadow = '0 6px 16px rgba(0,0,0,.12)';
        });
      });
    }

    // ===== 編輯班表 =====
    function ensureDraftKey(ds) {
      if (!draftAssignedMap[ds]) {
        console.log('🔧 初始化日期:', ds);
        draftAssignedMap[ds] = { '上午': [], '晚上': [] };
      }
    }

    function inDraft(ds, period, name) {
      return (draftAssignedMap[ds]?.[period] || []).some(x => x.name === name);
    }

    function addToDraft(ds, period, name, time, showHighlight = false) {
      console.log('📝 addToDraft 被調用:', { ds, period, name, time, showHighlight });
      
      ensureDraftKey(ds);
      
      console.log('📦 當前 draftAssignedMap[ds]:', draftAssignedMap[ds]);
      
      if (!inDraft(ds, period, name)) {
        draftAssignedMap[ds][period].push({ name, time, note: '' });
        console.log('✅ 成功添加到草稿:', { ds, period, name, time });
        console.log('📊 更新後的列表:', draftAssignedMap[ds][period]);
        
        // ⭐ 關鍵修改:先檢查 td 是否存在
        const td = document.querySelector(
          `#editorBody td[data-ds="${ds}"][data-period="${period}"]`
        );
        
        if (td) {
          renderEditorCell(ds, period, showHighlight);
        } else {
          console.warn('⚠️ 表格尚未渲染該日期,稍後會自動顯示:', ds);
          // 數據已經加到 draftAssignedMap,當表格渲染時會自動顯示
        }
      } else {
        console.log('⚠️ 該員工已存在於草稿中');
      }
    }

    function removeFromDraft(ds, period, name) {
      if (draftAssignedMap[ds]?.[period]) {
        draftAssignedMap[ds][period] = draftAssignedMap[ds][period].filter(
          x => x.name !== name
        );
        renderEditorCell(ds, period);
      }
    }

    function upsertDraft(ds, period, name, time, originalName = null) {
      ensureDraftKey(ds);
      
      if (originalName && originalName !== name) {
        removeFromDraft(ds, period, originalName);
      }
      
      const list = draftAssignedMap[ds][period];
      const existing = list.find(x => x.name === name);
      
      if (existing) {
        existing.time = time;
      } else {
        list.push({ name, time, note: '' });
      }
      
      renderEditorCell(ds, period);
    }

    function renderEditorHeader(monday) {
      const headRow = document.getElementById('editorHeaderRow');
      headRow.querySelectorAll('th:nth-child(n+2)').forEach(th => th.remove());
      
      const labels = ['一', '二', '三', '四', '五', '六', '日'];
      daysOfWeek(monday).forEach((d, i) => {
        const th = document.createElement('th');
        th.innerHTML = `${d.getMonth() + 1}/${d.getDate()}<br>星期${labels[i]}`;
        headRow.appendChild(th);
      });
    }

    function renderEditorGrid(monday) {
      const tbody = document.getElementById('editorBody');
      tbody.innerHTML = '';
      
      PERIODS.forEach(period => {
        const tr = document.createElement('tr');
        const th = document.createElement('th');
        th.className = 'bg-light';
        th.textContent = period;
        tr.appendChild(th);

        daysOfWeek(monday).forEach(d => {
          const ds = fmt(d);
          ensureDraftKey(ds);
          
          const td = document.createElement('td');
          td.dataset.ds = ds;
          td.dataset.period = period;
          td.innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-assign-btn">
              <i class="fas fa-plus me-1"></i>新增
            </button>
          `;
          
          tr.appendChild(td);

          td.querySelector('.add-assign-btn').addEventListener('click', () => 
            openAssignModal({ ds, period })
          );
          
          renderEditorCell(ds, period);
        });

        tbody.appendChild(tr);
      });
    }

    function renderEditorCell(ds, period, highlightNew = false) {
      console.log('🎨 renderEditorCell:', { ds, period, highlightNew });
      
      const td = document.querySelector(
        `#editorBody td[data-ds="${ds}"][data-period="${period}"]`
      );
      
      if (!td) {
        console.error('❌ 找不到對應的 td 元素:', { ds, period });
        console.log('📋 當前週一:', fmt(currentMonday));
        console.log('📅 嘗試渲染的日期:', ds);
        
        // 檢查日期是否在當前週範圍內
        const monday = currentMonday;
        const weekDates = daysOfWeek(monday).map(d => fmt(d));
        console.log('📆 當前週的日期範圍:', weekDates);
        
        if (!weekDates.includes(ds)) {
          console.warn('⚠️ 該日期不在當前週範圍內,無法渲染');
        }
        
        return;
      }
      
      console.log('✅ 找到 td 元素');
      
      const wrap = td.querySelector('div');
      wrap.innerHTML = '';
      
      const list = draftAssignedMap[ds]?.[period] || [];
      console.log('📝 要渲染的員工列表:', list);
      
      list.forEach(({ name, time }, index) => {
        const chip = document.createElement('span');
        chip.className = 'badge text-bg-primary assign-chip d-inline-flex align-items-center';
        chip.innerHTML = `
          <i class="fas fa-user me-1"></i>${name}
          <small class="opacity-75 ms-1">${time || ''}</small>
          <button type="button" class="btn btn-light btn-sm chip-btn ms-2" title="修改">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn btn-light btn-sm chip-btn" title="移除">×</button>
        `;
        
        const [btnEdit, btnDel] = chip.querySelectorAll('button');
        btnEdit.addEventListener('click', () => 
          openAssignModal({ ds, period, name, time })
        );
        btnDel.addEventListener('click', () => 
          removeFromDraft(ds, period, name)
        );
        
        wrap.appendChild(chip);
        
        // 如果是新添加的(最後一個),添加高亮動畫
        if (highlightNew && index === list.length - 1) {
          console.log('✨ 添加高亮動畫');
          chip.classList.add('chip-highlight');
          setTimeout(() => {
            chip.classList.remove('chip-highlight');
          }, 1500);
        }
      });
      
      // 讓表格單元格也閃一下
      if (highlightNew) {
        console.log('✨ 單元格閃爍動畫');
        td.classList.add('cell-flash');
        setTimeout(() => {
          td.classList.remove('cell-flash');
        }, 1500);
      }
      
      console.log('✅ renderEditorCell 完成');
    }

    // ===== Modal =====
    const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    const assignForm = document.getElementById('assignForm');
    const assignNameSelect = document.getElementById('assignNameSelect');

    function openAssignModal({ ds, period, name = '', time = '' }) {
      document.getElementById('assignDs').value = ds;
      document.getElementById('assignPeriod').value = period;
      document.getElementById('assignOriginalName').value = name || '';
      document.getElementById('assignModalTitle').textContent = 
        name ? '修改人員' : '新增人員';

      assignNameSelect.value = name || '';

      let start = '', end = '';
      if (time && time.includes('-')) {
        [start, end] = time.split('-');
      }
      document.getElementById('assignStart').value = start || '';
      document.getElementById('assignEnd').value = end || '';

      assignModal.show();
    }

    assignForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const ds = document.getElementById('assignDs').value;
      const period = document.getElementById('assignPeriod').value;
      const originalName = document.getElementById('assignOriginalName').value || null;
      const name = assignNameSelect.value;
      const start = document.getElementById('assignStart').value;
      const end = document.getElementById('assignEnd').value;

      if (!name || !start || !end) {
        if (!name) alert('請選擇姓名');
        return;
      }
      
      const time = `${start}-${end}`;
      upsertDraft(ds, period, name, time, originalName);
      assignModal.hide();
    });

    // ===== 儲存班表 =====
    async function saveDraft(monday) {
      const payload = { 
        week_start: fmt(monday), 
        assignments: {} 
      };
      
      daysOfWeek(monday).forEach(d => {
        const ds = fmt(d);
        payload.assignments[ds] = {};
        
        PERIODS.forEach(period => {
          payload.assignments[ds][period] = (draftAssignedMap[ds]?.[period] || [])
            .map(x => ({
              name: x.name,
              time: x.time,
              note: x.note || ''
            }));
        });
      });

      try {
        const result = await fetchJSON('確認班表.php', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        if (result && result.success) {
          await loadSchedulePreview(currentMonday);
          alert('班表已確認並儲存!');
        } else {
          alert('儲存失敗: ' + (result?.error || '未知錯誤'));
        }
      } catch (err) {
        console.error('儲存班表錯誤', err);
        alert('儲存班表失敗,請稍後再試');
      }
    }

    // ===== 刷新流程 =====
    const defaultDateForLoad = new Date();
    defaultDateForLoad.setDate(defaultDateForLoad.getDate() + 7);
    
    let currentMonday = getMonday(defaultDateForLoad);

    async function refreshAll() {
      console.log('🔄 開始刷新所有數據...');
      
      renderWeekHeader(currentMonday);
      renderEditorHeader(currentMonday);
      
      await loadSchedulePreview(currentMonday);
      draftAssignedMap = JSON.parse(JSON.stringify(scheduleAssignedMap));
      
      // ⭐ 先渲染編輯表格
      renderEditorGrid(currentMonday);
      
      await loadAvailability(currentMonday);
      
      // ⭐ 再渲染 Gantt 圖和按鈕
      renderDayButtons(currentMonday);
      
      console.log('✅ 所有數據刷新完成');
    }

    // ===== 事件綁定 =====
    document.getElementById('btnQuery').addEventListener('click', async () => {
      currentMonday = getMonday(selectedDate());
      await refreshAll();
    });

    document.getElementById('btnSaveDraft').addEventListener('click', () => 
      saveDraft(currentMonday)
    );

    document.getElementById('btnClearDraft').addEventListener('click', () => {
      if (!confirm('確定要清空本週的草稿嗎?')) return;
      draftAssignedMap = {};
      renderEditorGrid(currentMonday);
    });

    // 側欄開關
    document.getElementById('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    // 今日日期
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      });
    }

    // ===== 頁面初始化 =====
    window.addEventListener('DOMContentLoaded', async () => {
      await loadEmployeeList();
      initDateSelectors();
      await refreshAll();
    });
  </script>
</body>
</html>