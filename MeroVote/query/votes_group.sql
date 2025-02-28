CREATE TABLE votes_group (
    id SERIAL PRIMARY KEY,
    hashed_user_id VARCHAR(256) NOT NULL,
    candidate_id INT NOT NULL,
    candidate_name VARCHAR(255) NOT NULL,
    candidate_position VARCHAR(255) NOT NULL,
    election VARCHAR(256) NOT NULL,  -- This stores the election name from elections_group
    vote_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_group FOREIGN KEY (candidate_id) REFERENCES candidates_group(id) ON DELETE CASCADE,
    CONSTRAINT fk_election_group FOREIGN KEY (election) REFERENCES elections_group(name) ON DELETE CASCADE,
    CONSTRAINT unique_vote_group UNIQUE (hashed_user_id, election)
);
