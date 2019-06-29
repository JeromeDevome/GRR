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
		
		var actualDate = getQueryVariable("day") + '-' + getQueryVariable("month") + '-' + getQueryVariable("year");
		
		$(document).ready(function() {  
  
            // var userLang = navigator.language || navigator.userLanguage;  
			var lang = "<?php echo $langue; ?>";
			if (lang == "fr"){
				var userLang = "fr";
			} else if (lang == "en"){
				var userLang = "en";
			} else if (lang == "de"){
				var userLang = "de";
			} else if (lang == "it"){
				var userLang = "it";
			} else if (lang == "es"){
				var userLang = "es";
			} else { var userLang = "fr";} // modification proposée par Steven Boriboun sur Github
		
            var options = $.extend({},   
                $.datepicker.regional[userLang], {  
                    dateFormat: 'dd-mm-yy',
					inline: true,
					showWeek: true,
					changeMonth: true,
					changeYear: true,
					showOn: "button",
					showButtonPanel: true,
					minDate: new Date(<?php echo $byear; ?>,<?php echo $bmonth; ?>,<?php echo $bday; ?>),
					maxDate: new Date(<?php echo $eyear; ?>,<?php echo $emonth; ?>,<?php echo $eday; ?>),
					onChangeMonthYear: function(year,month,instance) {
						var mois = month;
						var annee = year;
							$(document).on("click",".ui-datepicker-next, .ui-datepicker-prev", function(){
							var area = getQueryVariable("area");
							var room = getQueryVariable("room");
							var dir = location.href.replace(/[^/]*$/, '');
							var date = $("#calendar").datepicker("getDate");
								day  = date.getDate();
							if(room){
								self.location.replace(dir +"month.php?room="+ room +"&day="+ day +"&year="+ annee +"&month="+ mois);
							} else {
								self.location.replace(dir +"month_all.php?area="+ area +"&day="+ day +"&year="+ annee +"&month="+ mois);}
							});
					},
					onSelect: function(dateText, inst) { 
						var date = $(this).datepicker('getDate'),
							day  = date.getDate(),  
							month = date.getMonth() + 1,              
							year =  date.getFullYear();
						var area = getQueryVariable("area");
						var room = getQueryVariable("room");
						var dir = location.href.replace(/[^/]*$/, '');					
						if(room){
							self.location.replace(dir +"day.php?room="+ room +"&day="+ day +"&year="+ year +"&month="+ month);
						} else {
							self.location.replace(dir +"day.php?area="+ area +"&day="+ day +"&year="+ year +"&month="+ month);
						}
					}  
                } 
            );
            
            var _gotoToday = jQuery.datepicker._gotoToday;
			jQuery.datepicker._gotoToday = function(a){
				var target = jQuery(a);
				var inst = this._getInst(target[0]);
				_gotoToday.call(this, a);
				jQuery.datepicker._selectDate(a, jQuery.datepicker._formatDate(inst,inst.selectedDay, inst.selectedMonth, inst.selectedYear));
			};
			  
            $("#calendar").datepicker(options);
            $( "#calendar" ).datepicker( "setDate", actualDate );  
            
            $(function() {	
				$( "#datepicker" ).datepicker({
			});
			
			// Highlight week on hover week number
			$(document).on("mouseenter",".ui-datepicker-week-col",
						   function(){$(this).siblings().find("a").addClass('ui-state-hover');} );
			$(document).on("mouseleave",".ui-datepicker-week-col",
						   function(){$(this).siblings().find("a").removeClass('ui-state-hover');} );
			
			// Select week on click on week number
			$(document).on("click",".ui-datepicker-week-col",
			   function(){
				   $first = $(this).siblings().find("a").first();
				   $first.click();
				   $parentFirst = $first.parent();
				   var area = getQueryVariable("area");
				   var room = getQueryVariable("room");
				   var dir = location.href.replace(/[^/]*$/, '');
				   var day  = $first.text(),  
					   month = $parentFirst.data("month")+1,              
					   year =  $parentFirst.data("year");
				   if(room){
						self.location.replace(dir +"week.php?room="+ room +"&day="+ day +"&year="+ year +"&month="+ month);
				   } else {
						self.location.replace(dir +"week_all.php?area="+ area +"&day="+ day +"&year="+ year +"&month="+ month);}
					});
				});
        });
		

	</script>
