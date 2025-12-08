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
-- 資料表結構 `ramen_rewards`
--

CREATE TABLE `ramen_rewards` (
  `優惠券編號` int(11) NOT NULL,
  `商品名稱` varchar(100) NOT NULL COMMENT '商品名稱',
  `需要點數` int(11) NOT NULL COMMENT '需要點數',
  `圖片` varchar(255) DEFAULT NULL COMMENT '商品圖片',
  `建立時間` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `ramen_rewards`
--

INSERT INTO `ramen_rewards` (`優惠券編號`, `商品名稱`, `需要點數`, `圖片`, `建立時間`) VALUES
(1, '溏心蛋', 30, 'assets/img/coupon-discount.jpg', '2025-10-15 02:44:45'),
(2, '加麵', 40, 'assets/img/coupon-extra-noodle.jpg', '2025-10-15 02:44:45'),
(3, '拉麵套餐', 400, 'assets/img/coupon-ramen-set.jpg', '2025-10-15 02:44:45');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `ramen_rewards`
--
ALTER TABLE `ramen_rewards`
  ADD PRIMARY KEY (`優惠券編號`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
