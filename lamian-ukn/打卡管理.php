<?php
// 🔥 新的 打卡管理.php (填寫頁面)
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

$pageTitle = '打卡管理 - 員工管理系統'; // 標題

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
  <title>打卡管理 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root{
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);
      --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --card-shadow: 0 15px 35px rgba(0,0,0,.1);
      --border-radius: 20px;
      --transition: all .3s cubic-bezier(.4,0,.2,1);
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
    
    *{transition:var(--transition)}
    body{background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh}

    .sb-topnav{background:var(--dark-bg)!important; border:none; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background:linear-gradient(45deg,#fff,#fff);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
    }
    .sb-sidenav{background:linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%)!important; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .sb-sidenav-menu-heading{color:rgba(255,255,255,.7)!important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px!important; margin-top:15px}
    .sb-sidenav .nav-link{border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter:blur(10px)}
    .sb-sidenav .nav-link:hover{background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important}
    .sb-sidenav .nav-link.active{background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15)}
    .sb-sidenav .nav-link::before{content:''; position:absolute; left:0; top:0; height:100%; width:4px; background:linear-gradient(45deg,#fff,#fff); transform:scaleY(0); border-radius:0 10px 10px 0}
    .sb-sidenav .nav-link:hover::before,.sb-sidenav .nav-link.active::before{transform:scaleY(1)}
    .sb-sidenav .nav-link i{width:20px; text-align:center; margin-right:10px; font-size:1rem}
    .sb-sidenav-footer{background:rgba(255,255,255,.1)!important; color:#fff!important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px}

    .container-fluid{padding:30px!important}
    h1{background:var(--primary-gradient); background-clip:text; -webkit-background-clip:text; color:transparent; -webkit-text-fill-color:transparent; font-weight:700; font-size:2.2rem; margin-bottom:0}
    .breadcrumb{background:rgba(255,255,255,.8); border-radius:var(--border-radius); padding:12px 16px; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .card{border:none; border-radius:var(--border-radius); box-shadow:var(--card-shadow); background:#fff}
    .card-header{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7)); font-weight:600}
    .table{border-radius:var(--border-radius); overflow:hidden; background:#fff; box-shadow:var(--card-shadow)}
    .table thead th{background:var(--primary-gradient); color:#000; border:none; font-weight:600; padding:12px}
    .table tbody td{padding:12px; vertical-align:middle; border-color:rgba(0,0,0,.05)}
    .table-hover tbody tr:hover{background:rgba(227,23,111,.05); transform:scale(1.01)}

    .badge-status{border-radius:999px; padding:.35rem .6rem; border:1px solid transparent}
    .badge-normal{background:rgba(25,135,84,.15); border-color:rgba(25,135,84,.35); color:#0f5132}
    .badge-ot{background:rgba(13,110,253,.15); border-color:rgba(13,110,253,.35); color:#084298}
    .badge-missing{background:rgba(220,53,69,.15); border-color:rgba(220,53,69,.35); color:#842029}

    .btn-primary{background:var(--primary-gradient)!important; border:none; border-radius:25px}
    .btn-primary:hover{transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.9)}
    .btn-outline-primary{border-radius:25px; border-color:#ff5a5a}

    .actions-bar{display:flex;gap:.5rem;justify-content:flex-end;flex-wrap:wrap;margin-top:.25rem}
    .btn-chip{
      --h:40px; --px:14px;
      height:var(--h); padding:0 var(--px);
      border-radius:999px; border:1px solid transparent;
      display:inline-flex; align-items:center; gap:.5rem;
      font-weight:600; letter-spacing:.02em;
      box-shadow:0 2px 8px rgba(0,0,0,.06);
      transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
    }
    .btn-chip .ic{font-size:15px; line-height:1}
    .btn-chip:hover{transform:translateY(-1px); box-shadow:0 6px 16px rgba(0,0,0,.08)}
    .btn-chip:active{transform:translateY(0); box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .btn-primary-lite{background:linear-gradient(135deg,#ff9b84 0%,#ff6a6a 100%); color:#fff; border-color:rgba(255,106,106,.25);}
    .btn-primary-lite:hover{filter:brightness(1.03)}
    .btn-ghost{background:#fff; color:#d35454; border-color:rgba(211,84,84,.35);}
    .btn-ghost:hover{background:#fff3f3}
    .btn-success-lite{background:linear-gradient(135deg,#2fb36a 0%,#238e54 100%); color:#fff; border-color:rgba(35,142,84,.25);}
    .btn-chip .dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.9); opacity:.75;}
    @media (max-width: 576px){ .btn-chip .tx{display:none} .btn-chip{--h:38px; --px:12px} }
    .filter-row .form-label{font-weight:600; color:#333}
    .filter-row .form-control{height:44px; border-radius:12px}
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
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
    <!-- 側欄 -->
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
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>會員管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="member-list.php">會員清單</a>
                <a class="nav-link" href="member-detail.php">詳細資料頁</a>
                <a class="nav-link" href="point-manage.php">點數管理</a>
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
            <a class="nav-link" href="tables.html"><div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables</a>
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
            <h1>打卡管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.html" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">打卡管理</li>
          </ol>

          <!-- 篩選列 -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-4 align-items-end">
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">開始日期</label>
                  <input type="date" class="form-control" id="start_date">
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">結束日期</label>
                  <input type="date" class="form-control" id="end_date">
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">員工</label>
                  <select class="form-control" id="employee_filter">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-lg-3 col-md-6">
                  <label class="form-label fw-semibold">狀態</label>
                  <select class="form-control" id="status_filter">
                    <option value="">全部</option>
                    <option value="正常">正常</option>
                    <option value="缺卡">缺卡</option>
                    <option value="加班">加班</option>
                  </select>
                </div>

                <div class="col-12 d-flex justify-content-end flex-wrap gap-3 pt-2">
                  <button class="btn btn-chip btn-primary-lite" id="btnSearch" type="button" title="查詢">
                    <i class="ic fas fa-search"></i><span class="tx">查詢</span>
                  </button>
                  <button class="btn btn-chip btn-ghost" id="btnClear" type="button" title="清除">
                    <i class="ic fas fa-eraser"></i><span class="tx">清除</span>
                  </button>
                  <button class="btn btn-chip btn-success-lite" id="btnExport" type="button" title="匯出 CSV">
                    <i class="ic fas fa-file-export"></i><span class="tx">匯出CSV</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 摘要 -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
              <div class="card text-white mb-4" style="background: var(--success-gradient);">
                <div class="card-body d-flex justify-content-between">
                  <div>
                    <div class="small text-white-50">總工時（小時）</div>
                    <div class="h5" id="sum_hours">-</div>
                  </div>
                  <i class="fas fa-clock fa-2x text-white-50"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card text-white mb-4" style="background: var(--primary-gradient);">
                <div class="card-body d-flex justify-content-between">
                  <div>
                    <div class="small text-white-50">出勤筆數</div>
                    <div class="h5" id="sum_records">-</div>
                  </div>
                  <i class="fas fa-list-check fa-2x text-white-50"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card text-white mb-4" style="background: var(--warning-gradient);">
                <div class="card-body d-flex justify-content-between">
                  <div>
                    <div class="small text-white-50">缺卡筆數</div>
                    <div class="h5" id="sum_missing">-</div>
                  </div>
                  <i class="fas fa-triangle-exclamation fa-2x text-white-50"></i>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card text-white mb-4" style="background: var(--secondary-gradient);">
                <div class="card-body d-flex justify-content-between">
                  <div>
                    <div class="small text-white-50">加班（小時）</div>
                    <div class="h5" id="sum_ot">-</div>
                  </div>
                  <i class="fas fa-bolt fa-2x text-white-50"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- 表格 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>打卡記錄列表</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead>
                    <tr>
                      <th>日期</th>
                      <th>員工姓名</th>
                      <th>員工編號</th>
                      <th>上班時間</th>
                      <th>下班時間</th>
                      <th>地點</th>
                      <th>工作時數</th>
                      <th>狀態</th>
                      <th style="width:140px">操作</th>
                    </tr>
                  </thead>
                  <tbody id="attTableBody">
                    <tr><td colspan="9" class="text-center text-muted py-4">載入中…</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 訊息 -->
          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div><a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a></div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- Modal：新增/編輯打卡 -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="editForm">
          <div class="modal-header">
            <h5 class="modal-title">編輯打卡</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="f_id">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">日期</label>
                <input type="date" class="form-control" id="f_date" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">員工編號（員工基本資料.id）</label>
                <input type="text" class="form-control" id="f_emp_id" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">上班時間</label>
                <input type="time" class="form-control" id="f_clock_in" step="60">
              </div>
              <div class="col-md-6">
                <label class="form-label">下班時間</label>
                <input type="time" class="form-control" id="f_clock_out" step="60">
              </div>
              <div class="col-md-6">
                <label class="form-label">狀態</label>
                <select id="f_status" class="form-select">
                  <option value="">自動判斷</option>
                  <option value="正常">正常</option>
                  <option value="加班">加班</option>
                  <option value="缺卡">缺卡</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">備註</label>
                <input type="text" class="form-control" id="f_note" placeholder="可留白">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">取消</button>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>儲存</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 今日日期 / 側欄
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    // === API 路徑 ===
    const API_BASE       = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_LIST       = API_BASE + '/clock_list.php';
    const API_DELETE     = API_BASE + '/clock_delete.php';
    const API_ADMIN_SAVE = API_BASE + '/clock_admin_save.php'; // 新增：管理端存檔

    // 小工具
    function parseHHMM(t){ if(!t) return null; const [h,m] = t.split(':').map(Number); if(Number.isNaN(h)||Number.isNaN(m)) return null; return h*60+m; }
    function minutesBetween(ci,co){ const a=parseHHMM(ci), b=parseHHMM(co); if(a==null||b==null) return null; let d=b-a; if(d<0) d+=1440; return d; }
    function hr2(mins){ return mins==null? '-' : (Math.round((mins/60)*100)/100).toFixed(2); }
    function inferStatus(ci,co,mins){ if(!ci||!co) return '缺卡'; if(mins!=null && mins>480) return '加班'; return '正常'; }
    function badge(status){
      if(status==='缺卡') return '<span class="badge-status badge-missing">缺卡</span>';
      if(status==='加班') return '<span class="badge-status badge-ot">加班</span>';
      return '<span class="badge-status badge-normal">正常</span>';
    }
    function showOk(m){ const a=document.getElementById('msgOk'); a.textContent=m; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'),2500);}
    function showErr(m){ const a=document.getElementById('msgErr'); a.textContent=m; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'),4000);}

    // 狀態
    let DATA = [];
    let timer = null;

    function setDefaultDates(){
      const end = new Date();
      const start = new Date(); start.setDate(end.getDate()-13);
      document.getElementById('end_date').value = end.toISOString().slice(0,10);
      document.getElementById('start_date').value = start.toISOString().slice(0,10);
    }

    function fillEmployeeFilter(rows){
      const sel = document.getElementById('employee_filter');
      const prev = sel.value;
      const ids = new Map(); // key: employee_id or emp_no, value: 顯示文字
      rows.forEach(r=>{
        const code = r.employee_id ?? r.emp_no ?? '';
        const name = r.emp_name ?? '';
        if(!code && !name) return;
        const label = code ? `${name}（${code}）` : name;
        ids.set(code || name, label);
      });
      sel.innerHTML = '<option value="">全部</option>' +
        Array.from(ids.entries()).map(([v,l])=>`<option value="${String(v).replace(/"/g,'&quot;')}">${l}</option>`).join('');
      if (ids.has(prev)) sel.value = prev; // 保留上一個選擇
    }

    async function loadList(){
      const p = new URLSearchParams();
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;
      const emp = document.getElementById('employee_filter').value;
      const st  = document.getElementById('status_filter').value;

      if(s) p.set('start_date', s);
      if(e) p.set('end_date', e);
      if(emp) p.set('q', emp); // 用 q 篩員工（姓名/編號/工號皆可）

      try{
        const r = await fetch(API_LIST + (p.toString()?('?'+p.toString()):''), {headers:{'Accept':'application/json'}});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const list = Array.isArray(rows)? rows : (rows.data||[]);
        // 前端再用狀態做一次過濾
        DATA = list.filter(x=>{
          if(!st) return true;
          const mins = minutesBetween(x.clock_in, x.clock_out);
          const status = inferStatus(x.clock_in, x.clock_out, mins);
          return status === st;
        });
        fillEmployeeFilter(list);
        render();
      }catch(err){
        console.error(err);
        document.getElementById('attTableBody').innerHTML =
          `<tr><td colspan="9" class="text-center text-danger py-4">載入失敗：${String(err.message)}</td></tr>`;
      }
    }

    function render(){
      const tbody = document.getElementById('attTableBody');
      if(!DATA.length){
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">目前沒有資料</td></tr>`;
        setSummary(0,0,0,0); return;
      }
      let total=0, miss=0, otMin=0;
      tbody.innerHTML = DATA.map(row=>{
        const mins = minutesBetween(row.clock_in, row.clock_out);
        const st = inferStatus(row.clock_in, row.clock_out, mins);
        total += (mins||0);
        if(st==='缺卡') miss++;
        if(st==='加班' && mins) otMin += (mins-480);
        const hrs = hr2(mins);
        const empCode = row.employee_id ?? row.emp_no ?? '';
        const ops = `
          <button class="btn btn-sm btn-outline-primary me-1" onclick='openEdit(${JSON.stringify(row).replace(/'/g,"&#39;")})'>
            <i class="fas fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="delRow(${row.id})">
            <i class="fas fa-trash"></i>
          </button>`;
        return `
          <tr>
            <td>${row.date??''}</td>
            <td>${row.emp_name??''}</td>
            <td>${empCode}</td>
            <td>${row.clock_in??''}</td>
            <td>${row.clock_out??''}</td>
            <td>—</td>
            <td>${hrs}</td>
            <td>${badge(st)}</td>
            <td>${ops}</td>
          </tr>`;
      }).join('');
      setSummary( (Math.round((total/60)*100)/100).toFixed(2), DATA.length, miss, (Math.round((otMin/60)*100)/100).toFixed(2) );
    }

    function setSummary(h, cnt, miss, ot){
      document.getElementById('sum_hours').textContent = h||'0.00';
      document.getElementById('sum_records').textContent = cnt||0;
      document.getElementById('sum_missing').textContent = miss||0;
      document.getElementById('sum_ot').textContent = ot||'0.00';
    }

    async function delRow(id){
      if(!confirm('確定要刪除此筆資料？')) return;
      try{
        const r = await fetch(API_DELETE + '?id=' + encodeURIComponent(id));
        const resp = await r.json();
        if(!r.ok || resp.error){ throw new Error(resp.error || ('HTTP '+r.status)); }
        showOk('已刪除');
        await loadList();
      }catch(err){
        console.error(err); showErr('刪除失敗：'+err.message);
      }
    }

    function exportCSV(){
      if(!DATA.length){ alert('目前沒有可匯出的資料'); return; }
      const headers = ['日期','員工姓名','員工編號','上班時間','下班時間','地點','工作時數','狀態'];
      const rows = DATA.map(r=>{
        const mins = minutesBetween(r.clock_in, r.clock_out);
        const st = inferStatus(r.clock_in, r.clock_out, mins);
        const empCode = r.employee_id ?? r.emp_no ?? r.user_id ?? '';
        return [ r.date||'', r.emp_name||'', empCode,
                 r.clock_in||'', r.clock_out||'', '—',
                 hr2(mins), st ];
      });
      const csv = [headers, ...rows].map(cols =>
        cols.map(v=> `"${String(v??'').replace(/"/g,'""')}"`).join(',')
      ).join('\r\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = '打卡管理_'+(new Date().toISOString().slice(0,10))+'.csv';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    // === 編輯 Modal ===
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    document.getElementById('editForm').addEventListener('submit', saveForm);

    function openEdit(row){
      document.getElementById('f_id').value        = row.id || '';
      document.getElementById('f_date').value      = row.date || '';
      document.getElementById('f_emp_id').value    = (row.employee_id ?? row.user_id ?? '');
      document.getElementById('f_clock_in').value  = row.clock_in || '';
      document.getElementById('f_clock_out').value = row.clock_out || '';
      document.getElementById('f_status').value    = row.status || '';
      document.getElementById('f_note').value      = row.note || '';
      editModal.show();
    }

    async function saveForm(e){
      e.preventDefault();
      const payload = {
        id:        (document.getElementById('f_id').value||'') || undefined,
        date:      document.getElementById('f_date').value,
        user_id:    document.getElementById('f_emp_id').value.trim(),
        clock_in:  document.getElementById('f_clock_in').value || null,
        clock_out: document.getElementById('f_clock_out').value || null,
        status:    document.getElementById('f_status').value || '',
        note:      document.getElementById('f_note').value.trim()
      };
      if(!payload.date)   return showErr('請填 日期');
      if(!payload.user_id) return showErr('請填 員工編號');

      try{
        const r = await fetch(API_ADMIN_SAVE, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials:'include'
        });
        const resp = await r.json();
        if(!r.ok || resp.error){ throw new Error(resp.detail || resp.error || ('HTTP '+r.status)); }
        editModal.hide();
        showOk('已儲存');
        await loadList();
      }catch(err){
        console.error(err);
        showErr('儲存失敗：'+err.message);
      }
    }

    // 綁定事件 & 初始化
    window.addEventListener('DOMContentLoaded', async ()=>{
      setDefaultDates();
      await loadList();

      document.getElementById('btnSearch').addEventListener('click', loadList);
      document.getElementById('btnClear').addEventListener('click', async ()=>{
        setDefaultDates();
        document.getElementById('employee_filter').value = '';
        document.getElementById('status_filter').value = '';
        await loadList();
      });
      document.getElementById('btnExport').addEventListener('click', exportCSV);

      // 自動刷新（8 秒）
      timer = setInterval(loadList, 8000);
    });
  </script>

  <script>// 取得登入者資訊（已從 PHP Session 取得）
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
      }</script>

  <script src="js/scripts.js"></script>
</body>
</html>