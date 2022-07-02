<?php
/**
 * edit_entry.php
 * Interface d'édition d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:51$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "edit_entry.php"; 

ini_set('display_errors', 'On');
error_reporting(E_ALL);
include "include/admin.inc.php";
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id,"integer");
}
else
	$id = NULL;
$period = isset($_GET["period"]) ? $_GET["period"] : NULL;
if (isset($period))
	settype($period,"integer");
if (isset($period))
	$end_period = $period;
$edit_type = isset($_GET["edit_type"]) ? $_GET["edit_type"] : NULL;
if (!isset($edit_type))
	$edit_type = "";
$page = verif_page();
if (isset($_GET["hour"]))
{
	$hour = $_GET["hour"];
	settype($hour,"integer");
	if ($hour < 10) $hour = "0".$hour;
}
else
	$hour = NULL;
if (isset($_GET["minute"]))
{
	$minute = $_GET["minute"];
	settype($minute,"integer");
	if ($minute < 10)
		$minute = "0".$minute;
}
else
	$minute = NULL;
$rep_num_weeks = '';
$rep_month_abs1 = 0;
$rep_month_abs2 = 1;
global $twentyfourhour_format;
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if (isset($id))
{
	if ($info = mrbsGetEntryInfo($id))
	{
		$area  = mrbsGetRoomArea($info["room_id"]);
		$room = $info["room_id"];
	}
	else
	{
		$area = -1;
		$room = -1;
	}
}
else
	Definition_ressource_domaine_site();
$room_back = (isset($_GET['room_back']))? $_GET['room_back']: ((isset($_GET['room']))? $_GET['room'] :'all') ;
if (@file_exists("../personnalisation/langue/lang_subst_".$area."_".$locale.".php"))
	include "../personnalisation/langue/lang_subst_".$area."_".$locale.".php";
get_planning_area_values($area);
// $affiche_mess_asterisque = false;
$type_affichage_reser = grr_sql_query1("SELECT type_affichage_reser FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$delais_option_reservation  = grr_sql_query1("SELECT delais_option_reservation FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$active_cle  = grr_sql_query1("SELECT active_cle FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$active_participant  = grr_sql_query1("SELECT active_participant FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$periodiciteConfig = Settings::get("periodicite");
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars( $_SERVER['HTTP_REFERER']);
$longueur_liste_ressources_max = Settings::get("longueur_liste_ressources_max");
if ($longueur_liste_ressources_max == '')
	$longueur_liste_ressources_max = 20;
if (check_begin_end_bookings($day, $month, $year))
{
	/* if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
		$type_session = "no_session";
	else
		$type_session = "with_session"; me semble inutile ici (on est en dehors de la période réservable)*/
	showNoBookings($day, $month, $year, $back);
	exit();
}
if ((authGetUserLevel(getUserName(),-1) < 2) && (auth_visiteur(getUserName(),$room) == 0))
{
	showAccessDenied($back);
	exit();
}
if (authUserAccesArea(getUserName(), $area) == 0)
{
	showAccessDenied($back);
	exit();
}
if (isset($id) && ($id != 0))
	$compt = 0;
else
	$compt = 1;
