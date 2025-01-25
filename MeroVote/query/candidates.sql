CREATE TABLE candidates (
    id SERIAL PRIMARY KEY, -- Unique identifier for each candidate
    name VARCHAR(255) NOT NULL, -- Candidate's name
    photo VARCHAR(255), -- Path to the candidate's photo
    election_id VARCHAR(255) NOT NULL, -- Links the candidate to a specific election
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp for when the candidate was added
    CONSTRAINT fk_election FOREIGN KEY (election_id) REFERENCES elections(name) ON DELETE CASCADE
);
