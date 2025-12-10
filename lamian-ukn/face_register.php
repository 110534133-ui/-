<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>人臉註冊管理</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <!-- Face-API.js - 同步載入確保庫可用 -->
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

  :root {
    --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
    --text-main: #0f172a;
    --text-subtle: #64748b;

    --card-bg: rgba(255, 255, 255, 0.96);
    --card-radius: 24px;

    --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.10);
    --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

    --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);

    /* 主色調（跟參考檔相同藍色系） */
    --primary-from: #3b82f6;
    --primary-to: #4f46e5;
  }

  * {
    box-sizing: border-box;
    transition: var(--transition-main);
    margin: 0;
    padding: 0;
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

  .container {
    max-width: 1200px;
  }

  /* ===== 頁首 hero 表頭 ===== */
  .header {
    position: relative;
    margin-bottom: 32px;
    padding: 26px 32px;
    border-radius: 28px;
    background: linear-gradient(135deg, #f9fbff 0%, #ffffff 40%, #f4f4ff 100%);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
    border: 1px solid rgba(226, 232, 240, 0.9);
    overflow: hidden;
  }

  /* 上方彩色細長條 */
  .header::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #6366f1, #3b82f6, #0ea5e9);
  }

  .header-inner {
    display: flex;
    align-items: center;
    gap: 24px;
  }

  /* 左邊方形圖示 */
  .header-icon {
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

  /* 標題 + 說明文字 */
  .header-title {
    margin: 0 0 6px;
    font-size: 40px;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #1f2937;
  }

  .header-subtitle {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    color: var(--text-subtle);
  }

  /* 小螢幕調整 */
  @media (max-width: 768px) {
    .header {
      padding: 20px 18px;
    }
    .header-inner {
      flex-direction: row;
      align-items: center;
      gap: 16px;
    }
    .header-icon {
      width: 64px;
      height: 64px;
      font-size: 32px;
    }
    .header-title {
      font-size: 30px;
    }
  }

  /* ===== 共用卡片樣式（表單區 / 已註冊清單） ===== */
  .card {
    background: var(--card-bg);
    border-radius: 28px;
    padding: 28px 32px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(226, 232, 240, 0.95);
  }

  .card h3 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .card h3 i {
    color: #2563eb;
  }

  .form-label {
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
  }

  .form-control,
  .form-select {
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 15px;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
  }

  small.text-muted {
    color: var(--text-subtle) !important;
    font-size: 13px;
  }

  /* ===== 主按鈕區（開始拍攝 + 拍照 + 取消） ===== */

  /* 開始拍攝 / 主按鈕：藍色膠囊（跟參考檔同色系） */
  .btn-start,
  .btn-primary {
    background: linear-gradient(135deg, var(--primary-from) 0%, var(--primary-to) 100%);
    border: none;
    border-radius: 999px;
    height: 56px;
    padding: 0 32px;
    font-weight: 700;
    font-size: 16px;
    color: #ffffff;
    box-shadow: 0 18px 40px rgba(37, 99, 235, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .btn-start:hover,
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-hover);
  }

  /* 拍照註冊：一樣改成藍色主色調，不再用綠色 */
  .btn-success {
    background: linear-gradient(135deg, var(--primary-from) 0%, var(--primary-to) 100%);
    border: none;
    border-radius: 999px;
    height: 52px;
    padding: 0 28px;
    font-weight: 700;
    font-size: 15px;
    color: #ffffff;
    box-shadow: 0 16px 36px rgba(37, 99, 235, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  .btn-success:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-hover);
  }

  /* 取消：紅色膠囊 */
  .btn-danger {
    background: linear-gradient(135deg, #f97373 0%, #ef4444 100%) !important;
    border: none;
    border-radius: 999px;
    height: 52px;
    padding: 0 24px;
    font-weight: 700;
    font-size: 15px;
    color: #ffffff;
    box-shadow: 0 16px 36px rgba(239, 68, 68, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  /* ===== 相機區 ===== */
  .camera-section {
    display: none;
    margin-top: 12px;
  }

  .camera-section.active {
    display: block;
  }

  .video-container {
    position: relative;
    width: 100%;
    max-width: 640px;
    margin: 0 auto 24px;
    border-radius: 20px;
    overflow: hidden;
    background: #020617;
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.65);
  }

  #video {
    width: 100%;
    height: auto;
    display: block;
  }

  #canvas {
    position: absolute;
    top: 0;
    left: 0;
  }

  .detection-status {
    position: absolute;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    padding: 8px 20px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 14px;
    backdrop-filter: blur(10px);
    z-index: 10;
  }

  .detection-status.detected {
    background: rgba(22, 163, 74, 0.95);
    color: #ffffff;
  }

  .detection-status.no-face {
    background: rgba(248, 113, 113, 0.95);
    color: #ffffff;
  }

/* ===== 已註冊人臉卡片（faceGallery） ===== */
.face-gallery {
  display: grid;
  grid-template-columns: repeat(2, 1fr);   /* 一排兩個 */
  gap: 32px;                               /* 卡片間距 */
  margin-top: 24px;
}

/* 單一卡片 */
.face-card {
  background: #ffffff;
  border-radius: 24px;
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.95);
  box-shadow: 0 14px 40px rgba(15, 23, 42, 0.10);
  transition: all 0.25s ease;
}

.face-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.18);
}

