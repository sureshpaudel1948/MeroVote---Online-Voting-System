CREATE TABLE IF NOT EXISTS candidates_group (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    photo VARCHAR(255),
    election_name VARCHAR(255) NOT NULL,
    elect_no INT NOT NULL,
    panel VARCHAR(50) NOT NULL CHECK (panel IN ('Panel 1', 'Panel 2')),  -- Restricting allowed values
    candidate_position VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key Constraints
    CONSTRAINT fk_electiongroup_name FOREIGN KEY (election_name) REFERENCES elections_group(name) ON DELETE CASCADE,
    CONSTRAINT fk_elect_no_group FOREIGN KEY (elect_no) REFERENCES elections_group(id) ON DELETE CASCADE
);

-- Ensure Indexes for Performance
CREATE INDEX IF NOT EXISTS idx_candidates_group_elect_no ON candidates_group(elect_no);
CREATE INDEX IF NOT EXISTS idx_candidates_group_election_name ON candidates_group(election_name);
