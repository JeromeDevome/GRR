jQuery(function($)
{
	$('a.poplight').on('click', function()
	{
		var popID = $(this).data('rel');
		var popWidth = $(this).data('width');
		$('#' + popID).fadeIn().css({ 'width': popWidth});
		var popMargTop = ($('#' + popID).height() + 80) / 2;
		var popMargLeft = ($('#' + popID).width() + 80) / 2;
		$('#' + popID).css({
			'margin-top' : -popMargTop,
			'margin-left' : -popMargLeft,
			'top' : '140px',
			'left' : '600px'
		});
		$('body').append('<div id="fade"></div>');
		$("body,html").animate({scrollTop:0},800);
		return false;
	});
	$('body').on('click', 'a.closepop, #fade', function()
	{
		$('#fade , .popup_block').fadeOut(function()
		{
			$('#fade, a.closepop').remove();
		});
		return false;
	});
	$('body').on('click', 'input.closepop, #fade', function()
	{
		$('#fade , .popup_block').fadeOut(function()
		{
			$('#fade, input.closepop').remove();
		});
		return false;
	});
});

function getXMLHttpRequest()
{
	var xmlhttp = null;
	if (window.XMLHttpRequest || window.ActiveXObject)
	{
		if (window.ActiveXObject)
		{
			try
			{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch(e)
			{
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
		else
		{
			xmlhttp = new XMLHttpRequest();
		}
	}
	else
	{
		alert("Votre navigateur ne supporte pas l'objet XMLHTTPRequest...");
		return null;
	}
	return xmlhttp;
}

function request(id,day,month,year,currentPage,callback)
{
	document.getElementById('popup_name').innerHTML="";
	var Id = id;
	var Day = day;
	var Month = month ;
	var Year = year ;
	var Page = currentPage ;
	var xhr = getXMLHttpRequest();
	xhr.onreadystatechange = function()
	{
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
			callback(xhr.responseText);
	};
	xhr.open("GET","view_entry.php?id="+Id+"&day="+Day+"&month="+Month+"&year="+Year+"&page="+Page+"", true);
	xhr.send(null);
}
function readData(sData)
{
	document.getElementById('popup_name').innerHTML += sData + '<input class=\"closepop btn btn-primary\" type=\"button\" onclick=\"location.href=\'#\'\" title=\"Fermeture\" value=\"Fermer\" ></div> ';
}