/* 頭像 */
.face-card img {
  width: 100%;
  height: 220px;         /* 改小一點更好看 */
  object-fit: cover;
  display: block;
}

/* 內容區 */
.face-card-body {
  padding: 16px 20px;
}

.face-card-title {
  font-weight: 700;
  font-size: 16px;
  margin-bottom: 4px;
}

.face-card-subtitle {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 4px;
}

.face-card-date {
  color: #9ca3af;
  font-size: 12px;
}

/* 刪除按鈕 */
.face-card .btn-danger {
  width: 100%;
  height: 48px;
  font-size: 14px;
  margin-top: 12px;
  border-radius: 999px;
}

/* ===== 手機版改回一排一個 ===== */
@media (max-width: 768px) {
  .face-gallery {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .face-card img {
    height: 200px;
  }
}

  /* ===== 訊息 / 空狀態 / loading ===== */
  .alert {
    border-radius: 16px;
    border: none;
    padding: 14px 18px;
    font-weight: 500;
  }

  .loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #4f46e5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-subtle);
  }

  .empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    display: block;
    color: #cbd5f5;
  }

  /* RWD 微調 */
  @media (max-width: 768px) {
    body {
      padding: 24px 10px;
    }
    .header {
      padding: 20px 18px;
    }
    .card {
      padding: 20px 18px;
      border-radius: 24px;
    }
    .header h1 {
      font-size: 26px;
    }
  }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="header-inner">
      <div class="header-icon">
        <i class="fa-solid fa-user-shield"></i>
      </div>
      <div>
        <h1 class="header-title">人臉註冊管理</h1>
        <p class="header-subtitle">為員工註冊人臉數據，用於打卡系統識別</p>
      </div>
    </div>
  </div>

  <div class="card">
    <h3><i class="fa-solid fa-user-plus"></i>註冊新人臉</h3>
    
    <div class="row g-3 mb-4 align-items-center">
      <div class="col-md-6">
        <label class="form-label">員工編號</label>
        <input type="text" class="form-control" id="employeeInput" placeholder="請輸入員工編號 (例如: 1002)">
        <small class="text-muted">請輸入「員工基本資料.id」欄位的值</small>
      </div>
      <div class="col-md-6 d-flex align-items-center justify-content-md-end">
        <button class="btn btn-primary w-100 btn-start" id="btnStartCamera">
          <i class="fa-solid fa-camera me-2"></i>開始拍攝
        </button>
      </div>
    </div>

    <div class="camera-section" id="cameraSection">
      <div class="video-container">
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="detection-status no-face" id="detectionStatus">
          <i class="fa-solid fa-exclamation-triangle me-2"></i>請正對鏡頭
        </div>
      </div>
      
      <div class="d-flex gap-3 justify-content-center">
        <button class="btn btn-success" id="btnCapture" disabled>
          <i class="fa-solid fa-camera me-2"></i>拍照註冊
        </button>
        <button class="btn btn-danger" id="btnCancelCamera">
          <i class="fa-solid fa-xmark me-2"></i>取消
        </button>
      </div>
    </div>

    <div id="msg" class="mt-3"></div>
  </div>

  <div class="card">
  <h3><i class="fa-solid fa-users"></i>已註冊人臉</h3>
  
  <div id="faceGallery" class="face-gallery">
    <div class="text-center py-5">
      <div class="loading-spinner"></div>
      <p class="mt-3 text-muted">載入中...</p>
    </div>
  </div>
</div>

</div>

