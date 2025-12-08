-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2025 年 12 月 08 日 09:39
-- 伺服器版本： 10.4.28-MariaDB
-- PHP 版本： 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `lamain`
--

-- --------------------------------------------------------

--
-- 資料表結構 `ramen_orders`
--

CREATE TABLE `ramen_orders` (
  `訂單編號` varchar(20) NOT NULL COMMENT '訂單編號',
  `電話` varchar(20) NOT NULL COMMENT '對應會員電話',
  `訂單日期` date DEFAULT NULL COMMENT '訂單日期',
  `總金額` int(11) DEFAULT NULL COMMENT '訂單總金額',
  `獲得點數` int(11) DEFAULT 0 COMMENT '獲得點數',
  `商品明細` text DEFAULT NULL COMMENT '商品明細（JSON 或文字）',
  `建立時間` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `ramen_orders`
--

INSERT INTO `ramen_orders` (`訂單編號`, `電話`, `訂單日期`, `總金額`, `獲得點數`, `商品明細`, `建立時間`) VALUES
('ORD202510170001', '0933333333', '2025-10-17', 1200, 120, '拉麵三人豪華套餐', '2025-10-17 02:27:40'),
('ORD202510170002', '0933333333', '2025-10-25', 300, 30, '豚骨拉麵', '2025-10-25 02:17:12'),
('ORD202510170003', '0933333333', '2025-10-27', 300, 30, '醬油拉麵', '2025-10-28 02:17:12'),
('ORD202511050004', '0933333333', NULL, 750, 75, '[{\"id\":4,\"name\":\"叉燒拉麵\",\"price\":250,\"quantity\":3,\"points\":25}]', '2025-11-05 06:50:10'),
('ORD202511050005', '0933333333', NULL, 560, 56, '[{\"id\":7,\"name\":\"豚骨拉麵\",\"price\":280,\"quantity\":2,\"points\":28}]', '2025-11-05 06:57:54'),
('ORD202511050006', '0933333333', NULL, 400, 40, '[{\"id\":6,\"name\":\"醬油拉麵\",\"price\":200,\"quantity\":2,\"points\":20}]', '2025-11-05 07:12:38'),
('ORD202511060007', '0900000000', NULL, 60, 6, '[{\"id\":1,\"name\":\"溏心蛋\",\"price\":30,\"quantity\":2,\"points\":3}]', '2025-11-06 03:12:54'),
('ORD202511070008', '0900000000', NULL, 60, 6, '[{\"id\":1,\"name\":\"溏心蛋\",\"price\":30,\"quantity\":2,\"points\":3}]', '2025-11-07 01:46:34'),
('ORD202511070009', '0900000000', '2025-11-07', 30, 3, '溏心蛋 x1', '2025-11-07 01:47:26'),
('ORD202511070010', '0900000000', '2025-11-07', 40, 4, '加麵 x1', '2025-11-07 01:47:59'),
('ORD202511070011', '0900000000', '2025-11-07', 100, 10, '溏心蛋 x2, 加麵 x1', '2025-11-07 01:48:24'),
('ORD202511100012', '0933333333', '2025-11-10', 800, 80, '拉麵套餐 x2', '2025-11-10 08:28:19'),
('ORD202511130013', '0900000000', '2025-11-13', 100, 10, '冰淇淋 x2', '2025-11-13 02:40:17'),
('ORD202511140014', '0909090909', '2025-11-14', 440, 44, '加麵 x1, 拉麵套餐 x1', '2025-11-14 02:26:41'),
('ORD202511140015', '0901000000', '2025-11-14', 180, 18, '溏心蛋 x6', '2025-11-14 05:35:10');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `ramen_orders`
--
ALTER TABLE `ramen_orders`
  ADD PRIMARY KEY (`訂單編號`),
  ADD KEY `fk_會員電話` (`電話`);

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `ramen_orders`
--
ALTER TABLE `ramen_orders`
  ADD CONSTRAINT `fk_會員電話` FOREIGN KEY (`電話`) REFERENCES `ramen_members` (`電話`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
