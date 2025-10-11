-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 12, 2025 lúc 12:53 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `event`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdatsukien`
--

CREATE TABLE `chitietdatsukien` (
  `ID_CT` int(11) NOT NULL,
  `ID_DatLich` int(11) NOT NULL,
  `ID_TB` int(11) DEFAULT NULL,
  `ID_Combo` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT 1,
  `DonGia` decimal(15,2) NOT NULL,
  `ThanhTien` decimal(15,2) GENERATED ALWAYS AS (`SoLuong` * `DonGia`) STORED,
  `GhiChu` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietdatsukien`
--

INSERT INTO `chitietdatsukien` (`ID_CT`, `ID_DatLich`, `ID_TB`, `ID_Combo`, `SoLuong`, `DonGia`, `GhiChu`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 4, NULL, 5, 1, 10000000.00, 'Combo thiết bị', '2025-10-09 07:22:08', '2025-10-09 07:22:08'),
(2, 5, NULL, 5, 1, 10000000.00, 'Combo thiết bị', '2025-10-09 08:22:58', '2025-10-09 08:22:58'),
(22, 6, 6, NULL, 1, 1800000.00, NULL, '2025-10-11 21:58:16', '2025-10-11 21:58:16'),
(23, 6, 14, NULL, 1, 400000.00, NULL, '2025-10-11 21:58:16', '2025-10-11 21:58:16'),
(24, 6, 4, NULL, 1, 2000000.00, NULL, '2025-10-11 21:58:16', '2025-10-11 21:58:16'),
(25, 6, NULL, 5, 1, 10000000.00, NULL, '2025-10-11 21:58:16', '2025-10-11 21:58:16');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combo`
--

CREATE TABLE `combo` (
  `ID_Combo` int(11) NOT NULL,
  `TenCombo` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `GiaCombo` decimal(15,2) NOT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combo`
--

