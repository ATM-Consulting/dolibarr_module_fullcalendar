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

			$datep = date('H',strtotime(GETPOST('date')));
			if($datep == '00' && !empty($conf->global->FULLCALENDAR_SHOW_THIS_HOURS) ){
				$a->datep = strtotime('+'.substr($conf->global->FULLCALENDAR_SHOW_THIS_HOURS,0,1).' hour',strtotime(GETPOST('date')));
			}
			else{
				if($datep=='00') $a->fulldayevent = 1;

				$a->datep = strtotime(GETPOST('date'));
			}
			$a->datef = strtotime('+2 hour',$a->datep);

			$a->userownerid = GETPOST('fk_user') ? GETPOST('fk_user') : $user->id;
			$a->type_code = GETPOST('type_code') ? GETPOST('type_code') : 'AC_OTH';
			$a->socid = GETPOST('fk_soc');
			$a->contactid = GETPOST('fk_contact');

			$a->fk_project = GETPOST('fk_project','int');
			$a->percentage = -1; // Non applicable

			$moreParams = GETPOST('moreParams');
			$moreParams = explode(',', $moreParams);
			$TParam = array();
			foreach ($moreParams as $param)
			{
				$a->_{$param} = GETPOST($param);
			}
			//var_dump($conf->global->FULLCALENDAR_SHOW_THIS_HOURS,GETPOST('date'),$a);exit;
			$res = $a->add($user);
			$a->update($user);
			print $res;

			break;
	}


function _events($date_start, $date_end) {
	global $db,$conf,$langs,$user,$hookmanager;

	$hookmanager->initHooks(array('agenda'));

	$pid=GETPOST("projectid","int",3);
	$status=GETPOST("status");
	$type=GETPOST("type");
	$state_id = GETPOST('state_id');

	$maxprint=(GETPOST("maxprint")?GETPOST("maxprint"):$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);

	//First try with GETPOST(array) (I don't know when it can be an array but why not)
	$actioncode=GETPOST("actioncode", "array", 3)?GETPOST("actioncode", "array", 3):(GETPOST("actioncode")=='0'?'0':'');

	//If empty then try GETPOST(alpha) (this one works with comm/action/index.php
	if(empty($actioncode)) {
		$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':'');
		$actioncode=array($actioncode);
	}
	if(empty($actioncode)) {
		$actioncode = array();
	}

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
	if (!empty($conf->global->FULLCALENDAR_FILTER_ON_STATE) && !empty($state_id))
	{
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
	}

	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
	// We must filter on assignement table
	if ($filtert > 0 || $usergroup > 0) $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON (ar.fk_actioncomm = a.id)";
	if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
	$sql.= ' WHERE 1=1';
	$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';
	if ($actioncode) $sql.=" AND ca.code IN ('".implode("','", $actioncode)."')";
	if ($conf->global->DONT_SHOW_AUTO_EVENT && strpos(implode(',', $actioncode),'AC_OTH_AUTO') == false) $sql.=" AND ca.code != 'AC_OTH_AUTO'";
	if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
	if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
	if (!empty($conf->global->FULLCALENDAR_FILTER_ON_STATE) && !empty($state_id)) $sql.= ' AND (s.fk_departement = '.$state_id.' OR sp.fk_departement = '.$state_id.')';
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
		$event->fetch_userassigned();

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

		$TUserassigned = array();
		$TColor=array();

		if($event->color && empty($conf->global->FULLCALENDAR_USE_ASSIGNED_COLOR)) $TColor[] = '#'.$event->color;

		if(!empty($conf->global->FULLCALENDAR_SHOW_AFFECTED_USER) ) {

			$userownerid = (int)$event->userownerid;

			if( $userownerid>0 && !isset($TUser[$userownerid])) {
	            $u = new User($db);
	            $u->fetch($userownerid);
	            $TUser[$userownerid]  = $u;
			}
			$TUserassigned[$userownerid] = 	$TUser[$userownerid]->getNomUrl(1);
        }

		if(!empty($conf->global->FULLCALENDAR_SHOW_PROJECT) && $event->fk_project>0 && !isset($TProject[$event->fk_project])) {
            $p = new Project($db);
            $p->fetch($event->fk_project);
            $TProject[$event->fk_project]  = $p->getNomUrl(1);

        }


		if(!empty($conf->global->FULLCALENDAR_SHOW_AFFECTED_USER) && !empty($event->userassigned)) {

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
		if(($user->id == $event->userownerid) || $user->rights->agenda->allactions->create) $editable = true;
//background: linear-gradient(to bottom, #1e5799 0%,#2989d8 25%,#207cca 67%,#7db9e8 100%);
		//$colors = implode(',',$TColor);
		$colors='';

		$color='';

		if(!empty($TColor)) {

			$color = $TColor[0];

			if(!empty($conf->global->FULLCALENDAR_SHOW_ALL_ASSIGNED_COLOR) && count($TColor)>1) {
				$colors = 'linear-gradient(to right ';
				foreach($TColor as $c) {

					$colors.= ','.$c;

				}

				$colors.=')';

			}

		}

		$TEvent[]=array(
			'id'=>$event->id
			,'title'=>$event->label
			,'allDay'=>(bool)($event->fulldayevent)
			,'start'=>(empty($event->datep) ? '' : date('Y-m-d H:i:s',(int)$event->datep))
			,'end'=>(empty($event->datef) ? '' : date('Y-m-d H:i:s',(int)$event->datef))
			,'url'=>dol_buildpath('/comm/action/card.php?id='.$event->id,1)
			,'editable'=>$editable
			,'color'=>$color
			,'isDarkColor'=>isDarkColor($color)
			,'colors'=>$colors
			,'note'=>$event->note
			,'statut'=>$event->getLibStatut(3)
			,'fk_soc'=>$event->socid
			,'fk_contact'=>$event->contactid
			,'fk_user'=>$event->userownerid
			,'fk_project'=>$event->fk_project
			,'societe'=>(!empty($TSociete[$event->socid]) ? $TSociete[$event->socid] : '')
			,'contact'=>(!empty($TContact[$event->contactid]) ? $TContact[$event->contactid] : '')
			,'user'=>(!empty($TUserassigned) ? implode(', ',$TUserassigned) : '')
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

function isDarkColor($color) {
	global $conf;

	$lightness_swap = empty($conf->global->FULLCALENDAR_LIGTHNESS_SWAP) ? 150 : $conf->global->FULLCALENDAR_LIGTHNESS_SWAP;

	$rgb = HTMLToRGB($color);
	$hsl = RGBToHSL($rgb);

	return ($hsl->lightness<$lightness_swap) ? 1 : 0;
}

function HTMLToRGB($htmlCode)
  {
    if($htmlCode[0] == '#')
      $htmlCode = substr($htmlCode, 1);

    if (strlen($htmlCode) == 3)
    {
      $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
    }

    $r = hexdec($htmlCode[0] . $htmlCode[1]);
    $g = hexdec($htmlCode[2] . $htmlCode[3]);
    $b = hexdec($htmlCode[4] . $htmlCode[5]);

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