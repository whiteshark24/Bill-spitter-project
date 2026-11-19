CREATE DATABASE bill_splitter_db;
USE bill_splitter_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bills table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_name VARCHAR(100) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    bill_type VARCHAR(50) NOT NULL,
    split_method VARCHAR(20) NOT NULL DEFAULT 'equal',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Bill shares table
CREATE TABLE bill_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    user_id INT NOT NULL,
    amount_owed DECIMAL(10,2) NOT NULL,
    is_paid TINYINT DEFAULT 0,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);