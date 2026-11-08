-- ============================================================================
-- Feature 2 : display-created-by
-- SQL INSERT corrigé pour la table grr_setting
-- ============================================================================

-- Schema réel de grr_setting :
-- CREATE TABLE `grr_setting` (
--   `NAME` varchar(32) NOT NULL DEFAULT '',
--   `VALUE` text NOT NULL,
--   PRIMARY KEY (`NAME`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Script SQL pour ajouter les paramètres d'affichage du créateur
-- ============================================================================

-- Affichage créateur pour non connecté (0=non, 1=oui, 2=popup)
INSERT INTO `grr_setting` (`NAME`, `VALUE`)
VALUES (
    'display_creator_nc',
    '2')
-- Description : Afficher créateur pour non connecté : 0=non, 1=oui, 2=info-bulle
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);

-- Affichage créateur pour visiteur (0=non, 1=oui, 2=info-bulle)
INSERT INTO `grr_setting` (`NAME`, `VALUE`)
VALUES (
    'display_creator_vi',
    '2')
-- Description : Afficher créateur pour visiteur : 0=non, 1=oui, 2=info-bulle
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);

-- Affichage créateur pour utilisateur (0=non, 1=oui, 2=info-bulle)
INSERT INTO `grr_setting` (`NAME`, `VALUE`)
VALUES (
    'display_creator_us',
    '1')
-- Description : Afficher créateur pour utilisateur : 0=non, 1=oui, 2=info-bulle
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);

-- Affichage créateur pour gestionnaire (0=non, 1=oui, 2=info-bulle)
INSERT INTO `grr_setting` (`NAME`, `VALUE`)
VALUES (
    'display_creator_gr',
    '1')
-- Description : Afficher créateur pour gestionnaire : 0=non, 1=oui, 2=info-bulle
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);

-- Affichage créateur pour admin (0=non, 1=oui, 2=info-bulle)
INSERT INTO `grr_setting` (`NAME`, `VALUE`)
VALUES (
    'display_creator_ad',
    '1')
-- Description : Afficher créateur pour admin : 0=non, 1=oui, 2=info-bulle
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);

-- ============================================================================
-- RÉSUMÉ DES VALEURS PAR DÉFAUT
-- ============================================================================
-- display_creator_nc = '2' (non connecté : affichage en info-bulle)
-- display_creator_vi = '2' (visiteur : affichage en info-bulle)
-- display_creator_us = '1' (utilisateur : affichage inline)
-- display_creator_gr = '1' (gestionnaire : affichage inline)
-- display_creator_ad = '1' (admin : affichage inline)
-- ============================================================================