if (UserRoomMaxBooking(getUserName(), $room, $compt) == 0)
{
	showAccessDeniedMaxBookings($day, $month, $year, $room, $back);
	exit();
}
$etype = 0;
if (isset($id)) // édition d'une réservation existante
{
	$sql = "SELECT name, beneficiaire, description, start_time, end_time, type, room_id, entry_type, repeat_id, option_reservation, jours, create_by, beneficiaire_ext, statut_entry, clef, courrier, nbparticipantmax FROM ".TABLE_PREFIX."_entry WHERE id=$id";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(1, grr_sql_error());
	if (grr_sql_count($res) != 1)
		fatal_error(1, get_vocab('entryid') . $id . get_vocab('not_found'));
	$row = grr_sql_row($res, 0);
	grr_sql_free($res);
	$breve_description = $row[0];
	$beneficiaire = $row[1];
	$beneficiaire_ext = $row[12];
	$tab_benef = donne_nom_email($beneficiaire_ext);
	$create_by = $row[11];
	$description = $row[2];
	$statut_entry = $row[13];
	$start_day = date('d', $row[3]);
	$start_month = date('m', $row[3]);
	$start_year = date('Y', $row[3]);
	$start_hour = date('H', $row[3]);
	$start_min = date('i', $row[3]);
	$end_day = date('d', $row[4]);
	$end_month = date('m', $row[4]);
	$end_year = date('Y', $row[4]);
	$end_hour = date('H', $row[4]);
	$end_min  = date('i', $row[4]);
	$duration = $row[4]-$row[3];
	$etype = $row[5];
	$room_id = $row[6];
	$entry_type = $row[7];
	$rep_id = $row[8];
	$option_reservation = $row[9];
	$jours_c = $row[10];
	$clef = $row[14];
	$courrier = $row[15];
	$nbparticipantmax = $row[16];
	$modif_option_reservation = 'n';

	if ($entry_type >= 1)
	{
		$sql = "SELECT rep_type, start_time, end_date, rep_opt, rep_num_weeks, end_time, type, name, beneficiaire, description
		FROM ".TABLE_PREFIX."_repeat WHERE id='".protect_data_sql($rep_id)."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(1, grr_sql_error());
		if (grr_sql_count($res) != 1)
			fatal_error(1, get_vocab('repeat_id') . $rep_id . get_vocab('not_found'));
		$row = grr_sql_row($res, 0);
		grr_sql_free($res);
		$rep_type = $row[0];
		if ($rep_type == 2) // périodidicté chaque semaine
			$rep_num_weeks = $row[4];
		if ($rep_type == 7) // périodidicté X Y du mois
		{
			$rep_month_abs1 = $row[4];
			$rep_month_abs2 = $row[3];
		}
		if ($edit_type == "series")
		{
			$start_day   = (int)date('d', $row[1]);
			$start_month = (int)date('m', $row[1]);
			$start_year  = (int)date('Y', $row[1]);
			$start_hour  = (int)strftime('%H', $row[1]);
			$start_min   = (int)strftime('%M', $row[1]);
			$duration    = $row[5]-$row[1];
			$end_day   = (int)date('d', $row[5]);
			$end_month = (int)date('m', $row[5]);
			$end_year  = (int)date('Y', $row[5]);
			$end_hour  = (int)strftime('%H', $row[5]);
			$end_min   = (int)strftime('%M', $row[5]);
			$rep_end_day   = (int)date('d', $row[2]);
			$rep_end_month = (int)date('m', $row[2]);
			$rep_end_year  = (int)date('Y', $row[2]);
			$type = $row[6];
			$breve_description = $row[7];
			$beneficiaire = $row[8];
			$description = $row[9];
			if ($rep_type==2)
			{
				$rep_day[0] = $row[3][0] != '0';
				$rep_day[1] = $row[3][1] != '0';
				$rep_day[2] = $row[3][2] != '0';
				$rep_day[3] = $row[3][3] != '0';
				$rep_day[4] = $row[3][4] != '0';
				$rep_day[5] = $row[3][5] != '0';
				$rep_day[6] = $row[3][6] != '0';
			}
			else
				$rep_day = array(0, 0, 0, 0, 0, 0, 0);
		}
		else
		{
			$rep_end_date = utf8_encode(strftime($dformat,$row[2]));
			$rep_opt      = $row[3];
			$start_time = $row[1];
			$end_time = $row[5];
		}
	}
	else
	{
		$flag_periodicite = 'y';
		$rep_id        = 0;
		$rep_type      = 0;
		$rep_end_day   = $day;
		$rep_end_month = $month;
		$rep_end_year  = $year;
		$rep_day       = array(0, 0, 0, 0, 0, 0, 0);
		$rep_jour      = 0;
	}
}
else // nouvelle réservation
{
	if ($enable_periods == 'y')
		$duration    = 60;
	else
	{
		$duree_par_defaut_reservation_area = grr_sql_query1("SELECT duree_par_defaut_reservation_area FROM ".TABLE_PREFIX."_area WHERE id='".$area."'");
		if ($duree_par_defaut_reservation_area == 0)
			$duree_par_defaut_reservation_area = $resolution;
		$duration = $duree_par_defaut_reservation_area ;
	}
	$edit_type   = "series";
	if (Settings::get("remplissage_description_breve") == '2')
		$breve_description = $_SESSION['prenom']." ".$_SESSION['nom'];
	else
		$breve_description = "";
	$beneficiaire   = getUserName();
	/*$tab_benef["nom"] = "";
	$tab_benef["email"] = "";*/
	$create_by    = getUserName();
	$description = "";
	$start_day   = $day;
	$start_month = $month;
	$start_year  = $year;
	$start_hour  = $hour;
	(isset($minute)) ? $start_min = $minute : $start_min ='00';
	if ($enable_periods == 'y')
	{
		$end_day   = $day;
		$end_month = $month;
		$end_year  = $year;
		$end_hour  = $hour;
		(isset($minute)) ? $end_min = $minute : $end_min ='00';
	}
	else
	{
		$now = mktime($hour, $minute, 0, $month, $day, $year);
		$fin = $now + $duree_par_defaut_reservation_area;
		$end_day   = date("d",$fin);
		$end_month = date("m",$fin);
		$end_year  = date("Y",$fin);
		$end_hour  = date("H",$fin);
		$end_min = date("i",$fin);
	}
	$type        	= "";
	$room_id     	= $room;
	$id				= 0;
	$rep_id        	= 0;
	$rep_type      	= 0;
	$rep_end_day   	= $day;
	$rep_end_month 	= $month;
	$rep_end_year  	= $year;
	$rep_day       	= array(0, 0, 0, 0, 0, 0, 0);
	$rep_jour      	= 0;
	$option_reservation = -1;
	$modif_option_reservation = 'y';
	$nbparticipantmax = 0;
}
if ( isset($_GET["Err"]))
	$Err = $_GET["Err"];
if ($enable_periods == 'y')
	toPeriodString($start_min, $duration, $dur_units);
else
	toTimeString($duration, $dur_units, true);
