CREATE TABLE IF NOT EXISTS votes_group (
    id SERIAL PRIMARY KEY,
    hashed_user_id VARCHAR(256) NOT NULL,
    candidate_id INT NOT NULL,
    candidate_name VARCHAR(255) NOT NULL,
    candidate_position VARCHAR(255) NOT NULL,
    election VARCHAR(256) NOT NULL,  -- Stores election name from elections_group
    vote_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key Constraints
    CONSTRAINT fk_candidate_group FOREIGN KEY (candidate_id) REFERENCES candidates_group(id) ON DELETE CASCADE,
    CONSTRAINT fk_election_group FOREIGN KEY (election) REFERENCES elections_group(name) ON DELETE CASCADE,

    -- Ensures a user can vote only once per position in an election
    CONSTRAINT unique_vote_group UNIQUE (hashed_user_id, election, candidate_position)
);

-- Indexes for Faster Lookups
CREATE INDEX IF NOT EXISTS idx_votes_group_election ON votes_group(election);
CREATE INDEX IF NOT EXISTS idx_votes_group_candidate ON votes_group(candidate_id);
CREATE INDEX IF NOT EXISTS idx_votes_group_hashed_user ON votes_group(hashed_user_id);
