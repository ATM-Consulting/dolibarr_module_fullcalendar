<?php
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal

	require '../config.php';
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';
    require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

	$langs->load("agenda");
	$langs->load("other");
	$langs->load("commercial");
	$langs->load("companies");

	$form = new Form($db);
	$formother = new FormOther($db);

	$get=GETPOST('get', 'none');
	$put=GETPOST('put', 'none');
    $hookmanager->initHooks(array('fullcalendarinterface'));


	if(empty($get) && empty($put)) $get = 'events';

	switch ($get) {
		case 'events':
			$start = GETPOST('start', 'none');
			$end = GETPOST('end', 'none');
			$year = GETPOST('year', 'none');
			$month = GETPOST('month', 'none');
			$day = GETPOST('day', 'none');

	/*
			if(!empty($year)) {

				if(!empty($day)) {
					$start = $year.'-'.$month.'-'.$day;
					$end = $year.'-'.$month.'-'.$day;
				}
				else{
					$start = $year.'-'.$month.'-01';
					$end = $year.'-'.$month.'-31';
				}

			}
	*/
			$TEvent = _events($start, $end);
			foreach ($TEvent as &$event) unset($event['object']->db);
			__out($TEvent, 'json');


			break;
        case 'tasks':
            $start = GETPOST('start', 'none');
			$end = GETPOST('end', 'none');
            $TEvent = _tasks($start, $end);
            foreach ($TEvent as &$event) unset($event['object']->db);
            __out($TEvent, 'json');
            break;
        case 'task-popin':
            $fk_task = GETPOST('fk_task','int');
            $popinContent = _taskEditableView($fk_task);
            print $popinContent;
            break;
		default:

			break;
	}


	switch($put){
		case 'event-move':

			$a=new ActionComm($db);
			if($a->fetch(GETPOST('id', 'int'))>0) {
				$a->fetch_userassigned();

				$TData = $_REQUEST['data'];

				if (GETPOST('fulldayevent', 'none') == 'true') $a->fulldayevent = 1;
				else $a->fulldayevent = 0;

				$splitedfulldayevent = GETPOST('splitedfulldayevent', 'int');
				if(!empty($splitedfulldayevent))
				{
					$a->fulldayevent = 1;
				}
				else{
					if(!empty($TData['minutes'])) {
						$a->datep = strtotime($TData['minutes'].' minute', $a->datep);
					    if (!empty($a->datef)) $a->datef = strtotime($TData['minutes'].' minute', $a->datef);
					}

					if(!empty($TData['hours'])) {
						$a->datep = strtotime($TData['hours'].' hour', $a->datep);
                        if (!empty($a->datef)) $a->datef = strtotime($TData['hours'].' hour', $a->datef);
                    }
				}

				if(!empty($TData['days'])) {
					$a->datep = strtotime($TData['days'].' day', $a->datep);
                    if (!empty($a->datef)) $a->datef = strtotime($TData['days'].' day', $a->datef);
				}


				$res = $a->update($user);
				addReminders($a, 'drop');

			}


			break;
        case 'task-move':

			$task=new Task($db);
			if($task->fetch(GETPOST('id', 'int'))>0) {
                $TData = $_REQUEST['data'];

                if(! empty($TData['minutes'])) {
                    $task->date_start = strtotime($TData['minutes'].' minute', $task->date_start);
                    if(! empty($task->date_end)) $task->date_end = strtotime($TData['minutes'].' minute', $task->date_end);
                }

                if(! empty($TData['hours'])) {
                    $task->date_start = strtotime($TData['hours'].' hour', $task->date_start);
                    if(! empty($task->date_end)) $task->date_end = strtotime($TData['hours'].' hour', $task->date_end);
                }
                if(! empty($TData['days'])) {
                    $task->date_start = strtotime($TData['days'].' day', $task->date_start);
                    if(! empty($task->date_end)) $task->date_end = strtotime($TData['days'].' day', $task->date_end);
                }

                $res = $task->update($user);
            }

			break;
		case 'task-edit':
            $TData = GETPOST('data', 'array');
            if(!empty($TData)) {
                $fk_task = 0;
                foreach($TData as $data) {
                    if($data['name'] == 'fk_task') {
                        $fk_task = $data['value'];
                        break;
                    }
                }
                if(! empty($fk_task)) {
                    $TDateStart = $TDateEnd = $TPlannedWorload = array();
                    $task = new Task($db);
                    if($task->fetch($fk_task) > 0) {
                        foreach($TData as $data) {
                            if($data['name'] == 'fk_task') continue;
                            else if(strpos($data['name'], 'options') !== false) $task->array_options[$data['name']] = $data['value'];
                            else if(strpos($data['name'], 'date_start') !== false) $TDateStart[$data['name']] = $data['value'];
                            else if(strpos($data['name'], 'date_end') !== false) $TDateEnd[$data['name']] = $data['value'];
                            else if(strpos($data['name'], 'planned_workload') !== false) $TPlannedWorload[$data['name']] = $data['value'];
                            else $task->{$data['name']} = $data['value'];
                            $parameters = array('name' => $data['name'], 'value' => $data['value']);
                            $reshook = $hookmanager->executeHooks('fullcalendarDataTaskUpdate', $parameters, $task); // Note that $action and $object may have been modified by hook
                        }
                        if(!empty($TDateStart)) $task->date_start = dol_mktime($TDateStart['date_starthour'], $TDateStart['date_startmin'], 0, $TDateStart['date_startmonth'], $TDateStart['date_startday'], $TDateStart['date_startyear']);
                        if(!empty($TDateEnd)) $task->date_end = dol_mktime($TDateEnd['date_endhour'], $TDateEnd['date_endmin'], 0, $TDateEnd['date_endmonth'], $TDateEnd['date_endday'], $TDateEnd['date_endyear']);
                        if(!empty($TPlannedWorload)) $task->planned_workload = intval($TPlannedWorload['planned_workloadhour']) * 3600 + intval($TPlannedWorload['planned_workloadmin']) * 60;
                        $parameters = array('TData' => $TData);
                        $reshook = $hookmanager->executeHooks('beforeTaskUpdate', $parameters, $task); // Note that $action and $object may have been modified by hook
                        $task->update($user);
                    }

                }
            }
			break;
		case 'event-resize':
			$a=new ActionComm($db);
			if($a->fetch(GETPOST('id', 'int'))>0) {
				$a->fetch_userassigned();

				$TData = $_REQUEST['data'];

				$splitedfulldayevent = GETPOST('splitedfulldayevent', 'int');
				if(!empty($splitedfulldayevent))
				{
					$a->fulldayevent = 1;
				}
				else{

					if(!empty($TData['minutes'])) {
						if(empty($a->datef))$a->datef = $a->datep;
						$a->datef = strtotime($TData['minutes'].' minute', $a->datef);
					}

					if(!empty($TData['hours'])) {
						if(empty($a->datef))$a->datef = $a->datep + 3600 * 2; // décalage de 2H
						$a->datef = strtotime($TData['hours'].' hour', $a->datef);
					}
				}


				if(!empty($TData['days'])) {
					if(empty($a->datef))$a->datef = $a->datep;
					$a->datef = strtotime($TData['days'].' day', $a->datef);
				}



				$res = $a->update($user);


			}



			break;
        case 'task-resize':
            $task = new Task($db);
            if($task->fetch(GETPOST('id', 'int')) > 0) {
                $TData = $_REQUEST['data'];
                if(! empty($TData['minutes'])) {
                    if(empty($task->date_end)) $task->date_end = $task->date_start;
                    $task->date_end = strtotime($TData['minutes'].' minute', $task->date_end);
                }

                if(! empty($TData['hours'])) {
                    if(empty($task->date_end)) $task->date_end = $task->date_start + 3600 * 2; // décalage de 2H
                    $task->date_end = strtotime($TData['hours'].' hour', $task->date_end);
                }

                if(! empty($TData['days'])) {
                    if(empty($task->date_end)) $task->date_end = $task->date_start;
                    $task->date_end = strtotime($TData['days'].' day', $task->date_end);
                }
                $res = $task->update($user);
            }

			break;

		case 'event':
			$type = getDolGlobalInt('AGENDA_USE_EVENT_TYPE');
			if (GETPOST('label', 'none') != ""  || $type ){


			$a=new ActionComm($db);
            // Gestion changements v13
            // Gestion de la rétrocompatibilité;
				$a->contactid = 1;
            $contactId = $a->contact_id ? $a->contact_id : $a->contactid;

			$id = GETPOST('id', 'int');
			if (!empty($id)) $a->fetch($id);

			$a->label = GETPOST('label', 'none');
			$a->note=$a->note_private= GETPOST('note', 'none');
/*
			if (empty($a->id))
			{
				$datep = date('H',strtotime(GETPOST('date', 'none')));
				if($datep == '00' && !empty($conf->global->FULLCALENDAR_SHOW_THIS_HOURS) ){
					$a->datep = strtotime('+'.substr($conf->global->FULLCALENDAR_SHOW_THIS_HOURS,0,1).' hour',strtotime(GETPOST('date', 'none')));
				}
				else{
					if($datep=='00') $a->fulldayevent = 1;

					$a->datep = strtotime(GETPOST('date', 'none'));
				}
				$a->datef = strtotime('+2 hour',$a->datep);
			}
*/

			$datep = GETPOST('date_start', 'none');
			$datef = GETPOST('date_end', 'none');

			$timestamp_start = strtotime($datep);
			$timestamp_end = strtotime($datef);

			$a->datep = dol_mktime(date("H", $timestamp_start), date("i", $timestamp_start), date("s", $timestamp_start), date("n", $timestamp_start),  date("j", $timestamp_start), date("Y", $timestamp_start), 'tzuserrel');
			$a->datef = dol_mktime(date("H", $timestamp_end), date("i", $timestamp_end), date("s", $timestamp_end), date("n", $timestamp_end),  date("j", $timestamp_end), date("Y", $timestamp_end), 'tzuserrel');

			$TUser = GETPOST('fk_user', 'none');
			if(empty($TUser))$TUser[] = $user->id;
			if(!is_array($TUser))$TUser=array($TUser);

			$a->userownerid = $TUser[0];
			$a->type_code = GETPOST('type_code', 'none') ? GETPOST('type_code', 'none') : 'AC_OTH';
			$a->code = $a->type_code; // Up to Dolibarr 3.4, code is used in ActionComm:add() instead of type_code. It's seems unused, but you never know for sure.
			$a->fk_action = dol_getIdFromCode($db, $a->type_code, 'c_actioncomm'); // type_code is not saved in ActionComm::update(), fk_action is up to Dolibarr 6.0
			$a->type_id = $a->fk_action; // type_id used instead of fk_action in ActionComm::update() since Dolibarr 7.0, used in ::add()/::create() since the beginning

			$a->socid = GETPOST('fk_soc', 'int');
			$contactId = GETPOST('fk_contact', 'int');

			$a->fk_project = GETPOST('fk_project','int');

			$percentage = -1; // Non applicable
			if (getDolGlobalString('FULLCALENDAR_CAN_UPDATE_PERCENT')) $percentage=in_array(GETPOST('status', 'none'),array(-1,100))?GETPOST('status', 'none'):(in_array(GETPOST('complete', 'none'),array(-1,100))?GETPOST('complete', 'none'):GETPOST("percentage", 'none'));	// [COPY FROM DOLIBARR] If status is -1 or 100, percentage is not defined and we must use status
			$a->percentage = $percentage;

			$moreParams = GETPOST('moreParams', 'none');
			$moreParams = explode(',', $moreParams);
			$TParam = array();
			foreach ($moreParams as $param)
			{
                $a->{'_'.$param} = GETPOST($param, 'none');
			}
			//var_dump($conf->global->FULLCALENDAR_SHOW_THIS_HOURS,GETPOST('date', 'none'),$a);exit;

			if($user->hasRight('agenda', 'allactions', 'create') ||
					(($a->authorid == $user->id || $a->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {

				$a->userassigned = array();
				if(!empty($TUser)) {
					foreach($TUser as $fk_user) {
						$a->userassigned[$fk_user] = array('id'=>$fk_user);
					}
				}

			}
			elseif($a->id>0) {
				$a->fetch_userassigned();
			}

			if (empty($a->id)) {
				if(method_exists($a, 'create')) {
					$res = $a->create($user);
					addReminders($a);
				} else {
					$res = $a->add($user);
					addReminders($a);
				}
			}
			else
			{
				if (empty($contactId)) $a->contact = null;

				$res = $a->update($user);
				if ($res > 0)
				{
					$res = $a->id;
					addReminders($a, 'update');
				}
			}


			print $res;
			}else{

				print $langs->trans('labelRequired');
			}
			break;
	}

function _taskEditableView($fk_task) {
    global $langs, $db, $form, $formother, $hookmanager;
    $task = new Task($db);
    $view = '';
    if($task->fetch($fk_task) > 0) {
        $view .= '<form id="editableViewForm"  style="" >';
        $view .= '<input type="hidden" name="fk_task" value="'.$fk_task.'">';
        $view .= '<table id="editableView" class="table" style="" >';
        if(empty($task->fields)) {
            $task->fields = array(
                'label' => array('type' => 'varchar', 'label' => 'Label', 'enabled' => 1, 'visible' => 1),
                'date_start' => array('type' => 'datetime', 'enabled' => 1, 'visible' => 1, 'position' => 30),
                'date_end' => array('type' => 'datetime',  'enabled' => 1, 'visible' => 1, 'position' => 35),
                'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => 1, 'position' => 55),
            );
        }

        $view .= '<tr><td>'.$langs->trans('Label').'</td><td>'.$task->showInputField(array(), 'label', $task->label).'</td></tr>';
        $view .= '<tr><td>'.$langs->trans('StartDate').'</td><td>'.$task->showInputField(array(), 'date_start', $task->date_start).'</td></tr>';
        $view .= '<tr><td>'.$langs->trans('EndDate').'</td><td>'.$task->showInputField(array(), 'date_end', $task->date_end).'</td></tr>';
        $view .= '<tr><td>'.$langs->trans('PlannedWorkload').'</td><td>'.$form->select_duration('planned_workload', $task->planned_workload, 0, 'text',0,1).'</td></tr>';
        $view .= '<tr><td>'.$langs->trans('Description').'</td><td>'.$task->showInputField(array(), 'description', html_entity_decode($task->description, ENT_QUOTES)).'</td></tr>';
        $view .= '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td>'.$formother->select_percent($task->progress, 'progress', 0, 5, 0, 100, 1).'</td></tr>';
        $parameters = array('task' => $task);
        $reshook = $hookmanager->executeHooks('addMoreTaskEditableView', $parameters, $view); // Note that $action and $object may have been modified by hook
        $view .= $hookmanager->resPrint;
        $view .= '</table></form>';
    } else {
        $view = '<strong>'.$langs->trans('CantFetchTask').'</strong>';
    }
    return $view;
}



    /**
     * @param string date $date_start
     * @param string date $date_end
     * @return array Task
     */
function _tasks($date_start, $date_end) {
    global $db, $user, $conf, $hookmanager;
    $TEvent = array();
    $task = new Task($db);
    $t_start = strtotime($date_start);
    $t_end = strtotime($date_end);

    //TODO get color by entity
    $color = explode(',', getDolGlobalString('THEME_ELDY_TOPMENU_BACK1'));
    $color = sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]); //Conversion de la couleur en hexa

    $sql = "SELECT t.rowid";
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= " FROM ".MAIN_DB_PREFIX.$task->table_element ." as t";
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= " WHERE ((t.datee>='".$db->idate($t_start - (60 * 60 * 24 * 7))."' AND t.dateo<='".$db->idate($t_end + (60 * 60 * 24 * 10))."')
				OR
			  	(t.dateo BETWEEN '".$db->idate($t_start - (60 * 60 * 24 * 7))."' AND '".$db->idate($t_end + (60 * 60 * 24 * 10))."'))
			  	AND t.entity IN (".getEntity('project').")";
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $resql = $db->query($sql);

    if(! empty($resql) && $db->num_rows($resql) > 0) {
        while($obj = $db->fetch_object($resql)) {
			$task = new Task($db);
            $res = $task->fetch($obj->rowid);
            if($res > 0) {
                $dateEnd = $task->date_end;
                if(empty($task->date_end) && ! empty($task->planned_workload)) $dateEnd = $task->date_start + ceil($task->planned_workload);
                $desc = makeTaskDesc($task, $dateEnd);
                $allDay = false;
                //si c'est sur plusieurs jours on passe en vue "all day"
                if(dol_print_date($task->date_start, '%Y-%m-%d') != dol_print_date($dateEnd, '%Y-%m-%d')) $allDay = true;
                $tmpEvent = array(
                	'headTask' => '',
                    'id' => $task->id,
                    'title' => $task->ref.' - '.$task->label,
                    'allDay' => $allDay,
                    'start' => (empty($task->date_start) ? '' : dol_print_date($task->date_start, '%Y-%m-%d %H:%M:%S')),
                    'end' => (empty($dateEnd) ? '' : dol_print_date($dateEnd, '%Y-%m-%d %H:%M:%S')),
                    'url_title' => dol_buildpath('/projet/tasks/task.php?id='.$task->id, 1),
                    'editable' =>  $user->hasRight('fullcalendar', 'task','write') ? 1 : 0,
                    'color' => $color,
                    'borderColor' => 'black',
                    'isDarkColor' => isDarkColor($color),
                    'description' => $desc,
                    //                        'fulldayevent' => $event->fulldayevent,
                    'more' => '',
                    'object' => $task
                );

                $parameters = array('sql' => $sql, 'task' => $task);
                $reshook = $hookmanager->executeHooks('setFullcalendarOrdoTask', $parameters, $tmpEvent, $action);    // Note that $action and $object may have been modified by hook
                if($reshook > 0) $tmpEvent = $hookmanager->resArray;

                $TEvent[] = $tmpEvent;
            }
        }
    }

    return $TEvent;
}