if (!getWritable($beneficiaire, getUserName(),$id))
{
	showAccessDenied($back);
	exit;
}
$nb_areas = 0;
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area";
$res = grr_sql_query($sql);
$allareas_id = array();
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		array_push($allareas_id, $row[0]);
		if (authUserAccesArea(getUserName(), $row[0])==1)
		{
			$nb_areas++;
		}
	}
}
$use_select2 = 'y';
$adm = 0;
$racine = "./";
$racineAd = "./admin/";
// pour le traitement des modules
include "./include/hook.class.php";
// début du code html ici, sinon le javascript provoque une erreur "header already sent by"
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
    setcookie("open", "true", time()+3600, "", "", false, false);
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
echo pageHead2(Settings::get("company"),$type_session="with_session");
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2($day, $month, $year, $type_session);
echo "</header>";
// Debut de la page
echo '<section>'.PHP_EOL;
?>
<script type="text/javascript" >
function insertBeneficiaires(area_,room_,user_){
	jQuery.ajax({
		type: 'GET',
		url: 'edit_entry_beneficiaire.php',
		data: {
			area: area_,
			room: room_,
            user: user_,
		},
		success: function(returnData)
		{
			$("#div_beneficiaire").html(returnData);
		},
		error: function(data)
		{
			alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_beneficiaire.php ');
		}
    });
}
function insertChampsAdd(areas_,id_,room_){
	jQuery.ajax({
		type: 'GET',
		url: 'edit_entry_champs_add.php',
		data: {
			areas: areas_,
			id: id_,
			room: room_,
		},
		success: function(returnData)
		{
			$("#div_champs_add").html(returnData);
		},
		error: function(data)
		{
			alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_champs_add.php ');
		}
    });
}
function insertTypes(areas_,room_){
    jQuery.ajax({
        type: 'GET',
        url: 'edit_entry_types.php',
        data: {
            areas: areas_,
            type: '<?php echo $etype; ?>',
            room: room_,
        },
        success: function(returnData){
            $('#div_types').html(returnData);
        },
        error: function(data){
            alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_types.php ');
        }
    });
}
/*function insertProfilBeneficiaire(){
    jQuery.ajax({
        type: 'GET',
        url: 'edit_entry_beneficiaire.php',
        data: {
            beneficiaire:'ADMINISTRATEUR',
            identifiant_beneficiaire: '<?php echo $beneficiaire; ?>'
        },
        success: function(returnData)

        {
            $("#div_profilBeneficiaire").html(returnData);
        },
        error: function(data)

        {
            alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_beneficiaire.php ');
        }
    });
}*/
function check_1(){
    menu = document.getElementById('menu2');
    if (menu)
    {
        if (!document.forms["main"].rep_type[2].checked)
        {
            document.forms["main"].elements['rep_day[0]'].checked=false;
            document.forms["main"].elements['rep_day[1]'].checked=false;
            document.forms["main"].elements['rep_day[2]'].checked=false;
            document.forms["main"].elements['rep_day[3]'].checked=false;
            document.forms["main"].elements['rep_day[4]'].checked=false;
            document.forms["main"].elements['rep_day[5]'].checked=false;
            document.forms["main"].elements['rep_day[6]'].checked=false;
            menu.style.display = "none";
        }
        else
        {
            menu.style.display = "";
        }
    }
    <?php
    if (Settings::get("jours_cycles_actif") == "Oui") {
        ?>
        menu = document.getElementById('menuP');
        if (menu)
        {
            if (!document.forms["main"].rep_type[5].checked)
            {
                menu.style.display = "none";
            }
            else
            {
                menu.style.display = "";
            }
        }
        <?php
    }
    ?>
}
function check_2 (){
    document.forms["main"].rep_type[2].checked=true;
    check_1 ();
}
function check_3 (){
    document.forms["main"].rep_type[3].checked=true;
}
function check_4 (){
    menu = document.getElementById('menu4');
    if (menu)
    {
        if (!document.forms["main"].beneficiaire.options[0].selected)
        //if (document.forms["main"].beneficiaire.value != '')
        {
            menu.style.display = "none";
            <?php
            if (Settings::get("remplissage_description_breve") == '2')
            {
                ?>
               document.forms["main"].name.value=document.forms["main"].beneficiaire.options[document.forms["main"].beneficiaire.options.selectedIndex].text;
               // document.forms["main"].name.value=document.forms["main"].beneficiaire.value;
                <?php
            }
            ?>
        }
        else
        {
            menu.style.display = "";
            <?php
            if (Settings::get("remplissage_description_breve") == '2')
            {
                ?>
                document.forms["main"].name.value="";
                <?php
            }
            ?>
        }
    }
}
function check_5 (){
    var menu; var menup; var menu2;
    menu = document.getElementById('menu1');
    menup = document.getElementById('menuP');
    menu2 = document.getElementById('menu2');
    if ((menu)&&(menu.style.display == "none"))
    {
        menup.style.display = "none";
        menu2.style.display = "none";
    }
    else
        check_1();
}
function setdefault (name,input){
    document.cookie = escape(name) + "=" + escape(input) +
    ( "" ? ";expires=" + ( new Date( ( new Date() ).getTime() + ( 1000 * lifeTime ) ) ).toGMTString() : "" ) +
    ( "" ? ";path=" + path : "") +
    ( "" ? ";domain=" + domain : "") +
    ( "" ? ";secure" : "");
}
function Load_entry (){
    recoverInputs(document.forms["main"],retrieveCookie('Grr_entry'),true);
    <?php
    if (!$id <> "")
    {
        ?>
        if (!document.forms["main"].rep_type[0].checked)
            clicMenu('1');
        <?php
    }
    ?>
}
function Save_entry (){
    setCookie('Grr_entry',getFormString(document.forms["main"],true));
}
function validate_and_submit (){
    var err;
    $("#error").html("");
    if (document.forms["main"].benef_ext_nom)
    {
        if ((document.forms["main"].beneficiaire.options[0].selected) &&(document.forms["main"].benef_ext_nom.value == ""))
        //if ((document.forms["main"].beneficiaire.value == "") &&(document.forms["main"].benef_ext_nom.value == ""))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").lcfirst(get_vocab("nom_beneficiaire")) ?></div>');
            err = 1;
        }
    }
    <?php if (Settings::get("remplissage_description_breve") == '1' || Settings::get("remplissage_description_breve") == '2')
    {
        ?>
        if (document.forms["main"].name.value == "")
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").get_vocab("brief_description") ?></div>');
            err = 1;
        }
        <?php
    }
     if (Settings::get("remplissage_description_complete") == '1')
    {
        ?>
        if (document.forms["main"].description.value == "")
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").get_vocab("fulldescription") ?></div>');
            err = 1;
        }
        <?php
    }
    foreach ($allareas_id as $idtmp)
    {
        $overload_fields = mrbsOverloadGetFieldslist($idtmp);
        foreach ($overload_fields as $fieldname=>$fieldtype)
        {
            if ($overload_fields[$fieldname]["obligatoire"] == 'y')
            {
                if ($overload_fields[$fieldname]["type"] != "list")
                {
                    echo "if ((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms[\"main\"].addon_".$overload_fields[$fieldname]["id"].".value == \"\")) {\n";
                }
                else
                {
                    echo "if ((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms[\"main\"].addon_".$overload_fields[$fieldname]["id"].".options[0].selected == true)) {\n";
                }
                ?>
					$("#error").append("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\"><span aria-hidden=\"true\">&times;</span><span class=\"sr-only\">Close</span></button><?php echo get_vocab('required'); ?></div>");
					err = 1;
				}
				<?php
			}
			if ($overload_fields[$fieldname]["type"] == "numeric")
			{
            ?>
				if (isNaN((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms['main'].addon_<?php echo $overload_fields[$fieldname]['id']?>.value))) 
                {
					$("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo addslashes($overload_fields[$fieldname]["name"]).get_vocab("deux_points"). get_vocab("is_not_numeric") ?></div>');
					err = 1;
				}
                <?php
            }
        }
    }
?>
    if  (document.forms["main"].type.value=='0')
    {
        $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("choose_a_type"); ?></div>');
        err = 1;
    }
    <?php
    if (($edit_type == "series") && ($periodiciteConfig == 'y'))
    {
        ?>
        i1 = parseInt(document.forms["main"].id.value);
        i2 = parseInt(document.forms["main"].rep_id.value);
        n = parseInt(document.forms["main"].rep_num_weeks.value);
        if ((document.forms["main"].elements['rep_day[0]'].checked || document.forms["main"].elements['rep_day[1]'].checked || document.forms["main"].elements['rep_day[2]'].checked || document.forms["main"].elements['rep_day[3]'].checked || document.forms["main"].elements['rep_day[4]'].checked || document.forms["main"].elements['rep_day[5]'].checked || document.forms["main"].elements['rep_day[6]'].checked) && (!document.forms["main"].rep_type[2].checked))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("no_compatibility_with_repeat_type"); ?></div>');
            err = 1;
        }
        if ((!document.forms["main"].elements['rep_day[0]'].checked && !document.forms["main"].elements['rep_day[1]'].checked && !document.forms["main"].elements['rep_day[2]'].checked && !document.forms["main"].elements['rep_day[3]'].checked && !document.forms["main"].elements['rep_day[4]'].checked && !document.forms["main"].elements['rep_day[5]'].checked && !document.forms["main"].elements['rep_day[6]'].checked) && (document.forms["main"].rep_type[2].checked))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("choose_a_day"); ?></div>');
            err = 1;
        }
        <?php
    }
    ?>
    if (err == 1)
        return false;
    document.forms["main"].submit();
    return true;
}
</script>
<?php
if ($id == 0)
	$A = get_vocab("addentry");
