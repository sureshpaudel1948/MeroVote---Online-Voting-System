CREATE TABLE candidates (
    id SERIAL PRIMARY KEY, -- Unique identifier for each candidate
    name VARCHAR(255) NOT NULL, -- Candidate's name
    photo VARCHAR(255), -- Path to the candidate's photo
    election_name VARCHAR(255) NOT NULL, -- Links the candidate to a specific election
    elect_no INT NOT NULL, -- Links the candidate to an election ID
    candidate_position VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp for when the candidate was added
    CONSTRAINT fk_election_name FOREIGN KEY (election_name) REFERENCES elections(name) ON DELETE CASCADE,
    CONSTRAINT fk_elect_no FOREIGN KEY (elect_no) REFERENCES elections(id) ON DELETE CASCADE
);
