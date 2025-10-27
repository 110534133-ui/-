<?php
session_start();
// 如果沒有登入，導回登入頁（根據實際路徑調整）
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.html");  // ../ 表示回到上一層（專案根目錄）
    exit;
}

// 防止快取，避免登出後按返回鍵看到頁面
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>首頁 - 員工管理系統</title>

 
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
    </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">會員系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

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
</form>
</li>
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

            <div class="sb-sidenav-menu-heading"></div>
            

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as: <br>會員</div>
          
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main >
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="color: #ce1212;">會員集點系統</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active"><i class="fas fa-home me-2"></i>首頁</li>
          </ol>

          

<!-- 歡迎卡片 -->
<div class="card mb-4" style="background: linear-gradient(135deg, #fff9f0 0%, #f5f5f0 100%) ; border: none;">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h3 class="mb-2">歡迎回來，<span id="memberName"></span>！</h3>
        <p class="mb-0 opacity-90">感謝您對本店的支持～</p>
      </div>
      <div class="col-md-4 text-end">
        <div class="display-4">👋</div>
      </div>
    </div>
  </div>
</div>

<!-- 快速統計卡片 -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card stat-card stat-success">
      <div class="card-body text-center">
        <div class="mb-2"><i class="fas fa-coins fa-2x" style="color:rgb(255, 182, 13);"></i></div>
        <div class="stat-label">可用點數</div>
        <div class="stat-value" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
          <span id="dashPoints">350</span>
        </div>
        <a href="點數記錄.php" class="btn btn-sm btn-outline-primary mt-2">查看明細</a>
      </div>
      <span class="stat-glow"></span>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="mb-2"><i class="fas fa-shopping-cart fa-2x" style="color: #4facfe;"></i></div>
        <div class="stat-label">累計消費</div>
        <div class="stat-value" style="color: #4facfe;">
          $<span id="dashTotal">12,850</span>
        </div>
        <a href="消費紀錄.php" class="btn btn-sm btn-outline-primary mt-2">消費記錄</a>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="mb-2"><i class="fas fa-gift fa-2x" style="color: #ff6b00;"></i></div>
        <div class="stat-label">可兌換券</div>
        <div class="stat-value" style="color: #ff6b00;">
          <span id="dashCoupons">2</span> 張
        </div>
        <a href="點數兌換.php" class="btn btn-sm btn-outline-primary mt-2">立即兌換</a>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center">
        <div class="mb-2"><i class="fas fa-calendar-check fa-2x" style="color: #54bcc1;"></i></div>
        <div class="stat-label">本月消費</div>
        <div class="stat-value" style="color: #54bcc1;">
          <span id="dashMonth">8</span> 次
        </div>
        <a href="" class="btn btn-sm btn-outline-primary mt-2">查看詳情</a>
      </div>
    </div>
  </div>
</div>

<!-- 快速功能 -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-newspaper me-2"></i>最新消息</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-6">
            <a href="會員基本資料.html" class="btn btn-outline-primary w-100 py-3">
              <i class="fas fa-tag me-2 text-danger"></i>12月限定優惠</h6>
                <br>
              <span>消費滿500元<br>即贈送免費飲料一杯</span>
            </a>
          </div>
          <div class="col-6">
            <a href="點數兌換.html" class="btn btn-outline-primary w-100 py-3">
              <i class="fas fa-gift me-2 text-warning"></i>新品上市</h6>
              <br>
              <span>辣味噌拉麵限時推出，<br>快來品嚐！</span>
            </a>
          </div>
          <div class="col-6">
            <a href="點數管理.html" class="btn btn-outline-primary w-100 py-3">
              <i class="fas fa-birthday-cake me-2 text-info"></i>生日優惠</h6><br>
              <span>- - - - - - - - -</span><br>
              <span>生日當月享好禮！</span>
            </a>
          </div>
          <div class="col-6">
            <a href="消費記錄.html" class="btn btn-outline-primary w-100 py-3">
              <i class="fas fa-file-invoice-dollar me-2 text-info"></i>隱藏活動</h6><br>
              <span>- - - - - - </span><br>
              <span>待更新</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-star me-2"></i>會員等級</div>
      <div class="card-body text-center">
        <div class="mb-3">
          <i class="fas fa-crown fa-3x" style="color: #fbb97c;"></i>
        </div>
        <h4 class="mb-2" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
          <span id="memberLevel">黃金會員</span>
        </h4>
        <p class="text-muted small mb-3">距離下一等級還需 <strong>1,150</strong> 元</p>
        <div class="progress" style="height: 25px; border-radius: 15px;">
          <div class="progress-bar" role="progressbar" style="width: 60%; background: var(--primary-gradient);" id="levelProgress">
            60%
          </div>
        </div>
        <p class="text-muted small mt-2">
          累計消費：$12,85 / $14,00

          
        </p>
      </div>
    </div>
  </div>
