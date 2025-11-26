// report_aaa.js
/**
 * daily_report_records.js
 * 用於：日報表記錄（fetch get_report.php => 填表、列表、分頁、匯出、明細）
 */


// ===== 初始化：日期顯示＋側欄收合 =====
document.addEventListener("DOMContentLoaded", () => {
  const dateEl = document.getElementById('currentDate');
  if (dateEl) {
    dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
      year: 'numeric', month: 'long', day: 'numeric', weekday: 'long'
    });
  }

  const toggleBtn = document.getElementById('sidebarToggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });
  }
});

// ===== 篩選與載入列表 =====
function loadReports(filters = {}) {
  let params = new URLSearchParams();
  if (filters.start_date) params.append("start_date", filters.start_date);
  if (filters.end_date) params.append("end_date", filters.end_date);
  if (filters.filled_by) params.append("filled_by", filters.filled_by);

  fetch("get_report.php?" + params.toString())
    .then(res => res.json())
    .then(data => {
      const tbody = document.getElementById("reportTableBody");
      tbody.innerHTML = "";

      if (!data.success || data.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="16" class="text-center text-muted">
          <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
        </td></tr>`;
        document.getElementById('total_records').textContent = 0;
        return;
      }

      data.data.forEach(row => {
        const id = row.id ?? 0;
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.report_date || ''}</td>
          <td>${row.filled_by || ''}</td>
          <td>${row.cash_income ?? ''}</td>
          <td>${row.linepay_income ?? ''}</td>
          <td>${row.uber_income ?? ''}</td>
          <td>${row.other_income ?? ''}</td>
          <td>${row.total_income ?? ''}</td>
          <td>${row.expense_food ?? ''}</td>
          <td>${row.expense_salary ?? ''}</td>
          <td>${row.expense_rent ?? ''}</td>
          <td>${row.expense_rent_month ?? ''}</td>
          <td>${row.expense_utilities ?? ''}</td>
          <td>${row.expense_delivery ?? ''}</td>
          <td>${row.expense_misc ?? ''}</td>
          <td>${row.total_expense ?? ''}</td>
          <td class="sticky-right">
            <button class="btn btn-primary btn-sm" onclick="editReport(${id})"><i class="fas fa-edit"></i></button>
            <button class="btn btn-info btn-sm" onclick="viewReportDetail(${id})"><i class="fas fa-eye"></i></button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(${id})"><i class="fas fa-trash-alt"></i></button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      document.getElementById('total_records').textContent = data.data.length;
      if (typeof updateSummary === "function") updateSummary(data.data);
    })
    .catch(err => console.error("API 錯誤:", err));
}

// 篩選
function filterReports() {
  const startDate = document.getElementById("start_date")?.value;
  const endDate = document.getElementById("end_date")?.value;
  const filledBy = document.getElementById("filled_by_filter")?.value;
  loadReports({ start_date: startDate, end_date: endDate, filled_by: filledBy });
}

// 清除篩選
function clearFilters() {
  document.getElementById("start_date").value = "";
  document.getElementById("end_date").value = "";
  document.getElementById("filled_by_filter").value = "";
  loadReports();
}

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("filter_btn")?.addEventListener("click", filterReports);
  document.getElementById("clear_btn")?.addEventListener("click", clearFilters);
  loadReports();
});

// ===== 修改功能 =====
function editReport(id) {
  fetch(`get_report.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert("無法取得資料：" + data.message);
        return;
      }
      const report = data.data;

      document.getElementById("editId").value = id;
      document.getElementById("editDate").value = report.report_date;
      document.getElementById("editFilledBy").value = report.filled_by;
      document.getElementById("editCashIncome").value = report.cash_income;
      document.getElementById("editLinepayIncome").value = report.linepay_income;
      document.getElementById("editUberIncome").value = report.uber_income;
      document.getElementById("editOtherIncome").value = report.other_income;
      document.getElementById("editTotalIncome").value = report.total_income;
      document.getElementById("editExpenseFood").value = report.expense_food;
      document.getElementById("editExpenseSalary").value = report.expense_salary;
      document.getElementById("editExpenseRent").value = report.expense_rent;
      document.getElementById("editExpenseRentMonth").value = report.expense_rent_month || '';
      document.getElementById("editExpenseUtilities").value = report.expense_utilities;
      document.getElementById("editExpenseDelivery").value = report.expense_delivery;
      document.getElementById("editExpenseMisc").value = report.expense_misc;
      document.getElementById("editTotalExpense").value = report.total_expense;

      const modal = new bootstrap.Modal(document.getElementById('editReportModal'));
      modal.show();
    });
}

