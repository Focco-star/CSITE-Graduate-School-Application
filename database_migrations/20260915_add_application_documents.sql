CREATE TABLE IF NOT EXISTS `application_documents` (
  `document_id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) DEFAULT NULL,
  `student_id` INT(11) NOT NULL,
  `stage` VARCHAR(100) NOT NULL,
  `document_type` VARCHAR(150) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('submitted','verified','incomplete') NOT NULL DEFAULT 'submitted',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `application_documents_application_id` (`application_id`),
  KEY `application_documents_student_id` (`student_id`),
  CONSTRAINT `application_documents_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `application_documents_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
