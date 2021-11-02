<?php
/**
 * validation.php
 * Interface de validation d'une réservation modérée
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-10-22 16:33$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "validation.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";

require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./login.php?url=$url");
	die();
}
// ici l'utilisateur devrait être connecté
include "include/language.inc.php";
// vérifie que la réservation existe et que le script est en appel initial
if (isset($_GET['id'])) // appel initial
{
	$id_resa = clean_input($_GET['id']);
	settype($id_resa,"integer");
	$room_id = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."'");
	if ($room_id == -1)// erreur ou pas de résultat
	{
		start_page_w_header('', '', '', "with_session");
		echo '<h1>'.get_vocab("accessdenied").'</h1>';
		echo '<p class="avertissement larger">'.get_vocab("invalid_entry_id").'</p>';
		echo '<p><a href="'.page_accueil().'">'.get_vocab("back").'</a></p>';
		end_page();
		die();
	}	
	else // réservation existante et script en appel initial
	{
		$area = mrbsGetRoomArea($room_id);
		get_planning_area_values($area);
		$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
		$user = getUserName();
		if (authUserAccesArea($user, $area) == 0)// vérifie l'accès de l'utilisateur à ce domaine
		{
			start_page_w_header('', '', '', "with_session");
			showAccessDenied($back);
			die();
		}
		if ($settings->get("verif_reservation_auto") == 0) 
		{
			verify_confirm_reservation();
			verify_retard_reservation();
		}
		// on récupère les informations liées à la réservation
		$sql = "SELECT ".TABLE_PREFIX."_entry.name,
		".TABLE_PREFIX."_entry.description,
		".TABLE_PREFIX."_entry.beneficiaire,
		".TABLE_PREFIX."_room.room_name,
		".TABLE_PREFIX."_area.area_name,
		".TABLE_PREFIX."_entry.type,
		".TABLE_PREFIX."_entry.room_id,
		".TABLE_PREFIX."_entry.repeat_id,
		".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry.timestamp").",
		(".TABLE_PREFIX."_entry.end_time - ".TABLE_PREFIX."_entry.start_time),
		".TABLE_PREFIX."_entry.start_time,
		".TABLE_PREFIX."_entry.end_time,
		".TABLE_PREFIX."_area.id,
		".TABLE_PREFIX."_entry.statut_entry,
		".TABLE_PREFIX."_room.delais_option_reservation,
		".TABLE_PREFIX."_entry.option_reservation, " .
		"".TABLE_PREFIX."_entry.moderate,
		".TABLE_PREFIX."_entry.beneficiaire_ext,
		".TABLE_PREFIX."_entry.create_by,
		".TABLE_PREFIX."_entry.jours,
		".TABLE_PREFIX."_room.active_ressource_empruntee,
		".TABLE_PREFIX."_entry.clef,
		".TABLE_PREFIX."_entry.courrier,
		".TABLE_PREFIX."_room.active_cle
		FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
		WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
		AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
		AND ".TABLE_PREFIX."_entry.id='".$id_resa."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$row = grr_sql_row($res, 0);
		grr_sql_free($res);
		$breve_description 	= $row[0];
		$description  		= bbcode(htmlspecialchars($row[1]),'');
		$beneficiaire    	= htmlspecialchars($row[2]);
		$room_name    		= htmlspecialchars($row[3]);
		$area_name    		= htmlspecialchars($row[4]);
		$type         		= $row[5];
		$room_id      		= $row[6];
		$repeat_id    		= $row[7];
		$updated      		= time_date_string($row[8], $dformat);
		$duration     		= $row[9];
		$area      			= $row[12];
		$statut_id 			= $row[13];
		$delais_option_reservation 	= $row[14];
		$option_reservation 		= $row[15];
		$moderate 					= $row[16];
		$beneficiaire_ext   		= htmlspecialchars($row[17]);
		$create_by    				= htmlspecialchars($row[18]);
		$jour_cycle    				= htmlspecialchars($row[19]);
		$active_ressource_empruntee = htmlspecialchars($row[20]);
		$keys						= $row[21];
		$courrier					= $row[22];
		$active_cle					= $row[23];
		$rep_type 					= 0;
		$verif_display_email 		= verif_display_email($user, $room_id);
		if ($enable_periods == 'y')
		{
			list( $start_period, $start_date) = period_date_string($row[10]);
			list( , $end_date) =  period_date_string($row[11], -1);
			toPeriodString($start_period, $duration, $dur_units);
		}	
		else
		{
			$start_date = time_date_string($row[10],$dformat);
			$end_date = time_date_string($row[11], $dformat);
			toTimeString($duration, $dur_units);
		}
		if ($beneficiaire != "")
			$mail_exist = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$beneficiaire'");
		else
		{
			$tab_benef = donne_nom_email($beneficiaire_ext);
			$mail_exist = $tab_benef["email"];
		}
		if ($verif_display_email)
			$option_affiche_nom_prenom_email = "withmail";
		else
			$option_affiche_nom_prenom_email = "nomail";
		$date_now = time();

		// envoie le début de page... 
		if (@file_exists("language/lang_subst_".$area.".".$locale))
			include "language/lang_subst_".$area.".".$locale;
		start_page_w_header('', '', '', "with_session");
		echo "<div class='container'>";
		echo '<table class="table table-noborder">'; // informations de la réservation
		echo '<caption style="font-size:12pt;font-weight:bold">'.get_vocab('entry').get_vocab('deux_points').affichage_lien_resa_planning($breve_description, $id_resa).'</caption>'."\n";
		echo '	<tr>';
		echo '		<td>';
		echo '<b>'.get_vocab("description").'</b>';
		echo '		</td>';
		echo '		<td>';
		echo nl2br($description);
		echo '		</td>';
		echo '	</tr>';
		$overload_data = mrbsEntryGetOverloadDesc($id_resa);
		foreach ($overload_data as $fieldname=>$fielddata)
		{
			if ($fielddata["confidentiel"] == 'n')
				$affiche_champ = TRUE;
			else
			{
				if ($user=='')
					$affiche_champ = FALSE;
				else
				{
					if ((authGetUserLevel($user, $room_id) >= 4) || ($beneficiaire == $user))
						$affiche_champ = TRUE;
					else
						$affiche_champ = FALSE;
				}
			}
			if ($affiche_champ)
			{
				echo "<tr><td><b>".bbcode(htmlspecialchars($fieldname).get_vocab("deux_points"), '')."</b></td>\n";
				echo "<td>".bbcode(htmlspecialchars($fielddata["valeur"]), '')."</td></tr>\n";
			}
		}
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("room"),get_vocab("deux_points").'</b></td>';
		echo '		<td>'.nl2br($area_name . " - " . $room_name).'</td>';
		echo '  </tr>';
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("start_date"),get_vocab("deux_points").'</b></td>';
		echo '		<td>'.$start_date.'</td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("duration").'</b></td>';
		echo '		<td>'.$duration." ".$dur_units.'</td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("end_date").'</b></td>';
		echo '		<td>'.$end_date.'</td>';
		echo '	</tr>';
		echo '<tr>',PHP_EOL,'<td><b>',get_vocab("type"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
			$type_name = grr_sql_query1("SELECT type_name from ".TABLE_PREFIX."_type_area where type_letter='".$type."'");
			if ($type_name == -1)
				$type_name = "?$type?";
		echo '<td>',$type_name,'</td>',PHP_EOL,'</tr>',PHP_EOL;
		if (($beneficiaire != $create_by)||($beneficiaire_ext != ''))
		{
			echo '<tr>';
			echo '	<td><b>'.get_vocab("reservation_au_nom_de"),get_vocab("deux_points").'</b></td>';
			echo '	<td>'.affiche_nom_prenom_email($beneficiaire, $beneficiaire_ext, $option_affiche_nom_prenom_email).'</td>';
			echo '</tr>';
		}
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("created_by"),get_vocab("deux_points").'</b></td>';
		echo '		<td>'.affiche_nom_prenom_email($create_by, "", $option_affiche_nom_prenom_email);
		echo '		</td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<td><b>'.get_vocab("lastupdate"),get_vocab("deux_points").'</b></td>';
		echo '		<td>'.$updated.'</td>';
		echo '	</tr>';
        if ($active_ressource_empruntee == 'y')
			{
				$id_resa_en_cours = grr_sql_query1("SELECT id from ".TABLE_PREFIX."_entry where room_id = '".$room_id."' and statut_entry='y'");
				if ($id_resa ==$id_resa_en_cours)
                    echo '<tr><td><span class="avertissement">(',get_vocab("reservation_en_cours"),') <img src="img_grr/buzy_big.png" align=middle alt="',get_vocab("ressource actuellement empruntee"),'" title="',get_vocab("ressource actuellement empruntee"),'" border="0" width="30" height="30" class="print_image" /></span></td></tr>',PHP_EOL;
			}
		if ($keys == 1)
		{
			echo '<tr>';
			echo '	<td><b>'.get_vocab("clef"),get_vocab("deux_points").'</b></td>';
			echo '	<td><img src="img_grr/key.png" alt="clef"></td>';
			echo '</tr>';
		}
		if ($courrier == 1)
		{
			echo '<tr>';
			echo '	<td><b>'.get_vocab("courrier"),get_vocab("deux_points").'</b></td>';
			echo '	<td><img src="img_grr/courrier.png" alt="courrier"></td>';
			echo '</tr>';
		}
		if (($delais_option_reservation > 0) && ($option_reservation != -1))
		{
			echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<div class="alert alert-danger" role="alert"><b>',get_vocab("reservation_a_confirmer_au_plus_tard_le"),PHP_EOL;
			echo time_date_string_jma($option_reservation, $dformat),'</b></div>',PHP_EOL;
			echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
		}
		if ($moderate == 1) // résa en attente de modération
		{
			echo '<tr>',PHP_EOL,'<td><b>',get_vocab("moderation"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
			tdcell("avertissement");
			echo '<strong>',get_vocab("en_attente_moderation"),'</strong></td>',PHP_EOL,'</tr>',PHP_EOL;
		}
		elseif ($moderate == 2) // résa acceptée
		{
			$sql = "SELECT motivation_moderation, login_moderateur FROM ".TABLE_PREFIX."_entry_moderate WHERE id=".$id_resa;
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			$row2 = grr_sql_row($res, 0);
			$description = $row2[0];
			$sql ="SELECT nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row2[1]."'";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			$row3 = grr_sql_row($res, 0);
			$nom_modo = $row3[1]. ' '. $row3[0];
			if (authGetUserLevel($user, -1) > 1)
			{
				echo '<tr>',PHP_EOL,'<td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td><td><strong>'.get_vocab("moderation_acceptee_par").' '.$nom_modo.'</strong>';
				if ($description != "")
					echo ' : <br />('.$description.')';
				echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
			}
		}
		elseif ($moderate == 3) // résa refusée
		{
			$sql = "SELECT motivation_moderation, login_moderateur from ".TABLE_PREFIX."_entry_moderate where id=".$id_resa;
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			$row4 = grr_sql_row($res, 0);
			$description = $row4[0];
			$sql ="SELECT nom, prenom from ".TABLE_PREFIX."_utilisateurs where login = '".$row4[1]."'";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			$row5 = grr_sql_row($res, 0);
			$nom_modo = $row5[1]. ' '. $row5[0];
			if (authGetUserLevel($user, -1) > 1)
			{
				echo '<tr><td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td>';
				tdcell("avertissement");
				echo '<strong>'.get_vocab("moderation_refusee").'</strong> par '.$nom_modo;
				if ($description != "")
					echo ' : <br />('.$description.')';
				echo '</td>',PHP_EOL,'</tr>',PHP_EOL;;
			}
		}
		echo "</table>";
		
		if ($repeat_id != 0) // cas d'une série
		{
			$res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$repeat_id");
			if (!$res)
				fatal_error(0, grr_sql_error());
			if (grr_sql_count($res) == 1)
			{
				$row6 			= grr_sql_row($res, 0);
				$rep_type     	= $row6[0];
				$rep_end_date 	= utf8_strftime($dformat,$row6[1]);
				$rep_opt      	= $row6[2];
				$rep_num_weeks 	= $row6[3];
				$start_time 	= $row6[4];
				$end_time 		= $row6[5];
				$duration 		= $row6[5] - $row6[4];
			}
			grr_sql_free($res);
			if ($enable_periods == 'y')
			{
				list( $start_period, $start_date) = period_date_string($start_time);
				toPeriodString($start_period, $duration, $dur_units);
			}
			else 
			{
				$start_date = time_date_string($start_time, $dformat);
				toTimeString($duration, $dur_units);
			}
			/*$weeklist = array("unused", "every week", "week 1/2", "week 1/3", "week 1/4", "week 1/5");
			if ($rep_type == 2)
				$affiche_period = get_vocab($weeklist[$rep_num_weeks]);
			else
				$affiche_period = get_vocab('rep_type_'.$rep_type);
			echo '<table class="table">';
			echo '<caption style="font-weight:bold">'.get_vocab('periodicite_associe').'</caption>\n';
			echo '<tr><td><b>'.get_vocab("rep_type").'</b></td><td>'.$affiche_period.'</td></tr>';*/
			if ($rep_type != 0)
			{
				$weeklist = array("unused", "every week", "week 1/2", "week 1/3", "week 1/4", "week 1/5");
				if ($rep_type == 2)
					$affiche_period = get_vocab($weeklist[$rep_num_weeks]);
				else
					$affiche_period = get_vocab('rep_type_'.$rep_type);
				echo '<table class="table">';
				echo '<caption style="font-weight:bold">'.get_vocab('periodicite_associe').'</caption>';
				echo '<tr><td><b>'.get_vocab("rep_type").'</b></td><td>'.$affiche_period.'</td></tr>';
				if ($rep_type == 2)
				{
					$opt = "";
					$nb = 0;
					for ($i = 0; $i < 7; $i++)
					{
						$daynum = ($i + $weekstarts) % 7;
						if ($rep_opt[$daynum])
						{
							if ($opt != '')
								$opt .=', ';
							$opt .= day_name($daynum);
							$nb++;
						}
					}
					if ($opt)
						if ($nb == 1)
							echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
						else
							echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_days"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
				}
				if ($rep_type == 6)
				{
					if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
						echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',get_vocab('jour_cycle'),' ',$jour_cycle,'</td>',PHP_EOL,'</tr>',PHP_EOL;
				}
				echo '<tr><td><b>'.get_vocab("date").get_vocab("deux_points").'</b></td><td>'.$start_date.'</td></tr>';
				echo '<tr><td><b>'.get_vocab("duration").'</b></td><td>'.$duration .' '. $dur_units.'</td></tr>';
				echo '<tr><td><b>'.get_vocab('rep_end_date').'</b></td><td>'.$rep_end_date.'</td></tr>';
			}
            if ((getWritable($user, $id_resa)) && verif_booking_date($user, $id_resa, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room($user, $room_id, $row[10], $enable_periods))
            {
                $message_confirmation = str_replace ( "'"  , "\\'"  , get_vocab("confirmdel").get_vocab("deleteseries"));
                //echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<input class="btn btn-primary" type="button" onclick="location.href=\'edit_entry.php?id=',$id_resa,'&amp;edit_type=series&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=',$page,'\'" value="',get_vocab("editseries"),'"></td>',PHP_EOL,'</tr>',PHP_EOL;
				echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<input class="btn btn-primary" type="button" onclick="location.href=\'edit_entry.php?id=',$id_resa,'&amp;edit_type=series\'" value="',get_vocab("editseries"),'"></td>',PHP_EOL,'</tr>',PHP_EOL;
                echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<a class="btn btn-danger" type="button" href="del_entry.php?id=',$id_resa,'&amp;series=1" onclick="return confirm(\'',$message_confirmation,'\');">',get_vocab("deleteseries"),'</a></td>',PHP_EOL,'</tr>',PHP_EOL;
            }
            echo '</table>',PHP_EOL;
        }
        if (!isset($area_id))
            $area_id = 1;
        if (!isset($room))
            $room = 1;
        if (Settings::get("pdf") == '1'){
            if ((authGetUserLevel($user, $area_id, "area") > 1) || (authGetUserLevel($user, $room) >= 4))
                echo '<br><input class="btn btn-primary" onclick="popUpPdf(',$id_resa,')" value="',get_vocab("Generer_pdf"),'" />',PHP_EOL;
        }
		// formulaire
		echo "<form action=\"validation.php\" method=\"post\">";
        if (($user != '') && (authGetUserLevel($user, $room_id) >= 3) && ($moderate == 1))
        {
            echo "<input type=\"hidden\" name=\"action_moderate\" value=\"y\" />";
            //echo "<input type=\"hidden\" name=\"id\" value=\"".$id_resa."\" />";
            echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("moderate_entry")."</legend>";
            echo "<p>";
            echo "<input type=\"radio\" name=\"moderate\" value=\"1\" checked=\"checked\" />".get_vocab("accepter_resa");
            echo "<br /><input type=\"radio\" name=\"moderate\" value=\"0\" />".get_vocab("refuser_resa");
            if ($repeat_id)
            {
                echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S1\" />".get_vocab("accepter_resa_serie");
                echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S0\" />".get_vocab("refuser_resa_serie");
            }
            echo "</p><p>";
            echo "<label for=\"description\">".get_vocab("justifier_decision_moderation").get_vocab("deux_points")."</label>\n";
            echo "<textarea class=\"form-control\" name=\"description\" id=\"description\" cols=\"40\" rows=\"3\"></textarea>";
            echo "</p>";
            echo "</fieldset>";
		}
        if ($active_ressource_empruntee == 'y')
        {
            if (($moderate != 1) && ($user != '') && (authGetUserLevel($user,$room_id) >= 3))
            {
                echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("reservation_en_cours")."</legend>\n";
                echo "<span class=\"larger\">".get_vocab("signaler_reservation_en_cours")."</span>".get_vocab("deux_points");
                echo "<br />".get_vocab("explications_signaler_reservation_en_cours");
                affiche_ressource_empruntee($room_id, "texte");
                echo "<br /><input type=\"radio\" name=\"statut_id\" value=\"-\" ";
                if ($statut_id == '-')
                {
                    if (!affiche_ressource_empruntee($room_id,"autre") == 'yes')
                        echo " checked=\"checked\" ";
                }
                echo " />".get_vocab("signaler_reservation_en_cours_option_0");
                echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"y\" ";
                if ($statut_id == 'y')
                    echo " checked=\"checked\" ";
                echo " />".get_vocab("signaler_reservation_en_cours_option_1");
                echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"e\" ";
                if ($statut_id == 'e')
                    echo " checked=\"checked\" ";
                if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                    echo " disabled ";
                echo " />".get_vocab("signaler_reservation_en_cours_option_2");
                if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                    echo "<br /><i>(".get_vocab("necessite fonction mail automatique").")</i>";
                if (Settings::get("automatic_mail") == 'yes')
                {
                    echo "<br /><br /><input type=\"checkbox\" name=\"envoyer_mail\" value=\"y\" ";
                    if ($mail_exist == "")
                        echo " disabled ";
                    echo " />".get_vocab("envoyer maintenant mail retard");
                    echo "<input type=\"hidden\" name=\"mail_exist\" value=\"".$mail_exist."\" />";
                }
                if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                    echo "<br /><i>(".get_vocab("necessite fonction mail automatique").")</i>";
                // echo "<br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" /></div>";
				echo "</fieldset>\n";
                //echo "<input type=\"hidden\" name=\"id\" value=\"".$id_resa."\" />";
                echo "<input type=\"hidden\" name=\"back\" value=\"".$back."\" />";//</div>";
            }
        }
        if (isset($keys) && isset($courrier))
        {
            echo "<fieldset>";//<legend style=\"font-weight:bold\">".get_vocab("reservation_en_cours")."</legend>\n";
            if ($active_cle == 'y'){
                echo "<span class=\"larger\">".get_vocab("status_clef").get_vocab("deux_points")."</span>";
                echo "<br /><input type=\"checkbox\" name=\"clef\" value=\"y\" ";
                if ($keys == 1)
                    echo " checked ";
                echo " /> ".get_vocab("msg_clef");
            }
            if (Settings::get('show_courrier') == 'y'){
                echo "<br /><span class=\"larger\">".get_vocab("status_courrier").get_vocab("deux_points")."</span>";
                echo "<br /><input type=\"checkbox\" name=\"courrier\" value=\"y\" ";
                if ($courrier == 1)
                    echo " checked ";
                echo " /> ".get_vocab("msg_courrier");
            }
            echo "<br />";
			echo "</fieldset>";
            //echo '<input type="hidden" name="id" value="',$id_resa,'" />',PHP_EOL;
            echo '<input type="hidden" name="back" value="',$back,'" />',PHP_EOL;
        }
		echo "<input type=\"hidden\" name=\"id\" value=\"".$id_resa."\" />";
		echo "<br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"commit\" value=\"".get_vocab("save")."\" /></div>\n";
		echo '</form>',PHP_EOL;
		echo "</div>";
		end_page();
		die();
	}	
}
elseif (isset($_POST['commit'])&& isset($_POST['action_moderate']))// deuxième appel au script pour validation
{
	//print_r($_POST);
	$id_resa = htmlspecialchars($_POST['id']);
	settype($id_resa,"integer");
	moderate_entry_do($id_resa,$_POST["moderate"], $_POST["description"]);
	if (isset($_POST['clef']))
		{
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET clef='1' WHERE id = '".$id_resa."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
	if (isset($_POST['courrier']))
		{
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET courrier='1' WHERE id = '".$id_resa."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
	header("Location: ".page_accueil());
	die();
}	
else // on ne devrait jamais être ici
{
	header("Location: ".page_accueil());
	die();	
}

?>