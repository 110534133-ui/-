<?php
// /lamian-ukn/api/helpers.php
declare(strict_types=1);

// 讓所有引用 helpers.php 的檔案，自動取得 DB 設定與 pdo()
require_once __DIR__ . '/config.php';

// HTML 安全輸出
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function parse_time_to_minutes(?string $hhmm): ?int {
  if (!$hhmm) return null;
  if (!preg_match('/^\d{1,2}:\d{2}$/', $hhmm)) return null;
  [$h,$m] = array_map('intval', explode(':', $hhmm));
  return $h*60 + $m;
}

function minutes_between_hhmm(?string $in, ?string $out): ?int {
  $a = parse_time_to_minutes($in);
  $b = parse_time_to_minutes($out);
  if ($a===null || $b===null) return null;
  $d = $b - $a; if ($d < 0) $d += 1440; // 跨日
  return $d;
}

function hours2f(?int $mins): ?float {
  if ($mins===null) return null;
  return round($mins/60, 2);
}

function infer_status(?string $in, ?string $out, ?int $mins): string {
  if (!$in || !$out) return '缺卡';
  if ($mins!==null && $mins > 480) return '加班';
  return '正常';
}

// 安全字串
function s($v): string { return trim((string)$v); }
