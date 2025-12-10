<?php
// 假設你在伺服器端知道這台裝置的 token（例如從 session、或設定檔）
$DEVICE_TOKEN = $_SESSION['device_token'] ?? ''; // 或從 config 讀取
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>人臉識別打卡機</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <!-- Face-API.js -->
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
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
    max-width: 1400px;
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
  }
  
  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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
  
  /* 攝影機區域 */
  .camera-container {
    position: relative;
    max-width: 640px;
    margin: 0 auto;
    background: #000;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.65);
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
    border: 4px solid rgba(79, 139, 255, 0.9);
    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
    pointer-events: none;
    animation: scanPulse 2s ease-in-out infinite;
  }
  
  @keyframes scanPulse {
    0%, 100% { opacity: 0.6; border-color: rgba(79, 139, 255, 0.9); }
    50% { opacity: 1; border-color: rgba(123, 109, 255, 1); }
  }
  
  /* 活體檢測指示覆蓋層 */
  .liveness-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10;
  }
  
  .liveness-instruction {
    background: linear-gradient(135deg, rgba(79, 139, 255, 0.98), rgba(123, 109, 255, 0.98));
    color: white;
    padding: 48px 64px;
    border-radius: 24px;
    text-align: center;
    font-size: 36px;
    font-weight: 800;
    border: 4px solid white;
    animation: instructionPulse 1.5s ease-in-out infinite;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.7);
  }
  
  @keyframes instructionPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }
  
  .liveness-instruction i {
    display: block;
    font-size: 72px;
    margin-bottom: 24px;
  }
  
  .detection-info {
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    background: rgba(15, 23, 42, 0.8);
    padding: 16px 24px;
    border-radius: 16px;
    color: white;
    font-weight: 700;
    font-size: 16px;
    text-align: center;
    z-index: 5;
  }
  
  .detection-info.success {
    background: rgba(34, 197, 94, 0.9);
  }
  
  .detection-info.warning {
    background: rgba(234, 179, 8, 0.9);
  }
  
  .detection-info.error {
    background: rgba(239, 68, 68, 0.9);
  }
  
  /* 活體檢測進度 */
  .liveness-progress {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 20px;
    padding: 24px;
    margin-top: 24px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
    border: 1px solid rgba(226, 232, 240, 0.95);
  }
  
  .action-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    margin-bottom: 12px;
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.9), rgba(224, 231, 255, 0.85));
    border-radius: 12px;
    border: 2px solid rgba(226, 232, 240, 0.95);
    transition: all 0.3s ease;
  }
  
  .action-card.current {
    border-color: #facc15;
    background: linear-gradient(135deg, rgba(250, 204, 21, 0.16), rgba(250, 250, 249, 0.95));
    transform: scale(1.02);
  }
  
  .action-card.completed {
    border-color: #22c55e;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(240, 253, 244, 0.96));
  }
  
  .action-icon {
    font-size: 24px;
    margin-right: 12px;
  }
  
  .action-text {
    flex: 1;
    font-weight: 700;
    font-size: 16px;
    color: #0f172a;
  }
  
  .action-status {
    font-size: 20px;
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
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.4);
    width: 100%;
  }
  
  .btn-big:hover:not(:disabled) {
    transform: translateY(-4px) scale(1.02);
  }
  
  .btn-big:active {
    transform: scale(0.98);
  }
  
  .btn-big:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
  }
  
  /* ✅ 跟前一頁一致：上班綠，下班藍 */
  .btn-face-in {
    background: linear-gradient(135deg, #22c55e, #16a34a);
  }
  
  .btn-face-out {
    background: linear-gradient(135deg, #2563eb, #4f8bff);
  }
  
  .loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  
  .msg-success, .msg-error {
    padding: 24px 32px;
    border-radius: 16px;
    margin-top: 24px;
    font-weight: 700;
    font-size: 18px;
    text-align: center;
    animation: slideIn 0.4s ease-out;
  }
  
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .msg-success {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
    border: 2px solid #22c55e;
  }
  
  .msg-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #b91c1c;
    border: 2px solid #ef4444;
  }
  
  .info-text {
    text-align: center;
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
    margin-top: 32px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(191, 219, 254, 0.6), rgba(221, 214, 254, 0.55));
    border-radius: 16px;
    border: 2px dashed rgba(148, 163, 184, 0.6);
  }
  
  .mode-switch {
    text-align: center;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 2px solid rgba(226, 232, 240, 0.9);
  }
  
  .mode-switch a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
  }
  
  .mode-switch a:hover {
    color: #1e3a8a;
    transform: translateX(2px);
  }
  
  .footer-text {
    font-size: 14px;
    font-weight: 600;
    opacity: 0.75;
    color: #64748b;
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
  }
