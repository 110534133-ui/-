// ===== 日報表.js =====
// 🔥 已修正:formatDate 函式中的語法錯誤
// 🔥 (移除了外層 DOMContentLoaded)
// 🔥 (已修正 API 呼叫路徑)
// 🔥 新增:水電瓦斯重複檢查功能 (不允許覆蓋)
// 🔥 修正:新增 total_expense 欄位以符合資料表結構

// ===== DOM 元素 =====
const currentDateEl = document.getElementById("currentDate");
const reportDate = document.getElementById("report_date");
const weekdayInput = document.getElementById("weekday");
const filledBy = document.getElementById("filled_by");

const incomeInputs = document.querySelectorAll(".income");
const expenseInputs = document.querySelectorAll(".expense");
const cashInputs = document.querySelectorAll(".cash");

const totalIncomeEl = document.getElementById("total_income");
const fixedExpenseEl = document.getElementById("t_expense");
const variableExpenseEl = document.getElementById("total_variable");
const cashTotalEl = document.getElementById("cash_total");

const kpiIncomeEl = document.getElementById("kpi_income");
const kpiExpenseEl = document.getElementById("kpi_expense");
const kpiNetEl = document.getElementById("kpi_net");
const kpiDepositEl = document.getElementById("kpi_deposit");

const successAlert = document.getElementById("successAlert");
const warningAlert = document.getElementById("warningAlert");
const errorAlert = document.getElementById("errorAlert");
const successMessage = document.getElementById("successMessage");
const warningMessage = document.getElementById("warningMessage");
const errorMessage = document.getElementById("errorMessage");

const depositInput = document.getElementById("deposit_to_bank");

// 勾選啟用元素
const utilitiesCheckbox = document.getElementById("enable_utilities");
const utilitiesInput = document.getElementById("expense_utilities");
const utilityTermSelect = document.getElementById("utility_month");

const rentCheckbox = document.getElementById("enable_rent");
const rentInput = document.getElementById("expense_rent");
const rentPeriodSelect = document.getElementById("rent_period");
const seasonSelect = document.getElementById("season_months");

const rentStartInput = document.getElementById("rent_start");
const rentEndInput = document.getElementById("rent_end");
const saveRentBtn = document.getElementById("saveRentSetting");
const rentSettingHidden = document.getElementById("rent_setting");

const dailyReportForm = document.getElementById("dailyReportForm");

// ===== 初始化日期與星期 =====
function formatDate(date) {
  const y = date.getFullYear();
  const m = ("0" + (date.getMonth() + 1)).slice(-2);
  const d = ("0" + date.getDate()).slice(-2);
  return `${y}-${m}-${d}`;
}

function getWeekday(date) {
  const weekdays = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
  return weekdays[date.getDay()];
}

const today = new Date();
if (currentDateEl) currentDateEl.textContent = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;
if (reportDate) reportDate.value = formatDate(today);
if (weekdayInput) weekdayInput.value = getWeekday(today);

// ===== 載入人事成本 =====
async function loadLaborCost() {
    try {
        const res = await fetch('/lamian-ukn/人事計算.php');
        const data = await res.json();
        console.log("人事成本 API 回傳:", data);
        const el = document.getElementById("expense_salary");
        if (!el) {
            console.warn("找不到 expense_salary 元素");
            return;
        }
        el.value = data.total_labor_cost;
    } catch (err) {
        console.error("人事成本載入失敗:", err);
    }
}

/**
 * 自動填入「填表人」欄位
 * 從 Navbar 或 Sidebar 抓取已登入的用戶名稱
 */
function autoFillUserName() {
    if (!filledBy) {
        console.warn("在日報表頁面找不到 'filled_by' 欄位。");
        return;
    }

    const navUser = document.getElementById("navUserName");
    const sidebarUser = document.getElementById("loggedAs");
    
    let userName = "";
    
    if (navUser && navUser.textContent) {
        userName = navUser.textContent.trim();
    } else if (sidebarUser && sidebarUser.textContent) {
        userName = sidebarUser.textContent.trim();
    }
    
    if (userName && userName !== "訪客") {
        filledBy.value = userName;
        console.log("已自動填入填表人:" + userName);
    } else {
        console.warn("無法從 navUserName 或 loggedAs 獲取用戶名稱,請手動填寫「填表人」。");
    }
}

