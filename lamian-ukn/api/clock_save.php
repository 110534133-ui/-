<?php
// /lamian-ukn/api/clock_save.php
require __DIR__.'/config.php';

try{
  $pdo = pdo();
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $code   = trim($body['emp_code'] ?? '');
  $action = strtolower(trim($body['action'] ?? ''));
  $note   = trim($body['note'] ?? '');

  if($code===''){ err('emp_code required',400); }
  if(!in_array($action,['in','out'],true)){ err('action must be in|out',400); }

  // 找員工
  $sqlEmp = "SELECT `".EMP_PK_COL."` AS id, `".EMP_NAME_COL."` AS name, `".EMP_CODE_COL."` AS code
             FROM `".EMP_TABLE."` WHERE `".EMP_CODE_COL."` = :c LIMIT 1";
  $st = $pdo->prepare($sqlEmp); $st->execute([':c'=>$code]);
  $emp = $st->fetch();
  if(!$emp){ err('找不到此員工編號',404, ['emp_code'=>$code]); }

  // 查有無未下班紀錄
  $sqlOpen = "SELECT * FROM `".ATT_TABLE."`
              WHERE user_id=:uid AND clock_out IS NULL
              ORDER BY clock_in DESC LIMIT 1";
  $st2 = $pdo->prepare($sqlOpen); $st2->execute([':uid'=>$emp['id']]);
  $open = $st2->fetch();

  if($action==='in'){
    if($open){ err('已有未下班紀錄，請先下班',409); }
    // 不寫 status 欄位（交由資料表預設值，例如「正常」）
    $sqlIns = "INSERT INTO `".ATT_TABLE."` (user_id, clock_in, note)
               VALUES (:uid, NOW(), :note)";
    $pdo->prepare($sqlIns)->execute([':uid'=>$emp['id'], ':note'=>$note]);
    ok(['ok'=>true,'message'=>'上班打卡成功','emp'=>$emp]);

  }else{ // 下班
    if(!$open){ err('找不到未下班紀錄，請先上班',409); }
    $sqlUpd = "UPDATE `".ATT_TABLE."`
               SET clock_out=NOW(),
                   hours = ROUND(TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60, 2),
                   status = CASE
                     WHEN TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > 480 THEN '加班'
                     ELSE '正常'
                   END,
                   note = COALESCE(NULLIF(:note,''), note)
               WHERE id=:id";
    $pdo->prepare($sqlUpd)->execute([':note'=>$note, ':id'=>$open['id']]);
    ok(['ok'=>true,'message'=>'下班打卡成功','emp'=>$emp]);
  }

}catch(Throwable $ex){
  err('save failed',500,['detail'=>$ex->getMessage()]);
}
