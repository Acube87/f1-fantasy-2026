-- Add avatar_style column to users table
-- Run this migration to enable avatar customization

ALTER TABLE users ADD COLUMN avatar_style VARCHAR(50) DEFAULT 'avataaars' AFTER email;
