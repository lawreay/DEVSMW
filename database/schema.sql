CREATE DATABASE IF NOT EXISTS devsmw_profiles
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE devsmw_profiles;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(160) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  github_username VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(180) NULL,
  title VARCHAR(180) NULL,
  location VARCHAR(180) NULL,
  work VARCHAR(255) NULL,
  phone VARCHAR(80) NULL,
  email VARCHAR(180) NULL,
  website VARCHAR(255) NULL,
  github_url VARCHAR(255) NULL,
  linkedin_url VARCHAR(255) NULL,
  bio TEXT NULL,
  strengths TEXT NULL,
  markdown LONGTEXT NULL,
  rank_private INT NULL,
  consent_status ENUM('public_data', 'claimed', 'opted_out', 'needs_review') NOT NULL DEFAULT 'public_data',
  visibility ENUM('published', 'draft', 'hidden') NOT NULL DEFAULT 'published',
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY profiles_search (github_username, name, title, location, work, bio, strengths)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED NOT NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  url VARCHAR(255) NULL,
  language VARCHAR(80) NULL,
  stars INT UNSIGNED NOT NULL DEFAULT 0,
  source VARCHAR(80) NOT NULL DEFAULT 'manual',
  is_private TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
  INDEX projects_profile_source (profile_id, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profile_sources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED NOT NULL,
  platform VARCHAR(80) NOT NULL,
  url VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  checked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sources_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id INT UNSIGNED NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  INDEX audit_entity (entity_type, entity_id),
  INDEX audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_users (username, password_hash, display_name, is_active)
VALUES ('admin', '$2y$10$sfRmC/h6DYPpG5R1YRZGseORL9RgAgi0kP0DrxJUscMA.8Q8LyR5q', 'Site Admin', 1)
ON DUPLICATE KEY UPDATE username = username;
