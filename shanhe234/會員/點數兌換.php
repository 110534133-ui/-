<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.html");
    exit;
}

$memberId = $_SESSION['member_id'];

// 抓會員本身點數
$stmt = $conn->prepare("SELECT 會員點數 FROM ramen_members WHERE id = ?");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$memberData = $stmt->get_result()->fetch_assoc();
$memberPoints = intval($memberData['會員點數'] ?? 0);

// 抓會員訂單累積點數
$stmt = $conn->prepare("SELECT IFNULL(SUM(獲得點數),0) AS totalEarnedPoints FROM ramen_orders WHERE 電話 = (SELECT 電話 FROM ramen_members WHERE id=?)");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$orderData = $stmt->get_result()->fetch_assoc();
$orderPoints = intval($orderData['totalEarnedPoints'] ?? 0);

// 總點數
$totalPoints = $memberPoints + $orderPoints;
?>




<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>點數兌換 - 會員系統</title>

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
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">會員系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
      <i class="fas fa-bars"></i>
    </button> 

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
<a class="nav-link" href="order.php">
  <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt"></i></div>我要點餐
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
            <h1>點數兌換</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">點數兌換</li>
          </ol>

          <!-- 點數兌換頁面 -->

<!-- 當前點數顯示 -->
<div class="card stat-card stat-success mb-4">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <div class="stat-label">您的可用點數</div>
      <div class="stat-value">
        <span id="userPoints"><?php echo $totalPoints; ?></span> 點
      </div>
      <div class="mt-2 small text-muted">
        點數可兌換以下優惠商品
      </div>
    </div>
    <div class="text-end">
      <div class="display-6 fw-bold" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
        <i class="fas fa-gift"></i>
      </div>
    </div>
  </div>
  <span class="stat-glow"></span>
</div>

<!-- 兌換商品區（去掉分類選單） -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <div><i class="fas fa-store me-2"></i>兌換商品</div>
  </div>
  <div class="card-body">
    <div class="row g-4" id="productsContainer">
      <!-- 兌換商品卡片會由 JS 動態生成 -->
    </div>
  </div>
</div>


<!-- 兌換記錄 -->
<div class="card">
  <div class="card-header"><i class="fas fa-history me-2"></i>兌換記錄</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 140px;">兌換日期</th>
            <th>商品名稱</th>
            <th style="width: 100px;" class="text-end">使用點數</th>
            <th style="width: 120px;">狀態</th>
            <th style="width: 150px;">有效期限</th>
            <th style="width: 120px;">操作</th
          </tr>
        </thead>
        <tbody id="exchangeHistoryBody">
          <tr id="noExchangeRow">
            <td colspan="6" class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2"></i><br>尚無兌換記錄
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- 兌換確認 Modal -->
<div class="modal fade" id="exchangeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>確認兌換</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <img id="modalProductImg" src="" alt="" style="max-width: 200px; border-radius: 10px;">
        </div>
        <h5 class="text-center mb-3" id="modalProductName"></h5>
        <div class="alert alert-info">
          <div class="d-flex justify-content-between mb-2">
            <span>需要點數：</span>
            <strong id="modalRequiredPoints"></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>您的點數：</span>
            <strong id="modalUserPoints"></strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <span>兌換後餘額：</span>
            <strong id="modalAfterPoints" style="color: #ff6b00;"></strong>
          </div>
        </div>
        <p class="small text-muted mb-0">
          <i class="fas fa-info-circle me-1"></i>兌換後將無法退回，請確認後再進行兌換。
        </p>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        <button class="btn btn-primary" id="confirmExchangeBtn">確認兌換</button>
      </div>
    </div>
  </div>
</div>

<!-- 兌換成功 Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="display-1 mb-3">🎉</div>
        <h4 class="mb-3">兌換成功！</h4>
        <p class="text-muted mb-3">您已成功兌換</p>
        <h5 id="successProductName" class="mb-3"></h5>
        <div class="alert alert-success">
          請至「兌換記錄」查看詳情
        </div>
      </div>
      <div class="modal-footer border-0 justify-content-center">
        <button class="btn btn-primary" data-bs-dismiss="modal">知道了</button>
      </div>
    </div>
  </div>
</div>

<style>
.product-card {
  border: 2px solid rgba(0,0,0,.05);
  border-radius: var(--border-radius);
  overflow: hidden;
  background: #fff;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
  border-color: rgba(251, 185, 124, 0.5);
}

.product-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
}

.product-body {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 10px;
  color: #333;
}

.product-desc {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 15px;
  flex: 1;
}

.product-points {
  font-size: 1.5rem;
  font-weight: 700;
  background: var(--primary-gradient);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
  margin-bottom: 15px;
}

.product-footer {
  margin-top: auto;
}

.badge-status {
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 500;
}

.badge-available {
  background: rgba(75, 172, 254, 0.12);
  color: #2196F3;
  border: 1px solid rgba(33, 150, 243, 0.25);
}

.badge-used {
  background: rgba(76, 175, 80, 0.12);
  color: #4caf50;
  border: 1px solid rgba(76, 175, 80, 0.25);
}

.badge-expired {
  background: rgba(158, 158, 158, 0.12);
  color: #9e9e9e;
  border: 1px solid rgba(158, 158, 158, 0.25);
}
</style>

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
    // ===== 共用：今日日期 / 側欄 =====
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });
  </script>
  <!-- 使用確認 Modal -->
