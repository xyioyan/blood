CREATE DATABASE blood_group_management;
USE blood_group_management;

-- -- Students table
-- CREATE TABLE students (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     student_id VARCHAR(20) UNIQUE NOT NULL,
--     full_name VARCHAR(100) NOT NULL,
--     email VARCHAR(100) UNIQUE NOT NULL,
--     phone VARCHAR(15) NOT NULL,
--     blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
--     department VARCHAR(50) NOT NULL,
--     year_of_study INT NOT NULL,
--     last_donation_date DATE,
--     is_available BOOLEAN DEFAULT TRUE,
--     password VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- -- Admins table
-- CREATE TABLE admins (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     username VARCHAR(50) UNIQUE NOT NULL,
--     password VARCHAR(255) NOT NULL,
--     full_name VARCHAR(100) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- -- Donation history table
-- CREATE TABLE donations (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     donor_id INT NOT NULL,
--     donation_date DATE NOT NULL,
--     recipient_details TEXT,
--     event_name VARCHAR(100),
--     FOREIGN KEY (donor_id) REFERENCES students(id)
-- );

-- CREATE TABLE password_resets (
--     email VARCHAR(255) NOT NULL,
--     token VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP NULL,
--     expires_at TIMESTAMP NULL
-- );
