// ==================== 初始化設定 ====================

// 顯示今天日期
document.addEventListener('DOMContentLoaded', () => {
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long'
        });
    }
});

// 側欄收合功能
const sidebarToggle = document.getElementById('sidebarToggle');
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.toggle('sb-sidenav-toggled');
    });
}

// API 設定
const API_URL = '/lamian-ukn/api/api_employees.php';  // ✅ 修正路徑
let EMP_CACHE = [];

// ==================== 員工資料管理函式 ====================

// 載入員工資料
async function loadEmployees() {
    try {
        const res = await fetch(API_URL);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const apiResponse = await res.json();
        if (apiResponse.success) {
            EMP_CACHE = Array.isArray(apiResponse.data) ? apiResponse.data : [];
            renderEmployees(EMP_CACHE);
        } else {
            console.error('API 錯誤：', apiResponse.message);
            document.getElementById('employeeTable').innerHTML =
                `<tr><td colspan="12" class="text-center text-danger">載入失敗：${apiResponse.message}</td></tr>`;
        }
    } catch (e) {
        console.error('載入員工資料失敗:', e);
        document.getElementById('employeeTable').innerHTML =
            '<tr><td colspan="12" class="text-center text-muted">載入資料失敗，請檢查伺服器連線。</td></tr>';
    }
}

// 渲染員工資料到表格
function renderEmployees(list) {
    const html = list.map(emp => {
        // ✅ 統一欄位名稱 (從 API 讀取)
        const id = emp.id ?? '';
        const name = emp.name ?? '';
        const birth_date = emp.birth_date ?? '';
        const telephone = emp.telephone ?? '';
        const email = emp.email ?? '';
        const address = emp.address ?? '';
        const id_card = emp.id_card ?? '';
        
        // 🔥 修正：API 回傳的是 role (A/B/C) 和 position (職位)
        const role = emp.role ?? ''; // 這裡會顯示 A, B, C
        const position = emp.position ?? '';
        
        // 判斷薪資類型
        const employmentType = (emp.base_salary && emp.base_salary > 0) ? '正職' : '臨時員工';
        const base_salary = (employmentType === '正職') ? (emp.base_salary ?? 'N/A') : 'N/A';
        const hourly_rate = (employmentType === '臨時員工') ? (emp.hourly_rate ?? 'N/A') : 'N/A';

        return `<tr>
            <td>${id}</td>
            <td>${name}</td>
            <td>${birth_date}</td>
            <td>${telephone}</td>
            <td>${email}</td>
            <td>${address}</td>
            <td>${id_card}</td>
            
            <td>${role}</td> 
            <td>${position}</td>

            <td>${base_salary}</td>
            <td>${hourly_rate}</td>
            <td>
                <button class="btn btn-primary btn-sm me-2" onclick="editEmployee('${id}')">編輯</button>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployee('${id}')">刪除</button>
            </td>
        </tr>`;
    }).join('');

    document.getElementById('employeeTable').innerHTML =
        html || '<tr><td colspan="12" class="text-center text-muted">目前沒有資料</td></tr>';
}

// ==================== 搜尋功能 ====================
function searchEmployees() {
    const query = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    if (!query) {
        // ✅ 修正：原本呼叫 loadEmployees() 會重新請求 API，
        // 改成 renderEmployees(EMP_CACHE) 直接從快取還原
        renderEmployees(EMP_CACHE); 
        return;
    }
    const filtered = EMP_CACHE.filter(emp =>
        String(emp.id ?? '').toLowerCase().includes(query) ||
        String(emp.name ?? '').toLowerCase().includes(query) ||
        String(emp.email ?? '').toLowerCase().includes(query)
    );
    renderEmployees(filtered);
}

// ==================== 刪除功能 ====================
async function deleteEmployee(id) {
    if (!confirm('確定要刪除這位員工嗎？')) return;
    try {
        const res = await fetch(API_URL, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }) // API 會接收 { "id": "A100000" }
        });
        const apiResponse = await res.json();
        if (apiResponse.success) {
            alert('刪除成功！');
            loadEmployees(); // 重新載入
        } else {
            alert('刪除失敗：' + apiResponse.message);
        }
    } catch (e) {
        console.error('刪除請求失敗:', e);
        alert('刪除請求失敗，請檢查網路連線。');
    }
}

// ==================== 新增功能 ====================

