<?php
session_start();

// 如果沒有登入，導回登入頁（根據實際路徑調整）
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.html");  // ../ 表示回到上一層（專案根目錄）
    exit;
}
require_once 'config.php';


// 防止快取，避免登出後按返回鍵看到頁面
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>會員基本資料 - 員工管理系統</title>

  <!-- 與其他頁一致 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <!-- 將表格轉成圖片 -->
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
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

    /* 頂欄 */
    .sb-topnav{background:var(--dark-bg)!important; border:none; box-shadow:var(--card-shadow); backdrop-filter:blur(10px)}
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#ffffff,#ffffff);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight: 700;
      font-size: 1.5rem;
      background: linear-gradient(45deg,rgb(0, 0, 0), #ffffff);/* 管理系統標題 */
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: none;
    }

    /* 側欄（與首頁一致） */
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
    .container-fluid { padding: 30px !important; }
    h1 {
      background: linear-gradient(135deg, #ce1212, #ff6666); /* 設置紅色漸層 */
    -webkit-background-clip: text; /* 剪裁背景到文字 */
    -webkit-text-fill-color: transparent; /* 文字填充為透明 */
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 30px;
}
    .breadcrumb { background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card { border: none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background: #fff; overflow: hidden; }
    .card-header { background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight: 600; }

    .table { border-radius: var(--border-radius); overflow: hidden; background: #fff; box-shadow: var(--card-shadow); }
    .table thead th { background: var(--primary-gradient); color: #000; border: none; font-weight: 600; padding: 15px; }
    .table tbody td, .table tbody th { padding: 12px; vertical-align: middle; border-color: rgba(0,0,0,.05); }
    .table-hover tbody tr:hover { background: rgba(227,23,111,.05); transform: scale(1.01); }

    .badge-shift { display:inline-block; min-width:72px; padding:.35rem .6rem; border-radius: 999px; background: rgba(102,126,234,.12); border:1px solid rgba(102,126,234,.25); }
    .badge-off   { display:inline-block; padding:.35rem .6rem; border-radius: 999px; background: rgba(0,0,0,.05); border:1px dashed rgba(0,0,0,.15); }

    /* 頂欄搜尋框只在頂欄變白字 */
    .sb-topnav .form-control{border-radius:25px; border:2px solid transparent; background:rgba(255,255,255,.2); color:#fff}
    .sb-topnav .form-control:focus{background:rgba(255,255,255,.3); border-color:rgba(255,255,255,.5); box-shadow:0 0 20px rgba(255,255,255,.2); color:#fff}

    .btn-primary{background:var(--primary-gradient); border:none; border-radius:25px}
    .btn-primary:hover{transform:scale(1.05); box-shadow:0 10px 25px rgba(201, 77, 112, 0.98)}

    /* 可排時段輸入的小樣式 */
    .range-item .input-group-text{min-width:2.5rem; justify-content:center}
    .range-item .form-control{min-width:6.5rem}
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">會員系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

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

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:<br>會員</div>
          
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>基本資料</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <!-- 麵包屑 -->
          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">資料管理</li>
          </ol>



<!-- 編輯按鈕區 -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>會員基本資料</h4>
  <div class="ms-auto">
    <button class="btn btn-primary" id="btnEditProfile">
      <i class="fas fa-edit me-2"></i>編輯資料
    </button>
  </div>
</div>

<!-- 資料顯示卡片（唯讀模式） -->
<div class="card mb-4" id="profileViewCard">
  <div class="card-header"><i class="fas fa-id-card me-2"></i>會員資訊</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-bold">姓名</label>
        <div class="form-control-plaintext" id="viewName">--</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">電話</label>
        <div class="form-control-plaintext" id="viewPhone">--</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">Email</label>
        <div class="form-control-plaintext" id="viewEmail">--</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">生日</label>
        <div class="form-control-plaintext" id="viewBirthday">--</div>
      </div>
      <div class="col-12">
        <label class="form-label fw-bold">地址</label>
        <div class="form-control-plaintext" id="viewAddress">--</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">會員點數</label>
        <div class="form-control-plaintext fw-bold" style="background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent; -webkit-text-fill-color: transparent; font-size: 1.25rem;" id="viewPoints">0 點</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">註冊日期</label>
        <div class="form-control-plaintext" id="viewRegDate">--</div>
      </div>
    </div>
    <div class="small text-muted mt-3">
      <i class="fas fa-info-circle me-1"></i>若需修改資料，請點擊右上角「編輯資料」按鈕。
    </div>
  </div>
</div>

<!-- 資料編輯卡片（編輯模式） -->
<div class="card" id="profileEditCard" style="display: none;">
  <div class="card-header"><i class="fas fa-edit me-2"></i>編輯會員資料</div>
  <div class="card-body">
    <form id="profileForm">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">姓名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="editName" name="name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">電話 <span class="text-danger">*</span></label>
          <input type="tel" class="form-control" id="editPhone" name="phone" required readonly style="background-color: #f8f9fa;">
          <div class="form-text">電話號碼為登入帳號，無法修改</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="editEmail" name="email">
        </div>
        <div class="col-md-6">
          <label class="form-label">生日</label>
          <input type="date" class="form-control" id="editBirthday" name="birthday" required readonly style="background-color: #f8f9fa;">
          <div class="form-text">生日無法修改</div>
        </div>
        <div class="col-12">
          <label class="form-label">地址</label>
          <input type="text" class="form-control" id="editAddress" name="address">
        </div>
        <div class="col-12">
          <hr>
          <h6 class="mb-3"><i class="fas fa-lock me-2"></i>修改密碼（選填）</h6>
        </div>
        <div class="col-md-6">
          <label class="form-label">新密碼</label>
          <input type="password" class="form-control" id="editPassword" name="password">
          <div class="form-text">若不修改密碼請留空</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">確認新密碼</label>
          <input type="password" class="form-control" id="editConfirmPassword" name="confirmPassword">
        </div>
      </div>

      <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" id="btnCancelEdit">
          <i class="fas fa-times me-2"></i>取消
        </button>
        <button type="submit" class="btn btn-primary ms-2">
          <i class="fas fa-save me-2"></i>儲存變更
        </button>
      </div>
    </form>
    <div id="profileFormMsg" class="mt-3"></div>
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
              <span class="mx-2">•</span>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>
  <script>
document.addEventListener("DOMContentLoaded", () => {
  const profileViewCard = document.getElementById('profileViewCard');
  const profileEditCard = document.getElementById('profileEditCard');
  const btnEditProfile = document.getElementById('btnEditProfile');
  const btnCancelEdit = document.getElementById('btnCancelEdit');
  const profileForm = document.getElementById('profileForm');
  const profileFormMsg = document.getElementById('profileFormMsg');

  // === 1. 從後端抓會員資料 ===
  let memberData = {}; // 存後端回來的資料

  function loadMemberFromDB() {
    fetch("getmb.php")
      .then(res => res.json())
      .then(data => {
        console.log(data); // 偵錯用

        if (data.success && data.member) {
          memberData = data.member;
          loadMemberData(); // 顯示模式
        } else {
          alert(data.message || "讀取會員資料失敗");
        }
      })
      .catch(err => console.error("錯誤：", err));
  }

  // === 2. 把資料填到顯示卡片 ===
  function loadMemberData() {
    document.getElementById('viewName').textContent = memberData.name || '--';
    document.getElementById('viewPhone').textContent = memberData.phone || '--';
    document.getElementById('viewEmail').textContent = memberData.email || '--';
    document.getElementById('viewBirthday').textContent = memberData.birthday || '--';
    document.getElementById('viewAddress').textContent = memberData.address || '--';
    document.getElementById('viewPoints').textContent = (memberData.points || 0) + ' 點';
    document.getElementById('viewRegDate').textContent = memberData.regDate || '--';
  }

  // === 3. 把資料填到編輯表單 ===
  function loadEditForm() {
    document.getElementById('editName').value = memberData.name || '';
    document.getElementById('editPhone').value = memberData.phone || '';
    document.getElementById('editEmail').value = memberData.email || '';
    document.getElementById('editBirthday').value = memberData.birthday || '';
    document.getElementById('editAddress').value = memberData.address || '';
    document.getElementById('editPassword').value = '';
    document.getElementById('editConfirmPassword').value = '';
  }

  // === 4. 編輯/取消切換 ===
  btnEditProfile.addEventListener('click', function() {
    loadEditForm();
    profileViewCard.style.display = 'none';
    profileEditCard.style.display = 'block';
  });

  btnCancelEdit.addEventListener('click', function() {
    profileViewCard.style.display = 'block';
    profileEditCard.style.display = 'none';
    profileFormMsg.innerHTML = '';
  });

  // === 5. 表單送出 ===
  profileForm.addEventListener('submit', function(e) {
    e.preventDefault();

    // 基本驗證
    const name = document.getElementById('editName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const password = document.getElementById('editPassword').value;
    const confirmPassword = document.getElementById('editConfirmPassword').value;

    if (!name) {
      showProfileMessage('請輸入姓名', 'danger');
      return;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showProfileMessage('Email 格式不正確', 'danger');
      return;
    }

    if (password || confirmPassword) {
      if (password.length < 6) {
        showProfileMessage('密碼長度至少需要6個字元', 'danger');
        return;
      }
      if (password !== confirmPassword) {
        showProfileMessage('兩次密碼輸入不一致', 'danger');
        return;
      }
    }

    // 收集表單資料
    const formData = new FormData(this);

    // 顯示載入狀態
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>儲存中...';
    submitBtn.disabled = true;

    
    fetch('updatemb.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        showProfileMessage('資料更新成功', 'success');
        // 重新抓資料庫刷新顯示
        loadMemberFromDB();
        // 回到顯示模式
        profileViewCard.style.display = 'block';
        profileEditCard.style.display = 'none';
      } else {
        showProfileMessage(result.message || '更新失敗', 'danger');
      }
    })
    .catch(err => {
      console.error('錯誤：', err);
      showProfileMessage('系統錯誤', 'danger');
    })
    .finally(() => {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
  });

  function showProfileMessage(msg, type) {
    profileFormMsg.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  }

  // === 頁面載入時抓資料 ===
  loadMemberFromDB();
});
</script>



<script> // === 表單送出 ===
const profileViewCard = document.getElementById('profileViewCard');
const profileEditCard = document.getElementById('profileEditCard');
const profileForm = document.getElementById('profileForm');
const profileFormMsg = document.getElementById('profileFormMsg'); 
  profileForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // 顯示載入狀態
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>儲存中...';
    submitBtn.disabled = true;

    fetch('updatemb.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 更新頁面顯示
            memberData.name = formData.get('name');
            memberData.email = formData.get('email');
            memberData.birthday = formData.get('birthday');
            memberData.address = formData.get('address');
            loadMemberData();

            showProfileMessage(data.message, 'success');

            setTimeout(() => {
                profileViewCard.style.display = 'block';
                profileEditCard.style.display = 'none';
                profileFormMsg.innerHTML = '';
            }, 1500);
        } else {
            showProfileMessage(data.message || '更新失敗', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showProfileMessage('更新失敗，請稍後再試', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<script>
  // 顯示當前日期
  document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-Hant', {
    year: 'numeric', month: 'long', day: 'numeric', weekday: 'long'
  });
</script>
</body>
</html>
