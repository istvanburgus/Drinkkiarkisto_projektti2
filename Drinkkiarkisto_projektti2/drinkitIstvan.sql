
-- Kayttaja table
CREATE TABLE Kayttaja (
    Kayttaja_ID INT AUTO_INCREMENT PRIMARY KEY,
    Kayttajanimi VARCHAR(50) NOT NULL UNIQUE,
    Sahkoposti VARCHAR(255) NOT NULL UNIQUE,
    Salasana VARCHAR(255) NOT NULL,
    Rooli TINYINT DEFAULT 0
);

-- Resepti table
CREATE TABLE Resepti (
    Drinkkin_ID INT AUTO_INCREMENT PRIMARY KEY,
    Kayttaja_ID INT NOT NULL,
    Nimi VARCHAR(50) NOT NULL,
    Kuvaus VARCHAR(255),
    Juomalaji VARCHAR(40),
    Hyvaksytty TINYINT DEFAULT 0,

    FOREIGN KEY (Kayttaja_ID) 
        REFERENCES Kayttaja(Kayttaja_ID)
        ON DELETE CASCADE
);

-- Aines table
CREATE TABLE Aines (
    Aines_ID INT AUTO_INCREMENT PRIMARY KEY,
    Aines_nimi VARCHAR(50) NOT NULL
);

-- Ainesosa table (junction table)
CREATE TABLE Ainesosa (
    Drinkkin_ID INT NOT NULL,
    Aines_ID INT NOT NULL,
    Maara VARCHAR(20),

    PRIMARY KEY (Drinkkin_ID, Aines_ID),

    FOREIGN KEY (Drinkkin_ID) 
        REFERENCES Resepti(Drinkkin_ID)
        ON DELETE CASCADE,

    FOREIGN KEY (Aines_ID) 
        REFERENCES Aines(Aines_ID)
        ON DELETE CASCADE
);