<?php
/**
 * 建立測試帳號工具
 * 用於為現有員工建立登入帳號
 */

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

$message = '';
$error = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $account = trim($_POST['account'] ?? '');
    $plainPassword = trim($_POST['password'] ?? '');
    
    if ($employeeId && $account && $plainPassword) {
        // 檢查員工是否存在
        $stmt = $conn->prepare("SELECT id, name FROM 員工基本資料 WHERE id = ?");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "找不到該員工 ID: " . $employeeId;
        } else {
            $employee = $result->fetch_assoc();
            
            // 檢查帳號是否已存在
            $stmt2 = $conn->prepare("SELECT account FROM 員工基本資料 WHERE account = ? AND id != ?");
            $stmt2->bind_param("si", $account, $employeeId);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result2->num_rows > 0) {
                $error = "該帳號已被使用: " . $account;
            } else {
                // 加密密碼並更新
                $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
                
                $stmt3 = $conn->prepare("UPDATE 員工基本資料 SET account = ?, password_hash = ? WHERE id = ?");
                $stmt3->bind_param("ssi", $account, $hashedPassword, $employeeId);
                
                if ($stmt3->execute()) {
                    $message = "✅ 成功為員工 " . $employee['name'] . " (ID: $employeeId) 建立帳號!<br>";
                    $message .= "帳號: <strong>$account</strong><br>";
                    $message .= "密碼: <strong>$plainPassword</strong>";
                } else {
                    $error = "更新失敗: " . $stmt3->error;
                }
                
                $stmt3->close();
            }
            
            $stmt2->close();
        }
        
        $stmt->close();
    } else {
        $error = "請填寫完整資料";
    }
}

// 取得所有員工列表
$employees = [];
$result = $conn->query("SELECT id, name, account, Position FROM 員工基本資料 ORDER BY id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>建立員工帳號</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,.2);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 25px;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .badge {
            padding: 0.5rem 0.8rem;
            border-radius: 10px;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(251, 185, 124, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center text-white mb-4">
            <h1><i class="fas fa-user-plus me-3"></i>員工帳號管理工具</h1>
            <p class="lead">為員工建立或更新登入帳號</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus me-2"></i>建立新帳號
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">員工 ID</label>
                            <input type="number" class="form-control" name="employee_id" required placeholder="輸入員工 ID">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">登入帳號</label>
                            <input type="text" class="form-control" name="account" required placeholder="設定帳號">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">登入密碼</label>
                            <input type="text" class="form-control" name="password" required placeholder="設定密碼">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-create">
                            <i class="fas fa-save me-2"></i>建立帳號
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users me-2"></i>現有員工列表
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>姓名</th>
                                <th>職位</th>
                                <th>帳號狀態</th>
                                <th>登入帳號</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?php echo $emp['id']; ?></td>
                                    <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['Position'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($emp['account'])): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>已設定
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation me-1"></i>未設定
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($emp['account']) ? htmlspecialchars($emp['account']) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <a href="login.php" class="btn btn-light btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>前往登入頁面
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>