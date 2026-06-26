-- Migration: add per-user timezone (added after the initial release).
-- Safe for existing data — only adds a column with a default of 'UTC'.
-- Apply once to a database created before the timezone feature existed.
--
-- New/fresh databases already include this column via database/schema.sql,
-- so this migration is only for upgrading an existing one.

ALTER TABLE users
    ADD COLUMN timezone VARCHAR(64) NOT NULL DEFAULT 'UTC' AFTER currency;
