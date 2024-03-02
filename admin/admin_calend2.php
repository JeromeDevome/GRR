<?php
/**
 * admin_calend2.php
 * interface permettant la réservation en bloc d'un créneau sur plusieurs Jours ou ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-03-02 12:12$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_calend2.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(4, $back);
$user_name = getUserName();
// Initialisation
$etape = isset($_POST["etape"]) ? $_POST["etape"] : NULL;
$areas = isset($_POST["areas"]) ? $_POST["areas"] : NULL;
$rooms = isset($_POST["rooms"]) ? $_POST["rooms"] : NULL;
$name = isset($_POST["name"]) ? clean_input($_POST["name"]) : NULL;
$beneficiaire = isset($_POST["beneficiaire"]) ? clean_input($_POST["beneficiaire"]) : NULL;
$description = isset($_POST["description"]) ? clean_input($_POST["description"]) : NULL;
$type_ = isset($_POST["type_"]) ? clean_input($_POST["type_"]) : NULL;
$type_resa = isset($_POST["type_resa"]) ? clean_input($_POST["type_resa"]) : NULL;
$hour = isset($_POST["hour"]) ? intval($_POST["hour"]) : NULL;
$end_hour = isset($_POST["end_hour"]) ? intval($_POST["end_hour"]) : NULL;
$minute = isset($_POST["minute"]) ? intval($_POST["minute"]) : NULL;
$end_minute = isset($_POST["end_minute"]) ? intval($_POST["end_minute"]) : NULL;
$period = isset($_POST["period"]) ? $_POST["period"] : NULL;
$end_period = isset($_POST["end_period"]) ? $_POST["end_period"] : NULL;
$all_day = isset($_POST["all_day"]) ? $_POST["all_day"] : NULL;
// enregistrement/suppression des réservations
$result = 0;
if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
	$etape = 4;
	$end_bookings = Settings::get("end_bookings");
	// On reconstitue le tableau des ressources
	$sql = "SELECT id, area_id, id_site FROM ".TABLE_PREFIX."_room JOIN ".TABLE_PREFIX."_j_site_area ON area_id = id_area ";
	$res = grr_sql_query($sql);
	if ($res)
	{
		foreach($res as $row)
		{
			$temp = "id_room_".$row['id'];
			if ((isset($_POST[$temp])) && verif_acces_ressource($user_name,$row['id']))
			{
			// La ressource est selectionnée
			// $rooms[] = $id;
			// On récupère les données du domaine
				//$area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id = '".$row[0]."'");
				//$id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area WHERE id_area = '".$area_id."'");
				//if (authGetUserLevel($user_name,$id_site,'site') >= 5)
				if (1)
				{
					get_planning_area_values($row['area_id']);
					$n = Settings::get("begin_bookings");
					$month = date("m", Settings::get("begin_bookings"));
					$year = date("Y", Settings::get("begin_bookings"));
					$day = 1;
					while ($n <= $end_bookings)
					{
						$daysInMonth = getDaysInMonth($month, $year);
						$day = 1;
						while ($day <= $daysInMonth)
						{
							$n = mktime(0, 0, 0, $month, $day, $year);
							if (isset($_POST[$n]))
							{
								$erreur = 'n';
								// Le jour a été selectionné dans le calendrier
								if (!isset($all_day))
								{
								// Cas des réservation par créneaux pré-définis
									if ($enable_periods=='y')
									{
										$resolution = 60;
										$hour = 12;
										$end_hour = 12;
										if (isset($period))
											$minute = $period;
										else
											$minute = 0;
										if (isset($end_period))
											$end_minute = $end_period + 1;
										else
											$end_minute = $eveningends_minutes + 1;
									}
									$starttime = mktime($hour, $minute, 0, $month, $day, $year);
									$endtime   = mktime($end_hour, $end_minute, 0, $month, $day, $year);
									if ($endtime <= $starttime)
										$erreur = 'y';
								}
								else
								{
									$starttime = mktime($morningstarts, 0, 0, $month, $day, $year);
									$endtime   = mktime($eveningends, $eveningends_minutes , 0, $month, $day, $year);
								}
								if ($erreur != 'y')
								{
									// On efface toutes les résa en conflit
									$result += grrDelEntryInConflict($row['id'], $starttime, $endtime, 0, 0, 1);
									// S'il s'agit d'une action de réservation, on réserve !
									if ($type_resa == "resa")
									{
										// Par sécurité, on teste quand même s'il reste des conflits
										$err = mrbsCheckFree($row['id'], $starttime, $endtime, 0,0);
										if (!$err)
											mrbsCreateSingleEntry($starttime, $endtime, 0, 0, $row['id'], $user_name, $beneficiaire, "", $name, $type_, $description, -1,array(),0,0,'-', 0, 0);
									}
								}
							}
							$day++;
						}
						$month++;
						if ($month == 13)
						{
							$year++;
							$month = 1;
						}
					}
				}
			}
		}
	}
  grr_sql_free($res);
}
elseif ($etape == 3) //sélection des jours
{
	if(isset($rooms))
	{
		$test_enable_periods_y = 0;
    $test_enable_periods_n = 0;
    $hidden_inputs = "";
    foreach ( $rooms as $room_id )
    {
      $room_id = intval($room_id);
      $temp = "id_room_".$room_id;
      $hidden_inputs.= "<input type=\"hidden\" name=\"".$temp."\" value=\"yes\" />";
      $area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id = '".$room_id."'");
      $test_enable_periods_y += grr_sql_query1("SELECT count(enable_periods) FROM ".TABLE_PREFIX."_area WHERE (id = '".$area_id."' and enable_periods='y')");
      $test_enable_periods_n += grr_sql_query1("SELECT count(enable_periods) FROM ".TABLE_PREFIX."_area WHERE (id = '".$area_id."' and enable_periods='n')");
    }
      // On teste si tous les domaines selectionnés sont du même type d'affichage à savoir :
      // soit des créneaux de réservation basés sur le temps,
      // soit des créneaux de réservation basés sur des intitulés pré-définis.
    if ($test_enable_periods_y == 0)
      $all_enable_periods = 'n';
    else if ($test_enable_periods_n == 0)
      $all_enable_periods = 'y';
    else
      $all_enable_periods = 'incompatible';

    if ($all_enable_periods != "incompatible")
    {
        // On propose une heure de début et une heure de fin de réservation
      $texte_debut_fin_reservation = "";
        // On prend comme domaine de référence le dernier domaine de la boucle  foreach ( $rooms as $room_id ) {
        // C'est pas parfait mais bon !
      get_planning_area_values($area_id);
      if ($all_enable_periods == 'y')
      {
          // Créneaux basés sur les intitulés pré-définis
          // Heure ou créneau de début de réservation
        $texte_debut_fin_reservation .= "<b>".get_vocab("date").get_vocab("deux_points")."</b>";
        $texte_debut_fin_reservation .= "<br />".get_vocab("period")."\n";
        $texte_debut_fin_reservation .= "<select name=\"period\">";
        foreach ($periods_name as $p_num => $p_val)
        {
          $texte_debut_fin_reservation .= "<option value=\"$p_num\">$p_val</option>";
        }
        $texte_debut_fin_reservation .= "</select>\n";
        $texte_debut_fin_reservation .= "<br /><br /><b>".get_vocab("fin_reservation").get_vocab("deux_points")."</b>";
        $texte_debut_fin_reservation .= "<br />".get_vocab("period")."\n";
        $texte_debut_fin_reservation .= "<select name=\"end_period\">";
        foreach ($periods_name as $p_num => $p_val)
        {
          $texte_debut_fin_reservation .= "<option value=\"$p_num\">$p_val</option>";
        }
        $texte_debut_fin_reservation .= "</select>\n";

      }
      else
      {
          // Créneaux basés sur le temps
          // Heure ou créneau de début de réservation
        $texte_debut_fin_reservation .= "<b>".get_vocab("date").get_vocab("deux_points")."</b>";
        $texte_debut_fin_reservation .= "<br />".get_vocab("time")."
        <input name=\"hour\" size=\"2\" value=\"".$morningstarts."\" MAXLENGTH=2 />
        <input name=\"minute\" size=\"2\" value=\"0\" MAXLENGTH=2 />";
        $texte_debut_fin_reservation .= "<br /><br /><b>".get_vocab("fin_reservation").get_vocab("deux_points")."</b>";
        $texte_debut_fin_reservation .= "<br />".get_vocab("time")."
        <input name=\"end_hour\" size=\"2\" value=\"".$morningstarts."\" MAXLENGTH=2 />
        <input name=\"end_minute\" size=\"2\" value=\"0\" MAXLENGTH=2 />";
      }
      $texte_debut_fin_reservation .= '<br /><br /><b><input name="all_day" type="checkbox" value="yes" />'.get_vocab("all_day").'</b>';
    }
    else
    {
      $texte_debut_fin_reservation = get_vocab("domaines_de_type_incompatibles");
      $texte_debut_fin_reservation.= "<input type=\"hidden\" name=\"all_day\" value=\"y\" />";
    }
  }
}
elseif($etape == 2){
  // bénéficiaire
  $sel_user = "";
  $sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and statut!='visiteur' ) order by nom, prenom";
  $res = grr_sql_query($sql);
  if ($res){
    $sel_user.= "<select size=\"1\" name=\"beneficiaire\" class=\"form-control\">\n";
    foreach($res as $row){
      $sel_user .= "<option value='".$row['login']."' ";
      if ($user_name == $row['login'])
        $sel_user.= " selected=\"selected\"";
      $sel_user.= ">".$row['nom']."  ".$row['prenom']." </option>";
    }
    $sel_user.="</select>";
  }
  grr_sql_free($res);
  // ressource(s)
  $sel_room = "";
  $tab_rooms_noaccess = no_book_rooms($user_name);
  $tab_rooms_noaccess[] = 0; // assure un tableau non vide
  $id_exclues = "('" . implode("', '", $tab_rooms_noaccess) . "')";
  $areas[] = 0;
  $id_areas = "('" . implode("', '", $areas) . "')";
  $sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id IN $id_areas AND id NOT IN $id_exclues ORDER BY order_display,room_name";
  $res = grr_sql_query($sql);
  if($res){
    $sel_room.= "<select name=\"rooms[]\" multiple class=\"form-control\">";
    foreach($res as $row){
      $sel_room.= "<option value=\"".$row['id']."\">".$row['room_name']."</option>";
    }
    $sel_room.= "</select>";
  }
  grr_sql_free($res);
  // types
  $sel_type = "";
  $sql = "SELECT DISTINCT t.type_name, t.type_letter, t.order_display FROM ".TABLE_PREFIX."_type_area t
  LEFT JOIN ".TABLE_PREFIX."_j_type_area j ON j.id_type=t.id
  WHERE (j.id_area IS NULL OR j.id_area NOT IN $id_areas) ORDER BY t.order_display";
  //echo $sql;
  $res = grr_sql_query($sql);
  if ($res)
  {
    $sel_type.= "<select name=\"type_\" class=\"form-control\">\n";
    foreach($res as $row){
      $sel_type.= "<option value=\"".$row['type_letter']."\" ";
      if ($type_ == $row['type_letter'])
        $sel_type.= " selected=\"selected\"";
      $sel_type.= " >".$row['type_name']."</option>\n";
    }
    $sel_type.= "</select>";
  }
  else fatal_error(1,grr_sql_error());
  grr_sql_free($res);
  if(($sel_user == "")||($sel_room == "")||($sel_type == "")){
    fatal_error(1,"erreur de lecture en base de données");
  }
}
else{// (étape 1) sélection des domaines et de l'action à réaliser
  $sel_area = "";
	if (authGetUserLevel($user_name, -1) >= 2){
    $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display, area_name";
    $res = grr_sql_query($sql);
  }
	else{
    $sql = "SELECT a.id, a.area_name FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j, ".TABLE_PREFIX."_site s, ".TABLE_PREFIX."_j_useradmin_site u
    WHERE a.id=j.id_area and j.id_site = s.id and s.id=u.id_site and u.login=? 
    ORDER BY a.order_display, a.area_name";
    $res = grr_sql_query($sql,"s",[$user_name]);
  }
	if ($res)
	{
    $sel_area.= "<select name=\"areas[]\" multiple=\"multiple\" class=\"form-control\">\n";
		foreach($res as $row)
		{
			if (authUserAccesArea($user_name,$row['id']) == 1)
        $sel_area.= "<option value=\"".$row['id']."\">".$row['area_name']."</option>\n";
		}
    $sel_area .= "</select>";
	}
  else 
    fatal_error(0,"erreur de lecture en base de données");
  grr_sql_free($res);
}
// code HTML
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_calendar_title.php')."</h2>\n";

if ($etape == 4) // affichage du résultat
{
	if ($result == '')
		$result = 0;
	if ($type_resa == "resa")
	{
		echo "<h3 style=\"text-align:center;\">".get_vocab("reservation_en_bloc")."</h3>\n";
		echo "<h3>".get_vocab("reservation_en_bloc_result")."</h3>\n";
		if ($result != 0)
			echo "<p>".get_vocab("reservation_en_bloc_result2")."<b>".$result."</b></p>\n";
	}
	else
	{
		echo "<h3 style=\"text-align:center;\" class=\"avertissement\">".get_vocab("suppression_en_bloc")."</h3>\n";
		echo "<h3>".get_vocab("suppression_en_bloc_result")."<b>".$result."</b></h3>\n";
	}
}

if ($etape == 3) //sélection des jours
{
	// Etape N° 3
	echo "<h3 style=\"text-align:center;\">".get_vocab("etape_n")."3/3</h3>\n";
	if ($type_resa == "resa")
		echo "<h3 style=\"text-align:center;\">".get_vocab("reservation_en_bloc")."</h3>\n";
	else
		echo "<h3 style=\"text-align:center;\"  class=\"avertissement\">".get_vocab("suppression_en_bloc")."</h3>\n";

	if (!isset($rooms))
	{
		echo "<h3>".get_vocab("noarea")."</h3>\n";
			// fin de l'affichage de la colonne de droite et de la page
		echo "</div></section></body></html>\n";
		die();
	}

	echo "<form action=\"admin_calend2.php\" method=\"post\" id=\"formulaire\" name=\"formulaire\" >\n";
	echo "<table class='table-noborder'>\n";
	$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
	for ($i = 0; $i < 7; $i++)
	{
		$show = $basetime + ($i * 24 * 60 * 60);
		$lday = utf8_strftime('%A',$show);
		echo "<tr>\n";
		echo "<td><span class='small'><a href='admin_calend2.php' onclick=\"setCheckboxesGrr('formulaire', true, '$lday' ); return false;\">".get_vocab("check_all_the").$lday."s</a></span></td>\n";
		echo "<td><span class='small'><a href='admin_calend2.php' onclick=\"setCheckboxesGrr('formulaire', false, '$lday' ); return false;\">".get_vocab("uncheck_all_the").$lday."s</a></span></td>\n";
		if ($i == 0)
			echo "<td rowspan=\"8\">  </td><td rowspan=\"8\">$texte_debut_fin_reservation</td>\n";
		echo "</tr>\n";
	}
	echo "<tr>\n<td><span class='small'><a href='admin_calend2.php' onclick=\"setCheckboxesGrr('formulaire', false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span></td>\n";
	echo "<td> </td></tr>\n";
	echo "</table>\n";
	echo "<table>\n";
	$n = Settings::get("begin_bookings");
	$end_bookings = Settings::get("end_bookings");
	$debligne = 1;
	$month = date("m", Settings::get("begin_bookings"));
	$year = date("Y", Settings::get("begin_bookings"));
	$inc = 0;
	while ($n <= $end_bookings)
	{
		if ($debligne == 1)
		{
			echo "<tr>\n";
			$inc = 0;
			$debligne = 0;
		}
		$inc++;
		echo "<td>\n";
		echo cal($month, $year, 1);
		echo "</td>";
		if ($inc == 3)
		{
			echo "</tr>";
			$debligne = 1;
		}
		$month++;
		if ($month == 13)
		{
			$year++;
			$month = 1;
		}
		$n = mktime(0, 0, 0, $month, 1, $year);
	}
	if ($inc < 3)
	{
		$k = $inc;
		while ($k < 3)
		{
			echo "<td> </td>\n";
			$k++;
		}
		echo "</tr>";
	}

	echo "</table>";
	echo "<div id=\"fixe\"><input type=\"submit\" class=\"btn btn-primary\" name=\"".get_vocab('save')."\" /></div>\n";
	echo "<div>\n<input type=\"hidden\" name=\"record\" value=\"yes\" />\n";
	echo "<input type=\"hidden\" name=\"etape\" value=\"4\" />\n";
	echo "<input type=\"hidden\" name=\"name\" value=\"".$name."\" />\n";
	echo "<input type=\"hidden\" name=\"description\" value=\"".$description."\" />\n";
	echo "<input type=\"hidden\" name=\"beneficiaire\" value=\"".$beneficiaire."\" />\n";
	echo "<input type=\"hidden\" name=\"type_\" value=\"".$type_."\" />\n";
	echo "<input type=\"hidden\" name=\"type_resa\" value=\"".$type_resa."\" />\n";
  echo $hidden_inputs;
	echo "</div>\n</form>";
}

else if ($etape == 2)
{
		// Etape 2
	?>
	<script  type="text/javascript"  >
		<?php
		if ($type_resa == "resa")
		{
			?>
			function validate_and_submit ()
			{
				if (document.getElementById("main").name.value == "")
				{
					alert ( "<?php echo get_vocab('you_have_not_entered') . '\n' . get_vocab('brief_description') ?>");
					return false;
				}
				else if (document.getElementById("main").elements[3].value == '')
				{
					alert("<?php echo get_vocab("choose_a_room"); ?>");
					return false;
				}
				else if (document.getElementById("main").type_.value == '0')
				{
					alert("<?php echo get_vocab("choose_a_type"); ?>");
					return false;
				}
				else
					return true;
			}
			<?php
		}
		else
		{
			?>

			function validate_and_submit ()
			{
				if (document.getElementById("main").elements[0].value == '')
				{
					alert("<?php echo get_vocab("choose_a_room"); ?>");
					return false;
				}
				else
					return true;
			}
			<?php
		}
		?>
	</script>
	<?php

	echo "<h3 style=\"text-align:center;\">".get_vocab("etape_n")."2/3</h3>\n";
	if ($type_resa == "resa")
		echo "<h3 style=\"text-align:center;\">".get_vocab("reservation_en_bloc")."</h3>\n";
	else
		echo "<h3 style=\"text-align:center;\"  class=\"avertissement\">".get_vocab("suppression_en_bloc")."</h3>\n";
	if (!isset($areas))
	{
		echo "<h3>".get_vocab("noarea")."</h3>\n";
		// fin de l'affichage de la colonne de droite et de la page
		echo "</div></section></body></html>\n";
		die();
	}
	// formulaire : bénéficiaire, brève description, description complète, ressource(s), type
	echo "<form action=\"admin_calend2.php\" method=\"post\" id=\"main\" onsubmit=\"return validate_and_submit();\">\n";
	echo "<table class='table table-noborder'>\n";
	if ($type_resa == "resa")
	{
		echo "<tr><td class=\"CR\"><b>".ucfirst(trim(get_vocab("reservation_au_nom_de"))).get_vocab("deux_points")."</b></td>\n\n";
		echo "<td class=\"CL\">";
    echo $sel_user;
		echo "</td>\n</tr>\n";
		echo "<tr><td class=\"CR\"><b>".get_vocab("namebooker").get_vocab("deux_points")."</b></td>\n";
		echo "<td class=\"CL\"><input class=\"form-control\" name=\"name\" size=\"40\" value=\"\" autocomplete='off' /></td></tr>";
		echo "<tr><td class=\"TR\"><b>".get_vocab("fulldescription")."</b></td>\n";
		echo "<td class=\"TL\"><textarea class=\"form-control\" name=\"description\" rows=\"8\" cols=\"40\" ></textarea></td></tr>";
	}
	echo "<tr><td class=\"CR\"><b>".get_vocab("rooms").get_vocab("deux_points")."</b></td>\n";
	echo "<td class=\"CL\" valign=\"top\"><table border=\"0\"><tr><td>";
  echo $sel_room;
  echo "</td><td>".get_vocab("ctrl_click")."</td></tr></table>\n";
	echo "</td></tr>\n";
	if ($type_resa == "resa")
	{
		echo "<tr><td class=\"CR\"><b>".get_vocab("type").get_vocab("deux_points")."</b></td>\n";
		echo "<td class=\"CL\">";
    echo $sel_type;
    echo "</td></tr>";
  }
echo "</table>\n";
echo "<div><input type=\"hidden\" name=\"etape\" value=\"3\" />\n";
echo "<input type=\"hidden\" name=\"type_resa\" value=\"".$type_resa."\" />\n";
echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"".get_vocab("next")."\" />";
echo "</div></form>";
}
else if (!$etape)
{
	// Etape 1 :
	echo get_vocab("admin_calendar_explain_1.php");
	echo "<h3 style=\"text-align:center;\">".get_vocab("etape_n")."1/3</h3>\n";
	// Choix des domaines
	echo "<form action=\"admin_calend2.php\" method=\"post\">\n";
	echo "<table border=\"1\"><tr><td>\n";
	echo "<p><b>".get_vocab("choix_domaines")."</b></p>";
  echo $sel_area;
	echo "<br />".get_vocab("ctrl_click_area");
  echo "</td><td>";
  echo "<p><b>".get_vocab("choix_action")."</b></p>";
  echo "<table><tr>";
  echo "<td><input type=\"radio\" name=\"type_resa\" value=\"resa\" checked=\"checked\" /></td>\n";
  echo "<td>".get_vocab("reservation_en_bloc")."</td>\n";
  echo "</tr><tr>\n";
  echo "<td><input type=\"radio\" name=\"type_resa\" value=\"suppression\" /></td>\n";
  echo "<td>".get_vocab("suppression_en_bloc")."</td>\n";
  echo "</tr></table>\n";
  echo "</td></tr></table>\n";
  echo "<div><input type=\"hidden\" name=\"etape\" value=\"2\" />\n";
  echo "<br /><input class=\"btn btn-primary\" type=\"submit\" name=\"Continuer\" value=\"".get_vocab("next")."\" />\n";
  echo "</div></form>\n";
	}
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>