<?php

	require '../config.php';
	
	list($langjs,$dummy) =explode('_', $langs->defaultlang);
	
	readfile(dol_buildpath('/fullcalendar/lib/moment/min/moment.min.js'));
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.js'));
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js'));
	
	$defaultView='month';
	
?>

$(document).ready(function() {
	$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.css',1) ?>" type="text/css" />');
		$('table.cal_month').hide();	
		
		$('table.cal_month').after('<div id="fullcalendar"></div>');
		
		$('#fullcalendar').fullCalendar({
	        header:{
	        	left:   'title',
			    center: 'agendaDay,agendaWeek,month',
			    right:  'prev,next today'
	        }
	        ,lang: 'fr'
	        ,weekNumbers:true
	        ,defaultView:'<?php echo $defaultView ?>'
	        ,events : '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
	        ,eventDrop:function( event, delta, revertFunc, jsEvent, ui, view ) { 
	        	console.log(delta);
	        	
	        	$.ajax({
	        		url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
	        		,data:{
						put:'event'
						,id:event.id
						,data:delta._data
	        		}
	        	})
	        }
	        ,eventResize:function( event, delta, revertFunc, jsEvent, ui, view ) { 
	        	console.log(delta);
	
	        	$.ajax({
	        		url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
	        		,data:{
						put:'event-resize'
						,id:event.id
						,data:delta._data
	        		}
	        	})
	        }
	        
	        
	    })		
	    
	    
	    
		
});
