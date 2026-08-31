-- suppy
CREATE TABLE supply_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20),
    address VARCHAR(255),
    description TEXT,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE imports (
    id INT AUTO_INCREMENT PRIMARY KEY,

    import_code VARCHAR(50) NOT NULL UNIQUE,

    supply_location_id INT NOT NULL,

    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'pending',
        'paid',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_import_supply_location
        FOREIGN KEY (supply_location_id)
        REFERENCES supply_locations(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,

    import_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    const_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    sell_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    subtotal DECIMAL(12,2)
        GENERATED ALWAYS AS (
            quantity * const_price
        ) STORED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_import_detail_import
        FOREIGN KEY (import_id)
        REFERENCES imports(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_import_detail_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE import_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    import_id INT NOT NULL,

    payment_method VARCHAR(50) NOT NULL,

    paid_amount DECIMAL(12,2) NOT NULL,

    cash_received DECIMAL(12,2) NULL,

    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    note VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_import_payment_import
        FOREIGN KEY (import_id)
        REFERENCES imports(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);
-- users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    role_id INT NOT NULL,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- refresh_token
CREATE TABLE refresh_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    token_hash VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    revoked_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_refresh_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    order_id INT UNSIGNED NOT NULL,

    amount DECIMAL(15,2) NOT NULL,

    currency VARCHAR(3) NOT NULL DEFAULT 'KHR',

    md5 VARCHAR(32) NOT NULL,

    transaction_hash VARCHAR(255) NULL,

    from_account_id VARCHAR(255) NULL,

    to_account_id VARCHAR(255) NULL,

    payment_method VARCHAR(50) NOT NULL DEFAULT 'bakong',

    status ENUM(
        'pending',
        'success',
        'failed'
    ) NOT NULL DEFAULT 'pending',

    payment_date DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_md5 (md5),

    INDEX idx_order_id (order_id),

    INDEX idx_status (status)
);