<?php
// 啟用登入保護
session_start();

// 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 取得用戶資訊
$userName = $_SESSION['name'] ?? '用戶';
$userId = $_SESSION['uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>請假申請 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 全站風格：跟首頁 / 日報表記錄 一致的淡藍漸層 ====== */
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
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "Microsoft JhengHei", sans-serif;
      color: var(--text-main);
    }

    /* ====== Top navbar：藍色漸層 ====== */
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

    /* ====== Sidebar：跟範例一樣的淡藍漸層 ====== */
    .sb-sidenav {
      background:
        radial-gradient(circle at 40% 0%, rgba(56, 189, 248, 0.38), transparent 65%),
        radial-gradient(circle at 80% 100%, rgba(147, 197, 253, 0.34), transparent 70%),
        linear-gradient(180deg, rgba(220, 235, 255, 0.92), rgba(185, 205, 255, 0.9));
      backdrop-filter: blur(22px);
      border-right: 1px solid rgba(255, 255, 255, 0.55);
    }

    .sb-sidenav-menu-heading {
      color: #1e293b !important;
      opacity: 0.75;
      font-size: 0.78rem;
      letter-spacing: .18em;
      margin: 20px 0 8px 16px;
    }

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

    /* 修正側欄箭頭 / icon 顏色（SVG / ::after） */
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
      padding: 6px 14px;
      font-size: 0.8rem;
      border: 1px solid rgba(148, 163, 184, 0.4);
      box-shadow: var(--shadow-soft);
    }

    .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
      color: #9ca3af;
    }

    /* ====== 卡片 ====== */
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

    /* ====== 表格樣式 ====== */
    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
      border: none;
      font-weight: 600;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
    }
    .table tbody td {
      text-align: center;
      vertical-align: middle;
      white-space: nowrap;
      border-color: rgba(226, 232, 240, 0.9);
      padding: 12px 10px;
    }
    .table tbody td.text-start {
      text-align: left;
      white-space: normal;
    }
    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    footer {
      background: transparent;
      border-top: 1px solid rgba(148, 163, 184, 0.35);
      margin-top: 24px;
      padding-top: 14px;
      font-size: 0.8rem;
      color: var(--text-subtle);
    }

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

    /* ====== 按鈕微調：主色改成藍漸層膠囊 ====== */
    .btn-primary {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      border: none;
      border-radius: 999px;
      padding-inline: 20px;
      font-weight: 600;
    }
    .btn-primary:hover {
      filter: brightness(1.03);
      box-shadow: 0 10px 22px rgba(59, 130, 246, 0.35);
      transform: translateY(-1px);
    }

    .btn-outline-secondary {
      border-radius: 999px;
    }

    /* ====== 表單細節 ====== */
    .form-label.required-label::after {
      content: ' *';
      color: #dc2626;
      font-weight: 700;
    }

    .upload-preview {
      border: 1px dashed rgba(148, 163, 184, 0.8);
      border-radius: 12px;
      padding: 10px;
      display: none;
      background: rgba(248, 250, 252, 0.9);
    }
    .upload-preview img {
      max-width: 160px;
      border-radius: 8px;
      display: block;
    }

    /* ====== 狀態 badge ====== */
    .badge-status {
      padding: .32rem .75rem;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.78rem;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .status-pending {
      background: rgba(251, 191, 36, 0.12);
      color: #92400e;
      border: 1px solid rgba(251, 191, 36, 0.55);
    }
    .status-approved {
      background: rgba(34, 197, 94, 0.12);
      color: #166534;
      border: 1px solid rgba(34, 197, 94, 0.55);
    }
    .status-rejected {
      background: rgba(248, 113, 113, 0.12);
      color: #b91c1c;
      border: 1px solid rgba(248, 113, 113, 0.55);
    }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="indexC.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
      <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
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
    <!-- Side Nav -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="indexC.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
              首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <!-- 直接平鋪的按鈕：不使用 collapse -->
            <a class="nav-link" href="新增班表.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表
            </a>
            <a class="nav-link active" href="新增請假申請.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請
            </a>
            <a class="nav-link" href="員工薪資記錄.php">
              <div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄
            </a>
            <a class="nav-link" href="員工打卡記錄.php">
              <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>打卡記錄
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
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>請假申請</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">
              <a class="text-decoration-none" href="index.html">首頁</a>
            </li>
            <li class="breadcrumb-item active">請假申請</li>
          </ol>

          <!-- 提交請假單 -->
          <div class="card mb-4">
            <div class="card-header">
              <i class="fas fa-file-signature me-2"></i>提交請假單
            </div>
            <div class="card-body">
              <form id="leaveForm">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label required-label">假別</label>
                    <select class="form-select" id="leaveType" required>
                      <option value="" disabled selected>請選擇</option>
                      <option>事假</option>
                      <option>病假</option>
                      <option>生理假</option>
                      <option>特休</option>
                      <option>婚假</option>
                      <option>喪假</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label required-label">開始日期</label>
                    <input type="date" class="form-control" id="startDate" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label required-label">結束日期</label>
                    <input type="date" class="form-control" id="endDate" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">請假原因</label>
                    <textarea class="form-control" id="reason" rows="3" placeholder="可簡述原因 (選填)"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label required-label">上傳證明(照片,必填)</label>
                    <input type="file" class="form-control" id="photo" accept="image/*" required>
                    <div class="form-text">支持 jpg / png / heic，大小建議 &lt; 5MB</div>
                    <div class="upload-preview mt-2" id="previewBox">
                      <img id="previewImg" alt="預覽" />
                    </div>
                  </div>
                </div>
                <div class="text-end mt-3">
                  <button type="button" class="btn btn-outline-secondary" id="btnClear">清除</button>
                  <button type="submit" class="btn btn-primary ms-2" id="btnSubmit">
                    <i class="fas fa-paper-plane me-1"></i>送出申請
                  </button>
                </div>
              </form>
              <div id="formMsg" class="mt-3"></div>
            </div>
          </div>

          <!-- 我的請假紀錄 -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><i class="fas fa-history me-2"></i>我的請假紀錄</div>
              <small class="text-muted">最新筆在最上方</small>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead>
                    <tr>
                      <th>假別</th>
                      <th>開始</th>
                      <th>結束</th>
                      <th>原因</th>
                      <th>狀態</th>
                    </tr>
                  </thead>
                  <tbody id="myLeaveTable">
                    <tr>
                      <td colspan="5" class="text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>載入中...
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div>
              <a href="#">Privacy Policy</a>
              &middot;
              <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 初始化
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW', {
        year:'numeric', month:'long', day:'numeric', weekday:'long'
      });

    document.getElementById('sidebarToggle').addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    const API_SUBMIT = '新增請假.php';
    const API_MYLIST = '查詢請假紀錄.php';

    // 圖片預覽
    const photoInput = document.getElementById('photo');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImg');

    photoInput.addEventListener('change', () => {
      const f = photoInput.files?.[0];
      if (!f) {
        previewBox.style.display='none';
        previewImg.src='';
        return;
      }
      if (!f.type.startsWith('image/')) {
        alert('僅支持圖片檔');
        photoInput.value='';
        return;
      }
      if (f.size > 5 * 1024 * 1024) {
        alert('檔案大小請小於 5MB');
        photoInput.value='';
        return;
      }
      const url = URL.createObjectURL(f);
      previewImg.src = url;
      previewBox.style.display = 'block';
    });

    // 清除按鈕
    document.getElementById('btnClear').addEventListener('click', () => {
      document.getElementById('leaveForm').reset();
      previewBox.style.display = 'none';
      previewImg.src='';
      showFormMsg('');
    });

    // 表單送出
    document.getElementById('leaveForm').addEventListener('submit', async (e) => {
      e.preventDefault();

      const type  = document.getElementById('leaveType').value || '';
      const start = document.getElementById('startDate').value || '';
      const end   = document.getElementById('endDate').value || '';
      const photo = photoInput.files?.[0];

      // 驗證必填欄位
      if (!type || !start || !end) {
        showFormMsg('請先完整選擇假別與起訖日期', 'danger');
        return;
      }

      // 驗證照片必填
      if (!photo) {
        showFormMsg('請上傳證明照片（必填）', 'danger');
        return;
      }

      if (new Date(end) < new Date(start)) {
        showFormMsg('結束日期不可早於開始日期', 'danger');
        return;
      }

      const btn = document.getElementById('btnSubmit');
      btn.disabled = true;
      const oldHtml = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>送出中...';

      try {
        const fd = new FormData();
        fd.append('leaveType', type);
        fd.append('startDate', start);
        fd.append('endDate', end);
        fd.append('reason', document.getElementById('reason').value || '');
        fd.append('photo', photo);

        const res  = await fetch(API_SUBMIT, {
          method: 'POST',
          body: fd
        });

        const text = await res.text();

        if (!res.ok) {
          throw new Error(text || '送出失敗');
        }

        showFormMsg(text || '已送出申請!', 'success');

        // 重整列表
        await loadMyLeave();

        // 清空表單
        document.getElementById('leaveForm').reset();
        previewBox.style.display='none';
        previewImg.src='';

      } catch(err) {
        console.error('送出錯誤:', err);
        showFormMsg('送出失敗: ' + err.message, 'danger');
      } finally {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
      }
    });

    // 顯示訊息
    function showFormMsg(text, type='secondary') {
      const slot = document.getElementById('formMsg');
      slot.innerHTML = text
        ? `<div class="alert alert-${type} mb-0" role="alert">${text}</div>`
        : '';
    }

    // 載入請假紀錄
    async function loadMyLeave() {
      const tbody = document.getElementById('myLeaveTable');

      try {
        const res = await fetch(API_MYLIST);

        if (!res.ok) {
          throw new Error('HTTP ' + res.status);
        }

        const json = await res.json();
        const list = json.data || [];

        tbody.innerHTML = '';

        if (list.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="5" class="text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
              </td>
            </tr>
          `;
          return;
        }

        // 渲染資料
        tbody.innerHTML = list.map(item => `
          <tr>
            <td>${escapeHtml(item.type || '')}</td>
            <td>${escapeHtml(item.start || '')}</td>
            <td>${escapeHtml(item.end || '')}</td>
            <td class="text-start">${escapeHtml(item.reason || '')}</td>
            <td>${renderStatus(item.status)}</td>
          </tr>
        `).join('');

      } catch(err) {
        console.error('載入請假紀錄失敗:', err);
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-danger py-4">
              <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
              載入失敗: ${err.message}
            </td>
          </tr>
        `;
      }
    }

    // 狀態渲染
    function renderStatus(s) {
      const status = parseInt(s);

      if (status === 2) {
        return `<span class="badge-status status-approved">已通過</span>`;
      }

      if (status === 3) {
        return `<span class="badge-status status-rejected">已駁回</span>`;
      }

      return `<span class="badge-status status-pending">審核中</span>`;
    }

    // HTML 跳脫
    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, s => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[s]));
    }

    // 頁面載入時執行
    window.addEventListener('DOMContentLoaded', () => {
      loadMyLeave();
    });
  </script>
  <script src="js/scripts.js"></script>
  <script src="js/user-avatar-loader.js"></script>
</body>
</html>
