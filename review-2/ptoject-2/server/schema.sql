CREATE DATABASE IF NOT EXISTS ticket_booking;
USE ticket_booking;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(100) NOT NULL,
  department_name VARCHAR(100) NOT NULL,
  date_time VARCHAR(100) NOT NULL,
  venue VARCHAR(100) NOT NULL,
  ticket_price DECIMAL(10,2) NOT NULL,
  available_tickets INT NOT NULL,
  total_tickets INT NOT NULL DEFAULT 0,
  status ENUM('open','closed') DEFAULT 'open'
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  department VARCHAR(100) NOT NULL,
  ticket_count INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('confirmed','cancelled') DEFAULT 'confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Upgrade existing tables
ALTER TABLE events ADD COLUMN IF NOT EXISTS total_tickets INT NOT NULL DEFAULT 0;
ALTER TABLE events ADD COLUMN IF NOT EXISTS status ENUM('open','closed') DEFAULT 'open';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS status ENUM('confirmed','cancelled') DEFAULT 'confirmed';

-- Sync total_tickets for existing rows
UPDATE events SET total_tickets = available_tickets WHERE total_tickets = 0;

-- Sample events
INSERT INTO events (event_name, department_name, date_time, venue, ticket_price, available_tickets, total_tickets, status)
SELECT 'TechFest 2026','Computer Science & Engineering','20 April 2026, 10:00 AM','Seminar Hall, Block A',200.00,100,100,'open'
WHERE NOT EXISTS (SELECT 1 FROM events LIMIT 1);
