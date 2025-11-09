<?php
// 🔹 確保 session 在整個網站路徑都有效
session_set_cookie_params(['path' => '/']);
session_start();

// 🔹 檢查登入狀態（用登入時設定的 session 變數）
if (!isset($_SESSION['member_phone'])) {
    // 尚未登入 → 導回登入頁
    header("Location: ../login.html");
    exit;
}

// 🔹 防止快取（避免登出後按返回鍵看到舊頁面）
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ✅【重要修復】每次都從資料庫重新讀取最新資料
require_once "config.php"; // 確保引入資料庫連線

$phone = $_SESSION['member_phone'];
$sql = "SELECT * FROM ramen_members WHERE `電話` = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 使用者不存在，強制登出
    session_destroy();
    header("Location: ../login.html");
    exit;
}

$member = $result->fetch_assoc();
$stmt->close();

// ✅ 使用從資料庫讀取的最新資料，而不是 Session 中的舊資料
$memberId   = $member['id'];
$memberName = $member['姓名'];
$phone      = $member['電話'];

// ✅ 可選：更新 Session 為最新資料（保持相容性）
$_SESSION['member_id'] = $member['id'];
$_SESSION['member_name'] = $member['姓名'];
$_SESSION['member_phone'] = $member['電話'];
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>點數記錄 - 會員管理系統</title>

  <!-- 依賴 -->
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
      --dark-bg: linear-gradient(135deg,rgba(242, 189, 114, 0.21) 0%,rgba(249, 177, 77, 0.57) 100%);
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
      background: linear-gradient(45deg,rgb(0, 0, 0), #ffffff);/* 管理系統標題 */
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
    }

    /* 側欄（跟首頁一致） */
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
    /* 內容區 */
    .container-fluid{ padding:30px !important; }
    h1{
      background: linear-gradient(135deg, #ce1212, #ff6666); /* 設置紅色漸層 */
    -webkit-background-clip: text; /* 剪裁背景到文字 */
    -webkit-text-fill-color: transparent; /* 文字填充為透明 */
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 30px;}
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.95), rgba(255,255,255,.75)); font-weight:600; }
    .form-control, .form-select{ border-radius:12px; }
    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }

    /* 表格 */
    .table{ border-radius:var(--border-radius); overflow:hidden; background:#fff; box-shadow:var(--card-shadow); }
    .table thead th{ background:var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color:rgba(0,0,0,.05); }
    .table tbody tr:hover{ background:rgba(227,23,111,.05); transform:scale(1.01); }

    /* 統計摘要（與日報表記錄一致） */
    .stat-card{ border:none; color:#fff; border-radius:var(--border-radius); background:#999; box-shadow:var(--card-shadow); position:relative; overflow:hidden; }
    .stat-card .card-body{ padding:1.1rem 1.25rem; }
    .stat-label{ font-size:.85rem; opacity:.9; }
    .stat-value{ font-size:1.6rem; font-weight:700; line-height:1.2; }
    .stat-icon{ font-size:2.2rem; opacity:.35; }
    .stat-glow{ position:absolute; right:-30px; top:-30px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,.15); filter:blur(12px); }
    .stat-primary{  background: var(--primary-gradient);  }
    .stat-success{  background: var(--success-gradient);  }
    .stat-warning{  background: var(--warning-gradient);  }
    .stat-secondary{ background: var(--secondary-gradient); }

    /* 遮蔽（米字號） */
    .masked{ letter-spacing:.06em; }
    .reveal-toggle{ border:none; border-radius:25px; background:var(--secondary-gradient); color:#fff; padding:.5rem .9rem; }
    .reveal-toggle:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(0,0,0,.12); }

    .badge-paytype{ font-size:.75rem; }
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
          <div class="small">Logged in as:<br>會員</div>
          
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>點數記錄</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">點數加總：</li>
          </ol>

          <!-- 載入 / 訊息 -->
          <div id="loadingIndicator" class="d-none">
            <div class="d-flex justify-content-center align-items-center mb-4">
              <div class="spinner-border text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div>
              <span>載入中...</span>
            </div>
          </div>
          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>

          

<!-- 當月點數摘要 + 顯示按鈕 -->
<div class="card stat-card stat-success mb-4">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <div class="stat-label">當前點數總計</div>
      <div class="stat-value">
        <span id="totalPoints">0</span> 點
      </div>
      <div class="mt-2 small">
        <span class="me-3">本月獲得：<span id="monthEarned">0</span> 點</span>
        <span class="me-3">本月使用：<span id="monthUsed">0</span> 點</span>
        <span>待領任務：<span id="pendingTasks">0</span> 個</span>
      </div>
    </div>
    <div class="text-end">
      <div class="display-6 fw-bold" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
        <i class="fas fa-coins"></i>
      </div>
      <div class="mt-2">
        <button class="btn btn-light btn-sm" id="refreshPointsBtn">
          <i class="fas fa-sync-alt me-1"></i> 重新整理
        </button>
      </div>
    </div>
  </div>
  <span class="stat-glow"></span>
</div>

<!-- 任務領取區 -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fas fa-tasks me-2"></i>任務中心</div>
    <div class="text-muted small">完成任務即可獲得點數</div>
  </div>
  <div class="card-body">
    <div class="row g-3" id="tasksContainer">
      <!-- 任務卡片會動態生成 -->
    </div>
  </div>
</div>

<!-- 點數記錄 -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fas fa-history me-2"></i>點數記錄</div>
    <div>
   
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 150px;">日期時間</th>
            <th style="width: 120px;">類型</th>
            <th>說明</th>
            <th style="width: 100px;" class="text-end">點數變動</th>
            <th style="width: 100px;" class="text-end">剩餘點數</th>
          </tr>
        </thead>
        <tbody id="pointsHistoryBody">
          <tr id="noPointsRow">
            <td colspan="5" class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2"></i><br>尚無點數記錄
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- 分頁 -->
    <nav aria-label="Points pagination" id="paginationNav" class="mt-3" style="display: none;">
      <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
  </div>
</div>

<!-- 任務完成提示 Modal -->
<div class="modal fade" id="taskCompleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="fas fa-check-circle text-success me-2"></i>任務完成</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <div class="display-1 mb-3">🎉</div>
        <h4 class="mb-3">恭喜您完成任務！</h4>
        <p class="text-muted mb-3" id="taskCompleteName"></p>
        <div class="display-6 fw-bold mb-3" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent;">
          +<span id="taskCompletePoints">0</span> 點
        </div>
        <p class="small text-muted">已加入您的帳戶</p>
      </div>
      <div class="modal-footer border-0 justify-content-center">
        <button class="btn btn-primary" data-bs-dismiss="modal">太好了！</button>
      </div>
    </div>
  </div>
</div>

<style>
/* 任務卡片樣式 */
.task-card {
  border: 2px solid rgba(0,0,0,.05);
  border-radius: var(--border-radius);
  padding: 20px;
  background: #fff;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.task-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
  border-color: rgba(251, 185, 124, 0.5);
}

.task-card.completed {
  background: rgba(0,0,0,.02);
  border-color: rgba(0,0,0,.1);
}

.task-card.completed::after {
  content: '✓';
  position: absolute;
  top: 10px;
  right: 10px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--success-gradient);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
}

