CREATE TABLE elections (
    id SERIAL PRIMARY KEY,
    election_type VARCHAR(256) NOT NULL,
    name VARCHAR(256) NOT NULL UNIQUE, -- Ensure name is unique
    election_position VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
);