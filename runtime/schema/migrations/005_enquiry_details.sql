-- Booking / appointment / admission forms: extra answers (department, preferred date, course …) as JSON {"Label": "value"}.
ALTER TABLE `{{prefix}}contact_submissions` ADD COLUMN extra_json TEXT NULL AFTER message;