<script>
const API_BASE = '/lamian-ukn/api';
const API_FACE_REGISTER = API_BASE + '/face_register.php';
const API_FACE_LIST = API_BASE + '/face_list.php';
const API_FACE_DELETE = API_BASE + '/face_delete.php';

const employeeInput = document.getElementById('employeeInput');
const cameraSection = document.getElementById('cameraSection');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const detectionStatus = document.getElementById('detectionStatus');
const btnCapture = document.getElementById('btnCapture');
const msg = document.getElementById('msg');
const faceGallery = document.getElementById('faceGallery');

let stream = null;
let faceDetectionInterval = null;
let modelsLoaded = false;
let faceDetected = false;

// Load Face-API Models
async function loadModels() {
  if (modelsLoaded) return;
  try {
    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model';
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    modelsLoaded = true;
  } catch (error) {
    console.error('Model loading error:', error);
    showMsg('danger', '人臉識別模型載入失敗');
  }
}

// Start Camera
async function startCamera() {
  const empId = (employeeInput.value || '').trim();
  if (!empId) {
    showMsg('warning', '請先輸入員工編號');
    employeeInput.focus();
    return;
  }
  
  if (!modelsLoaded) {
    showMsg('info', '正在載入人臉識別模型...');
    await loadModels();
  }
  
  try {
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user'
      } 
    });
    
    video.srcObject = stream;
    cameraSection.classList.add('active');
    
    video.addEventListener('loadedmetadata', () => {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      startFaceDetection();
    });
    
  } catch (error) {
    console.error('Camera error:', error);
    showMsg('danger', '無法開啟攝像頭,請確認已授予權限');
  }
}

// Stop Camera
function stopCamera() {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  if (faceDetectionInterval) {
    clearInterval(faceDetectionInterval);
    faceDetectionInterval = null;
  }
  cameraSection.classList.remove('active');
  faceDetected = false;
  btnCapture.disabled = true;
}

// Face Detection
async function startFaceDetection() {
  const detectFace = async () => {
    if (!video.videoWidth) return;
    
    try {
      const detection = await faceapi.detectSingleFace(
        video, 
        new faceapi.TinyFaceDetectorOptions()
      ).withFaceLandmarks().withFaceDescriptor();
      
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      
      if (detection) {
        const box = detection.detection.box;
        ctx.strokeStyle = '#11998e';
        ctx.lineWidth = 4;
        ctx.strokeRect(box.x, box.y, box.width, box.height);
        
        const landmarks = detection.landmarks.positions;
        ctx.fillStyle = '#11998e';
        landmarks.forEach(point => {
          ctx.beginPath();
          ctx.arc(point.x, point.y, 3, 0, 2 * Math.PI);
          ctx.fill();
        });
        
        faceDetected = true;
        detectionStatus.className = 'detection-status detected';
        detectionStatus.innerHTML = '<i class="fa-solid fa-check me-2"></i>人臉已識別';
        btnCapture.disabled = false;
      } else {
        faceDetected = false;
        detectionStatus.className = 'detection-status no-face';
        detectionStatus.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2"></i>請正對鏡頭';
        btnCapture.disabled = true;
      }
    } catch (error) {
      console.error('Detection error:', error);
    }
  };
  
  faceDetectionInterval = setInterval(detectFace, 100);
}

// Capture and Register
async function captureAndRegister() {
  if (!faceDetected) {
    showMsg('warning', '請確保臉部完全在畫面中');
    return;
  }
  
  const empId = (employeeInput.value || '').trim();
  if (!empId) {
    showMsg('warning', '請輸入員工編號');
    return;
  }
  
  btnCapture.disabled = true;
  detectionStatus.className = 'detection-status no-face';
  detectionStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>處理中...';
  
  try {
    const captureCanvas = document.createElement('canvas');
    captureCanvas.width = video.videoWidth;
    captureCanvas.height = video.videoHeight;
    const ctx = captureCanvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    const imageData = captureCanvas.toDataURL('image/jpeg', 0.8);
    
    const detection = await faceapi.detectSingleFace(
      video,
      new faceapi.TinyFaceDetectorOptions()
    ).withFaceLandmarks().withFaceDescriptor();
    
    if (!detection) {
      throw new Error('無法提取人臉特徵');
    }
    
    const faceDescriptor = Array.from(detection.descriptor);
    
    const response = await fetch(API_FACE_REGISTER, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        emp_code: empId,
        descriptors: [faceDescriptor]  // API 期望陣列格式
      }),
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if (!response.ok || result.error) {
      throw new Error(result.error || result.detail || '註冊失敗');
    }
    
    showMsg('success', '人臉註冊成功!');
    stopCamera();
    loadFaceGallery();
    
  } catch (error) {
    console.error('Register error:', error);
    showMsg('danger', '人臉註冊失敗: ' + error.message);
    btnCapture.disabled = false;
  }
}

