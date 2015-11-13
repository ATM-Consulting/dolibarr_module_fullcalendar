<?php

	require '../config.php';
	dol_include_once('/core/class/html.formactions.class.php');
	//var_dump($user->array_options);
	
	list($langjs,$dummy) =explode('_', $langs->defaultlang);
	
	readfile(dol_buildpath('/fullcalendar/lib/moment/min/moment.min.js'));
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.js'));
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js'));
	
	if(!empty($user->array_options['options_googlecalendarapi'])) {
	//	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar/gcal.js'));
		
	}
	
	ob_start();
	$formactions=new FormActions($db);
	$formactions->select_type_actions(-1, "type_code","systemauto");
	$select_type_action = ob_get_clean();
	
	$form=new Form($db);
	$select_company = $form->select_thirdparty('','fk_soc','',1,1,0);
	$select_user = $form->select_dolusers($user->id, 'fk_user');
	ob_start();
	$form->select_contacts(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
	$select_contact = ob_get_clean();
	
	$defaultView='month';
	$defaultDay = date('d');
?>
if(document.location.href.indexOf('/comm/action/index.php') != -1) {
	
	
	$(document).ready(function() {
		var year = $('form[name=listactionsfilter]').find('input[name=year]').val();
		var month = $('form[name=listactionsfilter]').find('input[name=month]').val();
		var defaultDate = year+'-'+month+'-<?php echo $defaultDay ?>';
		
		$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.css',1) ?>" type="text/css" />');
			$('table.cal_month').hide();	
			$('table.cal_month').prev('table').find('td.titre_right').remove();

			$('table.cal_month').after('<div id="fullcalendar"></div>');
			
			$('#fullcalendar').fullCalendar({
		        header:{
		        	left:   'title',
				    center: 'agendaDay,agendaWeek,month',
				    right:  'prev,next today'
		        }
		        ,defaultDate:defaultDate
		        <?php
			   /* if(!empty($user->array_options['options_googlecalendarapi'])) {
			    	?>
			    	,googleCalendarApiKey: '<?php echo $user->array_options['options_googlecalendarapi']; ?>'
			    	,eventSources: [
		            	{
		                	googleCalendarId: '<?php echo $user->array_options['options_googlecalendarurl']; ?>'
		            	}
		            ]
			    	<?php
			    }*/
				?>
		        
		        ,lang: 'fr'
		        ,weekNumbers:true
				,height: "auto"
		        ,defaultView:'<?php echo $defaultView ?>'
		        ,events : '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$('form[name=listactionsfilter]').serialize() 
				,eventLimit : <?php echo !empty($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW) ? $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW : 3; ?>
				,dayRender:function(date, cell) {

					if(date.format('YYYYMMDD') == moment().format('YYYYMMDD')) {
						cell.css('background-color', '#ddddff');
					}
					else if(date.format('E') >=6) {
						cell.css('background-color', '#999');
					}
					else {
						cell.css('background-color', '#fff');
					}
				}
				,eventRender:function( event, element, view ) {
					
					var note = event.note;
					if(event.fk_soc>0){
						 element.append('<div>'+event.societe+'</div>');
						 note += '<div>'+event.societe+'</div>';
					}
					if(event.fk_contact>0){
						 element.append('<div>'+event.contact+'</div>');
						 note += '<div>'+event.contact+'</div>';
					}
					<?php
					if(!empty($conf->global->FULLCALENDAR_SHOW_AFFECTED_USER)) {
						
						?>
						if(event.fk_user>0){
							 element.append('<div>'+event.user+'</div>');
							 note += '<div>'+event.user+'</div>';
						}
						<?php
						
					}
					
					if(!empty($conf->global->FULLCALENDAR_SHOW_PROJECT)) {
						
						?>
						if(event.fk_project>0){
							 element.append('<div>'+event.project+'</div>');
							 note = '<div>'+event.project+'</div>'+note;
						}
						<?php
						
					}
					
					?>
					if(event.more)  {
						 element.append('<div>'+event.more+'</div>');
					}
					
					element.tipTip({
						maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50  
						,content : '<strong>'+event.title+'</strong><br />'+ note
					});
	
					element.find(".classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
	
				 }
		        ,eventDrop:function( event, delta, revertFunc, jsEvent, ui, view ) { 
		        	console.log(delta);	
		        	
		        	$.ajax({
		        		url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
		        		,data:{
							put:'event-move'
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
		        ,dayClick:function( date, jsEvent, view ) { 
		        	console.log(date.format());
		        	//document.location.href = "<?php echo dol_buildpath('/comm/action/card.php?action=create',1); ?>"
		        	
		        	$('#pop-new-event').remove();
		        	
		        	$div = $('<div id="pop-new-event"></div>');
		        	$div.append("<?php echo strtr(addslashes($select_type_action),array("\n"=>"\\\n")); ?>");
		        	$div.append('<br /><input type="text" name="label" value="" placeholder="<?php echo $langs->trans('Title') ?>" style="width:300px">');
		        	$div.append('<br /><textarea name="note" value="" placeholder="<?php echo $langs->trans('Note') ?>"  style="width:300px" rows="3"></textarea>');
		        	$div.append("<br /><?php echo $langs->trans('Company').' : '.strtr(addslashes($select_company),array("\n"=>"\\\n")); ?>");
		        	$div.append("<br /><?php echo $langs->trans('Contact').' : '.strtr(addslashes('<span rel="contact">'.$select_contact.'</span>'),array("\n"=>"\\\n")); ?>");
		        	$div.append("<br /><?php echo $langs->trans('User').' : '.strtr(addslashes($select_user),array("\n"=>" ","\r"=>"")); ?>");
		        	
		        	$div.find('select[name=fk_soc]').change(function() {
		        		var fk_soc = $(this).val();
		        		
		        		$.ajax({
		        			url: "<?php echo dol_buildpath('/core/ajax/contacts.php?action=getContacts&htmlname=contactid',1) ?>&id="+fk_soc
		        			,dataType:'json'
		        		}).done(function(data) {
		        			$('#pop-new-event span[rel=contact]').html(data.value);
		        		});
		        		
		        	});
		        	
		        	$('body').append($div);
		        		
		        	$('#pop-new-event').dialog({
		        		modal:true
		        		,width:'auto'
		        		,title:"<?php echo $langs->transnoentities('AddAnAction') ?>"
		        		,buttons:{
		        			    "<?php echo $langs->transnoentities('Add') ?>": function() {									if($('#pop-new-event input[name=label]').val() != '') {
										
										$.ajax({
											url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
							        		,data:{
												put:'event'
												,label:$('#pop-new-event input[name=label]').val()
												,note:$('#pop-new-event input[name=note]').val()
												,date:date.format()
												,fk_soc:$('#pop-new-event select[name=fk_soc]').val()
												,fk_contact:$('#pop-new-event select[name=contactid]').val()
												,fk_user:$('#pop-new-event select[name=fk_user]').val()
												,type_code:$('#pop-new-event select[name=type_code]').val()
							        		}
										}).done(function() {
											$('#fullcalendar').fullCalendar( 'refetchEvents' );
											$('#pop-new-event').dialog( "close" );
										});
										
									}
		        			    	
		        			    }
						        ,"<?php echo $langs->transnoentities('Cancel') ?>": function() {
						          $('#pop-new-event').dialog( "close" );
						        }
		        		}
		        	});
		        		
		        }
		        
		    });		
		    
		    
	});
}
