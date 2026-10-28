-- Script SQL pour ajouter le paramètre form_layout_centered

INSERT INTO `grr_setting` (`NAME`, `VALUE`) 
VALUES (
    'form_layout_centered', 
    '0')
--    'Affichage du formulaire de réservation : 0=standard (2 colonnes), 1=centré et empilé (contemporain et responsive)'
ON DUPLICATE KEY UPDATE `VALUE` = VALUES(`VALUE`);
