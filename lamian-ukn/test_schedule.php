<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>班表API測試工具</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; background: #f5f5f5; }
        .test-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .btn-test { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🧪 班表API測試工具</h1>
        
        <!-- 當前登入狀態 -->
        <div class="test-card">
            <h3>👤 當前登入狀態</h3>
            <p><strong>用戶ID:</strong> <code id="currentUid">檢查中...</code></p>
            <p><strong>用戶名稱:</strong> <code id="currentName">檢查中...</code></p>
        </div>

        <!-- 測試 1: 查詢班表 -->
        <div class="test-card">
            <h3>📋 測試 1: 查詢本週班表 (GET)</h3>
            <div class="mb-3">
                <label class="form-label">選擇週一日期:</label>
                <input type="date" class="form-control" id="testDate1" style="max-width: 200px;">
            </div>
            <button class="btn btn-primary btn-test" onclick="testGetSchedule()">🔍 查詢班表</button>
            <div id="result1" class="mt-3"></div>
        </div>

        <!-- 測試 2: 新增班表 -->
        <div class="test-card">
            <h3>✏️ 測試 2: 新增班表 (POST)</h3>
            <p class="text-muted">自動產生本週的測試資料</p>
            <div class="mb-3">
                <label class="form-label">選擇週一日期:</label>
                <input type="date" class="form-control" id="testDate2" style="max-width: 200px;">
            </div>
            <button class="btn btn-success btn-test" onclick="testPostSchedule()">➕ 新增班表</button>
            <button class="btn btn-warning btn-test" onclick="testPostCustomSchedule()">➕ 自訂班表</button>
            <div id="result2" class="mt-3"></div>
            
            <!-- 自訂班表區域 -->
            <div id="customScheduleArea" style="display:none;" class="mt-3">
                <h5>自訂班表內容:</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label>日期:</label>
                        <input type="date" class="form-control mb-2" id="customDate">
                    </div>
                    <div class="col-md-3">
                        <label>開始時間:</label>
                        <input type="time" class="form-control mb-2" id="customStart" value="09:00">
                    </div>
                    <div class="col-md-3">
                        <label>結束時間:</label>
                        <input type="time" class="form-control mb-2" id="customEnd" value="17:00">
                    </div>
                </div>
                <button class="btn btn-primary mt-2" onclick="submitCustomSchedule()">送出</button>
            </div>
        </div>

        <!-- 查看除錯日誌 -->
        <div class="test-card">
            <h3>📝 除錯建議</h3>
            <ul>
                <li>如果測試失敗,請查看 <code>班表_debug.log</code> 檔案</li>
                <li>打開瀏覽器 DevTools (F12) 查看 Network 和 Console</li>
                <li>確認已登入系統</li>
            </ul>
            <a href="check_session.php" class="btn btn-info" target="_blank">🔍 檢查Session狀態</a>
        </div>
    </div>

    <script>
        // 取得當前週一
        function getMonday(d = new Date()) {
            const date = new Date(d);
            const day = (date.getDay() + 6) % 7;
            date.setDate(date.getDate() - day);
            return date;
        }

        function formatDate(d) {
            return d.toISOString().slice(0, 10);
        }

        // 初始化日期
        const today = new Date();
        const monday = getMonday(today);
        document.getElementById('testDate1').value = formatDate(monday);
        document.getElementById('testDate2').value = formatDate(monday);
        document.getElementById('customDate').value = formatDate(monday);

        // 檢查登入狀態
        async function checkLogin() {
            try {
                const res = await fetch('check_session.php');
                const html = await res.text();
                
                // 簡單解析 (實際應該用 API)
                const uidMatch = html.match(/uid 值<\/th>\s*<td><code>([^<]+)<\/code>/);
                const nameMatch = html.match(/name 值<\/th>\s*<td><code>([^<]+)<\/code>/);
                
                if (uidMatch) document.getElementById('currentUid').textContent = uidMatch[1];
                if (nameMatch) document.getElementById('currentName').textContent = nameMatch[1];
            } catch (e) {
                document.getElementById('currentUid').textContent = '無法檢查';
                document.getElementById('currentName').textContent = '無法檢查';
            }
        }
        checkLogin();

        // 測試 GET
        async function testGetSchedule() {
            const date = document.getElementById('testDate1').value;
            const resultDiv = document.getElementById('result1');
            
            resultDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">載入中...</span></div>';
            
            try {
                const res = await fetch(`班表.php?start=${date}`);
                const data = await res.json();
                
                resultDiv.innerHTML = `
                    <div class="alert ${data.rows ? 'alert-success' : 'alert-warning'}">
                        <strong>回應狀態:</strong> ${res.status} ${res.statusText}
                    </div>
                    <h5>回應內容:</h5>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (e) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>錯誤:</strong> ${e.message}
                    </div>
                `;
            }
        }

        // 測試 POST (自動產生資料)
        async function testPostSchedule() {
            const date = document.getElementById('testDate2').value;
            const resultDiv = document.getElementById('result2');
            
            // 自動產生一週的測試資料
            const weekStart = new Date(date);
            const availability = {};
            
            for (let i = 0; i < 7; i++) {
                const currentDate = new Date(weekStart);
                currentDate.setDate(currentDate.getDate() + i);
                const dateStr = formatDate(currentDate);
                
                // 週一到週五給班表,週六日休息
                if (i < 5) {
                    availability[dateStr] = [
                        { start: '09:00', end: '17:00', note: '早班' }
                    ];
                }
            }
            
            await submitSchedule(availability, resultDiv);
        }

        // 顯示自訂表單
        function testPostCustomSchedule() {
            document.getElementById('customScheduleArea').style.display = 'block';
        }

        // 提交自訂班表
        async function submitCustomSchedule() {
            const date = document.getElementById('customDate').value;
            const start = document.getElementById('customStart').value;
            const end = document.getElementById('customEnd').value;
            const resultDiv = document.getElementById('result2');
            
            if (!date || !start || !end) {
                alert('請填寫完整資料');
                return;
            }
            
            const availability = {
                [date]: [
                    { start: start, end: end, note: '測試班次' }
                ]
            };
            
            await submitSchedule(availability, resultDiv);
        }

        // 送出班表
        async function submitSchedule(availability, resultDiv) {
            const weekStart = document.getElementById('testDate2').value;
            
            resultDiv.innerHTML = '<div class="spinner-border text-success" role="status"><span class="visually-hidden">送出中...</span></div>';
            
            const payload = {
                week_start: weekStart,
                availability: availability
            };
            
            console.log('送出資料:', payload);
            
            try {
                const res = await fetch('班表.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                
                resultDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <strong>回應狀態:</strong> ${res.status} ${res.statusText}
                    </div>
                    <h5>回應內容:</h5>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                    ${data.success ? '<div class="alert alert-info mt-3">✅ 新增成功!請用測試1查詢確認</div>' : ''}
                `;
                
                // 隱藏自訂表單
                document.getElementById('customScheduleArea').style.display = 'none';
            } catch (e) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>錯誤:</strong> ${e.message}
                    </div>
                `;
            }
        }
    </script>
</body>
</html>