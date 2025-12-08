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
-- 資料表結構 `ramen_members`
--

CREATE TABLE `ramen_members` (
  `id` int(11) NOT NULL,
  `姓名` varchar(50) NOT NULL,
  `電話` varchar(20) NOT NULL,
  `密碼` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `生日` date DEFAULT NULL,
  `地址` text DEFAULT NULL,
  `會員點數` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `驗證碼` varchar(6) DEFAULT NULL,
  `驗證碼建立時間` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `ramen_members`
--

INSERT INTO `ramen_members` (`id`, `姓名`, `電話`, `密碼`, `Email`, `生日`, `地址`, `會員點數`, `created_at`, `updated_at`, `驗證碼`, `驗證碼建立時間`) VALUES
(1, 'lkc', '0900000000', '$2y$10$rH9rdC1wLJkdAlnvpJcHM.cNg8C0hy3MJssVFpNFXgZElwnztY7WW', '123@gmail.com', '2000-06-06', '12233232332', 270, '2025-09-25 02:09:27', '2025-11-01 08:44:11', '415681', '2025-10-03 12:33:17'),
(2, '1', '0901123123', '$2y$10$AuHCxxXzMWYBhqr2wMpRheGFVC8qgbJkxfrKePneCQeU2/blJa/ju', NULL, '2025-09-04', NULL, 450, '2025-09-25 02:17:49', '2025-10-31 00:53:23', '778037', '2025-10-03 11:42:47'),
(3, '2', '0900789789', '$2y$10$gKzmJUjrkAt3a/9D9zRe9ejJHzaMyC7OfarfniFik.sW8iKjbIbA.', NULL, '2025-09-03', NULL, 0, '2025-09-26 02:03:48', '2025-10-05 12:40:35', NULL, NULL),
(4, '0', '0908000000', '$2y$10$aXH47b0/YNekjGORGo3UNuKpL7EQYfSj5G0mlEzNFT8tsLM8yovaa', NULL, '2025-09-06', NULL, 0, '2025-09-26 05:57:48', '2025-10-05 12:40:35', NULL, NULL),
(5, '4', '0988888888', '$2y$10$pEneZ/Xp8Thp4KVVY3ETWOeXPDvWVGSCuuWAIYK0WGpR1WCP2oYQm', NULL, '2025-09-04', NULL, 0, '2025-09-26 07:24:01', '2025-10-05 12:40:35', NULL, NULL),
(6, '123', '0900000001', '$2y$10$.a8hMj5H9MGbK2RHREgW2uo9x9Scfd55ZN/1b6DvOoGxAzxqZsjvu', NULL, '2025-10-16', NULL, 0, '2025-10-01 01:41:04', '2025-10-05 12:40:35', NULL, NULL),
(7, '567', '0911111111', '$2y$10$xCYQ6aP6NKN23u1M0m3Sd.KSfdjkriildI4p6cjK0UUoADkQc9P2e', NULL, '2025-10-04', NULL, 0, '2025-10-01 03:28:44', '2025-10-05 12:40:35', '914853', '2025-10-01 05:30:33'),
(8, 'y', '0977777777', '$2y$10$JDQe7J21ZctQLWBbP0w2ke02zW9ZEQm7qd.dVRIyq8I0jSmC1lQey', '1234@gmail.com', NULL, '12345', 50, '2025-10-13 08:28:56', '2025-10-13 08:30:49', NULL, NULL),
(11, '3', '0933333333', '$2y$10$k9Xs4iesblwQy2o1lIYhK.5mf1Ek.vdmfM0R0qY.4pHUe3rXGLwrW', '0303@gmail.com', '1999-08-02', NULL, 480, '2025-10-15 06:43:50', '2025-11-14 19:02:32', NULL, NULL),
(12, 'oo', '0978787787', '$2y$10$DkX1OkMMMK0n5/N/X7CmO.MVHr0SpEwsvOfvYb8qnEW65gWW6z4LS', NULL, '2025-11-06', NULL, 0, '2025-11-07 04:15:31', '2025-11-07 04:15:31', NULL, NULL),
(13, 'najaemin', '0981813813', '$2y$10$aLfZiNosVmYNsH91/PHNk.tJ7yDK7MzGymQuZXKSWAHStpmciwlYO', 'na@gmail.com', '2000-08-13', NULL, 0, '2025-11-07 04:34:26', '2025-11-07 04:34:26', NULL, NULL),
(14, 'yn', '0901000000', '$2y$10$r1Muc8T8yY3t1Qiw6MwdFu6iuVsjls38eR05tXB96UIWFg.Xvzev6', '110534133@stu.ukn.edu.tw', '2005-01-24', NULL, 0, '2025-11-13 09:38:53', '2025-11-28 02:37:28', NULL, NULL),
(15, 'vita', '0909090909', '$2y$10$AKDty7qlL1yNBTat4Jkycu8BYGYZFHv.lXA8Rh34zX4N5/ceF06hG', '110534119@stu.ukn.edu.tw', '2025-11-04', NULL, -20, '2025-11-14 02:25:29', '2025-11-14 05:34:04', NULL, NULL),
(16, '123', '0901231231', '$2y$10$iLQyn5m59HpkRgvvktNAvu0RRaZH5mYeOrRzLK8DgM9R9dH9TOlj2', '110534133@stu.ukn.edu.tw', '2000-12-05', NULL, 0, '2025-12-05 01:30:31', '2025-12-05 01:30:31', NULL, NULL);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `ramen_members`
--
ALTER TABLE `ramen_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `電話` (`電話`),
  ADD UNIQUE KEY `unique_phone` (`電話`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ramen_members`
--
ALTER TABLE `ramen_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
