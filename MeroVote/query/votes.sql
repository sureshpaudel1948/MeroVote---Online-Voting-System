CREATE TABLE votes (
    id SERIAL PRIMARY KEY, -- Unique vote identifier
    hashed_user_id VARCHAR(256) NOT NULL, -- Stores the hashed user ID for anonymity
    candidate_id INT NOT NULL, -- Links the vote to the chosen candidate
    candidate_name VARCHAR(255) NOT NULL, -- Stores the candidate's name for redundancy
    election VARCHAR(256) NOT NULL, -- Tracks which election the vote belongs to
    candidate_position VARCHAR(255) NOT NULL,
    vote_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date and time of the vote
    CONSTRAINT fk_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    CONSTRAINT fk_election FOREIGN KEY (election) REFERENCES elections(name) ON DELETE CASCADE,
    CONSTRAINT unique_vote UNIQUE (hashed_user_id, election_id) -- Ensures one vote per user per election
);
