-- Xóa database cũ nếu tồn tại
DROP DATABASE IF EXISTS key_manager;

-- Tạo database mới
CREATE DATABASE key_manager;
USE key_manager;

-- Xóa bảng cũ nếu cần
DROP TABLE IF EXISTS vip_keys;
DROP TABLE IF EXISTS key_logs;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS admin_logs;

-- Tạo lại các bảng mới
CREATE TABLE vip_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_type ENUM('VIP', 'FREE', 'ADMIN') NOT NULL DEFAULT 'VIP',
    create_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NOT NULL,
    device_id VARCHAR(255) NULL,
    allowed_ip VARCHAR(45) NULL,
    ip_limit INT DEFAULT 0,
    status ENUM('active', 'expired', 'banned') DEFAULT 'active',
    last_used DATETIME NULL,
    usage_count INT DEFAULT 0,
    notes TEXT NULL,
    renewed_from_id INT NULL,
    renewed_date DATETIME NULL,
    INDEX (api_key),
    INDEX (key_type),
    INDEX (status),
    INDEX (device_id),
    INDEX (renewed_from_id),
    FOREIGN KEY (renewed_from_id) REFERENCES vip_keys(id)
);

CREATE TABLE key_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (key_id) REFERENCES vip_keys(id),
    INDEX (device_id),
    INDEX (created_at)
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip_address),
    INDEX (attempt_time)
);

CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (admin_username),
    INDEX (created_at)
);

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'blocked') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin account
INSERT INTO admin_users (username, password) 
VALUES ('admin', PASSWORD('admin123'));

-- Thêm key ADMIN mẫu
INSERT INTO vip_keys (api_key, key_type, end_date) 
VALUES ('ADMIN_KEY', 'ADMIN', '2099-12-31 23:59:59');