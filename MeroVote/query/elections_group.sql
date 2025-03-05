CREATE TABLE IF NOT EXISTS elections_group (
    id SERIAL PRIMARY KEY,
    election_type VARCHAR(256) NOT NULL,
    name VARCHAR(256) NOT NULL UNIQUE,
    start_date DATE NOT NULL CHECK (start_date <= end_date),  -- Ensures start_date is before or equal to end_date
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    -- Panel Positions (Defaults to empty string for optional fields)
    panel1_pos1 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos2 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos3 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos4 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos5 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos6 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos7 VARCHAR(255) NOT NULL DEFAULT '',
    panel1_pos8 VARCHAR(255) NOT NULL DEFAULT '',

    panel2_pos1 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos2 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos3 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos4 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos5 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos6 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos7 VARCHAR(255) NOT NULL DEFAULT '',
    panel2_pos8 VARCHAR(255) NOT NULL DEFAULT '',

    panel3_pos1 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos2 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos3 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos4 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos5 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos6 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos7 VARCHAR(255) NOT NULL DEFAULT '',
    panel3_pos8 VARCHAR(255) NOT NULL DEFAULT '',

    panel4_pos1 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos2 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos3 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos4 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos5 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos6 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos7 VARCHAR(255) NOT NULL DEFAULT '',
    panel4_pos8 VARCHAR(255) NOT NULL DEFAULT ''
);

-- Ensure Indexes for Faster Lookups
CREATE INDEX IF NOT EXISTS idx_elections_group_name ON elections_group(name);
CREATE INDEX IF NOT EXISTS idx_elections_group_start_date ON elections_group(start_date);