// 頁面載入完成後執行
document.addEventListener("DOMContentLoaded", () => {
    autoFillUserName();
    loadLaborCost().then(updateKPI);
});

// ===== 收入總計 =====
function calculateIncome() {
  let total = 0;
  incomeInputs.forEach(input => {
    const val = parseFloat(input.value) || 0;
    total += val;
  });
  if (totalIncomeEl) totalIncomeEl.textContent = total.toLocaleString();
  return total;
}

// ===== 支出總計 =====
function calculateExpense() {
  let fixedTotal = 0;
  let variableTotal = 0;
  expenseInputs.forEach(input => {
    const val = parseFloat(input.value) || 0;
    if (["expense_salary", "expense_rent", "expense_utilities"].includes(input.id)) {
      fixedTotal += val;
    } else {
      variableTotal += val;
    }
  });
  if (fixedExpenseEl) fixedExpenseEl.textContent = fixedTotal.toLocaleString();
  if (variableExpenseEl) variableExpenseEl.textContent = variableTotal.toLocaleString();
  return { fixedTotal, variableTotal };
}

// ===== 現金總計 =====
function calculateCash() {
  let total = 0;
  cashInputs.forEach(input => {
    const val = Number(input.value) || 0;
    const span = input.closest(".input-group")?.querySelector(".input-group-text");
    const denomination = span ? Number(span.textContent) : 0;
    total += val * denomination;
  });
  if (cashTotalEl) cashTotalEl.textContent = total.toLocaleString();
  return total;
}

// ===== KPI 更新 =====
function updateKPI() {
  const incomeTotal = calculateIncome();
  const { fixedTotal, variableTotal } = calculateExpense();
  const deposit = parseFloat(depositInput?.value) || 0;

  if (kpiIncomeEl) kpiIncomeEl.textContent = incomeTotal.toLocaleString();
  if (kpiExpenseEl) kpiExpenseEl.textContent = (fixedTotal + variableTotal).toLocaleString();
  if (kpiNetEl) kpiNetEl.textContent = (incomeTotal - (fixedTotal + variableTotal)).toLocaleString();
  if (kpiDepositEl) kpiDepositEl.textContent = deposit.toLocaleString();
}

// ===== 綁定輸入事件 =====
incomeInputs.forEach(input => input.addEventListener("input", updateKPI));
expenseInputs.forEach(input => input.addEventListener("input", updateKPI));
cashInputs.forEach(input => input.addEventListener("input", updateKPI));
if (depositInput) depositInput.addEventListener("input", updateKPI);

// ===== 勾選啟用控制 =====
if (utilitiesCheckbox) {
  utilitiesCheckbox.addEventListener("change", () => {
    const enabled = utilitiesCheckbox.checked;
    utilitiesInput.disabled = !enabled;
    utilityTermSelect.disabled = !enabled;
    
    // 如果取消勾選,清空數值
    if (!enabled) {
      utilitiesInput.value = "";
      utilityTermSelect.value = "term1";
    }
  });
}

if (rentCheckbox) {
  rentCheckbox.addEventListener("change", () => {
    const enabled = rentCheckbox.checked;
    rentInput.disabled = !enabled;
    rentPeriodSelect.disabled = !enabled;
    seasonSelect.disabled = !enabled;
    
    // 如果取消勾選,清空數值
    if (!enabled) {
      rentInput.value = "";
    }
  });
}

// ===== 租金設定 Modal 顯示/期別控制 =====
if (rentPeriodSelect) {
  rentPeriodSelect.addEventListener("change", () => {
    const seasonWrap = document.getElementById("season_wrap");
    if (rentPeriodSelect.value === "season") {
      seasonWrap.classList.remove("d-none");
    } else {
      seasonWrap.classList.add("d-none");
    }
  });
}

