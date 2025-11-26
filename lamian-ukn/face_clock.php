<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>人臉識別打卡機</title>
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
      max-width: 1400px;
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
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
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
    
    /* 攝影機區域 */
    .camera-container {
      position: relative;
      max-width: 640px;
      margin: 0 auto;
      background: #000;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
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
    
    .camera-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 300px;
      height: 400px;
      border: 4px solid rgba(251, 185, 124, 0.8);
      border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
      pointer-events: none;
      animation: scanPulse 2s ease-in-out infinite;
    }
    
    @keyframes scanPulse {
      0%, 100% { opacity: 0.6; border-color: rgba(251, 185, 124, 0.8); }
      50% { opacity: 1; border-color: rgba(255, 90, 90, 1); }
    }
    
    .detection-info {
      position: absolute;
      top: 20px;
      left: 20px;
      right: 20px;
      background: rgba(0, 0, 0, 0.7);
      padding: 16px 24px;
      border-radius: 16px;
      color: white;
      font-weight: 700;
      font-size: 16px;
      text-align: center;
    }
    
    .detection-info.success {
      background: rgba(32, 201, 151, 0.9);
    }
    
    .detection-info.warning {
      background: rgba(255, 193, 7, 0.9);
    }
    
    .detection-info.error {
      background: rgba(220, 53, 69, 0.9);
    }
    
    /* 控制按鈕 */
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
      width: 100%;
    }
    
    .btn-big:hover {
      transform: translateY(-6px) scale(1.03);
    }
    
    .btn-big:active {
      transform: scale(0.96);
    }
    
    .btn-big:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }
    
    .btn-face-in {
      background: linear-gradient(135deg, #20c997 0%, #17a589 100%);
    }
    
    .btn-face-in:hover {
      box-shadow: 0 16px 50px rgba(32, 201, 151, 0.5);
    }
    
    .btn-face-out {
      background: linear-gradient(135deg, #4c9aff 0%, #3182e0 100%);
    }
    
    .btn-face-out:hover {
      box-shadow: 0 16px 50px rgba(76, 154, 255, 0.5);
    }
    
    .btn-register {
      background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%);
    }
    
    .btn-register:hover {
      box-shadow: 0 16px 50px rgba(255, 90, 90, 0.5);
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
    
    .mode-switch {
      text-align: center;
      margin-top: 32px;
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
    
    .loading-spinner {
      display: inline-block;
      width: 24px;
      height: 24px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top: 3px solid #fff;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 12px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header-section">
    <div class="header-content">
      <div class="brand-wrapper">
        <div class="brand-icon">
          <i class="fa-solid fa-face-smile"></i>
        </div>
        <div>
          <div class="brand">人臉識別打卡</div>
          <div class="brand-subtitle">Face Recognition Attendance</div>
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
      <i class="fa-solid fa-camera"></i>
      人臉識別打卡
    </div>
    <div class="panel-b">
      <div class="camera-container">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="camera-overlay"></div>
        <div id="detectionInfo" class="detection-info" style="display: none;"></div>
      </div>
      
      <div class="row g-4 mt-4">
        <div class="col-md-6">
          <button class="btn btn-big btn-face-in" id="btnFaceIn" disabled>
            <i class="fa-solid fa-door-open me-2"></i>
            <span>人臉上班打卡</span>
          </button>
        </div>
        <div class="col-md-6">
          <button class="btn btn-big btn-face-out" id="btnFaceOut" disabled>
            <i class="fa-solid fa-door-closed me-2"></i>
            <span>人臉下班打卡</span>
          </button>
        </div>
      </div>
      
      <div class="info-text">
        <i class="fa-solid fa-lightbulb me-2"></i>
        請將臉部對準框架內,系統會自動識別您的身份。首次使用請先進行人臉註冊。
      </div>
      
      <div class="mt-4" id="msg"></div>
      
      <div class="mode-switch">
        <a href="face_register.php">
          <i class="fa-solid fa-user-plus me-2"></i>
          還沒註冊人臉?點此註冊
        </a>
        <span class="mx-3">|</span>
        <a href="打卡機.php">
          <i class="fa-solid fa-keyboard me-2"></i>
          切換到手動輸入模式
        </a>
      </div>
    </div>
  </div>

  <div class="footer-text text-center text-muted mt-5">
    <i class="fa-solid fa-shield-halved me-2"></i>
    門市打卡系統 - 人臉識別技術由 Face-API.js 提供
  </div>
</div>

<script>
const API_BASE = '/lamian-ukn/api';
const API_FACE_RECOGNIZE = API_BASE + '/face_recognize.php';
const API_FACE_PUNCH = API_BASE + '/face_punch.php';

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const msg = document.getElementById('msg');
const detectionInfo = document.getElementById('detectionInfo');
const btnFaceIn = document.getElementById('btnFaceIn');
const btnFaceOut = document.getElementById('btnFaceOut');

let modelsLoaded = false;
let currentDescriptor = null;
let recognizedEmployee = null;
let isProcessing = false;

// 更新時間
document.getElementById('now').textContent =
  new Date().toLocaleString('zh-TW',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});

// 顯示訊息
function showMsg(ok, text){
  msg.className = ok ? 'msg-success' : 'msg-error';
  msg.innerHTML = `<i class="fa-solid fa-${ok ? 'circle-check' : 'circle-xmark'} me-2"></i>${text}`;
  setTimeout(()=> msg.innerHTML='', 5000);
}

// 顯示檢測資訊
function showDetectionInfo(text, type = ''){
  detectionInfo.textContent = text;
  detectionInfo.className = 'detection-info ' + type;
  detectionInfo.style.display = 'block';
}

function hideDetectionInfo(){
  detectionInfo.style.display = 'none';
}

// 載入 Face-API 模型
async function loadModels(){
  try {
    showDetectionInfo('正在載入人臉識別模型...', 'warning');
    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
    
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
      faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
    ]);
    
    modelsLoaded = true;
    showDetectionInfo('✓ 模型載入完成,請將臉部對準框架', 'success');
    setTimeout(hideDetectionInfo, 3000);
    console.log('Face-API models loaded successfully');
  } catch(err) {
    console.error('Failed to load models:', err);
    showDetectionInfo('✗ 模型載入失敗', 'error');
    showMsg(false, '人臉識別模型載入失敗,請重新整理頁面');
  }
}

// 啟動攝影機
async function startCamera(){
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user'
      } 
    });
    video.srcObject = stream;
    
    video.addEventListener('loadedmetadata', () => {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
    });
    
    console.log('Camera started successfully');
  } catch(err) {
    console.error('Failed to start camera:', err);
    showMsg(false, '無法啟動攝影機,請確認已授予相機權限');
  }
}

