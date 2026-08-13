-- Database migration: Add email configuration and password reset token tables
-- Created for DEVSMW SMTP email system

-- Email Configuration Table
-- Stores SMTP settings for sending emails
CREATE TABLE IF NOT EXISTS email_config (
    id INT PRIMARY KEY DEFAULT 1,
    smtp_host VARCHAR(255) DEFAULT 'smtp.gmail.com',
    smtp_port INT DEFAULT 587,
    smtp_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    from_address VARCHAR(255),
    from_name VARCHAR(255) DEFAULT 'DEVSMW',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT check_one_row CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default row
INSERT IGNORE INTO email_config (id) VALUES (1);

-- Password Reset Tokens Table
-- Stores password reset tokens for admins
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_hash VARCHAR(64) PRIMARY KEY COMMENT 'SHA256 hash of the reset token',
    admin_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL COMMENT 'Token expires after 1 hour',
    used_at TIMESTAMP NULL COMMENT 'When the token was used to reset password',
    
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used_at (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