.task-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 28px;
  margin-bottom: 15px;
}

.task-points {
  font-size: 1.5rem;
  font-weight: 700;
  background: var(--primary-gradient);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
}

.badge-type {
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 500;
}

.badge-earn {
  background: rgba(75, 172, 254, 0.12);
  color: #2196F3;
  border: 1px solid rgba(33, 150, 243, 0.25);
}

.badge-use {
  background: rgba(245, 87, 108, 0.12);
  color: #f5576c;
  border: 1px solid rgba(245, 87, 108, 0.25);
}

.badge-task {
  background: rgba(251, 185, 124, 0.12);
  color: #ff6b00;
  border: 1px solid rgba(255, 107, 0, 0.25);
}

.points-positive {
  color: #4facfe;
  font-weight: 600;
}

.points-negative {
  color: #f5576c;
  font-weight: 600;
}
</style>

<script>
(function() {
  const tasksContainer = document.getElementById('tasksContainer');
  let tasks = [];

  // 從後端抓任務資料
  function loadTasks() {
    fetch('get_tasks.php')
      .then(res => res.json())
      .then(data => {
        tasks = data.tasks;
        renderTasks();
      });
  }

  function renderTasks() {
    tasksContainer.innerHTML = tasks.map(task => `
      <div class="col-md-4">
        <div class="task-card ${task.claimed ? 'completed' : ''}">
          <div class="task-icon">
            <i class="fas fa-tasks"></i>
          </div>
          <h5 class="mb-2">${task.name}</h5>
          <p class="text-muted small mb-3">${task.points} 點</p>
          <button class="btn btn-sm ${task.claimed ? 'btn-secondary' : 'btn-primary'}"
                  onclick="claimTask('${task.name}', ${task.points})"
                  ${task.claimed ? 'disabled' : ''}>
            ${task.claimed ? '已完成' : '領取'}
          </button>
        </div>
      </div>
    `).join('');
  }

  window.claimTask = function(taskName, points) {
    fetch('claim_task.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `taskName=${encodeURIComponent(taskName)}&points=${points}`
    })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      loadTasks();
    });
  };

  loadTasks();
})();
</script>
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
</main>
  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 頂欄日期 & 側欄收合
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });
</script>
 <script>
    // 頂欄日期 & 側欄收合
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });
// 
    document.addEventListener("DOMContentLoaded", () => {
  const totalPointsEl = document.getElementById("totalPoints");
  const monthEarnedEl = document.getElementById("monthEarned");
  const monthUsedEl = document.getElementById("monthUsed");
  const pendingTasksEl = document.getElementById("pendingTasks");
  const refreshBtn = document.getElementById("refreshPointsBtn");

  const loadingIndicator = document.getElementById("loadingIndicator");
  const errorAlert = document.getElementById("errorAlert");
  const errorMessage = document.getElementById("errorMessage");

  async function loadPoints() {
    try {
      // Show loading
      loadingIndicator.classList.remove("d-none");
      errorAlert.classList.add("d-none");

      const response = await fetch("get_points.php");
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      // Update UI
      totalPointsEl.textContent = data.totalPoints ?? 0;
      monthEarnedEl.textContent = data.monthEarned ?? 0;
      monthUsedEl.textContent = data.monthUsed ?? 0;
      pendingTasksEl.textContent = data.pendingTasks ?? 0;

    } catch (error) {
      console.error(error);
      errorMessage.textContent = "載入點數資料時發生錯誤。";
      errorAlert.classList.remove("d-none");
    } finally {
      // Hide loading
      loadingIndicator.classList.add("d-none");
    }
  }

  // When page loads
  loadPoints();

  // When user clicks refresh button
  refreshBtn.addEventListener("click", () => loadPoints());
});


   
  </script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const tbody = document.getElementById("pointsHistoryBody");
  const noDataRow = document.getElementById("noPointsRow");

  fetch('點數紀錄表.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && data.data.length > 0) {
        noDataRow.style.display = 'none';
        tbody.innerHTML = '';

        let cumulativePoints = 0; // 累計點數
        // 假設 data.data 是按日期由新到舊排序，如果不是可先排序
        data.data.forEach(item => {
          const point = Number(item.點數);
          cumulativePoints += point; // 每筆加減累計

          const pointText = point > 0
            ? `<span class="text-success">+${point}</span>`
            : `<span class="text-danger">${point}</span>`;

          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${item.日期 || '-'}</td>
            <td>${item.類型 || '-'}</td>
            <td>${item.備註 || '-'}</td>
            <td class="text-end">${pointText}</td>
            <td class="text-end">${cumulativePoints}</td>
          `;
          tbody.appendChild(row);
        });
      } else {
        noDataRow.style.display = '';
      }
    })
    .catch(err => {
      console.error('載入點數紀錄失敗', err);
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-danger py-4">
            無法載入資料，請稍後再試
          </td>
        </tr>
      `;
    });
});
</script>



  <script src="js/scripts.js"></script>
</body>
</html>
