-- Create database
CREATE DATABASE transmitter_fault_system;
USE transmitter_fault_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Engineer','Technician') DEFAULT 'Technician',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transmitters table
CREATE TABLE transmitters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transmitter_name VARCHAR(50) NOT NULL,
    frequency VARCHAR(50),
    location VARCHAR(100),
    manufacturer VARCHAR(100),
    power_rating VARCHAR(50),
    status ENUM('Operational','Maintenance','Offline','Faulty') DEFAULT 'Operational',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Faults table
CREATE TABLE faults (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fault_no VARCHAR(20) UNIQUE NOT NULL,
    transmitter_id INT,
    program_name VARCHAR(100),
    frequency VARCHAR(50),
    fault_description TEXT NOT NULL,
    severity ENUM('Low','Medium','Critical') DEFAULT 'Medium',
    reported_by INT,
    date_reported DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Open','In Progress','Fixed','Closed') DEFAULT 'Open',
    FOREIGN KEY (transmitter_id) REFERENCES transmitters(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Troubleshooting table
CREATE TABLE troubleshooting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fault_id INT NOT NULL,
    observation TEXT,
    actions_taken TEXT,
    measurement TEXT,
    date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    FOREIGN KEY (fault_id) REFERENCES faults(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Solutions table
CREATE TABLE solutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fault_id INT NOT NULL,
    root_cause TEXT,
    solution TEXT NOT NULL,
    parts_replaced TEXT,
    repair_time VARCHAR(50),
    fixed_by INT,
    date_fixed DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fault_id) REFERENCES faults(id) ON DELETE CASCADE,
    FOREIGN KEY (fixed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Maintenance table
CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transmitter_id INT,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    date_done DATE,
    engineer VARCHAR(100),
    next_maintenance_date DATE,
    status ENUM('Scheduled','In Progress','Completed','Overdue') DEFAULT 'Scheduled',
    FOREIGN KEY (transmitter_id) REFERENCES transmitters(id) ON DELETE SET NULL
);

-- Audit log table (for tracking changes)
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Spare parts inventory (optional but useful)
CREATE TABLE spare_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_name VARCHAR(100) NOT NULL,
    part_number VARCHAR(50),
    quantity INT DEFAULT 0,
    min_quantity INT DEFAULT 5,
    location VARCHAR(100),
    supplier VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (fullname, username, password, role) VALUES
('Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('Mandla Matsebula', 'mandla', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Engineer'),
('Technician User', 'tech', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician');

INSERT INTO transmitters (transmitter_name, frequency, location, manufacturer, power_rating, status) VALUES
('TX1', '101.5 MHz', 'Studio A', 'Harris', '100 kW', 'Operational'),
('TX2', '95.8 MHz', 'Studio B', 'Nautel', '50 kW', 'Operational'),
('TX3', '15105 kHz', 'Studio C', 'Harris', '100 kW', 'Operational'),
('TX4', '89.2 MHz', 'Studio D', 'Continental', '25 kW', 'Maintenance'),
('TX5', '98.7 MHz', 'Studio E', 'Nautel', '75 kW', 'Operational');

INSERT INTO faults (fault_no, transmitter_id, program_name, frequency, fault_description, severity, reported_by) VALUES
('F0001', 3, 'Afar and Tigrinya', '15105 kHz', 'No audio output on Afar and Tigrinya programs. Transmitter screen frozen.', 'Critical', 1),
('F0002', 1, 'Morning Show', '101.5 MHz', 'Low audio level - VSWR high', 'Medium', 2);