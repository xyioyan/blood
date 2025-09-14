INSERT INTO students (student_id, full_name, email, phone, blood_group, department, year_of_study, last_donation_date, is_available, password)
VALUES 
('S1001', 'Alice Johnson', 'alice.johnson@example.com', '9876543210', 'A+', 'Computer Science', 2, '2024-11-12', TRUE, 'hashed_password1'),
('S1002', 'Bob Smith', 'bob.smith@example.com', '9876543211', 'B+', 'Mechanical Engineering', 3, '2024-10-05', TRUE, 'hashed_password2'),
('S1003', 'Clara Lee', 'clara.lee@example.com', '9876543212', 'O-', 'Electrical Engineering', 1, NULL, TRUE, 'hashed_password3'),
('S1004', 'David Kim', 'david.kim@example.com', '9876543213', 'AB+', 'Civil Engineering', 4, '2025-01-20', FALSE, 'hashed_password4');
INSERT INTO admins (username, password, full_name)
VALUES
('admin1', 'hashed_admin_pass1', 'John Admin'),
('admin2', 'hashed_admin_pass2', 'Jane Admin');
INSERT INTO donations (donor_id, donation_date, recipient_details, event_name)
VALUES
(1, '2024-11-12', 'City Hospital – Emergency Case', 'Blood Drive 2024'),
(2, '2024-10-05', 'Local Clinic – Surgery Patient', 'Engineering Blood Camp'),
(4, '2025-01-20', 'St. Mary’s Hospital – Accident Victim', 'Annual College Camp');
INSERT INTO password_resets (email, token, created_at, expires_at)
VALUES
('alice.johnson@example.com', 'token123', NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR)),
('bob.smith@example.com', 'token456', NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR));
