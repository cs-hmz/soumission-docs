-- ============================================
-- Base de données : etudiants_app
-- Plateforme Étudiante de Soumission de Documents
-- ============================================

CREATE DATABASE IF NOT EXISTS etudiants_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE etudiants_app;

-- ============================================
-- Table : etudiants
-- ============================================
CREATE TABLE IF NOT EXISTS etudiants (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)        NOT NULL,
    prenom        VARCHAR(100)        NOT NULL,
    num_apogee    VARCHAR(20)         NOT NULL UNIQUE,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255)        DEFAULT NULL,          -- rempli après activation
    code_activation VARCHAR(8)        NOT NULL,              -- 8 chiffres aléatoires
    est_active    TINYINT(1)          NOT NULL DEFAULT 0,    -- 0 = non activé, 1 = activé
    date_inscription DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table : administrateurs
-- ============================================
CREATE TABLE IF NOT EXISTS administrateurs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255)        NOT NULL,
    date_creation DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Compte admin par défaut (mot de passe : Admin1234)
-- À changer en production !
INSERT INTO administrateurs (nom, email, mot_de_passe)
VALUES (
    'Administrateur',
    'admin@example.com',
    '$2y$12$eImiTXuWVxfM37uY4JANjOe5XxPaXBFAMSe1Y4RULtEGasj1Dl5V2'
)
ON DUPLICATE KEY UPDATE id = id;

-- ============================================
-- Table : fichiers
-- ============================================
CREATE TABLE IF NOT EXISTS fichiers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id   INT UNSIGNED        NOT NULL,
    nom_fichier   VARCHAR(255)        NOT NULL,              -- nom original
    nom_stockage  VARCHAR(255)        NOT NULL,              -- nom unique sur disque
    type_mime     VARCHAR(100)        NOT NULL,
    taille        INT UNSIGNED        NOT NULL,              -- en octets
    date_envoi    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fichiers_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Index utiles
-- ============================================
CREATE INDEX idx_etudiants_email       ON etudiants(email);
CREATE INDEX idx_etudiants_code        ON etudiants(code_activation);
CREATE INDEX idx_fichiers_etudiant_id  ON fichiers(etudiant_id);