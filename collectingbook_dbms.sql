-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 30, 2024 at 04:52 AM
-- Server version: 10.6.18-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elnvento_RTSport`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_Name` varchar(100) DEFAULT NULL,
  `Password` varchar(60) DEFAULT NULL,
  `Date_of_last_use` datetime DEFAULT NULL,
  `Admin_Status` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`Admin_ID`, `Admin_Name`, `Password`, `Date_of_last_use`, `Admin_Status`) VALUES
(1, 'Admin', '$2y$10$3K8f1QOZwvB3jzZcNIUG0eMMqvFWC8rZ.AnFtHNUfTGI6S8VMEG8a', '2024-10-20 14:27:30', 1);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `Book_ID` int(11) NOT NULL,
  `Category_ID` int(11) DEFAULT NULL,
  `Publisher_ID` int(11) DEFAULT NULL,
  `Book_Name` varchar(100) DEFAULT NULL,
  `Author` varchar(100) DEFAULT NULL,
  `Printed` int(11) DEFAULT NULL,
  `ISBN` varchar(13) DEFAULT NULL,
  `Number_of_Page` int(11) DEFAULT NULL,
  `Book_Picture` varchar(500) DEFAULT NULL,
  `Score` int(11) DEFAULT NULL,
  `Number_of_Reader` int(11) NOT NULL,
  `Avg_Score` float NOT NULL,
  `Recommend_Status` tinyint(1) DEFAULT NULL,
  `Datetime_Added` datetime DEFAULT current_timestamp(),
  `Admin_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `bookshelves` (
  `Bookshelf_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Bookshelf_Name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookshelves`
--

-- --------------------------------------------------------

--
-- Table structure for table `books_on_bookshelf`
--

CREATE TABLE `books_on_bookshelf` (
  `Book_on_Bookshelf_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Bookshelf_ID` int(11) NOT NULL,
  `Page_read_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books_on_bookshelf`
--


-- --------------------------------------------------------

--
-- Table structure for table `books_scores`
--

CREATE TABLE `books_scores` (
  `Book_Score_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Book_ID` int(11) DEFAULT NULL,
  `Date_Review` datetime DEFAULT NULL,
  `Score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books_scores`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `Category_id` int(11) NOT NULL,
  `Category_Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--


-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `Goal_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Day` int(11) DEFAULT NULL,
  `Goal_Time` int(11) DEFAULT NULL,
  `Time_of_Notification` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goals`
--

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `Profile_ID` int(11) NOT NULL,
  `Profile_Name` varchar(100) DEFAULT NULL,
  `Profile_Picture` varchar(500) DEFAULT NULL,
  `Points` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--


-- --------------------------------------------------------

--
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `Publisher_ID` int(11) NOT NULL,
  `Publisher_Name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers`
--


-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `Quote_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Book_ID` int(11) DEFAULT NULL,
  `Quote_Detail` varchar(225) DEFAULT NULL,
  `Page_of_Quote` int(11) DEFAULT NULL,
  `Datetime_Add_Quote` datetime DEFAULT NULL,
  `Quote_Status` tinyint(4) DEFAULT NULL,
  `Number_of_Like` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotes`
--

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `Report_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Report_Datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `Report_Topic` varchar(100) DEFAULT NULL,
  `Report_Detail` varchar(255) DEFAULT NULL,
  `Report_Picture` varchar(500) DEFAULT NULL,
  `Report_Status` tinyint(4) DEFAULT NULL,
  `Action_Datetime` datetime DEFAULT NULL,
  `Action_Detail` varchar(255) DEFAULT NULL,
  `Admin_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `Request_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Bookshelf_ID` int(11) DEFAULT NULL,
  `Request_Datetime` datetime DEFAULT current_timestamp(),
  `Request_Book_Name` varchar(255) DEFAULT NULL,
  `Request_Author` varchar(500) DEFAULT NULL,
  `Request_Printed` tinyint(4) DEFAULT NULL,
  `Request_ISBN` varchar(13) DEFAULT NULL,
  `Request_Picture` varchar(500) DEFAULT NULL,
  `Request_Status` tinyint(4) DEFAULT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Action_Datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--


-- --------------------------------------------------------

--
-- Table structure for table `statistics`
--

