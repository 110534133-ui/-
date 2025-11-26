// daily_report_records.js
document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#reportTable tbody");
  const editModal = new bootstrap.Modal(document.getElementById("editReportModal"));
  const editForm = document.getElementById("editReportForm");

  // ===== 載入所有日報表資料 =====
  async function loadReports() {
    try {
      const res = await fetch("get_reports.php");
      const data = await res.json();
      tableBody.innerHTML = "";

      data.forEach((report) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${report.report_date}</td>
          <td>${report.weekday}</td>
          <td>${report.filled_by}</td>
          <td>${report.total_income}</td>
          <td>${report.total_expense}</td>
          <td>${report.cash_total}</td>
          <td>${report.deposit_to_bank}</td>
          <td>
            <button class="btn btn-sm btn-primary me-1" onclick="editReport(${report.id})">修改</button>
            <button class="btn btn-sm btn-danger" onclick="deleteReport(${report.id})">刪除</button>
          </td>
        `;
        tableBody.appendChild(tr);
      });
    } catch (err) {
      console.error("載入資料錯誤：", err);
    }
  }

  // ===== 刪除報表 =====
  window.deleteReport = async function (id) {
    if (!confirm("確定要刪除此筆報表嗎？")) return;
    try {
      const res = await fetch("delete_report.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}`,
      });
      const data = await res.json();
      if (data.success) {
        alert("刪除成功！");
        loadReports();
      } else {
        alert("刪除失敗：" + data.message);
      }
    } catch (err) {
      console.error("刪除錯誤：", err);
    }
  };

  // ===== 編輯報表 =====
  window.editReport = async function (id) {
    try {
      const res = await fetch(`get_report.php?id=${id}`);
      const data = await res.json();
      if (!data.success) {
        alert("無法取得資料：" + data.message);
        return;
      }

      const r = data.data;

      // 將資料填入 Modal
      document.getElementById("editId").value = r.id;
      document.getElementById("editDate").value = r.report_date;
      document.getElementById("editFilledBy").value = r.filled_by;
      document.getElementById("editCashIncome").value = r.cash_income;
      document.getElementById("editLinepayIncome").value = r.linepay_income;
      document.getElementById("editUberIncome").value = r.uber_income;
      document.getElementById("editOtherIncome").value = r.other_income;
      document.getElementById("editTotalIncome").value = r.total_income;

      document.getElementById("editExpenseFood").value = r.expense_food;
      document.getElementById("editExpenseSalary").value = r.expense_salary;
      document.getElementById("editExpenseRent").value = r.expense_rent;
      document.getElementById("editRantDaily").value = r.rent_daily;
      document.getElementById("editExpenseUtilities").value = r.expense_utilities;
      document.getElementById("editExpenseDelivery").value = r.expense_delivery;
      document.getElementById("editExpenseMisc").value = r.expense_misc;
      document.getElementById("editTotalExpense").value = r.total_expense;

      editModal.show();
    } catch (err) {
      console.error("編輯錯誤：", err);
    }
  };

  // ===== 儲存修改 =====
  editForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(editForm);

    try {
      const res = await fetch("update_report.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      if (data.success) {
        alert("修改成功！");
        editModal.hide();
        loadReports();
      } else {
        alert("修改失敗：" + data.message);
      }
    } catch (err) {
      console.error("更新錯誤：", err);
    }
  });

  // ===== 初始化載入 =====
  loadReports();
});