function makeTaskDesc($task, $dateEnd) {
    global $langs;
    $desc = '<strong>'.$langs->trans('StartDate').' : </strong>'.dol_print_date($task->date_start, 'dayhourtext').'<br/>';
    $desc .= '<strong>'.$langs->trans('EndDate').' : </strong>'.dol_print_date($dateEnd, 'dayhourtext').'<br/>';
    if(! empty($task->planned_workload)) {
        $hours = sprintf('%02d:%02d', ($task->planned_workload / 3600), ($task->planned_workload / 60 % 60));
        $desc .= '<strong>'.$langs->trans('PlannedWorkload').' : </strong>'.$hours.'<br/>';
    }
    if(! empty($task->progress)) $desc .= '<strong>'.$langs->trans('Progress').' : </strong>'.$task->progress.'%<br/>';
    if(! empty($task->duration_effective)) {
        $hours = sprintf('%02d:%02d', ($task->duration_effective / 3600), ($task->duration_effective / 60 % 60));
        $desc .= '<strong>'.$langs->trans('DurationEffective').' : </strong>'.$hours.'<br/>';
    }
    if(empty($task->project)) $task->fetch_projet();
    if(! empty($task->project)) {
        $desc .= '<strong>'.$langs->trans('Project').' : </strong>'.$task->project->ref.' - '.$task->project->title.'<br/>';
        if(!empty($task->project->socid)) {
            $langs->load('companies');
            $task->project->fetch_thirdparty();
            $desc .= '<strong>'.$langs->trans('ThirdParty').' : </strong>'.$task->project->thirdparty->getNomUrl().'<br/>';
        }

    }
    $desc .= '<strong>'.$langs->trans('Description').' : </strong>'.$task->description.'<br/>';
    return $desc;
}

