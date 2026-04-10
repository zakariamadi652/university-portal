-- ============================================================
-- Gestion de Scolarité — Base de données
-- Module: Programmation Web (LAACHEMI 2025/2026)
-- ============================================================

DROP DATABASE IF EXISTS gestion_scolarite;
CREATE DATABASE gestion_scolarite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_scolarite;

-- ------------------------------------------------------------
-- Table: etudiants
-- ------------------------------------------------------------
CREATE TABLE etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    email VARCHAR(150) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: enseignants
-- ------------------------------------------------------------
CREATE TABLE enseignants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    specialite VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: modules
-- ------------------------------------------------------------
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_module VARCHAR(20) NOT NULL UNIQUE,
    intitule VARCHAR(200) NOT NULL,
    coefficient INT NOT NULL DEFAULT 1,
    id_enseignant INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_enseignant) REFERENCES enseignants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: notes
-- ------------------------------------------------------------
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_module INT NOT NULL,
    note DECIMAL(5,2) NOT NULL CHECK (note >= 0 AND note <= 20),
    date_saisie DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_etudiant) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_note (id_etudiant, id_module)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: utilisateurs
-- ------------------------------------------------------------
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Enseignant', 'Etudiant') NOT NULL,
    id_ref INT DEFAULT NULL COMMENT 'Référence vers etudiants.id ou enseignants.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Données de démonstration
-- ============================================================

-- Enseignants
INSERT INTO enseignants (nom, prenom, email, specialite) VALUES
('Laachemi', 'Mohammed', 'laachemi@univ.dz', 'Programmation Web'),
('Benali', 'Fatima', 'benali@univ.dz', 'Bases de Données'),
('Khedim', 'Ahmed', 'khedim@univ.dz', 'Réseaux Informatiques');

-- Modules
INSERT INTO modules (code_module, intitule, coefficient, id_enseignant) VALUES
('PROGWEB', 'Programmation Web', 3, 1),
('BDD', 'Bases de Données', 3, 2),
('RES', 'Réseaux Informatiques', 2, 3),
('ALGO', 'Algorithmique Avancée', 2, 1);

-- Étudiants
INSERT INTO etudiants (matricule, nom, prenom, date_naissance, email, niveau) VALUES
('ETU2025001', 'Boumediene', 'Amine', '2003-05-15', 'amine.b@etu.dz', 'L3 Informatique'),
('ETU2025002', 'Hamidi', 'Sara', '2002-11-22', 'sara.h@etu.dz', 'L3 Informatique'),
('ETU2025003', 'Cherif', 'Youssef', '2003-01-08', 'youssef.c@etu.dz', 'L3 Informatique'),
('ETU2025004', 'Mansouri', 'Amina', '2002-08-30', 'amina.m@etu.dz', 'L3 Informatique'),
('ETU2025005', 'Tabet', 'Karim', '2003-03-19', 'karim.t@etu.dz', 'L3 Informatique');

-- Notes
INSERT INTO notes (id_etudiant, id_module, note) VALUES
(1, 1, 15.50), (1, 2, 14.00), (1, 3, 16.00), (1, 4, 13.50),
(2, 1, 17.00), (2, 2, 16.50), (2, 3, 14.50), (2, 4, 15.00),
(3, 1, 12.00), (3, 2, 11.50), (3, 3, 13.00), (3, 4, 10.00),
(4, 1, 18.00), (4, 2, 17.50), (4, 3, 15.00), (4, 4, 16.50),
(5, 1, 10.00), (5, 2, 9.50),  (5, 3, 11.00), (5, 4, 12.00);

-- Utilisateurs (mots de passe hachés avec password_hash)
-- admin / admin123
-- prof1 / prof123 , prof2 / prof123, prof3 / prof123
-- ETU2025001..ETU2025005 / (matricule comme mot de passe)
INSERT INTO utilisateurs (username, password, role, id_ref) VALUES
('admin',      '$2y$10$as2kSCLBxfleNjPFu7.py.ZFjCYK3Czuxl0DnMCPE993.F77hsjdy', 'Admin', NULL),
('prof1',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Enseignant', 1),
('prof2',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Enseignant', 2),
('prof3',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Enseignant', 3),
('ETU2025001', '$2y$10$4B9YT4P9tEQ5XQ/2fQMPZ.2Ci1/62hmZiU/J6EsR.mb/Lkqa1./9y', 'Etudiant', 1),
('ETU2025002', '$2y$10$K66vFuPP5cnr/RMS2521zeU0wmE2SsTDAkJX0WYuTQoJcT8AhsO6u', 'Etudiant', 2),
('ETU2025003', '$2y$10$h58p1m1MKxO1qQmlAl6.O.UHkvheguC/BhqDUSFzHki6BdgoUOeHq', 'Etudiant', 3),
('ETU2025004', '$2y$10$.8rb.nEzPS6nKFpERgmRWOcd764573VfXeiIErh/iRusLvksF3y5S', 'Etudiant', 4),
('ETU2025005', '$2y$10$5nwJQhkEh4m5fRxZ7uMgz.exYsRNKl52MpYF4FKB.vGSsBMsOOrcG', 'Etudiant', 5);
