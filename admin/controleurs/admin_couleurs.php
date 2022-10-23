<?php
/**
 * admin_couleurs.php
 * Interface permettant à l'administrateur la personnalisation de certaines couleurs
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-02-28 20:05$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_couleurs.php";

$back = 'admin_accueil.php';
$ok = (isset($_GET['ok']))? $_GET['ok']: NULL;


get_vocab_admin('admin_couleurs');
get_vocab_admin('admin_couleurs_explications');

get_vocab_admin('page_header');
get_vocab_admin('menuG');
get_vocab_admin('cal_titre');
get_vocab_admin('cal_jours');
get_vocab_admin('cal_sem');
get_vocab_admin('cal_semhovercolor');
get_vocab_admin('cal_week');
get_vocab_admin('cal_cell');
get_vocab_admin('cal_cellhover');
get_vocab_admin('cal_current_day_');
get_vocab_admin('pl2_titre');
get_vocab_admin('pl2_entete');
get_vocab_admin('pl2_cell');
get_vocab_admin('icons_color');
get_vocab_admin('btn_primary_');
get_vocab_admin('active_btn_primary_');
get_vocab_admin('focus_btn_primary_');

get_vocab_admin('text');
get_vocab_admin('bgcolor');
get_vocab_admin('hover_text');
get_vocab_admin('bordcolor');

get_vocab_admin('cancel');
get_vocab_admin('default');
get_vocab_admin('save');

get_vocab_admin('message_records');

/* initialisations */
// Liste des couleurs paramétrées
$champs_couleur=array('header_bgcolor' => '--header-bgcolor'
,'header_text' => '--header-text'
,'header_hover' => '--header-hover'
,'menuG_bgcolor' => '--menuG-bgcolor'
,'menuG_color' => '--menuG-color'
,'cal_titrecolor' => '--cal-titrecolor'
,'cal_titrebgcolor' => '--cal-titrebgcolor'
,'cal_joursbgcolor' => '--cal-joursbgcolor'
,'cal_jourscolor' => '--cal-jourscolor'
,'cal_sembgcolor' => '--cal-sembgcolor'
,'cal_semcolor' => '--cal-semcolor'
,'cal_semhovercolor' => '--cal-semhovercolor'
,'cal_weekbgcolor' => '--cal-weekbgcolor'
,'cal_weekcolor' => '--cal-weekcolor'
,'cal_cellbgcolor' => '--cal-cellbgcolor'
,'cal_cellcolor' => '--cal-cellcolor'
,'cal_cellhoverbgcolor' => '--cal-cellhoverbgcolor'
,'cal_cellhovercolor' => '--cal-cellhovercolor'
,'cal_current_day_bgcolor' => '--cal-current-day-bg'
,'cal_current_day_color' => '--cal-current-day-col'
,'pl2_titrebgcolor' => '--pl2-titrebgcolor'
,'pl2_titrecolor' => '--pl2-titrecolor'
,'pl2_entetebgcolor' => '--pl2-entetebgcolor'
,'pl2_entetecolor' => '--pl2-entetecolor'
,'pl2_cellbgcolor' => '--pl2-cellbgcolor'
,'pl2_cellcolor' => '--pl2-cellcolor'
,'icons_color' => '--icons-color'
,'btn_primary_color' => '--btn-primary-color'
,'btn_primary_bgcolor' => '--btn-primary-bgcolor'
,'btn_primary_bordcolor' => '--btn-primary-bordcolor'
,'active_btn_primary_color' => '--active-btn-primary-color'
,'active_btn_primary_bgcolor' => '--active-btn-primary-bgcolor'
,'active_btn_primary_bordcolor' => '--active-btn-primary-bordcolor'
,'focus_btn_primary_bgcolor' => '--focus-btn-primary-bgcolor'
,'focus_btn_primary_bordcolor' => '--focus-btn-primary-bordcolor'
,'focus_btn_primary_color' => '--focus-btn-primary-color'
);
// NOTE: Pour JavaScript, on n'a pas le droit au '-' dans un nom de variable
// liste des couleurs en cours d'utilisation -reste à mettre au point
$current_color_tab=array();
// liste des couleurs par défaut, ce sont celles du modèle "default"
$default_color_tab=array('header_bgcolor' => '#FFF'
,'header_text' => '#337AB7'
,'header_hover' => '#23527C'
,'menuG_bgcolor' => '#FFF'
,'menuG_color' => '#333'
,'cal_titrecolor' => '#333'
,'cal_titrebgcolor' => '#FFF'
,'cal_joursbgcolor' => '#FFF'
,'cal_jourscolor' => '#333'
,'cal_sembgcolor' => '#FFF'
,'cal_semcolor' => '#337AB7'
,'cal_semhovercolor' => '#23527C'
,'cal_weekbgcolor' => '#FFF'
,'cal_weekcolor' => '#337AB7'
,'cal_cellbgcolor' => '#FFF'
,'cal_cellcolor' => '#337AB7'
,'cal_cellhoverbgcolor' => '#FFF'
,'cal_cellhovercolor' => '#23527C'
,'cal_current_day_bgcolor' => '#FFF'
,'cal_current_day_color' => '#000'
,'pl2_titrebgcolor' => 'FFF'
,'pl2_titrecolor' => '777'
,'pl2_entetebgcolor' => 'FFF'
,'pl2_entetecolor' => '337AB7'
,'pl2_cellbgcolor' => 'FFF'
,'pl2_cellcolor' => '337AB7'
,'icons_color' => '#333'
,'btn_primary_color' => '#FFF'
,'btn_primary_bgcolor' => '#337AB7'
,'btn_primary_bordcolor' => '#2E6DA4'
,'active_btn_primary_color' => '#FFF'
,'active_btn_primary_bgcolor' => '#2C6CA3'
,'active_btn_primary_bordcolor' => '#2E6DA4'
,'focus_btn_primary_bgcolor' => '#286090'
,'focus_btn_primary_bordcolor' => '#204D74'
,'focus_btn_primary_color' => '#FFF'
);

