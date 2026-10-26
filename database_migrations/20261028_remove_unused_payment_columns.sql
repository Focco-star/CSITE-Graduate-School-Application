-- Migration: remove unused payment columns (2026-10-28)
-- The site stores uploaded receipt files in application_documents and does
-- not read or write either of these payments columns.
--
-- Before applying this migration, verify that no live data will be lost:
-- SELECT SUM(proof_document_id IS NOT NULL) AS linked_proof_count,
--        SUM(notes IS NOT NULL AND TRIM(notes) <> '') AS payment_notes_count
-- FROM payments;
--
-- The payments table itself remains in use by saveApplicationPayment().

-- Remove the foreign key before dropping the proof document column.
ALTER TABLE `payments`
  DROP FOREIGN KEY `fk_payments_proof_document`;

-- Remove the unused index and columns.
ALTER TABLE `payments`
  DROP INDEX `idx_payments_proof_document`,
  DROP COLUMN `proof_document_id`,
  DROP COLUMN `notes`;