else
{
	if ($edit_type == "series")
		$A = get_vocab("editseries");
	else
	{
		if (isset($_GET["copier"]))
			$A = get_vocab("copyentry");
		else
			$A = get_vocab("editentry");
	}
}
$B = get_vocab("namebooker");
if (Settings::get("remplissage_description_breve") != '0')
{
	$B .= " *";
	//$affiche_mess_asterisque=true;
}
$B .= get_vocab("deux_points");
$C = htmlspecialchars($breve_description);
$D = get_vocab("fulldescription");
if (Settings::get("remplissage_description_complete") == '1')
{
	$D .= " *";
	//$affiche_mess_asterisque=true;
}
$D .= get_vocab("deux_points");
$E = htmlspecialchars ( $description );
$F = get_vocab("date").get_vocab("deux_points");
$sql = "SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id=$room_id";
$res = grr_sql_query($sql);
$row = grr_sql_row($res, 0);
$area_id = $row[0];
$moderate = grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_room WHERE id='".$room_id."'");
echo '<h2>'.$A.'</h2>'.PHP_EOL;
if ($moderate)
	echo '<h3><span class="texte_ress_moderee">'.$vocab["reservations_moderees"].'</span></h3>'.PHP_EOL;
echo '<form class="form-inline" id="main" action="edit_entry_handler.php" method="get">'.PHP_EOL;
?>
<script type="text/javascript" >
	function changeRooms( formObj )
	{
		areasObj = eval( "formObj.areas" );
		area = areasObj[areasObj.selectedIndex].value
		roomsObj = eval( "formObj.elements['rooms[]']" )
		l = roomsObj.length;
		for (i = l; i > 0; i-- )
		{
			roomsObj.options[i] = null
		}
		switch (area)
		{
			<?php
			if ($enable_periods == 'y')
				$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE id='".$area."' ORDER BY area_name";
			else
				$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE enable_periods != 'y' ORDER BY area_name";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					if (authUserAccesArea(getUserName(), $row[0]) == 1)
					{
						print "      case \"".$row[0]."\":\n";
						$sql2 = "SELECT id, room_name FROM ".TABLE_PREFIX."_room WHERE area_id='".$row[0]."'";
						$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
						foreach($tab_rooms_noaccess as $key)
						{
							$sql2 .= " AND id != $key ";
						}
						$sql2 .= " ORDER BY room_name";
						$res2 = grr_sql_query($sql2);
						if ($res2)
						{
							$len = grr_sql_count($res2);
							print "roomsObj.size=".min($longueur_liste_ressources_max,$len).";\n";
							for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
                            {
                                print "roomsObj.options[$j] = new Option(\"".str_replace('"','\\"',$row2[1])."\",".$row2[0] .")\n";
/*                                 if (($j == 0)&&($row[0] == 4))
                                    $room = $row2[0]; */
                            }
							print "roomsObj.options[0].selected = true\n";
						}
						print "break\n";
					}
				}
			}
			?>
		}
        roomsObj = eval( "formObj.elements['rooms[]']" );
        room = roomsObj[roomsObj.selectedIndex].value;
        insertBeneficiaires(area,room,<?php echo json_encode(getUserName())?>);
        insertChampsAdd(area,<?php echo $id;?>,room);
        insertTypes(area,room);
        //insertProfilBeneficiaire();
	}
    function changeRoom( formObj)
    {	
        areasObj = eval( "formObj.areas" );
		area = areasObj[areasObj.selectedIndex].value
        roomsObj = eval("formObj.elements['rooms[]']");
        room = roomsObj[roomsObj.selectedIndex].value;
        //insertBeneficiaires(area,room,<?php echo json_encode(getUserName())?>);
        //insertChampsAdd(area,<?php echo $id;?>,room);
        //insertTypes(area,room);
        
    }
</script>

<?php
echo '<input type="hidden" name="oldRessource" value="'.$room_id.'">'.PHP_EOL;
echo '<div id="error"></div>';
//echo '<table class="table-bordered EditEntryTable"><tr>'.PHP_EOL;
echo '<div class="row2">';
echo '<div class="col-sm-6 col-xs-12">';
//echo '<td style="width:50%; vertical-align:top; padding-left:15px; padding-top:5px; padding-bottom:5px;">'.PHP_EOL;

echo '<table>'.PHP_EOL;
// bloc choix du bénéficiaire
echo '<div id="div_beneficiaire"></div>';

echo '<tr><td class="E">'.PHP_EOL;
echo '<b>'.$B.'</b>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td class="CL">'.PHP_EOL;
echo '<input id="name" class="pleine form-control" name="name" maxlength="80" size="60" value="'.$C.'" />'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td class="E">'.PHP_EOL;
echo '<b>'.$D.'</b>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td class="TL">'.PHP_EOL;
echo '<textarea name="description" class="pleine form-control" rows="4" columns="60" >'.$E.'</textarea>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td>'.PHP_EOL;
echo '<div id="div_champs_add">'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;

if($active_participant > 0){
	echo '<tr><td class="E"><br>'.PHP_EOL;
	echo '<b>'.get_vocab("nb_participant_max").get_vocab("deux_points").'</b>'.PHP_EOL;
	echo '<input name="nbparticipantmax" type="number" value="'.$nbparticipantmax.'" > '.get_vocab("nb_participant_zero");
	echo '</td></tr>'.PHP_EOL;
}

