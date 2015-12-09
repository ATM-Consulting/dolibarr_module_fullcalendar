<?php

	require '../config.php';
	dol_include_once('/comm/action/class/actioncomm.class.php');
	dol_include_once('/contact/class/contact.class.php');
	dol_include_once('/projet/class/project.class.php');

	$langs->load("agenda");
	$langs->load("other");
	$langs->load("commercial");
	
	$get=GETPOST('get');
	$put=GETPOST('put');
	
	
	if(empty($get) && empty($put)) $get = 'events';
	
	switch ($get) {
		case 'events':
			$start = GETPOST('start');
			$end = GETPOST('end');	
			$year = GETPOST('year');
			$month = GETPOST('month');
			$day = GETPOST('day');			

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
			__out($TEvent, 'json');
				
					
			break;
		default:
			
			break;
	}
	
	
	switch($put){
		case 'event-move':
			
			$a=new ActionComm($db);
			if($a->fetch(GETPOST('id'))>0) {
				$a->fetch_userassigned();
				
				$TData = $_REQUEST['data'];
				
				if(!empty($TData['minutes'])) {
					$a->datep = strtotime($TData['minutes'].' minute', $a->datep);
					$a->datef = strtotime($TData['minutes'].' minute', $a->datef);
				}
				
				if(!empty($TData['hours'])) {
					$a->datep = strtotime($TData['hours'].' hour', $a->datep);
					$a->datef = strtotime($TData['hours'].' hour', $a->datef);
				}
				
				if(!empty($TData['days'])) {
					$a->datep = strtotime($TData['days'].' day', $a->datep);
					$a->datef = strtotime($TData['days'].' day', $a->datef);
				}
				
				$res = $a->update($user);
				
				
			}	
			
			
			break;
		
		case 'event-resize':
			$a=new ActionComm($db);
			if($a->fetch(GETPOST('id'))>0) {
				$a->fetch_userassigned();
				
				$TData = $_REQUEST['data'];
				
				if(!empty($TData['minutes'])) {
					if(empty($a->datef))$a->datef = $a->datep;
					$a->datef = strtotime($TData['minutes'].' minute', $a->datef);
				}
				
				if(!empty($TData['hours'])) {
					if(empty($a->datef))$a->datef = $a->datep;
					$a->datef = strtotime($TData['hours'].' hour', $a->datef);
				}
				
				if(!empty($TData['days'])) {
					if(empty($a->datef))$a->datef = $a->datep;
					$a->datef = strtotime($TData['days'].' day', $a->datef);
				}
				
				$res = $a->update($user);
				
				
			}	
			
			
			
			break;
		
		case 'event':
			$a=new ActionComm($db);
			$a->label = GETPOST('label');
			$a->note= GETPOST('note');
			
			$a->datep = strtotime(GETPOST('date'));
			
			$a->userownerid = GETPOST('fk_user') ? GETPOST('fk_user') : $user->id;
			$a->type_code = GETPOST('type_code') ? GETPOST('type_code') : 'AC_OTH';
			$a->socid = GETPOST('fk_soc');
			$a->contactid = GETPOST('fk_contact');
			
			$a->fk_project = GETPOST('fk_project','int');
			
			$moreParams = GETPOST('moreParams');
			$moreParams = explode(',', $moreParams);
			$TParam = array();
			foreach ($moreParams as $param)
			{
				$a->_{$param} = GETPOST($param);
			}
			
			print $a->add($user);
			
			break;
	}
	
	
