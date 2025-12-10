<?php
// /lamian-ukn/帳號設置.php
// 🔥 啟用登入保護
session_start();

// 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 資料庫連線設定
$db_host = '127.0.0.1';
$db_name = 'lamian';
$db_user = 'root'; // 請根據實際情況修改
$db_pass = '';     // 請根據實際情況修改

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

// 取得用戶資訊
$userId = $_SESSION['uid'] ?? '';
$userName = $_SESSION['name'] ?? '用戶';
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';

// 從資料庫讀取完整用戶資料
$userData = null;
if ($userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM 員工基本資料 WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 更新 session 中的姓名（如果資料庫有更新）
        if ($userData && $userData['name']) {
            $userName = $userData['name'];
            $_SESSION['name'] = $userName;
        }
    } catch(PDOException $e) {
        error_log("讀取用戶資料失敗：" . $e->getMessage());
    }
}

$API_BASE_URL = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>帳號設置 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
      --text-main: #0f172a;
      --text-subtle: #64748b;

      --card-bg: rgba(255, 255, 255, 0.96);
      --card-radius: 22px;

      --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.12);
      --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

      --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);
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
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text-main);
    }

    /* ====== Top navbar ====== */
    .sb-topnav {
      background: linear-gradient(120deg, #1e3a8a, #3658ff) !important;
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

    /* 右上角頭像圓形不變形 */
    .user-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      object-fit: cover;
      vertical-align: middle;
      display: inline-block;
    }

    /* ====== Sidebar 背景：淡藍漸層延伸 ====== */
    .sb-sidenav {
      background:
        radial-gradient(circle at 40% 0%, rgba(56, 189, 248, 0.38), transparent 65%),
        radial-gradient(circle at 80% 100%, rgba(147, 197, 253, 0.34), transparent 70%),
        linear-gradient(180deg, rgba(220, 235, 255, 0.92), rgba(185, 205, 255, 0.9));
      backdrop-filter: blur(22px);
      border-right: 1px solid rgba(255, 255, 255, 0.55);
    }

    /* ====== Sidebar 標題（CORE / PAGES / ADDONS） ====== */
    .sb-sidenav-menu-heading {
      color: #1e293b !important;
      opacity: 0.75;
      font-size: 0.78rem;
      letter-spacing: .18em;
      margin: 20px 0 8px 16px;
    }

    /* ====== Sidebar 按鈕（膠囊卡片） ====== */
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

    /* ====== Sidebar footer（Logged in as） ====== */
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
      padding: 8px 16px;
      font-size: 0.8rem;
      border: 1px solid rgba(148, 163, 184, 0.4);
      box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(10px);
    }

    .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
      color: #9ca3af;
    }

    /* ====== 一般卡片 / 表單 ====== */
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
    .form-select,
    textarea {
      border-radius: 14px;
      border: 1px solid rgba(203, 213, 225, 0.9);
    }

    .form-control:focus,
    .form-select:focus,
    textarea:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.30);
    }

    /* ====== Avatar 區塊 ====== */
    .avatar-wrap {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      overflow: hidden;
      position: relative;
      box-shadow: var(--shadow-soft);
      background: radial-gradient(circle at 0 0, rgba(191, 219, 254, 0.7), transparent 60%);
    }

    .avatar-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-mask {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: rgba(15, 23, 42, 0.35);
      color: #fff;
      opacity: 0;
      cursor: pointer;
    }

    .avatar-wrap:hover .avatar-mask {
      opacity: 1;
    }

    .avatar-mask i {
      font-size: 1.1rem;
      margin-bottom: 4px;
    }

    /* ====== Alert 訊息 ====== */
    #msgOk,
    #msgErr {
      border-radius: 16px;
      border-width: 1.4px;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
      background: rgba(255, 255, 255, 0.96);
    }

    /* ====== 按鈕樣式 ====== */
    .btn-primary {
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      border: none;
      border-radius: 999px;
      padding-inline: 22px;
      box-shadow: 0 10px 26px rgba(79, 70, 229, 0.35);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #4338ca, #4f46e5);
      transform: translateY(-1px);
      box-shadow: 0 16px 32px rgba(79, 70, 229, 0.45);
    }

    .btn-outline-secondary {
      border-radius: 999px;
      padding-inline: 20px;
      border-color: rgba(148, 163, 184, 0.9);
      color: #4b5563;
      background: rgba(255, 255, 255, 0.9);
    }

    .btn-outline-secondary:hover {
      background: rgba(148, 163, 184, 0.08);
      color: #111827;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }

    footer {
      background: transparent;
      border-top: 1px solid rgba(148, 163, 184, 0.35);
      margin-top: 24px;
      padding-top: 14px;
      font-size: 0.8rem;
      color: var(--text-subtle);
    }

    /* ====== RWD ====== */
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

    /* ====== 修正側邊欄箭頭（SVG / ::after / background-image 全吃） ====== */
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
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <!-- 這裡改成用 .user-avatar 控制大小 & 裁切 -->
          <img
            class="user-avatar rounded-circle me-1"
            src="<?php echo $userData && $userData['avatar_url'] ? htmlspecialchars($userData['avatar_url']) : 'https://i.pravatar.cc/40?u=' . urlencode($userName); ?>"
            alt="User Avatar">
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

    <!-- Core：首頁依權限導向不同 -->
    <div class="sb-sidenav-menu-heading">Core</div>
    <?php if ($userLevel === 'A'): ?>
      <a class="nav-link" href="index.php">
        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
        首頁
      </a>
    <?php elseif ($userLevel === 'B'): ?>
      <a class="nav-link" href="indexB.php">
        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
        首頁
      </a>
    <?php else: ?>
      <a class="nav-link" href="indexC.php">
        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
        首頁
      </a>
    <?php endif; ?>


    <!-- ================= A / B 級的 Pages ================= -->
    <?php if ($userLevel === 'A' || $userLevel === 'B'): ?>

      <div class="sb-sidenav-menu-heading">Pages</div>

      <!-- 人事管理 -->
      <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
        <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
      </a>
      <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">

          <?php if ($userLevel === 'A'): ?>
            <!-- 只有 A 級 -->
            <a class="nav-link" href="員工資料表.php">員工資料表</a>
          <?php endif; ?>

          <a class="nav-link" href="班表管理.php">班表管理</a>

          <?php if ($userLevel === 'A'): ?>
            <!-- 只有 A 級 -->
            <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
          <?php endif; ?>

          <a class="nav-link" href="假別管理.php">假別管理</a>
          <a class="nav-link" href="打卡管理.php">打卡管理</a>

          <?php if ($userLevel === 'A'): ?>
            <!-- 只有 A 級 -->
            <a class="nav-link" href="薪資管理.php">薪資管理</a>
          <?php endif; ?>

        </nav>
      </div>

      <!-- 營運管理 -->
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

          <?php if ($userLevel === 'A'): ?>
            <!-- 只有 A 級 -->
            <a class="nav-link" href="activity_log.php">
              <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>修改紀錄
            </a>
          <?php endif; ?>

        </nav>
      </div>

      <!-- 網站管理（A、B 都有） -->
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


    <?php endif; ?>


    <!-- ================= C 級的 Pages ================= -->
    <?php if ($userLevel === 'C'): ?>

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

    <?php endif; ?>

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
            <h1>帳號設置</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">帳號設置</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

          <!-- 個人資料 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user me-2"></i>個人資料</div>
            <div class="card-body">
              <div class="row g-4 align-items-center">
                <div class="col-auto">
                  <div class="avatar-wrap">
                    <img id="avatarImg" src="<?php echo $userData && $userData['avatar_url'] ? htmlspecialchars($userData['avatar_url']) : 'https://i.pravatar.cc/240?img=12'; ?>" alt="avatar">
                    <div id="avatarMask" class="avatar-mask">
                      <i class="fas fa-camera mb-1"></i>
                      <small>更換頭像</small>
                    </div>
                  </div>
                  <div class="text-muted small mt-2">建議 512×512,JPG/PNG,&lt; 3MB</div>
                  <input id="avatarFile" type="file" accept="image/png,image/jpeg" class="d-none">
                </div>

                <div class="col">
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">員工編號</label>
                      <input id="empNo" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['id'] ?? $userId); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">員工姓名</label>
                      <input id="empName" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['name'] ?? $userName); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">手機</label>
                      <input id="empPhone" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['telephone'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">職稱</label>
                      <input id="empTitle" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['position'] ?? ''); ?>" readonly>
                    </div>
                  </div>
                  <div class="text-muted small mt-2">如需修改以上資訊,請聯繫經理或老闆。</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 可編輯項目 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-pen-to-square me-2"></i>可編輯資訊</div>
            <div class="card-body">
              <form id="profileForm" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input id="email" class="form-control" type="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">通訊地址</label>
                  <input id="addr" class="form-control" type="text" placeholder="例:台北市…" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">緊急聯絡人</label>
                  <input id="emgName" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['emergency_contact'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">緊急聯絡電話</label>
                  <input id="emgPhone" class="form-control" type="text" value="<?php echo htmlspecialchars($userData['emergency_phone'] ?? ''); ?>">
                </div>

                <div class="col-12">
                  <label class="form-label">備註</label>
                  <textarea id="memo" class="form-control" rows="3" placeholder="選填"><?php echo htmlspecialchars($userData['memo'] ?? ''); ?></textarea>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-12 d-flex justify-content-end gap-2">
                  <button class="btn btn-outline-secondary" type="button" id="btnReset"><i class="fas fa-rotate-left me-1"></i>還原</button>
                  <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>儲存</button>
                </div>
              </form>
            </div>
          </div>

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
  document.getElementById('currentDate').textContent =
    new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
  document.getElementById('sidebarToggle').addEventListener('click', e=>{
    e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
  });

  const API_BASE   = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
  const API_ME     = API_BASE + '/me.php';
  const API_UPDATE = API_BASE + '/me_update.php';
  const API_AVATAR = API_BASE + '/me_avatar.php';

  const el = id => document.getElementById(id);
  function showOk(msg){
    const a=el('msgOk');
    a.textContent=msg;
    a.classList.remove('d-none');
    setTimeout(()=>a.classList.add('d-none'), 2000);
  }
  function showErr(msg){
    const a=el('msgErr');
    a.textContent=msg;
    a.classList.remove('d-none');
    setTimeout(()=>a.classList.add('d-none'), 3500);
  }

  // 🔄 重新載入自己
  async function loadMe(){
    location.reload();
  }

  // 🖼 點頭像 → 選檔案
  el('avatarMask').addEventListener('click', ()=> el('avatarFile').click());

  // 🖼 上傳頭像
  el('avatarFile').addEventListener('change', async (e)=>{
    const f = e.target.files?.[0];
    if(!f) return;

    if(!['image/jpeg','image/png'].includes(f.type))
      return showErr('只接受 JPG / PNG');

    if(f.size > 3*1024*1024)
      return showErr('檔案太大(上限 3MB)');

    try{
      const fd = new FormData();
      fd.append('avatar', f);

      const r = await fetch(API_AVATAR, {
        method:'POST',
        body:fd,
        credentials:'include'
      });
      const resp = await r.json();
      if(!r.ok || resp.error) throw new Error(resp.error || ('HTTP '+r.status));

      showOk('已更新頭像');
      // 重新載入顯示新頭像
      loadMe();
    }catch(err){
      showErr('上傳失敗:'+err.message);
    }finally{
      e.target.value='';
    }
  });

  // ✏️ 編輯個人可修改資訊
  el('profileForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const body = {
      email: el('email').value.trim(),
      address: el('addr').value.trim(),
      emergency_contact: el('emgName').value.trim(),
      emergency_phone: el('emgPhone').value.trim(),
      memo: el('memo').value.trim()
    };

    try{
      const r = await fetch(API_UPDATE, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(body),
        credentials:'include'
      });
      const resp = await r.json();
      if(!r.ok || resp.error) throw new Error(resp.error || ('HTTP '+r.status));

      // 如果頁面上根本沒有 newPwd/newPwd2，就不要動它，避免錯誤
      const pwd1 = el('newPwd');
      const pwd2 = el('newPwd2');
      if (pwd1 && pwd2) {
        pwd1.value = '';
        pwd2.value = '';
      }

      showOk('已儲存');
      setTimeout(() => location.reload(), 1000);
    }catch(err){
      showErr('儲存失敗:'+err.message);
    }
  });

  el('btnReset').addEventListener('click', loadMe);
</script>
</body>
</html>
