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
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      padding: 40px 20px;
    }
    
    .container {
      max-width: 1200px;
    }
    
    .header {
      background: white;
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 32px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }
    
    .header h1 {
      font-size: 36px;
      font-weight: 800;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    
    .header p {
      color: #7f8c8d;
      font-size: 16px;
    }
    
    .card {
      background: white;
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 24px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      border: none;
    }
    
    .card h3 {
      font-size: 24px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    
    .form-control, .form-select {
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 15px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #3498db;
      box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      padding: 12px 32px;
      border-radius: 12px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }
    
    .btn-success {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      border: none;
      padding: 12px 32px;
      border-radius: 12px;
      font-weight: 600;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
    }
    
    .video-container {
      position: relative;
      width: 100%;
      max-width: 640px;
      margin: 0 auto 24px;
      border-radius: 16px;
      overflow: hidden;
      background: #000;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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
      border-radius: 50px;
      font-weight: 600;
      font-size: 14px;
      backdrop-filter: blur(10px);
      z-index: 10;
    }
    
    .detection-status.detected {
      background: rgba(17, 153, 142, 0.9);
      color: white;
    }
    
    .detection-status.no-face {
      background: rgba(235, 51, 73, 0.9);
      color: white;
    }
    
    .face-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 24px;
    }
    
    .face-card {
      background: #f8f9fa;
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s ease;
      border: 2px solid #e0e0e0;
    }
    
    .face-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      border-color: #667eea;
    }
    
    .face-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    
    .face-card-body {
      padding: 16px;
    }
    
    .face-card-title {
      font-weight: 700;
      font-size: 16px;
      color: #2c3e50;
      margin-bottom: 4px;
    }
    
    .face-card-subtitle {
      color: #7f8c8d;
      font-size: 13px;
      margin-bottom: 4px;
    }
    
    .face-card-date {
      color: #95a5a6;
      font-size: 12px;
    }
    
    .alert {
      border-radius: 12px;
      border: none;
      padding: 16px 20px;
      font-weight: 500;
    }
    
    .camera-section {
      display: none;
    }
    
    .camera-section.active {
      display: block;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 40px;
      height: 40px;
      border: 4px solid #f0f0f0;
      border-top: 4px solid #667eea;
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
      color: #95a5a6;
    }
    
    .empty-state i {
      font-size: 64px;
      margin-bottom: 16px;
      display: block;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1><i class="fa-solid fa-face-smile me-3"></i>人臉註冊管理</h1>
    <p>為員工註冊人臉數據,用於打卡系統識別</p>
  </div>

  <div class="card">
    <h3><i class="fa-solid fa-user-plus"></i>註冊新人臉</h3>
    
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label">員工編號</label>
        <input type="text" class="form-control" id="employeeInput" placeholder="請輸入員工編號 (例如: 1002)">
        <small class="text-muted">請輸入「員工基本資料.id」欄位的值</small>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <button class="btn btn-primary w-100" id="btnStartCamera">
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
    
    <div id="faceGallery">
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

// Load Employees
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