$msg = '';
if(isset($_GET['theme']) && $_GET['theme'] == 'defaut') // Reset CSS
{
	foreach($champs_couleur as $code_js => $code_css)
	{
		Settings::delette("sp_".$code_js);	
    }
	if(file_exists("../personnalisation/".$gcDossierCss."/perso.css"))
		unlink("../personnalisation/".$gcDossierCss."/perso.css");
}
elseif ((isset($_POST['record'])) && (!isset($ok))) // Enregistrement des données si enregistrement
{
    try {
        $fich=fopen("../personnalisation/".$gcDossierCss."/perso.css","w+"); // première écriture
        fwrite($fich,"/* personnalisations */");
        fclose($fich);
        $fich=fopen("../personnalisation/".$gcDossierCss."/perso.css","a+");
        fwrite($fich,":root{");
        foreach($champs_couleur as $code_js => $code_css)
		{
            $couleur = valid_color($_POST[$code_js]);
			Settings::set("sp_".$code_js, $couleur);
            fwrite($fich," 
    ".$code_css.": ".$couleur.";");
			
        }
        fwrite($fich,"}
");
        fclose($fich);
    }
    catch (Exception $e) {
        echo 'Exception reçue : ',  $e->getMessage(), "\n";
        $ok = 'no';
        die();
    }

    // Si pas de problème, message de confirmation
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
       // $msg = get_vocab('message_records');
    }
}

$AllSettings = Settings::getAll(); //Refresh

// couleurs pour le formulaire ; si on arrive ici sans avoir enregistré, on récupère les valeurs par défaut, définies dans le tableau $default_color_tab
$hexa['header_text'] = (isset($AllSettings['sp_header_text']))? valid_color($AllSettings['sp_header_text']) : $default_color_tab["header_text"];
$hexa['header_bgcolor'] = (isset($AllSettings['sp_header_bgcolor']))? valid_color($AllSettings['sp_header_bgcolor']) : $default_color_tab["header_bgcolor"];
$hexa['header_hover'] = (isset($AllSettings['sp_header_hover']))? valid_color($AllSettings['sp_header_hover']) : $default_color_tab["header_hover"];
$hexa['menuG_color'] = (isset($AllSettings['sp_menuG_color']))? valid_color($AllSettings['sp_menuG_color']) : $default_color_tab["menuG_color"];
$hexa['menuG_bgcolor'] = (isset($AllSettings['sp_menuG_bgcolor']))? valid_color($AllSettings['sp_menuG_bgcolor']) : $default_color_tab["menuG_bgcolor"];
$hexa['cal_titrecolor'] = (isset($AllSettings['sp_cal_titrecolor']))? valid_color($AllSettings['sp_cal_titrecolor']) : $default_color_tab["cal_titrecolor"];
$hexa['cal_titrebgcolor'] = (isset($AllSettings['sp_cal_titrebgcolor']))? valid_color($AllSettings['sp_cal_titrebgcolor']) : $default_color_tab["cal_titrebgcolor"];
$hexa['cal_jourscolor'] = (isset($AllSettings['sp_cal_jourscolor']))? valid_color($AllSettings['sp_cal_jourscolor']) : $default_color_tab["cal_jourscolor"];
$hexa['cal_joursbgcolor'] = (isset($AllSettings['sp_cal_joursbgcolor']))? valid_color($AllSettings['sp_cal_joursbgcolor']) : $default_color_tab["cal_joursbgcolor"];
$hexa['cal_semcolor'] = (isset($AllSettings['sp_cal_semcolor']))? valid_color($AllSettings['sp_cal_semcolor']) : $default_color_tab["cal_semcolor"];
$hexa['cal_sembgcolor'] = (isset($AllSettings['sp_cal_sembgcolor']))? valid_color($AllSettings['sp_cal_sembgcolor']) : $default_color_tab["cal_sembgcolor"];
$hexa['cal_semhovercolor'] = (isset($AllSettings['sp_cal_semhovercolor']))? valid_color($AllSettings['sp_cal_semhovercolor']) : $default_color_tab["cal_semhovercolor"];
$hexa['cal_weekcolor'] = (isset($AllSettings['sp_cal_weekcolor']))? valid_color($AllSettings['sp_cal_weekcolor']) : $default_color_tab["cal_weekcolor"];
$hexa['cal_weekbgcolor'] = (isset($AllSettings['sp_cal_weekbgcolor']))? valid_color($AllSettings['sp_cal_weekbgcolor']) : $default_color_tab["cal_weekbgcolor"];
$hexa['cal_cellcolor'] = (isset($AllSettings['sp_cal_cellcolor']))? valid_color($AllSettings['sp_cal_cellcolor']) : $default_color_tab["cal_cellcolor"];
$hexa['cal_cellbgcolor'] = (isset($AllSettings['sp_cal_cellbgcolor']))? valid_color($AllSettings['sp_cal_cellbgcolor']) : $default_color_tab["cal_cellbgcolor"];
$hexa['cal_cellhovercolor'] = (isset($AllSettings['sp_cal_cellhovercolor']))? valid_color($AllSettings['sp_cal_cellhovercolor']) : $default_color_tab["cal_cellhovercolor"];
$hexa['cal_cellhoverbgcolor'] = (isset($AllSettings['sp_cal_cellhoverbgcolor']))? valid_color($AllSettings['sp_cal_cellhoverbgcolor']) : $default_color_tab['cal_cellhoverbgcolor'];
$hexa['cal_current_day_color'] = (isset($AllSettings['sp_cal_current_day_color']))? valid_color($AllSettings['sp_cal_current_day_color']) : $default_color_tab["cal_current_day_color"];
$hexa['cal_current_day_bgcolor'] = (isset($AllSettings['sp_cal_current_day_bgcolor']))? valid_color($AllSettings['sp_cal_current_day_bgcolor']) : $default_color_tab["cal_current_day_bgcolor"];
$hexa['pl2_titrecolor'] = (isset($AllSettings['sp_pl2_titrecolor']))? valid_color($AllSettings['sp_pl2_titrecolor']) : $default_color_tab["pl2_titrecolor"];
$hexa['pl2_titrebgcolor'] = (isset($AllSettings['sp_pl2_titrebgcolor']))? valid_color($AllSettings['sp_pl2_titrebgcolor']) : $default_color_tab['pl2_titrebgcolor'];
$hexa['pl2_entetecolor'] = (isset($AllSettings['sp_pl2_entetecolor']))? valid_color($AllSettings['sp_pl2_entetecolor']) : $default_color_tab["pl2_entetecolor"];
$hexa['pl2_entetebgcolor'] = (isset($AllSettings['sp_pl2_entetebgcolor']))? valid_color($AllSettings['sp_pl2_entetebgcolor']) : $default_color_tab["pl2_entetebgcolor"];
$hexa['pl2_cellcolor'] = (isset($AllSettings['sp_pl2_cellcolor']))? valid_color($AllSettings['sp_pl2_cellcolor']) : $default_color_tab["pl2_cellcolor"];
$hexa['pl2_cellbgcolor'] = (isset($AllSettings['sp_pl2_cellbgcolor']))? valid_color($AllSettings['sp_pl2_cellbgcolor']) : $default_color_tab["pl2_cellbgcolor"];
$hexa['icons_color'] = (isset($AllSettings['sp_icons_color']))? valid_color($AllSettings['sp_icons_color']) : $default_color_tab["icons_color"];
$hexa['btn_primary_color'] = (isset($AllSettings['sp_btn_primary_color']))? valid_color($AllSettings['sp_btn_primary_color']) : $default_color_tab["btn_primary_color"];
$hexa['btn_primary_bgcolor'] = (isset($AllSettings['sp_btn_primary_bgcolor']))? valid_color($AllSettings['sp_btn_primary_bgcolor']) : $default_color_tab["btn_primary_bgcolor"];
$hexa['btn_primary_bordcolor'] = (isset($AllSettings['sp_btn_primary_bordcolor']))? valid_color($AllSettings['sp_btn_primary_bordcolor']) : $default_color_tab["btn_primary_bordcolor"];
$hexa['active_btn_primary_color'] = (isset($AllSettings['sp_active_btn_primary_color']))? valid_color($AllSettings['sp_active_btn_primary_color']) : $default_color_tab["active_btn_primary_color"];
$hexa['active_btn_primary_bgcolor'] = (isset($AllSettings['sp_active_btn_primary_bgcolor']))? valid_color($AllSettings['sp_active_btn_primary_bgcolor']) : $default_color_tab["active_btn_primary_bgcolor"];
$hexa['active_btn_primary_bordcolor'] = (isset($AllSettings['sp_active_btn_primary_bordcolor']))? valid_color($AllSettings['sp_active_btn_primary_bordcolor']) : $default_color_tab["active_btn_primary_bordcolor"];
$hexa['focus_btn_primary_color'] = (isset($AllSettings['sp_focus_btn_primary_color']))? valid_color($AllSettings['sp_focus_btn_primary_color']) : $default_color_tab["focus_btn_primary_color"];
$hexa['focus_btn_primary_bgcolor'] = (isset($AllSettings['sp_focus_btn_primary_bgcolor']))? valid_color($AllSettings['sp_focus_btn_primary_bgcolor']) : $default_color_tab["focus_btn_primary_bgcolor"];
$hexa['focus_btn_primary_bordcolor'] = (isset($AllSettings['sp_focus_btn_primary_bordcolor']))? valid_color($AllSettings['sp_focus_btn_primary_bordcolor']) : $default_color_tab["focus_btn_primary_bordcolor"];


//affiche_pop_up($msg,"admin");

echo $twig->render('admin_couleurs.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'hexa' => $hexa));

?>