document.addEventListener("DOMContentLoaded", () => {

  /* -------------------- Modal 元件：必須放在最前面 -------------------- */
  const editModal = new bootstrap.Modal(document.getElementById("editModal"));

  const edit_user_id = document.getElementById("edit_user_id");
  const edit_name = document.getElementById("edit_name");
  const edit_month = document.getElementById("edit_month");
  const paytype_monthly = document.getElementById("paytype_monthly");
  const paytype_hourly = document.getElementById("paytype_hourly");
  const edit_base_salary = document.getElementById("edit_base_salary");
  const edit_hourly_rate = document.getElementById("edit_hourly_rate");
  const edit_working_hours = document.getElementById("edit_working_hours");
  const edit_calc_basepay = document.getElementById("edit_calc_basepay");
  const edit_bonus = document.getElementById("edit_bonus");
  const edit_deductions = document.getElementById("edit_deductions");
  const edit_total_salary = document.getElementById("edit_total_salary");

  /* -------------------- Modal 功能區 -------------------- */
  let editOriginal = {}; // 用來儲存原始資料

  function openEditModal(id, name, month, paytype, payvalue, hours, bonus, deductions) {

    edit_user_id.value = id;
    edit_name.value = name;
    edit_month.value = month;

    // 勞務類型
    if (paytype === "月薪") {
      paytype_monthly.checked = true;
      edit_base_salary.value = payvalue;
      edit_hourly_rate.value = "";
      edit_working_hours.value = "";
    } else {
      paytype_hourly.checked = true;
      edit_hourly_rate.value = payvalue;
      edit_base_salary.value = "";
      edit_working_hours.value = hours;
    }

    edit_bonus.value = bonus;
    edit_deductions.value = deductions;

    recalcEditTotal();

    // 記錄原始資料
    editOriginal = {
      working_hours: Number(hours),
      bonus: Number(bonus),
      deductions: Number(deductions)
    };

    editModal.show();
  }

  // ===== 計算薪資 =====
  function recalcEditTotal() {
    let total = 0;

    const b = Number(edit_base_salary.value || 0);
    const hr = Number(edit_hourly_rate.value || 0);
    const hrs = Number(edit_working_hours.value || 0);
    const bonus = Number(edit_bonus.value || 0);
    const ded = Number(edit_deductions.value || 0);

    let calc_base = 0;

    if (paytype_monthly.checked) {
      calc_base = b;
    } else {
      calc_base = hr * hrs;
    }

    edit_calc_basepay.value = calc_base.toFixed(0);
    total = calc_base + bonus - ded;

    edit_total_salary.textContent = total.toFixed(0);
  }

  // 即時計算事件
  [edit_working_hours, edit_bonus, edit_deductions].forEach(el => {
    el.addEventListener("input", recalcEditTotal);
  });

  // ====== 儲存修改（加入確認訊息） ======
  async function submitEdit(event) {
    event.preventDefault();

    const new_hours = Number(edit_working_hours.value || 0);
    const new_bonus = Number(edit_bonus.value || 0);
    const new_deductions = Number(edit_deductions.value || 0);

    let confirmMsg = "";

    // 工時比對
    if (new_hours !== editOriginal.working_hours) {
      confirmMsg += `➡ 工時：${editOriginal.working_hours} ➜ ${new_hours}\n`;
    }

    // 獎金比對
    if (new_bonus !== editOriginal.bonus) {
      confirmMsg += `➡ 獎金：${editOriginal.bonus} ➜ ${new_bonus}\n`;
    }

    // 扣款比對
    if (new_deductions !== editOriginal.deductions) {
      confirmMsg += `➡ 扣款：${editOriginal.deductions} ➜ ${new_deductions}\n`;
    }

    // 如果有任何變動，跳出確認
    if (confirmMsg !== "") {
      const userConfirm = confirm(`確認要修改以下內容嗎？\n\n${confirmMsg}`);
      if (!userConfirm) return;  // 使用者取消 → 不送出
    }

    const payload = {
      action: "update",
      user_id: edit_user_id.value,
      salary_month: edit_month.value,
      base_salary: Number(edit_base_salary.value || 0),
      hourly_rate: Number(edit_hourly_rate.value || 0),
      working_hours: new_hours,
      bonus: new_bonus,
      deductions: new_deductions
    };

    try {
      const res = await fetch("薪資管理_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (!data.success) throw new Error(data.message);

      editModal.hide();
      loadSalaryData(monthPicker.value, keywordInput.value);

    } catch (err) {
      alert("儲存失敗：" + err.message);
    }
  }

  window.submitEdit = submitEdit;
  window.openEditModal = openEditModal;

  /* -------------------- 原本你的程式碼（完全未更動） -------------------- */
  const monthPicker = document.getElementById("monthPicker");
  const keywordInput = document.getElementById("keyword");
  const salaryTableBody = document.getElementById("salaryTableBody");
  const noDataRow = document.getElementById("noDataRow");
  const currentDateEl = document.getElementById("currentDate");

  const summaryEmployees = document.getElementById("summary_employees");
  const summaryTotalPayroll = document.getElementById("summary_total_payroll");
  const summaryTotalBonus = document.getElementById("summary_total_bonus");
  const summaryTotalDeductions = document.getElementById("summary_total_deductions");

  const loadingIndicator = document.getElementById("loadingIndicator");
  const errorAlert = document.getElementById("errorAlert");
  const errorMessage = document.getElementById("errorMessage");

  // 顯示今天日期
  const now = new Date();
  const yyyyMM = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
  currentDateEl.textContent = now.toLocaleDateString("zh-TW");
  monthPicker.value = yyyyMM;

  loadSalaryData(yyyyMM);

  async function loadSalaryData(month, keyword = "") {
    showLoading(true);
    try {
      const res = await fetch("薪資管理_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "fetch", month, keyword })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      renderSalaryTable(data.records);
      updateSummary(data.records);
      showError(false);
    } catch (err) {
      console.error(err);
      showError(true, err.message);
    } finally {
      showLoading(false);
    }
  }

  function updateSummary(records) {
    if (!Array.isArray(records)) return;

    let totalEmployees = records.length;
    let totalPayroll = 0;
    let totalBonus = 0;
    let totalDeductions = 0;

    records.forEach(r => {
      totalPayroll += Number(r.total_salary ?? 0);
      totalBonus += Number(r.bonus ?? 0);
      totalDeductions += Number(r.deductions ?? 0);
    });

    summaryEmployees.textContent = totalEmployees;
    summaryTotalPayroll.textContent = totalPayroll.toFixed(0);
    summaryTotalBonus.textContent = totalBonus.toFixed(0);
    summaryTotalDeductions.textContent = totalDeductions.toFixed(0);
  }

  function renderSalaryTable(records) {
    salaryTableBody.innerHTML = "";

    if (!records || records.length === 0) {
      noDataRow.classList.remove("d-none");
      return;
    }
    noDataRow.classList.add("d-none");

    records.forEach(rec => {
      const payType = rec.salary_type || (rec.base_salary ? "月薪" : "時薪");
      const payValue = rec.base_salary > 0 ? rec.base_salary : rec.hourly_rate;
      const hours = rec.hours ?? 0;
      const bonus = rec.bonus ?? 0;
      const deductions = rec.deductions ?? 0;
      const totalSalary = rec.total_salary ?? 0;

      const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
      };

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(rec.id)}</td>
        <td>${escapeHtml(rec.name)}</td>
        <td>${escapeHtml(rec.salary_month)}</td>
        <td><span class="badge bg-info text-dark">${escapeHtml(payType)}</span></td>
        <td>${payValue}</td>
        <td>${hours}</td>
        <td>${bonus}</td>
        <td>${deductions}</td>
        <td>${totalSalary}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-info me-2" data-action="detail" data-id="${escapeHtml(rec.id)}" data-month="${escapeHtml(rec.salary_month)}">
            <i class="fas fa-eye"></i>
          </button>
          <button class="btn btn-sm btn-warning" data-action="edit"
            data-id="${escapeHtml(rec.id)}"
            data-name="${escapeHtml(rec.name)}"
            data-month="${escapeHtml(rec.salary_month)}"
            data-paytype="${escapeHtml(payType)}"
            data-payvalue="${payValue}"
            data-hours="${hours}"
            data-bonus="${bonus}"
            data-deductions="${deductions}">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      `;
      salaryTableBody.appendChild(tr);
    });
  }

  salaryTableBody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;

    if (action === 'detail') {
      showDetail(btn.dataset.id, btn.dataset.month);
    } else if (action === 'edit') {
      window.openEditModal(
        btn.dataset.id,
        btn.dataset.name,
        btn.dataset.month,
        btn.dataset.paytype,
        btn.dataset.payvalue,
        btn.dataset.hours,
        btn.dataset.bonus,
        btn.dataset.deductions
      );
    }
  });

  function showLoading(show) {
    loadingIndicator.classList.toggle("d-none", !show);
  }
  function showError(show, msg = "") {
    errorAlert.classList.toggle("d-none", !show);
    if (show) errorMessage.textContent = msg;
  }

});
