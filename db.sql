-- Création de la base de données pour l'application web soumission-docs
-- Encodage UTF8MB4 pour supporter les accents et les caractères spéciaux

CREATE DATABASE IF NOT EXISTS `soumission_docs`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `soumission_docs`;

-- Table des étudiants
CREATE TABLE IF NOT EXISTS `etudiants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `apogee` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `code_activation` VARCHAR(20) DEFAULT NULL,
    `est_active` TINYINT(1) NOT NULL DEFAULT 0,
    `mot_de_passe` VARCHAR(255) DEFAULT NULL,
    `date_inscription` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_etudiants_apogee` (`apogee`),
    UNIQUE KEY `uk_etudiants_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Table des fichiers uploadés par les étudiants
CREATE TABLE IF NOT EXISTS `fichiers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_fichier` VARCHAR(255) NOT NULL,
    `date_upload` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `id_etudiant` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_fichiers_etudiant` (`id_etudiant`),
    CONSTRAINT `fk_fichiers_etudiant`
        FOREIGN KEY (`id_etudiant`)
        REFERENCES `etudiants` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Table des administrateurs
CREATE TABLE IF NOT EXISTS `administrateurs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `mot_de_passe` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_administrateurs_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
-- Compte administrateur de test
INSERT IGNORE INTO `administrateurs` (`nom`, `email`, `mot_de_passe`)
VALUES ('Administrateur', 'admin@example.com', '$2y$10$0hmM0Y9IIOwugP2h7BFGremxc/9vcpoW8XfrXopVIKMxsjB2WsMkm');
