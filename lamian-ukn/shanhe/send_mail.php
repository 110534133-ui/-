<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
include 'config.php'; // 資料庫連線

session_start();
session_set_cookie_params(['path' => '/']); // 確保跨資料夾 session 可用

$mail = new PHPMailer(true);
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ======================
        // ✉️ SMTP 設定
        // ======================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'linghebouduo@gmail.com';
        $mail->Password   = 'npne ycfl ijvn jeko'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('linghebouduo@gmail.com', '拉麵店系統');

        // ======================
        // 💡 1️⃣ 忘記密碼寄驗證碼
        // ======================
        if (isset($_POST['reset_email'])) {
            $email = htmlspecialchars($_POST['reset_email']);

            // 🔍 檢查會員是否存在
            $check_sql = "SELECT * FROM ramen_members WHERE Email = '$email'";
            $result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($result) === 0) {
                echo "尚未註冊過會員！";
                exit;
            }

            // 🔢 產生隨機 6 碼驗證碼
            $verificationCode = rand(100000, 999999);
            $timeNow = date("Y-m-d H:i:s");

            // 💾 更新到資料庫（驗證碼 + 時間）
            $update_sql = "UPDATE ramen_members 
                           SET 驗證碼 = '$verificationCode', 驗證碼建立時間 = '$timeNow'
                           WHERE Email = '$email'";
            mysqli_query($conn, $update_sql);

            // ✅ 儲存使用者 email 到 Session，供 verify_code.php 使用
            $_SESSION['reset_email'] = $email;

            // 📬 寄出驗證碼郵件
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = '密碼重設驗證碼（5 分鐘內有效）';
            $mail->Body = "
                您好，<br><br>
                您的密碼重設驗證碼為：<h2 style='color:#e74c3c;'>$verificationCode</h2>
                請於 5 分鐘內輸入完成驗證。<br><br>
                若非本人操作，請忽略此信件。<br><br>
                —— 拉麵店會員中心
            ";

            $mail->send();
            echo "驗證碼已寄出！請檢查您的信箱。";
            exit;
        }

        // ======================
        // 💬 2️⃣ 網站留言回饋功能（保留）
        // ======================
        elseif (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['message'])) {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $subject = htmlspecialchars($_POST['subject'] ?? '訪客留言');
            $message = htmlspecialchars($_POST['message']);

            // 寄給老闆的信件
            $mail->addAddress('linghebouduo@gmail.com', '店長');
            $mail->isHTML(true);
            $mail->Subject = "網站留言：$subject";
            $mail->Body = "
                <b>姓名：</b> $name<br>
                <b>Email：</b> $email<br><br>
                <b>留言內容：</b><br>" . nl2br($message);

            $mail->send();
            echo "留言已成功寄出，感謝您的回饋！";
            exit;
        }

        // ======================
        // ⚠️ 其他情況
        // ======================
        else {
            echo "請透過正確的表單送出資料。";
        }

    } catch (Exception $e) {
        echo "寄信失敗 ❌：" . $mail->ErrorInfo;
    }
} else {
    echo "請透過表單送出資料。";
}
?>
