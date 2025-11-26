// ===== 日報表紀錄.js =====
// 🔥 已修正：移除外層 DOMContentLoaded
// 🔥 已修正：API 呼叫路徑 (API_BASE)

// ===== DOM 元素 (直接選取) =====
const startDateInput = document.getElementById("start_date");
const endDateInput = document.getElementById("end_date");
const filledByFilter = document.getElementById("filled_by_filter");
const filterBtn = document.getElementById("filter_btn");
const clearBtn = document.getElementById("clear_btn");
const reportTableBody = document.getElementById("reportTableBody");
const totalRecordsEl = document.getElementById("total_records");
const totalIncomeSumEl = document.getElementById("total_income_sum");
const totalExpenseSumEl = document.getElementById("total_expense_sum");
const netIncomeEl = document.getElementById("net_income");
const currentDateEl = document.getElementById("currentDate");
const noDataRow = document.getElementById("noDataRow");

// ===== Modal 表單元素 =====
const editModalEl = document.getElementById("editReportModal");
const editReportModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
const editForm = document.getElementById("editReportForm");

const editId = document.getElementById("editId");
const editDate = document.getElementById("editDate");
const editFilledBy = document.getElementById("editFilledBy");

// 收入欄位
const editCashIncome = document.getElementById("editCashIncome");
const editLinepayIncome = document.getElementById("editLinepayIncome");
const editUberIncome = document.getElementById("editUberIncome");
const editOtherIncome = document.getElementById("editOtherIncome");
const total_income = document.getElementById("total_income");

// 支出欄位
const editExpenseFood = document.getElementById("editExpenseFood");
const editExpenseSalary = document.getElementById("editExpenseSalary");
const editExpenseRent = document.getElementById("editExpenseRent");
const editRentDaily = document.getElementById("editRentDaily");
const editExpenseUtilities = document.getElementById("editExpenseUtilities");
const editExpenseDelivery = document.getElementById("editExpenseDelivery");
const editExpenseMisc = document.getElementById("editExpenseMisc");
const total_expense = document.getElementById("total_expense");

// ===== 顯示今日日期 =====
if (currentDateEl) currentDateEl.textContent = new Date().toLocaleString("zh-TW");

// ===== 預設篩選日期：過去30天 =====
const today = new Date();
const past30 = new Date();
past30.setDate(today.getDate() - 30);
if (startDateInput) startDateInput.value = past30.toISOString().split("T")[0];
if (endDateInput) endDateInput.value = today.toISOString().split("T")[0];

let allReports = [];
let currentFilteredData = [];

// ===== 取得全部日報表 =====
async function fetchReportsFromPHP() {
  // 🔥 檢查 API_BASE 變數是否存在 (它在 PHP 頁尾定義)
  if (typeof API_BASE === 'undefined') {
      alert('系統錯誤：API_BASE 未定義，請檢查 PHP 頁面');
      return [];
  }
  
  try {
    console.log("開始發送 fetch 請求...");
    
    // 🔥 修正：加上 API_BASE 路徑
    const res = await fetch(`${API_BASE}/api_report_list.php?action=list`); 
    
    if (!res.ok) {
      console.error("HTTP 錯誤：", res.status, res.statusText);
      // 🔥 顯示 404 錯誤
      if (res.status === 404) {
          alert(`伺服器錯誤：404 Not Found\n找不到 API 檔案，請確認 ${API_BASE}/api_report_list.php 檔案是否存在。`);
      } else {
          alert(`伺服器錯誤：${res.status} ${res.statusText}`);
      }
      return [];
    }

    const text = await res.text();
    console.log("伺服器回應：", text);
    
    const json = JSON.parse(text);
    
    // [!! 關鍵 !!] 這裡現在可以正確解析 {"success":true, "data":...}
    if (!json.success) {
      alert("取得資料失敗：" + json.message);
      return [];
    }
    
    console.log("成功取得 " + json.data.length + " 筆資料");
    return json.data;
  } catch (err) {
    console.error("fetch 錯誤：", err);
    alert("伺服器連線錯誤：" + err.message);
    return [];
  }
}

