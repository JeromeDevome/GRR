/*
 * ./js/functions.js
 * fichier Bibliothèque de fonctions Javascript de GRR
 * Dernière modification : $Date: 2021-04-20 14:51$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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
 
// Permet de faire une validation afin que l'usager ne puisse pas sélectionner un jour invalide pour le début du premier Jours/Cycle
function verifierJoursCycles()
{
	valeurA = document.getElementById('jourDebut').value;
	valeurB = document.getElementById('nombreJours').value;
	if (parseInt(valeurA) > parseInt(valeurB))
	{
		alert('La date du premier jour est invalide');
		return false;
	}
	else
	{
		return true;
	}
}
function clicMenu(num)
{
	var fermer;var ouvrir;var menu;
	menu = document.getElementById('menu' + num);
	if (document.getElementById('fermer'))
		fermer = document.getElementById('fermer');
	if (document.getElementById('ouvrir'))
		ouvrir = document.getElementById('ouvrir');
	// On ouvre ou ferme
	if (menu)
	{
		if (menu.style.display == "none")
		{
			if (fermer)
				fermer.style.display = "";
			if (ouvrir)
				ouvrir.style.display = "none";
			menu.style.display = "";
		}
		else
		{
			menu.style.display = "none";
			if (fermer)
				fermer.style.display = "none";
			if (ouvrir)
				ouvrir.style.display = "";
		}
	}
}
function centrerpopup(page,largeur,hauteur,options)
{
// les options :
//    * left=100 : Position de la fenêtre par rapport au bord gauche de l'écran.
//    * top=50 : Position de la fenêtre par rapport au haut de l'écran.
//    * resizable=x : Indique si la fenêtre est redimensionnable.
//    * scrollbars=x : Indique si les barres de navigations sont visibles.
//    * menubar=x : Indique si la barre des menus est visible.
//    * toolbar=x : Indique si la barre d'outils est visible.
//    * directories=x : Indique si la barre d'outils personnelle est visible.
//    * location=x : Indique si la barre d'adresse est visible.
//    * status=x : Indique si la barre des status est visible.
//
// x = yes ou 1 si l'affirmation est vrai ; no ou 0 si elle est fausse.
var top = (screen.height - hauteur) / 2;
var left = (screen.width - largeur) / 2;
window.open(page,"","top="+top+",left="+left+",width="+largeur+",height="+hauteur+",directories=no,toolbar=no,menubar=no,location=no,"+options);
}
/**
 * Displays an confirmation box beforme to submit a query
 * This function is called while clicking links
 *
 * @param   object   the link
 * @param   object   the sql query to submit
 * @param   object   the message to display
 *
 * @return  boolean  whether to run the query or not
 */
 function confirmlink(theLink, theSqlQuery, themessage)
 {
 	var is_confirmed = confirm(themessage + ' :\n' + theSqlQuery);
 	if (is_confirmed) {
 		theLink.href += '&js_confirmed=1';
 	}
 	return is_confirmed;
 }
