CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL
);


CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    company_id INT NOT NULL,

    name VARCHAR(150) NOT NULL,

    -- Google Maps location
    address TEXT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    place_id VARCHAR(255) NULL,

    status BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_departments_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE employee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    company_id INT NOT NULL,
    department_id INT NOT NULL,

    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    image VARCHAR(255) NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_employee_profiles_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_employee_profiles_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_employee_profiles_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq_employee_profile_user (user_id),

    INDEX idx_employee_company (company_id),
    INDEX idx_employee_department (department_id)
);


CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    company_id INT NOT NULL,

    name VARCHAR(150) NOT NULL,

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    type ENUM(
        'Normal',
        'Overnight',
        'Part-time'
    ) NOT NULL DEFAULT 'Normal',

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_shifts_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    INDEX idx_shifts_company (company_id)
);


CREATE TABLE user_shifts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    shift_id INT NOT NULL,

    effective_from DATE NOT NULL,
    effective_to DATE NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_shifts_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_shifts_shift
        FOREIGN KEY (shift_id)
        REFERENCES shifts(id)
        ON DELETE RESTRICT,

    INDEX idx_user_shifts_user_id (user_id),
    INDEX idx_user_shifts_shift_id (shift_id),
    INDEX idx_user_shifts_effective (
        user_id,
        effective_from,
        effective_to
    )
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    shift_id INT NOT NULL,

    work_date DATE NOT NULL,
    check_in_at DATETIME NOT NULL,
    check_out_at DATETIME NULL,

    status ENUM(
        'present',
        'late',
        'absent',
        'leave'
    ) NOT NULL DEFAULT 'present',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_shift
        FOREIGN KEY (shift_id)
        REFERENCES shifts(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    UNIQUE KEY uq_attendance_user_date (
        user_id,
        work_date
    ),

    INDEX idx_attendance_user (user_id),
    INDEX idx_attendance_shift (shift_id),
    INDEX idx_attendance_date (work_date)
);

CREATE TABLE attendance_checkouts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    check_in_id INT NOT NULL,
    user_id INT NOT NULL,

    check_out_at DATETIME NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_checkout_checkin
        FOREIGN KEY (check_in_id)
        REFERENCES attendance(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_checkout_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_checkout_checkin (
        check_in_id
    ),

    INDEX idx_checkout_user (
        user_id
    ),

    INDEX idx_checkout_time (
        check_out_at
    )
);