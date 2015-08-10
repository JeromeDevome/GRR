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
 * Checks/unchecks les boites à cocher
 *
 * the_form   string   the form name
 * do_check   boolean  whether to check or to uncheck the element
 * day la valaur de de la boite à cocher ou à décocher
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
function _setCheckboxesGrr(the_form, do_check, day)
{
	var elts = document.forms[the_form];
	for (i = 0; i < elts.elements.length; i++)
	{
		type = elts.elements[i].type;
		if (type="checkbox")
		{
			if ((elts.elements[i].value== day) || (day=='all'))
			{
				elts.elements[i].checked = do_check;
			}
		}
	}
	return true;
}
// end of the 'setCheckboxes()' function
// Les quatre fonctions qui suivent servent à enregistrer un cookie
// Elles sont utilisé par edit_enty.php pour conserver les informations de la saisie pour
// pouvoir les récupérer lors d'une erreur.
//Hugo
// Voir http://www.howtocreate.co.uk/jslibs/script-saveformvalues
var FS_INCLUDE_NAMES = 0, FS_EXCLUDE_NAMES = 1, FS_INCLUDE_IDS = 2, FS_EXCLUDE_IDS = 3, FS_INCLUDE_CLASSES = 4, FS_EXCLUDE_CLASSES = 5;
//Hugo - fonction qui récupère les informations des champs input pour les stocker dans un cookie (Voir http://www.howtocreate.co.uk/jslibs/script-saveformvalues)
function getFormString( formRef, oAndPass, oTypes, oNames )
{
	if (oNames)
	{
		oNames = new RegExp((( oTypes > 3 )?'\\b(':'^(')+oNames.replace(/([\\\/\[\]\(\)\.\+\*\{\}\?\^\$\|])/g,'\\$1').replace(/,/g,'|')+(( oTypes > 3 )?')\\b':')$'),'');
		var oExclude = oTypes % 2;
	}
	for (var x = 0, oStr = '', y = false; formRef.elements[x]; x++)
	{
		if (formRef.elements[x].type)
		{
			if (oNames)
			{
				var theAttr = (oTypes > 3) ? formRef.elements[x].className : ((oTypes > 1) ? formRef.elements[x].id : formRef.elements[x].name);
				if ((oExclude && theAttr && theAttr.match(oNames)) || (!oExclude && !( theAttr && theAttr.match(oNames))))
				{
					continue;
				}
			}
			var oE = formRef.elements[x];var oT = oE.type.toLowerCase();
			if (oT == 'text' || oT == 'textarea' || ( oT == 'password' && oAndPass ) || oT == 'datetime' || oT == 'datetime-local' || oT == 'date' || oT == 'month' || oT == 'week' || oT == 'time' || oT == 'number' || oT == 'range' || oT == 'email' || oT == 'url')
			{
				oStr += ( y ? ',' : '' ) + oE.value.replace(/%/g,'%p').replace(/,/g,'%c');
				y = true;
			}
			else if (oT == 'radio' || oT == 'checkbox')
			{
				oStr += (y ? ',' : '') + (oE.checked ? '1' : '');
				y = true;
			}
			else if (oT == 'select-one')
			{
				oStr += (y ? ',' : '') + oE.selectedIndex;
				y = true;
			}
			else if (oT == 'select-multiple')
			{
				for (var oO = oE.options, i = 0; oO[i]; i++ )
				{
					oStr += (y ? ',' : '') + (oO[i].selected ? '1' : '');
					y = true;
				}
			}
		}
	}
	return oStr;
}
//Hugo - Fonction qui récupère les informations stockées de le cookie pour les remttres dans les inputs (Voir http://www.howtocreate.co.uk/jslibs/script-saveformvalues)
function recoverInputs( formRef, oStr, oAndPass, oTypes, oNames )
{
	if (oStr)
	{
		oStr = oStr.split( ',' );
		if (oNames)
		{
			oNames = new RegExp((( oTypes > 3 )?'\\b(':'^(')+oNames.replace(/([\\\/\[\]\(\)\.\+\*\{\}\?\^\$\|])/g,'\\$1').replace(/,/g,'|')+(( oTypes > 3 )?')\\b':')$'),'');
			var oExclude = oTypes % 2;
		}
		for (var x = 0, y = 0; formRef.elements[x]; x++ )
		{
			if (formRef.elements[x].type)
			{
				if (oNames)
				{
					var theAttr = ( oTypes > 3 ) ? formRef.elements[x].className : ( ( oTypes > 1 ) ? formRef.elements[x].id : formRef.elements[x].name );
					if ((oExclude && theAttr && theAttr.match(oNames)) || (!oExclude && (!theAttr || !theAttr.match(oNames))))
					{
						continue;
					}
				}
				var oE = formRef.elements[x];var oT = oE.type.toLowerCase();
				if (oT == 'text' || oT == 'textarea' || (oT == 'password' && oAndPass) || oT == 'datetime' || oT == 'datetime-local' || oT == 'date' || oT == 'month' || oT == 'week' || oT == 'time' || oT == 'number' || oT == 'range' || oT == 'email' || oT == 'url' )
				{
					oE.value = oStr[y].replace(/%c/g,',').replace(/%p/g,'%');
					y++;
				}
				else if (oT == 'radio' || oT == 'checkbox')
				{
					oE.checked = oStr[y] ? true : false;
					y++;
				}
				else if ( oT == 'select-one')
				{
					oE.selectedIndex = parseInt( oStr[y]);
					y++;
				}
				else if ( oT == 'select-multiple')
				{
					for (var oO = oE.options, i = 0; oO[i]; i++ )
					{
						oO[i].selected = oStr[y] ? true : false;
						y++;
					}
				}
			}
		}
	}
}
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
	document.cookie = escape(cookieName) + "=" + escape(cookieValue) + (lifeTime ? ";expires=" + (new Date((new Date()).getTime() + (1000 * lifeTime))).toGMTString() : "") + (path ? ";path=" + path : "") + (domain ? ";domain=" + domain : "") + (isSecure ? ";secure" : "");
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
/*-----MAJ Loïs THOMAS  --> Fonctions qui permettent de cacher et afficher le menu à gauche -----*/
function divaffiche(month_all2)
{
	var Nbr = month_all2;
	if ( Nbr == 1)
	{
		document.getElementById("menuGaucheMonthAll2").style.display = "block";
		document.getElementById("planningMonthAll2").style.marginLeft = "300px";
		document.getElementById("planningMonthAll2").style.width = "auto";
	}
	else
	{
		document.getElementById("menuGauche").style.display = "block";
		document.getElementById("planning").style.marginLeft = "300px";
		document.getElementById("planning").style.width = "auto";
	}
	document.getElementById("cacher").style.display = "inline";
	document.getElementById("voir").style.display = "none";
}
function divcache(month_all2)
{
	var Nbr = month_all2;
	if (Nbr == 1)
	{
		document.getElementById("menuGaucheMonthAll2").style.display = "none";
		document.getElementById("planningMonthAll2").style.marginLeft = "0px";
		document.getElementById("planningMonthAll2").style.width = "auto";
	}
	else
	{
		document.getElementById("menuGauche").style.display = "none";
		document.getElementById("planning").style.marginLeft = "0px";
		document.getElementById("planning").style.width = "auto";
	}
	document.getElementById("cacher").style.display = "none";
	document.getElementById("voir").style.display = "inline";
}
function afficherMoisSemaine(a)
{
	var Nb = a;
	document.getElementById('afficherBoutonSelection'+Nb).style.display = "none";
	document.getElementById('cacherBoutonSelection'+Nb).style.display = "inline";
	document.getElementById('boutonSelection'+Nb).style.display = "inline";
}
function cacherMoisSemaine(a)
{
	var Nb = a;
	document.getElementById('cacherBoutonSelection'+Nb).style.display = "none";
	document.getElementById('afficherBoutonSelection'+Nb).style.display = "inline";
	document.getElementById('boutonSelection'+Nb).style.display = "none";
}

function charger(){
	var test = document.getElementById("chargement");
	test.style.display = 'Block';
}