// end of the 'confirmLink()' function
function confirmButton(theform,themessage)
{
	var is_confirmed = window.confirm(themessage);
	if (is_confirmed)
	{
		document.forms[theform].submit();
	}
	return is_confirmed;
}
// end of the 'confirmButton()' function
/**
 * Checks/unchecks les boîtes à cocher
 *
 * the_form   string   the form name
 * do_check   boolean  whether to check or to uncheck the element
 * day la valeur de la boîte à cocher ou à décocher
 * return  boolean  always true
 */
 function setCheckboxesGrr(elts, do_check, day)
 {
 	for (i = 0; i < elts.length; i++)
 	{
 		type = elts.type;
 		if (type="checkbox")
 		{
 			if ((elts[i].value== day) || (day=='all'))
 			{
 				elts[i].checked = do_check;
 			}
 		}
 	}
 	return true;
} // end of the 'setCheckboxes()' function
 function setCheckboxesGrrName(elts, do_check, day)
 {
 	for (i = 0; i < elts.length; i++)
 	{
 		type = elts.type;
 		if (type="checkbox")
 		{
 			if (elts[i].name== day)
 			{
 				elts[i].checked = do_check;
 			}
 		}
 	}
 	return true;
} // end of the 'setCheckboxes()' function
// end of the 'setCheckboxes()' function
// Les quatre fonctions qui suivent servent à enregistrer un cookie
// Elles sont utilisées par edit_entry.php pour conserver les informations de la saisie pour
// pouvoir les récupérer lors d'une erreur.
//Hugo
// Voir http://www.howtocreate.co.uk/jslibs/script-saveformvalues
// les erreurs constatées lors de l'utilisation de champs additionnels sont prévisibles : howtocreate déconseille l'utilisation des scripts lorsque le formulaire est calculé par Javascript :-(
var FS_INCLUDE_NAMES = 0, FS_EXCLUDE_NAMES = 1, FS_INCLUDE_IDS = 2, FS_EXCLUDE_IDS = 3, FS_INCLUDE_CLASSES = 4, FS_EXCLUDE_CLASSES = 5;
function retrieveCookie(cookieName)
{
	/* retrieved in the format
	cookieName4=value; cookieName3=value; cookieName2=value; cookieName1=value
	only cookies for this domain and path will be retrieved */
	var cookieJar = document.cookie.split( "; " );
	for( var x = 0; x < cookieJar.length; x++ )
	{
		var oneCookie = cookieJar[x].split( "=" );
		if (oneCookie[0] == escape(cookieName))
		{
			return oneCookie[1] ? unescape(oneCookie[1]) : '';
		}
	}
	return null;
}
function setCookie(cookieName, cookieValue, lifeTime, path, domain, isSecure)
{
	if (!cookieName)
	{
		return false;
	}
	if (lifeTime == "delete" )
	{
		lifeTime = -10;
	}
	//this is in the past. Expires immediately.
	/* This next line sets the cookie but does not overwrite other cookies.
	syntax: cookieName=cookieValue[;expires=dataAsString[;path=pathAsString[;domain=domainAsString[;secure]]]]
	Because of the way that document.cookie behaves, writing this here is equivalent to writing
	document.cookie = whatIAmWritingNow + "; " + document.cookie; */
	document.cookie = escape(cookieName) + "=" + escape(cookieValue) + (lifeTime ? ";expires=" + (new Date((new Date()).getTime() + (1000 * lifeTime))).toGMTString() : "") + (path ? ";path=" + path : "") + (domain ? ";domain=" + domain : "") + (isSecure ? ";secure" : "") + "; SameSite=Lax";
	//check if the cookie has been set/deleted as required
	if ( lifeTime < 0 )
	{
		if (typeof(retrieveCookie(cookieName)) == "string")
		{
			return false;
		}
		return true;
	}
	if (typeof(retrieveCookie(cookieName)) == "string")
	{
		return true;
	}
	return false;
}
/* fonction qui est utilisée pour basculer un élément d'une liste1 vers une liste2 et inversement (utilisé lors de la création d'une demande) */
function Deplacer(liste1, liste2)
{
	while (liste1.options.selectedIndex >= 0)
	{
		opt = new Option(liste1.options[liste1.options.selectedIndex].text,liste1.options[liste1.options.selectedIndex].value);
		liste2.options[liste2.options.length] = opt;
		liste1.options[liste1.options.selectedIndex] = null;
	}
}
function vider_liste(IdListe)
{
	var l = IdListe.options.length;
	for (var i = 0; i < l; i++)
	{
		IdListe.options[i] = null;
	}
}
function selectionner_liste(IdListe)
{
	var l = IdListe.options.length;
	for(var i = 0; i < l; i++)
	{
		IdListe.options[i].selected = true;
	}
}
function afficherMoisSemaine(a)
{
	var Nb = a;
	document.getElementById('afficherBoutonSelection'+Nb).style.display = "none";
	document.getElementById('cacherBoutonSelection'+Nb).style.display = "inline";
	document.getElementById('boutonSelection'+Nb).style.display = "inline";
    $('.floatthead').floatThead('reflow');
}
function cacherMoisSemaine(a)
{
	var Nb = a;
	document.getElementById('cacherBoutonSelection'+Nb).style.display = "none";
	document.getElementById('afficherBoutonSelection'+Nb).style.display = "inline";
	document.getElementById('boutonSelection'+Nb).style.display = "none";
    $('.floatthead').floatThead('reflow');
}

