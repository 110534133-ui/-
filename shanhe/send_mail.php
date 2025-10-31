<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mail = new PHPMailer(true);
// 設定編碼
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
    try {
        // SMTP 設定
        $mail->SMTPDebug = 2; // debug 模式
        $mail->Debugoutput = 'html';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'linghebouduo@gmail.com'; // 你的 Gmail
        $mail->Password   = 'npne ycfl ijvn jeko';    // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // 收發件人
        $mail->setFrom('linghebouduo@gmail.com', '網站意見表單');
        $mail->addAddress('linghebouduo@gmail.com', '收件人');

        // 表單內容
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $subject = htmlspecialchars($_POST['subject']);
        $message = htmlspecialchars($_POST['message']);

        // 郵件內容
        $mail->isHTML(true);
        $mail->Subject = "網站留言: $subject";
        $mail->Body    = "
            <b>姓名：</b> $name<br>
            <b>Email：</b> $email<br><br>
            <b>留言內容：</b><br>
            " . nl2br($message) . "
        ";

        $mail->send();
        echo "寄信成功 ✅";
    } catch (Exception $e) {
        echo "寄信失敗 ❌：{$mail->ErrorInfo}";
    }
} else {
    echo "請透過表單送出資料";
}
?>