function _events($date_start, $date_end) {
	global $db,$conf,$langs,$user,$hookmanager;

	$hookmanager->initHooks(array('agenda'));

	$pid=GETPOST("projectid","int",3);
	$status=GETPOST("status", 'none');
	if(empty($status)) $status = GETPOST("search_status", 'none');
	$type=GETPOST("type", 'none');
	$state_id = GETPOST('state_id', 'int');

	$maxprint=(GETPOST("maxprint", 'none')?GETPOST("maxprint", 'none'):(getDolGlobalString('AGENDA_MAX_EVENTS_DAY_VIEW') ? getDolGlobalString('AGENDA_MAX_EVENTS_DAY_VIEW') : ''));

	//First try with GETPOST(array, 'none') (I don't know when it can be an array but why not)
	$actioncode=GETPOST("actioncode", "array", 3)?GETPOST("actioncode", "array", 3):(GETPOST("actioncode", 'none')=='0'?'0':'');
    if(empty($actioncode)){
        $actioncode=GETPOST("search_actioncode", "array", 3)?GETPOST("search_actioncode", "array", 3):(GETPOST("search_actioncode", 'none')=='0'?'0':'');
    }


	//If empty then try GETPOST(alpha, 'none') (this one works with comm/action/index.php
	if(empty($actioncode)) {

		$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode", 'none')=='0'?'0':'');
        if(empty($actioncode)){
            $actioncode=GETPOST("search_actioncode", "alpha", 3)?GETPOST("search_actioncode", "alpha", 3):(GETPOST("search_actioncode", 'none')=='0'?'0':'');
        }

		if(!empty($actioncode)) $actioncode=array($actioncode);

	}
	if(empty($actioncode)) {
		$actioncode = array();
	}

	$filter=GETPOST("filter",'',3);
	$filtert = GETPOST("usertodo","int",3)?GETPOST("usertodo","int",3):GETPOST("filtert","int",3);
	if(empty($filtert)) $filtert = GETPOST("search_filtert","int",3);
	$usergroup = GETPOST("usergroup","int",3);
	$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;

	if (empty($filtert) && !getDolGlobalString('AGENDA_ALL_CALENDARS'))
	{
		$filtert=$user->id;
	}
	$socid = GETPOST("socid","int");

	$t_start = strtotime($date_start);
	$t_end = strtotime($date_end);

	$now=dol_now();

	$sql = 'SELECT ';
	if ($usergroup > 0) $sql.=" DISTINCT";
	$sql.= ' a.id, a.label,';
	$sql.= ' a.datep,';
	$sql.= ' a.datep2,';
	$sql.= ' a.percent,';
	$sql.= ' a.fk_user_author,a.fk_user_action,';
	$sql.= ' a.transparency, a.priority, a.fulldayevent, a.location,';
	$sql.= ' a.fk_soc, a.fk_contact,a.note,';
	$sql.= ' u.color,';
	$sql.= ' ca.color as type_color,';
	$sql.= ' ca.code as type_code, ca.libelle as type_label';
	$sql.= ' FROM '.MAIN_DB_PREFIX."actioncomm as a";
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm as ca ON (a.fk_action = ca.id)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (a.fk_user_action=u.rowid )';
	if (getDolGlobalString('FULLCALENDAR_FILTER_ON_STATE') && !empty($state_id))
	{
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
	}

	if (! $user->hasRight('societe', 'client', 'voir') && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON (ar.fk_actioncomm = a.id)";
	if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
	$sql.= ' WHERE 1=1';
	$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';

    if ($actioncode){

        $sql.=" AND ( ca.code IN ('".implode("','", $actioncode)."')";

        if (in_array('AC_NON_AUTO', $actioncode)) $sql .= " OR ca.type != 'systemauto'";
        elseif (in_array('AC_ALL_AUTO', $actioncode)) $sql .= " OR ca.type = 'systemauto'";
        elseif(!getDolGlobalString('AGENDA_USE_EVENT_TYPE'))
        {
            if (in_array('AC_OTH', $actioncode)) $sql .= " OR ca.type != 'systemauto'";
            if (in_array('AC_OTH_AUTO', $actioncode)) $sql .= " OR ca.type = 'systemauto'";
        }
        else {
            $sql .= " OR ca.code IN ('".implode("','", $actioncode)."')";
        }

        $sql.=" )";
    }

    if (getDolGlobalString('DONT_SHOW_AUTO_EVENT') && strpos(implode(',', $actioncode),'AC_OTH_AUTO') == false) $sql.=" AND ca.code != 'AC_OTH_AUTO'";
	if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
	if (! $user->hasRight('societe', 'client', 'voir') && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
	if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
	if (getDolGlobalString('FULLCALENDAR_FILTER_ON_STATE') && !empty($state_id)) $sql.= ' AND (s.fk_departement = '.$state_id.' OR sp.fk_departement = '.$state_id.')';
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.= " AND ar.element_type='user'";

	$sql.=" AND
			(
				(a.datep2>='".$db->idate($t_start-(60*60*24*7))."' AND datep<='".$db->idate($t_end+(60*60*24*10))."')
				OR
			  	(a.datep BETWEEN '".$db->idate($t_start-(60*60*24*7))."' AND '".$db->idate($t_end+(60*60*24*10))."')
			) ";

	if ($type) $sql.= " AND ca.id = ".$type;
	if ($status == '0') { $sql.= " AND a.percent = 0"; }
	if (DOL_VERSION > 12 && $status == 'na' || DOL_VERSION < 13 && $status == '-1') { $sql.= " AND a.percent = -1"; }	// Not applicable
	if ($status == '50') { $sql.= " AND (a.percent > 0 AND a.percent < 100)"; }	// Running already started
	if ($status == 'done' || $status == '100') { $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))"; }
	if ($status == 'todo') { $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))"; }
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0)
	{
	    $sql.= " AND (";
	    if ($filtert > 0) $sql.= "ar.fk_element = ".$filtert;
	    if ($usergroup > 0) $sql.= ($filtert>0?" OR ":"")." ugu.fk_usergroup = ".$usergroup;
	    $sql.= ")";
	}
	// Sort on date
	$sql.= ' ORDER BY datep';
	$sql .= " LIMIT 100";

	$TEvent=array();
	if(isset($_REQUEST['DEBUG'])) print $sql;
