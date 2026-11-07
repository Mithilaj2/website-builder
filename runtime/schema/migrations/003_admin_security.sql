-- Password reset tokens for website admins (stored as SHA-256 hashes, valid for 1 hour).
ALTER TABLE `{{prefix}}admin_users`
  ADD COLUMN reset_token_hash CHAR(64) NULL AFTER password_hash,
  ADD COLUMN reset_expires_at DATETIME NULL AFTER reset_token_hash,
  ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login_at;
