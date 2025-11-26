<?php
// /lamian-ukn/員工資料表.php
// ✅ 只有 A 級（老闆）可以訪問此頁
// 🔥 已套用 index.php 的外觀版型
// 🔥 (已補上按鈕美化 CSS)
// 🔥 (已新增 nowrap 讓表格可水平捲動)

require_once __DIR__ . '/includes/auth_check.php';

// 想「無權限就顯示禁止頁」→ 用這組
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}
// 若你想「無權限就導回各自首頁」→ 改用這行即可：
// require_level_A();

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '員工資料表 - 員工管理系統'; // 標題統一樣式

// 統一路徑：後端 API 與資料 API (從 index.php 複製過來，JS 會用到)
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

    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        /* ... (您的 CSS 樣式表，保持不變) ... */
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
        
        /* 美化搜尋區域 (from index.php) */
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
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .search-container:focus-within {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
            position: relative;
            overflow: hidden;
        }
        .search-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(251, 185, 124, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .search-btn:hover::before {
            width: 80px;
            height: 80px;
        }
        .search-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }
        .search-btn:active {
            transform: scale(0.95);
        }
        .search-btn i {
            color: #ff6b6b;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

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
        
        .table-container { }
        .table {
            border-radius:var(--border-radius);
            overflow:hidden;
            background:#fff;
        }
        .table thead th {
            background:var(--primary-gradient);
            color:#000;
            border:none;
            font-weight:600;
            padding:15px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap; /* 🔥 這裡：防止表頭換行 */
        }
        .table tbody td {
            padding:15px;
            vertical-align:middle;
            border-color:rgba(0,0,0,.05);
            text-align: center;
            white-space: nowrap; /* 🔥 這裡：防止儲存格內容換行 (包含地址) */
        }
        .table tbody tr:hover{background:rgba(227,23,111,.05); } 
        
        .breadcrumb{background:rgba(255,255,255,.8);border-radius:var(--border-radius);padding:15px 20px;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
        footer{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7))!important;border-top:1px solid rgba(0,0,0,.1);backdrop-filter:blur(10px)}
        .loading-shimmer{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.6s infinite}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .user-avatar{border:2px solid rgba(255,255,255,.5)}

        /* * --- 按鈕和表單樣式 --- */
        .btn-primary { 
            background: var(--primary-gradient); 
            border: none; 
            border-radius: 25px; 
            padding: 0.5rem 1.25rem;
            color: #fff; 
        }
        .btn-primary:hover { 
            transform: scale(1.05); 
            box-shadow: 0 10px 25px rgba(209, 209, 209, 0.976); 
            background: var(--primary-gradient); 
            color: #fff; 
        }
        .btn-outline-secondary {
            border-radius: 25px;
            padding: 0.5rem 1.25rem;
        }
        .form-control {
             border-radius: 12px;
        }
        .input-group > .form-control {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .input-group > .btn-outline-secondary {
            border-top-right-radius: 25px;
            border-bottom-right-radius: 25px;
        }


        @media (max-width:768px){.container-fluid{padding:15px!important}h1{font-size:2rem}}
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
                                <a class="nav-link active" href="員工資料表.php">員工資料表</a>
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

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>員工資料表</h1>
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="currentDate"></span>
                        </div>
                    </div>

                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
                        <li class="breadcrumb-item active">員工資料表</li>
                    </ol>

                    <div class="d-flex justify-content-end align-items-center mb-3 gap-2">
                        <button class="btn btn-primary" onclick="loadEmployees()">重新載入</button>
                        <button class="btn btn-primary" onclick="openAddEmployeeModal()">新增</button>
                        <div class="input-group" style="width:300px;">
                            <input type="text" class="form-control" placeholder="搜尋員工編號 / 姓名" id="searchInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchEmployees()">搜尋</button>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            員工清單
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>員工編號</th>
                                        <th>姓名</th>
                                        <th>出生年月日</th>
                                        <th>電話</th>
                                        <th>Email</th>
                                        <th>地址</th>
                                        <th>身份證</th>
                                        <th>雇用類別</th> <th>職位</th>
                                        <th>底薪</th>
                                        <th>時薪</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody id="employeeTable"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            <div class="modal fade" id="addEmployeeModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">新增員工</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addEmployeeForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">姓名 *</label>
                                        <input type="text" class="form-control" id="addName" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">出生年月日 *</label>
                                        <input type="date" class="form-control" id="addBirthDate" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">身份證字號 *</label>
                                        <input type="text" class="form-control" id="addIdCard" required maxlength="10" placeholder="A123456789">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">電話 *</label>
                                        <input type="tel" class="form-control" id="addTelephone" required placeholder="0912345678">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="addEmail" placeholder="example@email.com">
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">地址 *</label>
                                    <input type="text" class="form-control" id="addAddress" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">雇用類別 * (控制薪資)</label>
                                        <select class="form-select" id="addRole" required>
                                            <option value="">請選擇</option>
                                            <option value="正職">正職</option>
                                            <option value="臨時員工">臨時員工</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">權限等級 * (產生ID)</label>
                                        <select class="form-select" id="addPermissionLevel" required>
                                            <option value="">請選擇</option>
                                            <option value="A">A (老闆)</option>
                                            <option value="B">B (管理員)</option>
                                            <option value="C">C (員工)</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">職位 *</label>
                                        <input type="text" class="form-control" id="addPosition" required placeholder="例：店長、服務員">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div id="addSalaryHint" class="alert alert-info">
                                    請先選擇雇用類別
                                </div>
                                
                                <div class="row" id="addBaseSalaryGroup" style="display:none;">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">底薪 *</label>
                                        <input type="number" class="form-control" id="addBaseSalary" min="0" step="1000" placeholder="28000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row" id="addHourlyRateGroup" style="display:none;">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">時薪 *</label>
                                        <input type="number" class="form-control" id="addHourlyRate" min="0" step="10" placeholder="180">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" onclick="submitAddEmployee()">新增</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editEmployeeModal" tabindex="-1">
                 <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">編輯員工資料</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editEmployeeForm">
                                <input type="hidden" id="editId">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">姓名 *</label>
                                        <input type="text" class="form-control" id="editName" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">出生年月日 *</label>
                                        <input type="date" class="form-control" id="editBirthDate" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">身份證字號 *</label>
                                        <input type="text" class="form-control" id="editIdCard" required maxlength="10">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">電話 *</label>
                                        <input type="tel" class="form-control" id="editTelephone" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editEmail">
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">地址 *</label>
                                    <input type="text" class="form-control" id="editAddress" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">雇用類別 *</label>
                                        <select class="form-select" id="editRole" required>
                                            <option value="正職">正職</option>
                                            <option value="臨時員工">臨時員工</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">權限等級 *</label>
                                        <select class="form-select" id="editPermissionLevel" required>
                                            <option value="">請選擇</option>
                                            <option value="A">A (老闆)</option>
                                            <option value="B">B (管理員)</option>
                                            <option value="C">C (員工)</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">職位 *</label>
                                        <input type="text" class="form-control" id="editPosition" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row" id="editBaseSalaryGroup">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">底薪</label>
                                        <input type="number" class="form-control" id="editBaseSalary" min="0" step="1000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row" id="editHourlyRateGroup">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">時薪</label>
                                        <input type="number" class="form-control" id="editHourlyRate" min="0" step="10">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" onclick="submitEdit()">儲存</button>
                        </div>
                    </div>
                </div>
            </div>

            </main>
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
    
    <script src="員工資料表.js"></script>

    <script>
        // ---- 您的頁尾 JS 變數注入，保持不變 ----
        const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
        const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

        const $  = s => document.querySelector(s);
        const el = id => document.getElementById(id);

        // 今日日期 (此頁面也有 'currentDate')
        const dateEl = el('currentDate');
        if(dateEl) {
            dateEl.textContent = new Date().toLocaleDateString('zh-TW', {year:'numeric',month:'long',day:'numeric',weekday:'long'});
        }

        // 折起/展開側欄
        el('sidebarToggle')?.addEventListener('click', e => { 
            e.preventDefault(); 
            document.body.classList.toggle('sb-sidenav-toggled'); 
        });

        // 取得登入者資訊（已從 PHP Session 取得）
        async function loadLoggedInUser(){
            // 🔥 使用 PHP 傳遞的用戶資訊
            const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
            const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
            
            console.log('✅ 員工資料表 已登入:', userName, 'ID:', userId);
            
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
                // 即使失敗也不影響其他功能
            }
        }

       // 初始化
        window.addEventListener('DOMContentLoaded', async ()=>{
            await loadLoggedInUser();
            
            // 🔥 在這裡加上這一行，去執行 員工資料表.js 中的 loadEmployees
            if (typeof loadEmployees === 'function') {
                loadEmployees();
            } else {
                console.error('loadEmployees 函數未在 員工資料表.js 中定義');
            }
        });
    </script>
</body>
</html>