//echo $sql;exit;
	$res= $db->query($sql);
	//var_dump($db);

	$TSociete = array();
	$TContact = array();
	$TUser = array();
	$TProject = $TProjectObject = array();

	$TEventObject=array();
	while($obj=$db->fetch_object($res)) {
		$event = new ActionComm($db);
        // Gestion changements v13
        // Gestion de la rétrocompatibilité
        if (version_compare(DOL_VERSION, '14.0.0') > 0)
        {
            $eventContactId = $event->contact_id;
        }
        else $eventContactId = $event->contactid;

		$event->fetch($obj->id);
		if (method_exists($event, 'fetch_thirdparty')) $event->fetch_thirdparty();
		if (method_exists($event, 'fetchObjectLinked')) $event->fetchObjectLinked();
		$event->fetch_userassigned();

		$event->color = $obj->color;
		$event->type_color = $obj->type_color;

		if(getDolGlobalString('FULLCALENDAR_SPLIT_DAYS')
		&& getDolGlobalString('FULLCALENDAR_PREFILL_DATETIMES')
		&& getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_START')
		&& getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_END')
		&& getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START')
		&& getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END')
		&& !empty($event->fulldayevent)
		&& ($event->datef - $event->datep) <= 86400 // ne peut pas le faire sur plusieurs jours
		)
		{
			$datep = $event->datep;
			$datef = $event->datef;

			// Morning
			$eventMorning = clone $event;
			$eventMorning->fulldayevent = 0;
			$eventMorning->splitedfulldayevent = 1;
			$eventMorning->datep = $datep + _convertTimestampLocalToNoLocalSecond(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_START')); // Date action start (datep)
			$eventMorning->datef = $datep + _convertTimestampLocalToNoLocalSecond(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_MORNING_END')); // Date action end (datep2)
			$TEventObject[] = $eventMorning;


			// Afternoon
			$eventAfternoon = clone $event;
			$eventAfternoon->fulldayevent = 0;
			$eventAfternoon->splitedfulldayevent = 1;
			$eventAfternoon->datep = $datep + _convertTimestampLocalToNoLocalSecond(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_START')); // Date action start (datep)
			$eventAfternoon->datef = $datep + _convertTimestampLocalToNoLocalSecond(getDolGlobalString('FULLCALENDAR_PREFILL_DATETIME_AFTERNOON_END')); // Date action end (datep2)
			$TEventObject[] = $eventAfternoon;
		}

		$event->splitedfulldayevent = 0;
		$TEventObject[] = $event;
	}

	foreach($TEventObject as &$event) {

		if($event->socid>0 && !isset($TSociete[$event->socid])) {
			$societe = new Societe($db);
			$societe->fetch($event->socid);
			$TSociete[$event->socid]  = $societe->getNomUrl(1);

		}
		if($eventContactId>0 && !isset($TContact[$eventContactId])) {
            $contact = new Contact($db);
            $contact->fetch($eventContactId);
            $TContact[$eventContactId]  = $contact->getNomUrl(1);

        }

		$TUserassigned = array();
		$TColor=array();

		if($event->color && getDolGlobalString('FULLCALENDAR_USE_ASSIGNED_COLOR')) {
			$TColor[] = '#'.$event->color;
		}
		if($event->type_color && getDolGlobalString('FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR')) {
			$TColor[] = '#'.$event->type_color;
		}

		if(getDolGlobalString('FULLCALENDAR_SHOW_AFFECTED_USER') ) {

			$userownerid = (int)$event->userownerid;

			if( $userownerid>0 && !isset($TUser[$userownerid])) {
	            $u = new User($db);
	            $u->fetch($userownerid);
	            $TUser[$userownerid]  = $u;
			}
			$TUserassigned[$userownerid] = 	$TUser[$userownerid]->getNomUrl(1);
        }

		if(getDolGlobalString('FULLCALENDAR_SHOW_PROJECT') && $event->fk_project>0 && !isset($TProject[$event->fk_project])) {
            $p = new Project($db);
            $p->fetch($event->fk_project);
            $TProject[$event->fk_project]  = $p->getNomUrl(1);
            $TProjectObject[$event->fk_project]  = $p;

        }

        if(getDolGlobalString('FULLCALENDAR_SHOW_ORDER') && $event->fk_project>0) {
            if( !isset($TProject[$event->fk_project]) ) {
                $p = new Project($db);
                $p->fetch($event->fk_project);
                $TProject[$event->fk_project]  = $p->getNomUrl(1);
                $TProjectObject[$event->fk_project]  = $p;
            }

            if(!isset($TProjectObject[$event->fk_project]->fk_project_order)) {
                // c'est de la merde cette fonction, je custom :: $orders = $TProjectObject[$event->fk_project]->get_element_list('commande','commande');


                $res = $db->query("SELECT rowid, ref FROM ".MAIN_DB_PREFIX."commande WHERE fk_projet=".$event->fk_project." ORDER BY date_commande DESC LIMIT 1");
                if($res===false) {
                    var_dump($db);exit;
                }
                else{

                    dol_include_once('/commande/class/commande.class.php');

                    $obj = $db->fetch_object($res);
                    $o=new Commande($db);
                    $o->id = $obj->rowid;
                    $o->ref = $obj->ref;

                    $event->fk_project_order = $o->id;
                    $event->project_order = $o->getNomUrl(1);

                }

            }


        }

		if(getDolGlobalString('FULLCALENDAR_SHOW_AFFECTED_USER') && !empty($event->userassigned)) {

			foreach($event->userassigned as &$ua) {
				$userid = (int)$ua['id'];
				if(!isset($TUser[$userid])) {
					   $u = new User($db);
            		   $u->fetch($userid);
           			   $TUser[$userid]  = $u;

				}

				if(!isset($TUserassigned[$userid])) $TUserassigned[] = $TUser[$userid]->getNomUrl(1);

				if($TUser[$userid]->color && !in_array('#'.$TUser[$userid]->color,$TColor)) $TColor[] = '#'.$TUser[$userid]->color;

			}

		}



		$editable = false;
		if(($user->id == $event->userownerid) || $user->hasRight('agenda', 'allactions', 'create')) {
			$editable = true;
		}

		//background: linear-gradient(to bottom, #1e5799 0%,#2989d8 25%,#207cca 67%,#7db9e8 100%);
		//$colors = implode(',',$TColor);
		$colors='';

		$color='';
		if(!empty($TColor)) {

			$color = $TColor[0];

			if(getDolGlobalString('FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR') && count($TColor)>1) {
				$colors = ' linear-gradient(to right ';
				foreach($TColor as $c) {

					$colors.= ','.$c;

				}

				$colors.=')';

			}

		}
		$tmpEvent=array(
			'id'=>$event->id
		,'title'=>$event->label
		,'allDay'=>(bool)($event->fulldayevent)
		,'start'=>(empty($event->datep) ? '' : dol_print_date($event->datep, '%Y-%m-%d %H:%M:%S'))
		,'end'=>(empty($event->datef) ? '' : dol_print_date($event->datef, '%Y-%m-%d %H:%M:%S'))
		,'url_title'=>dol_buildpath('/comm/action/card.php?id='.$event->id,1)
		,'editable'=>$editable
		,'color'=>$color
		,'isDarkColor'=>isDarkColor($color)
		,'colors'=>$colors
		,'note'=>$event->note
		,'statut'=>$event->getLibStatut(3)
		,'fk_soc'=>$event->socid
		,'fk_contact'=>$eventContactId
		,'fk_user'=>$event->userownerid
		,'TFk_user'=>array_keys($event->userassigned)
		,'fk_project'=>$event->fk_project
		,'societe'=>(!empty($TSociete[$event->socid]) ? $TSociete[$event->socid] : '')
		,'contact'=>(!empty($TContact[$eventContactId]) ? $TContact[$eventContactId] : '')
		,'user'=>(!empty($TUserassigned) ? implode(', ',$TUserassigned) : '')
		,'project'=>(!empty($TProject[$event->fk_project]) ? $TProject[$event->fk_project] : '')

		,'project_order'=>(!empty( $event->project_order ) ? $event->project_order : '')
		,'fk_project_order'=>(!empty( $event->fk_project_order ) ? $event->fk_project_order : '0')

		,'splitedfulldayevent'=> $event->splitedfulldayevent
		,'fulldayevent'=> $event->fulldayevent
		,'more'=>''
		,'object'=>$event
		);

		/**
		 * $conf dispo en 13.0 permettant de gérer les notification push et mail
		 * si activées, on tente de récupérer les infos notifs
		 * sachant que s'il y en a plusieurs, ce qui change c'est juste le fk_user
		 */
		if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER'))
		{
			$sqlremind = "SELECT acr.rowid FROM ".MAIN_DB_PREFIX."actioncomm_reminder acr WHERE acr.fk_actioncomm = ".$event->id;
			$resql = $db->query($sqlremind);
			if ($resql && $db->num_rows($resql))
			{
				$obj = $db->fetch_object($resql);

				$actionCommReminder = new ActionCommReminder($db);
				$res = $actionCommReminder->fetch($obj->rowid);
				if ($res > 0)
				{
					$tmpEvent['reminder_offsetvalue'] = $actionCommReminder->offsetvalue;
					$tmpEvent['reminder_offsetunit'] = $actionCommReminder->offsetunit;
					$tmpEvent['reminder_typeremind'] = $actionCommReminder->typeremind;
					$tmpEvent['reminder_fk_email_template'] = $actionCommReminder->fk_email_template;
				}
			}
		}
		$TEvent[] = $tmpEvent;

	}

	//TODO getCalendarEvents compatbile standard
	// Complete $eventarray with events coming from external module
	$parameters=array('use_color_from'=>GETPOST('use_color_from', 'none'),'sql'=>$sql); $action = 'getEvents';
	$reshook=$hookmanager->executeHooks('updateFullcalendarEvents',$parameters,$TEvent,$action);
	if (! empty($hookmanager->resArray['eventarray'])) $TEvent=array_merge($TEvent, $hookmanager->resArray['eventarray']);

	completeWithExtEvent($TEvent, $TSociete, $TContact, $TProject);
//		var_dump($TEvent);exit;
	$mode = GETPOST('mode', 'aZ09');
	$year = GETPOST("year", "int") ?GETPOST("year", "int") : date("Y");
	$month = GETPOST("month", "int") ?GETPOST("month", "int") : date("m");
	$day = GETPOST("day", "int") ?GETPOST("day", "int") : date("d");
	if ($user->hasRight("holiday", "read")) {
		// LEAVE-HOLIDAY CALENDAR
		$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.statut, x.rowid, x.ref, x.fk_user,x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.statut as status, x.description";
		$sql .= " FROM ". $db->prefix() ."holiday as x, ". $db->prefix() ."user as u";
		$sql .= " WHERE u.rowid = x.fk_user";
		$sql .= " AND u.statut = '1'"; // Show only active users  (0 = inactive user, 1 = active user)
		$sql .= " AND (x.statut = '2' OR x.statut = '3')"; // Show only public leaves (2 = leave wait for approval, 3 = leave approved)

		if ($mode == 'show_day') {
			// Request only leaves for the current selected day
			$sql .= " AND '".$db->escape($year)."-".$db->escape($month)."-".$db->escape($day)."' BETWEEN x.date_debut AND x.date_fin";	// date_debut and date_fin are date without time
		} elseif ($mode == 'show_week') {
			// Restrict on current month (we get more, but we will filter later)
			$sql .= " AND date_debut < '".$db->idate(dol_get_last_day($year, $month))."'";
			$sql .= " AND date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
		} elseif ($mode == 'show_month') {
			// Restrict on current month
			$sql .= " AND date_debut <= '".$db->idate(dol_get_last_day($year, $month))."'";
			$sql .= " AND date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
		}
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$obj = $db->fetch_object($resql);

			$tmpEvent = array(
				'id' => $obj->rowid
			, 'title' => $obj->ref
			, 'allDay' => 1
			, 'start' => (empty($event->datep) ? '' : dol_print_date($obj->date_start, '%Y-%m-%d'))
			, 'end' => (empty($event->datef) ? '' : dol_print_date($obj->date_end, '%Y-%m-%d'))
			, 'url_title' => dol_buildpath('/holiday/card.php?id=' . $obj->rowid, 1)
			, 'editable' => $editable
			, 'color' => $color
			, 'isDarkColor' => isDarkColor($color)
			, 'colors' => $colors
			, 'note' => $obj->description
			, 'statut' => $obj->status
			, 'fk_soc' => null
			, 'fk_contact' => null
			, 'fk_user' => $obj->fk_user
			, 'TFk_user' => null
			, 'fk_project' => null
			, 'societe' => null
			, 'contact' => null
			, 'user' => $obj->fk_user
			, 'project' => null

			, 'project_order' => null
			, 'fk_project_order' => null

			, 'splitedfulldayevent' => null
			, 'fulldayevent' => 1
			, 'more' => ''
			,'moreclass' => 'family_holiday'
			);

			$TEvent[] = $tmpEvent;
		}
	}
	return $TEvent;

}

function isDarkColor($color) {
	global $conf;

	$lightness_swap = !getDolGlobalString('FULLCALENDAR_LIGTHNESS_SWAP') ? 150 : getDolGlobalString('FULLCALENDAR_LIGTHNESS_SWAP');

	$rgb = HTMLToRGB($color);
	$hsl = RGBToHSL($rgb);

	return ($hsl->lightness<$lightness_swap) ? 1 : 0;
}

function HTMLToRGB($htmlCode)
  {
    if(strpos($htmlCode, '#') === 0)
      $htmlCode = substr($htmlCode, 1);

    if (strlen($htmlCode) == 3)
    {
        $newhtmlCode = substr($htmlCode, 0, 1).substr($htmlCode, 0, 1).substr($htmlCode, 1, 1).substr($htmlCode, 1, 1).substr($htmlCode, 2, 1).substr($htmlCode, 2, 1);
      $htmlCode = $newhtmlCode;
    }

    $r = hexdec(substr($htmlCode, 0, 2));
    $g = hexdec(substr($htmlCode, 2, 2));
    $b = hexdec(substr($htmlCode, 4, 2));

    return $b + ($g << 0x8) + ($r << 0x10);
  }

function RGBToHSL($RGB) {
    $r = 0xFF & ($RGB >> 0x10);
    $g = 0xFF & ($RGB >> 0x8);
    $b = 0xFF & $RGB;

    $r = ((float)$r) / 255.0;
    $g = ((float)$g) / 255.0;
    $b = ((float)$b) / 255.0;

    $maxC = max($r, $g, $b);
    $minC = min($r, $g, $b);

    $l = ($maxC + $minC) / 2.0;

    if($maxC == $minC)
    {
      $s = 0;
      $h = 0;
    }
    else
    {
      if($l < .5)
      {
        $s = ($maxC - $minC) / ($maxC + $minC);
      }
      else
      {
        $s = ($maxC - $minC) / (2.0 - $maxC - $minC);
      }
      if($r == $maxC)
        $h = ($g - $b) / ($maxC - $minC);
      if($g == $maxC)
        $h = 2.0 + ($b - $r) / ($maxC - $minC);
      if($b == $maxC)
        $h = 4.0 + ($r - $g) / ($maxC - $minC);

      $h = $h / 6.0;
    }

    $h = (int)round(255.0 * $h);
    $s = (int)round(255.0 * $s);
    $l = (int)round(255.0 * $l);

    return (object) Array('hue' => $h, 'saturation' => $s, 'lightness' => $l);
  }


/**
 * Copié collé from Dolibarr "/comm/action/index.php"
 */
function completeWithExtEvent(&$TEvent, &$TSociete, &$TContact, &$TProject)
{
	global $conf,$db,$user,$langs;

	if (getDolGlobalString('AGENDA_DISABLE_EXT') && !empty($user->conf->AGENDA_DISABLE_EXT)) return;

	$listofextcals=array();

	if (!getDolGlobalString('AGENDA_EXT_NB')) $conf->global->AGENDA_EXT_NB=5;
	$MAXAGENDA= getDolGlobalString('AGENDA_EXT_NB');

	// Define list of external calendars (global admin setup)
	if (!getDolGlobalString('AGENDA_DISABLE_EXT'))
	{
		$i=0;
		while($i < $MAXAGENDA)
		{
			$i++;
			$source='AGENDA_EXT_SRC'.$i;
			$name='AGENDA_EXT_NAME'.$i;
			$offsettz='AGENDA_EXT_OFFSETTZ'.$i;
			$color='AGENDA_EXT_COLOR'.$i;
			$buggedfile='AGENDA_EXT_BUGGEDFILE'.$i;
			if ( getDolGlobalString($source) && getDolGlobalString($name))
			{
				// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
				$listofextcals[]=array('src'=>getDolGlobalString($source),'name'=>getDolGlobalString($name),'offsettz'=>getDolGlobalString($offsettz),'color'=>getDolGlobalString($color),'buggedfile'=>(isset($conf->global->buggedfile)?getDolGlobalString('buggedfile'):0));
			}
		}
	}

	// Define list of external calendars (user setup)
	if (empty($user->conf->AGENDA_DISABLE_EXT))
	{
		$i=0;
		while($i < $MAXAGENDA)
		{
			$i++;
			$source='AGENDA_EXT_SRC_'.$user->id.'_'.$i;
			$name='AGENDA_EXT_NAME_'.$user->id.'_'.$i;
			$offsettz='AGENDA_EXT_OFFSETTZ_'.$user->id.'_'.$i;
			$color='AGENDA_EXT_COLOR_'.$user->id.'_'.$i;
			$enabled='AGENDA_EXT_ENABLED_'.$user->id.'_'.$i;
			$buggedfile='AGENDA_EXT_BUGGEDFILE_'.$user->id.'_'.$i;
			if (! empty($user->conf->$source) && ! empty($user->conf->$name))
			{
				// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
				$listofextcals[] = array(
					'src' => isset($user->conf->$source) ? $user->conf->$source : null,
					'name' => isset($user->conf->$name) ? $user->conf->$name : null,
					'offsettz' => isset($user->conf->$offsettz) ? $user->conf->$offsettz : null,
					'color' => isset($user->conf->$color) ? $user->conf->$color : null,
					'buggedfile' => isset($user->conf->buggedfile) ? $user->conf->buggedfile : 0
				);}
		}
	}

	if (empty($listofextcals)) return;


	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


	$action=GETPOST('action','alpha');
	$year=GETPOST("year","int")?GETPOST("year","int"):date("Y");
	$month=GETPOST("month","int")?GETPOST("month","int"):date("m");
	$week=GETPOST("week","int")?GETPOST("week","int"):date("W");
	$day=GETPOST("day","int")?GETPOST("day","int"):0;
	if (empty($action) || $action=='show_month')
	{
		$prev = dol_get_prev_month($month, $year);
		$prev_year  = $prev['year'];
		$prev_month = $prev['month'];
		$next = dol_get_next_month($month, $year);
		$next_year  = $next['year'];
		$next_month = $next['month'];

		$max_day_in_prev_month = date("t",dol_mktime(0,0,0,$prev_month,1,$prev_year));  // Nb of days in previous month
		$max_day_in_month = date("t",dol_mktime(0,0,0,$month,1,$year));                 // Nb of days in next month
		// tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
		$tmpday = -date("w",dol_mktime(12,0,0,$month,1,$year,true))+2;		// date('w') is 0 fo sunday
		$tmpday+=((isset($conf->global->MAIN_START_WEEK)?getDolGlobalString('MAIN_START_WEEK'):1)-1);
		if ($tmpday >= 1) $tmpday -= 7;	// If tmpday is 0 we start with sunday, if -6, we start with monday of previous week.
		// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
		$firstdaytoshow=dol_mktime(0,0,0,$prev_month,$max_day_in_prev_month+$tmpday,$prev_year);
		$next_day=7 - ($max_day_in_month+1-$tmpday) % 7;
		if ($next_day < 6) $next_day+=7;
		$lastdaytoshow=dol_mktime(0,0,0,$next_month,$next_day,$next_year);
	}
	if ($action=='show_week')
	{
		$prev = dol_get_first_day_week($day, $month, $year);
		$prev_year  = $prev['prev_year'];
		$prev_month = $prev['prev_month'];
		$prev_day   = $prev['prev_day'];
		$first_day  = $prev['first_day'];
		$first_month= $prev['first_month'];
		$first_year = $prev['first_year'];

		$week = $prev['week'];

		$day = (int) $day;
		$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
		$next_year  = $next['year'];
		$next_month = $next['month'];
		$next_day   = $next['day'];

		// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
		$firstdaytoshow=dol_mktime(0,0,0,$first_month,$first_day,$first_year);
		$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 7, 'd');

		$max_day_in_month = date("t",dol_mktime(0,0,0,$month,1,$year));

		$tmpday = $first_day;
	}
	if ($action == 'show_day')
	{
		$prev = dol_get_prev_day($day, $month, $year);
		$prev_year  = $prev['year'];
		$prev_month = $prev['month'];
		$prev_day   = $prev['day'];
		$next = dol_get_next_day($day, $month, $year);
		$next_year  = $next['year'];
		$next_month = $next['month'];
		$next_day   = $next['day'];

		// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
		$firstdaytoshow=dol_mktime(0,0,0,$prev_month,$prev_day,$prev_year);
		$lastdaytoshow=dol_mktime(0,0,0,$next_month,$next_day,$next_year);
	}


	// Complete $eventarray with external import Ical
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/ical.class.php';
	foreach($listofextcals as $extcal)
	{
		$url=$extcal['src'];    // Example: https://www.google.com/calendar/ical/eldy10%40gmail.com/private-cde92aa7d7e0ef6110010a821a2aaeb/basic.ics
		$namecal = $extcal['name'];
		$offsettz = $extcal['offsettz'];
		$colorcal = $extcal['color'];
		$buggedfile = $extcal['buggedfile'];
		//print "url=".$url." namecal=".$namecal." colorcal=".$colorcal." buggedfile=".$buggedfile;
		$ical=new ICal();
		$ical->parse($url);

		// After this $ical->cal['VEVENT'] contains array of events, $ical->cal['DAYLIGHT'] contains daylight info, $ical->cal['STANDARD'] contains non daylight info, ...
		//var_dump($ical->cal); exit;
		$icalevents=array();
		if (is_array($ical->get_event_list())) $icalevents=array_merge($icalevents,$ical->get_event_list());        // Add $ical->cal['VEVENT']
		if (is_array($ical->get_freebusy_list())) $icalevents=array_merge($icalevents,$ical->get_freebusy_list());  // Add $ical->cal['VFREEBUSY']

		if (count($icalevents)>0)
		{
			// Duplicate all repeatable events into new entries
			$moreicalevents=array();
			foreach($icalevents as $icalevent)
			{
				if (isset($icalevent['RRULE']) && is_array($icalevent['RRULE'])) //repeatable event
				{
					//if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
					//if ($event->date_end_in_calendar > $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow-1);
					if ($icalevent['DTSTART;VALUE=DATE']) //fullday event
					{
						$datecurstart=dol_stringtotime($icalevent['DTSTART;VALUE=DATE'],1);
						$datecurend=dol_stringtotime($icalevent['DTEND;VALUE=DATE'],1)-1;  // We remove one second to get last second of day
					}
					else if (is_array($icalevent['DTSTART']) && ! empty($icalevent['DTSTART']['unixtime']))
					{
						$datecurstart=$icalevent['DTSTART']['unixtime'];
						$datecurend=$icalevent['DTEND']['unixtime'];
						if (! empty($ical->cal['DAYLIGHT']['DTSTART']) && $datecurstart)
						{
							//var_dump($ical->cal);
							$tmpcurstart=$datecurstart;
							$tmpcurend=$datecurend;
							$tmpdaylightstart=dol_mktime(0,0,0,1,1,1970,1) + (int) $ical->cal['DAYLIGHT']['DTSTART'];
							$tmpdaylightend=dol_mktime(0,0,0,1,1,1970,1) + (int) $ical->cal['STANDARD']['DTSTART'];
							//var_dump($tmpcurstart);var_dump($tmpcurend); var_dump($ical->cal['DAYLIGHT']['DTSTART']);var_dump($ical->cal['STANDARD']['DTSTART']);
							// Edit datecurstart and datecurend
							if ($tmpcurstart >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) $datecurstart-=((int) $ical->cal['DAYLIGHT']['TZOFFSETTO'])*36;
							else $datecurstart-=((int) $ical->cal['STANDARD']['TZOFFSETTO'])*36;
							if ($tmpcurend >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) $datecurend-=((int) $ical->cal['DAYLIGHT']['TZOFFSETTO'])*36;
							else $datecurend-=((int) $ical->cal['STANDARD']['TZOFFSETTO'])*36;
						}
						// datecurstart and datecurend are now GMT date
						//var_dump($datecurstart); var_dump($datecurend); exit;
					}
					else
					{
						// Not a recongized record
						dol_syslog("Found a not recognized repeatable record with unknown date start", LOG_ERR);
						continue;
					}
					//print 'xx'.$datecurstart;exit;

					$interval=(empty($icalevent['RRULE']['INTERVAL'])?1:$icalevent['RRULE']['INTERVAL']);
					$until=empty($icalevent['RRULE']['UNTIL'])?0:dol_stringtotime($icalevent['RRULE']['UNTIL'],1);
					$maxrepeat=empty($icalevent['RRULE']['COUNT'])?0:$icalevent['RRULE']['COUNT'];
					if ($until && ($until+($datecurend-$datecurstart)) < $firstdaytoshow) continue;  // We discard repeatable event that end before start date to show
					if ($datecurstart >= $lastdaytoshow) continue;                                   // We discard repeatable event that start after end date to show

					$numofevent=0;
					while (($datecurstart < $lastdaytoshow) && (empty($maxrepeat) || ($numofevent < $maxrepeat)))
					{
						if ($datecurend >= $firstdaytoshow)    // We add event
						{
							$newevent=$icalevent;
							unset($newevent['RRULE']);
							if ($icalevent['DTSTART;VALUE=DATE'])
							{
								$newevent['DTSTART;VALUE=DATE']=dol_print_date($datecurstart,'%Y%m%d');
								$newevent['DTEND;VALUE=DATE']=dol_print_date($datecurend+1,'%Y%m%d');
							}
							else
							{
								$newevent['DTSTART']=$datecurstart;
								$newevent['DTEND']=$datecurend;
							}
							$moreicalevents[]=$newevent;
						}
						// Jump on next occurence
						$numofevent++;
						$savdatecurstart=$datecurstart;
						if ($icalevent['RRULE']['FREQ']=='DAILY')
						{
							$datecurstart=dol_time_plus_duree($datecurstart, $interval, 'd');
							$datecurend=dol_time_plus_duree($datecurend, $interval, 'd');
						}
						if ($icalevent['RRULE']['FREQ']=='WEEKLY')
						{
							$datecurstart=dol_time_plus_duree($datecurstart, $interval, 'w');
							$datecurend=dol_time_plus_duree($datecurend, $interval, 'w');
						}
						elseif ($icalevent['RRULE']['FREQ']=='MONTHLY')
						{
							$datecurstart=dol_time_plus_duree($datecurstart, $interval, 'm');
							$datecurend=dol_time_plus_duree($datecurend, $interval, 'm');
						}
						elseif ($icalevent['RRULE']['FREQ']=='YEARLY')
						{
							$datecurstart=dol_time_plus_duree($datecurstart, $interval, 'y');
							$datecurend=dol_time_plus_duree($datecurend, $interval, 'y');
						}
						// Test to avoid infinite loop ($datecurstart must increase)
						if ($savdatecurstart >= $datecurstart)
						{
							dol_syslog("Found a rule freq ".$icalevent['RRULE']['FREQ']." not managed by dolibarr code. Assume 1 week frequency.", LOG_ERR);
							$datecurstart+=3600*24*7;
							$datecurend+=3600*24*7;
						}
					}
				}
			}
			$icalevents=array_merge($icalevents,$moreicalevents);

			// Loop on each entry into cal file to know if entry is qualified and add an ActionComm into $eventarray
			foreach($icalevents as $icalevent)
			{
				//var_dump($icalevent);

				//print $icalevent['SUMMARY'].'->'.var_dump($icalevent).'<br>';exit;
				if (! empty($icalevent['RRULE'])) continue;    // We found a repeatable event. It was already split into unitary events, so we discard general rule.

				// Create a new object action
				$event=new ActionComm($db);
				// Gestion changements v13
                // Gestion de la rétrocompatibilité

				$event->contactid = 1;
                $eventContactId = $event->contact_id;
                if (empty ($eventContactId)) $eventContactId = $event->contactid;

				$addevent = false;
				if (isset($icalevent['DTSTART;VALUE=DATE'])) // fullday event
				{
					// For full day events, date are also GMT but they wont but converted using tz during output
					$datestart=dol_stringtotime($icalevent['DTSTART;VALUE=DATE'],1);
					$dateend=dol_stringtotime($icalevent['DTEND;VALUE=DATE'],1)-1;  // We remove one second to get last second of day
					//print 'x'.$datestart.'-'.$dateend;exit;
					//print dol_print_date($dateend,'dayhour','gmt');
					$event->fulldayevent=true;
					$addevent=true;
				}
				elseif (!is_array($icalevent['DTSTART'])) // not fullday event (DTSTART is not array. It is a value like '19700101T000000Z' for 00:00 in greenwitch)
				{
					$datestart=$icalevent['DTSTART'];
					$dateend=$icalevent['DTEND'];

					$datestart+=+($offsettz * 3600);
					$dateend+=+($offsettz * 3600);

					$addevent=true;
					//var_dump($offsettz);
					//var_dump(dol_print_date($datestart, 'dayhour', 'gmt'));
				}
				elseif (isset($icalevent['DTSTART']['unixtime']))	// File contains a local timezone + a TZ (for example when using bluemind)
				{
					$datestart=$icalevent['DTSTART']['unixtime'];
					$dateend=$icalevent['DTEND']['unixtime'];

					$datestart+=+($offsettz * 3600);
					$dateend+=+($offsettz * 3600);

					// $buggedfile is set to uselocalandtznodaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtznodaylight'
					if ($buggedfile === 'uselocalandtznodaylight')	// unixtime is a local date that does not take daylight into account, TZID is +1 for example for 'Europe/Paris' in summer instead of 2
					{
						// TODO
					}
					// $buggedfile is set to uselocalandtzdaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtzdaylight' (for example with bluemind)
					if ($buggedfile === 'uselocalandtzdaylight')	// unixtime is a local date that does take daylight into account, TZID is +2 for example for 'Europe/Paris' in summer
					{
						$localtzs = new DateTimeZone(preg_replace('/"/','',$icalevent['DTSTART']['TZID']));
						$localtze = new DateTimeZone(preg_replace('/"/','',$icalevent['DTEND']['TZID']));
						$localdts = new DateTime(dol_print_date($datestart,'dayrfc','gmt'), $localtzs);
						$localdte = new DateTime(dol_print_date($dateend,'dayrfc','gmt'), $localtze);
						$tmps=-1*$localtzs->getOffset($localdts);
						$tmpe=-1*$localtze->getOffset($localdte);
						$datestart+=$tmps;
						$dateend+=$tmpe;
						//var_dump($datestart);
					}
					$addevent=true;
				}

				if ($addevent)
				{
					$event->id=$icalevent['UID'];
					$event->icalname=$namecal;
					$event->icalcolor=$colorcal;
					$usertime=0;    // We dont modify date because we want to have date into memory datep and datef stored as GMT date. Compensation will be done during output.
					$event->datep=$datestart+$usertime;
					$event->datef=$dateend+$usertime;
					$event->type_code="ICALEVENT";

					if($icalevent['SUMMARY']) $event->libelle=$icalevent['SUMMARY'];
					elseif($icalevent['DESCRIPTION']) $event->libelle=dol_nl2br($icalevent['DESCRIPTION'],1);
					else $event->libelle = $langs->trans("ExtSiteNoLabel");

					$event->date_start_in_calendar=$event->datep;

					if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
					else $event->date_end_in_calendar=$event->datep;

					// Define ponctual property
					if ($event->date_start_in_calendar == $event->date_end_in_calendar)
					{
						$event->ponctuel=1;
						//print 'x'.$datestart.'-'.$dateend;exit;
					}

					// Add event into $eventarray if date range are ok.
					if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow)
					{
						//print 'x'.$datestart.'-'.$dateend;exit;
						//print 'x'.$datestart.'-'.$dateend;exit;
						//print 'x'.$datestart.'-'.$dateend;exit;
						// This record is out of visible range
					}
					else
					{
						if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
						if ($event->date_end_in_calendar >= $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow - 1);

						// Add an entry in actionarray for each day
						$daycursor=$event->date_start_in_calendar;
						$annee = date('Y',$daycursor);
						$mois = date('m',$daycursor);
						$jour = date('d',$daycursor);

						// Loop on each day covered by action to prepare an index to show on calendar
						$loop=true; $j=0;
						// daykey must be date that represent day box in calendar so must be a user time
						$daykey=dol_mktime(0,0,0,$mois,$jour,$annee);
						$daykeygmt=dol_mktime(0,0,0,$mois,$jour,$annee,true,0);
						do
						{
							//if ($event->fulldayevent) print dol_print_date($daykeygmt,'dayhour','gmt').'-'.dol_print_date($daykey,'dayhour','gmt').'-'.dol_print_date($event->date_end_in_calendar,'dayhour','gmt').' ';


							$editable = false;
							if(($user->id == $event->userownerid) || $user->hasRight('agenda', 'allactions', 'create')) {
								$editable = true;
							}

							//$eventarray[$daykey][]=$event;
							$TEvent[]=array(
								'id'=>$event->id
								,'title'=>(!empty($event->label) ? $event->label : $event->libelle) . "\n(".$event->icalname.')'
								,'allDay'=>(bool)($event->fulldayevent)
								,'start'=>(empty($event->datep) ? '' : date('Y-m-d H:i:s',(int)$event->datep))
								,'end'=>(empty($event->datef) ? '' : date('Y-m-d H:i:s',(int)$event->datef))
								,'url'=>dol_buildpath('/comm/action/card.php?id='.$event->id,1)
								,'editable'=>$editable
								,'color'=>'#'.$colorcal
								,'isDarkColor'=>isDarkColor($color)
								,'colors'=>''
								,'note'=>$event->note
								,'statut'=>$event->getLibStatut(3)
								,'fk_soc'=>$event->socid
								,'fk_contact'=>$eventContactId
								,'fk_user'=>$event->userownerid
								,'fk_project'=>$event->fk_project
								,'societe'=>(!empty($TSociete[$event->socid]) ? $TSociete[$event->socid] : '')
								,'contact'=>(!empty($TContact[$eventContactId]) ? $TContact[$eventContactId] : '')
								,'user'=>''
								,'project'=>(!empty($TProject[$event->fk_project]) ? $TProject[$event->fk_project] : '')
								,'more'=>''
								,'moreclass' => 'family_ext'.md5($event->icalname)
							);

							$daykey+=60*60*24;  $daykeygmt+=60*60*24;   // Add one day
							if (($event->fulldayevent ? $daykeygmt : $daykey) > $event->date_end_in_calendar) $loop=false;
						}
						while ($loop);
					}

				}
			}
		}
	}

}
/*
 * convert stored hours with $form->select_date
 */