</div>

<!-- 最新消息 / 優惠活動 -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card">
      
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card">
      
    </div>
  </div>
</div>



<script>


  // 載入會員資料
  function loadDashboard() {
    document.getElementById('memberName').textContent = memberData.name;
    document.getElementById('dashPoints').textContent = memberData.points;
    document.getElementById('dashTotal').textContent = memberData.totalSpent.toLocaleString();
    document.getElementById('dashCoupons').textContent = memberData.availableCoupons;
    document.getElementById('dashMonth').textContent = memberData.monthConsumption;
    document.getElementById('memberLevel').textContent = memberData.level;
    document.getElementById('levelProgress').style.width = memberData.levelProgress + '%';
    document.getElementById('levelProgress').textContent = memberData.levelProgress + '%';
  }

  // 初始化
  loadDashboard();

  // TODO: 從後端載入資料
  /*
  fetchJSON('/member/dashboard').then(data => {
    if (data && data.success) {
      memberData = data.data;
      loadDashboard();
    }
  });
  */
})();
</script>

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
  margin-bottom: 10px;
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

.list-group-item-action:hover {
  background-color: rgba(251, 185, 124, 0.1);
}
</style>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">© 2025 餐廳管理系統 </div>
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

  <!-- 依你原本使用的版本 -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>

  
<script>
document.addEventListener('DOMContentLoaded', () => {
  fetch('get_member_dashboard.php', { credentials: 'include' })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('memberName').textContent = data.name;
        document.getElementById('dashPoints').textContent = data.points;
        document.getElementById('dashTotal').textContent = Number(data.total_spent).toLocaleString();
      } else {
        console.warn('讀取會員資料失敗：', data.message);
      }
    })
    .catch(err => console.error('錯誤：', err));
});
</script>
<script>
  // 取得訂單
fetch('get_orders.php')
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          console.log(data.data); // 訂單陣列
      }
  });

// 取得優惠券
fetch('get_coupons.php')
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          console.log(data.data); // 優惠券陣列
      }
  });
