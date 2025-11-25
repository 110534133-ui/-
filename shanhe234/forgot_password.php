

<?php //載入彈出視窗 + 錯誤提示
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']); // 顯示一次就清掉
?>
<div id="forgot-password-popup" class="popup" style="display: block;">
  <div class="popup-content">
    <span class="close-btn">&times;</span>
    <h2 class="form-title">重設密碼</h2>

    <?php if ($error): ?>
      <p style="color:red; text-align:center;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- 送驗證碼 -->
    <form method="post" action="send_sms.php">
      <div class="form-group">
        <label for="reset-phone">手機號碼：</label>
        <input type="tel" id="reset-phone" name="reset-phone" required>
        <button type="submit" class="btn">發送驗證碼</button>
      </div>
    </form>

    <!-- 驗證碼驗證 -->
    <form method="post" action="update_password.php">
      <div class="form-group">
        <label for="verification-code">驗證碼：</label>
        <input type="text" id="verification-code" name="verification-code" required>
      </div>
      <button type="submit" class="btn">驗證</button>
    </form>
  </div>
</div>
