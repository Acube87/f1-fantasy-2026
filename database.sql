-- F1 Fantasy Game Database Schema

CREATE DATABASE IF NOT EXISTS f1_fantasy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE f1_fantasy;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Races table (stores race information)
CREATE TABLE IF NOT EXISTS races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_name VARCHAR(100) NOT NULL,
    circuit_name VARCHAR(100),
    country VARCHAR(50),
    race_date DATE NOT NULL,
    race_number INT NOT NULL,
    status ENUM('upcoming', 'in_progress', 'completed', 'cancelled') DEFAULT 'upcoming',
    f1_race_id VARCHAR(50),
    results_fetched BOOLEAN DEFAULT FALSE,
    results_fetched_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_race_date (race_date),
    INDEX idx_race_number (race_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id VARCHAR(50) PRIMARY KEY,
    driver_name VARCHAR(100) NOT NULL,
    team VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Constructors table
CREATE TABLE IF NOT EXISTS constructors (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Race results table (stores actual F1 results)
CREATE TABLE IF NOT EXISTS race_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_id INT NOT NULL,
    driver_id VARCHAR(50) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    constructor_id VARCHAR(50),
    constructor_name VARCHAR(100),
    position INT NOT NULL,
    points DECIMAL(5,2) DEFAULT 0,
    fastest_lap BOOLEAN DEFAULT FALSE,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    INDEX idx_race_id (race_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User predictions table (driver positions)
CREATE TABLE IF NOT EXISTS predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    driver_id VARCHAR(50) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    predicted_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_race_driver (user_id, race_id, driver_id),
    INDEX idx_user_race (user_id, race_id),
    INDEX idx_race_id (race_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Constructor predictions table
CREATE TABLE IF NOT EXISTS constructor_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    constructor_id VARCHAR(50) NOT NULL,
    constructor_name VARCHAR(100) NOT NULL,
    predicted_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_race_constructor (user_id, race_id, constructor_id),
    INDEX idx_user_race (user_id, race_id),
    INDEX idx_race_id (race_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scores table (calculated scores per race per user)
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    driver_points INT DEFAULT 0,
    constructor_points INT DEFAULT 0,
    top3_bonus INT DEFAULT 0,
    constructor_top3_bonus INT DEFAULT 0,
    total_points INT DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_race_score (user_id, race_id),
    INDEX idx_user_id (user_id),
    INDEX idx_race_id (race_id),
    INDEX idx_total_points (total_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User total scores (aggregated)
CREATE TABLE IF NOT EXISTS user_totals (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    races_participated INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;