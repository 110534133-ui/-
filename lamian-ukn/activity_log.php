<?php
// 🔥 整合:加入權限檢查
// 這裡是員工個人頁面,只需要確認 "已登入"
// auth_check.php 會自動檢查登入,如果未登入會導向 login.html
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 整合:取得用戶資訊 (用於頂部導覽列)
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

// 🔥 整合:定義 API 路徑 (給 JS 使用)
$API_BASE_URL  = '/lamian-ukn/api';

/**
 * ==================================
 * 可調整參數
 * ==================================
 */
$PER_PAGE = 20;
$HAS_DB   = true;        // ✅ 啟用資料庫模式，從 edit_logs 讀取資料
$HAS_USERS_TABLE = false; // users(id,name) 可改 true 顯示人名

// 你的功能清單(下拉選單用)。key = 實際寫入資料庫的值,value = 顯示文字
$FEATURES = [
  ''          => '全部功能',
  'daily'     => '日報表',
  'attendance'=> '打卡管理',
  'payroll'   => '薪資管理',
  'profile'   => '員工資料',
  'inventory' => '庫存管理',
];

/**
 * ==================================
 * 讀取查詢參數(帶預設)
 * ==================================
 */
// 🔥 這裡改成「預設不限制日期」：不選 from/to = 全部資料
$from    = $_GET['from'] ?? '';
$to      = $_GET['to']   ?? '';
$feature = trim($_GET['feature'] ?? '');
$user_filter = trim($_GET['user'] ?? '');
$q       = trim($_GET['q']       ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));

/**
 * ==================================
 * 取得資料來源(DB 或 假資料)
 * 統一欄位:
 *  - feature (varchar)  ← 功能辨識
 *  - table_name 可留空或不使用
 * ==================================
 */
$rows = [];
$total = 0;

if ($HAS_DB) {
  try {
    // 設定你的 PDO
    $dsn = 'mysql:host=127.0.0.1;dbname=lamian;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 動態 WHERE
    $where = [];
    $bind  = [];

    if ($from !== '') { $where[] = 'el.created_at >= :from'; $bind[':from'] = $from . ' 00:00:00'; }
    if ($to   !== '') { $where[] = 'el.created_at <= :to';   $bind[':to']   = $to   . ' 23:59:59'; }
    if ($feature !== '') { $where[] = 'el.feature = :feature'; $bind[':feature'] = $feature; }
    if ($user_filter  !== '') { $where[] = 'el.user_id = :user_id';   $bind[':user_id'] = $user_filter; }
    if ($q     !== '') { $where[] = 'el.summary LIKE :q';      $bind[':q'] = "%$q%"; }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // 計數
    $sqlCount = "SELECT COUNT(*) FROM edit_logs el $whereSql";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute($bind);
    $total = (int)$stmt->fetchColumn();

    // 取資料
    $offset = ($page - 1) * $PER_PAGE;
    if ($HAS_USERS_TABLE) {
      $sql = "SELECT el.id, el.user_id, u.name AS user_name, el.feature, el.table_name, el.record_id, el.action,
                     el.summary, el.old_data, el.new_data, el.ip, el.created_at
              FROM edit_logs el
              LEFT JOIN users u ON el.user_id = u.id
              $whereSql
              ORDER BY el.created_at DESC
              LIMIT :offset,:limit";
    } else {
      $sql = "SELECT el.id, el.user_id, el.feature, el.table_name, el.record_id, el.action,
                     el.summary, el.old_data, el.new_data, el.ip, el.created_at
              FROM edit_logs el
              $whereSql
              ORDER BY el.created_at DESC
              LIMIT :offset,:limit";
    }
    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k=>$v) {
      $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit',  (int)$PER_PAGE, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
  } catch(Exception $e) {
    // 若 DB 出錯, fallback 假資料
    $HAS_DB = false;
    $rows = [];
    $total = 0;
    $db_error = $e->getMessage();
  }
}

