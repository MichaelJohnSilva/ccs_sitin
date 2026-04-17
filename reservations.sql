CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `remaining_sessions` int(11) NOT NULL DEFAULT 30,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