// ===== 渲染表格 =====
function renderTable(data) {
  if (!reportTableBody) return;
  reportTableBody.innerHTML = "";
  if (!data.length) {
    noDataRow?.classList.remove("d-none");
    if (totalRecordsEl) totalRecordsEl.textContent = 0;
    if (totalIncomeSumEl) totalIncomeSumEl.textContent = 0;
    if (totalExpenseSumEl) totalExpenseSumEl.textContent = 0;
    if (netIncomeEl) netIncomeEl.textContent = 0;
    return;
  } else {
    noDataRow?.classList.add("d-none");
  }

  data.forEach(item => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${item.report_date}</td>
      <td>${item.filled_by}</td>
      <td>${item.cash_income}</td>
      <td>${item.linepay_income}</td>
      <td>${item.uber_income}</td>
      <td>${item.other_income}</td>
      <td class="fw-bold">${item.total_income}</td>
      <td>${item.expense_food}</td>
      <td>${item.expense_salary}</td>
      <td>${item.expense_rent}</td>
      <td>${item.rent_daily || '-'}</td>
      <td>${item.expense_utilities}</td>
      <td>${item.expense_delivery}</td>
      <td>${item.expense_misc}</td>
      <td class="fw-bold text-danger">${item.total_expense}</td>
      <td class="sticky-right">
        <button class="btn btn-sm btn-warning" onclick="editReport(${item.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-danger" onclick="deleteReport(${item.id})"><i class="fas fa-trash"></i></button>
      </td>
    `;
    reportTableBody.appendChild(tr);
  });
}

// ===== 統計摘要 =====
function updateSummary(data) {
  const totalRecords = data.length;
  const totalIncome = data.reduce((sum, r) => sum + Number(r.total_income || 0), 0);
  const totalExpense = data.reduce((sum, r) => sum + Number(r.total_expense || 0), 0);
  const net = totalIncome - totalExpense;

  if (totalRecordsEl) totalRecordsEl.textContent = totalRecords;
  if (totalIncomeSumEl) totalIncomeSumEl.textContent = totalIncome.toLocaleString();
  if (totalExpenseSumEl) totalExpenseSumEl.textContent = totalExpense.toLocaleString();
  if (netIncomeEl) netIncomeEl.textContent = net.toLocaleString();
}

// ===== 填表人選單 =====
function populateFilledByOptions(data) {
  const uniqueNames = [...new Set(data.map(item => item.filled_by))];
  if (!filledByFilter) return;
  
  // [!! 修正 !!] 保存當前選定的值
  const currentValue = filledByFilter.value;
  
  filledByFilter.innerHTML =
    `<option value="">全部</option>` +
    uniqueNames.map(name => `<option value="${name}">${name}</option>`).join("");
    
  // [!! 修正 !!] 恢復選定的值
  filledByFilter.value = currentValue;
}

// ===== 匯出 Excel =====
window.exportToExcel = function () {
  if (!currentFilteredData.length) {
    alert("沒有可匯出的資料！");
    return;
  }
  const ws = XLSX.utils.json_to_sheet(currentFilteredData.map(item => ({
    日期: item.report_date,
    填表人: item.filled_by,
    現金收入: item.cash_income,
    LinePay: item.linepay_income,
    Uber: item.uber_income,
    其他收入: item.other_income,
    收入合計: item.total_income,
    食材成本: item.expense_food,
    人事成本: item.expense_salary,
    租金: item.expense_rent,
    每日租金平攤: item.rent_daily || 0,
    水電瓦斯費: item.expense_utilities,
    外送平台費: item.expense_delivery,
    雜項支出: item.expense_misc,
    支出合計: item.total_expense,
  })));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "日報表記錄");
  XLSX.writeFile(wb, "日報表記錄.xlsx");
};

// ===== 刪除報表 =====
window.deleteReport = async function (id) {
  if (typeof API_BASE === 'undefined') {
      alert('系統錯誤：API_BASE 未定義');
      return;
  }
  if (!confirm("確定要刪除這筆記錄嗎？")) return;
  try {
    // 🔥 修正：加上 API_BASE 路徑
    const res = await fetch(`${API_BASE}/api_report_list.php?action=delete&id=${id}`);
    
    if (!res.ok) {
      alert(`伺服器錯誤：${res.status}`);
      return;
    }

    const json = await res.json();
    alert(json.message);
    if (json.success) {
      // 清空快取，強制重新從伺服器取得資料
      allReports = [];
      await loadReports();
    }
  } catch (err) {
    console.error(err);
    alert("刪除失敗，伺服器錯誤：" + err.message);
  }
};

// ===== 編輯報表 =====
window.editReport = async function (id) {
  if (typeof API_BASE === 'undefined') {
      alert('系統錯誤：API_BASE 未定義');
      return;
  }
  try {
    // 🔥 修正：加上 API_BASE 路徑
    const res = await fetch(`${API_BASE}/api_report_list.php?action=get&id=${id}`);
    
    if (!res.ok) {
      alert(`伺服器錯誤：${res.status}`);
      return;
    }

    const json = await res.json();
    if (!json.success) {
      alert("取得單筆資料失敗：" + json.message);
      return;
    }
    const data = json.data;

    if (editId) editId.value = data.id ?? "";
    if (editDate) editDate.value = data.report_date ?? "";
    if (editFilledBy) editFilledBy.value = data.filled_by ?? "";

    if (editCashIncome) editCashIncome.value = data.cash_income ?? 0;
    if (editLinepayIncome) editLinepayIncome.value = data.linepay_income ?? 0;
    if (editUberIncome) editUberIncome.value = data.uber_income ?? 0;
    if (editOtherIncome) editOtherIncome.value = data.other_income ?? 0;
    if (total_income) total_income.value = data.total_income ?? 0;

    if (editExpenseFood) editExpenseFood.value = data.expense_food ?? 0;
    if (editExpenseSalary) editExpenseSalary.value = data.expense_salary ?? 0;
    if (editExpenseRent) editExpenseRent.value = data.expense_rent ?? 0;
    if (editRentDaily) editRentDaily.value = data.rent_daily ?? 0;
    if (editExpenseUtilities) editExpenseUtilities.value = data.expense_utilities ?? 0;
    if (editExpenseDelivery) editExpenseDelivery.value = data.expense_delivery ?? 0;
    if (editExpenseMisc) editExpenseMisc.value = data.expense_misc ?? 0;
    if (total_expense) total_expense.value = data.total_expense ?? 0;

    editReportModal?.show();
  } catch (err) {
    console.error(err);
    alert("載入報表資料失敗：" + err.message);
  }
};

// ===== 即時計算收入與支出 =====
function calcIncomeTotal() {
  const total =
    Number(editCashIncome?.value || 0) +
    Number(editLinepayIncome?.value || 0) +
    Number(editUberIncome?.value || 0) +
    Number(editOtherIncome?.value || 0);
  if (total_income) total_income.value = total.toFixed(0);
}

function calcExpenseTotal() {
  const total =
    Number(editExpenseFood?.value || 0) +
    Number(editExpenseSalary?.value || 0) +
    Number(editExpenseRent?.value || 0) +
    Number(editExpenseUtilities?.value || 0) +
    Number(editExpenseDelivery?.value || 0) +
    Number(editExpenseMisc?.value || 0);
  if (total_expense) total_expense.value = total.toFixed(0);
}

// 綁定即時計算事件
if (editCashIncome) editCashIncome.addEventListener("input", calcIncomeTotal);
if (editLinepayIncome) editLinepayIncome.addEventListener("input", calcIncomeTotal);
if (editUberIncome) editUberIncome.addEventListener("input", calcIncomeTotal);
if (editOtherIncome) editOtherIncome.addEventListener("input", calcIncomeTotal);

if (editExpenseFood) editExpenseFood.addEventListener("input", calcExpenseTotal);
if (editExpenseSalary) editExpenseSalary.addEventListener("input", calcExpenseTotal);
if (editExpenseRent) editExpenseRent.addEventListener("input", calcExpenseTotal);
if (editExpenseUtilities) editExpenseUtilities.addEventListener("input", calcExpenseTotal);
if (editExpenseDelivery) editExpenseDelivery.addEventListener("input", calcExpenseTotal);
if (editExpenseMisc) editExpenseMisc.addEventListener("input", calcExpenseTotal);

// ===== 提交修改 =====
editForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (typeof API_BASE === 'undefined') {
      alert('系統錯誤：API_BASE 未定義');
      return;
  }

  const data = {
    id: Number(editId?.value || 0),
    report_date: editDate?.value || "",
    filled_by: editFilledBy?.value || "",
    cash_income: Number(editCashIncome?.value || 0),
    linepay_income: Number(editLinepayIncome?.value || 0),
    uber_income: Number(editUberIncome?.value || 0),
    other_income: Number(editOtherIncome?.value || 0),
    expense_food: Number(editExpenseFood?.value || 0),
    expense_salary: Number(editExpenseSalary?.value || 0),
    expense_rent: Number(editExpenseRent?.value || 0),
    expense_utilities: Number(editExpenseUtilities?.value || 0),
    expense_delivery: Number(editExpenseDelivery?.value || 0),
    expense_misc: Number(editExpenseMisc?.value || 0)
  };

  try {
    console.log("提交修改資料：", data);
    // 🔥 修正：加上 API_BASE 路徑
    const res = await fetch(`${API_BASE}/api_report_update.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    });

    if (!res.ok) {
      alert(`伺服器錯誤：${res.status}`);
      return;
    }

    const json = await res.json();
    alert(json.message);
    if (json.success) {
      editReportModal?.hide();
      // 清空快取，強制重新從伺服器取得資料
      allReports = [];
      await loadReports();
    }
  } catch (err) {
    console.error(err);
    alert("修改失敗，伺服器錯誤：" + err.message);
  }
});