if (!$HAS_DB) {
  // 假資料 (當 DB 尚未接好時使用)
  $allMock = [
    [
      'id'=>1,'user_id'=>110534101,'user_name'=>'林宜伶',
      'feature'=>'daily','table_name'=>'日報表','record_id'=>101,
      'action'=>'INSERT',
      'summary'=>'新增 2025-10-25 日報表：營收 25,000 元',
      'old_data'=>null,
      'new_data'=>json_encode(['date'=>'2025-10-25','revenue'=>25000,'note'=>'週末營收佳'], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-25 21:35:12'
    ],
    [
      'id'=>2,'user_id'=>110534101,'user_name'=>'林宜伶',
      'feature'=>'attendance','table_name'=>'打卡紀錄','record_id'=>509,
      'action'=>'UPDATE',
      'summary'=>'修正員工 王小明 2025-10-25 下班時間(誤打 18:00 → 22:10)',
      'old_data'=>json_encode(['employee'=>'王小明','check_in'=>'10:00','check_out'=>'18:00'], JSON_UNESCAPED_UNICODE),
      'new_data'=>json_encode(['employee'=>'王小明','check_in'=>'10:00','check_out'=>'22:10'], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-25 21:42:00'
    ],
    [
      'id'=>3,'user_id'=>110534101,'user_name'=>'林宜伶',
      'feature'=>'attendance','table_name'=>'打卡紀錄','record_id'=>510,
      'action'=>'INSERT',
      'summary'=>'新增員工 黑松 2025-10-25 上班打卡',
      'old_data'=>null,
      'new_data'=>json_encode(['employee'=>'黑松','check_in'=>'09:00'], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-25 09:10:03'
    ],
    [
      'id'=>4,'user_id'=>110534101,'user_name'=>'林宜伶',
      'feature'=>'profile','table_name'=>'員工基本資料','record_id'=>12,'action'=>'DELETE',
      'summary'=>'刪除離職員工紀錄',
      'old_data'=>json_encode(['name'=>'李小華','status'=>'離職'], JSON_UNESCAPED_UNICODE),
      'new_data'=>null,
      'ip'=>'127.0.0.1','created_at'=>'2025-10-22 14:52:11'
    ],
    [
      'id'=>5,'user_id'=>1,'user_name'=>'管理者',
      'feature'=>'payroll','table_name'=>'薪資表','record_id'=>202510,'action'=>'UPDATE',
      'summary'=>'調整 10 月底薪資：「職務加給 +2,000」',
      'old_data'=>json_encode(['base'=>32000,'bonus'=>3000], JSON_UNESCAPED_UNICODE),
      'new_data'=>json_encode(['base'=>32000,'bonus'=>5000], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-21 19:20:00'
    ],
  ];

  // 簡單依查詢條件過濾假資料
  $filtered = array_filter($allMock, function($r) use($from,$to,$feature,$user_filter,$q){
    if ($from !== '' && substr($r['created_at'],0,10) < $from) return false;
    if ($to   !== '' && substr($r['created_at'],0,10) > $to)   return false;
    if ($feature !== '' && $r['feature'] !== $feature) return false;
    if ($user_filter !== '' && (string)$r['user_id'] !== $user_filter) return false;
    if ($q !== '' && mb_strpos($r['summary'],$q) === false) return false;
    return true;
  });

  $total = count($filtered);
  $rows  = array_slice(array_values($filtered), ($page-1)*$PER_PAGE, $PER_PAGE);
}

/**
 * ==================================
 * 計算分頁資訊
 * ==================================
 */
$totalPages = max(1, ceil($total / $PER_PAGE));

/**
 * ==================================
 * HTML開始
 * ==================================
 */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>系統修改紀錄 - 員工管理系統</title>

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
    body{
      background:linear-gradient(135deg,#fff 0%,#fff 100%);
      font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
      min-height:100vh
    }
    .sb-topnav{
      background:var(--dark-bg)!important;
      border:none;
      box-shadow:var(--card-shadow);
      backdrop-filter:blur(10px)
    }
    .navbar-brand{
      font-weight:700;
      font-size:1.5rem;
      background:linear-gradient(45deg,#fff,#fff);
      -webkit-background-clip:text;
      background-clip:text;
      color:transparent;
      -webkit-text-fill-color:transparent
    }

    .sb-sidenav{
      background:linear-gradient(180deg,#fbb97ce4 0%,#ff00006a 100%)!important;
      box-shadow:var(--card-shadow);
      backdrop-filter:blur(10px)
    }
    .sb-sidenav-menu-heading{
      color:rgba(255,255,255,.7)!important;
      font-weight:600;
      font-size:.85rem;
      text-transform:uppercase;
      letter-spacing:1px;
      padding:20px 15px 10px!important;
      margin-top:15px
    }
    .sb-sidenav .nav-link{
      border-radius:15px;
      margin:5px 15px;
      padding:12px 15px;
      position:relative;
      overflow:hidden;
      color:rgba(255,255,255,.9)!important;
      font-weight:500;
      backdrop-filter:blur(10px)
    }
    .sb-sidenav .nav-link:hover{
      background:rgba(255,255,255,.15)!important;
      transform:translateX(8px);
      box-shadow:0 8px 25px rgba(0,0,0,.2);
      color:#fff!important
    }
    .sb-sidenav .nav-link.active{
      background:rgba(255,255,255,.2)!important;
      color:#fff!important;
      font-weight:600;
      box-shadow:0 8px 25px rgba(0,0,0,.15)
    }
    .sb-sidenav .nav-link::before{
      content:'';
      position:absolute;
      left:0;
      top:0;
      height:100%;
      width:4px;
      background:linear-gradient(45deg,#fff,#fff);
      transform:scaleY(0);
      border-radius:0 10px 10px 0
    }
    .sb-sidenav .nav-link:hover::before,
    .sb-sidenav .nav-link.active::before{
      transform:scaleY(1)
    }
    .sb-sidenav .nav-link i{
      width:20px;
      text-align:center;
      margin-right:10px;
      font-size:1rem
    }
    .sb-sidenav-menu-nested .nav-link{
      padding-left:45px;
      font-size:.9rem;
      background:rgba(255,255,255,.05)!important;
      margin:2px 15px;
      border-radius:10px
    }
    .sb-sidenav-menu-nested .nav-link:hover{
      background:rgba(255,255,255,.1)!important;
      transform:translateX(5px);
      padding-left:50px
    }
    .sb-sidenav-footer{
      background:rgba(255,255,255,.1)!important;
      color:#fff!important;
      border-top:1px solid rgba(255,255,255,.2);
      padding:20px 15px;
      margin-top:20px
    }
    .sb-sidenav-footer .small{
      color:rgba(255,255,255,.7)!important;
      font-size:.8rem
    }

    .container-fluid{padding:30px!important}
    h1{
      background:var(--primary-gradient);
      -webkit-background-clip:text;
      background-clip:text;
      -webkit-text-fill-color:transparent;
      font-weight:700;
      font-size:2.5rem;
      margin-bottom:30px
    }
    .breadcrumb{
      background:rgba(255,255,255,.8);
      border-radius:var(--border-radius);
      padding:15px 20px;
      box-shadow:var(--card-shadow);
      backdrop-filter:blur(10px)
    }

    .card{
      border:none;
      border-radius:var(--border-radius);
      box-shadow:var(--card-shadow);
      backdrop-filter:blur(10px);
      background:rgba(255,255,255,.95);
      overflow:hidden;
      position:relative
    }
    .card::before{
      content:'';
      position:absolute;
      top:0;
      left:0;
      right:0;
      height:4px;
      background:var(--primary-gradient)
    }
    .card-header{
      border-bottom:none;
      background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(255,255,255,.8));
      display:flex;
      align-items:center;
      justify-content:space-between;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      padding: 20px 25px;
    }
    .card-header h5{
      margin:0;
      font-weight:700;
      color:#333;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .card-header h5 i{ color:#ff6b6b; }
    .card-body{ padding:25px; }

    .filter-row{
      display:flex;
      flex-wrap:wrap;
      gap:15px;
      align-items:flex-end;
      margin-bottom:20px;
    }
    .filter-row .form-label{
      font-size:0.85rem;
      font-weight:600;
      color:#555;
    }
    .filter-row .form-control,
    .filter-row .form-select{
      border-radius:12px;
      border:1px solid #e0e0e0;
      font-size:0.9rem;
    }
    .filter-row .btn{
      border-radius:999px;
      padding:8px 18px;
      font-size:0.9rem;
    }

    .badge-feature{
      border-radius:999px;
      padding:4px 10px;
      font-size:0.75rem;
      font-weight:600;
    }
    .badge-feature.daily{ background:#fff3cd; color:#856404; }
    .badge-feature.attendance{ background:#e2f0ff; color:#004085; }
    .badge-feature.payroll{ background:#e2f7e2; color:#155724; }
    .badge-feature.profile{ background:#f8d7da; color:#721c24; }
    .badge-feature.inventory{ background:#d1ecf1; color:#0c5460; }

    .table thead th{
      background:#f8f9fa;
      border-bottom:2px solid #e9ecef;
      font-size:0.85rem;
      text-transform:uppercase;
      letter-spacing:0.03em;
    }
    .table tbody td{
      font-size:0.9rem;
      vertical-align:middle;
    }
    .table tbody tr:hover{
      background:rgba(251,185,124,0.07);
    }

    .text-muted-small{ font-size:0.8rem; color:#888; }

    .pagination{
      margin:0;
    }
    .pagination .page-link{
      border-radius:999px!important;
      margin:0 3px;
      font-size:0.85rem;
    }

    /* Modal */
    .modal-content{
      border:none;
      border-radius:20px;
      box-shadow:0 15px 35px rgba(0,0,0,0.2);
      overflow:hidden;
    }
    .modal-header{
      background:var(--primary-gradient);
      color:#fff;
      border-bottom:none;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      padding: 20px 25px;
    }
    .modal-title{ font-weight: 700; }
    .btn-close{ filter: brightness(0) invert(1); }
    .modal-body{ padding: 25px; }
    
    pre{
      white-space:pre-wrap; 
      word-break:break-word;
      background: #f8f9fa;
      border-radius: 12px;
      padding: 15px;
      font-size: 0.9rem;
      line-height: 1.6;
    }

    .user-avatar{border:2px solid rgba(255,255,255,.5)}
    @media (max-width:768px){
      .container-fluid{padding:15px!important}
      h1{font-size:2rem}
    }
  </style>
</head>
<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-1" src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>" width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
          <span id="navUserName"><?php echo htmlspecialchars($userName); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="帳號設置.php">帳號設置</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="login.php"><i class="fas fa-right-from-bracket me-2"></i>登出</a></li>
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
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
              首頁
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

                <a class="nav-link" href="日報表.php">
                  <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表
                </a>
              </nav>
            </div>

            <a class="nav-link" href="activity_log.php">
              <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
              修改紀錄
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
            <h1>系統修改紀錄</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active"><i class="fas fa-history me-2"></i>修改紀錄</li>
          </ol>

          <div class="card mb-4">
            <div class="card-header">
              <h5><i class="fas fa-filter"></i> 篩選條件</h5>
            </div>
            <div class="card-body">
              <form method="get" class="filter-row">
                <div class="col-md-2">
                  <label class="form-label">起始日期</label>
                  <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">結束日期</label>
                  <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">功能</label>
                  <select name="feature" class="form-select">
                    <?php foreach($FEATURES as $k=>$v): ?>
                      <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $k===$feature?'selected':''; ?>>
                        <?php echo htmlspecialchars($v); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">使用者ID</label>
                  <input type="text" name="user" class="form-control" value="<?php echo htmlspecialchars($user_filter); ?>" placeholder="110534101">
                </div>
                <div class="col-md-3">
                  <label class="form-label">關鍵字(摘要)</label>
                  <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="例如: 日報表、新增、刪除">
                </div>
                <div class="col-md-1 text-end">
                  <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-search me-1"></i>查詢
                  </button>
                  <a href="activity_log.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-rotate-left me-1"></i>重置
                  </a>
                </div>
              </form>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header">
              <h5><i class="fas fa-list"></i> 修改紀錄列表</h5>
              <div class="text-muted-small">
                共 <?php echo $total; ?> 筆資料，頁數 <?php echo $page . ' / ' . $totalPages; ?>
                <?php if(isset($db_error)): ?>
                  <br><span class="text-danger">資料庫連線錯誤：<?php echo htmlspecialchars($db_error); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th style="width:80px;">ID</th>
                      <th style="width:130px;">時間</th>
                      <th style="width:100px;">使用者</th>
                      <th style="width:120px;">功能</th>
                      <th>摘要</th>
                      <th style="width:90px;">動作</th>
                      <th style="width:90px;">IP</th>
                      <th style="width:90px;">詳細</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($rows)): ?>
                      <tr><td colspan="8" class="text-center text-muted">目前沒有符合條件的修改紀錄</td></tr>
                    <?php else: ?>
                      <?php foreach($rows as $r): 
                        $badgeClass = 'badge-secondary';
                        if ($r['feature']==='daily')       $badgeClass='badge-feature daily';
                        elseif ($r['feature']==='attendance') $badgeClass='badge-feature attendance';
                        elseif ($r['feature']==='payroll')    $badgeClass='badge-feature payroll';
                        elseif ($r['feature']==='profile')    $badgeClass='badge-feature profile';
                        elseif ($r['feature']==='inventory')  $badgeClass='badge-feature inventory';
                      ?>
                        <tr>
                          <td><?php echo (int)$r['id']; ?></td>
                          <td>
                            <?php echo htmlspecialchars($r['created_at']); ?>
                          </td>
                          <td>
                            <?php 
                              echo htmlspecialchars($r['user_id']);
                              if (!empty($r['user_name'])) {
                                echo "<br><span class='text-muted-small'>".htmlspecialchars($r['user_name'])."</span>";
                              }
                            ?>
                          </td>
                          <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                              <?php 
                                $f = $r['feature'] ?? '';
                                echo htmlspecialchars($FEATURES[$f] ?? $f);
                              ?>
                            </span>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($r['summary']); ?>
                            <?php if (!empty($r['table_name']) || !empty($r['record_id'])): ?>
                              <div class="text-muted-small">
                                <?php if(!empty($r['table_name'])): ?>
                                  表：<?php echo htmlspecialchars($r['table_name']); ?>
                                <?php endif; ?>
                                <?php if(!empty($r['record_id'])): ?>
                                  ．ID：<?php echo htmlspecialchars($r['record_id']); ?>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($r['action']); ?>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($r['ip'] ?? ''); ?>
                          </td>
                          <td>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                              data-bs-toggle="modal"
                              data-bs-target="#logDetailModal"
                              data-log='<?php echo json_encode($r, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>'>
                              <i class="fas fa-eye"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- 分頁 -->
              <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center">
                  <?php
                    $buildQuery = function($p) use($from,$to,$feature,$user_filter,$q){
                      $params = [
                        'page'=>$p,
                        'from'=>$from,
                        'to'=>$to,
                        'feature'=>$feature,
                        'user'=>$user_filter,
                        'q'=>$q
                      ];
                      return 'activity_log.php?' . http_build_query($params);
                    };
                  ?>
                  <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $buildQuery(1); ?>" aria-label="First">
                      <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                  </li>
                  <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $buildQuery(max(1,$page-1)); ?>" aria-label="Previous">
                      <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>
                  <?php
                    $startPage = max(1, $page-2);
                    $endPage   = min($totalPages, $page+2);
                    for($p=$startPage; $p <= $endPage; $p++):
                  ?>
                    <li class="page-item <?php echo $p==$page?'active':''; ?>">
                      <a class="page-link" href="<?php echo $buildQuery($p); ?>"><?php echo $p; ?></a>
                    </li>
                  <?php endfor; ?>
                  <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $buildQuery(min($totalPages,$page+1)); ?>" aria-label="Next">
                      <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>
                  <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $buildQuery($totalPages); ?>" aria-label="Last">
                      <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                  </li>
                </ul>
              </nav>

            </div>
          </div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">© 2025 拉麵店經營系統 - ukn</div>
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

  <!-- 詳細內容 Modal -->
  <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logDetailModalLabel">修改紀錄詳細內容</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <strong>摘要：</strong><span id="detailSummary"></span>
          </div>
          <div class="mb-2 text-muted-small">
            <span>功能：<span id="detailFeature"></span></span>／
            <span>表：<span id="detailTable"></span></span>／
            <span>記錄ID：<span id="detailRecord"></span></span><br>
            <span>使用者ID：<span id="detailUser"></span></span>／
            <span>動作：<span id="detailAction"></span></span>／
            <span>IP：<span id="detailIp"></span></span>／
            <span>時間：<span id="detailTime"></span></span>
          </div>
          <hr>
          <div class="row">
            <div class="col-md-6">
              <h6>修改前 (old_data)</h6>
              <pre id="detailOld">(無)</pre>
            </div>
            <div class="col-md-6">
              <h6>修改後 (new_data)</h6>
              <pre id="detailNew">(無)</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    // 顯示今天日期
    document.addEventListener('DOMContentLoaded', () => {
      const dateEl = document.getElementById('currentDate');
      if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          weekday: 'long'
        });
      }
    });

    // 側欄收合
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.toggle('sb-sidenav-toggled');
      });
    }

    // Modal 顯示詳細資料
    const detailModal = document.getElementById('logDetailModal');
    if (detailModal) {
      detailModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const logData = button.getAttribute('data-log');
        if (!logData) return;

        let log;
        try {
          log = JSON.parse(logData);
        } catch(e) { return; }

        const $ = id => document.getElementById(id);

        $('#detailSummary').textContent = log.summary || '';
        $('#detailFeature').textContent = log.feature || '';
        $('#detailTable').textContent   = log.table_name || '';
        $('#detailRecord').textContent  = log.record_id || '';
        $('#detailUser').textContent    = (log.user_id || '') + (log.user_name ? (' / ' + log.user_name) : '');
        $('#detailAction').textContent  = log.action || '';
        $('#detailIp').textContent      = log.ip || '';
        $('#detailTime').textContent    = log.created_at || '';

        const formatJson = (val) => {
          if (!val) return '(無)';
          try {
            const obj = JSON.parse(val);
            return JSON.stringify(obj, null, 2);
          } catch(e) {
            return val;
          }
        };

        $('#detailOld').textContent = formatJson(log.old_data);
        $('#detailNew').textContent = formatJson(log.new_data);
      });
    }

    // 取得登入者資訊（從 PHP Session 來）→ 更新頂部/底部名稱
    (function loadLoggedInUserFromPHP(){
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const loggedAs = document.getElementById('loggedAs');
      const navName  = document.getElementById('navUserName');
      if (loggedAs) loggedAs.textContent = userName;
      if (navName)  navName.textContent  = userName;
    })();

    // 如果你之後有做 /api/me.php 的頭像更新也可以加在這
    async function tryLoadAvatarFromAPI(){
      try {
        const r = await fetch('<?php echo $API_BASE_URL; ?>/me.php', {credentials:'include'});
        if(r.ok){
          const data = await r.json();
          if(data.avatar_url){
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
