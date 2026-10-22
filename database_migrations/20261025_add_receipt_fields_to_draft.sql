-- Migration: receipt details in the application draft (2026-10-25)
--
-- The student now enters the receipt number, the amount and the payment
-- date together with the official receipt. The draft keeps those three
-- values as well, so nothing is lost when the student leaves the page
-- before sending the receipt.

ALTER TABLE `application_drafts`
  ADD COLUMN `receipt_number` VARCHAR(100) DEFAULT NULL AFTER `doc_type`,
  ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `receipt_number`,
  ADD COLUMN `payment_date` DATE DEFAULT NULL AFTER `payment_amount`;
