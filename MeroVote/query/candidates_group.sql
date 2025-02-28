CREATE TABLE candidates_group (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    photo VARCHAR(255),
    election_name VARCHAR(255) NOT NULL,
    elect_no INT NOT NULL,
    panel VARCHAR(50) NOT NULL,  -- 'Panel 1' or 'Panel 2'
    candidate_position VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_electiongroup_name FOREIGN KEY (election_name) REFERENCES elections_group(name) ON DELETE CASCADE,
    CONSTRAINT fk_elect_no_group FOREIGN KEY (elect_no) REFERENCES elections_group(id) ON DELETE CASCADE
);