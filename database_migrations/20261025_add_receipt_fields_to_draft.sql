-- Names the migration and date for receipt details in the draft.
-- Migration: receipt details in the application draft (2026-10-25)
-- Divider line separating the title from the explanation.
--
-- Says the student enters receipt number, amount, and payment date.
-- The student now enters the receipt number, the amount and the payment
-- These are entered together with the official receipt.
-- date together with the official receipt. The draft keeps those three
-- The draft keeps those three values as well.
-- values as well, so nothing is lost when the student leaves the page
-- So nothing is lost when the student leaves before sending.
-- before sending the receipt.

-- Changes the application_drafts table.
ALTER TABLE `application_drafts`
  -- Adds an optional receipt number after doc_type.
  ADD COLUMN `receipt_number` VARCHAR(100) DEFAULT NULL AFTER `doc_type`,
  -- Adds an optional payment amount after the receipt number.
  ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `receipt_number`,
  -- Adds an optional payment date after the amount.
  ADD COLUMN `payment_date` DATE DEFAULT NULL AFTER `payment_amount`;
