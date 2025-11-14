<?php
// 查詢員工資料

include("db_connect.php");

$keyword = "";
$searchField = "name"; // 預設欄位
$result = null;

// 可選查詢欄位（避免SQL注入）
$allowedFields = [
    "id" => "員工編號",
    "name" => "姓名",
    "role" => "角色",
    "Position" => "職位名稱",
    "base_salary" => "底薪",
    "hourly_rate" => "時薪",
    "Telephone" => "電話",
    "address" => "地址",
    "ID card" => "身分證",
    "account" => "帳號"
];

// 預設顯示全部員工
$sql = "SELECT id, name, role, Position, base_salary, hourly_rate,
               Telephone, address, `ID_card` AS ID_card, account, password_hash
        FROM `員工基本資料`";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["keyword"]) && isset($_POST["searchField"])) {
    $keyword = trim($_POST["keyword"]);
    $searchField = $_POST["searchField"];

    if (array_key_exists($searchField, $allowedFields)) {
        $likeKeyword = "%" . $keyword . "%";
        $fieldForSQL = ($searchField === "ID_card") ? "`ID_card`" : $searchField;

        $sql .= " WHERE $fieldForSQL LIKE ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL 錯誤：" . $conn->error);
        }

        $stmt->bind_param("s", $likeKeyword);
        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    // 沒搜尋時，顯示全部
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>指定欄位查詢員工資料</title>
    <style>
        body { font-family: Arial; padding: 30px; text-align: center; }
        table { margin: auto; border-collapse: collapse; width: 95%; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 8px; word-break: break-word; }
        th { background-color: #f2f2f2; }
        input[type="text"], select { padding: 6px; width: 250px; margin-right: 10px; }
        input[type="submit"] { padding: 6px 12px; }
    </style>
</head>
<body>

<h2>🔍 查詢員工資料（可指定欄位）</h2>

<form method="post" action="employee_search_select.php">
    <select name="searchField">
        <?php
        foreach ($allowedFields as $field => $label) {
            $selected = ($field === $searchField) ? "selected" : "";
            echo "<option value=\"" . htmlspecialchars($field) . "\" $selected>" . htmlspecialchars($label) . "</option>";
        }
        ?>
    </select>
    <input type="text" name="keyword" placeholder="輸入關鍵字" value="<?php echo htmlspecialchars($keyword); ?>">
    <input type="submit" value="搜尋">
</form>

<?php if ($result !== null): ?>
    <h3>員工資料列表：</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>姓名</th>
            <th>角色</th>
            <th>職位</th>
            <th>底薪</th>
            <th>時薪</th>
            <th>電話</th>
            <th>住址</th>
            <th>身分證</th>
            <th>帳號</th>
            <th>密碼雜湊</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["role"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["Position"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["base_salary"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["hourly_rate"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["Telephone"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["address"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["ID_card"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["account"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["password_hash"]) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='11'>查無資料</td></tr>";
        }
        ?>
    </table>
<?php endif; ?>

</body>
</html>