</style>

</head>
<body>
<div class="wrap">
  <div class="header-section">
    <div class="header-content">
      <div class="brand-wrapper">
        <div class="brand-icon">
          <i class="fa-solid fa-user-shield"></i>
        </div>
        <div>
          <div class="brand">人臉識別打卡</div>
          <div class="brand-subtitle">Face Recognition + Liveness Detection</div>
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
        <div id="livenessOverlay" class="liveness-overlay">
          <div class="liveness-instruction">
            <i id="livenessIcon"></i>
            <div id="livenessText"></div>
          </div>
        </div>
        <div id="detectionInfo" class="detection-info" style="display: none;"></div>
      </div>
      
      <!-- 人臉辨識進度 -->
      <div id="livenessProgress" class="liveness-progress" style="display: none;">
        <h5 class="mb-3" style="font-weight: 800; color: #333;">
          <i class="fa-solid fa-tasks me-2"></i>人臉辨識進度
        </h5>
        <div id="actionsList"></div>
        <div class="progress mt-3" style="height: 12px; border-radius: 10px;">
          <div id="progressBar" class="progress-bar bg-success" style="width: 0%; transition: width 0.5s ease;"></div>
        </div>
        <div class="text-center mt-2">
          <small class="text-muted" style="font-weight: 700;">
            完成進度: <span id="progressText">0/1</span>
          </small>
        </div>
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
        <i class="fa-solid fa-shield-halved me-2"></i>
        系統採用人臉識別技術,防止照片欺騙。請左右搖頭完成驗證後再進行打卡。
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
        <span class="mx-3">|</span>
        <a href="face_admin.php">
          <i class="fa-solid fa-user-gear me-2"></i>
          人臉資料管理
        </a>
      </div>
    </div>
  </div>

  <div class="footer-text text-center text-muted mt-5">
    <i class="fa-solid fa-shield-halved me-2"></i>
    門市打卡系統 - 人臉識別
  </div>
</div>

<script>  
// 全域常數:由伺服器注入
const DEVICE_TOKEN = <?= json_encode($DEVICE_TOKEN) ?>;
</script>

<script>
const API_BASE = '/lamian-ukn/api';
const API_FACE_RECOGNIZE = API_BASE + '/face_api.php?action=recognize';
const API_FACE_PUNCH = API_BASE + '/face_api.php?action=punch';

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const msg = document.getElementById('msg');
const detectionInfo = document.getElementById('detectionInfo');
const btnFaceIn = document.getElementById('btnFaceIn');
const btnFaceOut = document.getElementById('btnFaceOut');
const livenessOverlay = document.getElementById('livenessOverlay');
const livenessIcon = document.getElementById('livenessIcon');
const livenessText = document.getElementById('livenessText');
const livenessProgress = document.getElementById('livenessProgress');
const actionsList = document.getElementById('actionsList');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');

let modelsLoaded = false;
let currentDescriptor = null;
let recognizedEmployee = null;
let isProcessing = false;
let livenessVerified = false;
let livenessTimestamp = 0;

// 活體檢測變數
let currentActionIndex = 0;
let headPoseHistory = [];

// 定義檢測動作 - 只需要搖頭
const LIVENESS_ACTIONS = [
  { 
    id: 'turnHead',
    name: '搖頭',
    icon: 'fa-arrows-left-right',
    instruction: '請左右搖頭',
    check: checkHeadTurn
  }
];

// 不需要隨機排序，只有一個動作
let challengeActions = [...LIVENESS_ACTIONS];
let actionStates = challengeActions.map(a => ({ ...a, completed: false, current: false }));

// 更新時間
function updateTime() {
  document.getElementById('now').textContent =
    new Date().toLocaleString('zh-TW',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});
}
updateTime();
setInterval(updateTime, 1000);

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

// 顯示活體檢測指示
function showLivenessInstruction(icon, text) {
  livenessIcon.className = 'fa-solid ' + icon;
  livenessText.textContent = text;
  livenessOverlay.style.display = 'flex';
}

function hideLivenessInstruction() {
  livenessOverlay.style.display = 'none';
}

