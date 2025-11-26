<?php
// /lamian-ukn/includes/auth_check.php
// 統一的權限檢查函數庫

/**
 * 檢查用戶是否已登入
 * 未登入則跳轉到登入頁
 */
function require_login() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!isset($_SESSION['uid'])) {
        header('Location: /lamian-ukn/login.php');
        exit;
    }
    
    return $_SESSION['uid'];
}

/**
 * 檢查用戶等級
 * @param string|array $allowed_levels 允許的等級 'A' 或 ['A', 'B']
 * @param bool $redirect 是否自動跳轉（預設true）
 * @return bool 是否有權限
 */
function check_user_level($allowed_levels, $redirect = true) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!isset($_SESSION['uid'])) {
        if ($redirect) {
            header('Location: /lamian-ukn/login.php');
            exit;
        }
        return false;
    }
    
    // 取得用戶等級
    $user_level = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';
    
    // 轉換為陣列
    if (!is_array($allowed_levels)) {
        $allowed_levels = [$allowed_levels];
    }
    
    // 檢查權限
    $has_permission = in_array($user_level, $allowed_levels);
    
    if (!$has_permission && $redirect) {
        // 無權限，跳轉到對應的首頁
        switch($user_level) {
            case 'A':
                header('Location: /lamian-ukn/index.php');
                break;
            case 'B':
                header('Location: /lamian-ukn/indexB.php');
                break;
            case 'C':
            default:
                header('Location: /lamian-ukn/indexC.php');
                break;
        }
        exit;
    }
    
    return $has_permission;
}

/**
 * 只允許 A 級（老闆）訪問
 */
function require_level_A() {
    return check_user_level('A', true);
}

/**
 * 只允許 A 和 B 級（老闆和管理員）訪問
 */
function require_level_AB() {
    return check_user_level(['A', 'B'], true);
}

/**
 * 允許所有登入用戶訪問
 */
function require_level_ABC() {
    return check_user_level(['A', 'B', 'C'], true);
}

/**
 * 取得當前用戶資訊
 * @return array
 */
function get_user_info() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    return [
        'uid' => $_SESSION['uid'] ?? null,
        'name' => $_SESSION['name'] ?? '訪客',
        'level' => $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C',
        'is_logged_in' => isset($_SESSION['uid'])
    ];
}

/**
 * 檢查是否為老闆
 */
function is_boss() {
    $user = get_user_info();
    return $user['level'] === 'A';
}

/**
 * 檢查是否為管理員或以上
 */
function is_manager_or_above() {
    $user = get_user_info();
    return in_array($user['level'], ['A', 'B']);
}

/**
 * 顯示無權限頁面（不跳轉）
 */
function show_no_permission_page() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>無權限訪問</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .permission-card {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
            }
            .permission-icon {
                font-size: 80px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .btn-back {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
                padding: 12px 30px;
                border-radius: 25px;
                font-weight: 600;
                margin-top: 20px;
            }
            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102,126,234,0.4);
            }
        </style>
    </head>
    <body>
        <div class="permission-card">
            <div class="permission-icon">🚫</div>
            <h2>無權限訪問</h2>
            <p class="text-muted mt-3">抱歉，您的帳號等級無法訪問此頁面</p>
            <p class="text-muted">請聯繫管理員以獲取相應權限</p>
            <a href="javascript:history.back()" class="btn btn-back">返回上一頁</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}