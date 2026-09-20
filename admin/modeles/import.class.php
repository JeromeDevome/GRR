<?php
/**
 * import.class.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2026-09-13 16:00$
 * @author    JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


Class Adm_Import
{

	public static function NomsChamps($champ)
	{
		$nomsChamps = array(
			'identifiant' => 'identifiant',
			'nom' => 'nom',
			'Nom' => 'nom',
			'prenom' => 'prenom',
			'mot_de_passe' => 'mot_de_passe',
			'email' => 'email',
			'statut' => 'statut',
			'Date du jour' => 'date',
			'Heure de début' => 'heure_debut',
			'Heure de fin' => 'heure_fin',
			'resa_date_debut' => 'date',
			'resa_date_fin' => 'date_fin',
			'resa_heure_debut' => 'heure_debut',
			'resa_heure_fin' => 'heure_fin',
			'resa_ressource' => 'ressource',
			'resa_description' => 'description',
			'resa_type' => 'type_reservation',
			'Ressource' => 'ressource',
			'Description' => 'description',
			'Type de réservation' => 'type_reservation',
			'Archiver' => 'archiver',
			'Identifiant' => 'identifiant_type',
			'Ordre' => 'ordre',
			'Couleur du fond' => 'couleur_fond',
			'Couleur du texte' => 'couleur_texte',
			'Couleur des icônes' => 'couleur_icones',
		);

		return $nomsChamps[$champ] ?? $champ;
	}


	public static function ValidationCellule($champ, $valeur, $type, $separateurMultiples = ',', $authentification = '')
	{
		$nomChamp = Adm_Import::NomsChamps($champ);
		$valeur = trim((string) $valeur);
		$longueur = function_exists('mb_strlen') ? mb_strlen($valeur) : strlen($valeur);
		$erreur = '';

		// Validation pour les groupes
		if ($type === 'groupes')
		{
			if ($valeur === '' && in_array($nomChamp, array('gr_nom'), true)) {
				$erreur = 'Valeur obligatoire';
			} elseif ($nomChamp === 'gr_nom' && $longueur > 120) {
				$erreur = '120 caractères maximum';
			} elseif ($nomChamp === 'description' && $longueur > 65535) {
				$erreur = 'Description trop longue';
			} elseif ($nomChamp === 'gr_archiver' && !in_array($valeur, array('0', '1'), true)) {
				$erreur = 'Valeur attendue : 0, 1';
			}
		}

		// Validation pour les utilisateurs
		if ($type === 'utilisateurs')
		{
			if ($valeur === '' && in_array($nomChamp, array('user_identifiant', 'user_nom', 'user_prenom', 'user_statut'), true)) {
				$erreur = 'Valeur obligatoire';
			} elseif ($nomChamp === 'user_identifiant' && ($longueur > 40 || !preg_match('/^[A-Za-z0-9_@.\-]+$/', $valeur))) {
				$erreur = 'Identifiant invalide (40 caractères maximum, sans espace)';
			} elseif ($nomChamp === 'user_nom' && $longueur > 30) {
				$erreur = '30 caractères maximum';
			} elseif ($nomChamp === 'user_prenom' && $longueur > 30) {
				$erreur = '30 caractères maximum';
			} elseif ($nomChamp === 'user_statut' && !in_array(strtolower($valeur), array('visiteur', 'utilisateur', 'administrateur', 'gestionnaire_utilisateur'), true)) {
				$erreur = 'Statut non reconnu';
			} elseif ($nomChamp === 'user_email' && ($longueur > 100 || ($valeur !== '' && !filter_var($valeur, FILTER_VALIDATE_EMAIL)))) {
				$erreur = 'Adresse e-mail invalide ou trop longue';
			} elseif ($nomChamp === 'user_mot_de_passe' && strtolower($authentification) === 'local') {
				if ($longueur > 184) {
					$erreur = '184 caractères maximum';
				} elseif ($valeur === '') {
					$erreur = 'Valeur obligatoire';
				} elseif ($valeur !== '' && !check_password_difficult($valeur)) {
					$erreur = 'Le mot de passe ne respecte pas les critères de complexité';
				}
			} elseif ($nomChamp === 'user_authentification' && !in_array(strtolower($valeur), array('local', 'ext'), true)) {
				$erreur = 'Valeur attendue : local, ext';
			} elseif ($nomChamp === 'user_actif' && !in_array(strtolower($valeur), array('actif', 'inactif'), true)) {
				$erreur = 'Valeur attendue : actif, inactif';
			} elseif ($nomChamp === 'user_changement_mdp' && !in_array(strtolower($valeur), array('0', '1'), true)) {
				$erreur = 'Valeur attendue : 0, 1';
			} elseif ($nomChamp === 'user_groupes' && $valeur !== '') {
				// Pour chaque groupe, vérifier si le groupe existe dans la base de données
				$nomsGroupes = array_map('trim', explode($separateurMultiples, $valeur));
				foreach ($nomsGroupes as $nomGroupe) {
					$nomGroupe = SecuChaine::ProtectDataSql($nomGroupe);
					if (grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_groupes WHERE nom = '$nomGroupe'") <= 0) {
						$erreur = 'Groupe inexistant';
						break;
					}
				}
			} elseif ($nomChamp === 'user_commentaire' && $longueur > 65535) {
				$erreur = 'Commentaire trop long';
			} elseif ($nomChamp === 'user_desactiver_mails' && !in_array(strtolower($valeur), array('0', '1'), true)) {
				$erreur = 'Valeur attendue : 0, 1';
			} elseif ($nomChamp === 'user_commentaire' && $longueur > 65535) {
				$erreur = 'Commentaire trop long';
			}
		}

		// Validation pour les types de réservation
		if ($type === 'types')
		{
			if ($valeur === '' && in_array($nomChamp, array('type_identifiant', 'type_nom'), true)) {
				$erreur = 'Valeur obligatoire';
			} elseif ($nomChamp === 'type_identifiant' && ($longueur > 2 || !preg_match('/^[A-Z]{1,2}$/', $valeur))) {
				$erreur = 'Identifiant de type invalide';
			} elseif ($nomChamp === 'type_nom' && $longueur > 120) {
				$erreur = '120 caractères maximum';
			} elseif ($nomChamp === 'type_ordre' && !preg_match('/^-?\d+$/', $valeur)) {
				$erreur = 'Nombre entier attendu';
			} elseif (in_array($nomChamp, array('type_couleur_fond', 'type_couleur_texte', 'type_couleur_icones'), true) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $valeur)) {
				$erreur = 'Couleur attendue au format #RRGGBB';
			} elseif ($nomChamp === 'type_disponible' && !in_array(strtolower($valeur), array('2', '3', '5'), true)) {
				$erreur = 'Valeur attendue : 2, 3, 5';
			}
		}

		// Validation pour les réservations
		if ($type === 'reservations')
		{
			if ($valeur === '' && in_array($nomChamp, array('date', 'date_fin', 'heure_debut', 'heure_fin', 'ressource', 'description', 'type_reservation'), true) && $nomChamp !== 'date_fin') {
				$erreur = 'Valeur obligatoire';
			} elseif (in_array($nomChamp, array('date', 'date_fin'), true) && $valeur !== '') {
				$dateValide = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valeur, $partiesDate)
					&& checkdate((int) $partiesDate[2], (int) $partiesDate[3], (int) $partiesDate[1]);
				if (!$dateValide) {
					$erreur = 'Date invalide (format attendu : YYYY-MM-DD)';
				}
			} elseif ($nomChamp === 'heure_debut' && !preg_match('/^\d{2}:\d{2}$/', $valeur)) {
				$erreur = 'Heure invalide (format attendu : HH:MM)';
			} elseif ($nomChamp === 'heure_fin' && !preg_match('/^\d{2}:\d{2}$/', $valeur)) {
				$erreur = 'Heure invalide (format attendu : HH:MM)';
			} elseif ($nomChamp === 'ressource') {
				// Vérifier si la ressource existe dans la base de données
				$nomRessource = SecuChaine::ProtectDataSql($valeur);
				if (grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_room WHERE room_name = '$nomRessource'") <= 0) {
					$erreur = 'Ressource inexistante';
				}
			} elseif ($nomChamp === 'description' && $longueur > 65535) {
				$erreur = 'Description trop longue';
			} elseif ($nomChamp === 'type_reservation') {
				// Vérifier si le type de réservation existe dans la base de données
				$lettreType = SecuChaine::ProtectDataSql($valeur);
				if (grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_type_area WHERE type_letter = '$lettreType'") <= 0) {
					$erreur = 'Type de réservation inexistant';
				}
			}
			
		}

		return array('value' => $valeur, 'valid' => $erreur === '', 'error' => $erreur);
	}



	public static function CreationOuModification($type, $colonnesCorrespondantes, $ligne)
	{
		$valeurs = array();
		foreach ($colonnesCorrespondantes as $colonne => $champ) {
			$valeurs[Adm_Import::NomsChamps($champ)] = trim((string) ($ligne[$colonne] ?? ''));
		}

		// Controle si le goupe existe déjà dans la base de données
		if ($type === 'groupes' && !empty($valeurs['gr_nom'])) {
			$nom = SecuChaine::ProtectDataSql($valeurs['gr_nom']);
			return grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_groupes WHERE nom = '$nom'") > 0;
		}

		// Controle si l'utilisateur existe déjà dans la base de données
		if ($type === 'utilisateurs' && !empty($valeurs['user_identifiant'])) {
			$identifiant = SecuChaine::ProtectDataSql($valeurs['user_identifiant']);
			return grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '$identifiant'") > 0;
		}

		// Controle si le type de réservation existe déjà dans la base de données
		if ($type === 'types' && !empty($valeurs['type_identifiant'])) {
			$lettreType = SecuChaine::ProtectDataSql($valeurs['type_identifiant']);
			return grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_type_area WHERE type_letter = '$lettreType'") > 0;
		}

		return false;
	}


	public static function EnregistrementLigne($type, $colonnesCorrespondantes, $ligne, $existant, $separateurMultiples = ',', &$messageErreur = null)
	{
		$valeurs = Adm_Import::ImportValeurs($colonnesCorrespondantes, $ligne);
		$valeursSql = array();
		foreach ($valeurs as $champ => $valeur) {
			$valeursSql[$champ] = SecuChaine::ProtectDataSql($valeur);
		}

		// Import des groupes
		if ($type === 'groupes') {
			$nom = $valeursSql['gr_nom'];
			$colonnes = array(
				"nom = '$nom'",
				"description = '".($valeursSql['gr_description'] ?? '')."'",
				"archive = '".($valeursSql['gr_archiver'] ?? '0')."'",
			);
			if ($existant) {
				return grr_sql_query("UPDATE ".TABLE_PREFIX."_groupes SET ".implode(', ', $colonnes)." WHERE nom = '$nom'");
			}
			return grr_sql_query("INSERT INTO ".TABLE_PREFIX."_groupes SET ".implode(', ', $colonnes));
		}

		// Import des utilisateurs
		if ($type === 'utilisateurs') {
			$identifiant = $valeursSql['user_identifiant'];
			$colonnes = array(
				'nom' => "nom = '".($valeursSql['user_nom'] ?? '')."'",
				'prenom' => "prenom = '".($valeursSql['user_prenom'] ?? '')."'",
				'statut' => "statut = '".($valeursSql['user_statut'] ?? '')."'",
				'email' => isset($valeursSql['user_email']) ? "email = '".$valeursSql['user_email']."'" : '',
				'password' => isset($valeursSql['user_mot_de_passe']) ? "password = '".SecuChaine::ProtectDataSql(password_hash($valeurs['user_mot_de_passe'], PASSWORD_DEFAULT))."'" : '',
				'source' => isset($valeursSql['user_authentification']) ? "source = '".$valeursSql['user_authentification']."'" : "source = 'local'",
				'etat' => isset($valeursSql['user_actif']) ? "etat = '".$valeursSql['user_actif']."'" : "etat = 'actif'",
				'changepwd' => isset($valeursSql['user_changement_mdp']) ? "changepwd = '".$valeursSql['user_changement_mdp']."'" : "changepwd = '0'",
				'desactive_mail' => isset($valeursSql['user_desactiver_mails']) ? "desactive_mail = '".$valeursSql['user_desactiver_mails']."'" : "desactive_mail = '0'",
				'commentaire' => isset($valeursSql['user_commentaire']) ? "commentaire = '".$valeursSql['user_commentaire']."'" : ''
			);
			$colonnes = array_filter($colonnes);
			if ($existant) {
				$enregistre = grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET ".implode(', ', $colonnes)." WHERE login = '$identifiant'");
			} else {
				$colonnes['login'] = "login = '$identifiant'";
				$colonnes['etat'] = "etat = 'actif'";
				$colonnes['source'] = "source = 'local'";
				$enregistre = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_utilisateurs SET ".implode(', ', $colonnes));
			}

			if (!$enregistre || !array_key_exists('user_groupes', $valeurs)) {
				if (!$enregistre) {
					$messageErreur = grr_sql_error();
				}
				return $enregistre;
			}

			if (!grr_sql_query("DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login = '$identifiant'")) {
				$messageErreur = grr_sql_error();
				return false;
			}

			$nomsGroupes = array_map('trim', explode($separateurMultiples, $valeurs['user_groupes']));
			foreach ($nomsGroupes as $nomGroupe) {
				if ($nomGroupe === '') {
					continue;
				}
				$nomGroupeProtege = SecuChaine::ProtectDataSql($nomGroupe);
				$idGroupe = (int) grr_sql_query1("SELECT idgroupes FROM ".TABLE_PREFIX."_groupes WHERE nom = '$nomGroupeProtege'");
				if ($idGroupe <= 0 || !grr_sql_query("INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login = '$identifiant', idgroupes = $idGroupe")) {
					$messageErreur = $idGroupe <= 0 ? 'Groupe introuvable : '.$nomGroupe : grr_sql_error();
					return false;
				}
			}

			return true;
		}

		// Import des types de réservation
		if ($type === 'types') {
			$identifiant = $valeursSql['type_identifiant'];
			$colonnes = array(
				"type_name = '".($valeursSql['type_nom'] ?? '')."'",
				"type_letter = '$identifiant'",
				"order_display = '".($valeursSql['type_ordre'] ?? '0')."'",
				"disponible = '".($valeursSql['type_disponible'] ?? '2')."'",
				"couleurhexa = '".($valeursSql['type_couleur_fond'] ?? '#FFFFFF')."'",
				"couleurtexte = '".($valeursSql['type_couleur_texte'] ?? '#000000')."'",
				"couleuricone = '".($valeursSql['type_couleur_icones'] ?? '#000000')."'",
			);
			if ($existant) {
				return grr_sql_query("UPDATE ".TABLE_PREFIX."_type_area SET ".implode(', ', $colonnes)." WHERE type_letter = '$identifiant'");
			}
			return grr_sql_query("INSERT INTO ".TABLE_PREFIX."_type_area SET ".implode(', ', $colonnes));
		}

		// Import des réservations
		if ($type === 'reservations') {
			$date = $valeurs['date'];
			$dateFin = !empty($valeurs['date_fin']) ? $valeurs['date_fin'] : $date;
			$heureDebut = str_replace('h', ':', strtolower($valeurs['heure_debut']));
			$heureFin = str_replace('h', ':', strtolower($valeurs['heure_fin']));
			$partiesHeureDebut = explode(':', $heureDebut);
			$partiesHeureFin = explode(':', $heureFin);
			$heure_deb = (int) ($partiesHeureDebut[0] ?? 0);
			$minute_deb = (int) ($partiesHeureDebut[1] ?? 0);
			$heure_fin = (int) ($partiesHeureFin[0] ?? 0);
			$minute_fin = (int) ($partiesHeureFin[1] ?? 0);
			$debut = strtotime($date.' '.$heureDebut);
			$fin = strtotime($dateFin.' '.$heureFin);
			$nomRessource = SecuChaine::ProtectDataSql(strtoupper($valeurs['ressource']));
			$idRessource = (int) grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name = '$nomRessource'");
			if ($idRessource <= 0 || $debut === false || $fin === false) {
				return false;
			}
			$description = SecuChaine::ProtectDataSql($valeurs['description'] ?? '');
			$typeReservation = SecuChaine::ProtectDataSql($valeurs['type_reservation'] ?? 'A');
			//if ($existing) {
			//    $entryId = (int) grr_sql_query1("SELECT e.id FROM ".TABLE_PREFIX."_entry e WHERE e.start_time = ".(int) $start." AND e.end_time = ".(int) $end." AND e.room_id = $roomId");
			//    return $entryId > 0 && grr_sql_query("UPDATE ".TABLE_PREFIX."_entry SET name = '$name', type = '$reservationType' WHERE id = $entryId");
			//}
			//return grr_sql_query("INSERT INTO ".TABLE_PREFIX."_entry SET start_time = ".(int) $start.", end_time = ".(int) $end.", room_id = $roomId, description = '$name', type = '$reservationType', create_by = 'Administrateur', beneficiaire = 'Administrateur', beneficiaire_ext = 'Administrateur'");
			return Adm_import::ReservationAjout($idRessource, $date, $heure_deb, $minute_deb, $heure_fin, $minute_fin, $description, $typeReservation);
		}

		return false;
	}


	public static function ReservationPossible($colonnesCorrespondantes, $ligne)
	{
		$valeurs = Adm_Import::ImportValeurs($colonnesCorrespondantes, $ligne);
		if (empty($valeurs['date']) || empty($valeurs['heure_debut']) || empty($valeurs['heure_fin']) || empty($valeurs['ressource'])) {
			return '';
		}

		$nomRessource = SecuChaine::ProtectDataSql(strtoupper($valeurs['ressource']));
		$idRessource = (int) grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name = '$nomRessource'");
		if ($idRessource <= 0) {
			return 'Ressource inexistante';
		}

		$dateFin = !empty($valeurs['date_fin']) ? $valeurs['date_fin'] : $valeurs['date'];
		$heureDebut = strtotime($valeurs['date'].' '.$valeurs['heure_debut']);
		$heureFin = strtotime($dateFin.' '.$valeurs['heure_fin']);
		if ($heureDebut === false || $heureFin === false || $heureFin <= $heureDebut) {
			return 'Plage horaire invalide';
		}

		if ($heureDebut < time()) {
			return get_vocab("booking_in_past");
		}

		$creneauOccupe = mrbsCheckFree($idRessource, $heureDebut, $heureFin, 0, 0, "../");
		return $creneauOccupe ? 'Le créneau est déjà occupé' : '';
	}


	public static function LigneValide($ligneValidee)
	{
		foreach ($ligneValidee as $cellule) {
			if (!$cellule['valid']) {
				return false;
			}
		}

		return true;
	}


	private static function ImportValeurs($colonnesCorrespondantes, $ligne)
	{
		$valeurs = array();
		foreach ($colonnesCorrespondantes as $colonne => $champ) {
			$valeurs[Adm_Import::NomsChamps($champ)] = trim((string) ($ligne[$colonne] ?? ''));
		}

		return $valeurs;
	}


	private static function CorrectionDate($valeur)
	{
		$valeur = trim((string) $valeur);
		if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $valeur, $parties)) {
			return $parties[3].'-'.$parties[2].'-'.$parties[1];
		}

		return $valeur;
	}


	public static function CorrectionLigne($type, $colonnesCorrespondantes, $ligne)
	{
		if ($type === 'reservations') {
			$colonneDateDebut = null;
			$colonneDateFin = null;

			foreach ($colonnesCorrespondantes as $colonne => $champ) {
				$nomChamp = Adm_Import::NomsChamps($champ);
				if ($nomChamp === 'date') {
					$colonneDateDebut = $colonne;
					$ligne[$colonne] = Adm_Import::CorrectionDate($ligne[$colonne] ?? '');
				} elseif ($nomChamp === 'date_fin') {
					$colonneDateFin = $colonne;
					$ligne[$colonne] = Adm_Import::CorrectionDate($ligne[$colonne] ?? '');
				} elseif (in_array($nomChamp, array('heure_debut', 'heure_fin'), true)) {
					$ligne[$colonne] = preg_replace('/^([01]\d|2[0-3])h([0-5]\d)$/i', '$1:$2', trim((string) ($ligne[$colonne] ?? '')));
				}
			}

			if ($colonneDateFin !== null && $colonneDateDebut !== null && trim((string) ($ligne[$colonneDateFin] ?? '')) === '') {
				$ligne[$colonneDateFin] = $ligne[$colonneDateDebut] ?? '';
			}

			return $ligne;
		}

		if ($type !== 'utilisateurs') {
			return $ligne;
		}

		foreach ($colonnesCorrespondantes as $colonne => $champ) {
			if (Adm_Import::NomsChamps($champ) === 'user_statut' && !in_array(strtolower(trim((string) ($ligne[$colonne] ?? ''))), array('visiteur', 'utilisateur', 'administrateur', 'gestionnaire_utilisateur'), true)) {
				$ligne[$colonne] = 'visiteur';
			}
		}

		return $ligne;
	}


	private static function ReservationAjout($idRessource, $date, $heure_deb, $minute_deb, $heure_fin, $minute_fin, $description, $type)
	{
		global $max_rep_entrys, $erreur;

		$journee	= 86400;
		$semaine	= 86400*7;
		$erreur		= '';
		$id			= null; // nouvelle réservation
		$ampm		= NULL; // AMPM ou 24h
		$txtRetour	= "";
		// détermination du type de réservation
		//$type_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_type_area WHERE type_name='".$type."'");
		// voir pour traiter les erreurs 	

		// détermination du début de la réservation
		// on convertit la date en année, mois, jour
		$annee = substr($date, 0, 4); settype($annee, "integer");
		$mois = substr($date, 5, 2); settype($mois, "integer");
		$jour = substr($date, 8, 2); settype($jour, "integer");

		settype($heure_deb,"integer");settype($minute_deb,"integer");
		$debutReservation = mktime($heure_deb, $minute_deb, 0, $mois, $jour, $annee);

		// vérification du début
		if($debutReservation < Settings::get("begin_bookings"))
			$erreur = 'y';

		// détermination de la fin
		settype($heure_fin,"integer");settype($minute_fin,"integer");
		$finReservation = mktime($heure_fin, $minute_fin, 0, $mois, $jour, $annee);

		// vérification de la fin
		if($finReservation > Settings::get("end_bookings"))
			$erreur = 'y';

		if ($finReservation <= $debutReservation)
			$erreur = 'y';


		$statut_entry = "-";
		$rep_jour_c = 0;

		$create_by = "Administrateur";
		$beneficiaire = "Administrateur";
		$benef_ext_nom = "Administrateur";
		$benef_ext_email = "";
		$beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);

		$room_back =NULL; if (isset($room_back)) settype($room_back,"integer");
		$option_reservation = NULL;
		if (isset($option_reservation))
			settype($option_reservation,"integer");
		else
			$option_reservation = -1;

		// On récupère la zone de la ressource
		$zone = mrbsGetRoomArea($idRessource);
		if(($idRessource<=0) || ($zone<=0))
		{
			$erreur="Erreur de salle";
			return array(false, $txtRetour);
		}
		
		$idIgnore = 0;

		# Acquire mutex to lock out others trying to book the same slot(s).
		if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
			fatal_error(1, get_vocab('failed_to_acquire'));

		$dateActuelle = time();
		$erreurReservationPasse = false;
		$erreurRessourceIndisponible = false; // note si la ressource est disponible
		$erreurDureeMaxZone = 'no';
		$erreurDelaiMaxRessource = 'no';
		$erreurDelaiMinRessource = 'no';
		$erreurDateOptionReservation = 'no';
		$erreurChevauchement = 'no';
		$erreurQuiPeutReserverPour = 'no';
		$erreurHeureDebutFin = false;
		$erreurConflit = '';

		// on vérifie que le créneau est bien libre
		$creneauOccupe=mrbsCheckFree($idRessource, $debutReservation, $finReservation, 0, 0,"../");
		// echo date('c',$debutReservation)." ".date('c',$finReservation)."</br>";
		// echo $libre ;
		//echo "créneau libre ";
		if($creneauOccupe){
			$txtRetour .= "Créneau occupé".$creneauOccupe."</br>";
			$erreurRessourceIndisponible = true ;
		}
		else
			$txtRetour .= "Créneau libre </br>";

		// on vérifie qu'on ne réserve pas dans le passé
		$erreurReservationPasse = $debutReservation < $dateActuelle ;
		// Si il y a tentative de réserver dans le passé
		if ($erreurReservationPasse) {
			$dateTexte = utf8_strftime("%d %B %Y, %H:%M", $dateActuelle);
			$erreur=get_vocab("booking_in_past");
			$erreur.= ",".get_vocab("booking_in_past_explain") . $dateTexte;
			return array(false, $txtRetour);
		}

		if (empty($erreurConflit)
			and (!$erreurReservationPasse)
			and ($erreurDureeMaxZone == 'no')
			and ($erreurDelaiMaxRessource == 'no')
			and ($erreurDelaiMinRessource == 'no')
			and (!$erreurRessourceIndisponible)
			and ($erreurDateOptionReservation == 'no')
			and ($erreurChevauchement == 'no')
			and ($erreurQuiPeutReserverPour == 'no')
			and (!$erreurHeureDebutFin)
			)
		{
			// l'utilisateur est gestionnaire ou admin de la ressource donc on ne modère pas !
			$entry_moderate = 0;
			$send_mail_moderate = 0;	
			$entry_type = 0; // réservation isolée
			$repeat_id = 0; 
			$name = $description;
			$overload_data = '';
			$moderate = 0;
			$keys = 0;
			$courrier = 0;
			$nbmaxparticipant = 0;
			$txtRetour .= date('c',$debutReservation)." ".date('c',$finReservation)."</br>";
			$ecriture=mrbsCreateSingleEntry(0,$debutReservation, $finReservation, $entry_type, $repeat_id, $idRessource, $create_by, $beneficiaire, $beneficiaire_ext,
							$name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, $keys, $courrier, $nbmaxparticipant);
			$txtRetour .= $ecriture."</br>";
			return array(true, $txtRetour);
		}

		grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

		// Si l'utilisateur tente de réserver une ressource non disponible
		if ($erreurRessourceIndisponible) {
			$erreur.=  get_vocab("norights");
			$erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
			return array(false, $txtRetour);
		}

		if(strlen($erreurConflit))
		{

			$txtRetour .= "<h2>" . get_vocab("sched_conflict") . "</h2>";
			if(!isset($hide_title))
			{
				$txtRetour .= get_vocab("conflict");
				$txtRetour .= "<UL>";
			}
			$txtRetour .= $erreurConflit;

			if(!isset($hide_title))
				$txtRetour .= "</UL>";
				// possibilité de supprimer la (les) réservation(s) afin de valider la nouvelle réservation.
				if(SecuAccess::UserLevel(getUserName(),$zone,'area') >= 4)
					$txtRetour .= "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='".traite_grr_url("","y")."app.php?p=editentreetrt&amp;".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h4></td></tr></table></center><br />";
		}
		return array(true, $txtRetour);
	}

}

?>