function _convertTimestampLocalToNoLocalSecond($timestamp)
{
	global $db;
	$date = dol_print_date($timestamp, '%Y-%m-%d %H:%M:%S');
	return $db->jdate($date, true);
}

/**
 * function qui ajoute les notif agenda pour chaque user assigné à l'event
 * @param ActionComm $a
 * @param string $mode
 */
function addReminders($a, $mode = 'create')
{
	global $db, $conf, $user;

	if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER'))
	{
		$setReminder = GETPOST('setReminder');
		$reminderValue = GETPOST('reminderValue');
		$reminderUnit = GETPOST('reminderUnit');
		$reminderType = GETPOST('reminderType');
		$reminderTemplate = GETPOST('reminderTemplate');

		if ($mode == 'drop')
		{
			// aller rechercher les infos de reminder attaché
			$sqlremind = "SELECT acr.rowid FROM ".MAIN_DB_PREFIX."actioncomm_reminder acr WHERE acr.fk_actioncomm = ".$a->id;
			$resql = $db->query($sqlremind);
			if ($resql && $db->num_rows($resql))
			{
				$setReminder = true;

				$obj = $db->fetch_object($resql);

				$actionCommReminder = new ActionCommReminder($db);
				$res = $actionCommReminder->fetch($obj->rowid);
				if ($res > 0)
				{
					$reminderValue = $actionCommReminder->offsetvalue;
					$reminderUnit = $actionCommReminder->offsetunit;
					$reminderType = $actionCommReminder->typeremind;
					$reminderTemplate = $actionCommReminder->fk_email_template;
				}
			}


		}

		if ($mode == 'update' || $mode == 'drop')
		{
			// delete reminders to recreate them
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_reminder WHERE fk_actioncomm = ".$a->id;
			$resql = $db->query($sql);
		}

		if ($setReminder)
		{
			$actionCommReminder = new ActionCommReminder($db);

			if ($reminderUnit == 'minute'){
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'i');
			} elseif ($reminderUnit == 'hour'){
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'h');
			} elseif ($reminderUnit == 'day') {
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'd');
			} elseif ($reminderUnit == 'week') {
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'w');
			} elseif ($reminderUnit == 'month') {
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'm');
			} elseif ($reminderUnit == 'year') {
				$dateremind = dol_time_plus_duree($a->datep, -$reminderValue, 'y');
			}

			$actionCommReminder->dateremind = $dateremind;
			$actionCommReminder->typeremind = $reminderType;
			$actionCommReminder->offsetunit = $reminderUnit;
			$actionCommReminder->offsetvalue = $reminderValue;
			$actionCommReminder->status = $actionCommReminder::STATUS_TODO;
			$actionCommReminder->fk_actioncomm = $a->id;
			if ($reminderType == 'email') $actionCommReminder->fk_email_template = $reminderTemplate;

			foreach ($a->userassigned as $userassigned)
			{
				$actionCommReminder->fk_user = $userassigned['id'];
				$res = $actionCommReminder->create($user);
			}
		}
	}
}
