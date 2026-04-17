-- Add computer_number column to sitin_records table if not exists
ALTER TABLE sitin_records ADD COLUMN computer_number VARCHAR(10) AFTER lab;

-- Add computer_number column to reservations table if not exists  
ALTER TABLE reservations ADD COLUMN computer_number VARCHAR(10) AFTER lab;