-- Add SMTP configuration table
CREATE TABLE IF NOT EXISTS email_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  smtp_host VARCHAR(255) NOT NULL,
  smtp_port INT NOT NULL DEFAULT 587,
  smtp_encryption VARCHAR(10) NOT NULL DEFAULT 'tls',
  smtp_username VARCHAR(255) NOT NULL,
  smtp_password VARCHAR(255) NOT NULL,
  from_address VARCHAR(255) NOT NULL,
  from_name VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  token_hash VARCHAR(64) PRIMARY KEY,
  admin_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  CONSTRAINT fk_reset_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  INDEX reset_admin_id (admin_user_id),
  INDEX reset_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default SMTP config (empty, needs setup)
INSERT INTO email_config (smtp_host, smtp_port, smtp_encryption, smtp_username, smtp_password, from_address, from_name)
VALUES ('smtp.gmail.com', 587, 'tls', '', '', '', 'DEVSMW')
ON DUPLICATE KEY UPDATE id = id;