// Load Face Gallery
async function loadFaceGallery() {
  try {
    const response = await fetch(API_FACE_LIST, { credentials: 'include' });
    const data = await response.json();
    
    console.log('Face list response:', data);
    
    // 處理新的返回格式 {success: true, face_data: [...], stats: {...}}
    let faces = [];
    if (data.success && Array.isArray(data.face_data)) {
      faces = data.face_data;
    } else if (Array.isArray(data)) {
      // 向後兼容舊格式
      faces = data;
    }
    
    if (faces.length === 0) {
      faceGallery.innerHTML = `
        <div class="empty-state">
          <i class="fa-regular fa-face-frown"></i>
          <p>尚無已註冊的人臉</p>
        </div>
      `;
      return;
    }
    
    faceGallery.innerHTML = faces.map(face => {
      // 處理頭像
      let avatarHtml = '';
      if (face.avatar_url && face.avatar_url.trim() !== '') {
        avatarHtml = `<img src="${face.avatar_url}" alt="${face.emp_name}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-size=\'80\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3E${(face.emp_name || '?').charAt(0)}%3C/text%3E%3C/svg%3E'">`;
      } else {
        avatarHtml = `<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23ddd' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' font-size='80' text-anchor='middle' dy='.3em' fill='%23999'%3E${(face.emp_name || '?').charAt(0)}%3C/text%3E%3C/svg%3E" alt="${face.emp_name}">`;
      }
      
      return `
      <div class="face-card">
        ${avatarHtml}
        <div class="face-card-body">
          <div class="face-card-title">${face.emp_name || '未知'}</div>
          <div class="face-card-subtitle">${face.user_id || face.employee_id || ''}</div>
          <div class="face-card-date">註冊於 ${new Date(face.created_at).toLocaleDateString('zh-TW')}</div>
          <button class="btn btn-danger btn-sm w-100 mt-2" onclick="deleteFace(${face.id})">
            <i class="fa-solid fa-trash me-1"></i>刪除
          </button>
        </div>
      </div>
    `;
    }).join('');
    
  } catch (error) {
    console.error('Load gallery error:', error);
    faceGallery.innerHTML = `
      <div class="empty-state">
        <i class="fa-solid fa-exclamation-triangle"></i>
        <p>載入失敗: ${error.message}</p>
      </div>
    `;
  }
}

// Delete Face
async function deleteFace(id) {
  if (!confirm('確定要刪除此人臉資料嗎?')) return;
  
  try {
    const response = await fetch(`${API_FACE_DELETE}?id=${id}`, {
      method: 'DELETE',
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if (!response.ok || result.error) {
      throw new Error(result.error || '刪除失敗');
    }
    
    showMsg('success', '人臉資料已刪除');
    loadFaceGallery();
    
  } catch (error) {
    console.error('Delete error:', error);
    showMsg('danger', '刪除失敗: ' + error.message);
  }
}

// Show Message
function showMsg(type, text) {
  msg.className = `alert alert-${type}`;
  msg.innerHTML = text;
  msg.style.display = 'block';
  setTimeout(() => { msg.style.display = 'none'; }, 5000);
}

// Initialize when everything is ready
function initialize() {
  console.log('Initializing face register system...');
  
  // Check if faceapi is available
  if (typeof faceapi === 'undefined') {
    console.error('Face-API.js not loaded!');
    showMsg('danger', '❌ 人臉識別庫載入失敗,請重新整理頁面');
    return;
  }
  
  console.log('Face-API.js loaded successfully');
  
  // Event Listeners
  document.getElementById('btnStartCamera').addEventListener('click', startCamera);
  document.getElementById('btnCancelCamera').addEventListener('click', stopCamera);
  document.getElementById('btnCapture').addEventListener('click', captureAndRegister);
  
  // Load initial data
  loadFaceGallery();
  loadModels();
}

// Wait for DOM and scripts to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initialize);
} else {
  // DOM already loaded
  initialize();
}
</script>
</body>
</html>