// ===== 篩選與初始化 =====
// 🔥 將 loadReports 設為全域函式，這樣 PHP 頁尾才能呼叫到
window.loadReports = async function () {
  if (!allReports.length) {
    allReports = await fetchReportsFromPHP();
  }

  // [!! 關鍵修正 !!]
  // 1. 我們改用 YYYY-MM-DD 字串來比較，避免 new Date() 的時區問題
  // 2. 我們將 item.filled_by 轉為字串來比較，避免 10 !== "10" 的問題
  
  const start = startDateInput?.value || "";
  const end = endDateInput?.value || "";
  const by = filledByFilter?.value || "";

  const filtered = allReports.filter(item => {
    const date = item.report_date; // e.g., "2025-11-10"
    const filledBy = String(item.filled_by); // e.g., "10"

    // 比較字串
    if (start && date < start) return false;
    if (end && date > end) return false;
    if (by && filledBy !== by) return false; // <-- 修正後的比較
    
    return true;
  });

  currentFilteredData = filtered;
  renderTable(filtered);
  updateSummary(filtered);
  populateFilledByOptions(allReports);
}

filterBtn?.addEventListener("click", loadReports);
clearBtn?.addEventListener("click", () => {
  if (startDateInput) startDateInput.value = past30.toISOString().split("T")[0];
  if (endDateInput) endDateInput.value = today.toISOString().split("T")[0];
  if (filledByFilter) filledByFilter.value = "";
  loadReports();
});

// 🔥 移除：loadReports(); (已改由 PHP 頁尾的 'DOMContentLoaded' 觸發)