// 渲染動作列表
function renderActionsList() {
  actionsList.innerHTML = actionStates.map((action, index) => {
    let statusIcon = '⏳';
    let cardClass = '';
    
    if (action.completed) {
      statusIcon = '✓';
      cardClass = 'completed';
    } else if (action.current) {
      statusIcon = '▶';
      cardClass = 'current';
    }
    
    return `
      <div class="action-card ${cardClass}">
        <i class="fa-solid ${action.icon} action-icon" style="color: ${action.completed ? '#20c997' : action.current ? '#ffc107' : '#999'}"></i>
        <div class="action-text">${index + 1}. ${action.name}</div>
        <div class="action-status">${statusIcon}</div>
      </div>
    `;
  }).join('');
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
    showDetectionInfo('✓ 模型載入完成', 'success');
    setTimeout(hideDetectionInfo, 2000);
    console.log('✓ Face-API models loaded');
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
    
    console.log('✓ Camera started');
  } catch(err) {
    console.error('Failed to start camera:', err);
    showMsg(false, '無法啟動攝影機,請確認已授予相機權限');
  }
}

// ==================== 人臉識別函數 ====================

function startLivenessCheck() {
  console.log('=== 開始人臉識別 ===');
  livenessVerified = false;
  currentActionIndex = 0;
  headPoseHistory = [];
  
  // 不需要隨機，只有搖頭一個動作
  challengeActions = [...LIVENESS_ACTIONS];
  actionStates = challengeActions.map(a => ({ ...a, completed: false, current: false }));
  
  livenessProgress.style.display = 'block';
  renderActionsList();
  startNextAction();
}

function startNextAction() {
  if (currentActionIndex >= challengeActions.length) {
    completeLivenessCheck();
    return;
  }
  
  // 重置狀態
  headPoseHistory = [];
  
  // 更新當前動作
  actionStates = actionStates.map((a, i) => ({
    ...a,
    current: i === currentActionIndex
  }));
  
  renderActionsList();
  
  // 顯示指示
  const currentAction = challengeActions[currentActionIndex];
  showLivenessInstruction(currentAction.icon, currentAction.instruction);
  
  setTimeout(() => {
    hideLivenessInstruction();
  }, 2000);
  
  console.log(`開始動作: ${currentAction.name}`);
}


function completeCurrentAction() {
  actionStates[currentActionIndex].completed = true;
  actionStates[currentActionIndex].current = false;
  
  currentActionIndex++;
  
  // 更新進度
  const progress = (currentActionIndex / challengeActions.length) * 100;
  progressBar.style.width = progress + '%';
  progressText.textContent = `${currentActionIndex}/${challengeActions.length}`;
  
  renderActionsList();
  
  if (currentActionIndex < challengeActions.length) {
    setTimeout(() => startNextAction(), 500);
  } else {
    completeLivenessCheck();
  }
}

function completeLivenessCheck() {
  livenessVerified = true;
  livenessTimestamp = Date.now();
  
  showLivenessInstruction('fa-circle-check', '✓ 人臉識別通過！');
  showDetectionInfo('✓ 人臉識別通過,開始識別人臉', 'success');
  
  setTimeout(() => {
    hideLivenessInstruction();
    livenessProgress.style.display = 'none';
  }, 2000);
  
  console.log('✓ 人臉識別完成');
}

// 搖頭檢測（左右轉動頭部）
function checkHeadTurn(detection) {
  const nose = detection.landmarks.getNose();
  const leftEye = detection.landmarks.getLeftEye();
  const rightEye = detection.landmarks.getRightEye();
  
  const noseTip = nose[3];
  const leftEyeCenter = getCenterPoint(leftEye);
  const rightEyeCenter = getCenterPoint(rightEye);
  
  const eyesCenterX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
  const offset = noseTip.x - eyesCenterX;
  const eyeDistance = Math.abs(leftEyeCenter.x - rightEyeCenter.x);
  const normalizedOffset = offset / eyeDistance;
  
  headPoseHistory.push(normalizedOffset);
  if (headPoseHistory.length > 30) headPoseHistory.shift();
  
  // 檢查是否有明顯的左右轉動
  if (headPoseHistory.length >= 20) {
    const maxOffset = Math.max(...headPoseHistory);
    const minOffset = Math.min(...headPoseHistory);
    const range = maxOffset - minOffset;
    
    // 如果偏移範圍超過0.5，表示有左右轉動
    if (range > 0.5) {
      completeCurrentAction();
    }
  }
}

// 輔助函數
function euclidean(p1, p2) {
  return Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
}

function getCenterPoint(points) {
  const x = points.reduce((sum, p) => sum + p.x, 0) / points.length;
  const y = points.reduce((sum, p) => sum + p.y, 0) / points.length;
  return { x, y };
}

