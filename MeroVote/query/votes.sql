CREATE TABLE votes (
    id SERIAL PRIMARY KEY, -- Unique vote identifier
    user_id INT NOT NULL, -- Tracks the voter while maintaining anonymity
    candidate_id INT NOT NULL, -- Links the vote to the chosen candidate
    election_id INT NOT NULL, -- Tracks which election the vote belongs to
    vote_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date and time of the vote
    CONSTRAINT fk_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    CONSTRAINT fk_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
);