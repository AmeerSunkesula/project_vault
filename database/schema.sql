-- Project Vault Database Schema
-- Dr. YC James Yen Government Polytechnic, Kuppam

CREATE DATABASE IF NOT EXISTS project_vault;
USE project_vault;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    roll_number VARCHAR(12) NULL, -- Only for students
    branch ENUM('DCME', 'DEEE', 'DME', 'DECE') NULL, -- Only for students
    role ENUM('student', 'staff', 'admin') DEFAULT 'student',
    status ENUM('active', 'pending', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    short_description TEXT NOT NULL,
    long_description LONGTEXT NOT NULL,
    branch ENUM('DCME', 'DEEE', 'DME', 'DECE') NOT NULL,
    project_type VARCHAR(100) NOT NULL,
    github_link VARCHAR(500) NULL,
    created_by INT NOT NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    stars INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Project collaborators table
CREATE TABLE project_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_collaboration (project_id, user_id)
);

-- Project votes table
CREATE TABLE project_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (project_id, user_id)
);

-- Project stars table
CREATE TABLE project_stars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_star (project_id, user_id)
);

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL, -- For replies
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('collaboration_request', 'collaboration_response', 'project_approval', 'password_reset') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT NULL, -- ID of related project, user, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password reset requests table
CREATE TABLE password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
-- Password: admin123 (hashed with PHP password_hash)
INSERT INTO users (username, email, password, full_name, role, status) 
VALUES ('admin', 'admin@polytechnic.edu', '$2y$10$X7OANJOdu/TxjgasIsC94eAm3QnnAcLxI4msbAIZB8PO/NBvRLBCK', 'System Administrator', 'admin', 'active');

-- Project types for each branch
-- These will be used in the frontend for dynamic selection
-- DCME: Web Development, Mobile Apps, Desktop Applications, Database Systems, Network Projects
-- DEEE: IoT Projects, Power Systems, Control Systems, Renewable Energy, Electronics
-- DME: CAD Projects, Manufacturing Systems, Robotics, Machine Design, Automation
-- DECE: Embedded Systems, Communication Systems, Signal Processing, VLSI, Digital Systems
