-- Migration: Add authentication features (2FA, Remember me, Password reset, Rate limiting, Theme)

-- 2FA/TOTP columns
ALTER TABLE users 
ADD COLUMN totp_secret VARCHAR(32) NULL AFTER password,
ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret,
ADD COLUMN totp_recovery_codes TEXT NULL AFTER totp_enabled;

-- Remember me tokens
ALTER TABLE users 
ADD COLUMN remember_token VARCHAR(64) NULL AFTER totp_recovery_codes,
ADD COLUMN remember_token_expires TIMESTAMP NULL AFTER remember_token;

-- Password reset
ALTER TABLE users 
ADD COLUMN reset_token VARCHAR(64) NULL AFTER remember_token_expires,
ADD COLUMN reset_token_expires TIMESTAMP NULL AFTER reset_token;

-- Rate limiting / brute force protection
ALTER TABLE users 
ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0 AFTER reset_token_expires,
ADD COLUMN locked_until TIMESTAMP NULL AFTER failed_login_attempts;

-- Theme preference
ALTER TABLE users 
ADD COLUMN theme_preference ENUM('dark', 'light', 'system') NOT NULL DEFAULT 'dark' AFTER locked_until;

-- Indexes for performance
CREATE INDEX idx_users_remember_token ON users(remember_token);
CREATE INDEX idx_users_reset_token ON users(reset_token);
CREATE INDEX idx_users_email_status ON users(email, status);

-- Login attempts table for more granular rate limiting (by IP + email)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(150) NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_ip_time (ip_address, attempted_at),
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OAuth/Social login connections
CREATE TABLE IF NOT EXISTS user_oauth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider ENUM('google', 'microsoft', 'github') NOT NULL,
    provider_id VARCHAR(100) NOT NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_provider (provider, provider_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_provider (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;