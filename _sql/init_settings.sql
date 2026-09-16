-- Script SQL pour ajouter le paramètre form_layout_centered

INSERT INTO `grr_setting` (`key`, `value`, `description`) 
VALUES (
    'form_layout_centered', 
    '0', 
    'Affichage du formulaire de réservation : 0=standard (2 colonnes), 1=centré et empilé (moderne)'
)
ON DUPLICATE KEY UPDATE `description` = 'Affichage du formulaire de réservation : 0=standard (2 colonnes), 1=centré et empilé (moderne)';
