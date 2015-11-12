<?php

	require '../config.php';
	dol_include_once('/comm/action/class/actioncomm.class.php');
	dol_include_once('/contact/class/contact.class.php');

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
			
			$a->userownerid = $user->id;
			$a->type_code = 'AC_OTH';
			
			print $a->add($user);
			
			break;
	}
	
	
function _events($date_start, $date_end) {
	global $db,$conf,$langs,$user;
	
	
	$pid=GETPOST("projectid","int",3);
	$status=GETPOST("status");
	$type=GETPOST("type");
	$maxprint=(isset($_GET["maxprint"])?GETPOST("maxprint"):$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
	$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':'');
		
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
	$sql.= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm as ca, '.MAIN_DB_PREFIX."actioncomm as a";
	$sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (a.fk_user_action=u.rowid )';
	
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.=", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
	if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
	$sql.= ' WHERE a.fk_action = ca.id';
	$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';
	if ($actioncode) $sql.=" AND ca.code='".$db->escape($actioncode)."'";
	if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
	if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
	
    $sql.= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate($t_start-(60*60*24*7))."'";   // Start 7 days before
    $sql.= " AND '".$db->idate($t_end+(60*60*24*10))."')";            // End 7 days after + 3 to go from 28 to 31
    $sql.= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate($t_start-(60*60*24*7))."'";
    $sql.= " AND '".$db->idate($t_end+(60*60*24*10))."')";
    $sql.= " OR ";
    $sql.= " (a.datep < '".$db->idate($t_start-(60*60*24*7))."'";
    $sql.= " AND a.datep2 > '".$db->idate($t_end+(60*60*24*10))."')";
    $sql.= ')';

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
	
	$res= $db->query($sql);
	//var_dump($db);

	$TSociete = array();
	$TContact = array();

	while($obj=$db->fetch_object($res)) {
		
		if($obj->fk_soc>0 && !isset($TSociete[$obj->fk_soc])) {
			$societe = new Societe($db);
			$societe->fetch($obj->fk_soc);
			$TSociete[$obj->fk_soc]  = $societe->getNomUrl(1);

		}
		if($obj->fk_contact && !isset($TContact[$obj->fk_contact])) {
                        $contact = new Contact($db);
                        $contact->fetch($obj->fk_contact);
                        $TContact[$obj->fk_contact]  = $contact->getNomUrl(1);

                }
		
		$event = new ActionComm($db);
		$event->fetch($obj->id);

		$TEvent[]=array(
			'id'=>$event->id
			,'title'=>$event->label
			,'allDay'=>(bool)($obj->fulldayevent)
			,'start'=>$obj->datep
			,'end'=>$obj->datep2
			,'url'=>dol_buildpath('/comm/action/card.php?id='.$obj->id,1)
			,'editable'=>true
			,'color'=>($obj->color ? '#'.$obj->color : '') 
			,'note'=>$event->getLibStatut(3).' '.$obj->note
			,'fk_soc'=>$obj->fk_soc
			,'fk_contact'=>$obj->fk_contact
			,'societe'=>(!empty($TSociete[$obj->fk_soc]) ? $TSociete[$obj->fk_soc] : '')
			,'contact'=>(!empty($TContact[$obj->fk_contact]) ? $TContact[$obj->fk_contact] : '')
		);
		
	}
	
	
	
	return $TEvent;
	
}
