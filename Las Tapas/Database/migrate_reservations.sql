USE las_tapas;

CREATE TABLE IF NOT EXISTS reservation_tables (
    table_number TINYINT UNSIGNED NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (table_number)
) ENGINE=InnoDB;

INSERT IGNORE INTO reservation_tables (table_number, capacity) VALUES
    (1, 2), (2, 2), (3, 2),
    (4, 4), (5, 4), (6, 4), (7, 4),
    (8, 6), (9, 6), (10, 6),
    (11, 8), (12, 8), (13, 8), (14, 8),
    (15, 8), (16, 8), (17, 8), (18, 8),
    (19, 8), (20, 8), (21, 8), (22, 8),
    (23, 8), (24, 8), (25, 8), (26, 8);

CREATE TABLE IF NOT EXISTS reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(254) NOT NULL,
    special_requests VARCHAR(1000) NOT NULL DEFAULT '',
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    guest_count TINYINT UNSIGNED NOT NULL,
    table_number TINYINT UNSIGNED NOT NULL,
    package_code ENUM('two-hour', 'three-hour', 'three-hour-drinks') NOT NULL,
    price_per_guest DECIMAL(6, 2) NOT NULL,
    total_price DECIMAL(8, 2) NOT NULL,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_reservations_table_date (table_number, reservation_date, status),
    CONSTRAINT fk_reservations_table
        FOREIGN KEY (table_number) REFERENCES reservation_tables (table_number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservation_request_limits (
    client_hash CHAR(64) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_hash),
    INDEX idx_reservation_request_window (window_started_at)
) ENGINE=InnoDB;
