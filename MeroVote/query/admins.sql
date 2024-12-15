CREATE TABLE admins (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL, -- Already filled
    phone_number VARCHAR(15) NOT NULL, -- Already filled
    password VARCHAR(255),           -- Left empty to be updated
    role VARCHAR(50) NOT NULL,       -- Already filled
    admin_id VARCHAR(50) NOT NULL    -- Already filled
);

