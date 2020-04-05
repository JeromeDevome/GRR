<?php

echo'
<script type="text/javascript" src="js/jspdf.min.js"></script>
		<script src="js/html2canvas.js"></script>
		<script type="text/javascript">
			var pdf = new jsPDF(\'p\',\'pt\',\'a4\');

			pdf.addHTML(document.body,function() {
				pdf.output(\'dataurl\');
			});
</script>';

?>