if($active_cle == 'y'){
	echo '<tr><td class="E"><br>'.PHP_EOL;
	echo '<b>'.get_vocab("status_clef").get_vocab("deux_points").'</b>'.PHP_EOL;
	echo '</td></tr>'.PHP_EOL;
	echo '<tr><td class="CL">'.PHP_EOL;
	echo '<input name="keys" type="checkbox" value="y" ';
	if (isset($clef) && $clef == 1)
		echo 'checked';
	echo ' > '.get_vocab("msg_clef");
	echo '</td></tr>'.PHP_EOL;
}

if (Settings::get("show_courrier") == 'y'){ // proposition scoubinaire le 12/03/2018
echo '<tr><td class="E"><br>'.PHP_EOL;
echo '<b>'.get_vocab("status_courrier").get_vocab("deux_points").'</b>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td class="CL">'.PHP_EOL;
echo '<input name="courrier" type="checkbox" value="y" ';
if (isset($courrier) && $courrier == 1)
	echo 'checked';
echo ' > '.get_vocab("msg_courrier");
echo '</td></tr>'.PHP_EOL;
}

echo '<tr><td class="E">'.PHP_EOL;
echo '<b>'.$F.'</b>'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td class="CL">'.PHP_EOL;

echo '<div class="form-group">'.PHP_EOL;
jQuery_DatePicker('start');

if ($enable_periods == 'y')
{

	echo '<b>'.get_vocab("period").'</b>'.PHP_EOL;

	echo '<select name="period">'.PHP_EOL;
	foreach ($periods_name as $p_num => $p_val)
	{
		echo '<option value="'.$p_num.'"';
		if ((isset( $period ) && $period == $p_num ) || $p_num == $start_min)
			echo ' selected="selected"';
		echo '>'.$p_val.'</option>'.PHP_EOL;
	}
	echo '</select>'.PHP_EOL;
}
else
{
	echo "<b>".get_vocab("time")." : </b>";
	if (isset ($_GET['id']))
	{
		$duree_par_defaut_reservation_area = $duration;
		jQuery_TimePicker('start_', $start_hour, $start_min,$duree_par_defaut_reservation_area);
	}
	else
	{
		jQuery_TimePicker('start_', '', '',$duree_par_defaut_reservation_area);
	}
	if (!$twentyfourhour_format)
	{
		$checked = ($start_hour < 12) ? 'checked="checked"' : "";
		echo '<input name="ampm" type="radio" value="am" '.$checked.' />'.date("a",mktime(1,0,0,1,1,1970));
		$checked = ($start_hour >= 12) ? 'checked="checked"' : "";
		echo '<input name="ampm" type="radio" value="pm" '.$checked.' />'.date("a",mktime(13,0,0,1,1,1970));
	}

}
echo '</div>'.PHP_EOL;
echo "</td></tr>".PHP_EOL;
if ($type_affichage_reser == 0) // sélection de la durée
{
	echo '<tr><td class="E">'.PHP_EOL;
	echo '<b>'.get_vocab("duration").'</b>'.PHP_EOL;
	echo '</td></tr>'.PHP_EOL;
	echo '<tr><td class="CL">'.PHP_EOL;
	// echo '<div class="form-group">'.PHP_EOL;
    echo '<div>'.PHP_EOL;
	// spinner($duration);
    echo '<input class="form-control" id="duree" name="duration" type="number" value="'.$duration.'" min="1">'; 
    // echo '<div class="col-xs-3">'.PHP_EOL;
	echo '<select class="form-control" name="dur_units">'.PHP_EOL;
    // echo '<select class="form-control" name="dur_units" size="0.5">'.PHP_EOL;
    // echo '<select name="dur_units" >'.PHP_EOL;
	if ($enable_periods == 'y')
		$units = array("periods", "days");
	else
	{
		$duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area FROM ".TABLE_PREFIX."_area WHERE id='".$area."'");
		if ($duree_max_resa_area < 0)
			$units = array("minutes", "hours", "days", "weeks");
		else if ($duree_max_resa_area < 60)
			$units = array("minutes");
		else if ($duree_max_resa_area < 60*24)
			$units = array("minutes", "hours");
		else if ($duree_max_resa_area < 60*24*7)
			$units = array("minutes", "hours", "days");
		else
			$units = array("minutes", "hours", "days", "weeks");
	}
	// while (list(,$unit) = each($units)) deprecated in php 7.2.0
    foreach($units as $unit)
	{
		echo '<option value="'.$unit.'"';
		if ($dur_units ==  get_vocab($unit))
			echo ' selected="selected"';
		echo '>'.get_vocab($unit).'</option>'.PHP_EOL;
	}
	echo '</select>'.PHP_EOL;
    echo "</div>";

	/*$fin_jour = $eveningends;
	$minute = $resolution / 60;
	$minute_restante = $minute % 60;
	$heure_ajout = ($minute - $minute_restante)/60;
	if ($minute_restante < 10)
		$minute_restante = "0".$minute_restante;
	$heure_finale = round($fin_jour+$heure_ajout,0);
	if ($heure_finale > 24)
	{
		$heure_finale_restante = $heure_finale % 24;
		$nb_jour = ($heure_finale - $heure_finale_restante) / 24;
		$heure_finale = $nb_jour. " ". $vocab["days"]. " + ". $heure_finale_restante;
	}
	$af_fin_jour = $heure_finale." H ".$minute_restante;*/
	// l'heure de fin du jour est définie par eveningends et eveningends_minutes
	// on suppose les données vérifiées : eveningends:eveningends_minutes <= 24:00
	$af_fin_jour = $eveningends." H ".substr("0".$eveningends_minutes,-2,2);
	echo '<b>
          <input name="all_day" type="checkbox" value="yes" />'.get_vocab("all_day");
	if ($enable_periods != 'y')
		echo ' ('.$morningstarts.' H - '.$af_fin_jour.')';
        echo '</b>'.PHP_EOL;
	echo '</td></tr>'.PHP_EOL;
}
else // sélection de l'heure ou du créneau de fin
{
	echo '<tr><td class="E"><b>'.get_vocab("fin_reservation").get_vocab("deux_points").'</b></td></tr>'.PHP_EOL;
	echo '<tr><td class="CL" >'.PHP_EOL;

	echo '<div class="form-group">'.PHP_EOL;
	jQuery_DatePicker('end');

	if ($enable_periods=='y')
	{
		echo "<b>".get_vocab("period")."</b>".PHP_EOL;
		// echo "<td class=\"CL\">\n"; à supprimer : balises mal équilibrées (YN le 20/11/2017)
		// echo "<select class=\"form-control\" name=\"end_period\">";
        echo "<select name=\"end_period\">";  // le style semble poser pb car non homogène avec celui du créneau début
		foreach ($periods_name as $p_num => $p_val)
		{
			echo "<option value=\"".$p_num."\"";
			if ( ( isset( $end_period ) && $end_period == $p_num ) || ($p_num+1) == $end_min)
				echo " selected=\"selected\"";
			echo ">$p_val</option>\n";
		}
		echo '</select>'.PHP_EOL;
	}
	else
	{
		echo "<b>".get_vocab("time")." : </b>";
		if (isset ($_GET['id']))
		{
			jQuery_TimePicker('end_', $end_hour, $end_min,$duree_par_defaut_reservation_area);
		}
		else
		{
			jQuery_TimePicker('end_', '', '',$duree_par_defaut_reservation_area);
		}
		if (!$twentyfourhour_format)
		{
			$checked = ($end_hour < 12) ? "checked=\"checked\"" : "";
			echo "<input name=\"ampm\" type=\"radio\" value=\"am\" $checked />".date("a",mktime(1,0,0,1,1,1970));
			$checked = ($end_hour >= 12) ? "checked=\"checked\"" : "";
			echo "<input name=\"ampm\" type=\"radio\" value=\"pm\" $checked />".date("a",mktime(13,0,0,1,1,1970));
		}
	}
	echo '</div>'.PHP_EOL;
	echo '</td></tr>'.PHP_EOL;
}
if (($delais_option_reservation > 0) && (($modif_option_reservation == 'y') || ((($modif_option_reservation == 'n') && ($option_reservation != -1)))))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	echo '<tr><td class="E"><br><div class="col-xs-12"><div class="alert alert-danger" role="alert"><b>'.get_vocab("reservation_a_confirmer_au_plus_tard_le").'</div>'.PHP_EOL;
	if ($modif_option_reservation == 'y')
	{
		echo '<select class="form-control" name="option_reservation" size="1">'.PHP_EOL;
		$k = 0;
		$selected = 'n';
		$aff_options = "";
		while ($k < $delais_option_reservation + 1)
		{
			$day_courant = $day + $k;
			$date_courante = mktime(0, 0, 0, $month, $day_courant,$year);
			$aff_date_courante = time_date_string_jma($date_courante,$dformat);
			$aff_options .= "<option value = \"".$date_courante."\" ";
			if ($option_reservation == $date_courante)
			{
				$aff_options .= " selected=\"selected\" ";
				$selected = 'y';
			}
			$aff_options .= ">".$aff_date_courante."</option>\n";
			$k++;
		}
		echo "<option value = \"-1\">".get_vocab("reservation_confirmee")."</option>\n";
		if (($selected == 'n') and ($option_reservation != -1))
		{
			echo "<option value = \"".$option_reservation."\" selected=\"selected\">".time_date_string_jma($option_reservation, $dformat)."</option>\n";
		}
		echo $aff_options;
		echo "</select>";
	}
	else
	{
		echo "<input type=\"hidden\" name=\"option_reservation\" value=\"".$option_reservation."\" /> <b>".
		time_date_string_jma($option_reservation,$dformat)."</b>\n";
		echo "<br /><input type=\"checkbox\" name=\"confirm_reservation\" value=\"y\" />".get_vocab("confirmer_reservation")."\n";
	}
	echo '<br /><div class="alert alert-danger" role="alert">'.get_vocab("avertissement_reservation_a_confirmer").'</b></div>'.PHP_EOL;
	echo "</div></td></tr>\n";
}
echo "<tr ";
if ($nb_areas == 1)
	echo "style=\"display:none\" ";
