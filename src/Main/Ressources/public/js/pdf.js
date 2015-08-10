/*
 *Fichier permettant l'ouverture d'un PopUP de la page view entry.php
 */
function myFunction(id) {
    var myWindow = window.open("pdfgenerator.php?id="+id+"", "_blank", "width=960");
}

 //~ function generationpdf()
 //~ {
   //~ var pdf = new jsPDF('p','pt','a4');
//~ 
//~ pdf.addHTML(document.getElementById('popup_name'),function() {
//~ pdf.output('dataurl');
//~ });
//~ }
