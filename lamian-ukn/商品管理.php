<?php
// 🔥 新頁面：商品管理.php (原 商品主檔管理.php)
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 權限：僅 A 級（老闆）可以訪問
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

// 🔥 修改：更新標題
$pageTitle = '商品管理 - 員工管理系統'; // 標題

// 統一路徑 (JS 會用到)
$API_BASE_URL  = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* 🔥 保留：維持您原有的橘色主題 CSS */
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
    *{ transition: var(--transition); }
    body{ background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }
    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{ font-weight:700; font-size:1.5rem; background: linear-gradient(45deg,#ffffff,#ffffff);
      -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; }

    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container {
        position: relative; display: flex; align-items: center;
        background: rgba(255, 255, 255, 0.15); border-radius: 50px;
        padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px); border: 2px solid transparent;
    }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); }
    .search-input {
        flex: 1; border: none; outline: none; background: transparent;
        padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500;
    }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        border: none; border-radius: 40px; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn i { color: #ff6b6b; font-size: 16px; }
    .user-avatar{border:2px solid rgba(255,255,255,.5)}

    .container-fluid{ padding:30px !important; }
    h1{ background: var(--primary-gradient); -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent; font-weight:700; font-size:2.5rem; margin-bottom:30px; }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; }
    .btn-primary{
      background: var(--primary-gradient) !important;
      border: none !important;
      border-radius: 25px;
      color: #fff;
    }
    .btn-primary:hover,.btn-primary:focus,.btn-primary:active{
      background: var(--primary-gradient) !important;
      filter: brightness(1.05);
      box-shadow: 0 10px 25px rgba(209,209,209,.976);
      color: #fff;
    }
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

            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory" aria-expanded="true">
                  庫存管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse show" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link active" href="商品管理.php">商品管理</a>
                  </nav>
                </div>
                <a class="nav-link" href="日報表.php"> <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
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
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
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
        <div class="container-fluid px-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>商品管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">商品管理</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>
          
          <div class="row g-4">
            
            <div class="col-lg-5">
              <div class="card h-100">
                <div class="card-header fw-semibold"><i class="fas fa-tags me-2"></i>商品分類管理</div>
                <div class="card-body">
                  <h5 class="mb-3">新增/編輯分類</h5>
                  <form id="categoryForm">
                    <div class="input-group">
                      <input type="hidden" id="catId" value="">
                      <input type="text" id="catName" class="form-control" placeholder="輸入分類名稱 (例如: 飲料)" required>
                      <button class="btn btn-primary" type="submit" id="btnSaveCat">儲存</button>
                      <button class="btn btn-outline-secondary" type="button" id="btnClearCat">清除</button>
                    </div>
                  </form>
                </div>
                <div class="card-body border-top">
                  <h5 class="mb-3">現有分類</h5>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>分類名稱</th>
                          <th style="width: 100px;">操作</th>
                        </tr>
                      </thead>
                      <tbody id="catListBody">
                        <tr><td colspan="2" class="text-muted">載入中...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-7">
              <div class="card h-100">
                <div class="card-header fw-semibold">
                  <i class="fas fa-boxes me-2"></i>商品主檔管理
                </div>
                <div class_="card-body p-3">
                  <div class="d-flex justify-content-end p-3">
                    <button class="btn btn-primary" id="btnShowProductModal">
                      <i class="fas fa-plus me-1"></i> 新增商品
                    </button>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                      <thead class="table-light">
                        <tr>
                          <th>ID</th>
                          <th>品項名稱</th>
                          <th>類別</th>
                          <th>單位</th>
                          <th>操作</th>
                        </tr>
                      </thead>
                      <tbody id="prodListBody">
                        <tr><td colspan="5" class="text-muted">載入中...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div> </div> </main>

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

  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalLabel">新增/編輯商品</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="productForm">
          <div class="modal-body">
            <input type="hidden" id="prodId" value="">
            <div class="mb-3">
              <label for="prodName" class="form-label">品項名稱 <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="prodName" required>
            </div>
            <div class="mb-3">
              <label for="prodUnit" class="form-label">單位 <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="prodUnit" placeholder="例如: 包, 瓶, 公斤, 個" required>
            </div>
            <div class="mb-3">
              <label for="prodCatId" class="form-label">商品分類 <span class="text-danger">*</span></label>
              <select class="form-select" id="prodCatId" required>
                <option value="">請先建立分類</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            <button type="submit" class="btn btn-primary" id="btnSaveProd">儲存</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteModalLabel">確認刪除</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="deleteModalText">您確定要刪除嗎？此操作無法復原。</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="btnConfirmDelete">確認刪除</button>
        </div>
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    // 🔥 修改：API Endpoints 已合併
    const API_BASE       = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_PRODS_LIST = API_BASE + '/product_list.php';      // (GET) 讀取商品 (沿用)
    const API_CAT_API    = API_BASE + '/category_master_api.php'; // (GET ?action=list) / (POST action=save/delete)
    const API_PROD_API   = API_BASE + '/product_master_api.php';  // (POST action=save/delete)

    // Global State
    let allCategories = [];
    let allProducts = [];
    let productModal, deleteModal; // BS Modal 實體
    
    // 工具
    const qs = sel => document.querySelector(sel);
    const qsa = sel => document.querySelectorAll(sel);
    const escapeHtml = str => String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    const setBusy = (btn, busy) => { btn.disabled = busy; btn.innerHTML = busy ? '<span class="spinner-border spinner-border-sm"></span>' : btn.dataset.text || '儲存'; };
    const showOk = (msg) => { qs('#msgOk').textContent = msg; qs('#msgOk').classList.remove('d-none'); setTimeout(()=>qs('#msgOk').classList.add('d-none'), 2500); };
    const showErr = (msg) => { qs('#msgErr').textContent = msg; qs('#msgErr').classList.remove('d-none'); setTimeout(()=>qs('#msgErr').classList.add('d-none'), 5000); };

    // ===== 初始化 =====
    window.addEventListener('DOMContentLoaded', async () => {
      // 側欄/日期
      qs('#currentDate').textContent = new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
      qs('#sidebarToggle').addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });
      
      // 儲存按鈕原始文字
      qsa('button[type="submit"]').forEach(btn => btn.dataset.text = btn.textContent);

      // 初始化 Modals
      productModal = new bootstrap.Modal(qs('#productModal'));
      deleteModal = new bootstrap.Modal(qs('#deleteModal'));
      
      await loadLoggedInUser();
      
      await loadCategories(); 
      await loadProducts();
      
      bindEvents();
    });

    // ===== 事件綁定 =====
    function bindEvents() {
      // 分類表單
      qs('#categoryForm').addEventListener('submit', saveCategory);
      qs('#btnClearCat').addEventListener('click', resetCategoryForm);
      qs('#catListBody').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        if (btn.dataset.action === 'edit-cat') {
          qs('#catId').value = id;
          qs('#catName').value = name;
          qs('#catName').focus();
        } else if (btn.dataset.action === 'del-cat') {
          showDeleteModal('category', id, name);
        }
      });
      
      // 商品表單
      qs('#btnShowProductModal').addEventListener('click', () => showProductModal(null));
      qs('#productForm').addEventListener('submit', saveProduct);
      qs('#prodListBody').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        if (btn.dataset.action === 'edit-prod') {
          const prod = allProducts.find(p => p.id == id);
          showProductModal(prod);
        } else if (btn.dataset.action === 'del-prod') {
          showDeleteModal('product', id, name);
        }
      });
      
      // 刪除 Modal
      qs('#btnConfirmDelete').addEventListener('click', executeDelete);
    }
    
    // ===== 資料載入 (R) =====
    
    // 載入分類
    async function loadCategories() {
      const tbody = qs('#catListBody');
      try {
        // 🔥 修改：呼叫合併的 API (action=list)
        const res = await fetch(API_CAT_API + '?action=list', {credentials:'include'});
        if (!res.ok) throw new Error('API 錯誤: ' + res.status);
        const data = await res.json();
        
        allCategories = Array.isArray(data) ? data : (data.data || []);
        renderCategoryTable();
        populateCategoryDropdown();
        
      } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="2" class="text-danger">分類載入失敗 (API: ${API_CAT_API})</td></tr>`;
        showErr('無法載入商品分類: ' + e.message);
      }
    }
    
    // 載入商品 (使用您已有的 product_list.php)
    async function loadProducts() {
      const tbody = qs('#prodListBody');
      try {
        const res = await fetch(API_PRODS_LIST + '?t=' + Date.now(), {credentials:'include'}); // 加 cache buster
        if (!res.ok) throw new Error('API 錯誤: ' + res.status);
        const data = await res.json();

        allProducts = Array.isArray(data) ? data : (data.data || []);
        renderProductTable();

      } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger">商品載入失敗 (API: ${API_PRODS_LIST})</td></tr>`;
        showErr('無法載入商品清單: ' + e.message);
      }
    }

    // ===== 畫面渲染 =====
    
    // 渲染分類表格
    function renderCategoryTable() {
      const tbody = qs('#catListBody');
      if (allCategories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-muted">尚無分類</td></tr>`;
        return;
      }
      tbody.innerHTML = allCategories.map(cat => `
        <tr>
          <td class="align-middle">${escapeHtml(cat.name)} (ID: ${cat.id})</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-action="edit-cat" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-action="del-cat" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }
    
    // 渲染商品表格
    function renderProductTable() {
      const tbody = qs('#prodListBody');
      if (allProducts.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-muted">尚無商品</td></tr>`;
        return;
      }
      tbody.innerHTML = allProducts.map(prod => `
        <tr>
          <td>${escapeHtml(prod.id)}</td>
          <td>${escapeHtml(prod.name)}</td>
          <td>${escapeHtml(prod.category || 'N/A')}</td>
          <td>${escapeHtml(prod.unit)}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-action="edit-prod" data-id="${prod.id}">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-action="del-prod" data-id="${prod.id}" data-name="${escapeHtml(prod.name)}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }
    
    // 填充商品 Modal 中的分類下拉選單
    function populateCategoryDropdown() {
      const sel = qs('#prodCatId');
      if (allCategories.length === 0) {
        sel.innerHTML = `<option value="">請先建立分類</option>`;
        sel.disabled = true;
      } else {
        sel.disabled = false;
        sel.innerHTML = '<option value="">-- 請選擇分類 --</option>' +
          allCategories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
      }
    }
    
    // ===== 資料儲存 (C/U) =====

    // 重置分類表單
    function resetCategoryForm() {
      qs('#catId').value = '';
      qs('#catName').value = '';
      qs('#btnSaveCat').dataset.text = '儲存';
      qs('#btnSaveCat').innerHTML = '儲存';
    }

    // 儲存分類
    async function saveCategory(e) {
      e.preventDefault();
      const btn = qs('#btnSaveCat');
      const id = qs('#catId').value;
      const name = qs('#catName').value.trim();
      if (!name) return showErr('請輸入分類名稱');
      
      setBusy(btn, true);
      try {
        // 🔥 修改：呼叫合併的 API (action=save)
        const res = await fetch(API_CAT_API, {
          method: 'POST', credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action: 'save', id: id || null, name: name })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || '儲存失敗');
        
        showOk(id ? '分類已更新' : '分類已新增');
        resetCategoryForm();
        await loadCategories(); // 重新載入分類

      } catch(e) {
        showErr('分類儲存失敗: ' + e.message);
      } finally {
        setBusy(btn, false);
      }
    }

    // 顯示商品 Modal (新增 or 編輯)
    function showProductModal(prod) {
      qs('#productForm').reset();
      if (prod) {
        // 編輯
        qs('#productModalLabel').textContent = '編輯商品';
        qs('#prodId').value = prod.id;
        qs('#prodName').value = prod.name;
        qs('#prodUnit').value = prod.unit;
        qs('#prodCatId').value = prod.category_id || '';
      } else {
        // 新增
        qs('#productModalLabel').textContent = '新增商品';
        qs('#prodId').value = '';
      }
      productModal.show();
    }
    
    // 儲存商品
    async function saveProduct(e) {
      e.preventDefault();
      const btn = qs('#btnSaveProd');
      const body = {
        action: 'save', // 🔥 修改：加入 action
        id: qs('#prodId').value || null,
        name: qs('#prodName').value.trim(),
        unit: qs('#prodUnit').value.trim(),
        category_id: qs('#prodCatId').value
      };
      
      if (!body.name || !body.unit || !body.category_id) {
        return showErr('所有欄位皆為必填');
      }
      
      setBusy(btn, true);
      try {
        // 🔥 修改：呼叫合併的 API (action=save)
        const res = await fetch(API_PROD_API, {
          method: 'POST', credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || '儲存失敗');
        
        showOk(body.id ? '商品已更新' : '商品已新增');
        productModal.hide();
        await loadProducts(); // 重新載入商品

      } catch(e) {
        showErr('商品儲存失敗: ' + e.message);
      } finally {
        setBusy(btn, false);
      }
    }
    
    // ===== 資料刪除 (D) =====
    
    // 顯示刪除確認
    function showDeleteModal(type, id, name) {
      qs('#deleteModalText').innerHTML = `您確定要刪除 ${type==='category'?'分類':'商品'}：<br><strong>${escapeHtml(name)} (ID: ${id})</strong>？<br>此操作無法復原。`;
      qs('#btnConfirmDelete').dataset.type = type;
      qs('#btnConfirmDelete').dataset.id = id;
      deleteModal.show();
    }
    
    // 執行刪除
    async function executeDelete() {
      const btn = qs('#btnConfirmDelete');
      const type = btn.dataset.type;
      const id = btn.dataset.id;
      
      // 🔥 修改：決定 API URL 和 body
      const url = (type === 'category') ? API_CAT_API : API_PROD_API;
      const body = { action: 'delete', id: id };
      
      setBusy(btn, true);
      try {
        const res = await fetch(url, {
          method: 'POST', credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || '刪除失敗');
        
        showOk('已刪除');
        deleteModal.hide();
        
        if (type === 'category') {
          await loadCategories(); // 重新載入
          await loadProducts(); // 重新載入商品 (分類可能已變)
        } else {
          await loadProducts(); // 重新載入
        }

      } catch(e) {
        showErr('刪除失敗: ' + e.message);
      } finally {
        setBusy(btn, false);
      }
    }

    // ===== 載入登入者資訊 (同其他頁) =====
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        // 🔥 修改：更新 console
        console.log('✅ 商品管理 已登入:', userName, 'ID:', userId);
        try {
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
            const data = await r.json();
            if(data.avatar_url) {
                const avatarUrl = data.avatar_url + (data.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
                const avatar = document.querySelector('.navbar .user-avatar');
                if(avatar) avatar.src = avatarUrl;
            }
            }
        } catch(e) {
            console.warn('載入頭像失敗:', e);
        }
    }
  </script>

</body>
</html>