// 開啟新增員工Modal
function openAddEmployeeModal() {
    // 清空表單
    document.getElementById('addEmployeeForm').reset();
    clearAddFormValidation();
    
    // 🔥 保留：原始的薪資欄位隱藏邏輯
    document.getElementById('addBaseSalaryGroup').style.display = 'none';
    document.getElementById('addHourlyRateGroup').style.display = 'none';
    document.getElementById('addSalaryHint').style.display = 'block';
    
    // 顯示Modal
    const addModal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));
    addModal.show();
}

// 清除新增表單驗證狀態
function clearAddFormValidation() {
    const invalidFields = document.querySelectorAll('#addEmployeeModal .is-invalid');
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
    
    // 清除錯誤訊息
    const feedbacks = document.querySelectorAll('#addEmployeeModal .invalid-feedback');
    feedbacks.forEach(feedback => feedback.textContent = '');
}

// 🔥 保留：原始的「雇用類別」變更事件 (ID: addRole)
document.addEventListener('DOMContentLoaded', function() {
    const addRoleSelect = document.getElementById('addRole'); // 監聽 "正職/臨時工"
    if (addRoleSelect) {
        addRoleSelect.addEventListener('change', function() {
            const role = this.value;
            const baseSalaryGroup = document.getElementById('addBaseSalaryGroup');
            const hourlyRateGroup = document.getElementById('addHourlyRateGroup');
            const salaryHint = document.getElementById('addSalaryHint');
            
            // 清除之前的值和驗證狀態
            const baseSalaryField = document.getElementById('addBaseSalary');
            const hourlyRateField = document.getElementById('addHourlyRate');
            
            if (baseSalaryField) baseSalaryField.value = '';
            if (hourlyRateField) hourlyRateField.value = '';
            
            baseSalaryField?.classList.remove('is-invalid');
            hourlyRateField?.classList.remove('is-invalid');
            
            if (role === '正職') {
                baseSalaryGroup.style.display = 'block';
                hourlyRateGroup.style.display = 'none';
                salaryHint.style.display = 'none';
                if (baseSalaryField) baseSalaryField.required = true;
                if (hourlyRateField) hourlyRateField.required = false;
            } else if (role === '臨時員工') {
                baseSalaryGroup.style.display = 'none';
                hourlyRateGroup.style.display = 'block';
                salaryHint.style.display = 'none';
                if (baseSalaryField) baseSalaryField.required = false;
                if (hourlyRateField) hourlyRateField.required = true;
            } else {
                baseSalaryGroup.style.display = 'none';
                hourlyRateGroup.style.display = 'none';
                salaryHint.style.display = 'block';
                if (baseSalaryField) baseSalaryField.required = false;
                if (hourlyRateField) hourlyRateField.required = false;
            }
        });
    }
});