echo "><td class=\"E\"><b>".get_vocab("match_area").get_vocab("deux_points")."</b></td></tr>\n";
echo "<tr ";
if ($nb_areas == 1)
	echo "style=\"display:none\" ";
echo "><td class=\"CL\" >\n";
echo "<div class=\"col-xs-3\"><select class=\"form-control\" id=\"areas\" name=\"areas\" onchange=\"changeRooms(this.form);\" >";
if ($enable_periods == 'y')
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE id='".$area."' ORDER BY area_name";
else
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE enable_periods != 'y' ORDER BY area_name";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (authUserAccesArea(getUserName(),$row[0]) == 1)
		{
			$selected = "";
			if ($row[0] == $area)
				$selected = 'selected="selected"';
			print '<option '.$selected.' value="'.$row[0].'">'.$row[1].'</option>'.PHP_EOL;
		}
	}
}
echo '</select>',PHP_EOL,'</div>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL;

echo '<!-- ************* Ressources edition ***************** -->',PHP_EOL;
echo "<tr><td class=\"E\"><b>".get_vocab("rooms").get_vocab("deux_points")."</b></td></tr>\n";
$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$area_id ";
$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
foreach ($tab_rooms_noaccess as $key)
{
	$sql .= " and id != $key ";
}
$sql .= " ORDER BY order_display,room_name";
$res = grr_sql_query($sql);
$len = grr_sql_count($res);
//Sélection de la "room" dans l'"area"
echo "<tr><td class=\"CL\" style=\"vertical-align:top;\"><table border=\"0\"><tr><td><select name=\"rooms[]\" size=\"".min($longueur_liste_ressources_max,$len)."\" multiple=\"multiple\" onchange=\"changeRoom(this.form) ;\">";
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$selected = "";
		if ($row[0] == $room_id)
			$selected = 'selected="selected"';
		echo '<option ',$selected,' value="',$row[0],'">',$row[1],'</option>',PHP_EOL;
	}
}
echo '</select>',PHP_EOL,'</td>',PHP_EOL,'<td>','&nbsp; &nbsp;',get_vocab("ctrl_click"),'</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL;
echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
// types
echo '<tr>',PHP_EOL,'<td>',PHP_EOL,'<div id="div_types">',PHP_EOL;
echo '</div>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL;
?>
<script type="text/javascript" >
    insertBeneficiaires(<?php echo $area?>,<?php echo $room?>,<?php echo json_encode(getUserName());?>);
	insertChampsAdd(<?php echo $area?>,<?php echo $id ?>,<?php echo $room?>);
	insertTypes(<?php echo $area?>,<?php echo $room?>);
	// insertProfilBeneficiaire();
</script>
<?php
echo '<tr>',PHP_EOL,'<td class="E">',PHP_EOL;
// if ($affiche_mess_asterisque)
	echo get_vocab("required");
