<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>門市打卡機</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #fff9f5 0%, #ffe8dc 50%, #ffd4c4 100%);
      min-height: 100vh;
      padding: 0;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: -50%;
      right: -20%;
      width: 800px;
      height: 800px;
      background: radial-gradient(circle, rgba(251, 185, 124, 0.15) 0%, transparent 70%);
      border-radius: 50%;
      animation: float 20s ease-in-out infinite;
      z-index: 0;
    }
    
    body::after {
      content: '';
      position: fixed;
      bottom: -30%;
      left: -10%;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(255, 90, 90, 0.12) 0%, transparent 70%);
      border-radius: 50%;
      animation: float 15s ease-in-out infinite reverse;
      z-index: 0;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-30px, -30px) scale(1.1); }
    }
    
    .wrap {
      max-width: 1300px;
      margin: 0 auto;
      padding: 40px 20px;
      position: relative;
      z-index: 1;
    }
    
    .header-section {
      background: white;
      border-radius: 36px;
      padding: 56px 48px;
      margin-bottom: 32px;
      box-shadow: 0 20px 60px rgba(251, 185, 124, 0.15), 0 0 0 1px rgba(251, 185, 124, 0.1);
      position: relative;
      overflow: hidden;
    }
    
    .header-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, #fbb97c 0%, #ff5a5a 50%, #fbb97c 100%);
      background-size: 200% 100%;
      animation: shimmer 3s linear infinite;
    }
    
    @keyframes shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }
    
    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 32px;
    }
    
    .brand-wrapper {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    
    .brand-icon {
      width: 88px;
      height: 88px;
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      color: white;
      box-shadow: 0 12px 40px rgba(255, 90, 90, 0.35);
      animation: pulse 3s ease-in-out infinite;
      position: relative;
    }
    
    .brand-icon::after {
      content: '';
      position: absolute;
      inset: -3px;
      border-radius: 26px;
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      z-index: -1;
      opacity: 0;
      animation: ringPulse 3s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    @keyframes ringPulse {
      0%, 100% { opacity: 0; transform: scale(1); }
      50% { opacity: 0.3; transform: scale(1.15); }
    }
    
    .brand {
      font-size: 52px;
      font-weight: 900;
      background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      letter-spacing: -2px;
      line-height: 1;
    }
    
    .brand-subtitle {
      font-size: 16px;
      color: #999;
      font-weight: 600;
      margin-top: 8px;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    
    .date-time {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.15), rgba(255, 90, 90, 0.1));
      padding: 20px 32px;
      border-radius: 60px;
      color: #555;
      font-size: 17px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 14px;
      border: 2px solid rgba(251, 185, 124, 0.3);
      box-shadow: 0 8px 24px rgba(251, 185, 124, 0.15);
    }
    
    .date-time i {
      color: #ff5a5a;
      font-size: 24px;
    }
    
    .panel {
      background: white;
      border-radius: 36px;
      margin-bottom: 32px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(251, 185, 124, 0.1);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .panel:hover {
      box-shadow: 0 30px 80px rgba(255, 90, 90, 0.15), 0 0 0 2px rgba(251, 185, 124, 0.2);
      transform: translateY(-6px);
    }
    
    .panel-h {
      padding: 36px 48px;
      font-weight: 800;
      font-size: 28px;
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.12), rgba(255, 90, 90, 0.08));
      border-bottom: 3px solid rgba(251, 185, 124, 0.2);
      color: #333;
      display: flex;
      align-items: center;
      gap: 18px;
    }
    
    .panel-h i {
      font-size: 32px;
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    
    .panel-b {
      padding: 48px;
    }
    
    .form-label {
      font-weight: 700;
      color: #333;
      margin-bottom: 14px;
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .form-control {
      background: #f8f9fa;
      border: 3px solid #e9ecef;
      border-radius: 20px;
      padding: 22px 28px;
      font-size: 19px;
      color: #333;
      transition: all 0.3s ease;
      font-weight: 600;
    }
    
    .form-control::placeholder {
      color: #adb5bd;
      font-weight: 500;
    }
    
    .form-control:focus {
      background: white;
      border-color: #fbb97c;
      box-shadow: 0 0 0 6px rgba(251, 185, 124, 0.15), 0 8px 24px rgba(251, 185, 124, 0.2);
      outline: none;
      color: #333;
      transform: translateY(-2px);
    }
    
    .form-text {
      color: #6c757d;
      font-size: 14px;
      margin-top: 12px;
      line-height: 1.7;
      font-weight: 500;
    }
    
    .btn-big {
      padding: 28px 40px;
      border-radius: 22px;
      font-size: 22px;
      font-weight: 800;
      border: none;
      color: white;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }
    
    .btn-big::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.4);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }
    
    .btn-big:hover::before {
      width: 500px;
      height: 500px;
    }
    
    .btn-big span,
    .btn-big i {
      position: relative;
      z-index: 1;
    }
    
    .btn-in {
      background: linear-gradient(135deg, #20c997 0%, #17a589 100%);
    }
    
    .btn-in:hover {
      box-shadow: 0 16px 50px rgba(32, 201, 151, 0.5);
      transform: translateY(-6px) scale(1.03);
    }
    
    .btn-out {
      background: linear-gradient(135deg, #4c9aff 0%, #3182e0 100%);
    }
    
    .btn-out:hover {
      box-shadow: 0 16px 50px rgba(76, 154, 255, 0.5);
      transform: translateY(-6px) scale(1.03);
    }
    
    .btn-big:active {
      transform: scale(0.96);
    }
    
    .msg-success {
      background: linear-gradient(135deg, #d4f4dd 0%, #c1f2d0 100%);
      border: 3px solid #20c997;
      color: #0a5a3e;
      padding: 24px 32px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 17px;
      animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 24px rgba(32, 201, 151, 0.2);
    }
    
    .msg-error {
      background: linear-gradient(135deg, #ffe5e5 0%, #ffd4d4 100%);
      border: 3px solid #ff5a5a;
      color: #a00;
      padding: 24px 32px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 17px;
      animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 24px rgba(255, 90, 90, 0.2);
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .info-text {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.15), rgba(255, 90, 90, 0.1));
      border: 2px solid rgba(251, 185, 124, 0.3);
      padding: 20px 28px;
      border-radius: 18px;
      color: #555;
      font-size: 15px;
      margin-top: 28px;
      font-weight: 600;
      border-left: 5px solid #fbb97c;
    }
    
    .table-responsive {
      border-radius: 20px;
      overflow: hidden;
      background: #f8f9fa;
      border: 2px solid #e9ecef;
    }
    
    .table {
      margin-bottom: 0;
      color: #333;
    }
    
    .table thead {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.15), rgba(255, 90, 90, 0.1));
    }
    
    .table thead th {
      border: none;
      padding: 24px 28px;
      font-weight: 800;
      color: #333;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 2px;
      border-bottom: 3px solid rgba(251, 185, 124, 0.3);
    }
    
    .table tbody td {
      padding: 28px;
      border-bottom: 2px solid #e9ecef;
      color: #444;
      font-size: 17px;
      font-weight: 600;
      background: white;
    }
    
    .table tbody tr {
      transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.08), rgba(255, 90, 90, 0.05)) !important;
      transform: scale(1.01);
      box-shadow: 0 4px 16px rgba(251, 185, 124, 0.15);
    }
    
    .table tbody tr:hover td {
      background: transparent;
    }
    
    .table tbody tr:last-child td {
      border-bottom: none;
    }
    
    .table code {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.2), rgba(255, 90, 90, 0.15));
      padding: 8px 16px;
      border-radius: 12px;
      font-size: 15px;
      color: #ff5a5a;
      font-weight: 800;
      border: 2px solid rgba(251, 185, 124, 0.3);
    }
    
    .badge-miss {
      background: linear-gradient(135deg, #ffe5e5, #ffd4d4);
      border: 2px solid #ff5a5a;
      color: #c00;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 16px rgba(255, 90, 90, 0.25);
    }
    
    .badge-ok {
      background: linear-gradient(135deg, #d4f4dd, #c1f2d0);
      border: 2px solid #20c997;
      color: #0a5a3e;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 16px rgba(32, 201, 151, 0.25);
    }
    
    .badge-ot {
      background: linear-gradient(135deg, #e0f0ff, #cce5ff);
      border: 2px solid #4c9aff;
      color: #0056b3;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 16px rgba(76, 154, 255, 0.25);
    }
    
    .empty-state,
    .loading-state {
      text-align: center;
      padding: 72px 20px;
      color: #999;
    }
    
    .empty-state i,
    .loading-state i {
      font-size: 72px;
      color: #ddd;
      margin-bottom: 24px;
      display: block;
    }
    
    .empty-state div,
    .loading-state div {
      font-size: 18px;
      font-weight: 600;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 56px;
      height: 56px;
      border: 5px solid #f0f0f0;
      border-top: 5px solid #fbb97c;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 24px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .footer-text {
      text-align: center;
      color: #999;
      font-size: 15px;
      margin-top: 56px;
      padding: 28px;
      font-weight: 600;
    }
    
    .mode-switch {
      text-align: center;
      margin-top: 24px;
    }
    
    .mode-switch a {
      color: #ff5a5a;
      text-decoration: none;
      font-weight: 700;
      font-size: 16px;
      padding: 12px 24px;
      border: 2px solid #ff5a5a;
      border-radius: 12px;
      transition: all 0.3s ease;
      display: inline-block;
    }
    
    .mode-switch a:hover {
      background: #ff5a5a;
      color: white;
      transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
      .wrap {
        padding: 24px 16px;
      }
      
      .header-section {
        padding: 36px 28px;
      }
      
      .brand {
        font-size: 36px;
      }
      
      .brand-icon {
        width: 64px;
        height: 64px;
        font-size: 32px;
      }
      
      .panel-h {
        padding: 28px 32px;
        font-size: 22px;
      }
      
      .panel-b {
        padding: 32px 28px;
      }
      
      .btn-big {
        padding: 22px 32px;
        font-size: 18px;
      }
      
      .table thead th,
      .table tbody td {
        padding: 18px 20px;
        font-size: 15px;
      }
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header-section">
    <div class="header-content">
      <div class="brand-wrapper">
        <div class="brand-icon">
          <i class="fa-solid fa-fingerprint"></i>
        </div>
        <div>
          <div class="brand">門市打卡機</div>
          <div class="brand-subtitle">Attendance System</div>
        </div>
      </div>
      <div class="date-time">
        <i class="fa-regular fa-calendar-days"></i>
        <span id="now"></span>
      </div>
    </div>
  </div>

  <div class="card panel">
    <div class="panel-h">
      <i class="fa-solid fa-bolt"></i>
      快速打卡
    </div>
    <div class="panel-b">
      <div class="row g-4 align-items-end">
        <div class="col-lg-5">
          <label class="form-label">員工編號</label>
          <input type="text" class="form-control" id="empCode" placeholder="輸入員工編號(例如 1002)">
          <div class="form-text">
            <i class="fa-solid fa-circle-info me-1"></i>
            此編號預設使用「員工基本資料.id」。若你要用其它欄位,改 config 的 EMP_CODE_COL。
          </div>
        </div>
        <div class="col-lg-7">
          <div class="d-flex gap-4">
            <button class="btn btn-big btn-in flex-fill" id="btnIn">
              <i class="fa-solid fa-door-open me-2"></i>
              <span>上班</span>
            </button>
            <button class="btn btn-big btn-out flex-fill" id="btnOut">
              <i class="fa-solid fa-door-closed me-2"></i>
              <span>下班</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="info-text">
        <i class="fa-solid fa-lightbulb me-2"></i>
        成功打卡後,下方「今日打卡紀錄」會自動刷新
      </div>
      
      <div class="mode-switch">
        <a href="face_clock.php">
          <i class="fa-solid fa-face-smile me-2"></i>
          切換到人臉識別模式
        </a>
      </div>
      
      <div class="mt-4" id="msg"></div>
    </div>
  </div>

  <div class="card panel">
    <div class="panel-h">
      <i class="fa-solid fa-clock-rotate-left"></i>
      今日打卡紀錄
    </div>
    <div class="panel-b">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
          <tr>
            <th style="width:120px">時間</th>
            <th>員工姓名</th>
            <th>員工編號</th>
            <th style="width:90px">類型</th>
            <th style="width:110px">狀態</th>
          </tr>
          </thead>
          <tbody id="listBody">
            <tr>
              <td colspan="5">
                <div class="loading-state">
                  <div class="loading-spinner"></div>
                  <div>載入中...</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="footer-text">
    <i class="fa-solid fa-shield-halved me-2"></i>
    門市打卡系統 - 安全可靠
  </div>
</div>

<script>
const API_BASE   = '/lamian-ukn/api';
const API_SAVE   = API_BASE + '/clock_save.php';
const API_LIST   = API_BASE + '/clock_list.php';

const empInput = document.getElementById('empCode');
const msg = document.getElementById('msg');
const listBody = document.getElementById('listBody');

document.getElementById('now').textContent =
  new Date().toLocaleString('zh-TW',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});

function badge(st){
  if(st==='缺卡') return '<span class="badge badge-miss">缺卡</span>';
  if(st==='加班') return '<span class="badge badge-ot">加班</span>';
  return '<span class="badge badge-ok">正常</span>';
}

async function loadToday(){
  const d = new Date().toISOString().slice(0,10);
  const url = `${API_LIST}?start_date=${d}&end_date=${d}`;
  
  console.log('Loading today records from:', url);
  
  try{
    const r = await fetch(url, {credentials:'include'});
    
    if(!r.ok) {
      throw new Error(`HTTP ${r.status}: ${r.statusText}`);
    }
    
    const rows = await r.json();
    console.log('Received data:', rows);
    
    if(!Array.isArray(rows)) {
      throw new Error('回應格式錯誤:不是陣列');
    }

    if(rows.length===0){
      listBody.innerHTML = `<tr><td colspan="5">
        <div class="empty-state">
          <i class="fa-regular fa-calendar-xmark"></i>
          <div>今天尚無打卡紀錄</div>
        </div>
      </td></tr>`;
      return;
    }
    
    listBody.innerHTML = rows.map(r=>{
      const type = r.clock_out ? '下班' : '上班';
      const time = r.clock_out || r.clock_in || '';
      const tm = (time||'').slice(0,5);
      const code = r.employee_id || r.user_id || '';
      return `<tr>
        <td><strong>${tm}</strong></td>
        <td>${r.emp_name || '—'}</td>
        <td><code>${code}</code></td>
        <td><strong>${type}</strong></td>
        <td>${badge(r.status)}</td>
      </tr>`;
    }).join('');
    
    console.log('Table updated with', rows.length, 'records');
    
  }catch(e){
    console.error('Load error:', e);
    listBody.innerHTML = `<tr><td colspan="5">
      <div class="empty-state text-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>載入失敗:${e.message}</div>
        <div style="font-size:14px;margin-top:12px;color:#666;">請檢查 Console 查看詳細錯誤</div>
      </div>
    </td></tr>`;
  }
}

function showMsg(ok, text){
  msg.className = ok ? 'msg-success' : 'msg-error';
  msg.innerHTML = `<i class="fa-solid fa-${ok ? 'circle-check' : 'circle-xmark'} me-2"></i>${text}`;
  setTimeout(()=> msg.innerHTML='', 3000);
}

async function punch(action){
  const emp_code = (empInput.value||'').trim();
  if(!emp_code){ 
    empInput.focus(); 
    return showMsg(false,'請輸入員工編號'); 
  }
  
  try{
    console.log('Punching:', action, 'for employee:', emp_code);
    
    const r = await fetch(API_SAVE, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ emp_code, action }),
      credentials:'include'
    });
    
    console.log('Punch response status:', r.status);
    
    const resp = await r.json();
    console.log('Punch response data:', resp);
    
    if(!r.ok || resp.error) {
      throw new Error(resp.error || resp.detail || ('HTTP '+r.status));
    }
    
    showMsg(true, `${resp.message}:${resp.emp?.name || emp_code}`);
    empInput.value='';
    
    // 等待一下讓資料庫有時間寫入
    setTimeout(() => loadToday(), 300);
    
  }catch(err){
    console.error('Punch error:', err);
    showMsg(false, '打卡失敗:'+err.message);
  }
}

document.getElementById('btnIn').addEventListener('click', ()=>punch('in'));
document.getElementById('btnOut').addEventListener('click', ()=>punch('out'));
empInput.addEventListener('keydown', e=>{ if(e.key==='Enter') punch('in'); });

// 頁面載入時立即載入今日記錄
loadToday();

// 每30秒自動刷新一次
setInterval(loadToday, 30000);
</script>
</body>
</html>