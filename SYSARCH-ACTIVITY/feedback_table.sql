-- Feedback Table
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `rating` int(1) NOT NULL,
  `comments` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
