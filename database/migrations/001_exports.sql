-- Phase 6: staged exports (full packages and update packages).
ALTER TABLE wb_exports
  ADD COLUMN type ENUM('full','update') NOT NULL DEFAULT 'full' AFTER website_id,
  ADD COLUMN status ENUM('running','done','failed') NOT NULL DEFAULT 'running' AFTER type,
  ADD COLUMN step VARCHAR(30) NULL AFTER status,
  ADD COLUMN progress TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER step,
  ADD COLUMN options TEXT NULL AFTER progress,
  ADD COLUMN state TEXT NULL AFTER options,
  ADD COLUMN log TEXT NULL AFTER state,
  ADD COLUMN error_message VARCHAR(500) NULL AFTER log,
  ADD COLUMN file_count INT UNSIGNED NULL AFTER size_bytes,
  ADD COLUMN runtime_version VARCHAR(20) NULL AFTER file_count,
  ADD COLUMN template_version VARCHAR(20) NULL AFTER runtime_version,
  ADD COLUMN finished_at DATETIME NULL AFTER created_at;