function charger(){
	var test = document.getElementById("chargement");
	test.style.display = 'Block';
}

/*
* Affichage du menu à gauche ou en haut
* Paramètre mode: 0 = menu caché; 1 menu à gauche; 2 menu en haut
*/
function afficheMenuHG(mode){
    var w = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    var menuGw,planningw,realmode;
    if (w < 992){
        realmode = 2;
    }
    else if (w < 1240){
        realmode = mode;
        menuGw = "25%";
        planningw = "75%";
    }
    else {
        realmode = mode;
        menuGw = "20%";
        planningw = "80%";
    }
    if (mode == 0) /* menus cachés */
    {
        document.getElementById("menuHaut").style.display = "none";
        document.getElementById("menuGauche2").style.display = "none";
        document.getElementById("planning2").style.width = "100%";
        document.getElementById("cacher").style.display = "none";
        document.getElementById("voir").style.display = "inline-block";
        $('.floatthead').floatThead('reflow');
    }
    else if (mode == 1) /* menu affiché à gauche*/
    {
        document.getElementById("menuHaut").style.display = "none";
        document.getElementById("menuGauche2").style.display = "inline-block";
        document.getElementById("menuGauche2").style.width = menuGw;
        document.getElementById("planning2").style.width = planningw;
        document.getElementById("cacher").style.display = "inline-block";
        document.getElementById("voir").style.display = "none";
        $('.floatthead').floatThead('reflow');
    }
    else if (mode == 2) /* menu affiché en haut */
    {
        document.getElementById("menuHaut").style.display = "inline-block";
        document.getElementById("menuGauche2").style.display = "none";
        document.getElementById("planning2").style.display = "inline-block";
        document.getElementById("planning2").style.width = "100%";
        document.getElementById("cacher").style.display = "inline-block";
        document.getElementById("voir").style.display = "none";
        $('.floatthead').floatThead('reflow');
    }
}

/*
 *Fonction permettant l'ouverture d'un PopUP de la page view entry.php pour création d'un pdf
 */
function lienPDF(id) {
    var myWindow = window.open("app.php?p=pdfgenerator&id="+id+"", "_blank", "width=960");
}
/*
 * Fonction faisant basculer l'affichage d'un div de display:inline-block à display:none
 */
function toggle_visibility(id) {
	var e = document.getElementById(id);
	if(e.style.display == 'none')
	   e.style.display = 'inline-block';
	else
	   e.style.display = 'none';
 }
 /*
 *Menu*
 */
 function setCookie(e,t,n){var r=new Date;r.setDate(r.getDate()+n);var i=escape(t)+(n==null?"":"; expires="+r.toUTCString());document.cookie=e+"="+i}function getCookie(e){var t,n,r,i=document.cookie.split(";");for(t=0;t<i.length;t++){n=i[t].substr(0,i[t].indexOf("="));r=i[t].substr(i[t].indexOf("=")+1);n=n.replace(/^\s+|\s+$/g,"");if(n==e){return unescape(r)}}}$(document).ready(function(){$("#open").click(function(){var e=$("div#panel").is(":hidden");if(e)$("div#panel").show("slow");else $("div#panel").hide("slow");setCookie("open",e,365)});var e=getCookie("open");if(e=="true"){$("div#panel").show()}else{$("div#panel").hide()}})