// ==================== 人臉檢測和識別 ====================

async function detectAndRecognize(){
  if(!modelsLoaded || isProcessing) return;
  
  const detection = await faceapi
    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();
  
  if(detection){
    // 繪製檢測框
    const dims = faceapi.matchDimensions(canvas, video, true);
    const resizedDetection = faceapi.resizeResults(detection, dims);
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    faceapi.draw.drawDetections(canvas, resizedDetection);
    faceapi.draw.drawFaceLandmarks(canvas, resizedDetection);
    
    // 如果正在進行活體檢測
    if (currentActionIndex < challengeActions.length && !livenessVerified) {
      const currentAction = challengeActions[currentActionIndex];
      currentAction.check(detection);
      return;
    }
    
    // 活體檢測通過後，進行人臉識別
    if (livenessVerified) {
      currentDescriptor = detection.descriptor;
      await recognizeFace(currentDescriptor);
    }
  } else {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (!livenessVerified) {
      // 還在活體檢測階段
      if (currentActionIndex < challengeActions.length) {
        showDetectionInfo('⚠ 請將臉部對準框架', 'warning');
      }
    } else {
      // 已通過活體檢測但沒偵測到人臉
      recognizedEmployee = null;
      currentDescriptor = null;
      btnFaceIn.disabled = true;
      btnFaceOut.disabled = true;
    }
  }
}

// 呼叫後端識別 API
async function recognizeFace(descriptor){
  if(isProcessing) return;
  
  try {
    // 取 DEVICE_TOKEN（先使用伺服器注入的全域常數，沒有再從 localStorage）
    const token = (typeof DEVICE_TOKEN !== 'undefined' && DEVICE_TOKEN) 
                  ? DEVICE_TOKEN 
                  : (localStorage.getItem('device_token') || '');
    
    const headers = { 'Content-Type': 'application/json' };
    if (token) {
      headers['X-Device-Token'] = token;
    }
    
    const response = await fetch(API_FACE_RECOGNIZE, {
      method: 'POST',
      headers: headers,
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
  
  // 檢查活體檢測是否過期（30秒）
  const now = Date.now();
  if (now - livenessTimestamp > 30000) {
    showMsg(false, '人臉識別已過期，請重新開始');
    livenessVerified = false;
    recognizedEmployee = null;
    btnFaceIn.disabled = true;
    btnFaceOut.disabled = true;
    startLivenessCheck();
    return;
  }
  
  isProcessing = true;
  const btn = action === 'in' ? btnFaceIn : btnFaceOut;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<span class="loading-spinner"></span>處理中...';
  btn.disabled = true;
  
  try {
    // 取 DEVICE_TOKEN（先使用伺服器注入的全域常數，沒有再從 localStorage）
    const token = (typeof DEVICE_TOKEN !== 'undefined' && DEVICE_TOKEN) 
                  ? DEVICE_TOKEN 
                  : (localStorage.getItem('device_token') || '');
    
    const headers = { 'Content-Type': 'application/json' };
    if (token) {
      headers['X-Device-Token'] = token;
    }
    
    const response = await fetch(API_FACE_PUNCH, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({
        emp_code: recognizedEmployee.code,
        action: action,
        descriptor: Array.from(currentDescriptor),
        liveness_passed: true,
        liveness_method: 'head_turn',
        liveness_timestamp: Math.floor(livenessTimestamp / 1000)
      }),
      credentials: 'include'
    });
    const result = await response.json();
    
    if(result.ok || result.success){
      showMsg(true, `${result.message}: ${recognizedEmployee.name}`);
      showDetectionInfo(`✓ ${action === 'in' ? '上班' : '下班'}打卡成功!`, 'success');
      
      // 成功後重置
      setTimeout(() => {
        recognizedEmployee = null;
        currentDescriptor = null;
        livenessVerified = false;
        hideDetectionInfo();
        startLivenessCheck();
      }, 3000);
    } else {
      throw new Error(result.message || result.error || result.detail || '打卡失敗');
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
  console.log('=== 初始化人臉識別系統 ===');
  
  if (typeof faceapi === 'undefined') {
    console.error('Face-API.js not loaded!');
    showMsg(false, '人臉識別庫載入失敗,請重新整理頁面');
    showDetectionInfo('✗ 系統載入失敗', 'error');
    return;
  }
  
  console.log('✓ Face-API.js loaded');
  
  await loadModels();
  await startCamera();
  
  // 開始活體檢測
  startLivenessCheck();
  
  // 每100ms檢測一次
  setInterval(detectAndRecognize, 100);
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