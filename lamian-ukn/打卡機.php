<?php
// 假設你在伺服器端知道這台裝置的 token（例如從 session、或設定檔）
$DEVICE_TOKEN = $_SESSION['device_token'] ?? ''; // 或從 config 讀取
?>
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
      transition: all .25s cubic-bezier(.4, 0, .2, 1);
    }
    
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      /* ✅ 改成跟 index 一樣的淡藍＋紫漸層背景 */
      background:
        radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.24), transparent 55%),
        radial-gradient(circle at 100% 0%, rgba(222, 114, 244, 0.24), transparent 55%),
        linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
      min-height: 100vh;
      padding: 0;
      position: relative;
      overflow-x: hidden;
      color: #0f172a;
    }
    
    /* 背景浮動光圈改成藍紫系 */
    body::before {
      content: '';
      position: fixed;
      top: -50%;
      right: -20%;
      width: 800px;
      height: 800px;
      background: radial-gradient(circle, rgba(56, 189, 248, 0.16) 0%, transparent 70%);
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
      background: radial-gradient(circle, rgba(129, 140, 248, 0.18) 0%, transparent 70%);
      border-radius: 50%;
      animation: float 15s ease-in-out infinite reverse;
      z-index: 0;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-30px, -30px) scale(1.06); }
    }
    
    .wrap {
      max-width: 1300px;
      margin: 0 auto;
      padding: 40px 20px;
      position: relative;
      z-index: 1;
    }
    
    .header-section {
      background: rgba(255, 255, 255, 0.96);
      border-radius: 36px;
      padding: 56px 48px;
      margin-bottom: 32px;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(226, 232, 240, 0.9);
    }
    
    .header-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      /* ✅ 頂部細線改成 index 的藍紫漸層 */
      background: linear-gradient(90deg, #1e3a8a 0%, #3658ff 40%, #7b6dff 100%);
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
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      color: white;
      box-shadow: 0 16px 42px rgba(59, 130, 246, 0.5);
      animation: pulse 3s ease-in-out infinite;
      position: relative;
    }
    
    .brand-icon::after {
      content: '';
      position: absolute;
      inset: -3px;
      border-radius: 26px;
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
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
      background: linear-gradient(135deg, #1e3a8a 0%, #4f8bff 45%, #7b6dff 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      letter-spacing: -2px;
      line-height: 1;
    }
    
    .brand-subtitle {
      font-size: 16px;
      color: #64748b;
      font-weight: 600;
      margin-top: 8px;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    
    .date-time {
      background: linear-gradient(135deg, rgba(191, 219, 254, 0.4), rgba(221, 214, 254, 0.4));
      padding: 20px 32px;
      border-radius: 60px;
      color: #1e293b;
      font-size: 17px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 14px;
      border: 2px solid rgba(148, 163, 184, 0.5);
      box-shadow: 0 10px 26px rgba(148, 163, 184, 0.4);
    }
    
    .date-time i {
      color: #2563eb;
      font-size: 24px;
    }
    
    .panel {
      background: rgba(255, 255, 255, 0.96);
      border-radius: 36px;
      margin-bottom: 32px;
      overflow: hidden;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
      border: 1px solid rgba(226, 232, 240, 0.95);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .panel:hover {
      box-shadow: 0 22px 60px rgba(37, 99, 235, 0.35);
      transform: translateY(-4px);
      border-color: rgba(191, 219, 254, 0.9);
    }
    
    .panel-h {
      padding: 36px 48px;
      font-weight: 800;
      font-size: 28px;
      background: linear-gradient(135deg, rgba(239, 246, 255, 0.96), rgba(224, 231, 255, 0.96));
      border-bottom: 2px solid rgba(203, 213, 225, 0.9);
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 18px;
    }
    
    .panel-h i {
      font-size: 32px;
      background: linear-gradient(135deg, #1e3a8a, #4f8bff, #7b6dff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    
    .panel-b {
      padding: 48px;
    }
    
    .form-label {
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 14px;
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .form-control {
      background: #f9fafb;
      border: 2px solid #e2e8f0;
      border-radius: 20px;
      padding: 22px 28px;
      font-size: 19px;
      color: #111827;
      transition: all 0.3s ease;
      font-weight: 600;
    }
    
    .form-control::placeholder {
      color: #94a3b8;
      font-weight: 500;
    }
    
    .form-control:focus {
      background: #ffffff;
      border-color: #2563eb;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25);
      outline: none;
      color: #0f172a;
      transform: translateY(-2px);
    }
    
    .form-text {
      color: #64748b;
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
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.4);
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
      width: 400px;
      height: 400px;
    }
    
    .btn-big:hover {
      transform: translateY(-4px);
      box-shadow: 0 22px 60px rgba(15, 23, 42, 0.55);
    }
    
    .btn-big:active {
      transform: translateY(-1px);
    }
    
    /* ✅ 上班 / 下班按鈕改成藍＋綠，但主色調還是跟 index 一致 */
    .btn-in {
      background: linear-gradient(135deg, #22c55e, #16a34a);
    }
    
    .btn-out {
      background: linear-gradient(135deg, #2563eb, #4f8bff);
    }
    
    .info-text {
      margin-top: 28px;
      padding: 20px 26px;
      background: linear-gradient(135deg, rgba(191, 219, 254, 0.4), rgba(221, 214, 254, 0.35));
      border-left: 4px solid #4f8bff;
      border-radius: 14px;
      font-size: 15px;
      color: #1e293b;
      font-weight: 600;
    }
    
    .info-text i {
      color: #2563eb;
      font-size: 18px;
    }
    
    .mode-switch {
      margin-top: 24px;
      text-align: center;
    }
    
    /* ✅ 切換模式按鈕改成藍紫膠囊風，跟 index sidebar / 按鈕同系 */
    .mode-switch a {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 32px;
      background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(224, 231, 255, 0.96));
      border: 2px solid rgba(191, 219, 254, 0.9);
      border-radius: 50px;
      color: #2563eb;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      box-shadow: 0 10px 28px rgba(148, 163, 184, 0.35);
    }
    
    .mode-switch a:hover {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 16px 40px rgba(59, 130, 246, 0.6);
    }
    
    .msg-success, .msg-error {
      padding: 20px 26px;
      border-radius: 16px;
      font-weight: 700;
      font-size: 16px;
      display: flex;
      align-items: center;
      animation: slideIn 0.4s ease;
      margin-top: 18px;
    }
    
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .msg-success {
      background: linear-gradient(135deg, #dcfce7, #bbf7d0);
      color: #166534;
      border-left: 5px solid #22c55e;
    }
    
    .msg-error {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: #b91c1c;
      border-left: 5px solid #ef4444;
    }
    
    .table-responsive {
      border-radius: 16px;
      overflow: hidden;
    }
    
    .table {
      margin-bottom: 0;
    }
    
    /* ✅ 表頭改成淡藍漸層，跟 index 卡片風格接近 */
    .table thead {
      background: linear-gradient(135deg, rgba(191, 219, 254, 0.9), rgba(221, 214, 254, 0.9));
    }
    
    .table thead th {
      border: none;
      color: #0f172a;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 14px;
      padding: 24px 28px;
    }
    
    .table tbody td {
      border-color: rgba(226, 232, 240, 0.9);
      padding: 24px 28px;
      font-size: 16px;
      color: #1f2933;
      font-weight: 600;
      background-color: rgba(255, 255, 255, 0.96);
    }
    
    .table tbody tr {
      transition: all 0.25s ease;
    }
    
    .table tbody tr:hover {
      background: linear-gradient(135deg, rgba(239, 246, 255, 0.85), rgba(224, 231, 255, 0.85));
      transform: scale(1.01);
    }
    
    .table code {
      background: rgba(191, 219, 254, 0.6);
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      color: #1e3a8a;
    }
    
    .badge {
      padding: 10px 22px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* ✅ 狀態標籤：用 index 風格的柔和綠 / 紅 / 藍 */
    .badge-ok {
      background: linear-gradient(135deg, #dcfce7, #bbf7d0);
      color: #166534;
    }
    
    .badge-miss {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: #b91c1c;
    }
    
    .badge-ot {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1d4ed8;
    }
    
    .loading-state, .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #94a3b8;
    }
    
    .loading-state i, .empty-state i {
      font-size: 56px;
      margin-bottom: 20px;
      color: #cbd5f5;
    }
    
    .loading-state div, .empty-state div {
      font-size: 18px;
      font-weight: 600;
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      margin: 0 auto 20px;
      border: 5px solid rgba(191, 219, 254, 0.6);
      border-top-color: #2563eb;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .footer-text {
      text-align: center;
      color: #64748b;
      font-size: 15px;
      font-weight: 600;
      margin-top: 32px;
      padding: 24px;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 50px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 10px 28px rgba(148, 163, 184, 0.28);
    }
    
    .footer-text i {
      color: #4f8bff;
    }
    
    @media (max-width: 991px) {
      .header-section {
        padding: 40px 32px;
      }
      
      .brand {
        font-size: 40px;
      }
      
      .brand-icon {
        width: 70px;
        height: 70px;
        font-size: 32px;
      }
      
      .panel-h {
        font-size: 24px;
        padding: 28px 32px;
      }
      
      .panel-b {
        padding: 32px;
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
  <div class="row g-4">
    <div class="col-lg-5">
      <label class="form-label">員工編號</label>
      <input type="text" class="form-control" id="empCode" placeholder="輸入員工編號(例如 1002)">
      <div class="form-text">
        <i class="fa-solid fa-circle-info me-1"></i>
        此編號預設使用「員工基本資料.id」。若你要用其它欄位,改 config 的 EMP_CODE_COL。
      </div>
    </div>

    <!-- ⭐ 只調整這一欄，讓按鈕在欄位裡垂直置中 -->
    <div class="col-lg-7 d-flex align-items-center">
      <div class="d-flex gap-4 w-100">
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
        <a href="face_clock_with_liveness.php">
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
  // 全域常數：由伺服器注入
  const DEVICE_TOKEN = <?= json_encode($DEVICE_TOKEN) ?>;
</script>

<script>
// ✅ 使用整合後的 API
const API_BASE = '/lamian-ukn/api/clock_api.php';

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
  const url = `${API_BASE}?action=list&start_date=${d}&end_date=${d}`;
  
  try {
    const r = await fetch(url, {credentials:'include'});
    if(!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);

    const rows = await r.json();
    if(!Array.isArray(rows)) throw new Error('回應格式錯誤:不是陣列');

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

  } catch(e) {
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

// ✅ 通用打卡函數（支援員工編號 & 臉部辨識）
async function punch(emp_code, action){
  emp_code = (emp_code || '').trim();
  if(!emp_code) return showMsg(false,'員工編號缺失');

  try {
    console.log('Punching:', action, 'for employee:', emp_code);

    // 取 DEVICE_TOKEN（先使用伺服器注入的全域常數，沒有再從 localStorage）
    const token = (typeof DEVICE_TOKEN !== 'undefined' && DEVICE_TOKEN) 
                  ? DEVICE_TOKEN 
                  : (localStorage.getItem('device_token') || '');
    if(!token) throw new Error('裝置 token 必填');

    // ✅ 使用整合 API 的 punch action
    const r = await fetch(`${API_BASE}?action=punch`, { 
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-Device-Token': token
      },
      body: JSON.stringify({ emp_code, action }),
      credentials: 'include'
    });

    let resp;
    try { resp = await r.json(); } 
    catch(e) { throw new Error('非 JSON 回傳: '+await r.text()); }

    console.log('Punch response:', resp);
    if(!r.ok || resp.error) throw new Error(resp.error || resp.detail || ('HTTP '+r.status));

    showMsg(true, `${resp.message || '打卡成功'}: ${resp.emp?.name || emp_code}`);
    empInput.value='';

    setTimeout(()=> loadToday(), 300);

  } catch(err){
    console.error('Punch error:', err);
    showMsg(false, '打卡失敗: '+err.message);
  }
}

// 綁定手動輸入打卡按鈕
document.getElementById('btnIn').addEventListener('click', ()=>punch(empInput.value,'in'));
document.getElementById('btnOut').addEventListener('click', ()=>punch(empInput.value,'out'));

// Enter 鍵快速打卡：Shift+Enter 下班，Enter 上班
empInput.addEventListener('keydown', e=>{
  if(e.key==='Enter') {
    e.preventDefault();
    punch(empInput.value, e.shiftKey ? 'out' : 'in');
  }
});

// ✅ 臉部辨識打卡範例（只要有 emp_code 就可呼叫 punch）
async function punchFace(emp_code, action){
  // emp_code 從臉部辨識結果取得
  await punch(emp_code, action);
}

// 初次載入今日打卡
loadToday();

// 每30秒自動刷新
setInterval(loadToday, 30000);
</script>

</body>
</html>
