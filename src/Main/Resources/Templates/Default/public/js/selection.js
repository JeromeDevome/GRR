function disableselect(e)
{
	return false
}

function reEnable()
{
	return true
}
function selection()
{
	document.onselectstart = new Function ("return false")
	if (window.sidebar)
	{
		document.onmousedown=disableselect
		document.onclick=reEnable
	}
}