// 提交新增員工表單
async function submitAddEmployee() {
    try {
        // 清除之前的驗證狀態
        clearAddFormValidation();
        
        // 檢查必要元素是否存在
        const requiredElements = [
            'addName', 'addBirthDate', 'addIdCard', 'addTelephone', 
            'addEmail', 'addAddress', 
            'addRole', // 雇用類別 (正/臨)
            'addPermissionLevel', // 🔥 新增：權限等級 (A/B/C)
            'addPosition'
        ];
        
        for (const elementId of requiredElements) {
            if (!document.getElementById(elementId)) {
                console.error(`缺少必要的表單元素: ${elementId}`);
                alert(`表單初始化錯誤：缺少 ${elementId} 欄位`);
                return;
            }
        }
        
        // 收集表單資料
        const formData = {
            name: document.getElementById('addName').value.trim(),
            birth_date: document.getElementById('addBirthDate').value,
            ID_card: document.getElementById('addIdCard').value.trim().toUpperCase(),
            Telephone: document.getElementById('addTelephone').value.trim(),
            email: document.getElementById('addEmail').value.trim(),
            address: document.getElementById('addAddress').value.trim(),
            
            // 🔥 修改：role 的值來自 addPermissionLevel (A/B/C)
            role: document.getElementById('addPermissionLevel').value, 
            
            Position: document.getElementById('addPosition').value.trim(),
            base_salary: null,
            hourly_rate: null
        };

        // 🔥 保留：讀取「雇用類別」的值 (僅供前端驗證使用)
        const employmentType = document.getElementById('addRole').value;

        // 🔥 保留：根據雇用類別設定薪資
        if (employmentType === '正職') {
            const baseSalaryInput = document.getElementById('addBaseSalary');
            if (baseSalaryInput) {
                const baseSalary = baseSalaryInput.value.trim();
                formData.base_salary = baseSalary ? parseInt(baseSalary) : null;
            }
        } else if (employmentType === '臨時員工') {
            const hourlyRateInput = document.getElementById('addHourlyRate');
            if (hourlyRateInput) {
                const hourlyRate = hourlyRateInput.value.trim();
                formData.hourly_rate = hourlyRate ? parseInt(hourlyRate) : null;
            }
        }

        // 前端驗證
        let isValid = true;

        // 姓名驗證
        if (!formData.name) {
            showAddFieldError('addName', '請輸入姓名');
            isValid = false;
        } else if (formData.name.length > 50) {
            showAddFieldError('addName', '姓名長度不可超過50個字');
            isValid = false;
        }

        // 出生日期驗證
        if (!formData.birth_date) {
            showAddFieldError('addBirthDate', '請選擇出生年月日');
            isValid = false;
        } else {
            const birthDate = new Date(formData.birth_date);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear() - 
                       (today < new Date(today.getFullYear(), birthDate.getMonth(), birthDate.getDate()) ? 1 : 0);
            
            if (isNaN(birthDate.getTime())) {
                showAddFieldError('addBirthDate', '無效的日期格式');
                isValid = false;
            } else if (age < 16 || age > 80) {
                showAddFieldError('addBirthDate', '年齡必須在16-80歲之間');
                isValid = false;
            } else if (birthDate > today) {
                showAddFieldError('addBirthDate', '出生日期不能是未來日期');
                isValid = false;
            }
        }

        // 身份證字號驗證
        if (!formData.ID_card) {
            showAddFieldError('addIdCard', '請輸入身份證字號');
            isValid = false;
        } else if (!validateIdCard(formData.ID_card)) {
            showAddFieldError('addIdCard', '身份證字號格式不正確或檢查碼錯誤');
            isValid = false;
        }

        // 電話號碼驗證
        if (!formData.Telephone) {
            showAddFieldError('addTelephone', '請輸入電話號碼');
            isValid = false;
        } else {
            // 🔥 保持您原始的寬鬆驗證 (09 或 市話)
            const phonePattern = /^(09\d{8}|0\d{1,2}-?\d{6,8}|\d{2,4}-?\d{6,8})$/;
            if (!phonePattern.test(formData.Telephone.replace(/\s+/g, ''))) {
                showAddFieldError('addTelephone', '電話號碼格式不正確');
                isValid = false;
            }
        }

        // Email驗證 (選填,但若填寫則需驗證格式)
        if (formData.email && !validateEmail(formData.email)) {
            showAddFieldError('addEmail', '請輸入正確的Email格式');
            isValid = false;
        }

        // 地址驗證
        if (!formData.address) {
            showAddFieldError('addAddress', '請輸入地址');
            isValid = false;
        } else if (formData.address.length > 200) {
            showAddFieldError('addAddress', '地址長度不可超過200個字');
            isValid = false;
        }

        // 🔥 保留：雇用類別驗證 (正職/臨時工)
        if (!employmentType || !['正職', '臨時員工'].includes(employmentType)) {
            showAddFieldError('addRole', '請選擇有效的雇用類別');
            isValid = false;
        }
        
        // 🔥 新增：權限等級驗證 (A/B/C)
        if (!formData.role || !['A', 'B', 'C'].includes(formData.role)) {
            showAddFieldError('addPermissionLevel', '請選擇有效的權限等級');
            isValid = false;
        }

        // 職位驗證
        if (!formData.Position) {
            showAddFieldError('addPosition', '請輸入職位');
            isValid = false;
        } else if (formData.Position.length > 50) {
            showAddFieldError('addPosition', '職位名稱長度不可超過50個字');
            isValid = false;
        }

        // 🔥 保留：原始的薪資驗證 (根據 "雇用類別" 決定是否必填)
        if (employmentType === '正職') {
            if (!formData.base_salary || formData.base_salary <= 0) {
                showAddFieldError('addBaseSalary', '請輸入有效的底薪金額（大於0）');
                isValid = false;
            } else if (formData.base_salary > 10000000) {
                showAddFieldError('addBaseSalary', '底薪金額不可超過一千萬');
                isValid = false;
            }
        } else if (employmentType === '臨時員工') {
            if (!formData.hourly_rate || formData.hourly_rate <= 0) {
                showAddFieldError('addHourlyRate', '請輸入有效的時薪金額（大於0）');
                isValid = false;
            } else if (formData.hourly_rate > 10000) {
                showAddFieldError('addHourlyRate', '時薪金額不可超過一萬元');
                isValid = false;
            }
        }

        if (!isValid) {
            return;
        }

        // 檢查 API_URL 是否已定義
        if (typeof API_URL === 'undefined') {
            console.error('API_URL 未定義');
            alert('系統設定錯誤：API_URL 未定義，請檢查設定');
            return;
        }

        console.log('準備送出的資料 (role 將送出 A/B/C):', formData);
        
        // 顯示載入狀態
        const submitBtn = document.querySelector('#addEmployeeModal .btn-primary');
        if (!submitBtn) {
            console.error('找不到提交按鈕');
            alert('介面錯誤：找不到提交按鈕');
            return;
        }
        
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>新增中...';
        submitBtn.disabled = true;

        // 發送請求
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        console.log('HTTP Status:', response.status);
        console.log('Response Headers:', Object.fromEntries(response.headers.entries()));
        
        // 檢查 HTTP 狀態碼
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP 錯誤:', response.status, errorText);
            
            if (response.status === 404) {
                alert('API 端點不存在，請檢查 API_URL 設定');
            } else if (response.status === 500) {
                alert('伺服器內部錯誤，請聯繫系統管理員');
            } else {
                alert(`請求失敗 (HTTP ${response.status}): ${errorText}`);
            }
            return;
        }
        
        // 檢查回應內容類型
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('非 JSON 回應:', textResponse);
            console.error('Content-Type:', contentType);
            alert('伺服器回應格式錯誤，請檢查 API 是否正常運作\n' + 
                  '預期：application/json\n' + 
                  '實際：' + (contentType || '無'));
            return;
        }

        const result = await response.json();
        console.log('API 回應:', result);

        if (result.success) {
            const message = `員工新增成功！\n員工編號：${result.data.employee_id}\n登入帳號：${result.data.account}`;
            alert(message);
            
            // 關閉Modal
            const addModal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal'));
            if (addModal) {
                addModal.hide();
            }
            
            // 重新載入資料
            if (typeof loadEmployees === 'function') {
                loadEmployees();
            } else {
                console.warn('loadEmployees 函數不存在，請手動刷新頁面');
            }
        } else {
            const errorMessage = result.message || result.error || '未知錯誤';
            alert('新增失敗：' + errorMessage);
            console.error('API 錯誤詳情:', result);
        }

    } catch (error) {
        console.error('新增員工失敗:', error);
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            alert('網路連線錯誤，請檢查網路狀態或伺服器是否正常運作');
        } else if (error.name === 'SyntaxError') {
            alert('伺服器回應格式錯誤，請聯繫系統管理員');
        } else {
            alert('新增失敗：' + error.message);
        }
    } finally {
        // 恢復按鈕狀態
        const submitBtn = document.querySelector('#addEmployeeModal .btn-primary');
        if (submitBtn) {
            submitBtn.innerHTML = '新增員工'; // 恢復原始文字
            submitBtn.disabled = false;
        }
    }
}