// 人臉檢測和識別
async function detectAndRecognize(){
  if(!modelsLoaded || isProcessing) return;
  
  const detection = await faceapi
    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();
  
  if(detection){
    currentDescriptor = detection.descriptor;
    
    // 繪製檢測框
    const dims = faceapi.matchDimensions(canvas, video, true);
    const resizedDetection = faceapi.resizeResults(detection, dims);
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    faceapi.draw.drawDetections(canvas, resizedDetection);
    faceapi.draw.drawFaceLandmarks(canvas, resizedDetection);
    
    // 識別身份
    await recognizeFace(currentDescriptor);
  } else {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    recognizedEmployee = null;
    currentDescriptor = null;
    btnFaceIn.disabled = true;
    btnFaceOut.disabled = true;
  }
}

// 呼叫後端識別 API
async function recognizeFace(descriptor){
  if(isProcessing) return;
  
  try {
    const response = await fetch(API_FACE_RECOGNIZE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ descriptor: Array.from(descriptor) }),
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if(result.success && result.employee){
      recognizedEmployee = result.employee;
      showDetectionInfo(`✓ 識別成功: ${result.employee.name} (${result.employee.code})`, 'success');
      btnFaceIn.disabled = false;
      btnFaceOut.disabled = false;
    } else {
      recognizedEmployee = null;
      showDetectionInfo('⚠ 未識別到註冊的人臉', 'warning');
      btnFaceIn.disabled = true;
      btnFaceOut.disabled = true;
    }
  } catch(err) {
    console.error('Recognition error:', err);
  }
}

// 人臉打卡
async function facePunch(action){
  if(!recognizedEmployee || !currentDescriptor || isProcessing) return;
  
  isProcessing = true;
  const btn = action === 'in' ? btnFaceIn : btnFaceOut;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<span class="loading-spinner"></span>處理中...';
  btn.disabled = true;
  
  try {
    const response = await fetch(API_FACE_PUNCH, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        emp_code: recognizedEmployee.code,
        action: action,
        descriptor: Array.from(currentDescriptor)
      }),
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if(result.ok || result.success){
      showMsg(true, `${result.message}: ${recognizedEmployee.name}`);
      showDetectionInfo(`✓ ${action === 'in' ? '上班' : '下班'}打卡成功!`, 'success');
      
      // 成功後暫停3秒
      setTimeout(() => {
        recognizedEmployee = null;
        currentDescriptor = null;
        hideDetectionInfo();
      }, 3000);
    } else {
      throw new Error(result.error || result.detail || '打卡失敗');
    }
  } catch(err) {
    console.error('Punch error:', err);
    showMsg(false, '打卡失敗: ' + err.message);
    showDetectionInfo('✗ 打卡失敗', 'error');
  } finally {
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
      isProcessing = false;
    }, 3000);
  }
}

// 事件監聽
btnFaceIn.addEventListener('click', () => facePunch('in'));
btnFaceOut.addEventListener('click', () => facePunch('out'));

// 初始化
async function init(){
  console.log('Initializing face recognition system...');
  
  // Check if faceapi is available
  if (typeof faceapi === 'undefined') {
    console.error('Face-API.js not loaded!');
    showMsg(false, '人臉識別庫載入失敗,請重新整理頁面');
    showDetectionInfo('✗ 系統載入失敗', 'error');
    return;
  }
  
  console.log('Face-API.js loaded successfully');
  
  await loadModels();
  await startCamera();
  
  // 每500ms檢測一次人臉
  setInterval(detectAndRecognize, 500);
}

// 頁面載入後啟動
if(document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
</script>
</body>
</html>
