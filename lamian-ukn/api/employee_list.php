<?php
// /lamian-ukn/api/clock_list.php
require __DIR__.'/config.php';

try {
  $pdo = pdo();
  $s = g('start_date');  // YYYY-MM-DD
  $e = g('end_date');    // YYYY-MM-DD
  $q = g('q');           // 關鍵字（姓名 / 編號）

  $sql = "
  SELECT
    a.id,
    a.user_id,
    DATE(a.clock_in)                                  AS date,
    DATE_FORMAT(a.clock_in,  '%H:%i')                 AS clock_in,
    DATE_FORMAT(a.clock_out, '%H:%i')                 AS clock_out,
    ROUND(COALESCE(a.hours, TIMESTAMPDIFF(MINUTE, a.clock_in, a.clock_out)/60), 2) AS hours,
    COALESCE(
      a.status,
      CASE
        WHEN a.clock_in IS NULL OR a.clock_out IS NULL THEN '缺卡'
        WHEN TIMESTAMPDIFF(MINUTE, a.clock_in, a.clock_out) > 480 THEN '加班'
        ELSE '正常'
      END
    ) AS status,
    a.note,
    e.`".EMP_NAME_COL."` AS emp_name,
    e.`".EMP_PK_COL."`   AS employee_id
  FROM `".ATT_TABLE."` a
  LEFT JOIN `".EMP_TABLE."` e
         ON CAST(e.`".EMP_PK_COL."` AS CHAR) = CAST(a.user_id AS CHAR)
  WHERE 1=1
  ";
  $p = [];
  if ($s){ $sql.=" AND DATE(a.clock_in) >= :s"; $p[':s']=$s; }
  if ($e){ $sql.=" AND DATE(a.clock_in) <= :e"; $p[':e']=$e; }
  if ($q){
    $sql.=" AND ( e.`".EMP_NAME_COL."` LIKE :q OR e.`".EMP_PK_COL."` LIKE :q )";
    $p[':q'] = '%'.$q.'%';
  }
  $sql.=" ORDER BY a.clock_in DESC, a.id DESC LIMIT 1000";

  $st = $pdo->prepare($sql);
  $st->execute($p);
  ok($st->fetchAll());

} catch(Throwable $ex){
  err('DB or query failed',500,['detail'=>$ex->getMessage()]);
}
