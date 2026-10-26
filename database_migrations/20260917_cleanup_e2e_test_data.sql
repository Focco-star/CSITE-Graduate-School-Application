-- Says this script removes test artifacts made during end-to-end testing.
-- Cleanup of verification/test artifacts created during end-to-end testing.
-- Notes only rows created by the test are removed.
-- Removes ONLY the rows created by the E2E test (test student + app + docs);
-- Promises that all real seed data stays untouched.
-- all real seed data is untouched.
-- Deletes the test uploaded files by their stored names.
DELETE FROM application_documents WHERE stored_name IN ('manual-test.pdf', 'via-function-test.pdf', '76b55845f3b8346e4b84004f24af600d.pdf');
-- Deletes test applications whose title mentions Test Uploader.
DELETE FROM applications WHERE paper_title LIKE '%Test Uploader%';
-- Deletes the student row of the test account by its email.
DELETE FROM students WHERE user_id IN (SELECT user_id FROM users WHERE email = 'test.uploader@adzu.edu.ph');
-- Deletes the test user account itself by its email.
DELETE FROM users WHERE email = 'test.uploader@adzu.edu.ph';
