<?php
$refer = '';
if(isset($_SERVER['HTTP_REFERER'])) $refer = $_SERVER['HTTP_REFERER'];

if(empty($refer) || preg_match('/comm\/action\/index.php/', $refer))
{
	require '../config.php';

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	
	$langs->load('fullcalendar@fullcalendar');
	
	if(!empty($conf->global->MAIN_NOT_INC_FULLCALENDAR_HEAD) && empty($_REQUEST['force_use_js'])) exit;

	if(empty($user->rights->fullcalendar->useit)) exit;

	dol_include_once('/core/class/html.formactions.class.php');
	dol_include_once('/core/class/html.formprojet.class.php');
	if (!empty($conf->global->FULLCALENDAR_CAN_UPDATE_PERCENT))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
	}

	list($langjs,$dummy) =explode('_', $langs->defaultlang);

	if($langjs=='en') $langjs = 'en-gb';

	readfile(dol_buildpath('/fullcalendar/lib/moment/min/moment.min.js'));
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.js'));
	
	if(!is_file(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js'))) $langjs = 'en-gb';
	
	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/lang/'.$langjs.'.js'));

	if(!empty($user->array_options['options_googlecalendarapi'])) {
	//	readfile(dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar/gcal.js'));

	}

	ob_start();
	$selected = !empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT) ? $conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT : -1;
	$formactions=new FormActions($db);
	$formactions->select_type_actions($selected, "type_code","systemauto");
	$select_type_action = ob_get_clean();

	$form=new Form($db);
	//$select_company = $form->select_thirdparty('','fk_soc','',1,1,0);
	$select_company = $form->select_company('', 'fk_soc', '', 1);

	$select_user = $form->select_dolusers($user->id, 'fk_user');

	ob_start();
	$form->select_contacts(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
	$select_contact = ob_get_clean();

	ob_start();
	$formProject = new FormProjets($db);
	$select_project = $formProject->select_projects_list(-1, 0, 'fk_project',0,0,1);
	$select_project .= ob_get_clean();

	$defaultDay = date('d');

	if(!empty($conf->global->MAIN_DEFAULT_WORKING_HOURS)) {
		list($hourStart, $hourEnd) = explode('-', $conf->global->MAIN_DEFAULT_WORKING_HOURS);
	}
	if(empty($hourStart)) $hourStart = 8;
	if(empty($hourEnd)) $hourEnd = 18;

	$moreOptions = '';
	$hookmanager->initHooks(array('fullcalendardao'));
	$parameters=array(); $action = 'addEvent'; $object = null;
	$reshook=$hookmanager->executeHooks('addOptionCalendarEvents',$parameters,$object,$action);
	if (! empty($hookmanager->resPrint)) $moreOptions = json_decode($hookmanager->resPrint);

	if (!empty($conf->global->FULLCALENDAR_FILTER_ON_STATE))
	{
		dol_include_once('/core/class/html.formcompany.class.php');
		$formcompany = new FormCompany($db);
	}

?>

	$(document).ready(function() {

		<?php if (!empty($conf->global->FULLCALENDAR_FILTER_ON_STATE)) { ?>
			var select_departement = <?php echo json_encode('<tr><td>'.fieldLabel('State','state_id').'</td><td>'.$formcompany->select_state(GETPOST('state_id'), 'FR').'</td></tr>'); ?>;
			$("#selectstatus").closest("tr").after(select_departement);
		<?php } ?>

		var year = $('form[name=listactionsfilter]').find('input[name=year]').val();
		var month = $('form[name=listactionsfilter]').find('input[name=month]').val();
		var defaultDate = year+'-'+month+'-<?php echo $defaultDay/*.' '.$hourStart.':00'*/ ?>';

		
		var defaultView='month';
		if($('form.listactionsfilter input[name=action]').val() == 'show_week') defaultView = 'agendaWeek';
		if($('form.listactionsfilter input[name=action]').val() == 'show_day') defaultView = 'agendaDay';

		$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.css',1) ?>" type="text/css" />');
		$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/css/fullcalendar.css',1) ?>" type="text/css" />');
		$('table.cal_month').hide();
		$('table.cal_month').prev('table').find('td.titre_right').remove();

		$('table.cal_month').after('<div id="fullcalendar"></div>');
		var currentsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$('form[name=listactionsfilter]').serialize();
		$('#fullcalendar').fullCalendar({
	        header:{
	        	left:   'title',
			    center: 'agendaDay,agendaWeek,month',
			    right:  'prev,next today'
	        }
	        ,defaultDate:defaultDate
	        ,businessHours: {
	        	start:'<?php echo $hourStart.':00'; ?>'
	        	,end:'<?php echo $hourEnd.':00'; ?>'
	        	,dow:[1,2,3,4,5]
	        }
	        <?php
				if(!empty($conf->global->FULLCALENDAR_SHOW_THIS_HOURS)) {
						list($hourShowStart, $hourShowEnd) = explode('-', $conf->global->FULLCALENDAR_SHOW_THIS_HOURS);
						if(!empty($hourShowStart) && !empty($hourShowEnd)) {
		        			?>,minTime:'<?php echo $hourShowStart.':00:00'; ?>'
		        			,maxTime:'<?php echo $hourShowEnd.':00:00'; ?>'<?php
						}
				}

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

		    if(!empty($conf->global->FULLCALENDAR_DURATION_SLOT)) {

				echo ',slotDuration:"'.$conf->global->FULLCALENDAR_DURATION_SLOT.'"';

		    }


			?>

	        ,lang: 'fr'
	        ,aspectRatio:1.36
	        ,weekNumbers:true
			,defaultView:defaultView
			,eventSources : [currentsource]
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
			<?php
				if(!empty($conf->global->FULLCALENDAR_HIDE_DAYS)) {

					?>
					,hiddenDays: [ <?php echo $conf->global->FULLCALENDAR_HIDE_DAYS ?> ]
					<?php

				}
			?>
			,eventAfterRender:function( event, element, view ) {
				console.log(element);
				if(event.colors!=""){
					console.log(event.id,event.colors);
					element.css({
						"background-color":""
						,"border":""
						,"background":event.colors

					});

				}


				if(event.isDarkColor == 1) {
					element.css({ color : "#fff" });

					element.find('a').css({
						color:"#fff"
					});
				}

			}
			,eventRender:function( event, element, view ) {
				var title = element.find('.fc-title').html();
				element.find('.fc-title').html('<a class="url_title" href="'+event.url_title+'" onclick="event.stopPropagation();">'+title+'</a>');
				var note = "";
				<?php

				if($conf->global->FULLCALENDAR_USE_HUGE_WHITE_BORDER) {
					echo 'element.css({
						"border":""
						,"border-radius":"0"
						,"border":"1px solid #fff"
						,"border-left":"2px solid #fff"
					});';

				}

				?>
				if(event.note) note+=event.note;

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
					 note = note+'<div>'+event.more+'</div>';
				}

				element.prepend('<div style="float:right;">'+event.statut+'</div>');

				element.tipTip({
					maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50
					,content : '<strong>'+event.title+'</strong><br />'+ note
				});

				element.find(".classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
				element.find(".classforcustomtooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 5000});

			 }
			,loading:function(isLoading, view) {

				// Engendre une impossibilité de naviguer sur les autres vues et de faire du prev, next (à voir si le décalage de pixel réapparait)
				/*if(!isLoading && defaultView != 'month') {
					$('#fullcalendar').fullCalendar( 'changeView', defaultView ); // sinon problème de positionnement
				}*/

				if(defaultView == 'month') {
					$('#fullcalendar').fullCalendar( 'option', 'height', 'auto');

				}

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

				showPopIn(date);

	        }
			,eventClick:function(calEvent, jsEvent, view) {
				showPopIn(calEvent.start, calEvent);
			}
			,eventAfterAllRender:function (view) {
				$('#fullcalendar').fullCalendar( 'option' , 'aspectRatio', 1.35);
			}
	    });
		
		function showPopIn(date, calEvent) {
			$('#pop-new-event').remove();

			$div = $('<div id="pop-new-event"></div>');
			
			$div.append("<?php echo strtr(addslashes($select_type_action),array("\n"=>"\\\n")); ?>");
			$div.append('<br /><input type="text" name="label" value="" placeholder="<?php echo $langs->trans('Title') ?>" style="width:300px"><br />');
			
			<?php 
				$doleditor=new DolEditor('note', '','',200,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_5,90);
				$fullcalendar_note = $doleditor->Create(1);
			?>
			$div.append(<?php echo json_encode($fullcalendar_note); ?>);
			
			<?php if (!empty($conf->global->FULLCALENDAR_CAN_UPDATE_PERCENT)) { ?>
			$div.append('<br /><?php echo $langs->trans('Status').' / '.$langs->trans('Percentage') ?> :');
			$div.append(<?php ob_start(); $formactions->form_select_status_action('formaction','0',1); $html_percent = ob_get_clean(); echo json_encode($html_percent); ?>);
			<?php } ?>
			
			$div.append("<br /><?php echo $langs->trans('Company'); ?> : ");
			$div.append(<?php echo json_encode($select_company); ?>);
			$div.append("<br /><?php echo $langs->trans('Contact').' : '.strtr(addslashes('<span rel="contact">'.$select_contact.'</span>'),array("\n"=>"\\\n")); ?>");
			$div.append("<br /><?php echo $langs->trans('User').' : '.strtr(addslashes($select_user),array("\n"=>" ","\r"=>"")); ?>");
			<?php

			if(!empty($conf->global->FULLCALENDAR_SHOW_PROJECT)) {

				?>
				$div.append("<br /><?php echo $langs->trans('Project').' : '.strtr(addslashes($select_project),array("\n"=>" ","\r"=>"")); ?>");
				<?php
			}



			if(!empty($moreOptions)) {

				foreach ($moreOptions as $param => $option)
				{
				?>
					$div.append("<br /><?php echo strtr(addslashes($option),array("\n"=>" ","\r"=>"")); ?>");
				<?php
				}

			}

			?>

			$div.find('select[name=fk_soc]').change(function() {
				var fk_soc = $(this).val();

				$.ajax({
					url: "<?php echo dol_buildpath('/core/ajax/contacts.php?action=getContacts&htmlname=contactid&showempty=1',1) ?>&id="+fk_soc
					,dataType:'json'
				}).done(function(data) {
					$('#pop-new-event span[rel=contact]').html(data.value);
				});

			});

			$div.append('<input type="hidden" name="id" value="" />');
			
			var fk_project = 0;
			if (typeof calEvent === 'object') {
				fk_project = calEvent.object.fk_project;
				
				$div.find('input[name=id]').val(calEvent.id);
				$div.find('#type_code').val(calEvent.object.type_code);
				$div.find('input[name=label]').val(calEvent.object.label);
				$div.find('textarea[name=note]').val(calEvent.object.note);
				<?php if (!empty($conf->global->FULLCALENDAR_CAN_UPDATE_PERCENT)) { ?>
				setTimeout(function() { // async needed
					if (calEvent.object.percentage == -1) $div.find('select[name=complete]').val(-1).trigger('change');
					else if (calEvent.object.percentage == 0) $div.find('select[name=complete]').val(0).trigger('change');
					else if (calEvent.object.percentage < 100) $div.find('select[name=complete]').val(50).trigger('change');
					else if (calEvent.object.percentage >= 100) $div.find('select[name=complete]').val(100).trigger('change');
					
					$div.find('input[name=percentage]').val(calEvent.object.percentage);
				}, 1);
				<?php } ?>
				if (calEvent.object.socid > 0) {
					$div.find('#fk_soc').val(calEvent.object.socid).trigger('change'); // Si COMPANY_USE_SEARCH_TO_SELECT == 0, alors le trigger "change" fera l'affaire
					setTimeout(function() { $div.find('#contactid').val(calEvent.object.contactid).trigger('change'); } ,250);
					<?php if (!empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) { ?>$div.find('#search_fk_soc').val(calEvent.object.thirdparty.name); <?php } ?>
				}
				$div.find('#contactid').val(calEvent.object.contactid).trigger('change');
				$div.find('#fk_user').val(calEvent.object.userownerid).trigger('change');
				$div.find('#fk_project').val(calEvent.object.fk_project).trigger('change');
			}
			
			
			$('body').append($div);

			var title_dialog = "<?php echo $langs->transnoentities('AddAnAction') ?>";
			var bt_add_lang = "<?php echo $langs->transnoentities('Add'); ?>";
			if (typeof calEvent === 'object')
			{
				title_dialog = "<?php echo $langs->transnoentities('EditAnAction') ?>";
				bt_add_lang = "<?php echo $langs->transnoentities('Update'); ?>";
			}
			
			$('#pop-new-event').dialog({
				modal:true
				,width:'auto'
				,title: title_dialog
				,buttons:[
					{
						text: bt_add_lang
						, click: function() {
							
							if($('#pop-new-event input[name=label]').val() != '') {

								var note = $('#pop-new-event textarea[name=note]').val();
								<?php if (!empty($conf->fckeditor->enabled)) { ?>note = CKEDITOR.instances['note'].getData(); <?php } ?>
								
								$.ajax({
									method: 'POST'
									,url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
									,data:{
										put:'event'
										,id:$('#pop-new-event input[name=id]').val()
										,label:$('#pop-new-event input[name=label]').val()
										,note:note
										,date:date.format()
										,fk_soc:$('#pop-new-event [name=fk_soc]').val()
										,fk_contact:$('#pop-new-event select[name=contactid]').val()
										,fk_user:$('#pop-new-event select[name=fk_user]').val()
										,fk_project:<?php if (!empty($conf->global->FULLCALENDAR_SHOW_PROJECT)) { ?>$('#pop-new-event select[name=fk_project]').val()<?php } else { ?>fk_project<?php } ?>
										,type_code:$('#pop-new-event select[name=type_code]').val()
										<?php if (!empty($conf->global->FULLCALENDAR_CAN_UPDATE_PERCENT)) { ?>
										,complete:$('#pop-new-event select[name=complete]').val()
										,percentage:$('#pop-new-event input[name=percentage]').val()
										<?php } ?>
										<?php
										if(!empty($moreOptions)) {

											foreach ($moreOptions as $param => $option)
											{
												echo ','.$param.':$("#pop-new-event select[name='.$param.']").val()';
											}
										}
										?>
									}
								}).done(function() {
									$('#fullcalendar').fullCalendar('removeEvents');
									$('#fullcalendar').fullCalendar( 'refetchEvents' );
									$('#pop-new-event').dialog( "close" );
								});

							}

						}
					}
					,{
						text: "<?php echo $langs->transnoentities('Cancel') ?>"
						, click: function() {
							$('#pop-new-event').dialog( "close" );
						}
					}
				]
			});
		}
		
		$('form[name=listactionsfilter]').submit(function(event) {
			console.log($('form[name=listactionsfilter]').serialize() );
			console.log($('#fullcalendar'));
			var newsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$('form[name=listactionsfilter]').serialize();
			$('#fullcalendar').fullCalendar('removeEvents');
			$('#fullcalendar').fullCalendar('removeEventSource', currentsource);
			$('#fullcalendar').fullCalendar( 'addEventSource', newsource);
			currentsource = newsource;
			event.preventDefault();
			var url = '<?php echo dol_buildpath('/comm/action/index.php',1) ?>?'+$('form[name=listactionsfilter]').serialize() ;
			history.pushState("FullCalendar","FullCalendar", url)


			var $a = $('table[summary=bookmarkstable] a.vsmenu[href*=create]');
			$a.attr('href',"<?php echo dol_buildpath('/bookmarks/card.php',1)  ?>?action=create&url_source="+encodeURIComponent(url)+"&url="+encodeURIComponent(url));

		});


	});

<?php
}
?>
