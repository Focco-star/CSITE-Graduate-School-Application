-- Apply once to an existing csite_grad_school database.
-- Keeps the MySQL applications.status column aligned with the coordinator status form.

ALTER TABLE `applications`
  MODIFY `status` ENUM(
    'submitted',
    'under_review',
    'for_payment',
    'payment_recorded',
    'ready_for_presentation',
    'scheduled',
    'approved',
    'requires_revision',
    'completed'
  ) NOT NULL DEFAULT 'submitted';
