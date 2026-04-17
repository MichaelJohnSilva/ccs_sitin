-- Add computer_number column to reservations table
ALTER TABLE reservations ADD COLUMN computer_number VARCHAR(10) AFTER lab;