document.getElementById("editReportForm")?.addEventListener("submit", function(e) {
  e.preventDefault();
  const formData = {
    id: parseInt(document.getElementById("editId").value, 10),
    report_date: document.getElementById("editDate").value,
    filled_by: document.getElementById("editFilledBy").value,
    cash_income: parseFloat(document.getElementById("editCashIncome").value) || 0,
    linepay_income: parseFloat(document.getElementById("editLinepayIncome").value) || 0,
    uber_income: parseFloat(document.getElementById("editUberIncome").value) || 0,
    other_income: parseFloat(document.getElementById("editOtherIncome").value) || 0,
    total_income: parseFloat(document.getElementById("editTotalIncome").value) || 0,
    expense_food: parseFloat(document.getElementById("editExpenseFood").value) || 0,
    expense_salary: parseFloat(document.getElementById("editExpenseSalary").value) || 0,
    expense_rent: parseFloat(document.getElementById("editExpenseRent").value) || 0,
    expense_rent_month: parseFloat(document.getElementById("editExpenseRentMonth").value) || 0,
    expense_utilities: parseFloat(document.getElementById("editExpenseUtilities").value) || 0,
    expense_delivery: parseFloat(document.getElementById("editExpenseDelivery").value) || 0,
    expense_misc: parseFloat(document.getElementById("editExpenseMisc").value) || 0,
    total_expense: parseFloat(document.getElementById("editTotalExpense").value) || 0
  };

  fetch("update_report.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(formData)
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("修改成功！");
        location.reload();
      } else {
        alert("修改失敗：" + data.message);
      }
    });
});

// ===== 檢視功能 =====
function viewReportDetail(id) {
  fetch(`get_report.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      const report = data.data;
      const html = `
        <table class="table table-bordered">
          <tr><th>日期</th><td>${report.report_date}</td></tr>
          <tr><th>填表人</th><td>${report.filled_by}</td></tr>
          <tr><th>現金收入</th><td>${report.cash_income}</td></tr>
          <tr><th>LinePay收入</th><td>${report.linepay_income}</td></tr>
          <tr><th>Uber收入</th><td>${report.uber_income}</td></tr>
          <tr><th>其他收入</th><td>${report.other_income}</td></tr>
          <tr><th>收入合計</th><td>${report.total_income}</td></tr>
          <tr><th>支出合計</th><td>${report.total_expense}</td></tr>
        </table>
      `;
      document.getElementById("modalBody").innerHTML = html;
      const modal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
      modal.show();
    });
}

// ===== 刪除功能 =====
let deleteId = null;
function confirmDelete(id) {
  deleteId = id;
  const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
  modal.show();
}
document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
  if (!deleteId) return;
  fetch(`delete_report.php?id=${deleteId}`, { method: 'DELETE' })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("刪除成功");
        loadReports();
      }
    })
    .finally(() => {
      deleteId = null;
      const modalEl = document.getElementById('confirmDeleteModal');
      bootstrap.Modal.getInstance(modalEl)?.hide();
    });
});

// ===== 四個大版面（摘要） =====
function updateSummary(reports) {
  if (!reports || reports.length === 0) {
    document.getElementById('total_records').textContent = 0;
    document.getElementById('total_income_sum').textContent = 0;
    document.getElementById('total_expense_sum').textContent = 0;
    document.getElementById('net_income').textContent = 0;
    return;
  }

  let totalIncome = 0, totalExpense = 0;
  reports.forEach(r => {
    totalIncome += parseFloat(r.total_income) || 0;
    totalExpense += parseFloat(r.total_expense) || 0;
  });

  document.getElementById('total_records').textContent = reports.length;
  document.getElementById('total_income_sum').textContent = totalIncome.toFixed(0);
  document.getElementById('total_expense_sum').textContent = totalExpense.toFixed(0);
  document.getElementById('net_income').textContent = (totalIncome - totalExpense).toFixed(0);
}

// ===== 匯出 Excel =====
function exportToExcel() {
  const tbody = document.getElementById("reportTableBody");
  const rows = Array.from(tbody.querySelectorAll("tr"))
    .filter(tr => !tr.classList.contains("no-data")); // 避免暫無資料列

  if (rows.length === 0) {
    alert("目前無資料可匯出！");
    return;
  }

  const headers = [
    "日期", "填表人", "現金收入", "LinePay", "Uber", "其他收入", "收入合計",
    "食材成本", "人事成本", "每日租金平攤", "月租金", "水電瓦斯費", "外送平台費", "雜項支出", "支出合計"
  ];

  const sheetData = [headers];

  rows.forEach(tr => {
    const cells = tr.querySelectorAll("td");
    sheetData.push([
      cells[0].textContent.trim(), // 日期
      cells[1].textContent.trim(), // 填表人
      cells[2].textContent.trim(), // 現金收入
      cells[3].textContent.trim(), // LinePay
      cells[4].textContent.trim(), // Uber
      cells[5].textContent.trim(), // 其他收入
      cells[6].textContent.trim(), // 收入合計
      cells[7].textContent.trim(), // 食材成本
      cells[8].textContent.trim(), // 人事成本
      cells[9].textContent.trim(), // 每日租金平攤
      cells[10].textContent.trim(), // 月租金
      cells[11].textContent.trim(), // 水電瓦斯費
      cells[12].textContent.trim(), // 外送平台費
      cells[13].textContent.trim(), // 雜項支出
      cells[14].textContent.trim()  // 支出合計
    ]);
  });

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(sheetData);
  XLSX.utils.book_append_sheet(wb, ws, "日報表");
  XLSX.writeFile(wb, `daily_report_${Date.now()}.xlsx`);
}