// 顯示新增表單欄位錯誤
function showAddFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) {
        console.error(`找不到欄位: ${fieldId}`);
        return;
    }
    
    field.classList.add('is-invalid');
    
    // 尋找或建立錯誤訊息元素
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = field.parentNode.querySelector('.invalid-feedback');
    }
    
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        feedback.textContent = message;
    } else {
        console.warn(`找不到 ${fieldId} 的錯誤訊息元素`);
    }
}

// ==================== 驗證函式 ====================

// Email格式驗證函式
function validateEmail(email) {
    if (!email) return true; // email為選填,空值視為有效
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailPattern.test(email);
}

// 驗證身份證字號（包含檢查碼）
function validateIdCard(id) {
    // 基本格式檢查
    if (!id || typeof id !== 'string') {
        return false;
    }
    
    // 轉為大寫並檢查格式
    id = id.toUpperCase();
    if (!id.match(/^[A-Z][12]\d{8}$/)) {
        console.log('身份證格式錯誤:', id);
        return false;
    }
    
    // 英文字母對應數字
    const letters = {
        'A': 10, 'B': 11, 'C': 12, 'D': 13, 'E': 14, 'F': 15,
        'G': 16, 'H': 17, 'I': 34, 'J': 18, 'K': 19, 'L': 20,
        'M': 21, 'N': 22, 'O': 35, 'P': 23, 'Q': 24, 'R': 25,
        'S': 26, 'T': 27, 'U': 28, 'V': 29, 'W': 32, 'X': 30,
        'Y': 31, 'Z': 33
    };
    
    const firstLetter = id[0];
    const letterValue = letters[firstLetter];
    
    if (!letterValue) {
        console.log('無效的身份證字母:', firstLetter);
        return false;
    }
    
    // 計算檢查碼
    let sum = Math.floor(letterValue / 10) + (letterValue % 10) * 9;
    
    for (let i = 1; i <= 8; i++) {
        const digit = parseInt(id[i]);
        if (isNaN(digit)) {
            console.log('身份證包含非數字字符:', id[i]);
            return false;
        }
        sum += digit * (9 - i);
    }
    
    const checkDigit = (10 - (sum % 10)) % 10;
    const actualCheckDigit = parseInt(id[9]);
    const isValid = checkDigit === actualCheckDigit;
    
    if (!isValid) {
        console.log('身份證檢查碼驗證失敗:', {
            身份證: id,
            預期檢查碼: checkDigit,
            實際檢查碼: actualCheckDigit
        });
    }
    
    return isValid;
}