function _events($date_start, $date_end) {
	global $db,$conf,$langs,$user,$hookmanager;
	
	$hookmanager->initHooks(array('agenda'));
	
	$pid=GETPOST("projectid","int",3);
	$status=GETPOST("status");
	$type=GETPOST("type");
	
	$maxprint=(GETPOST("maxprint")?GETPOST("maxprint"):$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
	//$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':'');
	$actioncode=GETPOST("actioncode", "array", 3)?GETPOST("actioncode", "array", 3):(GETPOST("actioncode")=='0'?'0':'');
	
	$filter=GETPOST("filter",'',3);
	$filtert = GETPOST("usertodo","int",3)?GETPOST("usertodo","int",3):GETPOST("filtert","int",3);
	$usergroup = GETPOST("usergroup","int",3);
	$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;

	if (empty($filtert) && empty($conf->global->AGENDA_ALL_CALENDARS))
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
	$sql.= ' a.fk_soc, a.fk_contact,u.color,a.note,';
	$sql.= ' ca.code as type_code, ca.libelle as type_label';
	$sql.= ' FROM '.MAIN_DB_PREFIX."actioncomm as a";
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm as ca ON (a.fk_action = ca.id)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (a.fk_user_action=u.rowid )';
	
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON (ar.fk_actioncomm = a.id)";
	if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
	$sql.= ' WHERE 1';
	$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';
	if ($actioncode) $sql.=" AND ca.code IN ('".implode("','", $actioncode)."')";
	if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
	if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
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
	if ($status == '-1') { $sql.= " AND a.percent = -1"; }	// Not applicable
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
	
	$TEvent=array();
	if(isset($_REQUEST['DEBUG'])) print $sql;
	
	$res= $db->query($sql);
	//var_dump($db);

	$TSociete = array();
	$TContact = array();
	$TUser = array();
	$TProject = array();

	$TEventObject=array();
	while($obj=$db->fetch_object($res)) {
		$event = new ActionComm($db);
		$event->fetch($obj->id);
		
		$event->color = $obj->color;
		
		$TEventObject[] = $event;
	}
	
	foreach($TEventObject as &$event) {
		
		if($event->socid>0 && !isset($TSociete[$event->socid])) {
			$societe = new Societe($db);
			$societe->fetch($event->socid);
			$TSociete[$event->socid]  = $societe->getNomUrl(1);

		}
		if($event->contactid>0 && !isset($TContact[$event->contactid])) {
            $contact = new Contact($db);
            $contact->fetch($event->contactid);
            $TContact[$event->contactid]  = $contact->getNomUrl(1);

        }
		
		if(!empty($conf->global->FULLCALENDAR_SHOW_AFFECTED_USER) && $event->userownerid>0 && !isset($TUser[$event->userownerid])) {
            $u = new User($db);
            $u->fetch($event->userownerid);
            $TUser[$event->userownerid]  = $u->getNomUrl(1);

        }
		
		if(!empty($conf->global->FULLCALENDAR_SHOW_PROJECT) && $event->fk_project>0 && !isset($TProject[$event->fk_project])) {
            $p = new Project($db);
            $p->fetch($event->fk_project);
            $TProject[$event->fk_project]  = $p->getNomUrl(1);

        }
		
		
		

		$editable = false;
		if(($user->id == $event->userownerid) || $user->rights->agenda->allactions->create) $editable = true;

		$TEvent[]=array(
			'id'=>$event->id
			,'title'=>$event->label
			,'allDay'=>(bool)($event->fulldayevent)
			,'start'=>(empty($event->datep) ? '' : date('Y-m-d H:i:s',(int)$event->datep))
			,'end'=>(empty($event->datef) ? '' : date('Y-m-d H:i:s',(int)$event->datef))
			,'url'=>dol_buildpath('/comm/action/card.php?id='.$event->id,1)
			,'editable'=>$editable
			,'color'=>($event->color ? '#'.$event->color : '') 
			,'note'=>$event->note
			,'statut'=>$event->getLibStatut(3)
			,'fk_soc'=>$event->socid
			,'fk_contact'=>$event->contactid
			,'fk_user'=>$event->userownerid
			,'fk_project'=>$event->fk_project
			,'societe'=>(!empty($TSociete[$event->socid]) ? $TSociete[$event->socid] : '')
			,'contact'=>(!empty($TContact[$event->contactid]) ? $TContact[$event->contactid] : '')
			,'user'=>(!empty($TUser[$event->userownerid]) ? $TUser[$event->userownerid] : '')
			,'project'=>(!empty($TProject[$event->fk_project]) ? $TProject[$event->fk_project] : '')
			,'more'=>''
		);
		
	}
		$use_workstation_color=null;
		if(GETPOST('use_workstation_color'))$use_workstation_color=1;
	//TODO getCalendarEvents compatbile standard
	// Complete $eventarray with events coming from external module
	$parameters=array('use_workstation_color'=>$use_workstation_color,'sql'=>$sql); $action = 'getEvents';
	$reshook=$hookmanager->executeHooks('updateFullcalendarEvents',$parameters,$TEvent,$action);
	if (! empty($hookmanager->resArray['eventarray'])) $TEvent=array_merge($TEvent, $hookmanager->resArray['eventarray']);
	
	return $TEvent;
	
}