echo '</td></tr>',PHP_EOL;
echo '</table>',PHP_EOL;
echo "</div>"; 
// fin du bloc de "gauche"
echo "<div class='col-sm-6 col-xs-12'>";
echo '<table class="table-header">',PHP_EOL;
$sql = "SELECT id FROM ".TABLE_PREFIX."_area;";
$res = grr_sql_query($sql);
echo '<!-- ************* Periodic edition ***************** -->',PHP_EOL;
$weeklist = array("unused","every_week","week_1_of_2","week_1_of_3","week_1_of_4","week_1_of_5");
$monthlist = array("firstofmonth","secondofmonth","thirdofmonth","fouthofmonth","fiveofmonth","lastofmonth");
if($periodiciteConfig == 'y'){
	if ( ($edit_type == "series") || (isset($flag_periodicite)))
	{
		/* echo '<tr>',PHP_EOL,
			'<td id="ouvrir" style="cursor: inherit" align="center" class="fontcolor4">',PHP_EOL,
				'<span class="fontcolor1 btn btn-primary"><b><a href="javascript:clicMenu(1);check_5()">',get_vocab("click_here_for_series_open"),'</a></b></span>',PHP_EOL,
			'</td>',PHP_EOL,
			'</tr>',PHP_EOL,
			'<tr>',PHP_EOL,
				'<td style="display:none; cursor: inherit white" id="fermer" align="center" class="fontcolor4">',PHP_EOL,
					'<span class="btn btn-primary fontcolor1 white"><b><a href="javascript:clicMenu(1);check_5()">',get_vocab("click_here_for_series_close"),'</a></b></span>',PHP_EOL,
				'</td>',PHP_EOL,
			'</tr>',PHP_EOL; */
        echo '<tr>',PHP_EOL,
			'<td id="ouvrir" class="CC">',PHP_EOL,
                '<input type="button" class="btn btn-primary" value="',get_vocab("click_here_for_series_open"),'" onclick="clicMenu(1);check_5();" />',PHP_EOL,
			'</td>',PHP_EOL,
			'</tr>',PHP_EOL,
			'<tr>',PHP_EOL,
				'<td style="display:none" id="fermer" class="CC">',PHP_EOL,
					'<input type="button" class="btn btn-primary" value="',get_vocab("click_here_for_series_close"),'" onclick="clicMenu(1);check_5();" />',PHP_EOL,
				'</td>',PHP_EOL,
			'</tr>',PHP_EOL;
		echo '<tr>',PHP_EOL,
				'<td>',PHP_EOL,'<table id="menu1" style="display:none;">',PHP_EOL,'<tr>',PHP_EOL,
		'<td class="F"><b>',get_vocab("rep_type"),'</b></td>',PHP_EOL,'</tr>',PHP_EOL,'<tr>',PHP_EOL,'<td class="CL">',PHP_EOL;
		echo '<table class="table" >',PHP_EOL;
		if (Settings::get("jours_cycles_actif") == "Oui")
			$max = 8;
		else
			$max = 7;
		for ($i = 0; $i < $max ; $i++)
		{
			if ($i == 6 && Settings::get("jours_cycles_actif") == "Non")
				$i++;
			if ($i != 5)
			{
				echo '<tr>',PHP_EOL,'<td>',PHP_EOL,'<input name="rep_type" type="radio" value="',$i,'"';
				if ($i == $rep_type)
					echo ' checked="checked"';
				if (($i == 3) && ($rep_type == 5))
					echo ' checked="checked"';
				echo ' onclick="check_1()" />',PHP_EOL,'</td>',PHP_EOL,'<td>',PHP_EOL;
				if (($i != 2) && ($i != 3))
					echo get_vocab("rep_type_$i");

				echo PHP_EOL;
				if ($i == '2')
				{
					echo '<select class="form-control" name="rep_num_weeks" size="1" onfocus="check_2()" onclick="check_2()">',PHP_EOL;
					echo '<option value="1" >',get_vocab("every_week"),'</option>',PHP_EOL;
					for ($weekit = 2; $weekit < 6; $weekit++)
					{
						echo '<option value="',$weekit,'"';
						if ($rep_num_weeks == $weekit)
							echo ' selected="selected"';
						echo '>',get_vocab($weeklist[$weekit]),'</option>',PHP_EOL;
					}
					echo '</select>',PHP_EOL;
				}
				if ($i == '3')
				{
					$monthrep3 = "";
					$monthrep5 = "";
					if ($rep_type == 3)
						$monthrep3 = " selected=\"selected\" ";
					if ($rep_type == 5)
						$monthrep5 = " selected=\"selected\" ";
					echo '<select class="form-control" name="rep_month" size="1" onfocus="check_3()" onclick="check_3()">'.PHP_EOL;
					echo "<option value=\"3\" $monthrep3>".get_vocab("rep_type_3")."</option>\n";
					echo "<option value=\"5\" $monthrep5>".get_vocab("rep_type_5")."</option>\n";
					echo "</select>\n";
				}
				if ($i == '7')
				{
					echo '<select class="form-control" name="rep_month_abs1" size="1" onfocus="check_7()" onclick="check_7()">'.PHP_EOL;
					for ($weekit = 0; $weekit < 6; $weekit++)
					{
						echo "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs1) echo " selected='selected' ";
						echo ">".get_vocab($monthlist[$weekit])."</option>\n";
					}
					echo '</select>'.PHP_EOL;
					echo '<select class="form-control" name="rep_month_abs2" size="1" onfocus="check_8()" onclick="check_8()">'.PHP_EOL;
					for ($weekit = 1; $weekit < 8; $weekit++)
					{
						echo "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs2) echo " selected='selected' ";
						echo ">".day_name($weekit)."</option>\n";
					}
					echo "</select>\n";
					echo get_vocab("ofmonth");
				}
				echo "</td></tr>\n";
			}
		}
		echo "</table>\n\n";
		echo "<!-- ***** Fin de périodidité ***** -->\n";
		echo "</td></tr>";
		echo "<tr><td class=\"F\"><b>".get_vocab("rep_end_date")."</b></td></tr>\n";
		echo "<tr><td class=\"CL\">";
		jQuery_DatePicker('rep_end');
		echo "</td></tr></table>\n";
		echo "<table style=\"display:none\" id=\"menu2\" width=\"100%\">\n";
		echo "<tr><td class=\"F\"><b>".get_vocab("rep_rep_day")."</b></td></tr>\n";
		echo "<tr><td class=\"CL\">";
		for ($i = 0; $i < 7; $i++)
		{
			$wday = ($i + $weekstarts) % 7;
			echo "<input name=\"rep_day[$wday]\" type=\"checkbox\"";
			if ($rep_day[$wday])
				echo " checked=\"checked\"";
			echo " onclick=\"check_1()\" />" . day_name($wday) . "\n";
		}
		echo "</td></tr>\n</table>\n";
		echo "<table style=\"display:none\" id=\"menuP\" width=\"100%\">\n";
		echo "<tr><td class=\"F\"><b>Jours/Cycle</b></td></tr>\n";
		echo "<tr><td class=\"CL\">";
		for ($i = 1; $i < (Settings::get("nombre_jours_Jours/Cycles") + 1); $i++)
		{
			$wday = $i;
			echo "<input type=\"radio\" name=\"rep_jour_\" value=\"$wday\"";
			if (isset($jours_c))
			{
				if ($i == $jours_c)
					echo ' checked="checked"';
			}
			echo ' onclick="check_1()" />',get_vocab("rep_type_6"),' ',$wday,PHP_EOL;
		}
		echo '</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL;
	}
	else
	{
		echo "<tr><td class=\"E\"><b>".get_vocab('periodicite_associe').get_vocab('deux_points')."</b></td></tr>\n";
		if ($rep_type == 2)
			$affiche_period = get_vocab($weeklist[$rep_num_weeks]);
		else
			$affiche_period = get_vocab('rep_type_'.$rep_type);
		echo '<tr><td class="E"><b>'.get_vocab('rep_type').'</b> '.$affiche_period.'</td></tr>'."\n";
		if ($rep_type != 0)
		{
			$opt = '';
			if ($rep_type == 2)
			{
				$nb = 0;
				for ($i = 0; $i < 7; $i++)
				{
					$wday = ($i + $weekstarts) % 7;
					if ($rep_opt[$wday])
					{
						if ($opt != '')
							$opt .=', ';
						$opt .= day_name($wday);
						$nb++;
					}
				}
			}
			if ($rep_type == 6)
			{
				$nb = 1;
				$opt .= get_vocab('jour_cycle').' '.$jours_c;
			}
			if ($opt)
				if ($nb == 1)
					echo '<tr><td class="E"><b>'.get_vocab('rep_rep_day').'</b> '.$opt.'</td></tr>'."\n";
				else
					echo '<tr><td class="E"><b>'.get_vocab('rep_rep_days').'</b> '.$opt.'</td></tr>'."\n";
				if ($enable_periods=='y') list( $start_period, $start_date) =  period_date_string($start_time);
				else $start_date = time_date_string($start_time,$dformat);
				$duration = $end_time - $start_time;
				if ($enable_periods=='y') toPeriodString($start_period, $duration, $dur_units);
				else toTimeString($duration, $dur_units, true);
				echo '<tr><td class="E"><b>'.get_vocab("date").get_vocab("deux_points").'</b> '.$start_date.'</td></tr>'."\n";
				echo '<tr><td class="E"><b>'.get_vocab("duration").'</b> '.$duration .' '. $dur_units.'</td></tr>'."\n";
				echo '<tr><td class="E"><b>'.get_vocab('rep_end_date').'</b> '.$rep_end_date.'</td></tr>'."\n";
			}
	}
}
	echo '</table>',PHP_EOL;
    echo "</div> </div>"; // fin colonne de "droite" et du bloc de réservation

	echo '<div id="fixe">';
        // définit l'adresse de retour, à passer à edit_entry_handler et à cancel
        // $ret_page = ($back) ?: $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area."&amp;room=".$room; 
        // $ret_page = $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area."&amp;room=".$room; // robuste ? YN le 20/03/2018
        $ret_page = $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area;
        if ((!strpos($page,"all"))&&($room_back != 'all')){
            $ret_page .= "&amp;room=".$room_back;
        }