// ==================== 輸入格式化（新增表單） ====================

document.addEventListener('DOMContentLoaded', function() {
    // 身份證字號自動轉大寫
    const addIdCardField = document.getElementById('addIdCard');
    if (addIdCardField) {
        addIdCardField.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // 限制數字欄位只能輸入正整數
    ['addBaseSalary', 'addHourlyRate'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 1) {
                    this.value = parseInt(this.value).toString();
                }
            });
        }
    });

    // 電話號碼格式化
    const addTelephoneField = document.getElementById('addTelephone');
    if (addTelephoneField) {
        addTelephoneField.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9-]/g, '');
        });
    }
});


// ==================== 🔥🔥 編輯功能 (已修正) 🔥🔥 ====================

// 開啟編輯員工Modal
function editEmployee(id) {
    // 1. 從快取中找到要編輯的員工資料
    const empToEdit = EMP_CACHE.find(emp => emp.id == id);
    if (!empToEdit) {
        alert('找不到員工資料！');
        return;
    }

    // 2. 將員工資料填入彈出視窗的表單中
    document.getElementById('editId').value = empToEdit.id;
    document.getElementById('editName').value = empToEdit.name;
    
    // 處理生日格式 - 確保是 YYYY-MM-DD 格式
    let birthDateValue = empToEdit.birth_date || empToEdit.birthDate || '';
    if (birthDateValue) {
        const dateObj = new Date(birthDateValue);
        if (!isNaN(dateObj.getTime())) {
            const year = dateObj.getFullYear();
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            const day = String(dateObj.getDate()).padStart(2, '0');
            birthDateValue = `${year}-${month}-${day}`;
        }
    }
    document.getElementById('editBirthDate').value = birthDateValue;
    
    // 🔥🔥【修正】: 填入「權限等級」 (A/B/C)
    document.getElementById('editPermissionLevel').value = empToEdit.role;
    
    // 統一使用正確的欄位名稱
    document.getElementById('editPosition').value = empToEdit.Position || empToEdit.position || '';
    document.getElementById('editTelephone').value = empToEdit.Telephone || empToEdit.telephone || '';
    document.getElementById('editEmail').value = empToEdit.email || ''; 
    document.getElementById('editAddress').value = empToEdit.address;
    document.getElementById('editIdCard').value = empToEdit.ID_card || empToEdit.id_card || '';

    // 3. 根據雇用類別顯示或隱藏底薪或時薪欄位
    // (判斷：如果 base_salary > 0 視為正職)
    if (empToEdit.base_salary > 0) {
        document.getElementById('editRole').value = '正職';
        document.getElementById('editBaseSalary').value = empToEdit.base_salary || '';
        document.getElementById('editBaseSalaryGroup').style.display = 'block';
        document.getElementById('editHourlyRateGroup').style.display = 'none';
    } else {
        document.getElementById('editRole').value = '臨時員工';
        document.getElementById('editHourlyRate').value = empToEdit.hourly_rate || '';
        document.getElementById('editBaseSalaryGroup').style.display = 'none';
        document.getElementById('editHourlyRateGroup').style.display = 'block';
    }

    // 4. 顯示彈出視窗
    const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    editModal.show();
}

