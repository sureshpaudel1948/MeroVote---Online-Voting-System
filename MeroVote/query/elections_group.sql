CREATE TABLE elections_group (
    id SERIAL PRIMARY KEY,
    election_type VARCHAR(256) NOT NULL,
    name VARCHAR(256) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    panel1_pos1 VARCHAR(255) NOT NULL,
    panel1_pos2 VARCHAR(255) NOT NULL,
    panel1_pos3 VARCHAR(255) NOT NULL,
    panel1_pos4 VARCHAR(255) NOT NULL,
    panel2_pos1 VARCHAR(255) NOT NULL,
    panel2_pos2 VARCHAR(255) NOT NULL,
    panel2_pos3 VARCHAR(255) NOT NULL,
    panel2_pos4 VARCHAR(255) NOT NULL
);
