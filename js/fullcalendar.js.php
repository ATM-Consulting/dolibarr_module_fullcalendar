<?php

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disable token renewal

header('Content-Type: text/javascript');
	require '../config.php';

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	$langs->load('fullcalendar@fullcalendar');

	if(getDolGlobalString('MAIN_NOT_INC_FULLCALENDAR_HEAD') && empty($_REQUEST['force_use_js'])) exit;

	if(!$user->hasRight('fullcalendar', 'useit')) exit;

	dol_include_once('/core/class/html.formactions.class.php');
	dol_include_once('/core/class/html.formprojet.class.php');
	if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT'))
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
	$selected = getDolGlobalString('AGENDA_USE_EVENT_TYPE_DEFAULT') ? getDolGlobalString('AGENDA_USE_EVENT_TYPE_DEFAULT') : -1;
	$selectedText = "";
	// on veut afficher le txt de l'action par defaut si la conf est activée
	if ($selected != -1 && getDolGlobalInt('FULLCALENDAR_AUTO_FILL_TITLE')) {
		$selectedText = $langs->getLabelFromKey($db, 'code', 'c_actioncomm', 'code', 'libelle', $selected);
	}
	$formactions=new FormActions($db);
	$formactions->select_type_actions($selected, "type_code","systemauto");
	$select_type_action = ob_get_clean();

	$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

	$form=new Form($db);
	//$select_company = $form->select_thirdparty('','fk_soc','',1,1,0);
	$select_company = $form->select_company('', 'fk_soc', '', 1);

	//$select_user = $form->select_dolusers($user->id, 'fk_user');
	$TUserToSelect=array();

	$force_entity=0;
	$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut, u.login, u.admin, u.entity";
	if (isModEnabled('multicompany') && $conf->entity == 1 && $user->admin && ! $user->entity)
	{
		$sql.= ", e.label";
	}
	$sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
	if (isModEnabled('multicompany') && $conf->entity == 1 && $user->admin && ! $user->entity)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."entity as e ON e.rowid=u.entity";
		if ($force_entity) $sql.= " WHERE u.entity IN (0,".$force_entity.")";
		else $sql.= " WHERE u.entity IS NOT NULL";
	}
	else
	{
		if (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE'))
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
			$sql.= " ON ug.fk_user = u.rowid";
			$sql.= " WHERE ug.entity = ".$conf->entity;
		}
		else
		{
			$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		}
	}

	if (!empty($user->socid)) {
		$sql.= " AND u.fk_soc = ".$user->socid;
	}
	if (getDolGlobalString('USER_HIDE_INACTIVE_IN_COMBOBOX')) $sql.= " AND u.statut <> 0";

	if(!getDolGlobalString('MAIN_FIRSTNAME_NAME_POSITION')){
		$sql.= " ORDER BY u.firstname ASC";
	}else{
		$sql.= " ORDER BY u.lastname ASC";
	}

	$resUser = $db->query($sql);
	$userstatic=new User($db);

	while($objUser = $db->fetch_object($resUser)) {
		$userstatic->id=$objUser->rowid;
		$userstatic->lastname=$objUser->lastname;
		$userstatic->firstname=$objUser->firstname;

		$TUserToSelect[$userstatic->id] = $userstatic->getFullName($langs,0,-1,80);

	}
	//var_dump($TUserToSelect);

	$select_user = $form->multiselectarray('fk_user', $TUserToSelect,array($user->id), 0,0,'minwidth300');

	ob_start();
	if(versioncompare(versiondolibarrarray(), array(20)) >= 0) {
		echo $form->select_contact(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
	} else {
		$form->select_contacts(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
	}
	$select_contact = ob_get_clean();

	ob_start();
	$formProject = new FormProjets($db);
	$formProject->select_projects(-1, 0, 'fk_project', 0, 0, 1, 1);
	$select_project = ob_get_clean();

	$defaultDay = date('d');

	if(getDolGlobalString('MAIN_DEFAULT_WORKING_HOURS')) {
		list($hourStart, $hourEnd) = explode('-', getDolGlobalString('MAIN_DEFAULT_WORKING_HOURS'));
	}
	if(empty($hourStart)) $hourStart = 8;
	if(empty($hourEnd)) $hourEnd = 18;

	$moreOptions = '';
	$hookmanager->initHooks(array('fullcalendardao'));
	$parameters=array(); $action = 'addEvent'; $object = null;
	$reshook=$hookmanager->executeHooks('addOptionCalendarEvents',$parameters,$object,$action);
	if (! empty($hookmanager->resPrint)) $moreOptions = json_decode($hookmanager->resPrint);

	if (getDolGlobalString('FULLCALENDAR_FILTER_ON_STATE'))
	{
		dol_include_once('/core/class/html.formcompany.class.php');
		$formcompany = new FormCompany($db);
	}

?>
	var token = '<?php echo $newToken; ?>';

	$(document).ready(function () {

<?php if (getDolGlobalString('FULLCALENDAR_AUTO_FILL_TITLE')) { ?>
		$(document).on("change", "#pop-new-event #type_code", function() {
			var typeCodeTitle = $( "#type_code option:selected" ).text();

			var labelContent =$( "#pop-new-event input[name='label']" ).val();
			if(!labelContent.length){
				$( "#pop-new-event input[name='label']" ).val(typeCodeTitle);
			}
		});
<?php } ?>



		$('.wordbreak, .wordbreakimp').hide(); //hide std dolibarr btn to change date
		$("li.paginationafterarrows").children().not("a.btnTitle").hide(); //hide std pagination actions

		//Add search btn to filter events
		var parentDiv = $(".liste_titre.liste_titre_bydiv.centpercent");

		var buttonElement = $("<div>").addClass("divsearchfield").append($("<button>").attr({
			type: "submit",
			class: "liste_titre button_search valignmiddle",
			name: "button_search_x",
			value: "x"
		}).html("<span class='fa fa-search'></span>"));


		var clearDiv = parentDiv.find('div[class="clearboth"]');
		if(!clearDiv.length) clearDiv = parentDiv.find('div[style="clear:both"]'); // for compatibility with old dolibarr version (<= 17)
		buttonElement.insertBefore(clearDiv);


		<?php if (getDolGlobalString('FULLCALENDAR_FILTER_ON_STATE')) { ?>
            <?php if (floatval(DOL_VERSION) <= 17) { ?>
                var select_departement = <?php echo json_encode('<tr><td>'.fieldLabel('State','state_id').'</td><td>'.$formcompany->select_state(GETPOST('state_id', 'int'), 'FR').'</td></tr>'); ?>;
                $("#selectstatus").closest("tr").after(select_departement);
            <?php } else { ?>
                var select_departement = <?php echo json_encode('<div class="divsearchfield">'.fieldLabel('State','state_id').' '.$formcompany->select_state(GETPOST('state_id', 'int'), 'FR').'</div>'); ?>;
                $('#selectsearch_status').closest('div').after(select_departement);
            <?php } ?>
		<?php } ?>

			var $form_selector = $('form#searchFormList');

		var year = $form_selector.find('input[name=year]').val();
		var month = $form_selector.find('input[name=month]').val();
		if(month.length < 2) month = '0'+month; // S'il n'y a qu'un caractère on ajoute un 0 devant (bug firefox)
		var defaultDate = year+'-'+month+'-<?php echo $defaultDay/*.' '.$hourStart.':00'*/ ?>';

		var defaultView='month';
		if($('form.listactionsfilter input[name=action]').val() == 'show_week') defaultView = 'agendaWeek';
		if($('form.listactionsfilter input[name=action]').val() == 'show_day') defaultView = 'agendaDay';
		if($('form.listactionsfilter input[name=mode]').val() == 'show_week') defaultView = 'agendaWeek';
		if($('form.listactionsfilter input[name=mode]').val() == 'show_day') defaultView = 'agendaDay';

		$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/lib/fullcalendar/dist/fullcalendar.min.css',1) ?>" type="text/css" />');
		$('head').append('<link rel="stylesheet" href="<?php echo dol_buildpath('/fullcalendar/css/fullcalendar.css',1) ?>" type="text/css" />');
		$('table.cal_month').hide();
		$('table.cal_month').prev('table').find('td.titre_right').remove();

		$('table.cal_month').after('<div id="fullcalendar"></div>');
		var currentsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$form_selector.serialize();


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
				if(getDolGlobalString('FULLCALENDAR_SHOW_THIS_HOURS')) {
						list($hourShowStart, $hourShowEnd) = explode('-', getDolGlobalString('FULLCALENDAR_SHOW_THIS_HOURS'));
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

			if(getDolGlobalString('FULLCALENDAR_DURATION_SLOT')) {

				echo ',slotDuration:"' . getDolGlobalString('FULLCALENDAR_DURATION_SLOT').'"';
			}


			?>

			,lang: 'fr'
			,aspectRatio:1.36
			,weekNumbers:true
			,defaultView:defaultView
			,eventSources : [currentsource]
			,eventLimit : <?php echo getDolGlobalString('AGENDA_MAX_EVENTS_DAY_VIEW') ? getDolGlobalString('AGENDA_MAX_EVENTS_DAY_VIEW') : 3; ?>
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
				if(getDolGlobalString('FULLCALENDAR_HIDE_DAYS')) {

					?>
					,hiddenDays: [ <?php echo getDolGlobalString('FULLCALENDAR_HIDE_DAYS') ?> ]
					<?php
				}
			?>
			,eventAfterRender:function( event, element, view ) {

				if(event.colors!=""){

					element.css({
						"background-color":""
						,"border":""
						,"background":event.colors

					});

				}

				let textColor = event.isDarkColor == 0 ? 'black' : 'white';
				element.css({color: textColor});
				element.find('a').css({color: textColor});

			}
			,eventRender:function( event, element, view ) {

				// if checkbox not checked, block is hidden
				jQuery(document).ready(function () {
					jQuery("div input[name^=\"check_ext\"]").each(function(index, elem) {
						var name = jQuery(elem).attr("name");
						if (jQuery(elem).is(":checked")) {
							jQuery(".family_ext" + name.replace("check_ext", "")).show();
						} else {
							jQuery(".family_ext" + name.replace("check_ext", "")).hide();
						}
					});
					jQuery("div input[name^=\"check_holiday\"]").each(function(index, elem) {
						var name = jQuery(elem).attr("name");
						if (jQuery(elem).is(":checked")) {
							jQuery(".family_holiday" ).show();
						} else {
							jQuery(".family_holiday").hide();
						}
					});
				})

				var title = element.find('.fc-title').html();
				element.find('.fc-title').html('<a class="url_title" href="'+event.url_title+'" onclick="event.stopPropagation();">'+title+'</a>');
				// add class with family_ext + company external calendar name in MD5
				element.addClass(event.moreclass);
				var note = "";
				<?php

				if (getDolGlobalString('FULLCALENDAR_USE_HUGE_WHITE_BORDER')) {
					echo 'element.css({
						"border":""
						,"border-radius":"0"
						,"border":"1px solid #fff"
						,"border-left":"2px solid #fff"
					});';
				}
				?>

				if(event.note)
				{
					<?php
					if(getDolGlobalString('FULLCALENDAR_SHOW_EVENT_DESCRIPTION'))
					{
						?>
						element.append('<div style="z-index:3;position:relative;">' + event.note + '</div>');
						<?php
					}
					?>
					note+=event.note;
				}

				if(event.fk_soc>0){
					 element.append('<div style="z-index:3;position:relative;">'+event.societe+'</div>');
					 note += '<div>'+event.societe+'</div>';
				}
				if(event.fk_contact>0){
					 element.append('<div style="z-index:3;position:relative;">'+event.contact+'</div>');
					 note += '<div>'+event.contact+'</div>';
				}
				<?php
				if(getDolGlobalString('FULLCALENDAR_SHOW_AFFECTED_USER')) {

					?>
					if(event.fk_user>0){
						 element.append('<div style="z-index:3;position:relative;">'+event.user+'</div>');
						 note += '<div style="z-index:3;position:relative;">'+event.user+'</div>';
					}
					<?php

				}

				if(getDolGlobalString('FULLCALENDAR_SHOW_PROJECT')) {

					?>
					if(event.fk_project>0){
						 element.append('<div style="z-index:3;position:relative;">'+event.project+'</div>');
						 note = '<div style="z-index:3;position:relative;">'+event.project+'</div>'+note;
					}
					<?php
				}

				if(getDolGlobalString('FULLCALENDAR_SHOW_ORDER')) {

					?>
					if(event.fk_project>0 && event.fk_project_order>0){
						 element.append('<div style="z-index:3;position:relative;">'+event.project_order+'</div>');
						 note = '<div style="z-index:3;position:relative;">'+event.project_order+'</div>'+note;
					}
					<?php

				}


				?>
				if(event.more)  {
					 element.append('<div style="z-index:3;position:relative;">'+event.more+'</div>');
					 note = note+'<div style="z-index:3;position:relative;">'+event.more+'</div>';
				}

				if(event.splitedfulldayevent)  {
					note = note+'<div style="z-index:3;position:relative;"><span class="badge badge-info"><?php print addslashes($langs->transnoentities('FullDayEventSplited')); ?></span></div>';
				}

				element.prepend('<div style="float:right;">'+event.statut+'</div>');

				if ($().tipTip) // ou $.fn.tipTip, mais $.tipTip ne fonctionne pas
				{
					element.tipTip({
						maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50
						,content : '<strong>'+event.title+'</strong><br />'+ note
					});

					element.find(".classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
					element.find(".classforcustomtooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 5000});
				}
				else
				{
					element.tooltip({
						items: 'a.fc-event' // La boîte entière de l'événement montre un tooltip, par défaut, ce sont tous les éléments contenus dans le sélecteur avec une balise title
						, show: { collision: "flipfit", effect: "toggle", delay: 50 }
						, hide: { delay: 50 }
						, position: { my: "left+10 center", at: "right center" }
						, content : '<strong>'+event.title+'</strong><br />'+ note
					});

					// On remet les tooltips des liens désactivés par l'appel ci-dessus
					element.find(".classfortooltip, .classforcustomtooltip").tooltip({
						show: { collision: "flipfit", effect: "toggle", delay: 50 }
						, hide: { delay: 50 }
						, tooltipClass: "mytooltip"
						, content: function() {
							return $(this).prop('title');
						}
					});
				}
			 }
			,loading:function(isLoading, view) {



			}
			,eventDrop:function( event, delta, revertFunc, jsEvent, ui, view ) {
				console.log(delta);
				// disable for fullday events
				if(event.splitedfulldayevent)  {
					//revertFunc();
					//return false;
				}

				$.ajax({
					url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
					,data:{
						put:'event-move'
						,id:event.id
						,data:delta._data
						,fulldayevent: event.allDay
						,splitedfulldayevent: event.splitedfulldayevent
						,token: token
					}
				}).done(function() {
					$('#fullcalendar').fullCalendar('refetchEvents');
				});
			}
			,eventResize:function( event, delta, revertFunc, jsEvent, ui, view ) {
				console.log(delta);
				// disable resizing for fullday events
				if(event.splitedfulldayevent)  {
					revertFunc();
					return false;
				}

				$.ajax({
					url:'<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'
					,data:{
						put:'event-resize'
						,id:event.id
						,data:delta._data
						,splitedfulldayevent: event.splitedfulldayevent
						,token: token
					}
				}).done(function() {
					$('#fullcalendar').fullCalendar('refetchEvents');
				});
			}
			,dayClick:function( date, jsEvent, view ) {
				console.log(date.format());
				//document.location.href = "<?php echo dol_buildpath('/comm/action/card.php?action=create',1); ?>"

				showPopIn(date);

			}
			,eventClick:function(calEvent, jsEvent, view) {
				if(! $(jsEvent.target).is('a[href]')) { // Si la cible du click est un lien avec un adresse, on ne montre pas la popin et on suit le lien

					// disable editing for splited fullday events
					if(calEvent.splitedfulldayevent)  {
						return false;
					}

					showPopIn(calEvent.start, calEvent);
				}
			}
			,eventAfterAllRender:function (view) {
				$('#fullcalendar').fullCalendar( 'option' , 'aspectRatio', 1.35);
			}
		});

		var $btnprev = $('[aria-label="prev"]');
		var $btnnext = $('[aria-label="next"]');
		var $btntoday = $('button.fc-today-button');

		var $currentYear = $('input[name="year"]');
		var $currentMonth = $('input[name="month"]');
		var $currentDay = $('input[name="day"]');

		$btntoday.on('click', function(e){
			var $date = new Date();
			$currentYear.val($date.getFullYear());
			$currentMonth.val($date.getMonth()+1);
			$currentDay.val($date.getDate());

			var newsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$form_selector.serialize();
			$('#fullcalendar').fullCalendar('removeEvents');
			$('#fullcalendar').fullCalendar('removeEventSource', currentsource);
			$('#fullcalendar').fullCalendar( 'addEventSource', newsource);
			currentsource = newsource;
		});

		$btnprev.on('click', function(e){
			var activeView = 'month';
			var activeBtn = $('button.fc-state-active');
			if (activeBtn.hasClass("fc-agendaDay-button")) activeView = 'day';
			else if (activeBtn.hasClass("fc-agendaWeek-button")) activeView = 'week';

			var $date = new Date($currentYear.val(), $currentMonth.val()-1, $currentDay.val());

			if (activeView == "day") {
				var newDate = new Date($date.getTime());

				if (newDate.getDate() == 1)
				{
					newDate.setMonth(newDate.getMonth());
				}
				newDate.setDate(newDate.getDate()-1);

				console.log('prevDay', newDate.toDateString())

			}
			else if (activeView == "month")
			{

				var newDate = new Date(
					$date.getMonth() == 0 ? $date.getFullYear()-1 : $date.getFullYear()
					, $date.getMonth() == 0 ? 11 : $date.getMonth()-1
					, $date.getDate()
				);
				console.log('prevMonth', newDate.toDateString())
			}
			else
			{
				var newDate = new Date($date.getTime());

				if (newDate.getDate() <= 7)
				{
					newDate.setMonth(newDate.getMonth());
				}
				newDate.setDate(newDate.getDate()-7);
				console.log('prevWeek', newDate.toDateString())
			}

			$currentYear.val(newDate.getFullYear());
			$currentMonth.val(newDate.getMonth()+1);
			$currentDay.val(newDate.getDate());

			var newsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$form_selector.serialize();
			$('#fullcalendar').fullCalendar('removeEvents');
			$('#fullcalendar').fullCalendar('removeEventSource', currentsource);
			$('#fullcalendar').fullCalendar( 'addEventSource', newsource);
			currentsource = newsource;
		});

		$btnnext.on('click', function(e){
			var activeView = 'month';
			var activeBtn = $('button.fc-state-active');
			if (activeBtn.hasClass("fc-agendaDay-button")) activeView = 'day';
			else if (activeBtn.hasClass("fc-agendaWeek-button")) activeView = 'week';

			var $date = new Date($currentYear.val(), $currentMonth.val()-1, $currentDay.val());

			if (activeView == "day") {
				var newDate = new Date($date.getTime() + (86400 *1000)); // le nombre de millièmes de secondes d'une journée à ajouter
				console.log('nextDay', newDate.toDateString())
			}
			else if (activeView == "month")
			{
				var newDate = new Date(
					$date.getMonth() == 11 ? $date.getFullYear()+1 : $date.getFullYear()
					, $date.getMonth() == 11 ? 0 : $date.getMonth()+1
					, $date.getDate()
				);
				console.log('nextMonth', newDate.toDateString())
			}
			else
			{
				var newDate = new Date($date.getTime() + (86400 * 7 *1000)); // 7 * le nombre de millièmes de secondes d'une journée à ajouter
				console.log('nextWeek', newDate.toDateString())
			}

			$currentYear.val(newDate.getFullYear());
			$currentMonth.val(newDate.getMonth()+1);
			$currentDay.val(newDate.getDate());

			var newsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$form_selector.serialize();
			$('#fullcalendar').fullCalendar('removeEvents');
			$('#fullcalendar').fullCalendar('removeEventSource', currentsource);
			$('#fullcalendar').fullCalendar( 'addEventSource', newsource);
			currentsource = newsource;
		});

		function formatDateUTC(date,format)
		{
			// alert('formatDate date='+date+' format='+format);

			// Force parametres en chaine
			format=format+"";

			var result="";

			var year=date.getUTCFullYear()+""; if (year.length < 4) { year=""+(year-0+1900); }
			var month=date.getUTCMonth()+1;
			var day=date.getUTCDate();
			var hour=date.getUTCHours();
			var minute=date.getUTCMinutes();
			var seconde=date.getUTCSeconds();

			var i=0;
			while (i < format.length)
			{
				c=format.charAt(i);	// Recupere char du format
				substr="";
				j=i;
				while ((format.charAt(j)==c) && (j < format.length))	// Recupere char successif identiques
				{
					substr += format.charAt(j++);
				}

				// alert('substr='+substr);
				if (substr == 'yyyy')      { result=result+year; }
				else if (substr == 'yy')   { result=result+year.substring(2,4); }
				else if (substr == 'M')    { result=result+month; }
				else if (substr == 'MM')   { result=result+(month<1||month>9?"":"0")+month; }
				else if (substr == 'd')    { result=result+day; }
				else if (substr == 'dd')   { result=result+(day<1||day>9?"":"0")+day; }
				else if (substr == 'hh')   { if (hour > 12) hour-=12; result=result+(hour<0||hour>9?"":"0")+hour; }
				else if (substr == 'HH')   { result=result+(hour<0||hour>9?"":"0")+hour; }
				else if (substr == 'mm')   { result=result+(minute<0||minute>9?"":"0")+minute; }
				else if (substr == 'ss')   { result=result+(seconde<0||seconde>9?"":"0")+seconde; }
				else { result=result+substr; }

				i+=substr.length;
			}

			// alert(result);
			return result;
		}

		function showPopIn(date, calEvent) {
			$('#pop-new-event').remove();

			$div = $('<div id="pop-new-event"></div>');

			var date_start = date._d;
			var date_end = date._d;

			$form = $('<form name="action"></form>');
			/*TODO better display */
			$form.append('<?php echo dol_escape_js($select_type_action); ?>');
			var selectedText ='<?php echo dol_escape_js($selectedText ); ?>';
			$form.append('<br /><input type="text" id="label_event" name="label" value="' + selectedText + '" placeholder="<?php echo $langs->trans('Title') ?>" style="width:300px"><br />');

			$form.append('<br /><?php echo $langs->trans("DateActionStart")?> : ');
			$form.append(<?php echo json_encode($form->select_date(0,'ap',1,1,0,"action",1,0,1,0,'fulldayend')); ?>);

			$form.append('<br /><?php echo $langs->trans("DateActionEnd") ?> : ');
			$form.append(<?php echo json_encode($form->select_date(0,'p2',1,1,0,"action",1,0,1,0,'fulldayend')); ?>);

			<?php if(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIMES')) { ?>

			$form.append('<br />Pré-remplissage : <a href="javascript:;" class="prefillDate" id="prefillDateMorning">Matin</a> | ');
			$form.append(' <a href class="prefillDate" id="prefillDateAfternoon">Après-midi</a> | ');
			$form.append(' <a href class="prefillDate" id="prefillDateDay">Journée</a><br />');

			$form.on('click', 'a.prefillDate', function()
			{
				var morningStartHour = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_START'), '%H'); ?>;
				var morningStartMin = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_START'), '%M'); ?>;
				var morningEndHour = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_END'), '%H'); ?>;
				var morningEndMin = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_END'), '%M'); ?>;
				var afternoonStartHour = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START'), '%H'); ?>;
				var afternoonStartMin = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START'), '%M'); ?>;
				var afternoonEndHour = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END'), '%H'); ?>;
				var afternoonEndMin = <?php echo dol_print_date(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END'), '%M'); ?>;

				var dateEnd = $('#ap').val();
				var dateEndDay = $('#apday').val();
				var dateEndMonth = $('#apmonth').val();
				var dateEndYear = $('#apyear').val();

				switch($(this).prop('id'))
				{
					case 'prefillDateMorning':
						startHour = morningStartHour;
						startMin = morningStartMin;
						endHour = morningEndHour;
						endMin = morningEndMin;

						break;

					case 'prefillDateAfternoon':
						startHour = afternoonStartHour;
						startMin = afternoonStartMin;
						endHour = afternoonEndHour;
						endMin = afternoonEndMin;

						break;

					case 'prefillDateDay':
						startHour = morningStartHour;
						startMin = morningStartMin;
						endHour = afternoonEndHour;
						endMin = afternoonEndMin;

						break;

					default:
						return false;
				}

				if(startHour < 10)	startHour = '0' + startHour;
				if(startMin < 10)	startMin = '0' + startMin;
				if(endHour < 10)	endHour = '0' + endHour;
				if(endMin < 10)		endMin = '0' + endMin;

				$('#aphour').val(startHour);
				$('#apmin').val(startMin);

				$('#p2').val(dateEnd);
				$('#p2day').val(dateEndDay);
				$('#p2month').val(dateEndMonth);
				$('#p2year').val(dateEndYear);
				$('#p2hour').val(endHour);
				$('#p2min').val(endMin);

				return false;
			});

			<?php }

				$doleditor=new DolEditor('note', '','',200,'dolibarr_notes','In',true,true,isModEnabled('fckeditor'),ROWS_5,90);
				$fullcalendar_note = $doleditor->Create(1);
			?>
			$form.append('<br />'+<?php echo json_encode($fullcalendar_note); ?>);

			<?php if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT')) { ?>
			$form.append('<br /><?php echo $langs->trans('Status').' / '.$langs->trans('Percentage') ?> :');
			$form.append(<?php ob_start(); $formactions->form_select_status_action('formaction','0',1); $html_percent = ob_get_clean(); echo json_encode($html_percent); ?>);
			<?php } ?>

			$form.append('<br /><?php echo $langs->trans('Company'); ?> : ');
			$form.append(<?php echo json_encode($select_company); ?>);
			$form.append('<br /><?php echo $langs->trans('Contact'); ?> : ');
			$form.append(<?php echo json_encode('<span rel="contact">'.$select_contact.'</span>'); ?>);
			$form.append('<br /><?php echo $langs->trans('User'); ?> : ');
			$form.append(<?php echo json_encode($select_user); ?>);
			<?php

			if(getDolGlobalString('FULLCALENDAR_SHOW_PROJECT')) {

				?>
				$form.append('<br /><?php echo $langs->trans('Project'); ?> : ');
				$form.append(<?php echo json_encode($select_project); ?>);
				<?php
			}

			/**
			 * conf utilisées en 13.0 pour activer les notifications
			 * si l'une d'elle est activée, on rajoute ce qu'il faut au formulaire
			 */
			if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
				if (is_callable(array($form, 'selectTypeDuration'), true)) {
					$select_typereminder = $form->selectTypeDuration('offsetunit');
				} else {
					$select_typereminder = $form->select_type_duration('offsetunit');
				}
				$TRemindTypes = array();
				if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) $TRemindTypes['email'] = $langs->trans('EMail');
				if (getDolGlobalString('AGENDA_REMINDER_BROWSER')) $TRemindTypes['browser'] = $langs->trans('BrowserPush');
				$select_remindertype =  $form->selectarray('selectremindertype', $TRemindTypes);

				if (is_callable(array($form, 'selectModelMail'), true)) {
					$select_mailtemplate = str_replace("\n", '', $form->selectModelMail('actioncommsend', 'actioncomm_send', 1));
				} else {
					$select_mailtemplate = str_replace("\n", '', $form->select_model_mail('actioncommsend', 'actioncomm_send', 1));
				}

				$script = '<script type="text/javascript">$(document).ready(function () {

						$("#addreminder").click(function(){
							if (this.checked) {
							  $(".reminderparameters").show();
							} else {
							$(".reminderparameters").hide();
							}
						 });

						$("#selectremindertype").change(function(){
							var selected_option = $("#selectremindertype option:selected").val();
							if(selected_option == "email") {
								$("#select_actioncommsendmodel_mail").closest("tr").show();
							} else {
								$("#select_actioncommsendmodel_mail").closest("tr").hide();
							};
						});

				   });</script>';

				$dataForJS = array(
					'selectTypeReminder' => $select_typereminder,
					'selectReminderType' => $select_remindertype,
					'selectMailTemplate' => $select_mailtemplate,
					'langs' => array(
						'ReminderTime' => $langs->trans("ReminderTime"),
						'TimeType' => $langs->trans("TimeType"),
						'ReminderType' => $langs->trans("ReminderType"),
						'EMailTemplates' => $langs->trans("EMailTemplates"),
						'AddReminder' => $langs->trans('AddReminder')
					)
				);

				?>
				let dataForJS = <?php print json_encode($dataForJS); ?>;
				$form.append('<br /><br />' + dataForJS.langs.AddReminder + ' : ');
				$form.append('<input type="checkbox" id="addreminder" name="addreminder" >');

				$form.append('<hr>');

				$reminderparameters = $('<table class="reminderparameters" style="width: 100%;display: none"></table>'); //style="display: none;"

				// temps
				$reminderparameters.append('<tr><td width="30%">' + dataForJS.langs.ReminderTime + ' : </td><td><input type="number" name="offsetvalue" value="10" size="5"></td></tr>');

				// unité de temps
				$reminderparameters.append('<tr><td width="30%">' + dataForJS.langs.TimeType + ' : </td><td>' + dataForJS.selectTypeReminder + '</td></tr>');

				// type de rappel
				$reminderparameters.append('<tr><td width="30%">' + dataForJS.langs.ReminderType + ' : </td><td>' + dataForJS.selectReminderType + '</td></tr>');

				// template mail
				$reminderparameters.append('<tr><td width="30%">' + dataForJS.langs.EMailTemplates + ' : </td><td>' + dataForJS.selectMailTemplate + '</td></tr>');
				$reminderparameters.append(<?php echo json_encode($script); ?>);
				$form.append($reminderparameters);
				<?php

			}

			if(!empty($moreOptions)) {

				foreach ($moreOptions as $param => $option)
				{
				?>
					$form.append('<br />'+<?php echo json_encode($option); ?>);
				<?php
				}

			}

			?>

			$form.find('#fk_soc').change(function() {
				var fk_soc = $(this).val();

				$.ajax({
					url: "<?php echo dol_buildpath('/core/ajax/contacts.php?action=getContacts&htmlname=contactid&showempty=1&token='.$newToken,1) ?>&id="+fk_soc
					,dataType:'json'
					,token: token
				}).done(function(data) {
					$('#pop-new-event span[rel=contact]').html('<select class="flat" id="contactid" name="contactid">'+data.value+'</select>');$('#contactid').select2();
				});

			});

			$form.append('<input type="hidden" name="id" value="" />');

			$div.append($form);

			var TUserId=[];
			var fk_project = 0;

			var editable = true;

			if (typeof calEvent === 'object') {
				fk_project = calEvent.object.fk_project;

				$div.find('input[name=id]').val(calEvent.id);
				$div.find('#type_code').val(calEvent.object.type_code);
				$div.find('input[name=label]').val(calEvent.object.label);
				$div.find('textarea[name=note]').val(calEvent.object.note);

				/**
				 * si l'event porte ce champs, c'est qu'il a des notif attachées...
				 */
				if (calEvent.reminder_offsetvalue)
				{
					$div.find('#addreminder').prop('checked', true);
					$div.find('.reminderparameters').show();
					$div.find('input[name=offsetvalue]').val(calEvent.reminder_offsetvalue);

					$div.find('select[name=offsetunittype_duration]').val(calEvent.reminder_offsetunit).change();
					$div.find('select[name=selectremindertype]').val(calEvent.reminder_typeremind).change();
					if (calEvent.reminder_typeremind == 'browser') $div.find('select[name=actioncommsendmodel_mail]').closest('tr').hide();
					if (calEvent.reminder_fk_email_template) $div.find('select[name=actioncommsendmodel_mail]').val(calEvent.reminder_fk_email_template).change();

				}

				<?php if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT')) { ?>
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
					setTimeout(function() { $div.find('#contactid').val(calEvent.object.contact_id).trigger('change'); } ,250);
					<?php if (getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT')) { ?>$div.find('#search_fk_soc')?.val(calEvent.object?.socname); <?php } ?>
				}
				$div.find('#contactid').val(calEvent.object.contact_id).trigger('change');
				TUserId = calEvent.TFk_user;
				$div.find('#fk_project').val(calEvent.object.fk_project).trigger('change');

				date_start = calEvent.start._d;
				date_end = calEvent.end ? calEvent.end._d : null;
				editable = calEvent.editable;


<?php
	$parameters=array(); $action = 'showPopIn'; $object = null;
	$reshook=$hookmanager->executeHooks('addShowPopInBehaviour',$parameters,$object,$action);
	if ($reshook >= 0 && ! empty($hookmanager->resPrint))
	{
		print $hookmanager->resPrint;
	}
?>
			}

			$('body').append($div);

			if(!editable) {
			//un peu violent mais quand pas le droit d'édition c'est le plus simple
				window.open("<?php echo dol_buildpath('/comm/action/card.php',1) ?>?id="+calEvent.id);
				return false;
			}

			$('body').append($div);


			var formattedDateStart = '', formattedHoursStart = '', formattedMinutesStart = '', formattedDateEnd = '', formattedHoursEnd = '', formattedMinutesEnd = '';

			if(date_start)
			{
				formattedDateStart = formatDateUTC(date_start, "<?php echo $langs->trans("FormatDateShortJavaInput") ?>");
				formattedHoursStart = formatDateUTC(date_start, 'HH');
				formattedMinutesStart = formatDateUTC(date_start, 'mm');
				// Décalage de deux heures si dates identiques
				if(date_end == date_start)
				{
					date_end.setTime(date_start.getTime() + 2 * 3600 * 1000); // Paramètres en millisecondes
				}
			}

			if(date_end)
			{
				formattedDateEnd = formatDateUTC(date_end, "<?php echo $langs->trans("FormatDateShortJavaInput") ?>");
				formattedHoursEnd = formatDateUTC(date_end, 'HH');
				formattedMinutesEnd = formatDateUTC(date_end, 'mm');
			}

			$('#pop-new-event #ap').val(formattedDateStart);
			$('#pop-new-event #aphour').val(formattedHoursStart);
			$('#pop-new-event #apmin').val(formattedMinutesStart);

			$('#pop-new-event #p2').val(formattedDateEnd);
			$('#pop-new-event #p2hour').val(formattedHoursEnd);
			$('#pop-new-event #p2min').val(formattedMinutesEnd);

			dpChangeDay('ap', "<?php echo $langs->trans("FormatDateShortJavaInput") ?>");
			dpChangeDay('p2', "<?php echo $langs->trans("FormatDateShortJavaInput") ?>");


			var title_dialog = "<?php echo $langs->transnoentities('AddAnAction') ?>";
			var bt_add_lang = "<?php echo $langs->transnoentities('Add'); ?>";
			if (typeof calEvent === 'object')
			{
				title_dialog = "<?php echo $langs->transnoentities('EditAnAction') ?>";
				bt_add_lang = "<?php echo $langs->transnoentities('Update'); ?>";
			}

			var TButton = [];

			if(editable) {

			TButton.push({
						text: bt_add_lang
						, click: function() {

							if($('#pop-new-event input[name=label]').val() != '') {

								var TUserId=[];
								var dataSelectUser = $('#pop-new-event #fk_user').select2('data');
								for(i in dataSelectUser) {
									TUserId.push(dataSelectUser[i].id);
								}


								var note = $('#pop-new-event textarea[name=note]').val();
								<?php if (isModEnabled('fckeditor')) { ?>note = CKEDITOR.instances['note'].getData(); <?php } ?>

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
										,fk_user:TUserId
										,fk_project:<?php if (getDolGlobalString('FULLCALENDAR_SHOW_PROJECT')) { ?>$('#pop-new-event #fk_project').val()<?php } else { ?>fk_project<?php } ?>
										,type_code:$('#pop-new-event select[name=type_code]').val()
										,date_start:$('#pop-new-event #apyear').val()+'-'+$('#pop-new-event #apmonth').val()+'-'+$('#pop-new-event #apday').val()+' '+$('#pop-new-event #aphour').val()+':'+$('#pop-new-event #apmin').val()+':00'
										,date_end:$('#pop-new-event #p2year').val()+'-'+$('#pop-new-event #p2month').val()+'-'+$('#pop-new-event #p2day').val()+' '+$('#pop-new-event #p2hour').val()+':'+$('#pop-new-event #p2min').val()+':00'
                                        ,token: token
                                        <?php if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT')) { ?>
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
										/**
										 * conf disponible en 13.0
										 * envoie des données servant à créer les notifs
										 */
										if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER'))
										{
											?>
												,setReminder: $('#pop-new-event input[name=addreminder]').prop('checked') == false ? 0 : 1
												,reminderValue:$('#pop-new-event input[name=offsetvalue]').val()
												,reminderUnit:$('#pop-new-event select[name=offsetunittype_duration]').val()
												,reminderType:$('#pop-new-event select[name=selectremindertype]').val()
												,reminderTemplate:$('#pop-new-event select[name=actioncommsendmodel_mail]').val()
											<?php
										}
										?>
									}
														}).done(function(data) {

									// le retour est un id  donc je close
									if (Number.isInteger(parseInt(data))){
										$('#fullcalendar').fullCalendar('removeEvents');
										$('#fullcalendar').fullCalendar( 'refetchEvents' );
										$('#pop-new-event').dialog( "close" );
									}else{
										if ($(".error").length){
											$(".error").html(data);
										}else{
											$("#label_event").after('<span class="error">' + data + '</span>')
										}
									}

								});

							}


						}
					}
			);

			}

			if (typeof calEvent === 'object' && editable)
			{
				var isCloningEvent = false;

				TButton.push({
					text: "<?php echo $langs->transnoentities('ToClone') ?>",
					click: function() {
						if (isCloningEvent) {
							console.warn('Clone action already in progress, skipping...');
							return;
						}
						isCloningEvent = true;

						//copier-coller moche pour sauvegarder avant de cloner
						if ($('#pop-new-event input[name=label]').val() != '') {
							var TUserId = []; // Use camelCase
							var dataSelectUser = $('#pop-new-event #fk_user').select2('data');
							for (var i in dataSelectUser) { // Use var to avoid global i
								TUserId.push(dataSelectUser[i].id);
							}

							var note = $('#pop-new-event textarea[name=note]').val();
							<?php if (isModEnabled('fckeditor')) { ?>
							note = CKEDITOR.instances['note'].getData();
							<?php } ?>

							console.log('Preparing to save event before cloning'); // Debug log
							$.ajax({
								method: 'POST',
								url: '<?php echo dol_buildpath('/fullcalendar/script/interface.php', 1) ?>',
								data: {
									put: 'event',
									id: $('#pop-new-event input[name=id]').val(),
									label: $('#pop-new-event input[name=label]').val(),
									note: note,
									date: date.format(),
									fk_soc: $('#pop-new-event [name=fk_soc]').val(), // Fixed: Removed invalid .val() argument
									fk_contact: $('#pop-new-event select[name=contactid]').val(),
									fk_user: TUserId,
									fk_project: <?php if (getDolGlobalString('FULLCALENDAR_SHOW_PROJECT')) { ?>$('#pop-new-event #fk_project').val()<?php } else { ?>fk_project<?php } ?>,
									type_code: $('#pop-new-event select[name=type_code]').val(),
									date_start: $('#pop-new-event #apyear').val() + '-' + $('#pop-new-event #apmonth').val() + '-' + $('#pop-new-event #apday').val() + ' ' + $('#pop-new-event #aphour').val() + ':' + $('#pop-new-event #apmin').val() + ':00',
									date_end: $('#pop-new-event #p2year').val() + '-' + $('#pop-new-event #p2month').val() + '-' + $('#pop-new-event #p2day').val() + ' ' + $('#pop-new-event #p2hour').val() + ':' + $('#pop-new-event #p2min').val() + ':00',
									token: token,
									<?php if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT')) { ?>
									complete: $('#pop-new-event select[name=complete]').val(),
									percentage: $('#pop-new-event input[name=percentage]').val(),
									<?php } ?>
									<?php
									if (!empty($moreOptions)) {
										foreach ($moreOptions as $param => $option) {
											echo ',' . $param . ':$("#pop-new-event select[name=' . $param . ']").val()';
										}
									}
									?>
								}
							}).done(function(response) {
								$.ajax({
									url: "<?php echo dol_buildpath('/comm/action/card.php', 1) ?>",
									data: {
										action: 'confirm_clone',
										token: token,
										confirm: 'yes',
										object: 'action',
										id: $('#pop-new-event input[name=id]').val(),
										fk_userowner: TUserId[0],
										socid: $('#pop-new-event [name=fk_soc]').val()
									}
								}).done(function(response) {
									$('#fullcalendar').fullCalendar('removeEvents');
									$('#fullcalendar').fullCalendar('refetchEvents');
									$('#pop-new-event').dialog("close");
									isCloningEvent = false; // Reset flag
								}).fail(function(jqXHR, textStatus, errorThrown) {
									console.error('Clone action failed:', textStatus, errorThrown); // Debug log
									setEventMessages('Error cloning event: ' + (jqXHR.responseText || 'Unknown error'), null, 'errors');
									isCloningEvent = false; // Reset flag
								});
							}).fail(function(jqXHR, textStatus, errorThrown) {
								console.error('Save event failed:', textStatus, errorThrown); // Debug log
								setEventMessages('Error saving event: ' + (jqXHR.responseText || 'Unknown error'), null, 'errors');
								isCloningEvent = false; // Reset flag
							});
						} else {
							isCloningEvent = false; // Reset flag if label is empty
						}
					}
				});
			}

			TButton.push({
						text: "<?php echo $langs->transnoentities('Cancel') ?>"
						, click: function() {
							$('#pop-new-event').dialog( "close" );
						}
					});


			function formatResult(record) {
					return record.text;
			}
			function formatSelection(record) {
					return record.text;
			}

			$('#pop-new-event #fk_user').select2({
					dir: 'ltr',
					formatResult: formatResult,
					templateResult: formatResult,
					formatSelection: formatSelection,
					templateResult: formatSelection
			});

			/*
				Qu'est-ce qui faut pas faire pour récupérer les users dans le bon order et conserver ainsi le owner
			*/
			var TDataSelect2=[];
			for(i in TUserId) {
				fk_user = TUserId[i];

				var $option = $('#pop-new-event #fk_user option[value='+fk_user+']');
				if($option.length>0) {
					TDataSelect2.push(fk_user);
				}
			}

			if(TDataSelect2.length>0) {
				$('#pop-new-event #fk_user').val(TDataSelect2).trigger('change'); // Select2 écoute l'événement change
			}

			$('#pop-new-event').dialog({
				modal:false
				,width:'auto'
				,title: title_dialog
				,buttons:TButton
			});
		}

		$form_selector.submit(function(event) {
			console.log($form_selector.serialize() );
			console.log($('#fullcalendar'));
			var newsource = '<?php echo dol_buildpath('/fullcalendar/script/interface.php',1) ?>'+'?'+$form_selector.serialize();
			$('#fullcalendar').fullCalendar('removeEvents');
			$('#fullcalendar').fullCalendar('removeEventSource', currentsource);
			$('#fullcalendar').fullCalendar( 'addEventSource', newsource);
			currentsource = newsource;
			event.preventDefault();
			var url = '<?php echo dol_buildpath('/comm/action/index.php',1) ?>?'+$form_selector.serialize() ;
			history.pushState("FullCalendar","FullCalendar", url)

			var $a = $('table[summary=bookmarkstable] a.vsmenu[href*=create]');
			$a.attr('href',"<?php echo dol_buildpath('/bookmarks/card.php',1)  ?>?action=create&url_source="+encodeURIComponent(url)+"&url="+encodeURIComponent(url));
			$('option[value=newbookmark]').attr("rel","<?php echo dol_buildpath('/bookmarks/card.php',1) ?>?action=create&url="+encodeURIComponent(url));

		});


<?php
	if(! empty($conf->use_javascript_ajax))
	{
?>
		$('#actioncode, #projectid').select2({ width: '100%' });
<?php
	}
?>
	});
