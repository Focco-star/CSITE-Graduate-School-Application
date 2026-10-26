-- Creates the application_documents table that stores uploaded requirement files.
CREATE TABLE IF NOT EXISTS `application_documents` (
  -- document_id is the auto-numbered primary key for each uploaded file.
  `document_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- application_id links the file to an application, or stays null when unknown.
  `application_id` INT(11) DEFAULT NULL,
  -- student_id links the file to the student who uploaded it.
  `student_id` INT(11) NOT NULL,
  -- stage names the workflow stage the file belongs to.
  `stage` VARCHAR(100) NOT NULL,
  -- document_type names the requirement, such as a transcript or receipt.
  `document_type` VARCHAR(150) NOT NULL,
  -- original_name keeps the file name the student saw on their computer.
  `original_name` VARCHAR(255) NOT NULL,
  -- stored_name keeps the renamed file name used inside the storage folder.
  `stored_name` VARCHAR(255) NOT NULL,
  -- mime_type records the file type reported during upload.
  `mime_type` VARCHAR(100) DEFAULT NULL,
  -- file_size stores the file size in bytes and defaults to zero.
  `file_size` INT(11) NOT NULL DEFAULT 0,
  -- status tracks whether the file is submitted, verified, or incomplete.
  `status` ENUM('submitted','verified','incomplete') NOT NULL DEFAULT 'submitted',
  -- uploaded_at records the date and time the file was uploaded.
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes document_id the primary key so every row is unique.
  PRIMARY KEY (`document_id`),
  -- Adds an index on application_id to speed up lookups by application.
  KEY `application_documents_application_id` (`application_id`),
  -- Adds an index on student_id to speed up lookups by student.
  KEY `application_documents_student_id` (`student_id`),
  -- Ties each document to an existing student and deletes files with that student.
  CONSTRAINT `application_documents_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  -- Ties the document to an application and clears the link if the application is deleted.
  CONSTRAINT `application_documents_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL
-- Uses InnoDB and utf8mb4 so foreign keys and special characters work.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
