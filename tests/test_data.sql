-- Minimal test data for abrechnung_test database
CREATE TABLE IF NOT EXISTS db_personen (
  person_id INT AUTO_INCREMENT PRIMARY KEY,
  vorname VARCHAR(100),
  nachname VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS db_produkte_standard (
  produkt_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  preis DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS db_eintrag (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE,
  person INT,
  produkt INT,
  anzahl INT,
  bezahlt TINYINT(1) DEFAULT 0
);

INSERT INTO db_personen (vorname, nachname) VALUES
('Anna', 'Müller'),
('Max', 'Schmidt');

INSERT INTO db_produkte_standard (name, preis) VALUES
('Wasser', 1.50),
('Bier', 2.80);

INSERT INTO db_eintrag (date, person, produkt, anzahl, bezahlt)
VALUES
(CURDATE(), 1, 1, 2, 0),
(CURDATE(), 2, 2, 1, 1);