CREATE TABLE `statistics` (
  `Statistics_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Book_ID` int(11) DEFAULT NULL,
  `Datetime_Start` datetime DEFAULT NULL,
  `Datetime_End` datetime DEFAULT NULL,
  `Reading_Points` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `statistics`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Profile_ID` int(11) DEFAULT NULL,
  `User_Name` varchar(100) DEFAULT NULL,
  `Password` varchar(64) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Year_of_Birth` smallint(6) DEFAULT NULL,
  `User_Status` tinyint(4) DEFAULT NULL,
  `Datetime_of_last_use` datetime DEFAULT NULL,
  `OTP` varchar(6) DEFAULT NULL,
  `Datetime_Register` datetime DEFAULT NULL,
  `User_Status_Confirm` tinyint(4) DEFAULT NULL,
  `Number_of_Books` int(11) DEFAULT NULL,
  `Total_Points` int(11) NOT NULL,
  `Last_Point_Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--



--
-- Table structure for table `user_likes_quotes`
--

CREATE TABLE `user_likes_quotes` (
  `Like_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Quote_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_likes_quotes`
--



-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `User_Profile_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Profile_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--
--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Admin_Name` (`Admin_Name`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`Book_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `Publisher_ID` (`Publisher_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `bookshelves`
--
ALTER TABLE `bookshelves`
  ADD PRIMARY KEY (`Bookshelf_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `books_on_bookshelf`
--
ALTER TABLE `books_on_bookshelf`
  ADD PRIMARY KEY (`Book_on_Bookshelf_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`),
  ADD KEY `Bookshelf_ID` (`Bookshelf_ID`);

--
-- Indexes for table `books_scores`
--
ALTER TABLE `books_scores`
  ADD PRIMARY KEY (`Book_Score_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`Category_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`Goal_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`Profile_ID`);

--
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`Publisher_ID`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`Quote_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`Request_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Bookshelf_ID` (`Bookshelf_ID`),
  ADD KEY `fk_admin_id` (`Admin_ID`);

--
-- Indexes for table `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`Statistics_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `User_Name` (`User_Name`),
  ADD KEY `fk_profile` (`Profile_ID`);

--
-- Indexes for table `user_likes_quotes`
--
ALTER TABLE `user_likes_quotes`
  ADD PRIMARY KEY (`Like_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Quote_ID` (`Quote_ID`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`User_Profile_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Profile_ID` (`Profile_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `Book_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `bookshelves`
--
ALTER TABLE `bookshelves`
  MODIFY `Bookshelf_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `books_on_bookshelf`
--
ALTER TABLE `books_on_bookshelf`
  MODIFY `Book_on_Bookshelf_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `books_scores`
--
ALTER TABLE `books_scores`
  MODIFY `Book_Score_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `Category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `Goal_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `Profile_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `Publisher_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `Quote_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `Report_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `Request_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `statistics`
--
ALTER TABLE `statistics`
  MODIFY `Statistics_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `user_likes_quotes`
--
ALTER TABLE `user_likes_quotes`
  MODIFY `Like_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `User_Profile_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`Category_ID`) REFERENCES `categories` (`Category_id`),
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`Publisher_ID`) REFERENCES `publishers` (`Publisher_ID`),
  ADD CONSTRAINT `books_ibfk_3` FOREIGN KEY (`Admin_ID`) REFERENCES `admins` (`Admin_ID`);

--
-- Constraints for table `bookshelves`
--
ALTER TABLE `bookshelves`
  ADD CONSTRAINT `bookshelves_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `books_on_bookshelf`
--
ALTER TABLE `books_on_bookshelf`
  ADD CONSTRAINT `books_on_bookshelf_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `books_on_bookshelf_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`),
  ADD CONSTRAINT `books_on_bookshelf_ibfk_3` FOREIGN KEY (`Bookshelf_ID`) REFERENCES `bookshelves` (`Bookshelf_ID`);

--
-- Constraints for table `books_scores`
--
ALTER TABLE `books_scores`
  ADD CONSTRAINT `books_scores_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `books_scores_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`);

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `quotes_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_admin_id` FOREIGN KEY (`Admin_ID`) REFERENCES `admins` (`Admin_ID`),
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`Bookshelf_ID`) REFERENCES `bookshelves` (`Bookshelf_ID`);

--
-- Constraints for table `statistics`
--
ALTER TABLE `statistics`
  ADD CONSTRAINT `statistics_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `statistics_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_profile` FOREIGN KEY (`Profile_ID`) REFERENCES `profiles` (`Profile_ID`);

--
-- Constraints for table `user_likes_quotes`
--
ALTER TABLE `user_likes_quotes`
  ADD CONSTRAINT `user_likes_quotes_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_likes_quotes_ibfk_2` FOREIGN KEY (`Quote_ID`) REFERENCES `quotes` (`Quote_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `user_profiles_ibfk_2` FOREIGN KEY (`Profile_ID`) REFERENCES `profiles` (`Profile_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
