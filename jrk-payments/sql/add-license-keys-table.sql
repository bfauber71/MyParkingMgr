-- Add table to track issued license keys
CREATE TABLE IF NOT EXISTS license_keys_issued (
    id VARCHAR(36) PRIMARY KEY,
    key_hash VARCHAR(64) UNIQUE NOT NULL COMMENT 'SHA256 hash of the full key',
    key_prefix VARCHAR(10) NOT NULL COMMENT 'First 10 chars for display',
    install_id VARCHAR(36) NULL COMMENT 'Specific installation or NULL for universal',
    customer_email VARCHAR(255) NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON NULL,
    INDEX idx_key_hash (key_hash),
    INDEX idx_install_id (install_id),
    INDEX idx_customer_email (customer_email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;