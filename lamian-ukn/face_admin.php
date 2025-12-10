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

  :root {
    --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
    --text-main: #0f172a;
    --text-subtle: #64748b;

    --card-bg: rgba(255, 255, 255, 0.96);
    --card-radius: 22px;

    --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.10);
    --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

    --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);
  }

  * {
    box-sizing: border-box;
    transition: var(--transition-main);
  }

  body {
    min-height: 100vh;
    padding: 40px 16px;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--text-main);
    background:
      radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.22), transparent 55%),
      radial-gradient(circle at 100% 0%, rgba(222, 114, 244, 0.20), transparent 55%),
      var(--bg-gradient);
  }

  .wrap {
    max-width: 1200px;
    margin: 0 auto;
  }

  /* 頁面抬頭下方小字 */
  .wrap > .text-center p.text-muted {
    color: var(--text-subtle) !important;
  }

  /* 統計卡片區（延續 index 的 stats-card 感覺） */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 28px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: var(--card-bg);
    border-radius: 26px;
    padding: 24px 26px;
    text-align: left;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(226, 232, 240, 0.9);
    position: relative;
    overflow: hidden;
  }

  .stat-card::after {
    content: "";
    position: absolute;
    right: -40px;
    bottom: -60px;
    width: 220px;
    height: 150px;
    border-radius: 999px;
    background: radial-gradient(circle at 20% 0, rgba(148, 163, 184, 0.22), transparent 65%);
    opacity: 0.9;
  }

  .stat-card i {
    font-size: 32px;
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    background: rgba(59, 130, 246, 0.12);
    color: #2563eb;
  }

  .stat-card .stat-number {
    font-size: 1.9rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 4px;
  }

  .stat-card .stat-label {
    font-size: 0.82rem;
    color: var(--text-subtle);
    letter-spacing: .05em;
    text-transform: uppercase;
  }

  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
  }

  /* 主列表卡片外框（panel） */
  .panel {
    background: var(--card-bg);
    border-radius: 28px;
    margin-bottom: 32px;
    overflow: hidden;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(226, 232, 240, 0.95);
  }

  .panel-h {
    padding: 20px 28px;
    font-weight: 700;
    font-size: 1rem;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(239, 246, 255, 0.96));
    border-bottom: 1px solid rgba(226, 232, 240, 0.95);
    color: var(--text-main);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .panel-h-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .panel-h-left i {
    font-size: 1.25rem;
    color: #2563eb;
  }

  .panel-b {
    padding: 22px 24px 26px;
  }

  /* 重新整理按鈕：藍色描邊膠囊 */
  .btn-refresh {
    padding: 8px 20px;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 1.5px solid #3b82f6;
    background: #ffffff;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-refresh:hover {
    background: linear-gradient(135deg, #3b82f6, #4f46e5);
    color: #ffffff;
    box-shadow: 0 10px 26px rgba(37, 99, 235, 0.4);
    transform: translateY(-1px);
  }

  /* 表格樣式：淡藍 header + 乾淨的列 */
  .table-responsive {
    border-radius: 22px;
    overflow: hidden;
    background: #f9fafb;
    border: 1px solid rgba(226, 232, 240, 0.95);
  }

  .table {
    margin-bottom: 0;
    color: var(--text-main);
  }

  .table thead {
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
  }

  .table thead th {
    border: none;
    padding: 16px 18px;
    font-weight: 700;
    color: #1f2933;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    border-bottom: 1px solid rgba(191, 219, 254, 0.9);
  }

  .table tbody td {
    padding: 18px 18px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    color: #111827;
    font-size: 0.95rem;
    background: #ffffff;
    vertical-align: middle;
  }

  .table tbody tr:hover td {
    background: rgba(239, 246, 255, 0.85);
  }

  /* 員工編號 code 樣式微調，改成藍色系 */
  td code {
    background: linear-gradient(135deg, rgba(191, 219, 254, 0.4), rgba(219, 234, 254, 0.8));
    padding: 6px 14px;
    border-radius: 999px;
    font-weight: 700;
    color: #1d4ed8;
    font-size: 0.85rem;
  }

  /* 頭像樣式 */
  .avatar-img {
    width: 48px;
    height: 48px;
    border-radius: 999px;
    object-fit: cover;
    border: 2px solid rgba(191, 219, 254, 0.9);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
  }

  .avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 999px;
    background: linear-gradient(135deg, #4f8bff, #7b6dff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.1rem;
    border: 2px solid rgba(191, 219, 254, 0.9);
  }

  /* 註冊狀態 badge */
 .badge-registered {
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  border: 1px solid #22c55e;
  color: #166534;
  padding: 6px 14px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;   /* <<< 不換行 */
}

  /* 刪除按鈕：紅色膠囊 */
  .btn-delete {
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    background: linear-gradient(135deg, #f97373, #ef4444);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    box-shadow: 0 10px 24px rgba(248, 113, 113, 0.45);
  }

  .btn-delete:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(239, 68, 68, 0.6);
  }

  /* 空狀態 */
  .empty-state {
    text-align: center;
    padding: 56px 20px;
    color: var(--text-subtle);
  }

  .empty-state i {
    font-size: 2.8rem;
    color: #cbd5f5;
    margin-bottom: 18px;
    display: block;
  }

  /* 返回連結 */
  .wrap > .text-center:last-child a {
    color: var(--text-subtle);
  }
  .wrap > .text-center:last-child a:hover {
    color: #2563eb;
  }

  /* modal */
  .modal-content {
    border-radius: 24px;
    border: none;
    box-shadow: var(--shadow-soft);
  }

  .modal-header {
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
    border-bottom: 1px solid rgba(191, 219, 254, 0.95);
    border-radius: 24px 24px 0 0;
    padding: 18px 24px;
  }

  .modal-title {
    font-weight: 700;
    color: var(--text-main);
  }

  .modal-body {
    padding: 24px;
    font-size: 0.95rem;
    color: #374151;
    line-height: 1.6;
  }

  .modal-footer {
    border-top: 1px solid rgba(226, 232, 240, 0.9);
    padding: 16px 24px;
  }

  @media (max-width: 768px) {
    body {
      padding: 24px 10px;
    }
    .panel-b {
      padding: 16px 14px 20px;
    }
    .panel-h {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
  }
/* ==== 頁首 hero 卡片（模仿打卡頁） ==== */
.hero-card {
  position: relative;
  margin-bottom: 32px;
  padding: 26px 32px;
  border-radius: 28px;
  background: linear-gradient(135deg, #f9fbff 0%, #ffffff 50%, #f4f4ff 100%);
  box-shadow: var(--shadow-soft);
  border: 1px solid rgba(226, 232, 240, 0.9);
  overflow: hidden;
}

/* 上方那條彩色進度條 */
.hero-card::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 6px;
  background: linear-gradient(90deg, #6366f1, #3b82f6, #0ea5e9);
}

.hero-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.hero-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

/* 左側大 icon 方塊 */
.hero-icon {
  width: 80px;
  height: 80px;
  border-radius: 24px;
  background: linear-gradient(135deg, #4f46e5, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 40px;
  box-shadow: 0 18px 40px rgba(59, 130, 246, 0.45);
}

/* 中間標題 + 小標 */
.hero-title {
  margin: 0 0 6px;
  font-size: 40px;
  font-weight: 900;
  letter-spacing: 0.06em;
  color: #1f2937;
}

.hero-subtitle {
  margin: 0;
  font-size: 16px;
  font-weight: 500;
  color: var(--text-subtle);
}

/* 小螢幕排版調整 */
@media (max-width: 768px) {
  .hero-card {
    padding: 20px 18px;
  }
  .hero-inner {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .hero-title {
    font-size: 30px;
  }
  .hero-icon {
    width: 64px;
    height: 64px;
    font-size: 32px;
  }
}

  
</style>

</head>
<body>
<div class="wrap">
  <div class="hero-card">
  <div class="hero-inner">
    <div class="hero-left">
      <div class="hero-icon">
        <i class="fa-solid fa-user-shield"></i>
      </div>
      <div>
        <h1 class="hero-title">人臉識別資料管理</h1>
        <p class="hero-subtitle">管理員工的人臉註冊資料</p>
      </div>
    </div>
    <!-- 右邊你如果之後想放日期或其他資訊可以加在這裡 -->
  </div>
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
    <a href="face_clock_with_liveness.php" class="text-muted" style="text-decoration: none; font-weight: 600;">
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
    const response = await fetch(`${API_BASE}/face_api.php?action=list`, {
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
    const response = await fetch(`${API_BASE}/face_api.php?action=delete`, {
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