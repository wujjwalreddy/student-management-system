-- Create database
CREATE DATABASE IF NOT EXISTS student_management_system;
USE student_management_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
email VARCHAR(100) NOT NULL UNIQUE,
fullname VARCHAR(100) NOT NULL,
role ENUM('admin', 'faculty', 'student') NOT NULL,
status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
student_id VARCHAR(20) NOT NULL UNIQUE,
name VARCHAR(100) NOT NULL,
email VARCHAR(100) NOT NULL,
phone VARCHAR(20),
dob DATE,
gender ENUM('male', 'female', 'other'),
address TEXT,
enrollment_date DATE,
status ENUM('active', 'inactive', 'graduated', 'suspended') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Faculty table
CREATE TABLE IF NOT EXISTS faculty (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
faculty_id VARCHAR(20) NOT NULL UNIQUE,
name VARCHAR(100) NOT NULL,
email VARCHAR(100) NOT NULL,
phone VARCHAR(20),
department VARCHAR(50),
designation VARCHAR(50),
qualification VARCHAR(100),
joining_date DATE,
address TEXT,
status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
id INT AUTO_INCREMENT PRIMARY KEY,
course_code VARCHAR(20) NOT NULL UNIQUE,
course_name VARCHAR(100) NOT NULL,
department VARCHAR(50) NOT NULL,
credits INT NOT NULL,
description TEXT,
semester VARCHAR(20),
start_date DATE,
end_date DATE,
status ENUM('active', 'inactive', 'upcoming', 'completed') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Course Instructors table
CREATE TABLE IF NOT EXISTS course_instructors (
id INT AUTO_INCREMENT PRIMARY KEY,
course_id INT NOT NULL,
faculty_id INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
id INT AUTO_INCREMENT PRIMARY KEY,
student_id INT NOT NULL,
course_id INT NOT NULL,
enrollment_date DATE NOT NULL,
status ENUM('active', 'completed', 'dropped', 'pending') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
id INT AUTO_INCREMENT PRIMARY KEY,
student_id INT NOT NULL,
course_id INT NOT NULL,
date DATE NOT NULL,
status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
remarks TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Assignments table
CREATE TABLE IF NOT EXISTS assignments (
id INT AUTO_INCREMENT PRIMARY KEY,
course_id INT NOT NULL,
title VARCHAR(100) NOT NULL,
description TEXT,
due_date DATETIME NOT NULL,
total_marks INT NOT NULL,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
FOREIGN KEY (created_by) REFERENCES faculty(id) ON DELETE CASCADE
);

-- Assignment Submissions table
CREATE TABLE IF NOT EXISTS assignment_submissions (
id INT AUTO_INCREMENT PRIMARY KEY,
assignment_id INT NOT NULL,
student_id INT NOT NULL,
submission_text TEXT,
file_path VARCHAR(255),
submission_date DATETIME NOT NULL,
marks_obtained FLOAT,
feedback TEXT,
status ENUM('submitted', 'graded', 'late', 'resubmit') NOT NULL DEFAULT 'submitted',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Grades table
CREATE TABLE IF NOT EXISTS grades (
id INT AUTO_INCREMENT PRIMARY KEY,
student_id INT NOT NULL,
course_id INT NOT NULL,
assignment_id INT,
exam_type ENUM('quiz', 'midterm', 'final', 'assignment', 'project') NOT NULL,
marks_obtained FLOAT NOT NULL,
total_marks FLOAT NOT NULL,
grade_letter VARCHAR(2),
remarks TEXT,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE SET NULL,
FOREIGN KEY (created_by) REFERENCES faculty(id) ON DELETE CASCADE
);

-- Activity Logs table
CREATE TABLE IF NOT EXISTS activity_logs (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
activity TEXT NOT NULL,
timestamp DATETIME NOT NULL,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user with plain text password (password: admin123)
INSERT INTO users (username, password, email, fullname, role, status)
VALUES ('admin', 'admin123', 'admin@example.com', 'System Administrator', 'admin', 'active');

-- Insert sample faculty users with plain text passwords
INSERT INTO users (username, password, email, fullname, role, status)
VALUES 
('faculty1', 'faculty123', 'robert.w@example.com', 'Dr. Robert Williams', 'faculty', 'active'),
('faculty2', 'faculty123', 'sarah.j@example.com', 'Dr. Sarah Johnson', 'faculty', 'active');

-- Insert sample student users with plain text passwords
INSERT INTO users (username, password, email, fullname, role, status)
VALUES 
('student1', 'student123', 'john.doe@example.com', 'John Doe', 'student', 'active'),
('student2', 'student123', 'jane.smith@example.com', 'Jane Smith', 'student', 'active');

-- Insert faculty records
INSERT INTO faculty (user_id, faculty_id, name, email, phone, department, designation, qualification, joining_date, address, status)
VALUES 
(2, 'F001', 'Dr. Robert Williams', 'robert.w@example.com', '555-123-4567', 'Computer Science', 'Professor', 'Ph.D. in Computer Science', '2020-01-15', '123 Faculty Ave, Academic City', 'active'),
(3, 'F002', 'Dr. Sarah Johnson', 'sarah.j@example.com', '555-987-6543', 'Business', 'Associate Professor', 'Ph.D. in Business Administration', '2019-08-10', '456 Scholar St, Academic City', 'active');

-- Insert student records
INSERT INTO students (user_id, student_id, name, email, phone, dob, gender, address, enrollment_date, status)
VALUES 
(4, 'S1001', 'John Doe', 'john.doe@example.com', '555-111-2222', '2000-05-15', 'male', '789 Student Blvd, College Town', '2022-09-01', 'active'),
(5, 'S1002', 'Jane Smith', 'jane.smith@example.com', '555-333-4444', '2001-03-22', 'female', '101 Campus Dr, College Town', '2022-09-01', 'active');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, department, credits, description, semester, start_date, end_date, status)
VALUES 
('CS101', 'Introduction to Programming', 'Computer Science', 3, 'An introductory course to programming concepts and practices.', 'Fall 2023', '2023-09-01', '2023-12-15', 'active'),
('CS301', 'Database Management', 'Computer Science', 4, 'Advanced database concepts and SQL.', 'Fall 2023', '2023-09-01', '2023-12-15', 'active'),
('BUS201', 'Business Management', 'Business', 3, 'Introduction to business management principles.', 'Fall 2023', '2023-09-01', '2023-12-15', 'active');

-- Assign courses to faculty
INSERT INTO course_instructors (course_id, faculty_id)
VALUES 
(1, 1), -- CS101 taught by Dr. Robert Williams
(2, 1), -- CS301 taught by Dr. Robert Williams
(3, 2); -- BUS201 taught by Dr. Sarah Johnson

-- Enroll students in courses
INSERT INTO enrollments (student_id, course_id, enrollment_date, status)
VALUES 
(1, 1, '2023-08-15', 'active'), -- John Doe in CS101
(1, 2, '2023-08-15', 'active'), -- John Doe in CS301
(2, 1, '2023-08-14', 'active'), -- Jane Smith in CS101
(2, 3, '2023-08-14', 'active'); -- Jane Smith in BUS201

-- Add sample assignments
INSERT INTO assignments (course_id, title, description, due_date, total_marks, created_by)
VALUES 
(1, 'Programming Exercise 3', 'Complete the programming exercises in Chapter 3.', '2023-08-15 23:59:59', 100, 1),
(2, 'Database Design', 'Design a database schema for an e-commerce website.', '2023-08-18 23:59:59', 100, 1),
(3, 'Case Study Analysis', 'Analyze the provided business case study.', '2023-08-18 23:59:59', 100, 2);

-- Add sample attendance records
INSERT INTO attendance (student_id, course_id, date, status, remarks)
VALUES 
(1, 1, '2023-08-01', 'present', NULL),
(1, 1, '2023-08-03', 'present', NULL),
(1, 2, '2023-08-02', 'present', NULL),
(2, 1, '2023-08-01', 'present', NULL),
(2, 1, '2023-08-03', 'absent', 'Medical leave'),
(2, 3, '2023-08-02', 'present', NULL);

-- Add sample grades
INSERT INTO grades (student_id, course_id, assignment_id, exam_type, marks_obtained, total_marks, grade_letter, remarks, created_by)
VALUES 
(1, 1, 1, 'assignment', 85, 100, 'A', 'Good work!', 1),
(1, 2, 2, 'assignment', 78, 100, 'B', 'Needs improvement in normalization.', 1),
(2, 1, 1, 'assignment', 92, 100, 'A', 'Excellent work!', 1),
(2, 3, 3, 'assignment', 88, 100, 'A', 'Well-analyzed case study.', 2);