<div class="modal fade" id="confirmUseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">確認使用優惠券</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">是否確認使用這張優惠券？</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">否</button>
        <button type="button" class="btn btn-primary" id="confirmUseBtn">是</button>
      </div>
    </div>
  </div>
</div>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const pointsEl = document.getElementById('userPoints');
  const productsContainer = document.getElementById('productsContainer');
  const historyBody = document.getElementById('exchangeHistoryBody');
  const noRow = document.getElementById('noExchangeRow');

  // 直接用 PHP 點數
  let userPoints = <?php echo $totalPoints; ?>;
  pointsEl.textContent = userPoints;

  let selectedCouponRow = null;

  const products = [
    { id: 1, name: '溏心蛋', points: 30, img: '溏心蛋.png' },
    { id: 2, name: '加麵', points: 40, img: '加麵.png' },
    { id: 3, name: '拉麵套餐', points: 400, img: '拉麵套餐.png' }
  ];

  // 渲染商品
  function renderProducts() {
    productsContainer.innerHTML = '';
    products.forEach(p => {
      const enough = userPoints >= p.points;
      const card = document.createElement('div');
      card.className = 'col-md-4';
      card.innerHTML = `
        <div class="card h-100">
          <img src="${p.img}" class="card-img-top" alt="${p.name}" 
               onerror="this.src='https://via.placeholder.com/400x200?text=${encodeURIComponent(p.name)}'">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">${p.name}</h5>
            <p class="card-text mb-3">${p.points} 點</p>
            <button class="btn ${enough ? 'btn-primary' : 'btn-secondary'} mt-auto w-100" ${!enough ? 'disabled' : ''} data-id="${p.id}">
              ${enough ? '立即兌換' : '點數不足'}
            </button>
          </div>
        </div>`;
      productsContainer.appendChild(card);
    });
    productsContainer.querySelectorAll('button').forEach(btn => btn.addEventListener('click', handleRedeem));
  }

  // 兌換商品
  function handleRedeem(e) {
    const id = e.currentTarget.dataset.id;
    const btn = e.currentTarget;
    const oldText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '處理中...';

    const fd = new FormData();
    fd.append('優惠券編號', id);

    fetch('redeem_reward.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          userPoints -= products.find(p => p.id == id).points; // 扣掉點數
          pointsEl.textContent = userPoints;
          renderProducts();   // 重新渲染商品按鈕
          loadCoupons();      // 刷新歷史紀錄
        } else alert(data.message);
      })
      .catch(() => alert('兌換失敗，請稍後再試'))
      .finally(() => { btn.disabled = false; btn.textContent = oldText; });
  }

  // 載入歷史優惠券
  function loadCoupons() {
    fetch('get_coupons.php', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        historyBody.innerHTML = '';
        if (!data.success || !data.data || data.data.length === 0) {
          if (noRow) { noRow.style.display = ''; historyBody.appendChild(noRow); }
          return;
        }
        if (noRow) noRow.style.display = 'none';
        data.data.forEach(c => addCouponRow(c));
      })
      .catch(err => console.error('get_coupons error', err));
  }

  // 新增表格列
  function addCouponRow(c) {
    const tr = document.createElement('tr');
    const expireDate = c.到期日 ? new Date(c.到期日 + 'T23:59:59') : null;
    const isExpired = expireDate && expireDate < new Date();
    const statusText = isExpired ? '已過期' : c.狀態 || '未使用';
    if (isExpired) tr.className = 'text-muted bg-light';

    tr.innerHTML = `
      <td>${(c.領取時間||'').slice(0,10)}</td>
      <td>${c.優惠券名稱}</td>
      <td class="text-end">${c.使用點數||'-'}</td>
      <td class="statusCell">${statusText}</td>
      <td>${c.到期日||'-'}</td>
      <td class="actionCell"></td>
    `;

    const actionCell = tr.querySelector('.actionCell');
    if (!isExpired && (!c.狀態 || c.狀態 === '未使用')) {
      const btn = document.createElement('button');
      btn.className = 'btn btn-success btn-sm';
      btn.textContent = '使用';
      btn.addEventListener('click', () => {
        selectedCouponRow = tr;
        selectedCouponRow.dataset.id = c.優惠券編號;
        const modal = new bootstrap.Modal(document.getElementById('confirmUseModal'));
        modal.show();
      });
      actionCell.appendChild(btn);
    } else actionCell.textContent = '-';

    historyBody.appendChild(tr);
  }

  // 使用優惠券 Modal
  document.getElementById('confirmUseBtn').addEventListener('click', () => {
    if (!selectedCouponRow) return;
    const couponId = selectedCouponRow.dataset.id;
    const fd = new FormData();
    fd.append('useCouponId', couponId);

    fetch('use_coupon.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        alert(data.message || (data.success ? '使用成功' : '使用失敗'));
        loadCoupons();
      })
      .catch(() => alert('伺服器錯誤'));

    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmUseModal'));
    modal.hide();
  });

  // 初始化
  renderProducts();  // 直接用 PHP 點數渲染商品
  loadCoupons();     // 載入歷史紀錄
});
</script>

  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
  <script src="js/datatables-simple-demo.js"></script>

</body>
</html>