?>
		<input type="button" class="btn btn-primary" value="<?php echo get_vocab("cancel")?>" onclick="window.location.href='<?php echo $ret_page?>'" />
		<input type="button" class="btn btn-primary" value="<?php echo get_vocab("save")?>" onclick="Save_entry();validate_and_submit();" />
		<input type="hidden" name="rep_id"    value="<?php echo $rep_id?>" />
		<input type="hidden" name="edit_type" value="<?php echo $edit_type?>" />
		<input type="hidden" name="page" value="<?php echo $page?>" />
		<input type="hidden" name="room_back" value="<?php echo $room_back?>" />
		<input type="hidden" name="page_ret" value="<?php echo $ret_page?>" />
<?php
		if (!isset($statut_entry))
			$statut_entry = "-";
		echo '<input type="hidden" name="statut_entry" value="'.$statut_entry.'" />'.PHP_EOL;
		echo '<input type="hidden" name="create_by" value="'.$create_by.'" />'.PHP_EOL;
		if ($id!=0)
			if (isset($_GET["copier"]))
				$id = NULL;
			else
				echo '<input type="hidden" name="id" value="'.$id.'" />'.PHP_EOL;
			echo '<input type="hidden" name="type_affichage_reser" value="'.$type_affichage_reser.'" />'.PHP_EOL;
?>
		</div>
	</form>
	<script type="text/javascript">
		insertBeneficiaires(<?php echo $area;?>,<?php echo $room;?>,<?php echo json_encode(getUserName());?>);
		insertChampsAdd(<?php echo $area; ?>,<?php echo $id; ?>,<?php echo $room; ?>);
		insertTypes(<?php echo $area; ?>,<?php echo $room; ?>)
	</script>
	<script type="text/javascript">
		$('#areas').on('change', function(){
			$('.multiselect').multiselect('destroy');
			$('.multiselect').multiselect();
		});
		$(document).ready(function() {
			$('.multiselect').multiselect();
			$("#select2").select2();
		});
	</script>
	<script type="text/javascript" >
		document.getElementById('main').name.focus();
		<?php
		if (isset($cookie) && $cookie)
			echo "check_4();";
		if (($id <> "") && (!isset($flag_periodicite)))
			echo "clicMenu('1'); check_5();\n";
		if (isset($Err) && $Err == "yes")
			echo "timeoutID = window.setTimeout(\"Load_entry();check_5();\",500);\n";
		?>
	</script>
</section>
</body>
</html>
