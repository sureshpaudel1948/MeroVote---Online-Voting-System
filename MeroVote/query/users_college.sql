CREATE TABLE users_college (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL, -- Already filled
    phone_number VARCHAR(15),       -- Left empty to be updated
    password VARCHAR(255),          -- Left empty to be updated
    role VARCHAR(50) NOT NULL,      -- Already filled
    gender VARCHAR(10) NOT NULL,    -- Already filled
    student_id VARCHAR(50) NOT NULL -- Already filled
);
