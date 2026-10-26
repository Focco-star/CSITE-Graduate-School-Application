-- Explains one payment record can attach to each application stage.
-- One payment record may be attached to each application stage submission.
-- Creates the payments table if it does not exist.
CREATE TABLE IF NOT EXISTS `payments` (
  -- payment_id is the auto-numbered primary key.
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- application_id links the payment to an application.
  `application_id` INT(11) NOT NULL,
  -- recorded_by_user_id identifies who recorded the payment.
  `recorded_by_user_id` INT(11) NOT NULL,
  -- receipt_number stores the official receipt number.
  `receipt_number` VARCHAR(100) NOT NULL,
  -- payment_date stores the date the payment was made.
  `payment_date` DATE NOT NULL,
  -- amount stores how much was paid with two decimals.
  `amount` DECIMAL(10,2) NOT NULL,
  -- proof_document_id optionally links an uploaded proof file.
  `proof_document_id` INT(11) DEFAULT NULL,
  -- notes holds extra free-text remarks.
  `notes` TEXT DEFAULT NULL,
  -- created_at records when the payment row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- updated_at refreshes automatically when the row changes.
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Makes payment_id the primary key.
  PRIMARY KEY (`payment_id`),
  -- Allows only one payment per application.
  UNIQUE KEY `uq_payments_application` (`application_id`),
  -- Ensures the same receipt number cannot repeat.
  UNIQUE KEY `uq_payments_receipt_number` (`receipt_number`),
  -- Adds an index on the user who recorded payments.
  KEY `idx_payments_recorder` (`recorded_by_user_id`),
  -- Adds an index on the linked proof document.
  KEY `idx_payments_proof_document` (`proof_document_id`),
  -- Ties the payment to its application and deletes it with that application.
  CONSTRAINT `fk_payments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Ties the payment to the recorder and blocks deleting that account.
  CONSTRAINT `fk_payments_recorder` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  -- Ties the proof file and clears the link if that file is deleted.
  CONSTRAINT `fk_payments_proof_document` FOREIGN KEY (`proof_document_id`) REFERENCES `application_documents` (`document_id`) ON DELETE SET NULL ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;