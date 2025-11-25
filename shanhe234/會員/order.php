<?php
session_start();

// 如果沒有登入，導回登入頁
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.html");
    exit;
}

// 防止快取
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 使用您的登入系統的連線方式
require_once "config.php";

// 直接從 session 獲取會員電話（登入時已經存入了）
$member_phone = $_SESSION['member_phone'] ?? '';

// 如果 session 中沒有電話，從資料庫查詢
if (empty($member_phone)) {
    $sql = "SELECT 電話 FROM ramen_members WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['member_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $member_phone = $member['電話'];
        // 存入 session 下次使用
        $_SESSION['member_phone'] = $member_phone;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>我要點餐 - 會員系統</title>

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
      padding-bottom: 120px; /* 為購物車總結留空間 */
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
    
    /* 購物車樣式 */
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

    .product-price {
      font-size: 1.5rem;
      font-weight: 700;
      background: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
      margin-bottom: 15px;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 15px 0;
    }

    .quantity-btn {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      border: none;
      background: var(--primary-gradient);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      cursor: pointer;
    }

    .quantity-input {
      width: 60px;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 5px;
    }

    .cart-summary {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      padding: 20px;
      box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      z-index: 1000;
    }

    .summary-info {
      background: rgba(251, 185, 124, 0.1);
      border-radius: 15px;
      padding: 15px;
      margin-right: 20px;
    }

    .checkout-summary {
      background: linear-gradient(135deg, #fff9f0 0%, #fff0e0 100%);
      border-radius: 15px;
      padding: 15px 25px;
      border: 2px solid rgba(251, 185, 124, 0.3);
      margin-right: 20px;
    }

    .checkout-summary .total-amount {
      font-size: 1.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
    }

    .checkout-summary .total-points {
      color: #666;
      font-size: 0.9rem;
    }
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
            <h1>我要點餐</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">我要點餐</li>
          </ol>

          <!-- 商品列表 -->
          <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
              <div><i class="fas fa-store me-2"></i>菜單</div>
            </div>
            <div class="card-body">
              <div class="row g-4" id="productsContainer">
                <!-- 商品卡片會由 JS 動態生成 -->
              </div>
            </div>
          </div>

          <!-- 購物車總結 -->
          <div class="cart-summary">
            <div class="container-fluid">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <div class="summary-info">
                    <h4 class="mb-2">共：<span id="totalAmountDisplay">0</span> 元</h4>
                    <p class="mb-0">可獲得：<span id="totalPointsDisplay">0</span> 點</p>
                  </div>
                </div>
                <div class="col-md-6 text-end">
                  <div class="d-flex align-items-center justify-content-end">
                    <div class="checkout-summary">
                      <div class="total-amount">共：<span id="totalAmount">0</span> 元</div>
                      <div class="total-points">可獲得：<span id="totalPoints">0</span> 點</div>
                    </div>
                    <button class="btn btn-primary btn-lg" id="checkoutBtn" disabled>
                      <i class="fas fa-shopping-cart me-2"></i>立即結帳
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
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
      e.preventDefault(); 
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    // 購物車功能
    document.addEventListener('DOMContentLoaded', function() {
      const productsContainer = document.getElementById('productsContainer');
      const totalAmountEl = document.getElementById('totalAmount');
      const totalPointsEl = document.getElementById('totalPoints');
      const totalAmountDisplay = document.getElementById('totalAmountDisplay');
      const totalPointsDisplay = document.getElementById('totalPointsDisplay');
      const checkoutBtn = document.getElementById('checkoutBtn');

      let cart = {};
      const memberPhone = '<?php echo $member_phone; ?>';

      console.log('會員電話:', memberPhone);

      // 商品列表
      const products = [
        { id: 1, name: '溏心蛋', price: 30, points: 3, img: '溏心蛋.png' },
        { id: 2, name: '加麵', price: 40, points: 4, img: '加麵.png' },
        { id: 3, name: '拉麵套餐', price: 400, points: 40, img: '拉麵套餐.png' },
        { id: 4, name: '叉燒拉麵', price: 250, points: 25, img: '叉燒拉麵.png' },
        { id: 5, name: '味噌拉麵', price: 220, points: 22, img: '味噌拉麵.png' },
        { id: 6, name: '醬油拉麵', price: 200, points: 20, img: '醬油拉麵.png' },
        { id: 7, name: '豚骨拉麵', price: 280, points: 28, img: '豚骨拉麵.png' },
        { id: 8, name: '辣味拉麵', price: 240, points: 24, img: '辣味拉麵.png' },
        { id: 9, name: '海鮮拉麵', price: 320, points: 32, img: '海鮮拉麵.png' },
        { id: 10, name: '蔬菜拉麵', price: 180, points: 18, img: '蔬菜拉麵.png' },
        { id: 11, name: '炸雞', price: 120, points: 12, img: '炸雞.png' },
        { id: 12, name: '煎餃', price: 80, points: 8, img: '煎餃.png' },
        { id: 13, name: '可樂', price: 40, points: 4, img: '可樂.png' },
        { id: 14, name: '綠茶', price: 35, points: 3, img: '綠茶.png' },
        { id: 15, name: '烏龍茶', price: 35, points: 3, img: '烏龍茶.png' },
        { id: 16, name: '啤酒', price: 80, points: 8, img: '啤酒.png' },
        { id: 17, name: '沙拉', price: 60, points: 6, img: '沙拉.png' },
        { id: 18, name: '布丁', price: 45, points: 4, img: '布丁.png' },
        { id: 19, name: '冰淇淋', price: 50, points: 5, img: '冰淇淋.png' },
        { id: 20, name: '飯糰', price: 55, points: 5, img: '飯糰.png' }
      ];

      // 渲染商品卡片
      function renderProducts() {
        productsContainer.innerHTML = '';
        
        products.forEach(product => {
          const card = document.createElement('div');
          card.className = 'col-md-3 col-sm-6 mb-4';
          card.innerHTML = `
            <div class="product-card">
              <img src="${product.img}" class="product-img" alt="${product.name}" 
                   onerror="this.src='https://via.placeholder.com/400x200?text=${encodeURIComponent(product.name)}'">
              <div class="product-body">
                <h5 class="product-title">${product.name}</h5>
                <div class="product-price">$${product.price}</div>
                <p class="text-muted mb-3">可獲得 ${product.points} 點</p>
                <div class="quantity-controls">
                  <button class="quantity-btn minus" data-id="${product.id}">-</button>
                  <input type="number" class="quantity-input" id="qty-${product.id}" value="0" min="0" readonly>
                  <button class="quantity-btn plus" data-id="${product.id}">+</button>
                </div>
                <button class="btn btn-primary add-to-cart mt-2" data-id="${product.id}">
                  <i class="fas fa-cart-plus me-2"></i>加入購物車
                </button>
              </div>
            </div>
          `;
          productsContainer.appendChild(card);
        });

        bindProductEvents();
      }

      function bindProductEvents() {
        document.querySelectorAll('.plus').forEach(btn => {
          btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const input = document.getElementById(`qty-${productId}`);
            input.value = parseInt(input.value) + 1;
          });
        });
        
        document.querySelectorAll('.minus').forEach(btn => {
          btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const input = document.getElementById(`qty-${productId}`);
            if (parseInt(input.value) > 0) {
              input.value = parseInt(input.value) - 1;
            }
          });
        });
        
        document.querySelectorAll('.add-to-cart').forEach(btn => {
          btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const input = document.getElementById(`qty-${productId}`);
            const quantity = parseInt(input.value);

            if (quantity <= 0) {
              alert('請選擇數量');
              return;
            }

            const product = products.find(p => p.id == productId);
            
            if (cart[productId]) {
              cart[productId].quantity += quantity;
            } else {
              cart[productId] = {
                ...product,
                quantity: quantity
              };
            }

            input.value = 0;
            updateCartSummary();
            
            // 顯示通知
            const notification = document.createElement('div');
            notification.className = 'alert alert-success position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
              <i class="fas fa-check-circle me-2"></i>
              已將 ${product.name} x${quantity} 加入購物車
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
              notification.remove();
            }, 3000);
          });
        });
      }

      function updateCartSummary() {
        let totalAmount = 0;
        let totalPoints = 0;

        Object.values(cart).forEach(item => {
          totalAmount += item.price * item.quantity;
          totalPoints += item.points * item.quantity;
        });

        // 更新所有顯示位置
        totalAmountEl.textContent = totalAmount;
        totalPointsEl.textContent = totalPoints;
        totalAmountDisplay.textContent = totalAmount;
        totalPointsDisplay.textContent = totalPoints;

        checkoutBtn.disabled = totalAmount === 0;
      }

      // 結帳功能
      checkoutBtn.addEventListener('click', function() {
        if (Object.keys(cart).length === 0) {
          alert('購物車是空的');
          return;
        }

        if (!memberPhone) {
          alert('無法獲取會員資訊，請重新登入');
          return;
        }

        const orderData = {
          phone: memberPhone,
          totalAmount: parseInt(totalAmountEl.textContent),
          totalPoints: parseInt(totalPointsEl.textContent),
          items: Object.values(cart).map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: item.quantity,
            points: item.points
          }))
        };

        console.log('發送訂單資料:', orderData);

        fetch('create_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
          console.log('伺服器回應:', data);
          if (data.success) {
            alert('訂單建立成功！訂單編號: ' + data.orderNumber);
            cart = {};
            updateCartSummary();
          } else {
            alert('訂單建立失敗: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('訂單建立失敗，請稍後再試');
        });
      });

      // 初始化
      renderProducts();
    });
  </script>
</body>
</html>