</script>

  <script>
    // 顯示當前日期
    const currentDateElem = document.getElementById('currentDate');
    const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
    const now = new Date();
    currentDateElem.textContent = now.toLocaleDateString('zh-TW', options);
  </script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  fetch("get_member_level.php")
    .then(response => response.json())
    .then(data => {
      console.log("會員資料回傳：", data); // ✅ 除錯用

      // 🟢 確保 API 回傳成功
      if (data.success) {
        const levelText = document.getElementById("memberLevel");
        const progressBar = document.getElementById("levelProgress");
        const nextTextEl = document.querySelector(".text-muted.small.mb-3");
        const totalTextEl = document.querySelector(".text-muted.small.mt-2");

        // 🟢 顯示會員等級
        levelText.textContent = data.level;

        // 🟢 顯示距離下一等級或最高等級
        if (data.nextTarget && data.remaining > 0) {
          nextTextEl.innerHTML = `距離下一等級還需 <strong>${data.remaining.toLocaleString()}</strong> 元`;
        } else {
          nextTextEl.innerHTML = `您已達最高等級 🎉`;
        }

        // 🟢 顯示進度條
        const progress = Math.min(data.progress, 100);
        progressBar.style.width = progress + "%";
        progressBar.textContent = progress + "%";

        // 🟢 顯示累計消費
        let maxDisplay = data.nextTarget ? data.nextTarget : data.total_spent;
        totalTextEl.innerHTML = `累計消費：$${data.total_spent.toLocaleString()} / $${maxDisplay.toLocaleString()}`;

      } else {
        console.warn("⚠️ API 錯誤：", data.message);
        showEmptyState("尚無消費紀錄");
      }
    })
    .catch(error => {
      console.error("⚠️ 無法讀取會員資料：", error);
      showEmptyState("無法載入資料");
    });

  // 🧹 清空畫面的函式
  function showEmptyState(msg) {
    document.getElementById("memberLevel").textContent = "—";
    document.querySelector(".text-muted.small.mb-3").textContent = msg;
    document.getElementById("levelProgress").style.width = "0%";
    document.getElementById("levelProgress").textContent = "0%";
    document.querySelector(".text-muted.small.mt-2").textContent = "累計消費：$0 / $1";
  }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const dashTotalEl = document.getElementById('dashTotal');
  const dashMonthEl = document.getElementById('dashMonth');
  const dashCouponsEl = document.getElementById('dashCoupons');

  function loadDashboard() {
    fetch('get_points.php', { credentials: 'same-origin' })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          console.warn('無法載入會員資料');
          dashTotalEl.textContent = '-';
          dashMonthEl.textContent = '-';
          dashCouponsEl.textContent = '0';
          return;
        }

        // 🔹 累計消費
        if (dashTotalEl) dashTotalEl.textContent = (data.totalSpent || 0).toLocaleString();

        // 🔹 本月消費
        if (dashMonthEl) dashMonthEl.textContent = (data.monthOrders || 0);

        // 🔹 可兌換券（未使用且未過期）
        fetch('get_coupons.php', { credentials: 'same-origin' })
          .then(r => r.json())
          .then(cdata => {
            let coupons = cdata.data || [];
            const now = new Date();
            const usable = coupons.filter(c => {
              if (c.狀態 !== '未使用') return false;
              if (!c.到期日) return false;
              return new Date(c.到期日 + 'T23:59:59') >= now;
            });
            if (dashCouponsEl) dashCouponsEl.textContent = usable.length;
          })
          .catch(err => {
            console.error('get_coupons error', err);
            if (dashCouponsEl) dashCouponsEl.textContent = '0';
          });

      })
      .catch(err => {
        console.error('get_points error', err);
        dashTotalEl.textContent = '-';
        dashMonthEl.textContent = '-';
        dashCouponsEl.textContent = '0';
      });
  }

  loadDashboard();
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("get_points.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 🔹 可用點數
                const pointsSpan = document.getElementById("dashPoints");
                if (pointsSpan) {
                    pointsSpan.textContent = data.totalPoints.toLocaleString();
                }

                // 如果你之後還要加本月消費、累計消費、可兌換券等，也可以在這裡加入更新
                // 例如：
                // document.getElementById("dashTotal").textContent = data.totalSpent.toLocaleString();
                // document.getElementById("dashMonth").textContent = data.monthOrders;
            } else {
                console.warn("❗ 取得點數資料失敗：", data.message);
                const pointsSpan = document.getElementById("dashPoints");
                if (pointsSpan) pointsSpan.textContent = "—";
            }
        })
        .catch(error => {
            console.error("⚠️ 讀取 get_points.php 失敗：", error);
            const pointsSpan = document.getElementById("dashPoints");
            if (pointsSpan) pointsSpan.textContent = "—";
        });
});
</script>

</body>
</html>
