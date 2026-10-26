-- Notes that this change is applied once to an existing csite_grad_school database.
-- Apply once to an existing csite_grad_school database.
-- Notes that the status values must match the choices on the coordinator status form.
-- Keeps the MySQL applications.status column aligned with the coordinator status form.

-- Changes the applications table that stores each student submission.
ALTER TABLE `applications`
  -- Redefines the status column with the full list of workflow states.
  MODIFY `status` ENUM(
    -- Submitted is the first state, set when the student sends the application.
    'submitted',
    -- Under review means a coordinator is currently checking the application.
    'under_review',
    -- For payment means the student still has to pay the fee.
    'for_payment',
    -- Payment recorded means the fee was received and logged.
    'payment_recorded',
    -- Ready for presentation means the student may now be scheduled.
    'ready_for_presentation',
    -- Scheduled means a presentation date and panel were already assigned.
    'scheduled',
    -- Approved means the panel accepted the application.
    'approved',
    -- Requires revision means the student must fix and resubmit requirements.
    'requires_revision',
    -- Completed means the whole application workflow is finished.
    'completed'
  -- Makes submitted the default status and forbids empty values.
  ) NOT NULL DEFAULT 'submitted';
