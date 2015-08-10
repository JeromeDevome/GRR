$(document).ready(function() {
var monthNames = [ "Enero", "Febrero", "Marzo", "abril", "mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre" ];
var dayNames= ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
var newDate = new Date();
newDate.setDate(newDate.getDate());
$('#Date').prepend(dayNames[newDate.getDay()] + " " + newDate.getDate() + ' ' + monthNames[newDate.getMonth()] + ' ' + newDate.getFullYear());
setInterval( function()
{
	var minutes = new Date().getMinutes();
	$("#min").html(( minutes < 10 ? "0" : "" ) + minutes);
},1000);
setInterval( function()
{
	var hours = new Date().getHours();
	$("#hours").html(( hours < 10 ? "0" : "" ) + hours);
}, 1000);
});