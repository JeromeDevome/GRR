    <script type='text/javascript'>
		
		function getQueryVariable(variable)
		{
		   var query = window.location.search.substring(1);
		   var vars = query.split("&");
		   for (var i=0;i<vars.length;i++) {
				   var pair = vars[i].split("=");
				   if(pair[0] == variable){return pair[1];}
		   }
		   return(false);
		}
		
		$(document).ready(function() {  
  
            var userLang = navigator.language || navigator.userLanguage;  
  
            var options = $.extend({},   
                $.datepicker.regional[userLang], {  
                    dateFormat: 'dd-mm-yy',
					inline: true,
					showWeek: true,
					changeMonth: true,
					changeYear: true,
					onSelect: function(dateText, inst) { 
						var date = $(this).datepicker('getDate'),
							day  = date.getDate(),  
							month = date.getMonth() + 1,              
							year =  date.getFullYear();
						var area = getQueryVariable("area");
						var hostname = window.location.host;
						var protocol = window.location.protocol;
						self.location.replace(protocol +"//"+ hostname +"/day.php?area="+ area +"&day="+ day +"&year="+ year +"&month="+ month);
						}  
                } 
            );  
  
            $("#calendar").datepicker(options);  
            
            $(function() {	
				$( "#datepicker" ).datepicker({
			});
			
			// Coloration des semaines au passage de la souris sur le numéro des semaines
			$(document).on("mouseenter",".ui-datepicker-week-col",
						   function(){$(this).siblings().find("a").addClass('ui-state-hover');} );
			$(document).on("mouseleave",".ui-datepicker-week-col",
						   function(){$(this).siblings().find("a").removeClass('ui-state-hover');} );
			
			// Selection de la semaine au clic sur le nombre d'une semaine, puis récupération de la première date
			$(document).on("click",".ui-datepicker-week-col",
			   function(){
				   $first = $(this).siblings().find("a").first();
				   $first.click();
				   $parentFirst = $first.parent();
				   var area = getQueryVariable("area");
				   var hostname = window.location.host;
				   var protocol = window.location.protocol;
				   var day  = $first.text(),  
					   month = $parentFirst.data("month")+1,              
					   year =  $parentFirst.data("year");
				   self.location.replace(protocol +"//"+ hostname +"/week_all.php?area="+ area +"&day="+ day +"&year="+ year +"&month="+ month);
					});
				});
        });
		

	</script>