INSERT INTO `combo` (`ID_Combo`, `TenCombo`, `MoTa`, `GiaCombo`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'Combo Hội nghị cơ bản', 'Gói thiết bị phục vụ hội nghị nhỏ, 50 - 100 khách', 7000000.00, '2025-09-08 19:52:41', '2025-09-08 19:52:41'),
(2, 'Combo Hội nghị chuyên nghiệp', 'Gói thiết bị cho hội nghị lớn, 200 - 500 khách', 20000000.00, '2025-09-08 19:52:41', '2025-09-08 19:52:41'),
(3, 'Combo Tiệc cưới sang trọng', 'Gói thiết bị âm thanh ánh sáng cho tiệc cưới tại nhà hàng/khách sạn', 15000000.00, '2025-09-08 19:52:41', '2025-09-08 19:52:41'),
(4, 'Combo Sân khấu ca nhạc', 'Gói thiết bị chuyên nghiệp cho liveshow, ca nhạc ngoài trời', 50000000.00, '2025-09-08 19:52:41', '2025-09-08 19:52:41'),
(5, 'Combo Triển lãm thương mại', 'Gói thiết bị hỗ trợ triển lãm, booth giới thiệu sản phẩm', 10000000.00, '2025-09-08 19:52:41', '2025-09-08 19:52:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combochitiet`
--

CREATE TABLE `combochitiet` (
  `ID_Combo` int(11) NOT NULL,
  `ID_TB` int(11) NOT NULL,
  `SoLuong` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combochitiet`
--

INSERT INTO `combochitiet` (`ID_Combo`, `ID_TB`, `SoLuong`) VALUES
(1, 1, 2),
(1, 2, 2),
(1, 3, 1),
(1, 6, 1),
(1, 7, 1),
(2, 1, 6),
(2, 2, 6),
(2, 4, 1),
(2, 6, 2),
(2, 8, 4),
(2, 10, 10),
(3, 1, 4),
(3, 2, 4),
(3, 11, 10),
(3, 12, 2),
(3, 14, 1),
(4, 1, 10),
(4, 2, 10),
(4, 4, 2),
(4, 8, 10),
(4, 11, 20),
(4, 12, 10),
(4, 13, 4),
(5, 6, 2),
(5, 7, 2),
(5, 8, 4),
(5, 15, 2),
(5, 16, 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combo_loaisk`
--

CREATE TABLE `combo_loaisk` (
  `ID_Combo` int(11) NOT NULL,
  `ID_LoaiSK` int(11) NOT NULL,
  `UuTien` int(11) DEFAULT 1,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combo_loaisk`
--

INSERT INTO `combo_loaisk` (`ID_Combo`, `ID_LoaiSK`, `UuTien`, `NgayTao`) VALUES
(1, 1, 1, '2025-10-09 07:06:19'),
(1, 5, 3, '2025-10-09 07:06:19'),
(2, 1, 2, '2025-10-09 07:06:19'),
(2, 5, 2, '2025-10-09 07:06:19'),
(3, 4, 1, '2025-10-09 07:06:19'),
(3, 6, 2, '2025-10-09 07:06:19'),
(4, 2, 1, '2025-10-09 07:06:19'),
(4, 6, 3, '2025-10-09 07:06:19'),
(5, 3, 1, '2025-10-09 07:06:19'),
(5, 6, 1, '2025-10-09 07:06:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combo_thietbi`
--

CREATE TABLE `combo_thietbi` (
  `ID_Combo` int(11) NOT NULL,
  `ID_TB` int(11) NOT NULL,
  `SoLuong` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combo_thietbi`
--

INSERT INTO `combo_thietbi` (`ID_Combo`, `ID_TB`, `SoLuong`) VALUES
(1, 1, 2),
(1, 2, 2),
(1, 3, 1),
(1, 6, 1),
(1, 7, 1),
(2, 1, 10),
(2, 2, 8),
(2, 4, 1),
(2, 8, 10),
(2, 10, 20),
(2, 11, 8),
(2, 12, 2),
(2, 15, 4),
(3, 1, 4),
(3, 2, 4),
(3, 6, 1),
(3, 7, 1),
(3, 10, 12),
(3, 11, 4),
(4, 6, 2),
(4, 7, 2),
(4, 8, 2),
(4, 14, 2),
(4, 15, 4),
(5, 1, 6),
(5, 2, 4),
(5, 9, 2),
(5, 10, 12),
(5, 12, 2),
(5, 14, 1),
(5, 15, 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `LastMessage_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`, `updated_at`, `LastMessage_ID`) VALUES
(1, 3, 17, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL),
(2, 29, 17, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL),
(3, 98, 17, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL),
(4, 3, 118, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL),
(5, 29, 118, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL),
(6, 98, 124, '2025-10-09 07:09:35', '2025-10-09 07:09:35', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhgia`
--

CREATE TABLE `danhgia` (
  `ID_DanhGia` int(11) NOT NULL,
  `ID_SuKien` int(11) NOT NULL,
  `ID_KhachHang` int(11) NOT NULL,
  `DiemDanhGia` tinyint(3) UNSIGNED NOT NULL,
  `NoiDung` text DEFAULT NULL,
  `ThoiGianDanhGia` timestamp NOT NULL DEFAULT current_timestamp(),
  `DanhGiaDiaDiem` tinyint(3) UNSIGNED DEFAULT NULL,
  `DanhGiaThietBi` tinyint(3) UNSIGNED DEFAULT NULL,
  `DanhGiaNhanVien` tinyint(3) UNSIGNED DEFAULT NULL,
  `LoaiDanhGia` enum('Sự kiện','Địa điểm','Thiết bị','Nhân viên') DEFAULT 'Sự kiện',
  `TrangThai` enum('Hiển thị','Ẩn') DEFAULT 'Hiển thị',
  `GhiChu` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `datlichsukien`
--

CREATE TABLE `datlichsukien` (
  `ID_DatLich` int(11) NOT NULL,
  `ID_KhachHang` int(11) NOT NULL,
  `TenSuKien` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `ID_DD` int(11) NOT NULL,
  `ID_LoaiSK` int(11) NOT NULL,
  `SoNguoiDuKien` int(11) DEFAULT NULL,
  `NganSach` decimal(15,2) DEFAULT NULL,
  `TienCocYeuCau` decimal(15,2) DEFAULT 0.00,
  `TrangThaiDuyet` enum('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
  `TrangThaiThanhToan` enum('Chưa thanh toán','Đã đặt cọc','Đã thanh toán đủ','Hoàn tiền') DEFAULT 'Chưa thanh toán',
  `GhiChu` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `datlichsukien`
--

INSERT INTO `datlichsukien` (`ID_DatLich`, `ID_KhachHang`, `TenSuKien`, `MoTa`, `NgayBatDau`, `NgayKetThuc`, `ID_DD`, `ID_LoaiSK`, `SoNguoiDuKien`, `NganSach`, `TienCocYeuCau`, `TrangThaiDuyet`, `TrangThaiThanhToan`, `GhiChu`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 5, 'ádasdas', 'qseqwe', '2025-10-09 07:35:00', '2025-10-09 09:35:00', 9, 6, 300, 400000.00, 0.00, 'Từ chối', 'Chưa thanh toán', 'ngày hôm nay bị trùng lịch. xin cảm ơn ', '2025-10-08 12:36:39', '2025-10-09 05:09:01'),
(2, 5, 'qưeqeqwq', 'qưeqeq', '2025-10-17 07:40:00', '2025-10-17 09:40:00', 1, 1, 500, 400000.00, 0.00, 'Đã duyệt', 'Chưa thanh toán', 'oke', '2025-10-08 12:39:26', '2025-10-09 05:08:30'),
(4, 5, 'seqeqqw', '12313', '2025-10-17 12:22:00', '2025-10-17 14:22:00', 4, 3, 2233, 22333.00, 0.00, 'Từ chối', 'Chưa thanh toán', 'ko hợp lí', '2025-10-09 07:22:08', '2025-10-09 07:23:37'),
(5, 5, '321312', '1313', '2025-10-15 13:01:00', '2025-10-15 15:01:00', 8, 3, 2444, 400000.00, 0.00, 'Chờ duyệt', 'Chưa thanh toán', 'Đăng ký từ website', '2025-10-09 08:22:58', '2025-10-09 08:22:58'),
(6, 17, 'Tiệc cuối năm', NULL, '2025-12-22 18:00:00', '0000-00-00 00:00:00', 9, 6, 200, 20000000000.00, 0.00, 'Đã duyệt', 'Chưa thanh toán', '', '2025-10-11 21:53:38', '2025-10-11 22:05:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diadiem`
--

CREATE TABLE `diadiem` (
  `ID_DD` int(11) NOT NULL,
  `TenDiaDiem` varchar(255) NOT NULL,
  `LoaiDiaDiem` enum('Trong nhà','Ngoài trời') NOT NULL,
  `DiaChi` varchar(255) NOT NULL,
  `SucChua` int(11) NOT NULL,
  `GiaThue` decimal(15,2) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `HinhAnh` varchar(255) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `TrangThaiHoatDong` varchar(50) DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `diadiem`
--

INSERT INTO `diadiem` (`ID_DD`, `TenDiaDiem`, `LoaiDiaDiem`, `DiaChi`, `SucChua`, `GiaThue`, `MoTa`, `HinhAnh`, `NgayTao`, `NgayCapNhat`, `TrangThaiHoatDong`) VALUES
(1, 'Trung tâm Hội nghị White Palace', 'Trong nhà', '194 Hoàng Văn Thụ, Quận Phú Nhuận, TP.HCM', 1500, 120000000.00, 'Không gian sang trọng, phù hợp hội nghị và tiệc cưới', 'whitepalace.jpg', '2025-09-08 10:10:14', '2025-10-08 04:38:40', 'Hoạt động'),
(2, 'Nhà hát Thành phố Hồ Chí Minh', 'Trong nhà', '07 Công Trường Lam Sơn, Quận 1, TP.HCM', 500, 80000000.00, 'Địa điểm sang trọng, phù hợp các chương trình nghệ thuật', 'nhahat_tphcm.jpg', '2025-09-08 10:10:14', '2025-09-08 10:24:25', 'Hoạt động'),
(3, 'Nhà thi đấu Quân khu 7', 'Trong nhà', '202 Hoàng Văn Thụ, Quận Tân Bình, TP.HCM', 5000, 150000000.00, 'Địa điểm tổ chức sự kiện, thi đấu thể thao trong nhà', 'nhathidau_qk7.jpg', '2025-09-08 10:10:14', '2025-09-08 10:24:25', 'Hoạt động'),
(4, 'Trung tâm GEM Center', 'Trong nhà', '08 Nguyễn Bỉnh Khiêm, Quận 1, TP.HCM', 1000, 100000000.00, 'Địa điểm tổ chức hội nghị và sự kiện doanh nghiệp', 'gemcenter.jpg', '2025-09-08 10:10:14', '2025-09-08 10:24:25', 'Hoạt động'),
(5, 'Trung tâm Hội nghị Riverside Palace', 'Trong nhà', '360D Bến Vân Đồn, Quận 4, TP.HCM', 1200, 90000000.00, 'Không gian tiệc cưới, hội nghị sang trọng tại trung tâm TP.HCM', 'riverside_palace.jpg', '2025-09-08 10:10:14', '2025-09-08 10:24:25', 'Hoạt động'),
(6, 'Sân vận động Thống Nhất', 'Ngoài trời', '138 Đào Duy Từ, Quận 10, TP.HCM', 25000, 300000000.00, 'Sân vận động trung tâm TP.HCM, phù hợp biểu diễn ca nhạc', 'svd_thongnhat.jpg', '2025-09-08 10:10:28', '2025-09-08 10:24:25', 'Hoạt động'),
(7, 'Nhà thi đấu Phú Thọ', 'Trong nhà', 'Quận 11, TP.HCM', 5000, 80000000.00, 'Địa điểm tổ chức thể thao, ca nhạc, triển lãm, phù hợp các sự kiện lớn', 'nha_thidau_phutho.jpg', '2025-09-08 10:10:28', '2025-09-10 01:10:44', 'Hoạt động'),
(8, 'Công viên 23/9', 'Ngoài trời', 'Phạm Ngũ Lão, Quận 1, TP.HCM', 3000, 80000000.00, 'Địa điểm tổ chức lễ hội, hội chợ ngay trung tâm TP.HCM', 'cv_23_9.jpg', '2025-09-08 10:10:28', '2025-09-08 10:24:25', 'Hoạt động'),
(9, 'Phố đi bộ Nguyễn Huệ', 'Ngoài trời', 'Nguyễn Huệ, Quận 1, TP.HCM', 5000, 150000000.00, 'Không gian công cộng nổi tiếng, phù hợp sự kiện âm nhạc, văn hóa', 'nguyen_hue.jpg', '2025-09-08 10:10:28', '2025-09-08 10:24:25', 'Hoạt động'),
(10, 'Công viên Tao Đàn', 'Ngoài trời', 'Quận 1, TP.HCM', 2000, 60000000.00, 'Công viên trung tâm, tổ chức hội hoa xuân và lễ hội văn hóa', 'cv_taodan.jpg', '2025-09-08 10:10:28', '2025-10-08 17:44:58', 'Hoạt động');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diadiem_loaisk`
--

CREATE TABLE `diadiem_loaisk` (
  `ID_DD` int(11) NOT NULL,
  `ID_LoaiSK` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `diadiem_loaisk`
--

INSERT INTO `diadiem_loaisk` (`ID_DD`, `ID_LoaiSK`) VALUES
(1, 1),
(1, 4),
(2, 2),
(6, 5),
(9, 2),
(9, 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kehoachthuchien`
--

CREATE TABLE `kehoachthuchien` (
  `id_kehoach` int(11) NOT NULL,
  `id_sukien` int(11) NOT NULL,
  `ten_kehoach` varchar(255) NOT NULL,
  `noidung` text DEFAULT NULL,
  `ngay_batdau` date NOT NULL,
  `ngay_ketthuc` date NOT NULL,
  `trangthai` enum('Chưa bắt đầu','Đang thực hiện','Hoàn thành') DEFAULT 'Chưa bắt đầu',
  `id_nhanvien` int(11) DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhanginfo`
--

CREATE TABLE `khachhanginfo` (
  `ID_KhachHang` int(11) NOT NULL,
  `ID_User` int(11) DEFAULT NULL,
  `HoTen` varchar(100) NOT NULL,
  `SoDienThoai` varchar(10) NOT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `NgaySinh` date DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khachhanginfo`
--

INSERT INTO `khachhanginfo` (`ID_KhachHang`, `ID_User`, `HoTen`, `SoDienThoai`, `DiaChi`, `NgaySinh`, `NgayTao`, `NgayCapNhat`) VALUES
(5, 17, 'Bùi Thanh Bình', '0707102548', '14 NVB', '2021-10-21', '2025-09-23 20:48:28', '2025-10-09 09:44:05'),
(12, 98, 'Vũ Thảo', '0356690717', '60 đường số 1', '2003-12-21', '2025-09-24 11:37:42', '2025-09-24 11:37:42'),
(15, 118, 'Vũ Thảo Ánh', '0356690717', '60 đường số 1', '2009-12-11', '2025-09-24 18:57:20', '2025-09-24 22:45:30'),
(17, 124, 'Vũ Thảo Ánh', '0356690717', '60 đường số 4\r\n', '2003-09-16', '2025-09-25 02:09:32', '2025-10-09 05:01:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichlamviec`
--

CREATE TABLE `lichlamviec` (
  `ID_LLV` int(11) NOT NULL,
  `ID_DatLich` int(11) NOT NULL,
  `ID_NhanVien` int(11) NOT NULL,
  `NhiemVu` varchar(255) NOT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` enum('Chưa làm','Đang làm','Hoàn thành','Báo sự cố') DEFAULT 'Chưa làm',
  `GhiChu` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_kehoach` int(11) DEFAULT NULL,
  `congviec` varchar(255) DEFAULT NULL,
  `han_hoanthanh` date DEFAULT NULL,
  `tiendo` varchar(50) DEFAULT '0%'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichlamviec`
--

INSERT INTO `lichlamviec` (`ID_LLV`, `ID_DatLich`, `ID_NhanVien`, `NhiemVu`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`, `GhiChu`, `NgayTao`, `NgayCapNhat`, `id_kehoach`, `congviec`, `han_hoanthanh`, `tiendo`) VALUES
(1, 2, 3, 'Chuẩn bị và setup thiết bị cho sự kiện', '2024-01-15 00:00:00', '2024-01-16 00:00:00', '', 'Cần chuẩn bị đầy đủ thiết bị âm thanh và ánh sáng', '2025-10-11 22:22:02', '2025-10-11 22:22:02', NULL, NULL, NULL, '0%'),
(2, 6, 4, 'Quản lý và điều phối sự kiện', '2024-01-20 00:00:00', '2024-01-21 00:00:00', '', 'Đảm bảo sự kiện diễn ra suôn sẻ', '2025-10-11 22:22:02', '2025-10-11 22:22:02', NULL, NULL, NULL, '0%');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaisukien`
--

CREATE TABLE `loaisukien` (
  `ID_LoaiSK` int(11) NOT NULL,
  `TenLoai` varchar(100) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loaisukien`
--

INSERT INTO `loaisukien` (`ID_LoaiSK`, `TenLoai`, `MoTa`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'Hội nghị - Hội thảo', 'Các sự kiện hội nghị, hội thảo, hội thảo khoa học', '2025-09-08 10:51:44', '2025-09-08 10:51:44'),
(2, 'Văn hóa - Nghệ thuật', 'Liveshow, nhạc kịch, biểu diễn nghệ thuật', '2025-09-08 10:51:44', '2025-09-08 10:51:44'),
(3, 'Thương mại - Quảng bá', 'Triển lãm, ra mắt sản phẩm, hội chợ', '2025-09-08 10:51:44', '2025-09-08 10:51:44'),
(4, 'Tiệc - Lễ kỷ niệm', 'Tiệc cưới, tiệc sinh nhật, Gala Dinner', '2025-09-08 10:51:44', '2025-09-08 10:51:44'),
(5, 'Thể thao - Giải trí', 'Giải bóng đá, eSports, hoạt động thể thao', '2025-09-08 10:51:44', '2025-09-08 10:51:44'),
(6, 'Cộng đồng - Xã hội', 'Sự kiện từ thiện, lễ hội cộng đồng, truyền thống', '2025-09-08 10:51:44', '2025-09-08 10:51:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `MessageText` text DEFAULT NULL,
  `IsRead` tinyint(1) DEFAULT NULL,
  `SentAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `MessageText`, `IsRead`, `SentAt`, `UpdatedAt`) VALUES
(1, 1, 3, 'Xin chào! Tôi có thể giúp gì cho bạn?', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(2, 1, 17, 'Tôi muốn đăng ký sự kiện', 1, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(3, 2, 29, 'Chào bạn! Bạn cần hỗ trợ gì?', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(4, 3, 98, 'Tôi có thể giúp bạn đăng ký sự kiện', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(5, 4, 3, 'Chào bạn! Tôi có thể hỗ trợ gì?', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(6, 5, 29, 'Bạn cần tư vấn về sự kiện nào?', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17'),
(7, 6, 98, 'Tôi sẵn sàng hỗ trợ bạn!', 0, '2025-10-09 14:09:35', '2025-10-10 23:17:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvieninfo`
--

CREATE TABLE `nhanvieninfo` (
  `ID_NhanVien` int(11) NOT NULL,
  `ID_User` int(11) DEFAULT NULL,
  `HoTen` varchar(100) NOT NULL,
  `SoDienThoai` varchar(10) NOT NULL,
  `DiaChi` varchar(255) NOT NULL,
  `NgaySinh` date NOT NULL,
  `ChucVu` varchar(100) DEFAULT NULL,
  `Luong` decimal(15,2) DEFAULT NULL,
  `NgayVaoLam` date DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvieninfo`
--

INSERT INTO `nhanvieninfo` (`ID_NhanVien`, `ID_User`, `HoTen`, `SoDienThoai`, `DiaChi`, `NgaySinh`, `ChucVu`, `Luong`, `NgayVaoLam`, `NgayTao`, `NgayCapNhat`) VALUES
(3, 29, 'Bùi Thanh Bình', '0323456774', '12 NVB', '2003-02-02', 'Quản lí tổ chức', 8000000.00, '2025-06-24', '2025-09-24 02:06:23', '2025-10-03 01:16:32'),
(4, 39, 'Đình Hiếu', '0323456677', '12 GV', '2001-12-11', 'Nhân viên kỹ thuật', 4000000.00, '2025-05-24', '2025-09-24 02:11:39', '2025-10-03 01:17:26'),
(5, 96, 'Đường Yên', '0356690717', '60 đường số 1', '2023-12-11', 'Nhân viên âm thanh', 400000.00, '2025-06-24', '2025-09-24 10:28:06', '2025-09-24 11:51:12'),
(7, 119, 'Đường Yên', '0356690717', '60 đường số 2', '1111-11-11', 'Quản lí tổ chức', 10000000.00, '2025-06-24', '2025-09-24 19:17:28', '2025-10-09 02:24:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phanquyen`
--

CREATE TABLE `phanquyen` (
  `ID_Role` int(11) NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `TrangThai` enum('Hoạt động','Ngừng') DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phanquyen`
--

INSERT INTO `phanquyen` (`ID_Role`, `RoleName`, `MoTa`, `TrangThai`) VALUES
(1, 'Admin', 'Toàn quyền quản trị hệ thống, quản lý tài khoản, thiết bị, địa điểm, sự kiện.', 'Hoạt động'),
(2, 'Quản lý tổ chức', 'Quản lý tổng thể các sự kiện, địa điểm, nhân sự và thiết bị của tổ chức.Duyệt đơn đặt lịch, phân công nhân viên, điều phối thiết bị cho từng sự kiện.', 'Hoạt động'),
(3, 'Quản lý sự kiện', 'Có thể đặt lịch cho khách hàng, tư vấn cho khách hàng', 'Hoạt động'),
(4, 'Nhân viên', 'Thực hiện các công việc kỹ thuật, lắp đặt và hỗ trợ trong quá trình diễn ra sự kiện.', 'Hoạt động'),
(5, 'Khách hàng', 'Người dùng đặt lịch sự kiện, chọn dịch vụ và theo dõi trạng thái sự kiện của mình.', 'Hoạt động'),
(6, 'Khách vãng lai', 'Người dùng chưa đăng nhập, chỉ có thể xem thông tin sự kiện và địa điểm.', 'Hoạt động');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sukien`
--

CREATE TABLE `sukien` (
  `ID_SuKien` int(11) NOT NULL,
  `ID_DatLich` int(11) NOT NULL,
  `MaSuKien` varchar(50) DEFAULT NULL,
  `TenSuKien` varchar(255) NOT NULL,
  `NgayBatDauThucTe` datetime NOT NULL,
  `NgayKetThucThucTe` datetime NOT NULL,
  `DiaDiemThucTe` varchar(255) DEFAULT NULL,
  `TrangThaiThucTe` enum('Đang chuẩn bị','Đang diễn ra','Hoàn thành','Hủy') DEFAULT 'Đang chuẩn bị',
  `TongChiPhiThucTe` decimal(15,2) DEFAULT 0.00,
  `DanhGiaKhachHang` tinyint(4) DEFAULT NULL,
  `NhanXetKhachHang` text DEFAULT NULL,
  `GhiChuQuanLy` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sukien`
--

INSERT INTO `sukien` (`ID_SuKien`, `ID_DatLich`, `MaSuKien`, `TenSuKien`, `NgayBatDauThucTe`, `NgayKetThucThucTe`, `DiaDiemThucTe`, `TrangThaiThucTe`, `TongChiPhiThucTe`, `DanhGiaKhachHang`, `NhanXetKhachHang`, `GhiChuQuanLy`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 2, 'EV202510090002', 'qưeqeqwq', '2025-10-17 07:40:00', '2025-10-17 09:40:00', 'Trung tâm Hội nghị White Palace', 'Đang chuẩn bị', 120000000.00, NULL, NULL, 'oke', '2025-10-09 05:08:30', '2025-10-09 05:08:30'),
(2, 6, 'EV202510120006', 'Tiệc cuối năm', '2025-12-22 18:00:00', '0000-00-00 00:00:00', 'Phố đi bộ Nguyễn Huệ', 'Đang chuẩn bị', 150000000.00, NULL, NULL, '', '2025-10-11 22:05:07', '2025-10-11 22:05:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sukien_combo`
--

CREATE TABLE `sukien_combo` (
  `ID_SuKien` int(11) NOT NULL,
  `ID_Combo` int(11) NOT NULL,
  `SoLuong` int(11) DEFAULT 1,
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sukien_thietbi`
--

CREATE TABLE `sukien_thietbi` (
  `ID_SuKien` int(11) NOT NULL,
  `ID_TB` int(11) NOT NULL,
  `SoLuong` int(11) DEFAULT 1,
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thanhtoan`
--

CREATE TABLE `thanhtoan` (
  `ID_ThanhToan` int(11) NOT NULL,
  `ID_DatLich` int(11) NOT NULL,
  `SoTien` decimal(15,2) NOT NULL,
  `LoaiThanhToan` enum('Đặt cọc','Thanh toán đủ','Hoàn tiền') DEFAULT 'Đặt cọc',
  `PhuongThuc` enum('Chuyển khoản','Momo','ZaloPay','Visa/MasterCard','Tiền mặt') NOT NULL,
  `TrangThai` enum('Đang xử lý','Thành công','Thất bại') DEFAULT 'Đang xử lý',
  `MaGiaoDich` varchar(100) DEFAULT NULL,
  `NgayThanhToan` timestamp NOT NULL DEFAULT current_timestamp(),
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thietbi`
--

CREATE TABLE `thietbi` (
  `ID_TB` int(11) NOT NULL,
  `TenThietBi` varchar(255) NOT NULL,
  `LoaiThietBi` varchar(100) NOT NULL,
  `HangSX` varchar(100) DEFAULT NULL,
  `SoLuong` int(11) NOT NULL,
  `DonViTinh` varchar(50) DEFAULT NULL,
  `GiaThue` decimal(15,2) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `HinhAnh` varchar(255) DEFAULT NULL,
  `TrangThai` enum('Sẵn sàng','Đang sử dụng','Bảo trì','Hỏng') DEFAULT 'Sẵn sàng',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thietbi`
--

INSERT INTO `thietbi` (`ID_TB`, `TenThietBi`, `LoaiThietBi`, `HangSX`, `SoLuong`, `DonViTinh`, `GiaThue`, `MoTa`, `HinhAnh`, `TrangThai`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'Loa Line Array', 'Âm thanh', 'JBL', 10, 'Cái', 1500000.00, 'Loa sân khấu hội trường, công suất lớn', 'loa_linearray.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(2, 'Micro không dây', 'Âm thanh', 'Shure', 20, 'Cái', 300000.00, 'Micro cầm tay dùng cho MC, ca sĩ', 'micro_khongday.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(3, 'Micro cổ ngỗng', 'Âm thanh', 'Bosch', 15, 'Cái', 250000.00, 'Micro dành cho hội nghị, phát biểu', 'micro_congong.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(4, 'Bàn mixer âm thanh', 'Âm thanh', 'Yamaha', 5, 'Bộ', 2000000.00, 'Bàn trộn tín hiệu âm thanh 16-32 line', 'mixer_yamaha.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(5, 'Amply công suất', 'Âm thanh', 'Crown', 8, 'Bộ', 1200000.00, 'Khuếch đại tín hiệu cho loa', 'amply_crown.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(6, 'Máy chiếu Full HD', 'Hình ảnh', 'Epson', 6, 'Bộ', 1800000.00, 'Máy chiếu độ phân giải cao cho hội trường', 'maychieu_epson.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(7, 'Màn chiếu 150 inch', 'Hình ảnh', 'Apollo', 6, 'Cái', 500000.00, 'Màn chiếu lớn dùng cho hội nghị', 'manchieu_150.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(8, 'Màn hình LED P4', 'Hình ảnh', 'Unilumin', 20, 'Tấm', 2500000.00, 'Màn LED ghép sân khấu ngoài trời', 'led_p4.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(9, 'Camera quay phim HD', 'Hình ảnh', 'Sony', 4, 'Cái', 2200000.00, 'Dùng để livestream và ghi hình sự kiện', 'camera_sony.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(10, 'Đèn Par LED', 'Ánh sáng', 'BeamZ', 30, 'Cái', 200000.00, 'Đèn đổi màu RGB cho sân khấu', 'den_parled.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(11, 'Đèn Moving Head', 'Ánh sáng', 'Martin', 12, 'Cái', 800000.00, 'Đèn quét tia nhiều màu', 'den_movinghead.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(12, 'Máy khói sân khấu', 'Ánh sáng', 'Antari', 5, 'Cái', 600000.00, 'Tạo hiệu ứng khói mờ cho sân khấu', 'may_khoi.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(13, 'Máy tính điều khiển', 'Phụ trợ', 'Dell', 5, 'Cái', 1000000.00, 'Dùng cho kỹ thuật viên điều khiển hệ thống', 'maytinh_dell.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(14, 'Bộ phát Wifi sự kiện', 'Phụ trợ', 'TP-Link', 4, 'Bộ', 400000.00, 'Phủ sóng wifi cho sự kiện', 'phatwifi_tplink.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(15, 'Bộ đàm cầm tay', 'Phụ trợ', 'Motorola', 20, 'Cái', 150000.00, 'Liên lạc nội bộ cho nhân viên', 'bodam_motorola.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(16, 'UPS lưu điện', 'Phụ trợ', 'APC', 6, 'Bộ', 700000.00, 'Bộ lưu điện dự phòng cho thiết bị sự kiện', 'ups_apc.jpg', 'Sẵn sàng', '2025-09-08 11:26:11', '2025-09-08 11:26:11'),
(17, 'Loa Subwoofer', 'Âm thanh', 'JBL', 10, 'Cái', 18000000.00, 'Loa trầm công suất lớn cho sân khấu xịn lắm', '68df8ab9c2e69_1759480505.webp', 'Sẵn sàng', '2025-10-03 01:35:05', '2025-10-03 01:36:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `ID_User` int(11) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `FacebookID` varchar(100) DEFAULT NULL,
  `GoogleID` varchar(100) DEFAULT NULL,
  `ID_Role` int(11) NOT NULL,
  `TrangThai` enum('Hoạt động','Bị khóa','Chưa xác minh') DEFAULT 'Hoạt động',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`ID_User`, `Email`, `Password`, `FacebookID`, `GoogleID`, `ID_Role`, `TrangThai`, `NgayTao`, `NgayCapNhat`) VALUES
(3, 'qtv1@gmail.com', '$2y$10$76AL.x2sD9yFnUQ2j6YlYeXXMAFp4HlCvHxNIVl5j8/.DSUmVl3im', NULL, NULL, 1, 'Hoạt động', '2025-09-19 23:54:54', '2025-09-19 23:54:54'),
(17, 'thanhbinh14062003@gmail.com', '$2y$10$vX5Lacdo5OaAIvtda/0CyOEldWJqSjOVJqr.YKd1O0OwIf9rz8tkS', NULL, NULL, 5, 'Hoạt động', '2025-09-23 20:48:28', '2025-09-23 20:48:28'),
(29, 'qltc2@gmail.com', '$2y$10$ig.u6SQkmvukGKXF7lFlS.D7ikk0Aja1lZPgJzeGeUJAm5zselWP.', NULL, NULL, 2, 'Hoạt động', '2025-09-24 02:06:23', '2025-09-24 02:06:23'),
(39, 'nhanvien1@gmail.com', '$2y$10$aFB3cdypIGWJPW343j4vSOP82d5lc.y4FG0QjqTqZu7RIKeb25GIC', NULL, NULL, 4, 'Hoạt động', '2025-09-24 02:11:39', '2025-09-24 02:11:39'),
(96, 'nhanvien2@gmail.com', '$2y$10$skx3dLcoSSUAt7SNyPDF5u8TNfIVSWGIhvoP6sN22F7LOu7JONQ9q', NULL, NULL, 4, 'Hoạt động', '2025-09-24 10:28:06', '2025-09-24 10:28:06'),
(98, 'thanhbinhcv14@gmail.com', '$2y$10$a.hXLfW6atu4QLo2uFgdquBCXoCnaSWh2y5g7etJcrE98ciK331IC', NULL, NULL, 3, 'Hoạt động', '2025-09-24 11:37:42', '2025-10-09 05:35:19'),
(118, 'khachhang1@gmail.com', 'bb08a3fd9986ef13e4b9a1e432b9deee', NULL, NULL, 5, 'Hoạt động', '2025-09-24 18:57:20', '2025-09-30 11:00:38'),
(119, 'qltc1@gmail.com', '$2y$10$FCLKvilsBjF2A6exn53/OOM9xDm7LffPSZSQhga7Oj4OUYpyyUXYe', NULL, NULL, 2, 'Hoạt động', '2025-09-24 19:17:28', '2025-10-09 02:13:16'),
(124, 'thaoanh@gmail.com', '$2y$10$DS4Pte9et5u.xNby9OQBMORbphO0mz36abpCh0/1NussDlaCOSo8e', NULL, NULL, 5, 'Hoạt động', '2025-09-25 02:09:32', '2025-10-09 03:12:13');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chitietdatsukien`
--
ALTER TABLE `chitietdatsukien`
  ADD PRIMARY KEY (`ID_CT`),
  ADD KEY `fk_ct_dl` (`ID_DatLich`),
  ADD KEY `fk_ct_tb` (`ID_TB`),
  ADD KEY `fk_ct_combo` (`ID_Combo`);

--
-- Chỉ mục cho bảng `combo`
--
ALTER TABLE `combo`
  ADD PRIMARY KEY (`ID_Combo`);

--
-- Chỉ mục cho bảng `combochitiet`
--
ALTER TABLE `combochitiet`
  ADD PRIMARY KEY (`ID_Combo`,`ID_TB`),
  ADD KEY `ID_TB` (`ID_TB`);

--
-- Chỉ mục cho bảng `combo_loaisk`
--
ALTER TABLE `combo_loaisk`
  ADD PRIMARY KEY (`ID_Combo`,`ID_LoaiSK`),
  ADD KEY `idx_loaisk_priority` (`ID_LoaiSK`,`UuTien`);

--
-- Chỉ mục cho bảng `combo_thietbi`
--
ALTER TABLE `combo_thietbi`
  ADD PRIMARY KEY (`ID_Combo`,`ID_TB`),
  ADD KEY `ID_TB` (`ID_TB`);

--
-- Chỉ mục cho bảng `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`),
  ADD KEY `user1_id` (`user1_id`),
  ADD KEY `user2_id` (`user2_id`),
  ADD KEY `fk_conversations_lastmessage` (`LastMessage_ID`);

--
-- Chỉ mục cho bảng `danhgia`
--
ALTER TABLE `danhgia`
  ADD PRIMARY KEY (`ID_DanhGia`),
  ADD KEY `ID_SuKien` (`ID_SuKien`),
  ADD KEY `ID_KhachHang` (`ID_KhachHang`);

--
-- Chỉ mục cho bảng `datlichsukien`
--
ALTER TABLE `datlichsukien`
  ADD PRIMARY KEY (`ID_DatLich`),
  ADD KEY `fk_datlich_khachhang` (`ID_KhachHang`),
  ADD KEY `fk_datlich_diadiem` (`ID_DD`),
  ADD KEY `fk_datlich_loaisk` (`ID_LoaiSK`);

--
-- Chỉ mục cho bảng `diadiem`
--
ALTER TABLE `diadiem`
  ADD PRIMARY KEY (`ID_DD`);

--
-- Chỉ mục cho bảng `diadiem_loaisk`
--
ALTER TABLE `diadiem_loaisk`
  ADD PRIMARY KEY (`ID_DD`,`ID_LoaiSK`),
  ADD KEY `ID_LoaiSK` (`ID_LoaiSK`);

--
-- Chỉ mục cho bảng `kehoachthuchien`
--
ALTER TABLE `kehoachthuchien`
  ADD PRIMARY KEY (`id_kehoach`),
  ADD KEY `id_sukien` (`id_sukien`),
  ADD KEY `id_nhanvien` (`id_nhanvien`);

--
-- Chỉ mục cho bảng `khachhanginfo`
--
ALTER TABLE `khachhanginfo`
  ADD PRIMARY KEY (`ID_KhachHang`),
  ADD UNIQUE KEY `ID_User` (`ID_User`);

--
-- Chỉ mục cho bảng `lichlamviec`
--
ALTER TABLE `lichlamviec`
  ADD PRIMARY KEY (`ID_LLV`),
  ADD KEY `fk_llv_datlich` (`ID_DatLich`),
  ADD KEY `fk_llv_nhanvien` (`ID_NhanVien`),
  ADD KEY `id_kehoach` (`id_kehoach`);

--
-- Chỉ mục cho bảng `loaisukien`
--
ALTER TABLE `loaisukien`
  ADD PRIMARY KEY (`ID_LoaiSK`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `created_at` (`SentAt`);

--
-- Chỉ mục cho bảng `nhanvieninfo`
--
ALTER TABLE `nhanvieninfo`
  ADD PRIMARY KEY (`ID_NhanVien`),
  ADD UNIQUE KEY `ID_User` (`ID_User`);

--
-- Chỉ mục cho bảng `phanquyen`
--
ALTER TABLE `phanquyen`
  ADD PRIMARY KEY (`ID_Role`),
  ADD UNIQUE KEY `TenRole` (`RoleName`);

--
-- Chỉ mục cho bảng `sukien`
--
ALTER TABLE `sukien`
  ADD PRIMARY KEY (`ID_SuKien`),
  ADD UNIQUE KEY `MaSuKien` (`MaSuKien`),
  ADD KEY `fk_sukien_datlich` (`ID_DatLich`);

--
-- Chỉ mục cho bảng `sukien_combo`
--
ALTER TABLE `sukien_combo`
  ADD PRIMARY KEY (`ID_SuKien`,`ID_Combo`),
  ADD KEY `ID_Combo` (`ID_Combo`);

--
-- Chỉ mục cho bảng `sukien_thietbi`
--
ALTER TABLE `sukien_thietbi`
  ADD PRIMARY KEY (`ID_SuKien`,`ID_TB`),
  ADD KEY `ID_TB` (`ID_TB`);

--
-- Chỉ mục cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD PRIMARY KEY (`ID_ThanhToan`),
  ADD KEY `ID_DatLich` (`ID_DatLich`);

--
-- Chỉ mục cho bảng `thietbi`
--
ALTER TABLE `thietbi`
  ADD PRIMARY KEY (`ID_TB`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID_User`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `FacebookID` (`FacebookID`),
  ADD UNIQUE KEY `GoogleID` (`GoogleID`),
  ADD KEY `ID_Role` (`ID_Role`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chitietdatsukien`
--
ALTER TABLE `chitietdatsukien`
  MODIFY `ID_CT` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `combo`
--
ALTER TABLE `combo`
  MODIFY `ID_Combo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `ID_DanhGia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `datlichsukien`
--
ALTER TABLE `datlichsukien`
  MODIFY `ID_DatLich` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `diadiem`
--
ALTER TABLE `diadiem`
  MODIFY `ID_DD` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `kehoachthuchien`
--
ALTER TABLE `kehoachthuchien`
  MODIFY `id_kehoach` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khachhanginfo`
--
ALTER TABLE `khachhanginfo`
  MODIFY `ID_KhachHang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `lichlamviec`
--
ALTER TABLE `lichlamviec`
  MODIFY `ID_LLV` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `loaisukien`
--
ALTER TABLE `loaisukien`
  MODIFY `ID_LoaiSK` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `nhanvieninfo`
--
ALTER TABLE `nhanvieninfo`
  MODIFY `ID_NhanVien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `phanquyen`
--
ALTER TABLE `phanquyen`
  MODIFY `ID_Role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `sukien`
--
ALTER TABLE `sukien`
  MODIFY `ID_SuKien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  MODIFY `ID_ThanhToan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thietbi`
--
ALTER TABLE `thietbi`
  MODIFY `ID_TB` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `ID_User` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietdatsukien`
--
ALTER TABLE `chitietdatsukien`
  ADD CONSTRAINT `fk_ct_combo` FOREIGN KEY (`ID_Combo`) REFERENCES `combo` (`ID_Combo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ct_dl` FOREIGN KEY (`ID_DatLich`) REFERENCES `datlichsukien` (`ID_DatLich`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ct_tb` FOREIGN KEY (`ID_TB`) REFERENCES `thietbi` (`ID_TB`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `combochitiet`
--
ALTER TABLE `combochitiet`
  ADD CONSTRAINT `combochitiet_ibfk_1` FOREIGN KEY (`ID_Combo`) REFERENCES `combo` (`ID_Combo`),
  ADD CONSTRAINT `combochitiet_ibfk_2` FOREIGN KEY (`ID_TB`) REFERENCES `thietbi` (`ID_TB`);

--
-- Các ràng buộc cho bảng `combo_loaisk`
--
ALTER TABLE `combo_loaisk`
  ADD CONSTRAINT `combo_loaisk_ibfk_1` FOREIGN KEY (`ID_Combo`) REFERENCES `combo` (`ID_Combo`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_loaisk_ibfk_2` FOREIGN KEY (`ID_LoaiSK`) REFERENCES `loaisukien` (`ID_LoaiSK`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `combo_thietbi`
--
ALTER TABLE `combo_thietbi`
  ADD CONSTRAINT `combo_thietbi_ibfk_1` FOREIGN KEY (`ID_Combo`) REFERENCES `combo` (`ID_Combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `combo_thietbi_ibfk_2` FOREIGN KEY (`ID_TB`) REFERENCES `thietbi` (`ID_TB`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`ID_User`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`ID_User`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conversations_lastmessage` FOREIGN KEY (`LastMessage_ID`) REFERENCES `messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `datlichsukien`
--
ALTER TABLE `datlichsukien`
  ADD CONSTRAINT `fk_datlich_diadiem` FOREIGN KEY (`ID_DD`) REFERENCES `diadiem` (`ID_DD`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_datlich_khachhang` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhanginfo` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_datlich_loaisk` FOREIGN KEY (`ID_LoaiSK`) REFERENCES `loaisukien` (`ID_LoaiSK`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `kehoachthuchien`
--
ALTER TABLE `kehoachthuchien`
  ADD CONSTRAINT `kehoachthuchien_ibfk_1` FOREIGN KEY (`id_sukien`) REFERENCES `sukien` (`ID_SuKien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kehoachthuchien_ibfk_2` FOREIGN KEY (`id_nhanvien`) REFERENCES `nhanvieninfo` (`ID_NhanVien`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `khachhanginfo`
--
ALTER TABLE `khachhanginfo`
  ADD CONSTRAINT `khachhanginfo_ibfk_1` FOREIGN KEY (`ID_User`) REFERENCES `users` (`ID_User`);

--
-- Các ràng buộc cho bảng `lichlamviec`
--
ALTER TABLE `lichlamviec`
  ADD CONSTRAINT `fk_llv_datlich` FOREIGN KEY (`ID_DatLich`) REFERENCES `datlichsukien` (`ID_DatLich`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_llv_nhanvien` FOREIGN KEY (`ID_NhanVien`) REFERENCES `nhanvieninfo` (`ID_NhanVien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lichlamviec_ibfk_1` FOREIGN KEY (`id_kehoach`) REFERENCES `kehoachthuchien` (`id_kehoach`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`ID_User`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nhanvieninfo`
--
ALTER TABLE `nhanvieninfo`
  ADD CONSTRAINT `nhanvieninfo_ibfk_1` FOREIGN KEY (`ID_User`) REFERENCES `users` (`ID_User`);

--
-- Các ràng buộc cho bảng `sukien`
--
ALTER TABLE `sukien`
  ADD CONSTRAINT `fk_sukien_datlich` FOREIGN KEY (`ID_DatLich`) REFERENCES `datlichsukien` (`ID_DatLich`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sukien_combo`
--
ALTER TABLE `sukien_combo`
  ADD CONSTRAINT `sukien_combo_ibfk_1` FOREIGN KEY (`ID_SuKien`) REFERENCES `sukien` (`ID_SuKien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sukien_combo_ibfk_2` FOREIGN KEY (`ID_Combo`) REFERENCES `combo` (`ID_Combo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sukien_thietbi`
--
ALTER TABLE `sukien_thietbi`
  ADD CONSTRAINT `sukien_thietbi_ibfk_1` FOREIGN KEY (`ID_SuKien`) REFERENCES `sukien` (`ID_SuKien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sukien_thietbi_ibfk_2` FOREIGN KEY (`ID_TB`) REFERENCES `thietbi` (`ID_TB`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD CONSTRAINT `thanhtoan_ibfk_1` FOREIGN KEY (`ID_DatLich`) REFERENCES `datlichsukien` (`ID_DatLich`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`ID_Role`) REFERENCES `phanquyen` (`ID_Role`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
