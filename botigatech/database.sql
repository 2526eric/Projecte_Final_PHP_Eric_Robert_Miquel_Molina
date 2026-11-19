-- =====================================================
-- BotigaTech - Projecte final PHP
-- Base de dades de la botiga online de components d'informàtica
-- Es pot importar directament des de phpMyAdmin (o HeidiSQL)
-- =====================================================

-- Fem servir UTF-8 perquè es guardin bé els accents i la "ç"
SET NAMES utf8mb4;

-- Si la base de dades ja existeix, l'esborrem i la tornem a crear
DROP DATABASE IF EXISTS botigatech;
CREATE DATABASE botigatech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE botigatech;

-- -----------------------------------------------------
-- Taula USUARIS
-- Guarda els clients i els administradors de la botiga.
-- La contrasenya es guarda encriptada amb password_hash().
-- -----------------------------------------------------
CREATE TABLE usuaris (
    id_usuari     INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(50)  NOT NULL,
    cognoms       VARCHAR(80)  NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    contrasenya   VARCHAR(255) NOT NULL,
    rol           ENUM('client','administrador') NOT NULL DEFAULT 'client',
    data_registre DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Taula CATEGORIES
-- Tipus de producte (processadors, memòria, etc.)
-- -----------------------------------------------------
CREATE TABLE categories (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(50) NOT NULL UNIQUE,
    descripcio   TEXT
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Taula PRODUCTES
-- Cada producte pertany a una categoria (clau forana).
-- -----------------------------------------------------
CREATE TABLE productes (
    id_producte  INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT           NOT NULL,
    nom          VARCHAR(100)  NOT NULL,
    marca        VARCHAR(50)   NOT NULL,
    descripcio   TEXT,
    preu         DECIMAL(8,2)  NOT NULL,
    stock        INT           NOT NULL DEFAULT 0,
    imatge       VARCHAR(255)  DEFAULT NULL,
    data_alta    DATE          NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categories(id_categoria)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Taula COMANDES
-- Una comanda la fa un usuari i té un estat.
-- -----------------------------------------------------
CREATE TABLE comandes (
    id_comanda   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuari    INT            NOT NULL,
    data_comanda DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estat        ENUM('pendent','enviada','entregada','cancelada') NOT NULL DEFAULT 'pendent',
    total        DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (id_usuari) REFERENCES usuaris(id_usuari)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Taula DETALLS_COMANDA
-- Les línies de cada comanda: quin producte i quantes unitats.
-- Guardem el preu unitari per si el preu del producte canvia més endavant.
-- -----------------------------------------------------
CREATE TABLE detalls_comanda (
    id_detall     INT AUTO_INCREMENT PRIMARY KEY,
    id_comanda    INT          NOT NULL,
    id_producte   INT          NOT NULL,
    quantitat     INT          NOT NULL,
    preu_unitari  DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (id_comanda)  REFERENCES comandes(id_comanda)  ON DELETE CASCADE,
    FOREIGN KEY (id_producte) REFERENCES productes(id_producte) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Taula VALORACIONS (funcionalitat extra)
-- Un usuari només pot valorar un cop cada producte.
-- -----------------------------------------------------
CREATE TABLE valoracions (
    id_valoracio   INT AUTO_INCREMENT PRIMARY KEY,
    id_producte    INT        NOT NULL,
    id_usuari      INT        NOT NULL,
    puntuacio      TINYINT    NOT NULL,
    comentari      TEXT,
    data_valoracio DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY una_valoracio_per_usuari (id_producte, id_usuari),
    FOREIGN KEY (id_producte) REFERENCES productes(id_producte) ON DELETE CASCADE,
    FOREIGN KEY (id_usuari)   REFERENCES usuaris(id_usuari)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DADES D'EXEMPLE
-- =====================================================

-- Usuaris de prova
--   admin@test.com  / admin123   (administrador)
--   usuari@test.com / 12345678   (client)
--   pau@test.com    / 12345678   (client)
--   laia@test.com   / 12345678   (client)
INSERT INTO usuaris (nom, cognoms, email, contrasenya, rol, data_registre) VALUES
('Admin',  'Botiga',  'admin@test.com',  '$2y$10$mqzWzkiHwjO051aTQsCjbuYAQMZd6vrYpghp9O9ydMG5a4gG9SSVm', 'administrador', '2026-09-01 10:00:00'),
('Marta',  'Soler',   'usuari@test.com', '$2y$10$1NHg.F0.NLwpfONRyftMzeBZsPXqckTmKYbjubl/SksYYV3KjPJRu', 'client',         '2026-09-05 17:30:00'),
('Pau',    'Ferrer',  'pau@test.com',    '$2y$10$1NHg.F0.NLwpfONRyftMzeBZsPXqckTmKYbjubl/SksYYV3KjPJRu', 'client',         '2026-09-12 09:15:00'),
('Laia',   'Puig',    'laia@test.com',   '$2y$10$1NHg.F0.NLwpfONRyftMzeBZsPXqckTmKYbjubl/SksYYV3KjPJRu', 'client',         '2026-10-02 20:45:00');

INSERT INTO categories (nom, descripcio) VALUES
('Processadors',        'CPU per a ordinadors de sobretaula.'),
('Targetes gràfiques',  'GPU per a jocs, disseny i càlcul.'),
('Memòria RAM',         'Mòduls de memòria DDR4 i DDR5.'),
('Emmagatzematge',      'Discs SSD i discs durs.'),
('Perifèrics',          'Ratolins, teclats, monitors i auriculars.');

INSERT INTO productes (id_categoria, nom, marca, descripcio, preu, stock, imatge, data_alta) VALUES
(1, 'Ryzen 5 5600',                    'AMD',             'Processador de 6 nuclis i 12 fils, ideal per a un PC de gamma mitjana.',          129.90, 15, NULL, '2026-09-10'),
(1, 'Core i5-12400F',                  'Intel',           'Processador de 6 nuclis sense gràfics integrats.',                                 139.00, 10, NULL, '2026-09-10'),
(1, 'Ryzen 7 5700X',                   'AMD',             'Processador de 8 nuclis i 16 fils per a multitasca i jocs.',                       189.00,  0, NULL, '2026-09-15'),
(2, 'GeForce RTX 4060 8GB',            'NVIDIA',          'Targeta gràfica per jugar a 1080p amb bon rendiment.',                             299.00,  8, NULL, '2026-09-20'),
(2, 'Radeon RX 7600 8GB',              'AMD',             'Targeta gràfica de gamma mitjana per a 1080p.',                                    269.00,  5, NULL, '2026-09-20'),
(3, 'Fury Beast 16GB DDR4 3200',       'Kingston',        'Mòdul de memòria RAM de 16 GB a 3200 MHz.',                                         44.90, 40, NULL, '2026-09-25'),
(3, 'Vengeance 32GB DDR5 (2x16GB)',    'Corsair',         'Kit de dos mòduls DDR5 de 16 GB cadascun.',                                        109.90, 12, NULL, '2026-09-25'),
(4, '980 SSD NVMe 1TB',                'Samsung',         'Disc SSD NVMe de 1 TB, arrencada ràpida i bona velocitat.',                         79.90, 25, NULL, '2026-10-01'),
(4, 'MX500 SSD 500GB',                 'Crucial',         'Disc SSD SATA de 500 GB, fiable i econòmic.',                                       49.90, 30, NULL, '2026-10-01'),
(4, 'Blue 2TB HDD',                    'Western Digital', 'Disc dur de 2 TB per guardar molta informació.',                                    59.90,  0, NULL, '2026-10-03'),
(5, 'G203 Ratolí gaming',              'Logitech',        'Ratolí amb cable i llum RGB, sensor de 8000 DPI.',                                  24.90, 50, NULL, '2026-10-05'),
(5, 'K552 Teclat mecànic',             'Redragon',        'Teclat mecànic compacte sense teclat numèric.',                                     39.90, 20, NULL, '2026-10-05'),
(5, 'Monitor 24" Full HD',             'LG',              'Monitor IPS de 24 polzades amb resolució 1920x1080.',                              119.00,  7, NULL, '2026-10-08'),
(5, 'Cloud Stinger Auriculars',        'HyperX',          'Auriculars amb micròfon, lleugers i còmodes.',                                      49.90, 18, NULL, '2026-10-10');

INSERT INTO comandes (id_usuari, data_comanda, estat, total) VALUES
(2, '2026-10-15 18:20:00', 'entregada', 219.70),
(3, '2026-10-28 12:05:00', 'enviada',   323.90),
(2, '2026-11-10 21:40:00', 'pendent',    79.90);

INSERT INTO detalls_comanda (id_comanda, id_producte, quantitat, preu_unitari) VALUES
(1, 1, 1, 129.90),
(1, 6, 2,  44.90),
(2, 4, 1, 299.00),
(2, 11, 1, 24.90),
(3, 8, 1,  79.90);

INSERT INTO valoracions (id_producte, id_usuari, puntuacio, comentari, data_valoracio) VALUES
(1, 2, 5, 'Molt bon processador pel preu, va fluid amb tot.',          '2026-10-20 19:00:00'),
(1, 3, 4, 'Compleix bé, però la refrigeració de sèrie és justa.',      '2026-10-25 11:30:00'),
(4, 3, 5, 'Va perfecte per jugar a 1080p.',                             '2026-11-02 22:10:00'),
(6, 2, 4, 'Memòria fiable, ha funcionat a la primera.',                '2026-10-21 08:45:00'),
(8, 2, 5, 'L''ordinador arrenca molt més ràpid.',                      '2026-11-12 10:20:00'),
(11, 3, 3, 'Està bé pel preu, però el cable és curt.',                 '2026-11-03 17:00:00');
