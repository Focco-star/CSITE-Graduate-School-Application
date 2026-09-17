-- Cleanup of verification/test artifacts created during end-to-end testing.
-- Removes ONLY the rows created by the E2E test (test student + app + docs);
-- all real seed data is untouched.
DELETE FROM application_documents WHERE stored_name IN ('manual-test.pdf', 'via-function-test.pdf', '76b55845f3b8346e4b84004f24af600d.pdf');
DELETE FROM applications WHERE paper_title LIKE '%Test Uploader%';
DELETE FROM students WHERE user_id IN (SELECT user_id FROM users WHERE email = 'test.uploader@adzu.edu.ph');
DELETE FROM users WHERE email = 'test.uploader@adzu.edu.ph';