// 提交編輯表單
async function submitEdit() {
    // 收集表單資料
    const id = document.getElementById('editId').value;
    const name = document.getElementById('editName').value.trim();
    const birth_date = document.getElementById('editBirthDate').value;
    
    // 🔥🔥【修正】: 從 'editPermissionLevel' 讀取 A/B/C
    const role = document.getElementById('editPermissionLevel').value;
    
    const position = document.getElementById('editPosition').value.trim();
    const telephone = document.getElementById('editTelephone').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const address = document.getElementById('editAddress').value.trim();
    const id_card = document.getElementById('editIdCard').value.trim();
    
    // 🔥🔥【修正】: 讀取 'editRole' (正職/臨時) 僅用於判斷薪資
    const employmentType = document.getElementById('editRole').value;

    // 驗證必填欄位 (🔥 修正：加入 editPermissionLevel 驗證)
    if (!id || !name || !birth_date || !role || !position || !telephone || !address || !id_card) {
        // 🔥🔥【修正】: 這裡的錯誤就是你看到的 "缺少員工 ID"
        // 雖然我們的主 bug 是 parseInt，但這個檢查也要確保 ID 真的有值
        if (!id) {
             alert('更新失敗：缺少員工 ID');
             return;
        }
        alert('請填寫所有必填欄位！');
        return;
    }

    // Email驗證
    if (email && !validateEmail(email)) {
        alert('請輸入正確的Email格式');
        return;
    }

    // 根據雇用類別收集正確的薪資資料
    let base_salary = null;
    let hourly_rate = null;
    
    if (employmentType === '正職') {
        const salaryValue = document.getElementById('editBaseSalary').value.trim();
        base_salary = salaryValue ? parseInt(salaryValue) : null;
    } else if (employmentType === '臨時員工') {
        const rateValue = document.getElementById('editHourlyRate').value.trim();
        hourly_rate = rateValue ? parseInt(rateValue) : null;
    }

    // 組織要提交的資料
    const data = {
        // 🔥🔥【修正】: ID 直接傳送字串，不要 parseInt
        id: id,
        name: name,
        birth_date: birth_date,
        role: role, // 🔥 這裡現在送出的是 A/B/C
        Position: position,
        Telephone: telephone,
        email: email,
        address: address,
        ID_card: id_card,
        base_salary: base_salary,
        hourly_rate: hourly_rate,
        password_hash: '' // 假設 API 會忽略這個欄位
    };

    try {
        const res = await fetch(API_URL, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const apiResponse = await res.json();
        
        if (apiResponse.success) {
            alert('更新成功！');
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal'));
            editModal.hide();
            loadEmployees();
        } else {
            // 這裡會顯示來自 API 的錯誤，例如 "缺少員工 ID"
            alert('更新失敗：' + apiResponse.message);
        }
    } catch (e) {
        console.error('更新請求失敗:', e);
        alert('更新請求失敗，請檢查網路連線。');
    }
}

// ==================== 編輯表單事件監聽器 ====================

document.addEventListener('DOMContentLoaded', function() {
    // 雇用類別變更時動態顯示或隱藏薪資欄位
    const editRoleSelect = document.getElementById('editRole');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', function() {
            if (this.value === '正職') {
                document.getElementById('editBaseSalaryGroup').style.display = 'block';
                document.getElementById('editHourlyRateGroup').style.display = 'none';
                document.getElementById('editHourlyRate').value = '';
            } else {
                document.getElementById('editBaseSalaryGroup').style.display = 'none';
                document.getElementById('editHourlyRateGroup').style.display = 'block';
                document.getElementById('editBaseSalary').value = '';
            }
        });
    }

    // 編輯表單的身份證字號自動轉大寫
    const editIdCardField = document.getElementById('editIdCard');
    if (editIdCardField) {
        editIdCardField.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // 編輯表單的數字欄位格式化
    ['editBaseSalary', 'editHourlyRate'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 1) {
                    this.value = parseInt(this.value).toString();
                }
            });
        }
    });

    // 編輯表單的電話號碼格式化
    const editTelephoneField = document.getElementById('editTelephone');
    if (editTelephoneField) {
        editTelephoneField.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9-]/g, '');
        });
    }
});

// ==================== 初始化載入 ====================
document.addEventListener('DOMContentLoaded', function () {
    // 您的 員工資料表.php 檔案中，頁尾的 <script> 區塊
    // 已經有 DOMContentLoaded 事件了，並會在 loadLoggedInUser 之後
    // 呼叫 loadEmployees()。
    // 所以這裡不需要再呼叫一次。
});