// ===== 租金日期即時檢查 =====
if (saveRentBtn) {
  saveRentBtn.addEventListener("click", async () => {
    if (typeof API_BASE === 'undefined') {
      alert('API_BASE 未定義');
      return;
    }
    
    const start = rentStartInput.value;
    const end = rentEndInput.value;
    const rentModalEl = document.getElementById("rentSettingModal");
    let rentModal = bootstrap.Modal.getOrCreateInstance(rentModalEl, { focus: false });

    if (!start || !end) {
      rentModal.hide();
      showAlert("warning", "請選擇完整的租金起訖日期!");
      return;
    }

    try {
      const checkRes = await fetch(`${API_BASE}/api_report_check.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ rent_start: start, rent_end: end })
      });
      const checkResult = await checkRes.json();

      if (!checkResult.success) {
        rentModal.hide();
        showAlert("warning", checkResult.message || "租金日期重疊");
        return;
      }

      const setting = {
        period: rentPeriodSelect.value,
        months: parseInt(seasonSelect.value) || 1,
        start,
        end
      };
      rentSettingHidden.value = JSON.stringify(setting);
      rentModal.hide();
      showAlert("success", "租金設定已儲存");

    } catch (err) {
      rentModal.hide();
      showAlert("error", "租金日期檢查錯誤:" + err.message);
    }
  });
}

// 🔥 ===== 新增:水電瓦斯重複檢查函數 ===== 🔥
/**
 * 檢查指定年份和期間的水電瓦斯是否已存在
 * @param {number} year - 年份
 * @param {string} term - 期間 (term1, term2, ...)
 * @returns {Promise<Object>} - { exists: boolean, data?: {...} }
 */
async function checkUtilitiesExist(year, term) {
    if (typeof API_BASE === 'undefined') {
        console.error('API_BASE 未定義');
        return { exists: false };
    }
    
    try {
        const response = await fetch(`${API_BASE}/api_report_check.php?year=${year}&term=${term}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('檢查失敗');
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('檢查水電瓦斯資料失敗:', error);
        return { exists: false };
    }
}

// ===== 沒回頂端 =====
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// ===== 共用通知顯示函式 =====
function showAlert(type, message) {
  // 先隱藏所有提示
  if (successAlert) successAlert.classList.add("d-none");
  if (warningAlert) warningAlert.classList.add("d-none");
  if (errorAlert) errorAlert.classList.add("d-none");

  // 根據類型顯示對應提示
  if (type === "success") {
    if (successMessage) successMessage.textContent = message;
    if (successAlert) successAlert.classList.remove("d-none");
  } else if (type === "warning") {
    if (warningMessage) warningMessage.textContent = message;
    if (warningAlert) warningAlert.classList.remove("d-none");
  } else if (type === "error") {
    if (errorMessage) errorMessage.textContent = message;
    if (errorAlert) errorAlert.classList.remove("d-none");
  }

  scrollToTop();
  
  // 🔥 自動隱藏提示 (錯誤訊息顯示較久)
  setTimeout(() => {
    if (type === "success" && successAlert) successAlert.classList.add("d-none");
    if (type === "warning" && warningAlert) warningAlert.classList.add("d-none");
    if (type === "error" && errorAlert) errorAlert.classList.add("d-none");
  }, type === "error" ? 8000 : 5000);
}

// ===== 將水電瓦斯選項轉換成文字 =====
function getUtilityTermText(value) {
  const mapping = {
    term1: "1–2月",
    term2: "3–4月",
    term3: "5–6月",
    term4: "7–8月",
    term5: "9–10月",
    term6: "11–12月"
  };
  return mapping[value] || "";
}

// 🔥 ===== 修改表單送出 (加入水電瓦斯重複檢查 + total_expense) ===== 🔥
if (dailyReportForm) {
  dailyReportForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    
    if (typeof API_BASE === 'undefined') {
      alert('API_BASE 未定義');
      return;
    }

    const submitBtn = document.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    
    try {
      const utilityText = getUtilityTermText(utilityTermSelect.value);
      
      // 🔥 1. 先檢查是否勾選水電瓦斯
      const enableUtilities = utilitiesCheckbox.checked;
      
      if (enableUtilities && utilityTermSelect.value) {
          const selectedDate = reportDate.value;
          if (!selectedDate) {
              showAlert("error", "請選擇報表日期");
              return;
          }
          
          const year = new Date(selectedDate).getFullYear();
          const term = utilityTermSelect.value; // term1, term2...
          
          // 顯示檢查中狀態
          if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>檢查中...';
          }
          
          // 🔥 2. 呼叫檢查 API
          const checkResult = await checkUtilitiesExist(year, term);
          
          if (checkResult.exists) {
              // ❌ 已存在,顯示錯誤並中止提交
              const termText = getUtilityTermText(term);
              showAlert("error", 
                  `${year}年的 ${termText} 已有水電瓦斯資料 (原資料日期: ${checkResult.data.report_date})\n請取消勾選水電瓦斯,或前往「日報表記錄」修改該筆資料`
              );
              
              if (submitBtn) {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = originalBtnText;
              }
              return; // 🔥 中止提交
          }
          
          // 檢查通過,更新按鈕狀態
          if (submitBtn) {
              submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>提交中...';
          }
      } else {
          // 沒有勾選水電瓦斯,直接顯示提交中
          if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>提交中...';
          }
      }

      // 🔥 3. 檢查通過,準備提交資料
      const expenseSalary = parseFloat(document.getElementById("expense_salary")?.value) || 0;
      const expenseUtilities = parseFloat(document.getElementById("expense_utilities")?.value) || 0;
      const expenseRent = parseFloat(document.getElementById("expense_rent")?.value) || 0;
      const expenseFood = parseFloat(document.getElementById("expense_food")?.value) || 0;
      const expenseDelivery = parseFloat(document.getElementById("expense_delivery")?.value) || 0;
      const expenseMisc = parseFloat(document.getElementById("expense_misc")?.value) || 0;
      
      // 🔥 計算支出總額
      const totalExpense = expenseSalary + expenseUtilities + expenseRent + expenseFood + expenseDelivery + expenseMisc;
      
      const data = {
        report_date: reportDate.value,
        weekday: weekdayInput.value,
        filled_by: filledBy.value,
        cash_income: parseFloat(document.getElementById("cash_income")?.value) || 0,
        linepay_income: parseFloat(document.getElementById("linepay_income")?.value) || 0,
        uber_income: parseFloat(document.getElementById("uber_income")?.value) || 0,
        other_income: parseFloat(document.getElementById("other_income")?.value) || 0,
        total_income: calculateIncome(),
        total_expense: totalExpense, // 🔥 新增:支出總額
        expense_salary: expenseSalary,
        expense_utilities: expenseUtilities,
        utilities_month: utilityText,
        enable_utilities: enableUtilities ? 1 : 0,
        utility_term: utilityTermSelect.value,
        expense_rent: expenseRent,
        enable_rent: rentCheckbox.checked ? 1 : 0,
        expense_food: expenseFood,
        expense_delivery: expenseDelivery,
        expense_misc: expenseMisc,
        expense_note: document.getElementById("expense_note")?.value || '',
        cash_1000: parseInt(document.getElementById("cash_1000")?.value) || 0,
        cash_500: parseInt(document.getElementById("cash_500")?.value) || 0,
        cash_100: parseInt(document.getElementById("cash_100")?.value) || 0,
        cash_50: parseInt(document.getElementById("cash_50")?.value) || 0,
        cash_10: parseInt(document.getElementById("cash_10")?.value) || 0,
        cash_5: parseInt(document.getElementById("cash_5")?.value) || 0,
        cash_1: parseInt(document.getElementById("cash_1")?.value) || 0,
        cash_total: calculateCash(),
        deposit_to_bank: parseFloat(depositInput?.value) || 0,
        rent_setting: rentSettingHidden?.value || ''
      };

      // 🔥 4. 提交到後端
      const saveRes = await fetch(`${API_BASE}/api_report_create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      
      const saveResult = await saveRes.json();
      
      if (saveResult.success) {
        // ✅ 成功
        showAlert("success", saveResult.message || "日報表送出成功!");
        
        // 重置表單
        dailyReportForm.reset();
        reportDate.value = formatDate(today);
        weekdayInput.value = getWeekday(today);
        
        // 重新填入用戶名稱和人事成本
        autoFillUserName();
        await loadLaborCost();
        updateKPI();
        
        // 3秒後可以選擇跳轉到記錄頁面
        setTimeout(() => {
          if (confirm("是否前往查看日報表記錄?")) {
            window.location.href = '日報表記錄.php';
          }
        }, 2000);

      } else {
        // ❌ 失敗
        showAlert("error", saveResult.error || saveResult.message || "資料儲存失敗");
      }
      
    } catch (err) {
      console.error("表單提交錯誤:", err);
      showAlert("error", "系統錯誤: " + err.message);
      
    } finally {
      // 恢復按鈕狀態
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    }
  });
}