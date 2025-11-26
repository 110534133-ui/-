<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>人臉資料管理</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #fff9f5 0%, #ffe8dc 50%, #ffd4c4 100%);
      min-height: 100vh;
      padding: 40px 20px;
    }
    
    .wrap {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .panel {
      background: white;
      border-radius: 36px;
      margin-bottom: 32px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
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
      justify-content: space-between;
    }
    
    .panel-h-left {
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
    
    .avatar-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid rgba(251, 185, 124, 0.3);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .avatar-placeholder {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 800;
      font-size: 20px;
      border: 3px solid rgba(251, 185, 124, 0.3);
    }
    
    .table tbody td {
      padding: 28px;
      border-bottom: 2px solid #e9ecef;
      color: #444;
      font-size: 16px;
      font-weight: 600;
      background: white;
      vertical-align: middle;
    }
    
    .table tbody tr:hover {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.08), rgba(255, 90, 90, 0.05)) !important;
    }
    
    .table tbody tr:hover td {
      background: transparent;
    }
    
    .btn-delete {
      padding: 12px 24px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 700;
      border: none;
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      transition: all 0.3s ease;
    }
    
    .btn-delete:hover {
      box-shadow: 0 8px 24px rgba(220, 53, 69, 0.4);
      transform: translateY(-2px);
    }
    
    .btn-refresh {
      padding: 14px 28px;
      border-radius: 16px;
      font-size: 16px;
      font-weight: 700;
      border: 2px solid #fbb97c;
      background: white;
      color: #ff5a5a;
      transition: all 0.3s ease;
    }
    
    .btn-refresh:hover {
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      color: white;
      transform: translateY(-2px);
    }
    
    .badge-registered {
      background: linear-gradient(135deg, #d4f4dd, #c1f2d0);
      border: 2px solid #20c997;
      color: #0a5a3e;
      padding: 8px 16px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 12px;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 32px;
    }
    
    .stat-card {
      background: white;
      border-radius: 24px;
      padding: 32px;
      text-align: center;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    }
    
    .stat-card i {
      font-size: 48px;
      background: linear-gradient(135deg, #fbb97c, #ff5a5a);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 16px;
      display: block;
    }
    
    .stat-card .stat-number {
      font-size: 36px;
      font-weight: 900;
      color: #333;
      margin-bottom: 8px;
    }
    
    .stat-card .stat-label {
      font-size: 14px;
      color: #666;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .empty-state {
      text-align: center;
      padding: 72px 20px;
      color: #999;
    }
    
    .empty-state i {
      font-size: 72px;
      color: #ddd;
      margin-bottom: 24px;
      display: block;
    }
    
    .modal-content {
      border-radius: 24px;
      border: none;
    }
    
    .modal-header {
      background: linear-gradient(135deg, rgba(251, 185, 124, 0.15), rgba(255, 90, 90, 0.1));
      border-bottom: 2px solid rgba(251, 185, 124, 0.3);
      border-radius: 24px 24px 0 0;
      padding: 24px 32px;
    }
    
    .modal-title {
      font-weight: 800;
      color: #333;
    }
    
    .modal-body {
      padding: 32px;
      font-size: 16px;
      color: #555;
      line-height: 1.7;
    }
    
    .modal-footer {
      border-top: 2px solid #e9ecef;
      padding: 24px 32px;
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="text-center mb-4">
    <h1 style="font-size: 48px; font-weight: 900; background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">
      <i class="fa-solid fa-user-gear me-3"></i>人臉資料管理
    </h1>
    <p class="text-muted" style="font-size: 18px; font-weight: 600; margin-top: 12px;">
      管理員工的人臉註冊資料
    </p>
  </div>

  <div class="stats-grid" id="statsGrid">
    <div class="stat-card">
      <i class="fa-solid fa-users"></i>
      <div class="stat-number" id="totalEmployees">-</div>
      <div class="stat-label">總員工數</div>
    </div>
    
    <div class="stat-card">
      <i class="fa-solid fa-user-check"></i>
      <div class="stat-number" id="registeredCount">-</div>
      <div class="stat-label">已註冊人臉</div>
    </div>
    
    <div class="stat-card">
      <i class="fa-solid fa-percentage"></i>
      <div class="stat-number" id="registrationRate">-</div>
      <div class="stat-label">註冊率</div>
    </div>
  </div>

  <div class="card panel">
    <div class="panel-h">
      <div class="panel-h-left">
        <i class="fa-solid fa-table-list"></i>
        <span>人臉註冊清單</span>
      </div>
      <button class="btn btn-refresh" onclick="loadFaceData()">
        <i class="fa-solid fa-rotate-right me-2"></i>
        重新整理
      </button>
    </div>
    <div class="panel-b">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width:80px">頭像</th>
              <th style="width:120px">員工編號</th>
              <th>姓名</th>
              <th>職位</th>
              <th style="width:180px">註冊時間</th>
              <th style="width:180px">更新時間</th>
              <th style="width:100px">狀態</th>
              <th style="width:120px">操作</th>
            </tr>
          </thead>
          <tbody id="faceDataBody">
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                  <div>載入中...</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="text-center">
    <a href="face_clock.php" class="text-muted" style="text-decoration: none; font-weight: 600;">
      <i class="fa-solid fa-arrow-left me-2"></i>返回打卡頁面
    </a>
  </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-triangle-exclamation me-2"></i>
          確認刪除
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>
          <strong>確定要刪除此員工的人臉資料嗎?</strong>
        </p>
        <p>
          員工編號: <strong id="deleteEmpCode"></strong><br>
          姓名: <strong id="deleteEmpName"></strong>
        </p>
        <p class="text-danger mb-0">
          <i class="fa-solid fa-exclamation-circle me-2"></i>
          此操作無法撤銷!員工需要重新註冊才能使用人臉打卡。
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        <button type="button" class="btn btn-delete" onclick="confirmDelete()">
          <i class="fa-solid fa-trash me-2"></i>確認刪除
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = '/lamian-ukn/api';
let deleteUserId = null;
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

// 載入人臉資料
async function loadFaceData() {
  const tbody = document.getElementById('faceDataBody');
  tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>載入中...</div></div></td></tr>';
  
  try {
    const response = await fetch(`${API_BASE}/face_list.php`, {
      credentials: 'include'
    });
    
    const data = await response.json();
    
    if (!data.success || !Array.isArray(data.face_data)) {
      throw new Error(data.message || '載入失敗');
    }
    
    // 更新統計資料
    updateStats(data.stats);
    
    if (data.face_data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fa-regular fa-face-frown"></i><div>目前沒有已註冊的人臉資料</div></div></td></tr>';
      return;
    }
    
    tbody.innerHTML = data.face_data.map(item => {
      // 頭像處理
      let avatarHtml = '';
      if (item.avatar_url && item.avatar_url.trim() !== '') {
        avatarHtml = `<img src="${item.avatar_url}" alt="${item.emp_name}" class="avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                      <div class="avatar-placeholder" style="display:none;">${(item.emp_name || '?').charAt(0)}</div>`;
      } else {
        avatarHtml = `<div class="avatar-placeholder">${(item.emp_name || '?').charAt(0)}</div>`;
      }
      
      return `
      <tr>
        <td>${avatarHtml}</td>
        <td><code style="background: linear-gradient(135deg, rgba(251, 185, 124, 0.2), rgba(255, 90, 90, 0.15)); padding: 8px 16px; border-radius: 12px; font-weight: 800; color: #ff5a5a;">${item.user_id}</code></td>
        <td><strong>${item.emp_name || '—'}</strong></td>
        <td>${item.emp_position || '—'}</td>
        <td>${formatDateTime(item.created_at)}</td>
        <td>${formatDateTime(item.updated_at)}</td>
        <td><span class="badge-registered">已註冊</span></td>
        <td>
          <button class="btn btn-delete btn-sm" onclick="showDeleteModal('${item.user_id}', '${item.emp_name}')">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
    }).join('');
    
  } catch (err) {
    console.error('Load error:', err);
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state text-danger"><i class="fa-solid fa-triangle-exclamation"></i><div>載入失敗: ${err.message}</div></div></td></tr>`;
  }
}

// 更新統計資料
function updateStats(stats) {
  document.getElementById('totalEmployees').textContent = stats.total_employees || 0;
  document.getElementById('registeredCount').textContent = stats.registered_count || 0;
  document.getElementById('registrationRate').textContent = stats.registration_rate || '0%';
}

// 格式化日期時間
function formatDateTime(dt) {
  if (!dt) return '—';
  const date = new Date(dt);
  return date.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// 顯示刪除確認 Modal
function showDeleteModal(userId, empName) {
  deleteUserId = userId;
  document.getElementById('deleteEmpCode').textContent = userId;
  document.getElementById('deleteEmpName').textContent = empName;
  deleteModal.show();
}

// 確認刪除
async function confirmDelete() {
  if (!deleteUserId) return;
  
  try {
    const response = await fetch(`${API_BASE}/face_delete.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: deleteUserId }),
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if (!result.success) {
      throw new Error(result.message || '刪除失敗');
    }
    
    alert('✓ 人臉資料已刪除');
    deleteModal.hide();
    deleteUserId = null;
    await loadFaceData();
    
  } catch (err) {
    console.error('Delete error:', err);
    alert('✗ 刪除失敗: ' + err.message);
  }
}

// 頁面載入時執行
loadFaceData();
</script>
</body>
</html>
