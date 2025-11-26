<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>門市打卡機</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <style>
    :root{ --g1: linear-gradient(135deg,#fbb97ce4 0%, #ff0000cb 100%); --card:0 15px 35px rgba(0,0,0,.08); --radius:20px;}
    body{font-family:system-ui,'Segoe UI','PingFang TC','Noto Sans TC',sans-serif}
    .wrap{max-width:900px;margin:32px auto;padding:0 16px}
    .brand{font-size:28px;font-weight:800;background:var(--g1);-webkit-background-clip:text;background-clip:text;color:transparent}
    .panel{border:none;border-radius:var(--radius);box-shadow:var(--card)}
    .panel-h{padding:16px 20px;font-weight:700;background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(255,255,255,.85));border-bottom:1px solid rgba(0,0,0,.05);border-radius:var(--radius) var(--radius) 0 0}
    .panel-b{padding:20px}
    .btn-big{padding:18px 24px;border-radius:16px;font-size:20px;font-weight:800;border:none;color:#fff}
    .btn-in{background:linear-gradient(135deg,#2bb673,#169e59)}
    .btn-out{background:linear-gradient(135deg,#5d8dee,#4a79da)}
    .badge-miss{background:#f8d7da;color:#842029}
    .badge-ok{background:#d1e7dd;color:#0f5132}
    .badge-ot{background:#cfe2ff;color:#084298}
  </style>
</head>
<body>
<div class="wrap">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="brand">門市打卡機</div>
    <div class="text-muted"><i class="fa-regular fa-calendar-days me-2"></i><span id="now"></span></div>
  </div>

  <div class="card panel mb-4">
    <div class="panel-h">打卡</div>
    <div class="panel-b">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">員工編號</label>
          <input type="text" class="form-control form-control-lg" id="empCode" placeholder="請輸入員工編號（預設用『員工基本資料.id』）">
          <div class="form-text">此編號預設使用「員工基本資料.id」。若要改用其它欄位，請修改 /api/config.php 的 EMP_CODE_COL。</div>
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-3">
            <button class="btn btn-big btn-in flex-fill" id="btnIn"><i class="fa-solid fa-door-open me-2"></i>上班</button>
            <button class="btn btn-big btn-out flex-fill" id="btnOut"><i class="fa-solid fa-door-closed me-2"></i>下班</button>
          </div>
        </div>
      </div>
      <div class="mt-3 small text-muted">※ 成功打卡後，下方「今日打卡紀錄」會自動刷新。</div>
      <div class="mt-3" id="msg"></div>
    </div>
  </div>

  <div class="card panel">
    <div class="panel-h"><i class="fa-solid fa-table-list me-2"></i>今日打卡紀錄</div>
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
          <tbody id="listBody"><tr><td colspan="5" class="text-center text-muted py-4">載入中…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="text-center text-muted small mt-3">© 打卡系統</div>
</div>

<script>
const API_BASE = '/lamian-ukn/api';
const API_SAVE = API_BASE + '/clock_save.php';
const API_LIST = API_BASE + '/clock_list.php';

const empInput = document.getElementById('empCode');
const msg = document.getElementById('msg');
const listBody = document.getElementById('listBody');

document.getElementById('now').textContent =
  new Date().toLocaleString('zh-TW',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});

function badge(st){
  if(st==='缺卡') return '<span class="badge badge-miss rounded-pill px-3 py-2">缺卡</span>';
  if(st==='加班') return '<span class="badge badge-ot rounded-pill px-3 py-2">加班</span>';
  return '<span class="badge badge-ok rounded-pill px-3 py-2">正常</span>';
}

function showMsg(ok, text){
  msg.className = ok ? 'text-success fw-bold' : 'text-danger fw-bold';
  msg.textContent = text;
  setTimeout(()=> msg.textContent='', 4000);
}

async function loadToday(){
  const d = new Date().toISOString().slice(0,10);
  const url = `${API_LIST}?start_date=${d}&end_date=${d}`;
  try{
    const r = await fetch(url, {credentials:'include'});
    const rows = await r.json();
    if(!Array.isArray(rows)){ throw new Error('格式錯誤'); }
    if(rows.length===0){
      listBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">今天尚無紀錄</td></tr>';
      return;
    }
    listBody.innerHTML = rows.map(r=>{
      const type = r.clock_out ? '下班' : '上班';
      const time = r.clock_out || r.clock_in || '';
      const tm = (time||'').slice(0,5);
      const code = r.employee_id ?? r.user_id ?? '';
      return `<tr>
        <td>${tm}</td>
        <td>${r.emp_name ?? '—'}</td>
        <td>${code}</td>
        <td>${type}</td>
        <td>${badge(r.status)}</td>
      </tr>`;
    }).join('');
  }catch(e){
    console.error(e);
    listBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">載入失敗</td></tr>';
  }
}

async function punch(action){
  const emp_code = (empInput.value||'').trim();
  if(!emp_code){ empInput.focus(); return showMsg(false,'請輸入員工編號'); }
  try{
    const r = await fetch(API_SAVE, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ emp_code, action }), // 'in' 或 'out'
      credentials:'include'
    });
    // 解析回應，優先顯示 detail（例如 SQL 錯誤）
    const json = await r.json().catch(async ()=>{
      const text = await r.text();
      throw new Error('伺服器回應不是 JSON：'+text.slice(0,200));
    });
    if(!r.ok || json.error){
      throw new Error(json.detail || json.error || ('HTTP '+r.status));
    }
    showMsg(true, json.message + '：' + (json.emp?.name || emp_code));
    empInput.value='';
    await loadToday();
  }catch(err){
    console.error(err);
    showMsg(false, '打卡失敗：' + err.message);
  }
}

document.getElementById('btnIn').addEventListener('click', ()=>punch('in'));
document.getElementById('btnOut').addEventListener('click', ()=>punch('out'));
empInput.addEventListener('keydown', e=>{ if(e.key==='Enter') punch('in'); });

loadToday();
</script>
</body>
</html>
