<?php
// 若 api 在 /lamian-ukn/api，這行不用改
$API_BASE_URL = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>庫存調整 - 員工管理系統</title>

  <!-- 與其他頁一致 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root{
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
    *{ transition: var(--transition); }
    body{ background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }

    /* 頂欄 */
    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#ffffff,#ffffff);
      -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
    }

    /* 側欄 */
    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    /* 內容區 */
    .container-fluid{ padding:30px !important; }
    h1{
      background: var(--primary-gradient);
      -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight:700; font-size:2.5rem; margin-bottom:30px;
    }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }

    .table thead th{ background: var(--primary-gradient); color:#000; border:none; }

    /* 讓所有 .btn-primary 用網站主色漸層（含 hover / active / focus） */
.btn-primary{
  background: var(--primary-gradient) !important;
  border: none !important;
  border-radius: 25px;
  color: #fff;
}
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active{
  background: var(--primary-gradient) !important;
  filter: brightness(1.05);
  box-shadow: 0 10px 25px rgba(209,209,209,.976);
  color: #fff;
}

  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
      <div class="input-group">
        <input class="form-control" type="text" placeholder="Search for..." aria-label="Search" />
        <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
      </div>
    </form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user fa-fw"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="#!">Settings</a></li>
          <li><a class="dropdown-item" href="#!">Activity Log</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="#!">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <div id="layoutSidenav">
    <!-- Side Nav（與庫存查詢相同結構，只把「庫存調整」設為 active） -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.html">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.html">員工資料表</a>
                <a class="nav-link" href="班表管理.html">班表管理</a>
                <a class="nav-link" href="日報表記錄.html">日報表記錄</a>
                <a class="nav-link" href="假別管理.html">假別管理</a>
                <a class="nav-link" href="打卡記錄.html">打卡紀錄</a>
                <a class="nav-link" href="薪資管理.html">薪資管理</a>
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
                    <a class="nav-link active" href="庫存調整.php">庫存調整</a>
                  </nav>
                </div>

                <a class="nav-link" href="日報表.html"><div class="sb-nav-link-icon"></div>日報表</a>
                <a class="nav-link" href="薪資記錄.html"><div class="sb-nav-link-icon"></div>薪資記錄</a>
                <a class="nav-link" href="班表.html"><div class="sb-nav-link-icon"></div>班表</a>
              </nav>
            </div>

            <a class="nav-link" href="請假申請.html"><div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請</a>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseWebsite" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>網站管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="layout-static.html">官網資料修改</a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#websiteCollapseMember" aria-expanded="false">
                  會員管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="websiteCollapseMember" data-bs-parent="#sidenavAccordionWebsite">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="member-list.html">會員清單</a>
                    <a class="nav-link" href="member-detail.html">詳細資料頁</a>
                    <a class="nav-link" href="point-manage.html">點數管理</a>
                  </nav>
                </div>
              </nav>
            </div>

            <div class="sb-sidenav-menu-heading">Addons</div>
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
            <a class="nav-link" href="tables.html"><div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables</a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          Start Bootstrap
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>庫存調整</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.html">首頁</a></li>
            <li class="breadcrumb-item active">庫存調整</li>
          </ol>

          <!-- 成功 / 失敗訊息 -->
          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

          <!-- 新增庫存（入庫 / 出庫） -->
          <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-plus-circle me-2"></i>新增庫存異動</div>
            <div class="card-body">
              <form id="adjustForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">品項</label>
                  <select id="itemSelect" class="form-select" required>
                    <option value="">請選擇品項</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">類別</label>
                  <input id="itemCategory" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">單位</label>
                  <input id="itemUnit" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">數量</label>
                  <input id="qty" class="form-control" type="number" step="1" placeholder="例如 10" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label d-block">方向</label>
                  <div class="d-flex gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_in" value="in" checked>
                      <label class="form-check-label" for="io_in">入庫</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_out" value="out">
                      <label class="form-check-label" for="io_out">出庫</label>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <label class="form-label">進貨/異動時間（可留白=現在）</label>
                  <input id="when" class="form-control" type="datetime-local">
                </div>
                <div class="col-md-3">
                  <label class="form-label">進貨人 / 經手人</label>
                  <input id="who" class="form-control" type="text" placeholder="輸入姓名" required>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-1)"></i>送出</button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-secondary w-100" type="button" id="btnClear"><i class="fas fa-eraser me-1"></i>清除</button>
                </div>
              </form>
            </div>
          </div>

          <!-- 最近異動 -->
          <div class="card">
            <div class="card-header fw-semibold"><i class="fas fa-clock-rotate-left me-2"></i>最近異動</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                  <thead class="table-light">
                    <tr>
                      <th>編號</th>
                      <th>品項名稱</th>
                      <th>類別</th>
                      <th>數量</th>
                      <th>單位</th>
                      <th>時間</th>
                      <th>經手人</th>
                    </tr>
                  </thead>
                  <tbody id="recentBody">
                    <tr id="recentEmpty" class="d-none">
                      <td colspan="7" class="text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料</td>
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
              <a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    // 今日日期 / 側欄收合（保持與庫存查詢同樣的行為，不強制顯示/隱藏）
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    // API endpoint
    const API_BASE   = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_PRODUCTS = API_BASE + '/product_list.php';
    const API_ADJUST  = API_BASE + '/inventory_adjust.php';
    const API_RECENT  = API_BASE + '/inventory_latest.php?limit=20';

    let products = []; // {id,name,category,unit}

    window.addEventListener('DOMContentLoaded', async ()=>{
      await loadProducts();
      await loadRecent();
      bind();
    });

    function bind(){
      document.getElementById('itemSelect').addEventListener('change', onItemChange);
      document.getElementById('adjustForm').addEventListener('submit', submitAdjust);
      document.getElementById('btnClear').addEventListener('click', resetForm);
    }

    async function loadProducts(){
      try{
        const r = await fetch(API_PRODUCTS, {credentials:'include'});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const data = await r.json();
        products = Array.isArray(data) ? data : (data.data||[]);
        const sel = document.getElementById('itemSelect');
        sel.innerHTML = '<option value="">請選擇品項</option>' +
          products.map(p => `<option value="${p.id}">${escapeHtml(p.name)}${p.unit?'（'+escapeHtml(p.unit)+'）':''}</option>`).join('');
      }catch(e){ showErr('載入品項失敗'); console.error(e); }
    }

    function onItemChange(){
      const id = Number(document.getElementById('itemSelect').value||0);
      const p = products.find(x=> Number(x.id) === id);
      document.getElementById('itemCategory').value = p ? (p.category||'') : '';
      document.getElementById('itemUnit').value      = p ? (p.unit||'')     : '';
    }

    async function submitAdjust(e){
      e.preventDefault();
      hideMsg();
      const item_id = Number(document.getElementById('itemSelect').value||0);
      const qty_raw = Number(document.getElementById('qty').value||0);
      const io = document.querySelector('input[name="io"]:checked')?.value || 'in';
      const updated_by = (document.getElementById('who').value||'').trim();
      const when = document.getElementById('when').value;

      if(!item_id){ return showErr('請選擇品項'); }
      if(!qty_raw || !Number.isFinite(qty_raw)){ return showErr('請輸入正確數量'); }
      if(!updated_by){ return showErr('請輸入經手人'); }

      const quantity = io === 'out' ? -Math.abs(qty_raw) : Math.abs(qty_raw);
      const body = { item_id, quantity, updated_by };
      if(when){ body.when = when; }

      try{
        const r = await fetch(API_ADJUST, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(body),
          credentials:'include'
        });
        const resp = await r.json();
        if(!r.ok || resp.error){ throw new Error(resp.error || ('HTTP '+r.status)); }
        showOk('已新增庫存異動（編號 '+resp.id+'）');
        resetForm();
        await loadRecent();
      }catch(e){ console.error(e); showErr('新增失敗：'+e.message); }
    }

    async function loadRecent(){
      try{
        const r = await fetch(API_RECENT, {credentials:'include'});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const tb = document.getElementById('recentBody');
        const empty = document.getElementById('recentEmpty');
        tb.innerHTML='';
        if(!rows.length){ empty.classList.remove('d-none'); tb.appendChild(empty); return; }
        empty.classList.add('d-none');
        tb.innerHTML = rows.map(x=>`
          <tr>
            <td>${escapeHtml(x.id)}</td>
            <td class="text-start">${escapeHtml(x.name||'')}</td>
            <td>${escapeHtml(x.category||'')}</td>
            <td class="${Number(x.quantity)<0?'text-danger fw-bold':''}">${escapeHtml(x.quantity)}</td>
            <td>${escapeHtml(x.unit||'')}</td>
            <td>${escapeHtml(x.last_update_iso||x.last_update||'')}</td>
            <td>${escapeHtml(x.updated_by||'')}</td>
          </tr>
        `).join('');
      }catch(e){ console.error(e); showErr('載入最近異動失敗'); }
    }

    function resetForm(){
      document.getElementById('itemSelect').value='';
      document.getElementById('itemCategory').value='';
      document.getElementById('itemUnit').value='';
      document.getElementById('qty').value='';
      document.getElementById('when').value='';
      document.getElementById('who').value='';
      document.getElementById('io_in').checked = true;
    }

    function showOk(msg){ const a=document.getElementById('msgOk'); a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 2500); }
    function showErr(msg){ const a=document.getElementById('msgErr'); a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 4000); }
    function hideMsg(){ document.getElementById('msgOk').classList.add('d-none'); document.getElementById('msgErr').classList.add('d-none'); }
    function escapeHtml(str){ return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s])); }
  </script>

  <script src="js/scripts.js"></script>
</body>
</html>
